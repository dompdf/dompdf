<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: image_renderer.cls.php,v $
 * Created on: 2004-08-04
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
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf

 */

/* $Id$ */

/**
 * Image renderer
 *
 * @access private
 * @package dompdf
 */
class Image_Renderer extends Block_Renderer {

  function render(Frame $frame) {
    // Render background & borders
    $style = $frame->get_style();
    $cb = $frame->get_containing_block();
    list($x, $y, $w, $h) = $frame->get_border_box();
  
    $this->_set_opacity( $frame->get_opacity( $style->opacity ) );  

    // Handle the last child
    if ( ($bg = $style->background_color) !== "transparent" ) 
      $this->_canvas->filled_rectangle( $x + $widths[3], $y + $widths[0], $w, $h, $bg);

    if ( ($url = $style->background_image) && $url !== "none" )           
      $this->_background_image($url, $x + $widths[3], $y + $widths[0], $w, $h, $style);
    
    $this->_render_border($frame);
    $this->_render_outline($frame);
    
    list($x, $y) = $frame->get_padding_box();
    $x += $style->length_in_pt($style->padding_left, $cb["w"]);
    $y += $style->length_in_pt($style->padding_top, $cb["h"]);
    
    $w = $style->length_in_pt($style->width, $cb["w"]);
    $h = $style->length_in_pt($style->height, $cb["h"]);

    if ( strrpos( $frame->get_image_url(), DOMPDF_LIB_DIR . "/res/broken_image.png", 0) !== false &&
      $alt = $frame->get_node()->getAttribute("alt") ) {
      $font = $style->font_family;
      $size = $style->font_size;
      $spacing = $style->word_spacing;
      $this->_canvas->text($x, $y, $alt,
                           $font, $size,
                           $style->color, $spacing);
    }
    else {
      $this->_canvas->image( $frame->get_image_url(), $frame->get_image_ext(), $x, $y, $w, $h);
    }
    
    if ( $msg = $frame->get_image_msg() ) {
      $parts = preg_split("/\s*\n\s*/", $msg);
      $height = 10;
      $_y = $alt ? $y+$h-count($parts)*$height : $y;
      
      foreach($parts as $i => $_part) {
        $this->_canvas->text($x, $_y + $i*$height, $_part, "times", $height*0.8, array(0.5, 0.5, 0.5));
      }
    }
    
    if (DEBUG_LAYOUT && DEBUG_LAYOUT_BLOCKS) {
      $this->_debug_layout($frame->get_border_box(), "blue");
      if (DEBUG_LAYOUT_PADDINGBOX) {
        $this->_debug_layout($frame->get_padding_box(), "blue", array(0.5, 0.5));
      }
    }
  }
}
