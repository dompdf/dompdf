<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: dompdf_config.inc.php,v $
 * Created on: 2004-08-04
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
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 0.5.1.htischer.20090507
 * - Allow overriding of configuration settings by calling php script.
 *   This allows replacing of dompdf by a new version in an application
 *   without any modification,
 * - Optionally separate font cache folder from font folder.
 *   This allows write protecting the entire installation
 * - Add settings to enable/disable additional debug output categories
 * - Change some defaults to more practical values
 * - Add comments about configuration parameter implications
 */

/* $Id$ */

//error_reporting(E_STRICT | E_ALL | E_DEPRECATED);
//ini_set("display_errors", 1);

/**
 * The root of your DOMPDF installation
 */
define("DOMPDF_DIR", str_replace(DIRECTORY_SEPARATOR, '/', realpath(dirname(__FILE__))));

/**
 * The location of the DOMPDF include directory
 */
define("DOMPDF_INC_DIR", DOMPDF_DIR . "/include");

/**
 * The location of the DOMPDF lib directory
 */
define("DOMPDF_LIB_DIR", DOMPDF_DIR . "/lib");

/**
 * Some installations don't have $_SERVER['DOCUMENT_ROOT']
 * http://fyneworks.blogspot.com/2007/08/php-documentroot-in-iis-windows-servers.html
 */
if( !isset($_SERVER['DOCUMENT_ROOT']) ) {
  $path = "";
  
  if ( isset($_SERVER['SCRIPT_FILENAME']) )
    $path = $_SERVER['SCRIPT_FILENAME'];
  elseif ( isset($_SERVER['PATH_TRANSLATED']) )
    $path = str_replace('\\\\', '\\', $_SERVER['PATH_TRANSLATED']);
    
  $_SERVER['DOCUMENT_ROOT'] = str_replace( '\\', '/', substr($path, 0, 0-strlen($_SERVER['PHP_SELF'])));
}

/** Include the custom config file if it exists */
if ( file_exists(DOMPDF_DIR . "/dompdf_config.custom.inc.php") ){
  require_once(DOMPDF_DIR . "/dompdf_config.custom.inc.php");
}

//FIXME: Some function definitions rely on the constants defined by DOMPDF. However, might this location prove problematic?
require_once(DOMPDF_INC_DIR . "/functions.inc.php");

/**
 * The location of the DOMPDF font directory
 *
 * If DOMPDF_FONT_DIR identical to DOMPDF_FONT_CACHE or user executing DOMPDF from the CLI,
 * this directory must be writable by the webserver process ().
 * *Please note the trailing slash.*
 *
 * Notes regarding fonts:
 * Additional .afm font metrics can be added by executing load_font.php from command line.
 * Converting ttf fonts to afm requires the external tool referenced by TTF2AFM
 *
 * Only the original "Base 14 fonts" are present on all pdf viewers. Additional fonts must
 * be embedded in the pdf file or the PDF may not display correctly. This can significantly
 * increase file size and could violate copyright provisions of a font. Font embedding is
 * not currently supported (? via HT).
 *
 * Any font specification in the source HTML is translated to the closest font available
 * in the font directory.
 *
 * The pdf standard "Base 14 fonts" are:
 * Courier, Courier-Bold, Courier-BoldOblique, Courier-Oblique,
 * Helvetica, Helvetica-Bold, Helvetica-BoldOblique, Helvetica-Oblique,
 * Times-Roman, Times-Bold, Times-BoldItalic, Times-Italic,
 * Symbol,
 * ZapfDingbats,
 *
 * *Please note the trailing slash.*
 */
def("DOMPDF_FONT_DIR", DOMPDF_DIR . "/lib/fonts/");

/**
 * The location of the DOMPDF font cache directory
 *
 * Note this directory must be writable by the webserver process
 * This folder must already exist!
 * It contains the .afm files, on demand parsed, converted to php syntax and cached
 * This folder can be the same as DOMPDF_FONT_DIR
 */
def("DOMPDF_FONT_CACHE", DOMPDF_FONT_DIR);

/**
 * The location of a temporary directory.
 *
 * The directory specified must be writeable by the webserver process.
 * The temporary directory is required to download remote images and when
 * using the PFDLib back end.
 */
