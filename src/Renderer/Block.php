<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;
use Dompdf\Helpers;

/**
 * Renders block frames
 *
 * @package dompdf
 */
class Block extends AbstractRenderer
{

    /**
     * @param Frame $frame
     */
    function render(Frame $frame)
    {
        $style = $frame->get_style();
        $node = $frame->get_node();
        $dompdf = $this->_dompdf;
        $options = $dompdf->getOptions();

        $this->_set_opacity($frame->get_opacity($style->opacity));

        [$x, $y, $w, $h] = $frame->get_border_box();

        if ($node->nodeName === "body") {
            $h = $frame->get_containing_block("h") - (float)$style->length_in_pt([
                        $style->margin_top,
                        $style->border_top_width,
                        $style->border_bottom_width,
                        $style->margin_bottom],
                    (float)$style->length_in_pt($style->width));
        }

        $border_box = [$x, $y, $w, $h];

        // Draw our background, border and content
        $this->_render_background($frame, $border_box);
        $this->_render_border($frame, $border_box);
        $this->_render_outline($frame, $border_box);

        // Handle anchors & links
        if ($node->nodeName === "a" && $href = $node->getAttribute("href")) {
            $href = Helpers::build_url($dompdf->getProtocol(), $dompdf->getBaseHost(), $dompdf->getBasePath(), $href);
            $this->_canvas->add_link($href, $x, $y, (float)$w, (float)$h);
        }

        if ($options->getDebugLayout()) {
            if ($options->getDebugLayoutBlocks()) {
                $debug_border_box = $frame->get_border_box();
                $this->_debug_layout([$debug_border_box['x'], $debug_border_box['y'], (float)$debug_border_box['w'], (float)$debug_border_box['h']], "red");
                if ($options->getDebugLayoutPaddingBox()) {
                    $debug_padding_box = $frame->get_padding_box();
                    $this->_debug_layout([$debug_padding_box['x'], $debug_padding_box['y'], (float)$debug_padding_box['w'], (float)$debug_padding_box['h']], "red", [0.5, 0.5]);
                }
            }

            if ($options->getDebugLayoutLines() && $frame->get_decorator()) {
                foreach ($frame->get_decorator()->get_line_boxes() as $line) {
                    $frame->_debug_layout([$line->x, $line->y, $line->w, $line->h], "orange");
                }
            }
        }

        $id = $frame->get_node()->getAttribute("id");
        if (strlen($id) > 0) {
            $this->_canvas->add_named_dest($id);
        }
    }

    /**
     * @param Frame $frame
     * @param float[] $border_box
     */
    protected function _render_background(Frame $frame, array $border_box): void
    {
        $style = $frame->get_style();
        [$x, $y, $w, $h] = $border_box;

        if ($style->has_border_radius()) {
            [$tl, $tr, $br, $bl] = $style->resolve_border_radius($border_box);
            $this->_canvas->clipping_roundrectangle($x, $y, (float) $w, (float) $h, $tl, $tr, $br, $bl);
        }

        if (($bg = $style->background_color) !== "transparent") {
            $this->_canvas->filled_rectangle($x, $y, (float) $w, (float) $h, $bg);
        }

        if (($url = $style->background_image) && $url !== "none") {
            $this->_background_image($url, $x, $y, (float) $w, (float) $h, $style);
        }

        if ($style->has_border_radius()) {
            $this->_canvas->clipping_end();
        }
    }

