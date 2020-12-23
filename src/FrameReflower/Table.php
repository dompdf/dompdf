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

/**
 * Reflows tables
 *
 * @access  private
 * @package dompdf
 */
class Table extends AbstractFrameReflower
{
    /**
     * Frame for this reflower
     *
     * @var TableFrameDecorator
     */
    protected $_frame;

    /**
     * Cache of results between call to get_min_max_width and assign_widths
     *
     * @var array
     */
    protected $_state;

    /**
     * Table constructor.
     * @param TableFrameDecorator $frame
     */
    function __construct(TableFrameDecorator $frame)
    {
        $this->_state = null;
        parent::__construct($frame);
    }

    /**
     * State is held here so it needs to be reset along with the decorator
     */
    function reset()
    {
        $this->_state = null;
        $this->_min_max_cache = null;
    }

    protected function _assign_widths()
    {
        $style = $this->_frame->get_style();

        // Find the min/max width of the table and sort the columns into
        // absolute/percent/auto arrays
        $min_width = $this->_state["min_width"];
        $max_width = $this->_state["max_width"];
        $percent_used = $this->_state["percent_used"];
        $absolute_used = $this->_state["absolute_used"];
        $auto_min = $this->_state["auto_min"];

        $absolute =& $this->_state["absolute"];
        $percent =& $this->_state["percent"];
        $auto =& $this->_state["auto"];

        // Determine the actual width of the table
        $cb = $this->_frame->get_containing_block();
        $columns =& $this->_frame->get_cellmap()->get_columns();

        $width = $style->width;

        // Calculate padding & border fudge factor
        $left = $style->margin_left;
        $right = $style->margin_right;

        $centered = ($left === "auto" && $right === "auto");

        $left = (float)($left === "auto" ? 0 : $style->length_in_pt($left, $cb["w"]));
        $right = (float)($right === "auto" ? 0 : $style->length_in_pt($right, $cb["w"]));

        $delta = $left + $right;

        if (!$centered) {
            $delta += (float)$style->length_in_pt([
                    $style->padding_left,
                    $style->border_left_width,
                    $style->border_right_width,
                    $style->padding_right],
                $cb["w"]);
        }

        $min_table_width = (float)$style->length_in_pt($style->min_width, $cb["w"] - $delta);

        // min & max widths already include borders & padding
        $min_width -= $delta;
        $max_width -= $delta;

        if ($width !== "auto") {
            $preferred_width = (float)$style->length_in_pt($width, $cb["w"]) - $delta;

            if ($preferred_width < $min_table_width) {
                $preferred_width = $min_table_width;
            }

            if ($preferred_width > $min_width) {
                $width = $preferred_width;
            } else {
                $width = $min_width;
            }

        } else {
            if ($max_width + $delta < $cb["w"]) {
                $width = $max_width;
            } else if ($cb["w"] - $delta > $min_width) {
                $width = $cb["w"] - $delta;
            } else {
                $width = $min_width;
            }

            if ($width < $min_table_width) {
                $width = $min_table_width;
            }

        }

        // Store our resolved width
        $style->width = $width;

        $cellmap = $this->_frame->get_cellmap();

        if ($cellmap->is_columns_locked()) {
            return;
        }

        // If the whole table fits on the page, then assign each column it's max width
        if ($width == $max_width) {
            foreach (array_keys($columns) as $i) {
                $cellmap->set_column_width($i, $columns[$i]["max-width"]);
            }

            return;
        }

        // Determine leftover and assign it evenly to all columns
        if ($width > $min_width) {
            // We have four cases to deal with:
            //
            // 1. All columns are auto--no widths have been specified.  In this
            // case we distribute extra space across all columns weighted by max-width.
            //
            // 2. Only absolute widths have been specified.  In this case we
            // distribute any extra space equally among 'width: auto' columns, or all
            // columns if no auto columns have been specified.
            //
            // 3. Only percentage widths have been specified.  In this case we
            // normalize the percentage values and distribute any remaining % to
            // width: auto columns.  We then proceed to assign widths as fractions
            // of the table width.
            //
            // 4. Both absolute and percentage widths have been specified.

            $increment = 0;

            // Case 1:
            if ($absolute_used == 0 && $percent_used == 0) {
                $increment = $width - $min_width;

                foreach (array_keys($columns) as $i) {
                    $cellmap->set_column_width($i, $columns[$i]["min-width"] + $increment * ($columns[$i]["max-width"] / $max_width));
                }
                return;
            }

            // Case 2
            if ($absolute_used > 0 && $percent_used == 0) {
                if (count($auto) > 0) {
                    $increment = ($width - $auto_min - $absolute_used) / count($auto);
                }

                // Use the absolutely specified width or the increment
                foreach (array_keys($columns) as $i) {
                    if ($columns[$i]["absolute"] > 0 && count($auto)) {
                        $cellmap->set_column_width($i, $columns[$i]["min-width"]);
                    } else if (count($auto)) {
                        $cellmap->set_column_width($i, $columns[$i]["min-width"] + $increment);
                    } else {
                        // All absolute columns
                        $increment = ($width - $absolute_used) * $columns[$i]["absolute"] / $absolute_used;

                        $cellmap->set_column_width($i, $columns[$i]["min-width"] + $increment);
                    }

                }
                return;
            }

            // Case 3:
            if ($absolute_used == 0 && $percent_used > 0) {
                $scale = null;
                $remaining = null;

                // Scale percent values if the total percentage is > 100, or if all
                // values are specified as percentages.
                if ($percent_used > 100 || count($auto) == 0) {
                    $scale = 100 / $percent_used;
                } else {
                    $scale = 1;
                }

                // Account for the minimum space used by the unassigned auto columns
                $used_width = $auto_min;

                foreach ($percent as $i) {
                    $columns[$i]["percent"] *= $scale;

                    $slack = $width - $used_width;

                    $w = min($columns[$i]["percent"] * $width / 100, $slack);

                    if ($w < $columns[$i]["min-width"]) {
                        $w = $columns[$i]["min-width"];
                    }

                    $cellmap->set_column_width($i, $w);
                    $used_width += $w;

                }

                // This works because $used_width includes the min-width of each
                // unassigned column
                if (count($auto) > 0) {
                    $increment = ($width - $used_width) / count($auto);

                    foreach ($auto as $i) {
                        $cellmap->set_column_width($i, $columns[$i]["min-width"] + $increment);
                    }

                }
                return;
            }

            // Case 4:

            // First-come, first served
            if ($absolute_used > 0 && $percent_used > 0) {
                $used_width = $auto_min;

                foreach ($absolute as $i) {
                    $cellmap->set_column_width($i, $columns[$i]["min-width"]);
                    $used_width += $columns[$i]["min-width"];
                }

                // Scale percent values if the total percentage is > 100 or there
                // are no auto values to take up slack
                if ($percent_used > 100 || count($auto) == 0) {
                    $scale = 100 / $percent_used;
                } else {
                    $scale = 1;
                }

                $remaining_width = $width - $used_width;

                foreach ($percent as $i) {
                    $slack = $remaining_width - $used_width;

                    $columns[$i]["percent"] *= $scale;
                    $w = min($columns[$i]["percent"] * $remaining_width / 100, $slack);

                    if ($w < $columns[$i]["min-width"]) {
                        $w = $columns[$i]["min-width"];
                    }

                    $columns[$i]["used-width"] = $w;
                    $used_width += $w;
                }

                if (count($auto) > 0) {
                    $increment = ($width - $used_width) / count($auto);

                    foreach ($auto as $i) {
                        $cellmap->set_column_width($i, $columns[$i]["min-width"] + $increment);
                    }
                }

                return;
            }
        } else { // we are over constrained
            // Each column gets its minimum width
            foreach (array_keys($columns) as $i) {
                $cellmap->set_column_width($i, $columns[$i]["min-width"]);
            }
        }
    }

