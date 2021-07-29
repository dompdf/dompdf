<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace Dompdf\Positioner;

use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameReflower\Inline as InlineFrameReflower;
use Dompdf\Exception;

/**
 * Positions inline frames
 *
 * @package dompdf
 */
class Inline extends AbstractPositioner
{

    /**
     * @param AbstractFrameDecorator $frame
     * @throws Exception
     */
    function position(AbstractFrameDecorator $frame)
    {
        /**
         * Find our nearest block level parent and access its lines property.
         * @var BlockFrameDecorator
         */
        $p = $frame->find_block_parent();

        // Debugging code:

        // Helpers::pre_r("\nPositioning:");
        // Helpers::pre_r("Me: " . $frame->get_node()->nodeName . " (" . spl_object_hash($frame->get_node()) . ")");
        // Helpers::pre_r("Parent: " . $p->get_node()->nodeName . " (" . spl_object_hash($p->get_node()) . ")");

        // End debugging

        if (!$p) {
            throw new Exception("No block-level parent found.  Not good.");
        }

        $cb = $frame->get_containing_block();
        $line = $p->get_current_line_box();
        $reflower = $frame->get_reflower();

        if ($reflower instanceof InlineFrameReflower && $frame->get_node()->nodeName !== "br") {
            [$min] = $reflower->get_min_first_line_width();

            // If no parts of the inline frame fit in the current line, it
            // should break to a new line
            if ($min > ($cb["w"] - $line->left - $line->w - $line->right)) {
                $p->add_line();
                $line = $p->get_current_line_box();
            }
        } elseif ($frame->is_inline_block()) {
            $width = $frame->get_margin_width();

            // If an inline-block frame doesn't fit in the current line, it
            // should break to a new line. Inline-block elements are formatted
            // as atomic inline boxes
            if ($width > ($cb["w"] - $line->left - $line->w - $line->right)) {
                $p->add_line();
                $line = $p->get_current_line_box();
            }
        }

        $frame->set_position($cb["x"] + $line->w, $line->y);
    }
}
