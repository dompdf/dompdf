<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: abstract_renderer.cls.php,v $
 * Created on: 2004-06-01
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

/* $Id: abstract_renderer.cls.php,v 1.1.1.1 2005-01-25 22:56:00 benjcarson Exp $ */

/**
 * Base renderer class
 *
 * @access private
 * @package dompdf
 */
abstract class Abstract_Renderer {

  // protected properties
  protected $_canvas;  // Rendering target

  function __construct(Canvas $canvas) { $this->_canvas = $canvas; }
  
  //........................................................................

  abstract function render(Frame $frame);

  //........................................................................

  protected function _border_none($x, $y, $length, $color, $widths, $side, $corner_style = "bevel") {
    return;
  }
  
  // Border rendering functions
  protected function _border_dotted($x, $y, $length, $color, $widths, $side, $corner_style = "bevel") {
    list($top, $right, $bottom, $left) = $widths;

    if ( $$side < 2 ) 
      $dash = array($$side, 2);
    else
      $dash = array($$side);
  
    
    switch ($side) {

    case "top":
      $delta = $top / 2;
    case "bottom":
      $delta = isset($delta) ? $delta : -$bottom / 2;
      $this->_canvas->line($x, $y + $delta, $x + $length, $y + $delta, $color, $$side, $dash);
      break;

    case "left":
      $delta = $left / 2;
    case "right":
      $delta = isset($delta) ? $delta : - $right / 2;
      $this->_canvas->line($x + $delta, $y, $x + $delta, $y + $length, $color, $$side, $dash);
      break;

    default:
      return;

    }
  }

  
  protected function _border_dashed($x, $y, $length, $color, $widths, $side, $corner_style = "bevel") {
    list($top, $right, $bottom, $left) = $widths;

    switch ($side) {

    case "top":
      $delta = $top / 2;
    case "bottom":
      $delta = isset($delta) ? $delta : -$bottom / 2;
      $this->_canvas->line($x, $y + $delta, $x + $length, $y + $delta, $color, $$side, array(3 * $$side));
      break;

    case "left":
      $delta = $left / 2;
    case "right":
      $delta = isset($delta) ? $delta : - $right / 2;
      $this->_canvas->line($x + $delta, $y, $x + $delta, $y + $length, $color, $$side, array(3 * $$side));
      break;

    default:
      return;
    }
    
  }

  
  protected function _border_solid($x, $y, $length, $color, $widths, $side, $corner_style = "bevel") {
    list($top, $right, $bottom, $left) = $widths;

    // All this polygon business is for beveled corners...
    switch ($side) {

    case "top":
      if ( $corner_style == "bevel" ) {
        
        $points = array($x, $y,
                        $x + $length, $y,
                        $x + $length - $right, $y + $top,
                        $x + $left, $y + $top);
        $this->_canvas->polygon($points, $color, null, null, true);
      } else 
        $this->_canvas->filled_rectangle($x, $y, $length, $top, $color);
      
      break;
      
    case "bottom":
      if ( $corner_style == "bevel" ) {
        $points = array($x, $y,
                        $x + $length, $y,
                        $x + $length - $right, $y - $bottom,
                        $x + $left, $y - $bottom);
        $this->_canvas->polygon($points, $color, null, null, true);
      } else
        $this->_canvas->filled_rectangle($x, $y - $bottom, $length, $bottom, $color);
      
      break;
      
    case "left":
      if ( $corner_style == "bevel" ) {
        $points = array($x, $y,
                        $x, $y + $length,
                        $x + $left, $y + $length - $bottom,
                        $x + $left, $y + $top);
        $this->_canvas->polygon($points, $color, null, null, true);
      } else 
        $this->_canvas->filled_rectangle($x, $y, $left, $length, $color);
      
      break;
      
    case "right":
      if ( $corner_style == "bevel" ) {
        $points = array($x, $y,
                        $x, $y + $length,
                        $x - $right, $y + $length - $bottom,
                        $x - $right, $y + $top);
        $this->_canvas->polygon($points, $color, null, null, true);
      } else
        $this->_canvas->filled_rectangle($x - $right, $y, $right, $length, $color);

      break;

    default:
      return;

    }
        
  }


