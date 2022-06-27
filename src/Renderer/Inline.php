<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;
use Dompdf\Helpers;

/**
 * Renders inline frames
 *
 * @package dompdf
 */
class Inline extends AbstractRenderer
{
    function render(Frame $frame)
    {
        if (!$frame->get_first_child()) {
            return; // No children, no service
        }

        $style = $frame->get_style();
        $dompdf = $this->_dompdf;

        $this->_set_opacity($frame->get_opacity($style->opacity));

        $do_debug_layout_line = $dompdf->getOptions()->getDebugLayout()
            && $dompdf->getOptions()->getDebugLayoutInline();

        // Draw the background & border behind each child.  To do this we need
        // to figure out just how much space each child takes:
        [$x, $y] = $frame->get_first_child()->get_position();
        [$w, $h] = $this->get_child_size($frame, $do_debug_layout_line);

        [, , $cbw] = $frame->get_containing_block();
        $margin_left = $style->length_in_pt($style->margin_left, $cbw);
        $pt = $style->length_in_pt($style->padding_top, $cbw);
        $pb = $style->length_in_pt($style->padding_bottom, $cbw);

        // Make sure that border and background start inside the left margin
        // Extend the drawn box by border and padding in vertical direction, as
        // these do not affect layout
        // FIXME: Using a small vertical offset of a fraction of the height here
        // to work around the vertical position being slightly off in general
        $x += $margin_left;
        $y -= $style->border_top_width + $pt - ($h * 0.1);
        $w += $style->border_left_width + $style->border_right_width;
        $h += $style->border_top_width + $pt + $style->border_bottom_width + $pb;

        $border_box = [$x, $y, $w, $h];
        $this->_render_background($frame, $border_box);
        $this->_render_border($frame, $border_box);
        $this->_render_outline($frame, $border_box);

        $node = $frame->get_node();
        $id = $node->getAttribute("id");
        if (strlen($id) > 0) {
            $this->_canvas->add_named_dest($id);
        }

        // Only two levels of links frames
        $is_link_node = $node->nodeName === "a";
        if ($is_link_node) {
            if (($name = $node->getAttribute("name"))) {
                $this->_canvas->add_named_dest($name);
            }
        }

        if ($frame->get_parent() && $frame->get_parent()->get_node()->nodeName === "a") {
            $link_node = $frame->get_parent()->get_node();
        }

        // Handle anchors & links
        if ($is_link_node) {
            if ($href = $node->getAttribute("href")) {
                $href = Helpers::build_url($dompdf->getProtocol(), $dompdf->getBaseHost(), $dompdf->getBasePath(), $href) ?? $href;
                $this->_canvas->add_link($href, $x, $y, $w, $h);
            }
        }
    }

    protected function get_child_size(Frame $frame, bool $do_debug_layout_line): array
    {
        $w = 0.0;
        $h = 0.0;

        foreach ($frame->get_children() as $child) {
            if ($child->get_node()->nodeValue === " " && $child->get_prev_sibling() && !$child->get_next_sibling()) {
                break;
            }

            $style = $child->get_style();
            $auto_width = $style->width === "auto";
            $auto_height = $style->height === "auto";
            [, , $child_w, $child_h] = $child->get_padding_box();

            if ($auto_width || $auto_height) {
                [$child_w2, $child_h2] = $this->get_child_size($child, $do_debug_layout_line);

                if ($auto_width) {
                    $child_w = $child_w2;
                }
    
                if ($auto_height) {
                    $child_h = $child_h2;
                }
            }

            $w += $child_w;
            $h = max($h, $child_h);

            if ($do_debug_layout_line) {
                $this->_debug_layout($child->get_border_box(), "blue");

                if ($this->_dompdf->getOptions()->getDebugLayoutPaddingBox()) {
                    $this->_debug_layout($child->get_padding_box(), "blue", [0.5, 0.5]);
                }
            }
        }

        return [$w, $h];
    }
}