    /**
     * Determine the frame's height based on min/max height
     *
     * @return float|int|mixed|string
     */
    protected function _calculate_height()
    {
        $style = $this->_frame->get_style();
        $height = $style->height;

        $cellmap = $this->_frame->get_cellmap();
        $cellmap->assign_frame_heights();
        $rows = $cellmap->get_rows();

        // Determine our content height
        $content_height = 0;
        foreach ($rows as $r) {
            $content_height += $r["height"];
        }

        $cb = $this->_frame->get_containing_block();

        if (!($style->overflow === "visible" ||
            ($style->overflow === "hidden" && $height === "auto"))
        ) {
            // Only handle min/max height if the height is independent of the frame's content

            $min_height = $style->min_height;
            $max_height = $style->max_height;

            if (isset($cb["h"])) {
                $min_height = $style->length_in_pt($min_height, $cb["h"]);
                $max_height = $style->length_in_pt($max_height, $cb["h"]);

            } else if (isset($cb["w"])) {
                if (mb_strpos($min_height, "%") !== false) {
                    $min_height = 0;
                } else {
                    $min_height = $style->length_in_pt($min_height, $cb["w"]);
                }
                if (mb_strpos($max_height, "%") !== false) {
                    $max_height = "none";
                } else {
                    $max_height = $style->length_in_pt($max_height, $cb["w"]);
                }
            }

            if ($max_height !== "none" && $max_height !== "auto" && (float)$min_height > (float)$max_height) {
                // Swap 'em
                list($max_height, $min_height) = [$min_height, $max_height];
            }

            if ($max_height !== "none" && $max_height !== "auto" && $height > (float)$max_height) {
                $height = $max_height;
            }

            if ($height < (float)$min_height) {
                $height = $min_height;
            }
        } else {
            // Use the content height or the height value, whichever is greater
            if ($height !== "auto") {
                $height = $style->length_in_pt($height, $cb["h"]);

                if ($height <= $content_height) {
                    $height = $content_height;
                } else {
                    $cellmap->set_frame_heights($height, $content_height);
                }
            } else {
                $height = $content_height;
            }
        }

        return $height;
    }

