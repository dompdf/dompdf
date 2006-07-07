<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: image_cache.cls.php,v $
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

/* $Id */

/**
 * Static class that resolves image urls and downloads and caches
 * remote images if required.
 *
 * @access private
 * @package dompdf
 */
class Image_Cache {

  /**
   * Array of downloaded images.  Cached so that identical images are
   * not needlessly downloaded.
   *
   * @var array
   */
  static protected $_cache = array();


  /**
   * Resolve and fetch an image for use.
   *
   * @param string $url        The url of the image
   * @param string $proto      Default protocol if none specified in $url
   * @param string $host       Default host if none specified in $url
   * @param string $base_path  Default path if none specified in $url
   * @return array             An array with two elements: The local path to the image and the image extension
   */
  static function resolve_url($url, $proto, $host, $base_path) {
    global $_dompdf_warnings;

    
    $resolved_url = null;

    // Remove dynamic part of url to determine the file extension
    $tmp = preg_replace('/\?.*/','',$url);

    // We need to preserve the file extenstion
    $i = mb_strrpos($tmp, ".");
    if ( $i === false )
      throw new DOMPDF_Exception("Unknown image type: $url.");

    $ext = mb_strtolower(mb_substr($tmp, $i+1));

    $parsed_url = explode_url($url);

    $remote = ($proto != "" && $proto != "file://");
    $remote = $remote || ($parsed_url['protocol'] != "");

    if ( !DOMPDF_ENABLE_REMOTE && $remote ) {
      $resolved_url = DOMPDF_LIB_DIR . "/res/broken_image.png";
      $ext = "png";

    } else if ( DOMPDF_ENABLE_REMOTE && $remote ) {
      // Download remote files to a temporary directory
      $url = build_url($proto, $host, $base_path, $url);

      if ( isset(self::$_cache[$url]) ) {
        list($resolved_url,$ext) = self::$_cache[$url];
        //echo "Using cached image $url (" . $resolved_url . ")\n";

      } else {

        //echo "Downloading file $url to temporary location: ";
        $resolved_url = tempnam(DOMPDF_TEMP_DIR, "dompdf_img_");
        //echo $resolved_url . "\n";

        $old_err = set_error_handler("record_warnings");
        $image = file_get_contents($url);
        restore_error_handler();

        if ( strlen($image) == 0 ) {
          $image = file_get_contents(DOMPDF_LIB_DIR . "/res/broken_image.png");
          $ext = "png";
        }

        file_put_contents($resolved_url, $image);

        self::$_cache[$url] = array($resolved_url,$ext);

      }

    } else {

      $resolved_url = build_url($proto, $host, $base_path, $url);

      //echo $resolved_url . "\n";

    }

    if ( !is_readable($resolved_url) || !filesize($resolved_url) ) {
      $_dompdf_warnings[] = "File " .$resolved_url . " is not readable or is an empty file.\n";
      $resolved_url = DOMPDF_LIB_DIR . "/res/broken_image.png";
      $ext = "png";
    }

    // Assume for now that all dynamic images are pngs
    if ( $ext == "php" )
      $ext = "png";

    return array($resolved_url, $ext);

  }

  /**
   * Unlink all cached images (i.e. temporary images either downloaded
   * or converted)
   */
  static function clear() {
    if ( count(self::$_cache) ) {
      foreach (self::$_cache as $entry) {
        list($file, $ext) = $entry;
        unlink($file);
      }
    }
  }

}
?>