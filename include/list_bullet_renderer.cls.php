<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: list_bullet_renderer.cls.php,v $
 * Created on: 2004-06-23
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
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf

 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 20090622
 * - bullet size proportional to font size, center position
 */

/* $Id$ */

/**
 * Renders list bullets
 *
 * @access private
 * @package dompdf
 */
class List_Bullet_Renderer extends Abstract_Renderer {

  //........................................................................
  private function make_counter($n, $type, $pad = null){
    $n = intval($n);
    $text = "";
    $uppercase = false;
    
    switch ($type) {
      case "decimal-leading-zero":
      case "decimal":
      case "1":
        if ($pad) 
          $text = str_pad($n, $pad, "0", STR_PAD_LEFT);
        else 
          $text = $n;
        break;
      
      case "upper-alpha":
      case "upper-latin":
      case "A":
        $uppercase = true;
      case "lower-alpha":
      case "lower-latin":
      case "a":
        $text = chr( ($n % 26) + ord('a') - 1);
        break;
        
      case "upper-roman":
      case "I":
        $uppercase = true;
      case "lower-roman":
      case "i":
        $text = dec2roman($n);
        break;
      
      case "lower-greek":
        $text = unichr($n + 944);
        break;
    }
    
    if ($uppercase) 
      $text = strtoupper($text);
      
    return $text.".";
  }
  
  function render(Frame $frame) {

    $style = $frame->get_style();
    $font_size = $style->get_font_size();
    $line_height = $style->length_in_pt($style->line_height, $frame->get_containing_block("w"));

    $this->_set_opacity( $frame->get_opacity( $style->opacity ) );
    
    // Handle list-style-image
    // If list style image is requested but missing, fall back to predefined types
    if ( $style->list_style_image !== "none" &&
         strcmp($img = $frame->get_image_url(), DOMPDF_LIB_DIR . "/res/broken_image.png") != 0) {

      list($x,$y) = $frame->get_position();
      
      //For expected size and aspect, instead of box size, use image natural size scaled to DPI.
      // Resample the bullet image to be consistent with 'auto' sized images
      // See also Image_Frame_Reflower::get_min_max_width
      // Tested php ver: value measured in px, suffix "px" not in value: rtrim unnecessary.
      //$w = $frame->get_width();
      //$h = $frame->get_height();
      list($width, $height) = dompdf_getimagesize($img);
      $w = (((float)rtrim($width, "px")) * 72) / DOMPDF_DPI;
      $h = (((float)rtrim($height, "px")) * 72) / DOMPDF_DPI;
      
      $x -= $w;
      $y -= ($line_height - $font_size)/2; //Reverse hinting of list_bullet_positioner

      $this->_canvas->image( $img, $frame->get_image_ext(), $x, $y, $w, $h);

    } else {

      $bullet_style = $style->list_style_type;

      $fill = false;

      switch ($bullet_style) {

      default:
      case "disc":
        $fill = true;

      case "circle":
        list($x,$y) = $frame->get_position();
        $r = ($font_size*(List_Bullet_Frame_Decorator::BULLET_SIZE /*-List_Bullet_Frame_Decorator::BULLET_THICKNESS*/ ))/2;
        $x -= $font_size*(List_Bullet_Frame_Decorator::BULLET_SIZE/2);
        $y += ($font_size*(1-List_Bullet_Frame_Decorator::BULLET_DESCENT))/2;
        $o = $font_size*List_Bullet_Frame_Decorator::BULLET_THICKNESS;
        $this->_canvas->circle($x, $y, $r, $style->color, $o, null, $fill);
        break;

      case "square":
        list($x, $y) = $frame->get_position();
        $w = $font_size*List_Bullet_Frame_Decorator::BULLET_SIZE;
        $x -= $w;
        $y += ($font_size*(1-List_Bullet_Frame_Decorator::BULLET_DESCENT-List_Bullet_Frame_Decorator::BULLET_SIZE))/2;
        $this->_canvas->filled_rectangle($x, $y, $w, $w, $style->color);
        break;
		
      case "decimal-leading-zero":
      case "decimal":
      case "lower-alpha":
      case "lower-latin":
      case "lower-roman":
      case "lower-greek":
      case "upper-alpha":
      case "upper-latin":
      case "upper-roman":
      case "1": // HTML 4.0 compatibility
      case "a":
      case "i":
      case "A":
      case "I":
        list($x,$y) = $frame->get_position();
        
        $pad = null;
        if ( $bullet_style === "decimal-leading-zero" ) {
          $pad = strlen($frame->get_parent()->get_parent()->get_node()->getAttribute("dompdf-children-count"));
        }
        
        $index = $frame->get_node()->getAttribute("dompdf-counter");
        $text = $this->make_counter($index, $bullet_style, $pad);
        $font_family = $style->font_family;
        $spacing = 0; //$frame->get_text_spacing() + $style->word_spacing;
        
        if ( trim($text) == "" )
          return;

        $x -= Font_Metrics::get_text_width($text, $font_family, $font_size, $spacing);
        
        $this->_canvas->text($x, $y, $text,
                             $font_family, $font_size,
                             $style->color, $spacing);
      
      case "none":
        break;
      }
    }
  }
}
