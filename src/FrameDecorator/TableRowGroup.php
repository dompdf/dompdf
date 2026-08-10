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
 * Table row group decorator
 *
 * Overrides split() method for tbody, thead & tfoot elements
 *
 * @package dompdf
 */
class TableRowGroup extends AbstractFrameDecorator
{

    /**
     * Class constructor
     *
     * @param Frame $frame   Frame to decorate
     * @param Dompdf $dompdf Current dompdf instance
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
    }

    /**
     * Split the row group at the given child and remove all subsequent child
     * rows and all subsequent row groups from the cellmap.
     */
    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            parent::split($child, $page_break, $forced);
            return;
        }

        // Remove child & all subsequent rows from the cellmap
        /** @var Table $parent */
        $parent = $this->get_parent();
        $cellmap = $parent->get_cellmap();
        $iter = $child;

        // If the row has already been split we don't need to do anything else,
        // otherwise we need to check for rowspanned cells. The rowspanned cells
        // that began on a previous row need to be split so that the split off
        // frame can be added to this row for rendering on the following page.
        if (!$child->is_split_off) {
            $row_info = $cellmap->get_spanned_cells($child);
            $row_index = $row_info["rows"][0];
            if ($row_index !== 0) {
                $row_cells = $cellmap->get_frames_in_row($child);
                ksort($row_cells);
                $prev_cell = null;
    
                // collect new frames so we're not modifying the collection as we evaulate it
                $new_frames = [];
                foreach ($row_cells as $row_cell) {
                    $cell_info = $cellmap->get_spanned_cells($row_cell);
                    if ($cell_info["rows"][0] === $row_index) {
                        $prev_cell = $row_cell;
                        continue;
                    }
    
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
    
                    // update the rowspan in the frame
                    $cell_info = $cellmap->get_spanned_cells($row_cell);
                    $rowspan = $used_rowspan = max((int) $split_frame->get_node()->getAttribute("rowspan"), 1);
                    if ($rowspan > 1) {
                        $used_rowspan = $row_info["rows"][0] - $cell_info["rows"][0];
                        $row_cell->get_node()->setAttribute("rowspan", max($used_rowspan, 1));
                        $split_frame->get_node()->setAttribute("rowspan", max($rowspan - $used_rowspan, 1));
                    }
    
                    $new_frames[] = [$split_frame, $prev_cell];
                    $prev_cell = $split_frame;
                }
                foreach ($new_frames as $new_frame) {
                    if ($new_frame[1] === null) {
                        $child->prepend_child($new_frame[0]);
                    } else {
                        $child->insert_child_after($new_frame[0], $new_frame[1]);
                    }
                }
            }
        }

        while ($iter) {
            $cellmap->remove_row($iter);
            $iter = $iter->get_next_sibling();
        }

        // Remove all subsequent row groups from the cellmap
        $iter = $this->get_next_sibling();

        while ($iter) {
            $cellmap->remove_row_group($iter);
            $iter = $iter->get_next_sibling();
        }

        // If we are splitting at the first child remove the
        // table-row-group from the cellmap as well
        if ($child === $this->get_first_child()) {
            $cellmap->remove_row_group($this);
            parent::split(null, $page_break, $forced);
            return;
        }

        $cellmap->update_row_group($this, $child->get_prev_sibling());
        parent::split($child, $page_break, $forced);
    }
}
