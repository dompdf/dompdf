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
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf
 * @version 0.5.1
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 0.5.1.htischer.20090507
 * - On getting type of images don't require any file endings
 *   and don't strip off url parameters,
 *   to allowing dynamically generated sites with image id
 *   in url parameters and not at end of url or missing file extension
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version dompdf_trunk_with_helmut_mods.20090524
 * - Made debug messages more individually configurable
 * @version 20090622
 * - don't cache broken image, but refer to original broken image replacement
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

    $parsed_url = explode_url($url);

    $DEBUGPNG=DEBUGPNG; //=DEBUGPNG; Allow override of global setting for ad hoc debug
    
    //debugpng
    if ($DEBUGPNG) print 'resolve_url('.$url.','.$proto.','.$host.','.$base_path.')('.$parsed_url['protocol'].')';

    $remote = ($proto != "" && $proto != "file://");
    $remote = $remote || ($parsed_url['protocol'] != "");

    if ( !DOMPDF_ENABLE_REMOTE && $remote ) {
      $resolved_url = DOMPDF_LIB_DIR . "/res/broken_image.png";
      $ext = "png";

      //debugpng
      if ($DEBUGPNG) $full_url_dbg = '(blockedremote)';

    } else if ( DOMPDF_ENABLE_REMOTE && $remote ) {
      // Download remote files to a temporary directory
      $full_url = build_url($proto, $host, $base_path, $url);

      if ( isset(self::$_cache[$full_url]) ) {
        list($resolved_url,$ext) = self::$_cache[$full_url];

        //debugpng
        if ($DEBUGPNG) $full_url_dbg = $full_url.'(cache)';

      } else {

        $resolved_url = tempnam(DOMPDF_TEMP_DIR, "ca_dompdf_img_");
        //debugpng
        if ($DEBUGPNG) echo $resolved_url . "\n";

        $old_err = set_error_handler("record_warnings");
        $image = file_get_contents($full_url);
        restore_error_handler();

        if ( strlen($image) == 0 ) {
          //target image not found
          $resolved_url = DOMPDF_LIB_DIR . "/res/broken_image.png";
          $ext = "png";

          //debugpng
          if ($DEBUGPNG) $full_url_dbg = $full_url.'(missing)';

        } else {

        file_put_contents($resolved_url, $image);

		//e.g. fetch.php?media=url.jpg&cache=1
		//- Image file name might be one of the dynamic parts of the url, don't strip off!
		//  if ( preg_match("/.*\.(\w+)/",$url,$match) ) $ext = $match[1];
		//- a remote url does not need to have a file extension at all
        //- local cached file does not have a matching file extension
        //Therefore get image type from the content

        $imagedim = getimagesize($resolved_url);
        if( $imagedim[2] >= 1 && $imagedim[2] <=3 && $imagedim[0] && $imagedim[1] ) {
        //target image is valid

        $imagetypes = array('','gif','jpeg','png','swf');
        $ext = $imagetypes[$imagedim[2]];
        if ( rename($resolved_url,$resolved_url.'.'.$ext) ) {
          $resolved_url .= '.'.$ext;
        }
 
 		//Don't put replacement image into cache - otherwise it will be deleted on cache cleanup.
 		//Only execute on successfull caching of remote image.       
        self::$_cache[$full_url] = array($resolved_url,$ext);

        } else {
          //target image is not valid.
          unlink($resolved_url);
          
          $resolved_url = DOMPDF_LIB_DIR . "/res/broken_image.png";
          $ext = "png";
        }
        }

      }

    } else {

      $resolved_url = build_url($proto, $host, $base_path, $url);
      if ($DEBUGPNG) print 'build_url('.$proto.','.$host.','.$base_path.','.$url.')('.$resolved_url.')';

      if ( !preg_match("/.*\.(\w+)/",$url,$match) ) {
        //debugpng
        if ($DEBUGPNG) print '[resolve_url exception '.$url.']';
          throw new DOMPDF_Exception("Unknown image type: $url.");
        }

        $ext = $match[1];

        //debugpng
        if ($DEBUGPNG) $full_url_dbg = '(local)';

    }

    if ( !is_readable($resolved_url) || !filesize($resolved_url) ) {

      //debugpng
      if ($DEBUGPNG) $full_url_dbg .= '(nocache'.$resolved_url.')';

      $_dompdf_warnings[] = "File " .$resolved_url . " is not readable or is an empty file.\n";
      $resolved_url = DOMPDF_LIB_DIR . "/res/broken_image.png";
      $ext = "png";
    }

    //debugpng
    if ($DEBUGPNG) print '[resolve_url '.$url.'|'.$full_url_dbg.'|'.$resolved_url.'|'.$ext.']';

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
        //debugpng
        if (DEBUGPNG) print '[clear unlink '.$file.']';
        if (!DEBUGKEEPTEMP)
          unlink($file);
      }
    }
  }

}
?>