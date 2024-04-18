<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;

/**
 * Renders inline frames
 *
 * @package dompdf
 */
class Inline extends AbstractRenderer
{
    function render(Frame $frame)
    {
        // Get the first in-flow child
        $child = $frame->get_first_child();
        while ($child && !$child->is_in_flow()) {
            $child = $child->get_next_sibling();
        }

        if (!$child) {
            return; // No children, no service
        }

        $style = $frame->get_style();
        $node = $frame->get_node();

        $this->_set_opacity($frame->get_opacity($style->opacity));

        // Draw background & border behind each child. To do this, we need to
        // to figure out just how much space each child takes. Retrieve the
        // position of the first child again, to account for text and vertical
        // alignment
        [$x, $y] = $child->get_position();
        [$w, $h] = $this->get_child_size($frame);

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
        $h += $style->border_top_width + $pt + $style->border_bottom_width + $pb;

        $border_box = [$x, $y, $w, $h];
        $this->_render_background($frame, $border_box);
        $this->_render_border($frame, $border_box);
        $this->_render_outline($frame, $border_box);

        $this->addNamedDest($node);
        $this->addHyperlink($node, $border_box);

        $options = $this->_dompdf->getOptions();

        if ($options->getDebugLayout() && $options->getDebugLayoutInline()) {
            $this->debugLayout($border_box, "blue");

            if ($options->getDebugLayoutPaddingBox()) {
                $padding_box = [
                    $x + $style->border_left_width,
                    $y + $style->border_top_width,
                    $w - $style->border_left_width - $style->border_right_width,
                    $h - $style->border_top_width - $style->border_bottom_width
                ];
                $this->debugLayout($padding_box, "blue", [0.5, 0.5]);
            }
        }
    }

    protected function get_child_size(Frame $frame): array
    {
        $w = 0.0;
        $h = 0.0;

        foreach ($frame->get_children() as $child) {
            if (!$child->is_in_flow()) {
                continue;
            }

            // Exclude trailing white space
            if ($child->get_node()->nodeValue === " "
                && $child->get_prev_sibling() && !$child->get_next_sibling()
            ) {
                break;
            }

            $style = $child->get_style();
            $auto_width = $style->width === "auto";
            $auto_height = $style->height === "auto";
            [, , $child_w, $child_h] = $child->get_border_box();

            if ($auto_width || $auto_height) {
                [$child_w2, $child_h2] = $this->get_child_size($child);

                if ($auto_width) {
                    $child_w = $child_w2;
                }
    
                if ($auto_height) {
                    $child_h = $child_h2;
                }
            }

            $w += $child_w;
            $h = max($h, $child_h);
        }

        return [$w, $h];
    }
}
