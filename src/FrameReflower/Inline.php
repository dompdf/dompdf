<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Inline as InlineFrameDecorator;
use Dompdf\FrameDecorator\Text as TextFrameDecorator;

/**
 * Reflows inline frames
 *
 * @package dompdf
 */
class Inline extends AbstractFrameReflower
{
    /**
     * Inline constructor.
     * @param InlineFrameDecorator $frame
     */
    function __construct(InlineFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    /**
     * Handle reflow of empty inline frames.
     *
     * Regular inline frames are positioned together with their text (or inline)
     * children after child reflow. Empty inline frames have no children that
     * could determine the positioning, so they need to be handled separately.
     *
     * @param BlockFrameDecorator $block
     */
    protected function reflow_empty(BlockFrameDecorator $block): void
    {
        /** @var InlineFrameDecorator */
        $frame = $this->_frame;
        $style = $frame->get_style();

        // Resolve width, so the margin width can be checked
        $style->set_used("width", 0.0);

        $cb = $frame->get_containing_block();
        $line = $block->get_current_line_box();
        $width = $frame->get_margin_width();

        if ($width > ($cb["w"] - $line->left - $line->w - $line->right)) {
            $block->add_line();

            // Find the appropriate inline ancestor to split
            $child = $frame;
            $p = $child->get_parent();
            while ($p instanceof InlineFrameDecorator && !$child->get_prev_sibling()) {
                $child = $p;
                $p = $p->get_parent();
            }

            if ($p instanceof InlineFrameDecorator) {
                // Split parent and stop current reflow. Reflow continues
                // via child-reflow loop of split parent
                $p->split($child);
                return;
            }
        }

        $frame->position();
        $block->add_frame_to_line($frame);
    }

    /**
     * @param BlockFrameDecorator|null $block
     */
    function reflow(BlockFrameDecorator $block = null)
    {
        /** @var InlineFrameDecorator */
        $frame = $this->_frame;

        // Check if a page break is forced
        $page = $frame->get_root();
        $page->check_forced_page_break($frame);

        if ($page->is_full()) {
            return;
        }

        // Counters and generated content
        $this->_set_content();

        $style = $frame->get_style();

        // Resolve auto margins
        // https://www.w3.org/TR/CSS21/visudet.html#inline-width
        // https://www.w3.org/TR/CSS21/visudet.html#inline-non-replaced
        if ($style->margin_left === "auto") {
            $style->set_used("margin_left", 0.0);
        }
        if ($style->margin_right === "auto") {
            $style->set_used("margin_right", 0.0);
        }
        if ($style->margin_top === "auto") {
            $style->set_used("margin_top", 0.0);
        }
        if ($style->margin_bottom === "auto") {
            $style->set_used("margin_bottom", 0.0);
        }

        // Handle line breaks
        if ($frame->get_node()->nodeName === "br") {
            if ($block) {
                $line = $block->get_current_line_box();
                $frame->set_containing_line($line);
                $block->maximize_line_height($frame->get_margin_height(), $frame);
                $block->add_line(true);

                $next = $frame->get_next_sibling();
                $p = $frame->get_parent();

                if ($next && $p instanceof InlineFrameDecorator) {
                    $p->split($next);
                }
            }
            return;
        }

        // Handle empty inline frames
        if (!$frame->get_first_child()) {
            if ($block) {
                $this->reflow_empty($block);
            }
            return;
        }

        // Add our margin, padding & border to the first and last children
        if (($f = $frame->get_first_child()) && $f instanceof TextFrameDecorator) {
            $f_style = $f->get_style();
            $f_style->margin_left = $style->margin_left;
            $f_style->padding_left = $style->padding_left;
            $f_style->border_left_width = $style->border_left_width;
            $f_style->border_left_style = $style->border_left_style;
            $f_style->border_left_color = $style->border_left_color;
        }

        if (($l = $frame->get_last_child()) && $l instanceof TextFrameDecorator) {
            $l_style = $l->get_style();
            $l_style->margin_right = $style->margin_right;
            $l_style->padding_right = $style->padding_right;
            $l_style->border_right_width = $style->border_right_width;
            $l_style->border_right_style = $style->border_right_style;
            $l_style->border_right_color = $style->border_right_color;
        }

        $cb = $frame->get_containing_block();

        // Set the containing blocks and reflow each child.  The containing
        // block is not changed by line boxes.
        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($cb);
            $child->reflow($block);

            // Stop reflow if the frame has been reset by a line or page break
            // due to child reflow
            if (!$frame->content_set) {
                return;
            }
        }

        if (!$frame->get_first_child()) {
            return;
        }

        // Assume the position of the first child
        [$x, $y] = $frame->get_first_child()->get_position();
        $frame->set_position($x, $y);

        // Handle relative positioning
        foreach ($frame->get_children() as $child) {
            $this->position_relative($child);
        }

        if ($block) {
            $block->add_frame_to_line($frame);
        }
    }
}
