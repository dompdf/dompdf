<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\Frame;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Text as TextFrameDecorator;

/**
 * Reflows inline frames
 *
 * @package dompdf
 */
class Inline extends AbstractFrameReflower
{

    function __construct(Frame $frame)
    {
        parent::__construct($frame);
    }

    //........................................................................

    function reflow(BlockFrameDecorator $block = null)
    {
        $frame = $this->_frame;

        // Check if a page break is forced
        $page = $frame->get_root();
        $page->check_forced_page_break($frame);

        if ($page->is_full()) {
            return;
        }

        $style = $frame->get_style();

        // Generated content
        $this->_set_content();

        $frame->position();

        $cb = $frame->get_containing_block();

        // Add our margin, padding & border to the first and last children
        if (($f = $frame->get_first_child()) && $f instanceof TextFrameDecorator) {
            $f_style = $f->get_style();
            $f_style->margin_left = $style->margin_left;
            $f_style->padding_left = $style->padding_left;
            $f_style->border_left = $style->border_left;
        }

        if (($l = $frame->get_last_child()) && $l instanceof TextFrameDecorator) {
            $l_style = $l->get_style();
            $l_style->margin_right = $style->margin_right;
            $l_style->padding_right = $style->padding_right;
            $l_style->border_right = $style->border_right;
        }

        if ($block) {
            $block->add_frame_to_line($this->_frame);
        }

        // Set the containing blocks and reflow each child.  The containing
        // block is not changed by line boxes.
        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($cb);
            $child->reflow($block);
        }
    }
}
