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
 * http://www.digitaljunkies.ca/dompdf
 *
 * @link http://www.digitaljunkies.ca/dompdf
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf
 * @version 0.3
 */

/* $Id: dompdf_config.inc.php,v 1.3 2005-03-02 00:51:23 benjcarson Exp $ */

error_reporting(E_STRICT | E_ALL);
ini_set("zend.ze1_compatibility_mode", "0");

/**
 * The root of your DOMPDF installation
 */
define("DOMPDF_DIR", realpath(dirname(__FILE__)));

/**
 * The location of the DOMPDF include directory
 */
define("DOMPDF_INC_DIR", DOMPDF_DIR . "/include");

/**
 * The location of the DOMPDF lib directory
 */
define("DOMPDF_LIB_DIR", DOMPDF_DIR . "/lib");

/**
 * The location of the DOMPDF font directory
 *
 * Note this directory must be writable by the webserver process (or user
 * executing DOMPDF from the CLI).  *Please note the trailing slash.*
 */
define("DOMPDF_FONT_DIR", DOMPDF_DIR . "/lib/fonts/");

/**
 * The location of the system's temporary directory.
 *
 * This directory must be writeable by the webserver process.
 * It is used to download remote images.
 */
define("DOMPDF_TEMP_DIR", "/tmp");

/**
 * The path to the tt2pt1 utility (used to convert ttf to afm)
 *
 * Not strictly necessary, but useful if you would like to install 
 * additional fonts using the {@link load_font.php} utility.
 *
 * @link http://ttf2pt1.sourceforge.net/
 */
define("TTF2AFM", "/usr/bin/ttf2pt1");

/**
 * The PDF rendering backend to use
 *
 * Valid settings are 'PDFLib', 'CPDF' (the bundled R&OS PDF class)
 * and 'auto'.  'auto' will look for PDFLib and use it if found, or if
 * not it will fall back on CPDF.
 *
 * Both PDFLib & CPDF rendering backends provide sufficient rendering
 * capabilities for dompdf, however additional features (e.g. object,
 * image and font support, etc.)  differ between backends.  Please see
 * {@link PDFLib_Adapter}, {@link CPDF_Adapter} and the documentation
 * for each backend for more information.
 * 
 * @link http://www.pdflib.com
 * @link http://www.ros.co.nz/pdf
 */
define("DOMPDF_PDF_BACKEND", "auto");

/**
 * The default paper size.
 *
 * If you live outside of North America, feel free to change this ;)
 *
 * @see CPDF_Adapter::PAPER_SIZES for valid sizes
 */
define("DOMPDF_DEFAULT_PAPER_SIZE", "letter");


/**
 * The default font family
 *
 * Used if no suitable fonts can be found
 * @var string
 */
define("DOMPDF_DEFAULT_FONT", "serif");

/**
 * Image DPI setting
 *
 * This setting determines the default DPI setting for jpeg & png images.
 * The DPI may be overridden by explictly setting the image's width & height
 * style attributes (i.e. if the image's native width is 600 pixels and you
 * specify the image's width as 72 points, the image will have a DPI of 600
 * in the rendered PDF.
 */
define("DOMPDF_DPI", "150");

/**
 * Enable inline PHP
 *
 * If this setting is set to true then DOMPDF will automatically evaluate
 * inline PHP contained within <script type="text/php"> ... </script> tags.
 *
 * Enabling this for documents you do not trust (e.g. arbitrary remote html
 * pages) is a security risk.  Set this option to false if you wish to process
 * untrusted documents.
 */
define("DOMPDF_ENABLE_PHP", true);


/**
 * Enable remote file access
 *
 * If this setting is set to true, DOMPDF will access remote sites for
 * images and CSS files as required.
 */
define("DOMPDF_ENABLE_REMOTE", false);
 
/**
 * DOMPDF autoload function
 *
 * If you have an existing autoload function, add a call to this function
 * from your existing __autoload() implementation.
 *
 * @param string $class
 */
function DOMPDF_autoload($class) {
  $filename = strtolower($class) . ".cls.php";
  require_once(DOMPDF_INC_DIR . "/$filename");
}

if ( !function_exists("__autoload") ) {
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
 * Global array of warnings generated by DomDocument parser and
 * stylesheet class
 *
 * @var array
 */
$_dompdf_warnings = array();

/**
 * If true, $_dompdf_warnings is dumped on script termination.
 *
 * @var bool
 */
$_dompdf_show_warnings = false;

/**
 * If true, the entire tree is dumped to stdout in dompdf.cls.php 
 *
 * @var bool
 */
$_dompdf_debug = false;

require_once(DOMPDF_INC_DIR . "/functions.inc.php");

?>