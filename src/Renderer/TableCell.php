<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;
use Dompdf\FrameDecorator\Table;

/**
 * Renders table cells
 *
 * @package dompdf
 */
class TableCell extends Block
{

    /**
     * @param Frame $frame
     */
    function render(Frame $frame)
    {
        $style = $frame->get_style();

        if (trim($frame->get_node()->nodeValue) === "" && $style->empty_cells === "hide") {
            return;
        }

        $this->_set_opacity($frame->get_opacity($style->opacity));

        $border_box = $frame->get_border_box();
        $table = Table::find_parent_table($frame);

        if ($table->get_style()->border_collapse !== "collapse") {
            $this->_render_background($frame, $border_box);
            $this->_render_border($frame, $border_box);
            $this->_render_outline($frame, $border_box);
        } else {
            // The collapsed case is slightly complicated...

            $cells = $table->get_cellmap()->get_spanned_cells($frame);

            if (is_null($cells)) {
                return;
            }

            // Render the background to the padding box, as the cells are
            // rendered individually one after another, and we don't want the
            // background to overlap an adjacent border
            $padding_box = $frame->get_padding_box();

            $this->_render_background($frame, $padding_box);
            $this->_render_collapsed_border($frame, $table);

            // FIXME: Outline should be drawn over other cells
            $this->_render_outline($frame, $border_box);
        }

        $id = $frame->get_node()->getAttribute("id");
        if (strlen($id) > 0) {
            $this->_canvas->add_named_dest($id);
        }

        // $this->debugBlockLayout($frame, "red", false);
    }

    /**
     * @param Frame $frame
     * @param Table $table
     */
    protected function _render_collapsed_border(Frame $frame, Table $table): void
    {
        $cellmap = $table->get_cellmap();
        $cells = $cellmap->get_spanned_cells($frame);
        $num_rows = $cellmap->get_num_rows();
        $num_cols = $cellmap->get_num_cols();

        [$table_x, $table_y] = $table->get_position();

        // Determine the top row spanned by this cell
        $i = $cells["rows"][0];
        $top_row = $cellmap->get_row($i);

        // Determine if this cell borders on the bottom of the table.  If so,
        // then we draw its bottom border.  Otherwise the next row down will
        // draw its top border instead.
        if (in_array($num_rows - 1, $cells["rows"])) {
            $draw_bottom = true;
            $bottom_row = $cellmap->get_row($num_rows - 1);
        } else {
            $draw_bottom = false;
        }

        // Draw the horizontal borders
        foreach ($cells["columns"] as $j) {
            $bp = $cellmap->get_border_properties($i, $j);
            $col = $cellmap->get_column($j);

            $x = $table_x + $col["x"] - $bp["left"]["width"] / 2;
            $y = $table_y + $top_row["y"] - $bp["top"]["width"] / 2;
            $w = $col["used-width"] + ($bp["left"]["width"] + $bp["right"]["width"]) / 2;

            if ($bp["top"]["width"] > 0) {
                $widths = [
                    (float)$bp["top"]["width"],
                    (float)$bp["right"]["width"],
                    (float)$bp["bottom"]["width"],
                    (float)$bp["left"]["width"]
                ];

                $method = "_border_" . $bp["top"]["style"];
                $this->$method($x, $y, $w, $bp["top"]["color"], $widths, "top", "square");
            }

            if ($draw_bottom) {
                $bp = $cellmap->get_border_properties($num_rows - 1, $j);
                if ($bp["bottom"]["width"] <= 0) {
                    continue;
                }
                
                $widths = [
                    (float)$bp["top"]["width"],
                    (float)$bp["right"]["width"],
                    (float)$bp["bottom"]["width"],
                    (float)$bp["left"]["width"]
                ];

                $y = $table_y + $bottom_row["y"] + $bottom_row["height"] + $bp["bottom"]["width"] / 2;

                $method = "_border_" . $bp["bottom"]["style"];
                $this->$method($x, $y, $w, $bp["bottom"]["color"], $widths, "bottom", "square");
            }
        }

        $j = $cells["columns"][0];
        $left_col = $cellmap->get_column($j);

        if (in_array($num_cols - 1, $cells["columns"])) {
            $draw_right = true;
            $right_col = $cellmap->get_column($num_cols - 1);
        } else {
            $draw_right = false;
        }

        // Draw the vertical borders
        foreach ($cells["rows"] as $i) {
            $bp = $cellmap->get_border_properties($i, $j);
            $row = $cellmap->get_row($i);

            $x = $table_x + $left_col["x"] - $bp["left"]["width"] / 2;
            $y = $table_y + $row["y"] - $bp["top"]["width"] / 2;
            $h = $row["height"] + ($bp["top"]["width"] + $bp["bottom"]["width"]) / 2;

            if ($bp["left"]["width"] > 0) {
                $widths = [
                    (float)$bp["top"]["width"],
                    (float)$bp["right"]["width"],
                    (float)$bp["bottom"]["width"],
                    (float)$bp["left"]["width"]
                ];

                $method = "_border_" . $bp["left"]["style"];
                $this->$method($x, $y, $h, $bp["left"]["color"], $widths, "left", "square");
            }

            if ($draw_right) {
                $bp = $cellmap->get_border_properties($i, $num_cols - 1);
                if ($bp["right"]["width"] <= 0) {
                    continue;
                }

                $widths = [
                    (float)$bp["top"]["width"],
                    (float)$bp["right"]["width"],
                    (float)$bp["bottom"]["width"],
                    (float)$bp["left"]["width"]
                ];

                $x = $table_x + $right_col["x"] + $right_col["used-width"] + $bp["right"]["width"] / 2;

                $method = "_border_" . $bp["right"]["style"];
                $this->$method($x, $y, $h, $bp["right"]["color"], $widths, "right", "square");
            }
        }
    }
}