def("DOMPDF_TEMP_DIR", sys_get_temp_dir());

/**
 * ==== IMPORTANT ====
 *
 * dompdf's "chroot": Prevents dompdf from accessing system files or other
 * files on the webserver.  All local files opened by dompdf must be in a
 * subdirectory of this directory.  DO NOT set it to '/' since this could
 * allow an attacker to use dompdf to read any files on the server.  This
 * should be an absolute path.
 * This is only checked on command line call by dompdf.php, but not by
 * direct class use like:
 * $dompdf = new DOMPDF();	$dompdf->load_html($htmldata); $dompdf->render(); $pdfdata = $dompdf->output();
 */
def("DOMPDF_CHROOT", realpath(DOMPDF_DIR));

/**
 * Whether to use Unicode fonts or not.
 *
 * When set to true the PDF backend must be set to "CPDF" and fonts must be
 * loaded via the modified ttf2ufm tool included with dompdf (see below).
 * Unicode font metric files (with .ufm extensions) must be created with
 * ttf2ufm.  load_font.php should do this for you if the TTF2AFM define below
 * points to the modified ttf2ufm tool included with dompdf.
 *
 * When enabled, dompdf can support all Unicode glyphs.  Any glyphs used in a
 * document must be present in your fonts, however.
 */
def("DOMPDF_UNICODE_ENABLED", true);

/**
 * The path to the tt2pt1 utility (used to convert ttf to afm)
 *
 * Not strictly necessary, but useful if you would like to install
 * additional fonts using the {@link load_font.php} utility.
 *
 * Windows users should use something like this:
 * define("TTF2AFM", "C:\\Program Files\\Ttf2Pt1\\bin\\ttf2pt1.exe");
 *
 * @link http://ttf2pt1.sourceforge.net/
 */
if ( strpos(PHP_OS, "WIN") === false )
  def("TTF2AFM", DOMPDF_LIB_DIR ."/ttf2ufm/ttf2ufm-src/ttf2pt1");
else 
  def("TTF2AFM", "C:\\Program Files\\GnuWin32\\bin\\ttf2pt1.exe");

/**
 * The PDF rendering backend to use
 *
 * Valid settings are 'PDFLib', 'CPDF' (the bundled R&OS PDF class), 'GD' and
 * 'auto'.  'auto' will look for PDFLib and use it if found, or if not it will
 * fall back on CPDF.  'GD' renders PDFs to graphic files.  {@link
 * Canvas_Factory} ultimately determines which rendering class to instantiate
 * based on this setting.
 *
 * Both PDFLib & CPDF rendering backends provide sufficient rendering
 * capabilities for dompdf, however additional features (e.g. object,
 * image and font support, etc.) differ between backends.  Please see
 * {@link PDFLib_Adapter} for more information on the PDFLib backend
 * and {@link CPDF_Adapter} and lib/class.pdf.php for more information
 * on CPDF.  Also see the documentation for each backend at the links
 * below.
 *
 * The GD rendering backend is a little different than PDFLib and
 * CPDF.  Several features of CPDF and PDFLib are not supported or do
 * not make any sense when creating image files.  For example,
 * multiple pages are not supported, nor are PDF 'objects'.  Have a
 * look at {@link GD_Adapter} for more information.  GD support is new
 * and experimental, so use it at your own risk.
 *
 * @link http://www.pdflib.com
 * @link http://www.ros.co.nz/pdf
 * @link http://www.php.net/image
 */
def("DOMPDF_PDF_BACKEND", "CPDF");

/**
 * PDFlib license key
 *
 * If you are using a licensed, commercial version of PDFlib, specify
 * your license key here.  If you are using PDFlib-Lite or are evaluating
 * the commercial version of PDFlib, comment out this setting.
 *
 * @link http://www.pdflib.com
 *
 * If pdflib present in web server and auto or selected explicitely above,
 * a real license code must exist!
 */
#def("DOMPDF_PDFLIB_LICENSE", "your license key here");

