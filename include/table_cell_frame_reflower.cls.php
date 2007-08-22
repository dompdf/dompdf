<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: table_cell_frame_reflower.cls.php,v $
 * Created on: 2004-06-07
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

/* $Id: table_cell_frame_reflower.cls.php,v 1.12 2007-08-22 23:02:07 benjcarson Exp $ */


/**
 * Reflows table cells
 *
 * @access private
 * @package dompdf
 */
class Table_Cell_Frame_Reflower extends Block_Frame_Reflower {

  //........................................................................

  function __construct(Frame $frame) {
    parent::__construct($frame);
  }

  //........................................................................

  function reflow() {

    $style = $this->_frame->get_style();

    $table = Table_Frame_Decorator::find_parent_table($this->_frame);
    $cellmap = $table->get_cellmap();

    list($x, $y) = $cellmap->get_frame_position($this->_frame);
    $this->_frame->set_position($x, $y);

    $cells = $cellmap->get_spanned_cells($this->_frame);

    $w = 0;
    foreach ( $cells["columns"] as $i ) {
      $col = $cellmap->get_column( $i );
      $w += $col["used-width"];
    }

    //FIXME?
    $h = $this->_frame->get_containing_block("h");

    $left_space = $style->length_in_pt(array($style->margin_left,
                                             $style->padding_left,
                                             $style->border_left_width),
                                       $w);

    $right_space = $style->length_in_pt(array($style->padding_right,
                                              $style->margin_right,
                                              $style->border_right_width),
                                        $w);

    $top_space = $style->length_in_pt(array($style->margin_top,
                                            $style->padding_top,
                                            $style->border_top_width),
                                      $w);
    $bottom_space = $style->length_in_pt(array($style->margin_bottom,
                                               $style->padding_bottom,
                                               $style->border_bottom_width),
                                      $w);

    $style->width = $cb_w = $w - $left_space - $right_space;

    $content_x = $x + $left_space;
    $content_y = $line_y = $y + $top_space;

    // Adjust the first line based on the text-indent property
    $indent = $style->length_in_pt($style->text_indent, $w);
    $this->_frame->increase_line_width($indent);

    // Set the y position of the first line in the cell
    $page = $this->_frame->get_root();
    $this->_frame->set_current_line($line_y);
    
    // Set the containing blocks and reflow each child
    foreach ( $this->_frame->get_children() as $child ) {
      
      if ( $page->is_full() )
        break;
    
      $child->set_containing_block($content_x, $content_y, $cb_w, $h);
      $child->reflow();

      $this->_frame->add_frame_to_line( $child );

    }

    // Determine our height
    $style_height = $style->length_in_pt($style->height, $w);

    $this->_frame->set_content_height($this->_calculate_content_height());

    $height = max($style_height, $this->_frame->get_content_height());

    // Let the cellmap know our height
    $cell_height = $height / count($cells["rows"]);

    if ($style_height < $height)
      $cell_height += $top_space + $bottom_space;

    foreach ($cells["rows"] as $i)
      $cellmap->set_row_height($i, $cell_height);

    $style->height = $height;

    $this->_text_align();

    $this->vertical_align();

  }

}
?>