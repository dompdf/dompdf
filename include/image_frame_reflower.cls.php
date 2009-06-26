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
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf
 * @version 0.5.1
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 0.5.1.htischer.20090507
 * - Fix image size as percent of wrapping box
 * - Fix arithmetic rounding of image size
 * - Time consuming additional image file scan only when really needed
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

    if (DEBUGPNG) {
      // Determine the image's size. Time consuming. Only when really needed?
      list($img_width, $img_height) = getimagesize($this->_frame->get_image_url());
      print "get_min_max_width() ".
        $this->_frame->get_style()->width.' '.
        $this->_frame->get_style()->height.';'.
        $this->_frame->get_parent()->get_style()->width." ".
        $this->_frame->get_parent()->get_style()->height.";".
        $this->_frame->get_parent()->get_parent()->get_style()->width.' '.
        $this->_frame->get_parent()->get_parent()->get_style()->height.';'.
        $img_width. ' '.
        $img_height.'|' ;
    }

    // We need to grab our *parent's* style because images are wrapped...
    $style = $this->_frame->get_parent()->get_style();

    //own style auto or invalid value: use natural size in px
    //own style value: ignore suffix text including unit, use given number as px
    //own style %: walk up parent chain until found available space in pt; fill available space
    //
    //special ignored unit: e.g. 10ex: e treated as exponent; x ignored; 10e completely invalid ->like auto

    $width = $this->_frame->get_style()->width;
    if ( is_percent($width) ) {
      $t = 0.0;
      for ($f = $this->_frame->get_parent(); $f; $f = $f->get_parent()) {
        $t = (float)($f->get_style()->width); //always in pt
        if ((float)$t != 0) {
        	break;
        }
      }
      $width = ((float)rtrim($width,"%") * $t)/100; //maybe 0
    } else {
      // Don't set image original size if "%" branch was 0 or size not given.
      // Otherwise aspect changed on %/auto combination for width/height
      // Resample according to px per inch
      // See also List_Bullet_Image_Frame_Decorator::__construct
      $width = (float)($width * 72) / DOMPDF_DPI;
    }

    $height = $this->_frame->get_style()->height;
    if ( is_percent($height) ) {
      $t = 0.0;
      for ($f = $this->_frame->get_parent(); $f; $f = $f->get_parent()) {
        $t = (float)($f->get_style()->height); //always in pt
        if ((float)$t != 0) {
        	break;
        }
      }
      $height = ((float)rtrim($height,"%") * $t)/100; //maybe 0
    } else {
      // Don't set image original size if "%" branch was 0 or size not given.
      // Otherwise aspect changed on %/auto combination for width/height
      // Resample according to px per inch
      // See also List_Bullet_Image_Frame_Decorator::__construct
      $height = (float)($height * 72) / DOMPDF_DPI;
    }

    if ($width == 0 && $height == 0) {
      // Determine the image's size. Time consuming. Only when really needed!
      list($img_width, $img_height) = getimagesize($this->_frame->get_image_url());
      // don't treat 0 as error. Can be downscaled or can be catched elsewhere if image not readable.
      // Resample according to px per inch
      // See also List_Bullet_Image_Frame_Decorator::__construct
      $width = (float)($img_width * 72) / DOMPDF_DPI;
      $height = (float)($img_height * 72) / DOMPDF_DPI;
    } else if ($height == 0) {
      list($img_width, $img_height) = getimagesize($this->_frame->get_image_url());
      //On error don't divide by 0
      if ($img_width == 0) {
        throw new DOMPDF_Exception('Image width not detected: '.$this->_frame->get_image_url());
      }
      $height = ($width / $img_width) * $img_height; //keep aspect ratio
    } else if ($width == 0) {
      list($img_width, $img_height) = getimagesize($this->_frame->get_image_url());
      if ($img_height == 0) {
        throw new DOMPDF_Exception('Image height not detected: '.$this->_frame->get_image_url());
      }
      $width = ($height / $img_height) * $img_width; //keep aspect ratio
    }

    if (DEBUGPNG) print $width.' '.$height.';';

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