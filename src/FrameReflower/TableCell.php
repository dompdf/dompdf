<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Table as TableFrameDecorator;
use Dompdf\Helpers;

/**
 * Reflows table cells
 *
 * @package dompdf
 */
class TableCell extends Block
{
    /**
     * TableCell constructor.
     * @param BlockFrameDecorator $frame
     */
    function __construct(BlockFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    /**
     * @param BlockFrameDecorator|null $block
     */
    function reflow(BlockFrameDecorator $block = null)
    {
        // Counters and generated content
        $this->_set_content();

        $style = $this->_frame->get_style();

        $table = TableFrameDecorator::find_parent_table($this->_frame);
        $cellmap = $table->get_cellmap();

        list($x, $y) = $cellmap->get_frame_position($this->_frame);
        $this->_frame->set_position($x, $y);

        $cells = $cellmap->get_spanned_cells($this->_frame);

        $w = 0;
        foreach ($cells["columns"] as $i) {
            $col = $cellmap->get_column($i);
            $w += $col["used-width"];
        }

        //FIXME?
        $h = $this->_frame->get_containing_block("h");

        $left_space = (float)$style->length_in_pt([$style->margin_left,
                $style->padding_left,
                $style->border_left_width],
            $w);

        $right_space = (float)$style->length_in_pt([$style->padding_right,
                $style->margin_right,
                $style->border_right_width],
            $w);

        $top_space = (float)$style->length_in_pt([$style->margin_top,
                $style->padding_top,
                $style->border_top_width],
            $h);
        $bottom_space = (float)$style->length_in_pt([$style->margin_bottom,
                $style->padding_bottom,
                $style->border_bottom_width],
            $h);

        $style->width = $cb_w = $w - $left_space - $right_space;

        $content_x = $x + $left_space;
        $content_y = $line_y = $y + $top_space;

        // Adjust the first line based on the text-indent property
        $indent = (float)$style->length_in_pt($style->text_indent, $w);
        $this->_frame->increase_line_width($indent);

        $page = $this->_frame->get_root();

        // Set the y position of the first line in the cell
        $line_box = $this->_frame->get_current_line_box();
        $line_box->y = $line_y;

        // Set the containing blocks and reflow each child
        foreach ($this->_frame->get_children() as $child) {
            $child->set_containing_block($content_x, $content_y, $cb_w, $h);
            $this->process_clear($child);
            $child->reflow($this->_frame);
            $this->process_float($child, $content_x, $cb_w);

            if ($page->is_full()) {
                break;
            }
        }

        // Determine our height
        $style_height = (float)$style->length_in_pt($style->height, $h);

        $this->_frame->set_content_height($this->_calculate_content_height());

        $height = max($style_height, (float)$this->_frame->get_content_height());

        // Let the cellmap know our height
        $cell_height = $height / count($cells["rows"]);

        if ($style_height <= $height) {
            $cell_height += $top_space + $bottom_space;
        }

        foreach ($cells["rows"] as $i) {
            $cellmap->set_row_height($i, $cell_height);
        }

        $style->height = $height;
        $this->_text_align();
        $this->vertical_align();

        // Handle relative positioning
        foreach ($this->_frame->get_children() as $child) {
            $this->position_relative($child);
        }
    }

    public function get_min_max_content_width(): array
    {
        // Ignore percentage values for a specified width here, as they are
        // relative to the table width, which is not determined yet
        $style = $this->_frame->get_style();
        $width = $style->width;
        $fixed_width = $width !== "auto" && !Helpers::is_percent($width);

        [$min, $max] = $this->get_min_max_child_width();

        // For table cells: Use specified width if it is greater than the
        // minimum defined by the content
        if ($fixed_width) {
            $width = (float) $style->length_in_pt($width, 0);
            $min = max($width, $min);
            $max = $min;
        }

        // Handle min/max width style properties
        $min_width = $this->resolve_min_width(null);
        $max_width = $this->resolve_max_width(null);
        $min = Helpers::clamp($min, $min_width, $max_width);
        $max = Helpers::clamp($max, $min_width, $max_width);

        return [$min, $max];
    }
}
