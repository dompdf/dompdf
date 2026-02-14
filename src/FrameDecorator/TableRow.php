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

        $table = Table::find_parent_table($this);
        $cellmap = $table->get_cellmap();
        $row_cells = $cellmap->get_frames_in_row($this);
        ksort($row_cells);

        $has_split_child = false;
        foreach ($row_cells as $row_cell) {
            if ($row_cell->is_split) {
                $has_split_child = true;
                break;
            }
        }
        if (!$has_split_child) {
            // No split children, just move the row to the next page.
            $this->get_parent()->split($this, $page_break, $forced);
            return;
        }

        $this->revert_counter_increment();

        $node = $this->_frame->get_node();
        $split = $this->copy($node->cloneNode());

        $style = $this->_frame->get_style();
        $split_style = $split->get_style();

        // Truncate the box decoration at the split.
        // if the parent table does not have headers
        $table = Table::find_parent_table($this);
        if (!$table->has_headers()) {
            // Clear bottom decoration of original frame
            $style->margin_bottom = 0.0;
            $style->padding_bottom = 0.0;
            $style->border_bottom_style = "hidden";
            $style->border_bottom_width = 0.0;
            $style->border_bottom_left_radius = 0.0;
            $style->border_bottom_right_radius = 0.0;

            // Clear top decoration of split frame
            $split_style->margin_top = 0.0;
            $split_style->padding_top = 0.0;
            $split_style->border_top_style = "hidden";
            $split_style->border_top_width = 0.0;
            $split_style->border_top_left_radius = 0.0;
            $split_style->border_top_right_radius = 0.0;
            $split_style->page_break_before = "auto";
        }

        $split_style->text_indent = 0.0;
        $split_style->counter_reset = "none";

        $this->is_split = true;
        $split->is_split_off = true;
        $split->_already_pushed = true;

        // Copy all child table cells to the cloned row.
        $row_info = $cellmap->get_spanned_cells($this);
        foreach ($row_cells as $row_cell) {
            // if the child was not split because it fully rendered
            // add a dummy frame and split at that point
            $split_frame = $row_cell->get_split_frame();
            if (!$split_frame) {
                $row_cell_style = $row_cell->get_frame()->get_style();
                
                $null_frame = new Frame(new \DOMText());
                $null_style = new \Dompdf\Css\Style($row_cell_style->get_stylesheet(), $row_cell_style->get_origin());
                $null_style->reset();
                $null_style->display = "none";
                $null_frame->set_style($null_style);
                $null_deco = new NullFrameDecorator($null_frame, $this->get_dompdf());
                $null_deco->set_reflower(new \Dompdf\FrameReflower\NullFrameReflower($null_deco, $this->get_dompdf()->getFontMetrics()));
                $row_cell->append_child($null_deco);
                $row_cell->split($null_deco, true, false);
                $split_frame = $row_cell->get_split_frame();
            }
            $split_frame->reset();

            // update the rowspan in the frame
            $cell_info = $cellmap->get_spanned_cells($row_cell);
            $rowspan = $used_rowspan = max((int) $split_frame->get_node()->getAttribute("rowspan"), 1);
            if ($rowspan > 1) {
                $used_rowspan = $row_info["rows"][0] - $cell_info["rows"][0];
                $row_cell->get_node()->setAttribute("rowspan", max($used_rowspan, 1));
                $split_frame->get_node()->setAttribute("rowspan", max($rowspan - $used_rowspan, 1));
            }

            $split->append_child($split_frame);
            $cellmap->resolve_frame_border($row_cell);
        }

        // Preserve the current counter values. This must be done after the
        // parent split, as counters get reset on frame reset
        $split->_counters = $this->_counters;
        
        $this->get_parent()->insert_child_after($split, $this, true);
        $this->get_parent()->split($split, $page_break, $forced);
    }

    /**
     * {@inheritdoc}
     *
     * The page break logic should not follow the last child of a Table Row
     * (i.e. a table cell) to check for an allowable page break.
     *
     * This situation would only occur if a table cell (or its contents) within
     * a following row does not fit on a page and a page break is not allowed
     * within that table cell or before its parent table row or before the
     * current table row.
     *
     * The table cells of a preceding row would have already gone through
     * the reflow process. The table cell & row split functionality can
     * currently only handle splitting within table cells & rows that are
     * being reflowed.
     */
    public function checkPageBreakBeforeLastChild(): bool
    {
        return false;
    }
}
