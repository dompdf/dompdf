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
        if ($this->_frame->find_pageable_context()->is_full()) {
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

            if ($this->_frame->find_pageable_context()->is_full()) {
                break;
            }
        }

        if ($this->_frame->find_pageable_context()->is_full()) {
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
        
        // split the row if one of the children was split
        foreach ($this->_frame->get_children() as $child) {
            if ($child->_split_frame !== null) {
                $frame->split($child, true, false);
                // Preserve the current counter values. This must be done after the
                // parent split, as counters get reset on frame reset

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