    /**
     * @param BlockFrameDecorator $block
     */
    function reflow(BlockFrameDecorator $block = null)
    {
        /** @var TableFrameDecorator */
        $frame = $this->_frame;

        // Check if a page break is forced
        $page = $frame->get_root();
        $page->check_forced_page_break($frame);

        // Bail if the page is full
        if ($page->is_full()) {
            return;
        }

        // Let the page know that we're reflowing a table so that splits
        // are suppressed (simply setting page-break-inside: avoid won't
        // work because we may have an arbitrary number of block elements
        // inside tds.)
        $page->table_reflow_start();

        // Collapse vertical margins, if required
        $this->_collapse_margins();

        $frame->position();

        // Table layout algorithm:
        // http://www.w3.org/TR/CSS21/tables.html#auto-table-layout

        if (is_null($this->_state)) {
            $this->get_min_max_width();
        }

        $cb = $frame->get_containing_block();
        $style = $frame->get_style();

        // This is slightly inexact, but should be okay.  Add half the
        // border-spacing to the table as padding.  The other half is added to
        // the cells themselves.
        if ($style->border_collapse === "separate") {
            list($h, $v) = $style->border_spacing;

            $v = (float)$style->length_in_pt($v) / 2;
            $h = (float)$style->length_in_pt($h) / 2;

            $style->padding_left = (float)$style->length_in_pt($style->padding_left, $cb["w"]) + $h;
            $style->padding_right = (float)$style->length_in_pt($style->padding_right, $cb["w"]) + $h;
            $style->padding_top = (float)$style->length_in_pt($style->padding_top, $cb["h"]) + $v;
            $style->padding_bottom = (float)$style->length_in_pt($style->padding_bottom, $cb["h"]) + $v;
        }

        $this->_assign_widths();

        // Adjust left & right margins, if they are auto
        $width = $style->width;
        $left = $style->margin_left;
        $right = $style->margin_right;

        $diff = (float)$cb["w"] - (float)$width;

        if ($left === "auto" && $right === "auto") {
            if ($diff < 0) {
                $left = 0;
                $right = $diff;
            } else {
                $left = $right = $diff / 2;
            }

            $style->margin_left = sprintf("%Fpt", $left);
            $style->margin_right = sprintf("%Fpt", $right);;
        } else {
            if ($left === "auto") {
                $left = (float)$style->length_in_pt($cb["w"], $cb["w"]) - (float)$style->length_in_pt($right, $cb["w"]) - (float)$style->length_in_pt($width, $cb["w"]);
            }
            if ($right === "auto") {
                $left = (float)$style->length_in_pt($left, $cb["w"]);
            }
        }

        list($x, $y) = $frame->get_position();

        // Determine the content edge
        $content_x = $x + (float)$left + (float)$style->length_in_pt([$style->padding_left,
                $style->border_left_width], $cb["w"]);
        $content_y = $y + (float)$style->length_in_pt([$style->margin_top,
                $style->border_top_width,
                $style->padding_top], $cb["h"]);

        if (isset($cb["h"])) {
            $h = $cb["h"];
        } else {
            $h = null;
        }

        $cellmap = $frame->get_cellmap();
        $col =& $cellmap->get_column(0);
        $col["x"] = $content_x;

        $row =& $cellmap->get_row(0);
        $row["y"] = $content_y;

        $cellmap->assign_x_positions();

        // Set the containing block of each child & reflow
        foreach ($frame->get_children() as $child) {
            // Bail if the page is full
            if (!$page->in_nested_table() && $page->is_full()) {
                break;
            }

            $child->set_containing_block($content_x, $content_y, $width, $h);
            $child->reflow();

            if (!$page->in_nested_table()) {
                // Check if a split has occured
                $page->check_page_break($child);
            }

        }

        // Assign heights to our cells:
        $style->height = $this->_calculate_height();

        if ($style->border_collapse === "collapse") {
            // Unset our borders because our cells are now using them
            $style->border_style = "none";
        }

        $page->table_reflow_end();

        // Debugging:
        //echo ($this->_frame->get_cellmap());

        if ($block && $style->float === "none" && $frame->is_in_flow()) {
            $block->add_frame_to_line($frame);
            $block->add_line();
        }
    }