  protected function _border_double($x, $y, $length, $color, $widths, $side, $corner_style = "bevel") {
    list($top, $right, $bottom, $left) = $widths;
    
    $line_width = $$side / 4;
    
    // We draw the outermost edge first. Points are ordered: outer left,
    // outer right, inner right, inner left, or outer top, outer bottom,
    // inner bottom, inner top.
    switch ($side) {

    case "top":
      if ( $corner_style == "bevel" ) {
        $left_line_width = $left / 4;
        $right_line_width = $right / 4;
        
        $points = array($x, $y,
                        $x + $length, $y,
                        $x + $length - $right_line_width, $y + $line_width,
                        $x + $left_line_width, $y + $line_width,);
        $this->_canvas->polygon($points, $color, null, null, true);
        
        $points = array($x + $left - $left_line_width, $y + $top - $line_width,
                        $x + $length - $right + $right_line_width, $y + $top - $line_width,
                        $x + $length - $right, $y + $top,
                        $x + $left, $y + $top);
        $this->_canvas->polygon($points, $color, null, null, true);

      } else {
        $this->_canvas->filled_rectangle($x, $y, $length, $line_width, $color);
        $this->_canvas->filled_rectangle($x, $y + $top - $line_width, $length, $line_width, $color);

      }
      break;
      
    case "bottom":
      if ( $corner_style == "bevel" ) {
        $left_line_width = $left / 4;
        $right_line_width = $right / 4;
        
        $points = array($x, $y,
                        $x + $length, $y,
                        $x + $length - $right_line_width, $y - $line_width,
                        $x + $left_line_width, $y - $line_width);
        $this->_canvas->polygon($points, $color, null, null, true);
        
        $points = array($x + $left - $left_line_width, $y - $bottom + $line_width,
                        $x + $length - $right + $right_line_width, $y - $bottom + $line_width,
                        $x + $length - $right, $y - $bottom,
                        $x + $left, $y - $bottom);
        $this->_canvas->polygon($points, $color, null, null, true);

      } else {
        $this->_canvas->filled_rectangle($x, $y - $line_width, $length, $line_width, $color);
        $this->_canvas->filled_rectangle($x, $y - $bottom, $length, $line_width, $color);
      }
          
      break;

    case "left":
      if ( $corner_style == "bevel" ) {
        $top_line_width = $top / 4;
        $bottom_line_width = $bottom / 4;
        
        $points = array($x, $y,
                        $x, $y + $length,
                        $x + $line_width, $y + $length - $bottom_line_width,
                        $x + $line_width, $y + $top_line_width);
        $this->_canvas->polygon($points, $color, null, null, true);

        $points = array($x + $left - $line_width, $y + $top - $top_line_width,
                        $x + $left - $line_width, $y + $length - $bottom + $bottom_line_width,
                        $x + $left, $y + $length - $bottom,
                        $x + $left, $y + $top);
        $this->_canvas->polygon($points, $color, null, null, true);

      } else {
        $this->_canvas->filled_rectangle($x, $y, $line_width, $length, $color);
        $this->_canvas->filled_rectangle($x + $left - $line_width, $y, $line_width, $length, $color);
      }
      
      break;      
                      
    case "right":
      if ( $corner_style == "bevel" ) {
        $top_line_width = $top / 4;
        $bottom_line_width = $bottom / 4;
        
      
        $points = array($x, $y,
                      $x, $y + $length,
                        $x - $line_width, $y + $length - $bottom_line_width,
                        $x - $line_width, $y + $top_line_width);
        $this->_canvas->polygon($points, $color, null, null, true);
        
        $points = array($x - $right + $line_width, $y + $top - $top_line_width,
                        $x - $right + $line_width, $y + $length - $bottom + $bottom_line_width,
                        $x - $right, $y + $length - $bottom,
                        $x - $right, $y + $top);
        $this->_canvas->polygon($points, $color, null, null, true);

      } else {
        $this->_canvas->filled_rectangle($x - $line_width, $y, $line_width, $length, $color);
        $this->_canvas->filled_rectangle($x - $right, $y, $line_width, $length, $color);
      }
      
      break;

    default:
      return;

    }
        
  }

