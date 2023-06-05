<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
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

    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            $this->get_parent()->split($this, $page_break, $forced);
            return;
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

        // Clear bottom decoration of original frame
        $style->margin_bottom = 0.0;
        $style->padding_bottom = 0.0;
        $style->border_bottom_width = 0.0;
        $style->border_bottom_left_radius = 0.0;
        $style->border_bottom_right_radius = 0.0;

        // Clear top decoration of split frame
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
        $split->_already_pushed = false;

        //TODO: increase rowspan for each cell associated with this row?
        $table = Table::find_parent_table($this);
        $cellmap = $table->get_cellmap();
        //foreach ($this->get_children() as $child) {
        foreach ($cellmap->frames_in_row($this) as $child) {
            //TODO: walk cell map and add missing cells (clone node, remove all children)
            //      AND/OR, update cell map so that existing cells +1 their rowspan?
            //      Increase the rowspan on any element with a rowspan? On the split cells?

            // add a dummy child and split cells that fully render on the current page
            if (!$child->_split_frame) {
                $child_style = $child->get_frame()->get_style();
                
                $null_frame = new Frame(new \DOMText());
                $null_style = new \Dompdf\Css\Style($child_style->get_stylesheet(), $child_style->get_origin());
                $null_style->reset();
                $null_style->display = "none";
                $null_frame->set_style($null_style);
                $null_deco = new NullFrameDecorator($null_frame, $this->get_dompdf());
                $null_deco->set_reflower(new \Dompdf\FrameReflower\NullFrameReflower($null_deco, $this->get_dompdf()->getFontMetrics()));
                $child->append_child($null_deco);
                $child->split($null_deco, true, false);
            }
            $split->append_child($child->_split_frame);
        }

        $this->get_parent()->insert_child_after($split, $this, true);
        $this->get_parent()->split($split, $page_break, $forced);
    }
}
