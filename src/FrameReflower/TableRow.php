<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Table as TableFrameDecorator;
use Dompdf\FrameDecorator\TableRow as TableRowFrameDecorator;
use Dompdf\Exception;

/**
 * Reflows table rows
 *
 * @package dompdf
 */
class TableRow extends AbstractFrameReflower
{
    /**
     * TableRow constructor.
     * @param TableRowFrameDecorator $frame
     */
    function __construct(TableRowFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    /**
     * @param BlockFrameDecorator|null $block
     */
    function reflow(?BlockFrameDecorator $block = null)
    {
        /** @var TableRowFrameDecorator */
        $frame = $this->_frame;

        // Check if a page break is forced
        $page = $frame->get_root();
        $page->check_forced_page_break($frame);

        // Bail if the page is full
        if ($frame->find_pageable_context()->is_full()) {
            return;
        }

        // Counters and generated content
        $this->_set_content();

        $frame->position();
        $style = $frame->get_style();
        $cb = $frame->get_containing_block();

        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($cb);
            $child->reflow();

            if ($frame->find_pageable_context()->is_full() && $child->get_position("x") === null) {
                break;
            }
        }

        if ($frame->find_pageable_context()->is_full()) {
            return;
        }

        $table = TableFrameDecorator::find_parent_table($frame);
        if ($table === null) {
            throw new Exception("Parent table not found for table row");
        }
        $cellmap = $table->get_cellmap();

        $style->set_used("width", $cellmap->get_frame_width($frame));
        $style->set_used("height", $cellmap->get_frame_height($frame));

        $frame->set_position($cellmap->get_frame_position($frame));
        
        // split the row if one of the contained cells was split
        $row_info = $cellmap->get_spanned_cells($frame);
        $row_index = $row_info["rows"][0];
        $row_cells = $cellmap->get_frames_in_row($frame);
        ksort($row_cells);
        foreach ($row_cells as $child) {
            if ($child->is_split) {
                // ...unless the child is rowspanned into the next row, then we wait
                $cell_info = $cellmap->get_spanned_cells($child);
                $cell_cols = array_keys($cell_info["rows"]);
                if (end($cell_cols) > $row_index) {
                    continue;
                }

                $frame->split(null, true, false);
                break;
            }
        }
    }

    /**
     * @throws Exception
     */
    public function get_min_max_width(): array
    {
        throw new Exception("Min/max width is undefined for table rows");
    }
}
