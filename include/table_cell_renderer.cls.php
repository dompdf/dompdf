<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: table_cell_renderer.cls.php,v $
 * Created on: 2004-06-09
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.digitaljunkies.ca/dompdf
 *
 * @link http://www.digitaljunkies.ca/dompdf
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf
 * @version 0.5.1
 */

/* $Id: table_cell_renderer.cls.php,v 1.5 2006-07-07 21:31:04 benjcarson Exp $ */

/**
 * Renders table cells
 *
 * @access private
 * @package dompdf
 */
class Table_Cell_Renderer extends Block_Renderer {

  //........................................................................

  function render(Frame $frame) {
    $style = $frame->get_style();
    list($x, $y, $w, $h) = $frame->get_padding_box();

    // Draw our background, border and content
    if ( ($bg = $style->background_color) !== "transparent" ) {
      list($x, $y, $w, $h) = $frame->get_padding_box();
      $this->_canvas->filled_rectangle( $x, $y, $w, $h, $style->background_color );
    }

    if ( ($url = $style->background_image) && $url !== "none" ) {
      $this->_background_image($url, $x, $y, $w, $h, $style);
    }

    if ( $style->border_collapse != "collapse" ) {
      $this->_render_border($frame, "bevel");
      return;
    }

    // The collapsed case is slightly complicated...

    $cellmap = Table_Frame_Decorator::find_parent_table($frame)->get_cellmap();
    $cells = $cellmap->get_spanned_cells($frame);
    $num_rows = $cellmap->get_num_rows();
    $num_cols = $cellmap->get_num_cols();

    // Determine the top row spanned by this cell
    $i = $cells["rows"][0];
    $top_row = $cellmap->get_row($i);

    // Determine if this cell borders on the bottom of the table.  If so,
    // then we draw its bottom border.  Otherwise the next row down will
    // draw its top border instead.
    if (in_array( $num_rows - 1, $cells["rows"])) {
      $draw_bottom = true;
      $bottom_row = $cellmap->get_row($num_rows - 1);
    } else
      $draw_bottom = false;


    // Draw the horizontal borders
    foreach ( $cells["columns"] as $j ) {
      $bp = $cellmap->get_border_properties($i, $j);

      $y = $top_row["y"] - $bp["top"]["width"] / 2;

      $col = $cellmap->get_column($j);
      $x = $col["x"] - $bp["left"]["width"] / 2;
      $w = $col["used-width"] + ($bp["left"]["width"] + $bp["right"]["width"] ) / 2;

      if ( $bp["top"]["style"] != "none" && $bp["top"]["width"] > 0 ) {
        $widths = array($bp["top"]["width"],
                        $bp["right"]["width"],
                        $bp["bottom"]["width"],
                        $bp["left"]["width"]);
        $method = "_border_". $bp["top"]["style"];
        $this->$method($x, $y, $w, $bp["top"]["color"], $widths, "top", "square");
      }

      if ( $draw_bottom ) {
        $bp = $cellmap->get_border_properties($num_rows - 1, $j);
        if ( $bp["bottom"]["style"] == "none" || $bp["bottom"]["width"] <= 0 )
          continue;

        $y = $bottom_row["y"] + $bottom_row["height"] + $bp["bottom"]["width"] / 2;

        $widths = array($bp["top"]["width"],
                        $bp["right"]["width"],
                        $bp["bottom"]["width"],
                        $bp["left"]["width"]);
        $method = "_border_". $bp["bottom"]["style"];
        $this->$method($x, $y, $w, $bp["bottom"]["color"], $widths, "bottom", "square");

      }
    }

    $j = $cells["columns"][0];

    $left_col = $cellmap->get_column($j);

    if (in_array($num_cols - 1, $cells["columns"])) {
      $draw_right = true;
      $right_col = $cellmap->get_column($num_cols - 1);
    } else
      $draw_right = false;

    // Draw the vertical borders
    foreach ( $cells["rows"] as $i ) {
      $bp = $cellmap->get_border_properties($i, $j);

      $x = $left_col["x"] - $bp["left"]["width"] / 2;

      $row = $cellmap->get_row($i);

      $y = $row["y"] - $bp["top"]["width"] / 2;
      $h = $row["height"] + ($bp["top"]["width"] + $bp["bottom"]["width"])/ 2;

      if ( $bp["left"]["style"] != "none" && $bp["left"]["width"] > 0 ) {

        $widths = array($bp["top"]["width"],
                        $bp["right"]["width"],
                        $bp["bottom"]["width"],
                        $bp["left"]["width"]);

        $method = "_border_" . $bp["left"]["style"];
        $this->$method($x, $y, $h, $bp["left"]["color"], $widths, "left", "square");
      }

      if ( $draw_right ) {
        $bp = $cellmap->get_border_properties($i, $num_cols - 1);
        if ( $bp["right"]["style"] == "none" || $bp["right"]["width"] <= 0 )
          continue;

        $x = $right_col["x"] + $right_col["used-width"] + $bp["right"]["width"] / 2;

        $widths = array($bp["top"]["width"],
                        $bp["right"]["width"],
                        $bp["bottom"]["width"],
                        $bp["left"]["width"]);

        $method = "_border_" . $bp["right"]["style"];
        $this->$method($x, $y, $h, $bp["right"]["color"], $widths, "right", "square");

      }
    }

  }
}
?>