/**
 * html target media view which should be rendered into pdf.
 * List of types and parsing rules for future extensions:
 * http://www.w3.org/TR/REC-html40/types.html
 *   screen, tty, tv, projection, handheld, print, braille, aural, all
 * Note: aural is deprecated in CSS 2.1 because it is replaced by speech in CSS 3.
 * Note, even though the generated pdf file is intended for print output,
 * the desired content might be different (e.g. screen or projection view of html file).
 * Therefore allow specification of content here.
 */
def("DOMPDF_DEFAULT_MEDIA_TYPE", "screen");

/**
 * The default paper size.
 *
 * North America standard is "letter"; other countries generally "a4"
 *
 * @see CPDF_Adapter::PAPER_SIZES for valid sizes
 */
def("DOMPDF_DEFAULT_PAPER_SIZE", "letter");

/**
 * The default font family
 *
 * Used if no suitable fonts can be found. This must exist in the font folder.
 * @var string
 */
def("DOMPDF_DEFAULT_FONT", "serif");

/**
 * Image DPI setting
 *
 * This setting determines the default DPI setting for images and fonts.  The
 * DPI may be overridden for inline images by explictly setting the
 * image's width & height style attributes (i.e. if the image's native
 * width is 600 pixels and you specify the image's width as 72 points,
 * the image will have a DPI of 600 in the rendered PDF.  The DPI of
 * background images can not be overridden and is controlled entirely
 * via this parameter.
 *
 * For the purposes of DOMPDF, pixels per inch (PPI) = dots per inch (DPI).
 * If a size in html is given as px (or without unit as image size),
 * this tells the corresponding size in pt.
 * This adjusts the relative sizes to be similar to the rendering of the
 * html page in a reference browser.
 *
 * In pdf, always 1 pt = 1/72 inch
 *
 * Rendering resolution of various browsers in px per inch:
 * Windows Firefox and Internet Explorer:
 *   SystemControl->Display properties->FontResolution: Default:96, largefonts:120, custom:?
 * Linux Firefox:
 *   about:config *resolution: Default:96
 *   (xorg screen dimension in mm and Desktop font dpi settings are ignored)
 *
 * Take care about extra font/image zoom factor of browser.
 *
 * In images, <img> size in pixel attribute, img css style, are overriding
 * the real image dimension in px for rendering.
 *
 * @var int
 */
def("DOMPDF_DPI", 96);

/**
 * Enable inline PHP
 *
 * If this setting is set to true then DOMPDF will automatically evaluate
 * inline PHP contained within <script type="text/php"> ... </script> tags.
 *
 * Enabling this for documents you do not trust (e.g. arbitrary remote html
 * pages) is a security risk.  Set this option to false if you wish to process
 * untrusted documents.
 *
 * @var bool
 */
def("DOMPDF_ENABLE_PHP", false);

/**
 * Enable inline Javascript
 *
 * If this setting is set to true then DOMPDF will automatically insert
 * JavaScript code contained within <script type="text/javascript"> ... </script> tags.
 *
 * @var bool
 */
def("DOMPDF_ENABLE_JAVASCRIPT", true);

/**
 * Enable remote file access
 *
 * If this setting is set to true, DOMPDF will access remote sites for
 * images and CSS files as required.
 * This is required for part of test case www/test/image_variants.html through www/examples.php
 *
 * Attention!
 * This can be a security risk, in particular in combination with DOMPDF_ENABLE_PHP and
 * allowing remote access to dompdf.php or on allowing remote html code to be passed to
 * $dompdf = new DOMPDF(); $dompdf->load_html(...);
 * This allows anonymous users to download legally doubtful internet content which on
 * tracing back appears to being downloaded by your server, or allows malicious php code
 * in remote html pages to be executed by your server with your account privileges.
 *
 * @var bool
 */
def("DOMPDF_ENABLE_REMOTE", false);

/**
 * The debug output log
 * @var string
 */
def("DOMPDF_LOG_OUTPUT_FILE", DOMPDF_FONT_DIR."log.htm");

/**
 * A ratio applied to the fonts height to be more like browsers' line height
 */
def("DOMPDF_FONT_HEIGHT_RATIO", 1.1);

/**
 * Enable CSS float
 *
 * Allows people to disabled CSS float support
 * @var bool
 */
def("DOMPDF_ENABLE_CSS_FLOAT", false);
 