    /**
     * @param Frame $frame
     * @param float[] $border_box
     * @param string $corner_style
     */
    protected function _render_border(Frame $frame, array $border_box, string $corner_style = "bevel"): void
    {
        $style = $frame->get_style();
        $bp = $style->get_border_properties();
        [$x, $y, $w, $h] = $border_box;
        [$tl, $tr, $br, $bl] = $style->resolve_border_radius($border_box);

        // Short-cut: If all the borders are "solid" with the same color and style, and no radius, we'd better draw a rectangle
        if (
            in_array($bp["top"]["style"], ["solid", "dashed", "dotted"]) &&
            $bp["top"] == $bp["right"] &&
            $bp["right"] == $bp["bottom"] &&
            $bp["bottom"] == $bp["left"] &&
            !$style->has_border_radius()
        ) {
            $props = $bp["top"];
            if ($props["color"] === "transparent" || $props["width"] <= 0) {
                return;
            }

            $width = (float)$style->length_in_pt($props["width"]);
            $pattern = $this->_get_dash_pattern($props["style"], $width);
            $this->_canvas->rectangle($x + $width / 2, $y + $width / 2, (float)$w - $width, (float)$h - $width, $props["color"], $width, $pattern);
            return;
        }

        // Do it the long way
        $widths = [
            (float)$style->length_in_pt($bp["top"]["width"]),
            (float)$style->length_in_pt($bp["right"]["width"]),
            (float)$style->length_in_pt($bp["bottom"]["width"]),
            (float)$style->length_in_pt($bp["left"]["width"])
        ];

        foreach ($bp as $side => $props) {
            list($x, $y, $w, $h) = $border_box;
            $length = 0;
            $r1 = 0;
            $r2 = 0;

            if (!$props["style"] ||
                $props["style"] === "none" ||
                $props["width"] <= 0 ||
                $props["color"] == "transparent"
            ) {
                continue;
            }

            switch ($side) {
                case "top":
                    $length = (float)$w;
                    $r1 = $tl;
                    $r2 = $tr;
                    break;

                case "bottom":
                    $length = (float)$w;
                    $y += (float)$h;
                    $r1 = $bl;
                    $r2 = $br;
                    break;

                case "left":
                    $length = (float)$h;
                    $r1 = $tl;
                    $r2 = $bl;
                    break;

                case "right":
                    $length = (float)$h;
                    $x += (float)$w;
                    $r1 = $tr;
                    $r2 = $br;
                    break;
                default:
                    break;
            }
            $method = "_border_" . $props["style"];

            // draw rounded corners
            $this->$method($x, $y, $length, $props["color"], $widths, $side, $corner_style, $r1, $r2);
        }
    }

    /**
     * @param Frame $frame
     * @param float[] $border_box
     * @param string $corner_style
     */
    protected function _render_outline(Frame $frame, array $border_box, string $corner_style = "bevel"): void
    {
        $style = $frame->get_style();

        $width = (float) $style->length_in_pt($style->outline_width);
        $outline_style = $style->outline_style;
        $color = $style->outline_color;

        if (!$outline_style || $outline_style === "none" || $color === "transparent" || $width <= 0) {
            return;
        }

        $offset = (float) $style->length_in_pt($style->outline_offset);
        [$x, $y, $w, $h] = $border_box;

        $x -= $offset;
        $y -= $offset;
        $w = (float) $w + $offset * 2;
        $h = (float) $h + $offset * 2;

        // For a simple outline, we can draw a rectangle
        if (in_array($outline_style, ["solid", "dashed", "dotted"], true)
            && !$style->has_border_radius()
        ) {
            $x -= $width / 2;
            $y -= $width / 2;
            $w += $width;
            $h += $width;

            $pattern = $this->_get_dash_pattern($outline_style, $width);
            $this->_canvas->rectangle($x, $y, $w, $h, $color, $width, $pattern);
            return;
        }

        $x -= $width;
        $y -= $width;
        $w += $width * 2;
        $h += $width * 2;

        $method = "_border_" . $outline_style;
        $widths = array_fill(0, 4, $width);
        $sides = ["top", "right", "left", "bottom"];

        foreach ($sides as $side) {
            switch ($side) {
                case "top":
                    $length = $w;
                    $side_x = $x;
                    $side_y = $y;
                    break;

                case "bottom":
                    $length = $w;
                    $side_x = $x;
                    $side_y = $y + $h;
                    break;

                case "left":
                    $length = $h;
                    $side_x = $x;
                    $side_y = $y;
                    break;

                case "right":
                    $length = $h;
                    $side_x = $x + $w;
                    $side_y = $y;
                    break;

                default:
                    break;
            }

            $this->$method($side_x, $side_y, $length, $color, $widths, $side, $corner_style);
        }
    }
}
