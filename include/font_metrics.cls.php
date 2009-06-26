<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: font_metrics.cls.php,v $
 * Created on: 2004-06-02
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
 * - On missing font on explicite font selection don't change subtype and don't return default font.
 * - On requesting default font and missing subtype, check similar subtypes, then any subtype, then normal. The last must exist.
 * - Add comments
 */

/* $Id: font_metrics.cls.php,v 1.7 2008-03-12 06:35:43 benjcarson Exp $ */

require_once(DOMPDF_LIB_DIR . "/class.pdf.php");

/**
 * Name of the font cache file
 *
 * This file must be writable by the webserver process only to update it
 * with save_font_families() after adding the .afm file references of a new font family
 * with Font_Metrics::save_font_families().
 * This is typically done only from command line with load_font.php on converting
 * ttf fonts to afm with an external tool referenced in the define _TTF2AFM
 *
 * Declared here because PHP5 prevents constants from being declared with expressions
 */
if (file_exists(DOMPDF_FONT_DIR . "dompdf_font_family_cache")) {
	define('__DOMPDF_FONT_CACHE_FILE', DOMPDF_FONT_DIR . "dompdf_font_family_cache");
} else {
	define('__DOMPDF_FONT_CACHE_FILE', DOMPDF_FONT_DIR . "dompdf_font_family_cache.dist");
}


/**
 * The font metrics class
 *
 * This class provides information about fonts and text.  It can resolve
 * font names into actual installed font files, as well as determine the
 * size of text in a particular font and size.
 *
 * @static
 * @package dompdf
 */
class Font_Metrics {

  /**
   * @see __DOMPDF_FONT_CACHE_FILE
   */
  const CACHE_FILE = __DOMPDF_FONT_CACHE_FILE;
  
  /**
   * Underlying {@link Cpdf} object to perform text size calculations
   *
   * @var Cpdf
   */
  static protected $_pdf = null;

  /**
   * Array of font family names to font files
   *
   * Usually cached by the {@link load_font.php} script
   *
   * @var array
   */
  static protected $_font_lookup = array();
  
  
  /**
   * Class initialization
   *
   */
  static function init() {
    if (!self::$_pdf) {
      self::load_font_families();
      self::$_pdf = Canvas_Factory::get_instance();
    }
  }

  /**
   * Calculates text size, in points
   *
   * @param string $text the text to be sized
   * @param string $font the desired font
   * @param float  $size the desired font size
   * @param float  $spacing word spacing, if any
   * @return float
   */
  static function get_text_width($text, $font, $size, $spacing = 0) {
    return self::$_pdf->get_text_width($text, $font, $size, $spacing);
  }

  /**
   * Calculates font height
   *
   * @param string $font
   * @param float $size
   * @return float
   */
  static function get_font_height($font, $size) {
    return self::$_pdf->get_font_height($font, $size);
  }

  /**
   * Resolves a font family & subtype into an actual font file
   *
   * Subtype can be one of 'normal', 'bold', 'italic' or 'bold_italic'.  If
   * the particular font family has no suitable font file, the default font
   * ({@link DOMPDF_DEFAULT_FONT}) is used.  The font file returned
   * is the absolute pathname to the font file on the system.
   *
   * @param string $family
   * @param string $subtype
   * @return string
   */
  static function get_font($family, $subtype = "normal") {

    /* Allow calling for various fonts in search path. Therefore not immediately
     * return replacement on non match.
     * Only when called with NULL try replacement.
     * When this is also missing there is really trouble.
     * If only the subtype fails, nevertheless return failure.
     * Only on checking the fallback font, check various subtypes on same font.
     */

    if ( $family ) {
      $family = str_replace( array("'", '"'), "", mb_strtolower($family));
      $subtype = mb_strtolower($subtype);

      if ( isset(self::$_font_lookup[$family][$subtype]) ) {
        return self::$_font_lookup[$family][$subtype];
      }
      return null;
    }

    $family = DOMPDF_DEFAULT_FONT;

    if ( isset(self::$_font_lookup[$family][$subtype]) ) {
      return self::$_font_lookup[$family][$subtype];
    }

    foreach ( self::$_font_lookup[$family] as $sub => $font ) {
      if (strpos($subtype, $sub) !== false) {
        return $font;
      }
    }

    if ($subtype != "normal") {
      foreach ( self::$_font_lookup[$family] as $sub => $font ) {
        if ($sub != "normal") {
          return $font;
        }
      }
    }

    $subtype = "normal";

    if ( isset(self::$_font_lookup[$family][$subtype]) ) {
      return self::$_font_lookup[$family][$subtype];
    }
    return null;
  }

  /**
   * Saves the stored font family cache
   *
   * The name and location of the cache file are determined by {@link
   * Font_Metrics::CACHE_FILE}.  This file should be writable by the
   * webserver process.
   *
   * @see Font_Metrics::load_font_families()
   */
  static function save_font_families() {

    file_put_contents(self::CACHE_FILE, var_export(self::$_font_lookup, true));
    
  }

  /**
   * Loads the stored font family cache
   *
   * @see save_font_families()
   */
  static function load_font_families() {
    if ( !is_readable(self::CACHE_FILE) )
      return;

    $data = file_get_contents(self::CACHE_FILE);

    if ( $data != "" )
      eval ('self::$_font_lookup = ' . $data . ";");
  }

  /**
   * Returns the current font lookup table
   *
   * @return array
   */
  static function get_font_families() {
    return self::$_font_lookup;
  }

  static function set_font_family($fontname, $entry) {
    self::$_font_lookup[mb_strtolower($fontname)] = $entry;
  }
}

Font_Metrics::init();

?>