<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace Dompdf\Positioner;

use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameReflower\Block;

/**
 * Positions absolutely positioned frames
 */
class Absolute extends AbstractPositioner
{

    /**
     * @param AbstractFrameDecorator $frame
     */
    function position(AbstractFrameDecorator $frame)
    {
        if ($frame->get_reflower() instanceof Block) {
            $style = $frame->get_style();
            [$cbx, $cby, $cbw, $cbh] = $frame->get_containing_block();

            // If the `top` value is `auto`, the frame will be repositioned
            // after its height has been resolved
            $left = (float) $style->length_in_pt($style->left, $cbw);
            $top = (float) $style->length_in_pt($style->top, $cbh);

            $frame->set_position($cbx + $left, $cby + $top);
        } else {
            // Legacy positioning logic for image and table frames
            // TODO: Resolve dimensions, margins, and offsets similar to the
            // block case in the reflowers and use the simplified logic above
            $style = $frame->get_style();
            $block_parent = $frame->find_block_parent();
            $current_line = $block_parent->get_current_line_box();
    
            list($x, $y, $w, $h) = $frame->get_containing_block();
            $inflow_x = $block_parent->get_content_box()["x"] + $current_line->left + $current_line->w;
            $inflow_y = $current_line->y;

            $top = $style->length_in_pt($style->top, $h);
            $right = $style->length_in_pt($style->right, $w);
            $bottom = $style->length_in_pt($style->bottom, $h);
            $left = $style->length_in_pt($style->left, $w);

            list($width, $height) = [$frame->get_margin_width(), $frame->get_margin_height()];

            $orig_width = $style->get_specified("width");
            $orig_height = $style->get_specified("height");

            /****************************
             *
             * Width auto:
             * ____________| left=auto | left=fixed |
             * right=auto  |     A     |     B      |
             * right=fixed |     C     |     D      |
             *
             * Width fixed:
             * ____________| left=auto | left=fixed |
             * right=auto  |     E     |     F      |
             * right=fixed |     G     |     H      |
             *****************************/

            if ($left === "auto") {
                if ($right === "auto") {
                    // A or E - Keep the frame at the same position
                    $x = $inflow_x;
                } else {
                    if ($orig_width === "auto") {
                        // C
                        $x += $w - $width - $right;
                    } else {
                        // G
                        $x += $w - $width - $right;
                    }
                }
            } else {
                if ($right === "auto") {
                    // B or F
                    $x += (float)$left;
                } else {
                    if ($orig_width === "auto") {
                        // D - TODO change width
                        $x += (float)$left;
                    } else {
                        // H - Everything is fixed: left + width win
                        $x += (float)$left;
                    }
                }
            }

            // The same vertically
            if ($top === "auto") {
                if ($bottom === "auto") {
                    // A or E - Keep the frame at the same position
                    $y = $inflow_y;
                } else {
                    if ($orig_height === "auto") {
                        // C
                        $y += (float)$h - $height - (float)$bottom;
                    } else {
                        // G
                        $y += (float)$h - $height - (float)$bottom;
                    }
                }
            } else {
                if ($bottom === "auto") {
                    // B or F
                    $y += (float)$top;
                } else {
                    if ($orig_height === "auto") {
                        // D - TODO change height
                        $y += (float)$top;
                    } else {
                        // H - Everything is fixed: top + height win
                        $y += (float)$top;
                    }
                }
            }

            $frame->set_position($x, $y);
        }
    }
}
