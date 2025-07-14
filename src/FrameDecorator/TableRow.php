<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Exception;
use Dompdf\Frame;

/**
 * Decorates Frames for table row layout
 *
 * @package dompdf
 */
class TableRow extends AbstractFrameDecorator
{

    /**
     * Indicates if a child table cell has been split.
     *
     * @var bool
     */
    public $_split_child_cell = FALSE;

    /**
     * TableRow constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
    }

    /**
     * Split the table row.
     *
     * If there are no split table cell children, the entire row will be moved
     * to the next page by calling the parent table row group split method.
     *
     * If there are split table cell children, the current table row is cloned
     * and the split table cells and empty table cells for non split cells are
     * added to the clone. The clone is then passed to the parent table row
     * group split method.
     *
     * @throws Exception
     */
    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if ($child !== null) {
            throw new Exception("Unable to split: child should not be provided.");
        }
        if (!$this->_split_child_cell) {
            $this->get_parent()->split($this, $page_break, $forced);
            return;
        }

        $this->revert_counter_increment();

        $node = $this->_frame->get_node();
        $split = $this->copy($node->cloneNode());

        $style = $this->_frame->get_style();
        $split_style = $split->get_style();

        // Truncate the box decoration at the split.
        // Clear bottom decoration of original frame.
        $style->margin_bottom = 0.0;
        $style->padding_bottom = 0.0;
        $style->border_bottom_width = 0.0;
        $style->border_bottom_left_radius = 0.0;
        $style->border_bottom_right_radius = 0.0;

        // Clear top decoration of split frame.
        $split_style->margin_top = 0.0;
        $split_style->padding_top = 0.0;
        $split_style->border_top_width = 0.0;
        $split_style->border_top_left_radius = 0.0;
        $split_style->border_top_right_radius = 0.0;
        $split_style->page_break_before = "auto";

        $split_style->text_indent = 0.0;
        $split_style->counter_reset = "none";

        $this->is_split = true;
        $split->is_split_off = true;
        $split->_already_pushed = true;

        // Copy all child table cells to the cloned row.
        foreach ($this->get_children() as $row_child) {
            $split_frame = $row_child->_split_frame;
            if ($split_frame !== null) {
                // If the table cell was split, add the split cell to the row.
                $split_frame->reset();
                $split->append_child($split_frame);
            } else {
                // The table cell was not split, add an empty table cell to the
                // row.
                $child_node = $row_child->_frame->get_node();
                $split_frame = $row_child->copy($child_node->cloneNode());
                $split_frame->reset();
                $split->append_child($split_frame);
            }
        }

        $this->get_parent()->insert_child_after($split, $this);

        if (!$forced) {
            // Reset top margin in case of an unforced page break
            // https://www.w3.org/TR/CSS21/page.html#allowed-page-breaks
            $child->get_style()->margin_top = 0.0;
        }


        $this->get_parent()->split($split, $page_break, $forced);

        // Preserve the current counter values. This must be done after the
        // parent split, as counters get reset on frame reset
        $split->_counters = $this->_counters;
    }
}