/**
 * DOMPDF autoload function
 *
 * If you have an existing autoload function, add a call to this function
 * from your existing __autoload() implementation.
 *
 * @param string $class
 */
function DOMPDF_autoload($class) {
  $filename = DOMPDF_INC_DIR . "/" . mb_strtolower($class) . ".cls.php";
  
  if ( is_file($filename) )
    require_once($filename);
}

// If SPL autoload functions are available (PHP >= 5.1.2)
if ( function_exists("spl_autoload_register") ) {
  $autoload = "DOMPDF_autoload";
  $funcs = spl_autoload_functions();
  
  // No functions currently in the stack. 
  if ( $funcs === false ) { 
    spl_autoload_register($autoload); 
  }
  
  // If PHP >= 5.3 the $prepend argument is available
  else if ( version_compare(PHP_VERSION, '5.3', '>=') ) {
    spl_autoload_register($autoload, true, true); 
  }
  
  else {
    // Unregister existing autoloaders... 
    $compat = version_compare(PHP_VERSION, '5.1.2', '<=') && 
              version_compare(PHP_VERSION, '5.1.0', '>=');
              
    foreach ($funcs as $func) { 
      if (is_array($func)) { 
        // :TRICKY: There are some compatibility issues and some 
        // places where we need to error out 
        $reflector = new ReflectionMethod($func[0], $func[1]); 
        if (!$reflector->isStatic()) { 
          throw new Exception('This function is not compatible with non-static object methods due to PHP Bug #44144.'); 
        }
        
        // Suprisingly, spl_autoload_register supports the 
        // Class::staticMethod callback format, although call_user_func doesn't 
        if ($compat) $func = implode('::', $func); 
      }
      
      spl_autoload_unregister($func); 
    } 
    
    // Register the new one, thus putting it at the front of the stack... 
    spl_autoload_register($autoload); 
    
    // Now, go back and re-register all of our old ones. 
    foreach ($funcs as $func) { 
      spl_autoload_register($func); 
    }
    
    // Be polite and ensure that userland autoload gets retained
    if ( function_exists("__autoload") ) {
      spl_autoload_register("__autoload");
    }
  }
}

else if ( !function_exists("__autoload") ) {
  /**
   * Default __autoload() function
   *
   * @param string $class
   */
  function __autoload($class) {
    DOMPDF_autoload($class);
  }
}

// ### End of user-configurable options ###


/**
 * Ensure that PHP is working with text internally using UTF8 character encoding.
 */
mb_internal_encoding('UTF-8');

/**
 * Global array of warnings generated by DomDocument parser and
 * stylesheet class
 *
 * @var array
 */
global $_dompdf_warnings;
$_dompdf_warnings = array();

/**
 * If true, $_dompdf_warnings is dumped on script termination when using
 * dompdf/dompdf.php or after rendering when using the DOMPDF class.
 * When using the class, setting this value to true will prevent you from
 * streaming the PDF.
 *
 * @var bool
 */
global $_dompdf_show_warnings;
$_dompdf_show_warnings = false;

/**
 * If true, the entire tree is dumped to stdout in dompdf.cls.php.
 * Setting this value to true will prevent you from streaming the PDF.
 *
 * @var bool
 */
global $_dompdf_debug;
$_dompdf_debug = false;

/**
 * Array of enabled debug message types
 *
 * @var array
 */
global $_DOMPDF_DEBUG_TYPES;
$_DOMPDF_DEBUG_TYPES = array(); //array("page-break" => 1);

/* Optionally enable different classes of debug output before the pdf content.
 * Visible if displaying pdf as text,
 * E.g. on repeated display of same pdf in browser when pdf is not taken out of
 * the browser cache and the premature output prevents setting of the mime type.
 */
def('DEBUGPNG', false);
def('DEBUGKEEPTEMP', false);
def('DEBUGCSS', false);

/* Layout debugging. Will display rectangles around different block levels.
 * Visible in the PDF itself.
 */
def('DEBUG_LAYOUT', false);
def('DEBUG_LAYOUT_LINES', true);
def('DEBUG_LAYOUT_BLOCKS', true);
def('DEBUG_LAYOUT_INLINE', true);
def('DEBUG_LAYOUT_PADDINGBOX', true);
