<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace Dompdf\Positioner;

use Dompdf\FrameDecorator\AbstractFrameDecorator;

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
        $style = $frame->get_style();

        $p = $frame->find_positionned_parent();

        list($x, $y, $w, $h) = $frame->get_containing_block();

        $top = $style->length_in_pt($style->top, $h);
        $right = $style->length_in_pt($style->right, $w);
        $bottom = $style->length_in_pt($style->bottom, $h);
        $left = $style->length_in_pt($style->left, $w);

        if ($p && !($left === "auto" && $right === "auto")) {
            // Get the parent's padding box (see http://www.w3.org/TR/CSS21/visuren.html#propdef-top)
            list($x, $y, $w, $h) = $p->get_padding_box();
        }

        list($width, $height) = array($frame->get_margin_width(), $frame->get_margin_height());

        $orig_style = $frame->get_original_style();
        $orig_width = $orig_style->width;
        $orig_height = $orig_style->height;

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
                $x = $x + $frame->find_block_parent()->get_current_line_box()->w;
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
                $y = $frame->find_block_parent()->get_current_line_box()->y;
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
