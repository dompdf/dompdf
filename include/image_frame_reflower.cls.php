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
 * @version 0.3
 */

/* $Id: image_frame_reflower.cls.php,v 1.3 2005-03-02 00:51:24 benjcarson Exp $ */

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
    
//     // Resample images if the sizes were set in px or were auto
//     if ( strpos($style->width, "px") !== false )
//       $width = ((float)rtrim($width, "px")) * 72 / DOMPDF_DPI;
    
//     if ( strpos($style->height, "px") !== false )
//       $height = ((float)rtrim($height, "px")) * 72 / DOMPDF_DPI;
    
    $this->_frame->get_style()->width = $width . "pt";
    $this->_frame->get_style()->height = $height . "pt";
    
  }
}
?>