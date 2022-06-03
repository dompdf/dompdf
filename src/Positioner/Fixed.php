<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace Dompdf\Positioner;

use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameReflower\Block;

/**
 * Positions fixely positioned frames
 */
class Fixed extends Absolute
{

    /**
     * @param AbstractFrameDecorator $frame
     */
    function position(AbstractFrameDecorator $frame): void
    {
        if ($frame->get_reflower() instanceof Block) {
            parent::position($frame);
        } else {
            // Legacy positioning logic for image and table frames
            // TODO: Resolve dimensions, margins, and offsets similar to the
            // block case in the reflowers and use the simplified logic above
            $style = $frame->get_style();
            $root = $frame->get_root();
            $initialcb = $root->get_containing_block();
            $initialcb_style = $root->get_style();

            $p = $frame->find_block_parent();
            if ($p) {
                $p->add_line();
            }
            // Compute the margins of the @page style
            $margin_top = (float)$initialcb_style->length_in_pt($initialcb_style->margin_top, $initialcb["h"]);
            $margin_right = (float)$initialcb_style->length_in_pt($initialcb_style->margin_right, $initialcb["w"]);
            $margin_bottom = (float)$initialcb_style->length_in_pt($initialcb_style->margin_bottom, $initialcb["h"]);
            $margin_left = (float)$initialcb_style->length_in_pt($initialcb_style->margin_left, $initialcb["w"]);

            // The needed computed style of the element
            $height = (float)$style->length_in_pt($style->get_specified("height"), $initialcb["h"]);
            $width = (float)$style->length_in_pt($style->get_specified("width"), $initialcb["w"]);

            $top = $style->length_in_pt($style->get_specified("top"), $initialcb["h"]);
            $right = $style->length_in_pt($style->get_specified("right"), $initialcb["w"]);
            $bottom = $style->length_in_pt($style->get_specified("bottom"), $initialcb["h"]);
            $left = $style->length_in_pt($style->get_specified("left"), $initialcb["w"]);

            $y = $margin_top;
            if (isset($top)) {
                $y = (float)$top + $margin_top;
                if ($top === "auto") {
                    $y = $margin_top;
                    if (isset($bottom) && $bottom !== "auto") {
                        $y = $initialcb["h"] - $bottom - $margin_bottom;
                        if ($frame->is_auto_height()) {
                            $y -= $height;
                        } else {
                            $y -= $frame->get_margin_height();
                        }
                    }
                }
            }

            $x = $margin_left;
            if (isset($left)) {
                $x = (float)$left + $margin_left;
                if ($left === "auto") {
                    $x = $margin_left;
                    if (isset($right) && $right !== "auto") {
                        $x = $initialcb["w"] - $right - $margin_right;
                        if ($frame->is_auto_width()) {
                            $x -= $width;
                        } else {
                            $x -= $frame->get_margin_width();
                        }
                    }
                }
            }

            $frame->set_position($x, $y);

            foreach ($frame->get_children() as $child) {
                $child->set_position($x, $y);
            }
        }
    }
}
