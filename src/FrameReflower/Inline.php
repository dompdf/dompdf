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
use Dompdf\FrameReflower\Text as TextFrameReflower;

/**
 * Reflows inline frames
 *
 * @package dompdf
 */
class Inline extends AbstractFrameReflower
{

    /**
     * Inline constructor.
     * @param Frame $frame
     */
    function __construct(Frame $frame)
    {
        parent::__construct($frame);
    }

    /**
     * @param BlockFrameDecorator|null $block
     */
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

        // Resolve auto margins
        // https://www.w3.org/TR/CSS21/visudet.html#inline-width
        // https://www.w3.org/TR/CSS21/visudet.html#inline-non-replaced
        if ($style->margin_left === "auto") {
            $style->margin_left = 0;
        }
        if ($style->margin_right === "auto") {
            $style->margin_right = 0;
        }
        if ($style->margin_top === "auto") {
            $style->margin_top = 0;
        }
        if ($style->margin_bottom === "auto") {
            $style->margin_bottom = 0;
        }

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

        $frame->position();

        $cb = $frame->get_containing_block();

        if ($block) {
            $block->add_frame_to_line($this->_frame);
        }

        // Set the containing blocks and reflow each child.  The containing
        // block is not changed by line boxes.
        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($cb);
            $child->reflow($block);
        }

        // Handle relative positioning
        foreach ($this->_frame->get_children() as $child) {
            $this->position_relative($child);
        }
    }

    /**
     * Get the minimum width needed for the first line of the frame, including
     * the margin box.
     *
     * @return array A pair of values: The minimum width, and whether it
     * includes the right margin box because the entire frame is covered.
     */
    public function get_min_first_line_width(): array
    {
        // FIXME This should not be completely correct: White-space styling
        // might affect the layout, such that several children have to be
        // considered. The right way to do this would probably be a (partial)
        // reflow of the frame
        $frame = $this->_frame;
        $firstChild = $frame->get_first_child();

        if ($firstChild === null) {
            return [0.0, true];
        }

        $hasSibling = $firstChild->get_next_sibling() !== null;
        $reflower = $firstChild->get_reflower();

        if ($reflower instanceof TextFrameReflower) {
            [$min, $includesAll, $childDelta] = $reflower->get_min_first_line_width();
            $applyRightWidths = $includesAll && !$hasSibling;
        } elseif ($reflower instanceof self) {
            [$min, $includesAll] = $reflower->get_min_first_line_width();
            $applyRightWidths = $includesAll && !$hasSibling;
        } else {
            [$min] = $reflower->get_min_max_width();
            $applyRightWidths = !$hasSibling;
        }

        // Because currently margin, border, and padding are not handled on the
        // inline frame itself, but applied to first resp. last text child, we
        // only want to add these here if they have not been applied to the
        // children yet (which can happen in case of nested inline children)
        $style = $frame->get_style();
        $line_width = $frame->get_containing_block("w");
        $widths = $applyRightWidths ? [
            $style->margin_left,
            $style->border_left_width,
            $style->padding_left,
            $style->padding_right,
            $style->border_right_width,
            $style->margin_right
        ] : [
            $style->margin_left,
            $style->border_left_width,
            $style->padding_left
        ];
        $delta = isset($childDelta) && $childDelta === 0.0
            ? (float) $style->length_in_pt($widths, $line_width)
            : 0.0;

        return [$min + $delta, $applyRightWidths];
    }
}
