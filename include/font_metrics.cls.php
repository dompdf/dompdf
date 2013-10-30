<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

require_once DOMPDF_LIB_DIR . "/class.pdf.php";

/**
 * Name of the font cache file
 *
 * This file must be writable by the webserver process only to update it
 * with save_font_families() after adding the .afm file references of a new font family
 * with Font_Metrics::save_font_families().
 * This is typically done only from command line with load_font.php on converting
 * ttf fonts to ufm with php-font-lib.
 *
 * Declared here because PHP5 prevents constants from being declared with expressions
 */
define('__DOMPDF_FONT_CACHE_FILE', DOMPDF_FONT_DIR . "dompdf_font_family_cache.php");

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
   * Underlying {@link Canvas} object to perform text size calculations
   *
   * @var Canvas
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
  static function init(Canvas $canvas = null) {
    if (!self::$_pdf) {
      if (!$canvas) {
        $canvas = Canvas_Factory::get_instance(new DOMPDF());
      }
      
      self::$_pdf = $canvas;
    }
  }

  /**
   * Calculates text size, in points
   *
   * @param string $text the text to be sized
   * @param string $font the desired font
   * @param float  $size the desired font size
   * @param float  $word_spacing
   * @param float  $char_spacing
   *
   * @internal param float $spacing word spacing, if any
   * @return float
   */
  static function get_text_width($text, $font, $size, $word_spacing = 0.0, $char_spacing = 0.0) {
    //return self::$_pdf->get_text_width($text, $font, $size, $word_spacing, $char_spacing);
    
    // @todo Make sure this cache is efficient before enabling it
    static $cache = array();
    
    if ( $text === "" ) {
      return 0;
    }
    
    // Don't cache long strings
    $use_cache = !isset($text[50]); // Faster than strlen
    
    $key = "$font/$size/$word_spacing/$char_spacing";
    
    if ( $use_cache && isset($cache[$key][$text]) ) {
      return $cache[$key]["$text"];
    }
    
    $width = self::$_pdf->get_text_width($text, $font, $size, $word_spacing, $char_spacing);
    
    if ( $use_cache ) {
      $cache[$key][$text] = $width;
    }
    
    return $width;
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
   * Subtype can be one of 'normal', 'bold', 'italic' or 'bold_italic'.  If
   * the particular font family has no suitable font file, the default font
   * ({@link DOMPDF_DEFAULT_FONT}) is used.  The font file returned
   * is the absolute pathname to the font file on the system.
   *
   * @param string $family_raw
   * @param string $subtype_raw
   *
   * @return string
   */
  static function get_font($family_raw, $subtype_raw = "normal") {
    static $cache = array();
    
    if ( isset($cache[$family_raw][$subtype_raw]) ) {
      return $cache[$family_raw][$subtype_raw];
    }
    
    /* Allow calling for various fonts in search path. Therefore not immediately
     * return replacement on non match.
     * Only when called with NULL try replacement.
     * When this is also missing there is really trouble.
     * If only the subtype fails, nevertheless return failure.
     * Only on checking the fallback font, check various subtypes on same font.
     */
    
    $subtype = strtolower($subtype_raw);
    
    if ( $family_raw ) {
      $family = str_replace( array("'", '"'), "", strtolower($family_raw));
      
      if ( isset(self::$_font_lookup[$family][$subtype]) ) {
        return $cache[$family_raw][$subtype_raw] = self::$_font_lookup[$family][$subtype];
      }
      
      return null;
    }

    $family = "serif";

    if ( isset(self::$_font_lookup[$family][$subtype]) ) {
      return $cache[$family_raw][$subtype_raw] = self::$_font_lookup[$family][$subtype];
    }
    
    if ( !isset(self::$_font_lookup[$family]) ) {
      return null;
    }
    
    $family = self::$_font_lookup[$family];

    foreach ( $family as $sub => $font ) {
      if (strpos($subtype, $sub) !== false) {
        return $cache[$family_raw][$subtype_raw] = $font;
      }
    }

    if ($subtype !== "normal") {
      foreach ( $family as $sub => $font ) {
        if ($sub !== "normal") {
          return $cache[$family_raw][$subtype_raw] = $font;
        }
      }
    }

    $subtype = "normal";

    if ( isset($family[$subtype]) ) {
      return $cache[$family_raw][$subtype_raw] = $family[$subtype];
    }
    
    return null;
  }
  
  static function get_family($family) {
    $family = str_replace( array("'", '"'), "", mb_strtolower($family));
    
    if ( isset(self::$_font_lookup[$family]) ) {
      return self::$_font_lookup[$family];
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
    // replace the path to the DOMPDF font directories with the corresponding constants (allows for more portability)
    $cache_data = var_export(self::$_font_lookup, true);
    $cache_data = str_replace('\''.DOMPDF_FONT_DIR , 'DOMPDF_FONT_DIR . \'' , $cache_data);
    $cache_data = str_replace('\''.DOMPDF_DIR , 'DOMPDF_DIR . \'' , $cache_data);
    $cache_data = "<"."?php return $cache_data ?".">";
    file_put_contents(self::CACHE_FILE, $cache_data);
  }

  /**
   * Loads the stored font family cache
   *
   * @see save_font_families()
   */
  static function load_font_families() {
    $dist_fonts = require_once DOMPDF_DIR . "/lib/fonts/dompdf_font_family_cache.dist.php";
    
    // FIXME: temporary step for font cache created before the font cache fix
    if ( is_readable( DOMPDF_FONT_DIR . "dompdf_font_family_cache" ) ) {
      $old_fonts = require_once DOMPDF_FONT_DIR . "dompdf_font_family_cache";
      // If the font family cache is still in the old format
      if ( $old_fonts === 1 ) {
        $cache_data = file_get_contents(DOMPDF_FONT_DIR . "dompdf_font_family_cache");
        file_put_contents(DOMPDF_FONT_DIR . "dompdf_font_family_cache", "<"."?php return $cache_data ?".">");
        $old_fonts = require_once DOMPDF_FONT_DIR . "dompdf_font_family_cache";
      }
      $dist_fonts += $old_fonts;
    }
    
    if ( !is_readable(self::CACHE_FILE) ) {
      self::$_font_lookup = $dist_fonts;
      return;
    }
    
    self::$_font_lookup = require_once self::CACHE_FILE;
    
    // If the font family cache is still in the old format
    if ( self::$_font_lookup === 1 ) {
      $cache_data = file_get_contents(self::CACHE_FILE);
      file_put_contents(self::CACHE_FILE, "<"."?php return $cache_data ?".">");
      self::$_font_lookup = require_once self::CACHE_FILE;
    }
    
    // Merge provided fonts
    self::$_font_lookup += $dist_fonts;
  }
  
  static function get_type($type) {
    if (preg_match("/bold/i", $type)) {
      if (preg_match("/italic|oblique/i", $type)) {
        $type = "bold_italic";
      }
      else {
        $type = "bold";
      }
    }
    elseif (preg_match("/italic|oblique/i", $type)) {
      $type = "italic";
    }
    else {
      $type = "normal";
    }
      
    return $type;
  }
  
  static function install_fonts($files) {
    $names = array();
    
    foreach($files as $file) {
      $font = Font::load($file);
      $records = $font->getData("name", "records");
      $type = self::get_type($records[2]);
      $names[mb_strtolower($records[1])][$type] = $file;
    }
    
    return $names;
  }
  
  static function get_system_fonts() {
    $files = glob("/usr/share/fonts/truetype/*.ttf") +
             glob("/usr/share/fonts/truetype/*/*.ttf") +
             glob("/usr/share/fonts/truetype/*/*/*.ttf") +
             glob("C:\\Windows\\fonts\\*.ttf") + 
             glob("C:\\WinNT\\fonts\\*.ttf") + 
             glob("/mnt/c_drive/WINDOWS/Fonts/");
    
    return self::install_fonts($files);
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
  
  static function register_font($style, $remote_file) {
    $fontname = mb_strtolower($style["family"]);
    $families = Font_Metrics::get_font_families();
    
    $entry = array();
    if ( isset($families[$fontname]) ) {
      $entry = $families[$fontname];
    }
    
    $local_file = DOMPDF_FONT_DIR . md5($remote_file);
    $cache_entry = $local_file;
    $local_file .= ".ttf";
    
    $style_string = Font_Metrics::get_type("{$style['weight']} {$style['style']}");
    
    if ( !isset($entry[$style_string]) ) {
      $entry[$style_string] = $cache_entry;
      
      Font_Metrics::set_font_family($fontname, $entry);
      
      // Download the remote file
      if ( !is_file($local_file) ) {
        file_put_contents($local_file, file_get_contents($remote_file));
      }
      
      $font = Font::load($local_file);
      
      if (!$font) {
        return false;
      }
      
      $font->parse();
      $font->saveAdobeFontMetrics("$cache_entry.ufm");
      
      // Save the changes
      Font_Metrics::save_font_families();
    }
    
    return true;
  }
}

Font_Metrics::load_font_families();
