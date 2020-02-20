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

        $id = $frame->get_node()->getAttribute("id");
        if (strlen($id) > 0)  {
            $this->_canvas->add_named_dest($id);
        }

        $this->_set_opacity($frame->get_opacity($style->opacity));
        list($x, $y, $w, $h) = $frame->get_border_box();
        

        $table = Table::find_parent_table($frame);

        if ($table->get_style()->border_collapse !== "collapse") {
            if (($bg = $style->background_color) !== "transparent") {
                $this->_canvas->filled_rectangle($x, $y, (float)$w, (float)$h, $bg);
            }

            if (($url = $style->background_image) && $url !== "none") {
                $this->_background_image($url, $x, $y, $w, $h, $style);
            }

            $this->_render_border($frame);
            $this->_render_outline($frame);
            return;
        }

        // The collapsed case is slightly complicated...
        // @todo Add support for outlines here

        $background_position_x = $x; $background_position_y = $y; $background_width = (float)$w; $background_height = (float)$h;
        $border_right_width = 0; $border_left_width = 0; $border_top_width = 0; $border_bottom_width = 0;
        $border_right_length = 0; $border_left_length = 0; $border_top_length = 0; $border_bottom_length = 0;

        $cellmap = $table->get_cellmap();
        $cells = $cellmap->get_spanned_cells($frame);

        if (is_null($cells)) {
            return;
        }

        $num_rows = $cellmap->get_num_rows();
        $num_cols = $cellmap->get_num_cols();

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
        $border_function_calls = [];
        foreach ($cells["columns"] as $j) {
            $bp = $cellmap->get_border_properties($i, $j);
            $col = $cellmap->get_column($j);

            $x = $col["x"] - $bp["left"]["width"] / 2;
            $y = $top_row["y"] - $bp["top"]["width"] / 2;
            $w = $col["used-width"] + ($bp["left"]["width"] + $bp["right"]["width"]) / 2;

            if ($bp["top"]["width"] > 0) {
                $widths = [
                    (float)$bp["top"]["width"],
                    (float)$bp["right"]["width"],
                    (float)$bp["bottom"]["width"],
                    (float)$bp["left"]["width"]
                ];

                $border_top_width = max($border_top_width, $widths[0]);
                
                $method = "_border_" . $bp["top"]["style"];
                $border_function_calls[] = [$method, [$x, $y, $w, $bp["top"]["color"], $widths, "top", "square"]];
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

                $y = $bottom_row["y"] + $bottom_row["height"] + $bp["bottom"]["width"] / 2;
                $border_bottom_width = max($border_bottom_width, $widths[2]);

                $method = "_border_" . $bp["bottom"]["style"];
                $border_function_calls[] = [$method, [$x, $y, $w, $bp["bottom"]["color"], $widths, "bottom", "square"]];
            } else {
                $adjacent_bp = $cellmap->get_border_properties($i+1, $j);
                $border_bottom_width = max($border_bottom_width, $adjacent_bp["top"]["width"]);
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

            $x = $left_col["x"] - $bp["left"]["width"] / 2;
            $y = $row["y"] - $bp["top"]["width"] / 2;
            $h = $row["height"] + ($bp["top"]["width"] + $bp["bottom"]["width"]) / 2;

            if ($bp["left"]["width"] > 0) {
                $widths = [
                    (float)$bp["top"]["width"],
                    (float)$bp["right"]["width"],
                    (float)$bp["bottom"]["width"],
                    (float)$bp["left"]["width"]
                ];

                $border_left_width = max($border_left_width, $widths[3]);

                $method = "_border_" . $bp["left"]["style"];
                $border_function_calls[] = [$method, [$x, $y, $h, $bp["left"]["color"], $widths, "left", "square"]];
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

                $x = $right_col["x"] + $right_col["used-width"] + $bp["right"]["width"] / 2;
                $border_right_width = max($border_right_width, $widths[1]);

                $method = "_border_" . $bp["right"]["style"];
                $border_function_calls[] = [$method, [$x, $y, $h, $bp["right"]["color"], $widths, "right", "square"]];
            } else {
                $adjacent_bp = $cellmap->get_border_properties($i, $j+1);
                $border_right_width = max($border_right_width, $adjacent_bp["left"]["width"]);
            }
        }

        // Draw our background, border and content
        if (($bg = $style->background_color) !== "transparent") {
            $this->_canvas->filled_rectangle(
                $background_position_x + ($border_left_width/2),
                $background_position_y + ($border_top_width/2),
                (float)$background_width - (($border_left_width + $border_right_width)/2),
                (float)$background_height - (($border_top_width + $border_bottom_width)/2),
                $bg
            );
        }
        if (($url = $style->background_image) && $url !== "none") {
            $this->_background_image(
                $url,
                $background_position_x + ($border_left_width/2),
                $background_position_y + ($border_top_width/2),
                (float)$background_width - (($border_left_width + $border_right_width)/2),
                (float)$background_height - (($border_top_width + $border_bottom_width)/2),
                $style
            );
        }
        foreach ($border_function_calls as $border_function_call_params)
        {
            call_user_func_array([$this, $border_function_call_params[0]], $border_function_call_params[1]);
        }
    }
}
