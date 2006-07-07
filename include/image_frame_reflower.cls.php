<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: image_frame_reflower.cls.php,v $
 * Created on: 2004-08-08
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

/* $Id: image_frame_reflower.cls.php,v 1.9 2006-07-07 21:31:03 benjcarson Exp $ */

/**
 * Image reflower class
 *
 * @access private
 * @package dompdf
 */
class Image_Frame_Reflower extends Frame_Reflower {

  function __construct(Image_Frame_Decorator $frame) {
    parent::__construct($frame);
  }

  function reflow() {
    
    // Set the frame's width
    $this->get_min_max_width();
    
  }

  function get_min_max_width() {

    // We need to grab our *parent's* style because images are wrapped...
    $style = $this->_frame->get_parent()->get_style();
    
    $width = $style->width;
    $height = $style->height;
    
    // Determine the image's size
    list($img_width, $img_height, $type) = getimagesize($this->_frame->get_image_url());

    if ( is_percent($width) )
      $width = ((float)rtrim($width,"%")) * $img_width / 100;

    if ( is_percent($height) )
      $height = ((float)rtrim($height,"%")) * $img_height / 100;
                 
    $width = $style->length_in_pt($width);
    $height = $style->length_in_pt($height);

    if ( $width === "auto" && $height === "auto" ) {
      $width = $img_width;
      $height = $img_height;
      
    } else if ( $width === "auto" && $height !== "auto" ) {
      $width = (float)$height / $img_height * $img_width;
      
    } else if ( $width !== "auto" && $height === "auto" ) {
      $height = (float)$width / $img_width * $img_height;
      
    } 
    
    // Resample images if the sizes were auto
    if ( $style->width === "auto" && $style->height === "auto" ) {
      $width = ((float)rtrim($width, "px")) * 72 / DOMPDF_DPI;
      $height = ((float)rtrim($height, "px")) * 72 / DOMPDF_DPI;
    }

    // Synchronize the styles
    $inner_style = $this->_frame->get_style();
    $inner_style->width = $style->width = $width . "pt";
    $inner_style->height = $style->height = $height . "pt";

    $inner_style->padding_top = $style->padding_top;
    $inner_style->padding_right = $style->padding_right;
    $inner_style->padding_bottom = $style->padding_bottom;
    $inner_style->padding_left = $style->padding_left;

    $inner_style->border_top_width = $style->border_top_width;
    $inner_style->border_right_width = $style->border_right_width;
    $inner_style->border_bottom_width = $style->border_bottom_width;
    $inner_style->border_left_width = $style->border_left_width;

    $inner_style->border_top_style = $style->border_top_style;
    $inner_style->border_right_style = $style->border_right_style;
    $inner_style->border_bottom_style = $style->border_bottom_style;
    $inner_style->border_left_style = $style->border_left_style;

    $inner_style->margin_top = $style->margin_top;
    $inner_style->margin_right = $style->margin_right;
    $inner_style->margin_bottom = $style->margin_bottom;
    $inner_style->margin_left = $style->margin_left;

    return array( $width, $width, "min" => $width, "max" => $width);
    
  }
}
?>