<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: table_cell_frame_decorator.cls.php,v $
 * Created on: 2004-07-29
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

/* $Id: table_cell_frame_decorator.cls.php,v 1.5 2006-07-07 21:31:04 benjcarson Exp $ */

/**
 * Decorates table cells for layout
 *
 * @access private
 * @package dompdf
 */
class Table_Cell_Frame_Decorator extends Block_Frame_Decorator {
  
  protected $_resolved_borders;
  protected $_content_height;
  
  //........................................................................

  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
    $this->_resolved_borders = array();
    $this->_content_height = 0;    
  }

  //........................................................................

  function reset() {
    parent::reset();
    $this->_resolved_borders = array();
    $this->_content_height = 0;
    $this->_frame->reset();    
  }
  
  function get_content_height() {
    return $this->_content_height;
  }

  function set_content_height($height) {
    $this->_content_height = $height;
  }
  
  function set_cell_height($height) {
    $style = $this->get_style();
    $v_space = $style->length_in_pt(array($style->margin_top,
                                          $style->padding_top,
                                          $style->border_top_width,
                                          $style->border_bottom_width,
                                          $style->padding_bottom,
                                          $style->margin_bottom),
                                    $style->width);

    $new_height = $height - $v_space;    
    $style->height = $new_height;

    if ( $new_height > $this->_content_height ) {
      // Adjust our vertical alignment
      $valign = $style->vertical_align;

      switch ($valign) {

      default:
      case "baseline":
        // FIXME: this isn't right
        
      case "top":
        // Don't need to do anything
        return;

      case "middle":
        $delta = ($new_height - $this->_content_height) / 2;
        break;

      case "bottom":
        $delta = $new_height - $this->_content_height;
        break;

      }
   
      // Move our children
      foreach ( $this->get_lines() as $i => $line ) {
        foreach ( $line["frames"] as $frame )
          $frame->set_position( null, $frame->get_position("y") + $delta );
      }
   }
        
  }

  function set_resolved_border($side, $border_spec) {    
    $this->_resolved_borders[$side] = $border_spec;
  }

  //........................................................................

  function get_resolved_border($side) {
    return $this->_resolved_borders[$side];
  }

  function get_resolved_borders() { return $this->_resolved_borders; }
}
?>