  protected function _border_groove($x, $y, $length, $color, $widths, $side, $corner_style = "bevel") {
    list($top, $right, $bottom, $left) = $widths;
      
    $half_widths = array($top / 2, $right / 2, $bottom / 2, $left / 2);
    
    $this->_border_inset($x, $y, $length, $color, $half_widths, $side);

    switch ($side) {

    case "top":
      $x += $left / 2;
      $y += $top / 2;
      $length -= $left / 2 + $right / 2;
      break;

    case "bottom":
      $x += $left / 2;
      $y -= $bottom / 2;
      $length -= $left / 2 + $right / 2;
      break;

    case "left":
      $x += $left / 2;
      $y += $top / 2;
      $length -= $top / 2 + $bottom / 2;
      break;

    case "right":
      $x -= $right / 2;
      $y += $top / 2;
      $length -= $top / 2 + $bottom / 2;
      break;

    default:
      return;

    }

    $this->_border_outset($x, $y, $length, $color, $half_widths, $side);
    
  }
  
  protected function _border_ridge($x, $y, $length, $color, $widths, $side, $corner_style = "bevel") {
    list($top, $right, $bottom, $left) = $widths;
     
    $half_widths = array($top / 2, $right / 2, $bottom / 2, $left / 2);
    
    $this->_border_outset($x, $y, $length, $color, $half_widths, $side);

    switch ($side) {

    case "top":
      $x += $left / 2;
      $y += $top / 2;
      $length -= $left / 2 + $right / 2;
      break;

    case "bottom":
      $x += $left / 2;
      $y -= $bottom / 2;
      $length -= $left / 2 + $right / 2;
      break;

    case "left":
      $x += $left / 2;
      $y += $top / 2;
      $length -= $top / 2 + $bottom / 2;
      break;

    case "right":
      $x -= $right / 2;
      $y += $top / 2;
      $length -= $top / 2 + $bottom / 2;
      break;

    default:
      return;

    }

    $this->_border_inset($x, $y, $length, $color, $half_widths, $side);

  }

  protected function _tint($c) {
    if ( !is_numeric($c) )
      return $c;
    
    return min(1, $c + 0.66);
  }

  protected function _shade($c) {
    if ( !is_numeric($c) )
      return $c;
    
    return max(0, $c - 0.66);
  }

  protected function _border_inset($x, $y, $length, $color, $widths, $side, $corner_style = "bevel") {
    list($top, $right, $bottom, $left) = $widths;
    
    switch ($side) {

    case "top":
    case "left":
      $shade = array_map(array($this, "_shade"), $color);
      $this->_border_solid($x, $y, $length, $shade, $widths, $side);
      break;

    case "bottom":
    case "right":
      $tint = array_map(array($this, "_tint"), $color);      
      $this->_border_solid($x, $y, $length, $tint, $widths, $side);
      break;

    default:
      return;
    }    
  }
  
  protected function _border_outset($x, $y, $length, $color, $widths, $side, $corner_style = "bevel") {
    list($top, $right, $bottom, $left) = $widths;
    
    switch ($side) {
    case "top":
    case "left":
      $tint = array_map(array($this, "_tint"), $color);
      $this->_border_solid($x, $y, $length, $tint, $widths, $side);
      break;

    case "bottom":
    case "right":
      $shade = array_map(array($this, "_shade"), $color);
      $this->_border_solid($x, $y, $length, $shade, $widths, $side);
      break;

    default:
      return;

    }    
  }

  //........................................................................
  

}

?>