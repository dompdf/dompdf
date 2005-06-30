<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: image_frame_decorator.cls.php,v $
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

/* $Id: image_frame_decorator.cls.php,v 1.4 2005-06-30 03:02:12 benjcarson Exp $ */

/**
 * Decorates frames for image layout and rendering
 *
 * @access private
 * @package dompdf
 */
class Image_Frame_Decorator extends Frame_Decorator {

  /**
   * Array of downloaded images.  Cached so that identical images are
   * not needlessly downloaded.
   *
   * @var array
   */
  static protected $_cache = array();
 
  /**
   * The path to the image file (note that remote images are
   * downloaded locally to DOMPDF_TEMP_DIR).
   *
   * @var string
   */
  protected $_image_url;

  /**
   * The image's file extension (i.e. png, jpeg, gif)
   *
   * @var string
   */
  protected $_image_ext;

  /**
   * Class constructor
   *
   * @param Frame $frame the frame to decorate
   * @param DOMPDF $dompdf the document's dompdf object (required to resolve relative & remote urls)
   */
  function __construct(Frame $frame, DOMPDF $dompdf) {
    global $_dompdf_warnings;
    
    parent::__construct($frame);
    $url = $frame->get_node()->getAttribute("src");

    $proto = $dompdf->get_protocol();
    $remote = ($proto != "" && $proto != "file://");
    
    if ( !DOMPDF_ENABLE_REMOTE && $remote ) {
      $this->_image_url = DOMPDF_LIB_DIR . "/res/broken_image.png";


    } else if ( DOMPDF_ENABLE_REMOTE && $remote ) {
      // Download remote files to a temporary directory
      $url = build_url($dompdf->get_protocol(),
                       $dompdf->get_host(),
                       $dompdf->get_base_path(),
                       $url);

      if ( isset(self::$_cache[$url]) ) {
        $this->_image_url = self::$_cache[$url];
        //echo "Using cached image $url (" . $this->_image_url . ")\n";
        
      } else {
           
        //echo "Downloading file $url to temporary location: ";
        $this->_image_url = tempnam(DOMPDF_TEMP_DIR, "dompdf_img_");
        //echo $this->_image_url . "\n";

        file_put_contents($this->_image_url, file_get_contents($url));

        self::$_cache[$url] = $this->_image_url;
      }
      
    } else {

      $this->_image_url = build_url($dompdf->get_protocol(),
                                    $dompdf->get_host(),
                                    $dompdf->get_base_path(),
                                    $url);
      
    }

    if ( !is_readable($this->_image_url) || !filesize($this->_image_url) ) {
      $_dompdf_warnings[] = "File " .$this->_image_url . " is not readable.\n";
      $this->_image_url = DOMPDF_LIB_DIR . "/res/broken_image.png";
    }

    // We need to preserve the file extenstion
    $i = strrpos($url, ".");
    if ( $i === false )
      throw new DOMPDF_Exception("Unknown image type: $url.");
    
    $this->_image_ext = strtolower(substr( $url, $i+1 ));
    
  }

  function get_image_url() {
    return $this->_image_url;
  }

  function get_image_ext() {
    return $this->_image_ext;
  }

  static function clear_image_cache() {
    if ( count(self::$_cache) ) {
      foreach (self::$_cache as $file)
        unlink($file);
    }
  }
}
?>