    /**
     * @return array|null
     */
    function get_min_max_width()
    {
        if (!is_null($this->_min_max_cache)) {
            return $this->_min_max_cache;
        }

        $style = $this->_frame->get_style();

        $this->_frame->normalise();

        // Add the cells to the cellmap (this will calcluate column widths as
        // frames are added)
        $this->_frame->get_cellmap()->add_frame($this->_frame);

        // Find the min/max width of the table and sort the columns into
        // absolute/percent/auto arrays
        $this->_state = [];
        $this->_state["min_width"] = 0;
        $this->_state["max_width"] = 0;

        $this->_state["percent_used"] = 0;
        $this->_state["absolute_used"] = 0;
        $this->_state["auto_min"] = 0;

        $this->_state["absolute"] = [];
        $this->_state["percent"] = [];
        $this->_state["auto"] = [];

        $columns =& $this->_frame->get_cellmap()->get_columns();
        foreach (array_keys($columns) as $i) {
            $this->_state["min_width"] += $columns[$i]["min-width"];
            $this->_state["max_width"] += $columns[$i]["max-width"];

            if ($columns[$i]["absolute"] > 0) {
                $this->_state["absolute"][] = $i;
                $this->_state["absolute_used"] += $columns[$i]["absolute"];
            } else if ($columns[$i]["percent"] > 0) {
                $this->_state["percent"][] = $i;
                $this->_state["percent_used"] += $columns[$i]["percent"];
            } else {
                $this->_state["auto"][] = $i;
                $this->_state["auto_min"] += $columns[$i]["min-width"];
            }
        }

        // Account for margins & padding
        $dims = [$style->border_left_width,
            $style->border_right_width,
            $style->padding_left,
            $style->padding_right,
            $style->margin_left,
            $style->margin_right];

        if ($style->border_collapse !== "collapse") {
            list($dims[]) = $style->border_spacing;
        }

        $delta = (float)$style->length_in_pt($dims, $this->_frame->get_containing_block("w"));

        $this->_state["min_width"] += $delta;
        $this->_state["max_width"] += $delta;

        return $this->_min_max_cache = [
            $this->_state["min_width"],
            $this->_state["max_width"],
            "min" => $this->_state["min_width"],
            "max" => $this->_state["max_width"],
        ];
    }
}
