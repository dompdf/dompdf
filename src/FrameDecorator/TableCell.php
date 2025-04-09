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
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;

/**
 * Decorates table cells for layout
 *
 * @package dompdf
 */
class TableCell extends BlockFrameDecorator
{
    /**
     * @var float
     */
    protected $content_height;

    /**
     * The stored split table cell.
     *
     * If the table cell's content does not fit on the current page, the new
     * split table cell will be stored in this property for processing by
     * TableRow::split().
     *
     * @var ?TableCell
     */
    private $_split_frame = null;

    /**
     * Retrieves the stored split cell.
     *
     * @var ?TableCell
     */
    public function get_split_frame() : ?TableCell
    {
        return $this->_split_frame;
    }

    /**
     * Sets the stored split cell.
     *
     * @var ?TableCell
     */
    public function set_split_frame() : ?TableCell
    {
        return $this->_split_frame;
    }

    /**
     * Clears the stored split cell.
     */
    public function clear_split_frame()
    {
        $this->_split_frame = null;
    }

    /**
     * TableCell constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
        $this->content_height = 0.0;
    }

    function reset()
    {
        parent::reset();
        $this->content_height = 0.0;
    }

    /**
     * Split the table cell at the given child.
     *
     *  - The current table cell is cloned and $child and all children following
     * $child are added to the clone.
     *  - The cloned table cell is stored to be added to a new table row in
     * TableRow::split() which is called from TableRow::reflow().
     *
     * @throws Exception
     *
     * @see \Dompdf\FrameDecorator\TableRow::split()
     * @see \Dompdf\FrameReflower\TableRow::reflow()
     */
    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            $child = $this->get_first_child();
        }

        if ($child->get_parent() !== $this) {
            throw new Exception("Unable to split: frame is not a child of this one.");
        }

        $this->revert_counter_increment();

        $node = $this->_frame->get_node();
        $split = $this->copy($node->cloneNode());

        $style = $this->_frame->get_style();
        $split_style = $split->get_style();

        // Truncate the box decoration at the split
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

        // Remove the frames that will be moved to the new split node from
        // the line boxes
        $this->remove_frames_from_line($child);

        // recalculate the float offsets after paging
        foreach ($this->get_line_boxes() as $line_box) {
            $line_box->get_float_offsets();
        }

        if (!$forced) {
            // Reset top margin in case of an unforced page break
            // https://www.w3.org/TR/CSS21/page.html#allowed-page-breaks
            $child->get_style()->margin_top = 0.0;
        }

        // Add $child and all following siblings to the new split node
        $iter = $child;
        while ($iter) {
            $frame = $iter;
            $iter = $iter->get_next_sibling();
            $frame->reset();
            $split->append_child($frame);
        }

        // Store the split table cell for future processing.
        $this->_split_frame = $split;

        // NOTE: The split method on the parent Table Row is not called at this
        // stage like other split methods to ensure all Table Cells are reflowed
        // first as their content may be able to be added to the current page.
    }

    /**
     * @return float
     */
    public function get_content_height(): float
    {
        return $this->content_height;
    }

    /**
     * @param float $height
     */
    public function set_content_height(float $height): void
    {
        $this->content_height = $height;
    }

    /**
     * @param float $height
     */
    public function set_cell_height(float $height): void
    {
        $style = $this->get_style();
        $v_space = (float)$style->length_in_pt(
            [
                $style->margin_top,
                $style->padding_top,
                $style->border_top_width,
                $style->border_bottom_width,
                $style->padding_bottom,
                $style->margin_bottom
            ],
            (float)$style->length_in_pt($style->height)
        );

        $new_height = $height - $v_space;
        $style->set_used("height", $new_height);

        if ($new_height > $this->content_height) {
            $y_offset = 0;

            // Adjust our vertical alignment
            switch ($style->vertical_align) {
                default:
                case "baseline":
                    // FIXME: this isn't right

                case "top":
                    // Don't need to do anything
                    return;

                case "middle":
                    $y_offset = ($new_height - $this->content_height) / 2;
                    break;

                case "bottom":
                    $y_offset = $new_height - $this->content_height;
                    break;
            }

            if ($y_offset) {
                // Move our children
                foreach ($this->get_line_boxes() as $line) {
                    foreach ($line->get_frames() as $frame) {
                        $frame->move(0, $y_offset);
                    }
                }
            }
        }
    }

    /**
     * Locate the parent table row group of this table cell.
     *
     * @return TableRowGroup|null
     *   The parent Table Row Group or null if this table cell
     *   does not have a table row group parent.
     */
    public function find_parent_table_row_group(): ?TableRowGroup
    {
        $parent = $this->get_parent();
        while (null !== $parent) {
            if (in_array($parent->get_style()->display, Table::ROW_GROUPS, true)) {
                break;
            }
            $parent = $parent->get_parent();
        }
        return $parent;
    }

    /**
     * {@inheritdoc}
     *
     * The page break logic should not follow previous siblings of a Table
     * Cell (i.e. preceding table cells in the same row) to check for an
     * allowable page break.
     *
     * These cells have already gone through the reflow process. The table
     * cell & row split functionality can currently only handle splitting
     * within table cells & rows that are in reflow.
     */
    public function checkPageBreakBeforePreviousSibling(): bool
    {
        return false;
    }
}
