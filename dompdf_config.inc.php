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
 * @version 0.5.1
 */

/* $Id: dompdf_config.inc.php,v 1.19 2006/07/07 21:31:02 benjcarson Exp $ */

error_reporting(E_STRICT | E_ALL);

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
define("DOMPDF_CHROOT", realpath(DOMPDF_DIR));

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
 * Valid settings are 'PDFLib', 'CPDF' (the bundled R&OS PDF class),
 * 'GD' and 'auto'.  'auto' will look for PDFLib and use it if found,
 * or if not it will fall back on CPDF.  'GD' renders PDFs to graphic
 * files.  {@link Canvas_Factory} ultimately determines which
 * rendering class to instantiate based on this setting.
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
define("DOMPDF_PDF_BACKEND", "auto");

/**
 * PDFlib license key
 *
 * If you are using a licensed, commercial version of PDFlib, specify
 * your license key here.  If you are using PDFlib-Lite or are evaluating
 * the commercial version of PDFlib, comment out this setting.
 *
 * @link http://www.pdflib.com
 */
#define("DOMPDF_PDFLIB_LICENSE", "your license key here");

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
 * This setting determines the default DPI setting for images.  The
 * DPI may be overridden for inline images by explictly setting the
 * image's width & height style attributes (i.e. if the image's native
 * width is 600 pixels and you specify the image's width as 72 points,
 * the image will have a DPI of 600 in the rendered PDF.  The DPI of
 * background images can not be overridden and is controlled entirely
 * via this parameter.
 *
 * @var int
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
 *
 * @var bool
 */
define("DOMPDF_ENABLE_PHP", false);


/**
 * Enable remote file access
 *
 * If this setting is set to true, DOMPDF will access remote sites for
 * images and CSS files as required.
 *
 * @var bool 
 */
define("DOMPDF_ENABLE_REMOTE", true);
 
/**
 * DOMPDF autoload function
 *
 * If you have an existing autoload function, add a call to this function
 * from your existing __autoload() implementation.
 *
 * @param string $class
 */
function DOMPDF_autoload($class) {
  $filename = mb_strtolower($class) . ".cls.php";
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
