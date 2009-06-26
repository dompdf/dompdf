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
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf
 * @version 0.5.1
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 0.5.1.htischer.20090507
 * - add optional debug output
 */

/* $Id: image_frame_decorator.cls.php,v 1.12 2006-08-02 18:44:25 benjcarson Exp $ */

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
    
    parent::__construct($frame, $dompdf);
    $url = $frame->get_node()->getAttribute("src");
      
    //debugpng
    if (DEBUGPNG) print '[__construct '.$url.']';

    list($this->_image_url, $this->_image_ext) = Image_Cache::resolve_url($url,
                                                                          $dompdf->get_protocol(),
                                                                          $dompdf->get_host(),
                                                                          $dompdf->get_base_path());
    
  }

  /**
   * Return the image's url
   *
   * @return string The url of this image
   */
  function get_image_url() {
    return $this->_image_url;
  }

  /**
   * Return the image's file extension
   *
   * @return string The image's file extension
   */
  function get_image_ext() {
    return $this->_image_ext;
  }
  
  /**
   * Unlink all cached images (i.e. temporary images either downloaded
   * or converted)
   */
  static function clear_image_cache() {
    if ( count(self::$_cache) ) {
      foreach (self::$_cache as $file)
        //debugpng
        if (DEBUGPNG) print '[clear_image_cache unlink '.$file.']';
        if (!DEBUGKEEPTEMP)
          unlink($file);
    }
  }
}
?>