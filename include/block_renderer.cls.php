<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: block_renderer.cls.php,v $
 * Created on: 2004-06-03
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

/* $Id: block_renderer.cls.php,v 1.1.1.1 2005-01-25 22:56:01 benjcarson Exp $ */

/**
 * Renders block frames
 *
 * @access private
 * @package dompdf
 */
class Block_Renderer extends Abstract_Renderer {

  function __construct(Canvas $canvas) {
    parent::__construct($canvas);
  }
  
  //........................................................................

  function render(Frame $frame) {
    $style = $frame->get_style();
    
    // Draw our background, border and content
    if ( ($bg = $style->background_color) !== "transparent" ) {      
      list($x, $y, $w, $h) = $frame->get_padding_box();
      $this->_canvas->filled_rectangle( $x, $y, $w, $h, $style->background_color );
    }

// FIXME: need to enable & test this eventually
//     if ( ($img = $style->background_image) !== "none" ) {
//       list($x, $y, $w, $h) = $frame->get_padding_box();
//       list($bx, $by) = $style->background_position;
//       $bx = $style->length_in_pt($bx, $w);
//       $by = $style->length_in_pt($by, $h);      
//       $this->_canvas->image($img, $x + $bx, $y + $by);

//     }

    $this->_render_border($frame);
    
  }

  protected function _render_border(Frame_Decorator $frame, $corner_style = "bevel") {
    $cb = $frame->get_containing_block();
    $style = $frame->get_style();

    $bbox = $frame->get_border_box();
    $bp = $frame->get_style()->get_border_properties();

    $widths = array($style->length_in_pt($bp["top"]["width"]),
                    $style->length_in_pt($bp["right"]["width"]),
                    $style->length_in_pt($bp["bottom"]["width"]),
                    $style->length_in_pt($bp["left"]["width"]));

    foreach ($bp as $side => $props) {
      list($x, $y, $w, $h) = $bbox;

      if ( !$props["style"] || $props["style"] == "none" || $props["width"] <= 0 )
        continue;


      switch($side) {
      case "top":
        $length = $w;
        break;

      case "bottom":
        $length = $w;
        $y += $h;
        break;

      case "left":
        $length = $h;
        break;

      case "right":
        $length = $h;
        $x += $w;
        break;
      default:
        break;        
      }
      $method = "_border_" . $props["style"];      

      $this->$method($x, $y, $length, $props["color"], $widths, $side, $corner_style);
    }        
  }  
}

?>