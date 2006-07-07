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
 * @version 0.5.1
 */

/* $Id: abstract_renderer.cls.php,v 1.6 2006-07-07 21:31:02 benjcarson Exp $ */

/**
 * Base renderer class
 *
 * @access private
 * @package dompdf
 */
abstract class Abstract_Renderer {

  /**
   * Rendering backend
   *
   * @var Canvas
   */
  protected $_canvas;

  /**
   * Current dompdf instance
   *
   * @var DOMPDF
   */
  protected $_dompdf;
  
  /**
   * Class constructor
   *
   * @param DOMPDF $dompdf The current dompdf instance
   */
  function __construct(DOMPDF $dompdf) {
    $this->_dompdf = $dompdf;
    $this->_canvas = $dompdf->get_canvas();
  }
  
  /**
   * Render a frame.
   *
   * Specialized in child classes
   *
   * @param Frame $frame The frame to render
   */
  abstract function render(Frame $frame);

  //........................................................................

  /**
   * Render a background image over a rectangular area
   *
   * @param string $img      The background image to load
   * @param float  $x        The left edge of the rectangular area
   * @param float  $y        The top edge of the rectangular area
   * @param float  $width    The width of the rectangular area
   * @param float  $height   The height of the rectangular area
   * @param Style  $style    The associated Style object
   */
  protected function _background_image($url, $x, $y, $width, $height, $style) {
    $sheet = $style->get_stylesheet();

    // Skip degenerate cases
    if ( $width == 0 || $height == 0 )
      return;
    
    list($img, $ext) = Image_Cache::resolve_url($url,
                                                $sheet->get_protocol(),
                                                $sheet->get_host(),
                                                $sheet->get_base_path());

    
    list($bg_x, $bg_y) = $style->background_position;
    $repeat = $style->background_repeat;

    if ( !is_percent($bg_x) )
      $bg_x = $style->length_in_pt($bg_x);
    if ( !is_percent($bg_y) )
      $bg_y = $style->length_in_pt($bg_y);

    $repeat = $style->background_repeat;
    $position = $style->background_position;
    $bg_color = $style->background_color;
    
    // Bail if the image is no good
    if ( $img == DOMPDF_LIB_DIR . "/res/broken_image.png" )
      return;
    
    $ext = strtolower($ext);
    
    list($img_w, $img_h) = getimagesize($img);

    $bg_width = round($width * DOMPDF_DPI / 72);
    $bg_height = round($height * DOMPDF_DPI / 72);

    // Create a new image to fit over the background rectangle
    $bg = imagecreatetruecolor($bg_width, $bg_height);
    if ( $bg_color == "transparent" )
      $bg_color = array(1,1,1);
    
    list($r,$g,$b) = $bg_color;
    $r *= 255; $g *= 255; $b *= 255;

    // Clip values
    $r = $r > 255 ? 255 : $r;
    $g = $g > 255 ? 255 : $g;
    $b = $b > 255 ? 255 : $b;
      
    $r = $r < 0 ? 0 : $r;
    $g = $g < 0 ? 0 : $g;
    $b = $b < 0 ? 0 : $b;

    $clear = imagecolorallocate($bg,round($r),round($g),round($b));
    imagecolortransparent($bg, $clear);
    imagefill($bg,1,1,$clear);
    
    switch ($ext) {

    case "png":
      $src = imagecreatefrompng($img);
      break;
      
    case "jpg":
    case "jpeg":
      $src = imagecreatefromjpeg($img);
      break;
      
    case "gif":
      $src = imagecreatefromgif($img);
      break;

    default:
      return; // Unsupported image type
    }

    if ( is_percent($bg_x) ) {
      // The point $bg_x % from the left edge of the image is placed
      // $bg_x % from the left edge of the background rectangle
      $p = ((float)$bg_x)/100.0;
      $x1 = $p * $img_w;
      $x2 = $p * $bg_width;

      $bg_x = $x2 - $x1;
    }
      
    if ( is_percent($bg_y) ) {
      // The point $bg_y % from the left edge of the image is placed
      // $bg_y % from the left edge of the background rectangle
      $p = ((float)$bg_y)/100.0;
      $y1 = $p * $img_h;
      $y2 = $p * $bg_height;

      $bg_y = $y2 - $y1;
    }

    // Copy regions from the source image to the background
    if ( $repeat == "no-repeat" ||
         ($repeat == "repeat-x" && $img_w >= $bg_width) ||
         ($repeat == "repeat-y" && $img_h >= $bg_height) ||
         ($repeat == "repeat" && $img_w >= $bg_width && $img_h >= $bg_height) ) {
      
      // Simply place the image on the background
      $src_x = 0;
      $src_y = 0;
      $dst_x = $bg_x;
      $dst_y = $bg_y;
      
      if ( $bg_x < 0 ) {
        $dst_x = 0;
        $src_x = -$bg_x;
      }

      if ( $bg_y < 0 ) {
        $dst_y = 0;
        $src_y = -$bg_y;
      }

      $bg_x = round($bg_x * DOMPDF_DPI / 72);
      $bg_y = round($bg_y * DOMPDF_DPI / 72);

      imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $img_w, $img_h);      

    } else if ( $repeat == "repeat-x" ) {
      $src_x = 0;
      $src_y = 0;

      $dst_y = $bg_y;

      if ( $bg_y < 0 ) {
        $dst_y = 0;
        $src_y = -$bg_y;
      }

      if ( $bg_x < 0 ) 
        $start_x = $bg_x;
      else
        $start_x = $bg_x % $img_w - $img_w;

      for ( $bg_x = $start_x; $bg_x < $bg_width; $bg_x += $img_w ) {
        if ( $bg_x < 0 ) {
          $dst_x = 0;
          $src_x = -$bg_x;
          $w = $img_w + $bg_x;
        } else {          
          $dst_x = $bg_x;
          $src_x = 0;
          $w = $img_w;
        }
        imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $w, $img_h);      
      }
      
    } else if ( $repeat == "repeat-y" ) {
      $src_x = 0;
      $src_y = 0;

      $dst_x = $bg_x;

      if ( $bg_x < 0 ) {
        $dst_x = 0;
        $src_x = -$bg_x;
      }

      if ( $bg_y < 0 ) 
        $start_y = $bg_y;
      else
        $start_y = $bg_y % $img_h - $img_h;

      for ( $bg_y = $start_y; $bg_y < $bg_height; $bg_y += $img_h ) {
        if ( $bg_y < 0 ) {
          $dst_y = 0;
          $src_y = -$bg_y;
          $h = $img_h + $bg_y;
        } else {          
          $dst_y = $bg_y;
          $src_y = 0;
          $h = $img_h;
        }
        imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $img_w, $h);
      }
      
    } else {

      if ( $bg_x < 0 ) 
        $start_x = $bg_x;
      else
        $start_x = $bg_x % $img_w - $img_w;

      if ( $bg_y < 0 ) 
        $start_y = $bg_y;
      else
        $start_y = $bg_y % $img_h - $img_h;
      
      for ( $bg_y = $start_y; $bg_y < $bg_height; $bg_y += $img_h ) {
        for ( $bg_x = $start_x; $bg_x < $bg_width; $bg_x += $img_w ) {

          if ( $bg_x < 0 ) {
            $dst_x = 0;
            $src_x = -$bg_x;
            $w = $img_w + $bg_x;
          } else {          
            $dst_x = $bg_x;
            $src_x = 0;
            $w = $img_w;
          }
          
          if ( $bg_y < 0 ) {
            $dst_y = 0;
            $src_y = -$bg_y;
            $h = $img_h + $bg_y;
          } else {          
            $dst_y = $bg_y;
            $src_y = 0;
            $h = $img_h;
          }
          imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $w, $h);
        }
      }
    }


    
    $tmp_file = tempnam(DOMPDF_TEMP_DIR, "dompdf_img_");
    imagepng($bg, $tmp_file);
    $this->_canvas->image($tmp_file, "png", $x, $y, $width, $height);
    unlink($tmp_file);
  }
  
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