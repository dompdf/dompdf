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
        // Find our nearest block level parent and access its lines property
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

        if ($frame->is_text_node() || $frame->get_node()->nodeName === "br") {
            $frame->set_position($cb["x"] + $line->w, $line->y);
            return;
        }

        $reflower = $frame->get_reflower();

        if ($reflower instanceof InlineFrameReflower) {
            [$min] = $reflower->get_min_first_line_width();

            // If no parts of the inline frame fit in the current line, it
            // should break to a new line
            if ($min > ($cb["w"] - $line->left - $line->w - $line->right)) {
                $p->add_line();
                $line = $p->get_current_line_box();
            }
        } else {
            // Atomic inline boxes and replaced inline elements
            // (inline-block, inline-table, img etc.)
            $width = $frame->get_margin_width();

            if ($width > ($cb["w"] - $line->left - $line->w - $line->right)) {
                $p->add_line();
                $line = $p->get_current_line_box();
            }
        }

        $frame->set_position($cb["x"] + $line->w, $line->y);
    }
}
