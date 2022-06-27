<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Table as TableFrameDecorator;
use Dompdf\Helpers;

/**
 * Reflows tables
 *
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
    public function reset(): void
    {
        parent::reset();
        $this->_state = null;
    }

    protected function _assign_widths()
    {
        $style = $this->_frame->get_style();

        // Find the min/max width of the table and sort the columns into
        // absolute/percent/auto arrays
        $delta = $this->_state["width_delta"];
        $min_width = $this->_state["min_width"];
        $max_width = $this->_state["max_width"];
        $percent_used = $this->_state["percent_used"];
        $absolute_used = $this->_state["absolute_used"];
        $auto_min = $this->_state["auto_min"];

        $absolute =& $this->_state["absolute"];
        $percent =& $this->_state["percent"];
        $auto =& $this->_state["auto"];

        // Determine the actual width of the table (excluding borders and
        // padding)
        $cb = $this->_frame->get_containing_block();
        $columns =& $this->_frame->get_cellmap()->get_columns();

        $width = $style->width;
        $min_table_width = $this->resolve_min_width($cb["w"]) - $delta;

        if ($width !== "auto") {
            $preferred_width = (float) $style->length_in_pt($width, $cb["w"]) - $delta;

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
            } elseif ($cb["w"] - $delta > $min_width) {
                $width = $cb["w"] - $delta;
            } else {
                $width = $min_width;
            }

            if ($width < $min_table_width) {
                $width = $min_table_width;
            }

        }

        // Store our resolved width
        $style->set_used("width", $width);

        $cellmap = $this->_frame->get_cellmap();

        if ($cellmap->is_columns_locked()) {
            return;
        }

        // If the whole table fits on the page, then assign each column it's max width
        if ($width == $max_width) {
            foreach ($columns as $i => $col) {
                $cellmap->set_column_width($i, $col["max-width"]);
            }

            return;
        }

        // Determine leftover and assign it evenly to all columns
        if ($width > $min_width) {
            // We have three cases to deal with:
            //
            // 1. All columns are auto or absolute width.  In this case we
            // distribute extra space across all auto columns weighted by the
            // difference between their max and min width, or by max width only
            // if the width of the table is larger than the max width for all
            // columns.
            //
            // 2. Only absolute widths have been specified, no auto columns.  In
            // this case we distribute extra space across all columns weighted
            // by their absolute width.
            //
            // 3. Percentage widths have been specified.  In this case we normalize
            // the percentage values and try to assign widths as fractions of
            // the table width. Absolute column widths are fully satisfied and
            // any remaining space is evenly distributed among all auto columns.

            // Case 1:
            if ($percent_used == 0 && count($auto)) {
                foreach ($absolute as $i) {
                    $w = $columns[$i]["min-width"];
                    $cellmap->set_column_width($i, $w);
                }

                if ($width < $max_width) {
                    $increment = $width - $min_width;
                    $table_delta = $max_width - $min_width;

                    foreach ($auto as $i) {
                        $min = $columns[$i]["min-width"];
                        $max = $columns[$i]["max-width"];
                        $col_delta = $max - $min;
                        $w = $min + $increment * ($col_delta / $table_delta);
                        $cellmap->set_column_width($i, $w);
                    }
                } else {
                    $increment = $width - $max_width;
                    $auto_max = $max_width - $absolute_used;

                    foreach ($auto as $i) {
                        $max = $columns[$i]["max-width"];
                        $f = $auto_max > 0 ? $max / $auto_max : 1 / count($auto);
                        $w = $max + $increment * $f;
                        $cellmap->set_column_width($i, $w);
                    }
                }
                return;
            }

            // Case 2:
            if ($percent_used == 0 && !count($auto)) {
                $increment = $width - $absolute_used;

                foreach ($absolute as $i) {
                    $abs = $columns[$i]["min-width"];
                    $f = $absolute_used > 0 ? $abs / $absolute_used : 1 / count($absolute);
                    $w = $abs + $increment * $f;
                    $cellmap->set_column_width($i, $w);
                }
                return;
            }

            // Case 3:
            if ($percent_used > 0) {
                // Scale percent values if the total percentage is > 100 or
                // there are no auto values to take up slack
                if ($percent_used > 100 || count($auto) == 0) {
                    $scale = 100 / $percent_used;
                } else {
                    $scale = 1;
                }

                // Account for the minimum space used by the unassigned auto
                // columns, by the columns with absolute widths, and the
                // percentage columns following the current one
                $used_width = $auto_min + $absolute_used;

                foreach ($absolute as $i) {
                    $w = $columns[$i]["min-width"];
                    $cellmap->set_column_width($i, $w);
                }

                $percent_min = 0;

                foreach ($percent as $i) {
                    $percent_min += $columns[$i]["min-width"];
                }

                // First-come, first served
                foreach ($percent as $i) {
                    $min = $columns[$i]["min-width"];
                    $percent_min -= $min;
                    $slack = $width - $used_width - $percent_min;

                    $columns[$i]["percent"] *= $scale;
                    $w = min($columns[$i]["percent"] * $width / 100, $slack);

                    if ($w < $min) {
                        $w = $min;
                    }

                    $cellmap->set_column_width($i, $w);
                    $used_width += $w;
                }

                // This works because $used_width includes the min-width of each
                // unassigned column
                if (count($auto) > 0) {
                    $increment = ($width - $used_width) / count($auto);

                    foreach ($auto as $i) {
                        $w = $columns[$i]["min-width"] + $increment;
                        $cellmap->set_column_width($i, $w);
                    }
                }
                return;
            }
        } else {
            // We are over-constrained:
            // Each column gets its minimum width
            foreach ($columns as $i => $col) {
                $cellmap->set_column_width($i, $col["min-width"]);
            }
        }
    }

    /**
     * Determine the frame's height based on min/max height
     *
     * @return float
     */
    protected function _calculate_height()
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $cb = $frame->get_containing_block();

        $height = $style->length_in_pt($style->height, $cb["h"]);

        $cellmap = $frame->get_cellmap();
        $cellmap->assign_frame_heights();
        $rows = $cellmap->get_rows();

        // Determine our content height
        $content_height = 0.0;
        foreach ($rows as $r) {
            $content_height += $r["height"];
        }

        if ($height === "auto") {
            $height = $content_height;
        }

        // Handle min/max height
        // https://www.w3.org/TR/CSS21/visudet.html#min-max-heights
        $min_height = $this->resolve_min_height($cb["h"]);
        $max_height = $this->resolve_max_height($cb["h"]);
        $height = Helpers::clamp($height, $min_height, $max_height);

        // Use the content height or the height value, whichever is greater
        if ($height <= $content_height) {
            $height = $content_height;
        } else {
            // FIXME: Borders and row positions are not properly updated by this
            // $cellmap->set_frame_heights($height, $content_height);
        }

        return $height;
    }

    /**
     * @param BlockFrameDecorator|null $block
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

        $this->determine_absolute_containing_block();

        // Counters and generated content
        $this->_set_content();

        // Collapse vertical margins, if required
        $this->_collapse_margins();

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
            [$h, $v] = $style->border_spacing;
            $v = $v / 2;
            $h = $h / 2;

            $style->set_used("padding_left", (float)$style->length_in_pt($style->padding_left, $cb["w"]) + $h);
            $style->set_used("padding_right", (float)$style->length_in_pt($style->padding_right, $cb["w"]) + $h);
            $style->set_used("padding_top", (float)$style->length_in_pt($style->padding_top, $cb["w"]) + $v);
            $style->set_used("padding_bottom", (float)$style->length_in_pt($style->padding_bottom, $cb["w"]) + $v);
        }

        $this->_assign_widths();

        // Adjust left & right margins, if they are auto
        $delta = $this->_state["width_delta"];
        $width = $style->width;
        $left = $style->length_in_pt($style->margin_left, $cb["w"]);
        $right = $style->length_in_pt($style->margin_right, $cb["w"]);

        $diff = (float) $cb["w"] - (float) $width - $delta;

        if ($left === "auto" && $right === "auto") {
            if ($diff < 0) {
                $left = 0;
                $right = $diff;
            } else {
                $left = $right = $diff / 2;
            }
        } else {
            if ($left === "auto") {
                $left = max($diff - $right, 0);
            }
            if ($right === "auto") {
                $right = max($diff - $left, 0);
            }
        }

        $style->set_used("margin_left", $left);
        $style->set_used("margin_right", $right);

        $frame->position();
        [$x, $y] = $frame->get_position();

        // Determine the content edge
        $offset_x = (float)$left + (float)$style->length_in_pt([
            $style->padding_left,
            $style->border_left_width
        ], $cb["w"]);
        $offset_y = (float)$style->length_in_pt([
            $style->margin_top,
            $style->border_top_width,
            $style->padding_top
        ], $cb["w"]);
        $content_x = $x + $offset_x;
        $content_y = $y + $offset_y;

        if (isset($cb["h"])) {
            $h = $cb["h"];
        } else {
            $h = null;
        }

        $cellmap = $frame->get_cellmap();
        $col =& $cellmap->get_column(0);
        $col["x"] = $offset_x;

        $row =& $cellmap->get_row(0);
        $row["y"] = $offset_y;

        $cellmap->assign_x_positions();

        // Set the containing block of each child & reflow
        foreach ($frame->get_children() as $child) {
            $child->set_containing_block($content_x, $content_y, $width, $h);
            $child->reflow();

            if (!$page->in_nested_table()) {
                // Check if a split has occurred
                $page->check_page_break($child);
    
                if ($page->is_full()) {
                    break;
                }
            }
        }

        // Stop reflow if a page break has occurred before the frame, in which
        // case it has been reset, including its position
        if ($page->is_full() && $frame->get_position("x") === null) {
            $page->table_reflow_end();
            return;
        }

        // Assign heights to our cells:
        $style->set_used("height", $this->_calculate_height());

        $page->table_reflow_end();

        if ($block && $frame->is_in_flow()) {
            $block->add_frame_to_line($frame);

            if ($frame->is_block_level()) {
                $block->add_line();
            }
        }
    }

    public function get_min_max_width(): array
    {
        if (!is_null($this->_min_max_cache)) {
            return $this->_min_max_cache;
        }

        $style = $this->_frame->get_style();
        $cellmap = $this->_frame->get_cellmap();

        $this->_frame->normalize();

        // Add the cells to the cellmap (this will calculate column widths as
        // frames are added)
        $cellmap->add_frame($this->_frame);

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

        $columns =& $cellmap->get_columns();
        foreach ($columns as $i => $col) {
            $this->_state["min_width"] += $col["min-width"];
            $this->_state["max_width"] += $col["max-width"];

            if ($col["absolute"] > 0) {
                $this->_state["absolute"][] = $i;
                $this->_state["absolute_used"] += $col["min-width"];
            } elseif ($col["percent"] > 0) {
                $this->_state["percent"][] = $i;
                $this->_state["percent_used"] += $col["percent"];
            } else {
                $this->_state["auto"][] = $i;
                $this->_state["auto_min"] += $col["min-width"];
            }
        }

        // Account for margins, borders, padding, and border spacing
        $cb_w = $this->_frame->get_containing_block("w");
        $lm = (float) $style->length_in_pt($style->margin_left, $cb_w);
        $rm = (float) $style->length_in_pt($style->margin_right, $cb_w);

        $dims = [
            $style->border_left_width,
            $style->border_right_width,
            $style->padding_left,
            $style->padding_right
        ];

        if ($style->border_collapse !== "collapse") {
            list($dims[]) = $style->border_spacing;
        }

        $delta = (float) $style->length_in_pt($dims, $cb_w);

        $this->_state["width_delta"] = $delta;

        $min_width = $this->_state["min_width"] + $delta + $lm + $rm;
        $max_width = $this->_state["max_width"] + $delta + $lm + $rm;

        return $this->_min_max_cache = [
            $min_width,
            $max_width,
            "min" => $min_width,
            "max" => $max_width
        ];
    }
}
