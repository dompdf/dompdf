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

/* $Id: image_frame_reflower.cls.php,v 1.1.1.1 2005-01-25 22:56:03 benjcarson Exp $ */

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
    $this->_frame->position();
    
    $style = $this->_frame->get_style();
    $width = $style->width;
    $height = $style->height;
    
    if ( $width === "auto" || is_percent($width) ) {

      // Determine the image's size
      list($img_width, $img_height, $type) = getimagesize($this->_frame->get_image_url());

      if ( $width === "auto" )
        $style->width = $img_width * 72 / DOMPDF_DPI;
      else {
        // Percentage
        $width = (float)rtrim($width, "%");
        $style->width = $width * $img_width / 100;
      }
    } else 
      // Resolve the length into points (no need for 2nd param as % vals are
      // handled above)
      $style->width = $style->length_in_pt($width);
      

    if ( $height === "auto" || is_percent($height) ) {
      if ( !isset($img_height) )
        list($img_width, $img_height, $type) = getimagesize($this->_frame->get_image_url());

      if ( $height === "auto" )
        $style->height = $img_height * 72 / DOMPDF_DPI;
      else {
        // Percentage
        $height = (float)rtrim($height, "%");
        $style->height = $height * $img_height / 100;
      }

    } else
      $style->height = $style->length_in_pt($height);
  }
}
?>