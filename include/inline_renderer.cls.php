<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: inline_renderer.cls.php,v $
 * Created on: 2004-06-30
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
 * @version 0.3
 */

/* $Id: inline_renderer.cls.php,v 1.1.1.1 2005-01-25 22:56:02 benjcarson Exp $ */

/**
 * Renders inline frames
 *
 * @access private
 * @package dompdf
 */
class Inline_Renderer extends Abstract_Renderer {

  function __construct(Canvas $canvas) {
    parent::__construct($canvas);
  }
  
  //........................................................................

  function render(Frame $frame) {
    $style = $frame->get_style();

    if ( !$frame->get_first_child() )
      return; // No children, no service
    
    // Draw the left border if applicable
    $bp = $style->get_border_properties();
    $widths = array($style->length_in_pt($bp["top"]["width"]),
                    $style->length_in_pt($bp["right"]["width"]),
                    $style->length_in_pt($bp["bottom"]["width"]),
                    $style->length_in_pt($bp["left"]["width"]));
    
    // Draw the background & border behind each child.  To do this we need
    // to figure out just how much space each child takes:
    list($x, $y) = $frame->get_first_child()->get_position();
    $w = null;
    $h = 0;
    $x += $widths[3];
    $y += $widths[0];
    
    $first_row = true;

    foreach ($frame->get_children() as $child) {
      list($child_x, $child_y, $child_w, $child_h) = $child->get_padding_box();
      $child_h += $widths[2];
      
      if ( !is_null($w) && $child_x < $x + $w ) {

        // The next child is on another line.  Draw the background on this line.
        if ( ($bg = $style->background_color) !== "transparent" ) 
          $this->_canvas->filled_rectangle( $x, $y, $w, $h, $style->background_color);

        // If this is the first row, draw the left border
        if ( $first_row ) {

          if ( $bp["left"]["style"] != "none" && $bp["left"]["width"] > 0 ) {
            $method = "_border_" . $bp["left"]["style"];
            $this->$method($x, $y, $h, $bp["left"]["color"], $widths, "left");
          }
          $first_row = false;
        }

        // Draw the top & bottom borders
        if ( $bp["top"]["style"] != "none" && $bp["top"]["width"] > 0 ) {
          $method = "_border_" . $bp["top"]["style"];
          $this->$method($x, $y, $w, $bp["top"]["color"], $widths, "top");
        }
        
        if ( $bp["bottom"]["style"] != "none" && $bp["bottom"]["width"] > 0 ) {
          $method = "_border_" . $bp["bottom"]["style"];
          $this->$method($x, $y + $h, $w, $bp["bottom"]["color"], $widths, "bottom");
        }
        
        $x = $child_x + $widths[3];
        $y = $child_y + $widths[0];
        $w = $child_w;
        $h = $child_h;
        continue;
      }

      if ( is_null($w) )
        $w = $child_w;
      else
        $w += $child_w;
      
      $h = max($h, $child_h);
    }

    
    // Handle the last child
    if ( ($bg = $style->background_color) !== "transparent" ) 
      $this->_canvas->filled_rectangle( $x, $y, $w, $h, $style->background_color);
    
    // If this is the first row, draw the left border too
    if ( $first_row && $bp["left"]["style"] != "none" && $widths[3] > 0 ) {
      $method = "_border_" . $bp["left"]["style"];
      $this->$method($x, $y, $h, $bp["left"]["color"], $widths, "left");
    }
    
    // Draw the top & bottom borders
    if ( $bp["top"]["style"] != "none" && $widths[0] > 0 ) {
      $method = "_border_" . $bp["top"]["style"];
      $this->$method($x, $y, $w, $bp["top"]["color"], $widths, "top");
    }
    
    if ( $bp["bottom"]["style"] != "none" && $widths[2] > 0 ) {
      $method = "_border_" . $bp["bottom"]["style"];
      $this->$method($x, $y + $h, $w, $bp["bottom"]["color"], $widths, "bottom");
    }
    
    // Draw the right border
    if ( $bp["right"]["style"] != "none" && $widths[1] > 0 ) {

      $method = "_border_" . $bp["right"]["style"];
      $this->$method($x + $w, $y, $h, $bp["right"]["color"], $widths, "right");
    }
  }
}
?>