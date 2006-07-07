#!/usr/bin/php
<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: load_font.php,v $
 * Created on: 2004-06-23
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

require_once("dompdf_config.inc.php");

/**
 * @access private
 */
define("_TTF2AFM", TTF2AFM . " -a -GAef -OW ");

if ( !file_exists(TTF2AFM) ) {
  die("Unable to locate the ttf2afm / ttf2pt1 executable (checked " . TTF2AFM . ").\n");
}
  
  
/**
 * Display command line usage
 * 
 */
function usage() {

  echo <<<EOD

Usage: {$_SERVER["argv"][0]} font_family n_file [b_file] [i_file] [bi_file]

font_family:      the name of the font, e.g. Verdana, 'Times New Roman', 
                  monospace, sans-serif.

n_file:           the .pfb or .ttf file for the normal, non-bold, non-italic
                  face of the font.

{b|i|bi}_file:    the files for each of the respective (bold, italic, 
                  bold-italic) faces.


If the optional b|i|bi files are not specified, load_font.php will search
the directory containing normal font file (n_file) for additional files that
it thinks might be the correct ones (e.g. that end in _Bold or b or B).  If
it finds the files they will also be processed.  All files will be
automatically copied to the DOMPDF font directory, and afm files will be
generated using ttf2afm.

Examples:

./load_font.php silkscreen /usr/share/fonts/truetype/slkscr.ttf
./load_font.php 'Times New Roman' /mnt/c_drive/WINDOWS/Fonts/times.ttf


EOD;

}

if ( $_SERVER["argc"] < 3 ) {
  usage();
  die();
}

/**
 * Installs a new font family
 *
 * This function maps a font-family name to a font.  It tries to locate the
 * bold, italic, and bold italic versions of the font as well.  Once the
 * files are located, ttf versions of the font are copied to the fonts
 * directory.  Changes to the font lookup table are saved to the cache.
 *
 * @param string $fontname the font-family name
 * @param string $normal the filename of the normal face font subtype
 * @param string $bold   the filename of the bold face font subtype
 * @param string $italic the filename of the italic face font subtype
 * @param string $bold_italic the filename of the bold italic face font subtype
 */
function install_font_family($fontname, $normal, $bold = null, $italic = null, $bold_italic = null) {

  // Check if the base filename is readable
  if ( !is_readable($normal) )
    throw new DOMPDF_Exception("Unable to read '$normal'.");
    
  $dir = dirname($normal);
  list($file, $ext) = explode(".", basename($normal), 2);  // subtract extension
    
  // Try $file_Bold.$ext etc.
  $ext = ".$ext";

  if ( !isset($bold) || !is_readable($bold) ) {
    $bold   = $dir . "/" . $file . "_Bold" . $ext;
    if ( !is_readable($bold) ) {
 
      // Try $file . "b"
      $bold = $dir . "/" . $file . "b" . $ext;
      if ( !is_readable($bold) ) {
          
        // Try $file . "B"
        $bold = $dir . "/" . $file . "B" . $ext;
        if ( !is_readable($bold) ) 
          $bold = null;
      }
    }
  }

  if ( is_null($bold) )
    echo ("Unable to find bold face file.\n");
  
  if ( !isset($italic) || !is_readable($italic) ) {
    $italic = $dir . "/" . $file . "_Italic" . $ext;
    if ( !is_readable($italic) ) {

      // Try $file . "i"
      $italic = $dir . "/" . $file . "i" . $ext;
      if ( !is_readable($italic) ) {
          
        // Try $file . "I"
        $italic = $dir . "/" . $file . "I" . $ext;
        if ( !is_readable($italic) ) 
          $italic = null;
      }
    }
  }

  if ( is_null($italic) )
    echo ("Unable to find italic face file.\n");

  if ( !isset($bold_italic) || !is_readable($bold_italic) ) {
    $bold_italic = $dir . "/" . $file . "_Bold_Italic" . $ext;
      
    if ( !is_readable($bold_italic) ) {

      // Try $file . "bi"
      $bold_italic = $dir . "/" . $file . "bi" . $ext;
      if ( !is_readable($bold_italic) ) {
          
        // Try $file . "BI"
        $bold_italic = $dir . "/" . $file . "BI" . $ext;
        if ( !is_readable($bold_italic) ) {
            
          // Try $file . "ib"
          $bold_italic = $dir . "/" . $file . "ib" . $ext;
          if ( !is_readable($bold_italic) ) {
              
            // Try $file . "IB"
            $bold_italic = $dir . "/" . $file . "IB" . $ext;
            if ( !is_readable($bold_italic) )
              $bold_italic = null;
          }
        }
      }
    }
  }
 
  if ( is_null($bold_italic) )
    echo ("Unable to find bold italic face file.\n");
 
  $fonts = compact("normal", "bold", "italic", "bold_italic");
  $entry = array();
    
  if ( mb_strtolower($ext) === ".pfb" || mb_strtolower($ext) === ".ttf" ) {

    // Copy the files to the font directory.
    foreach ($fonts as $var => $src) {

      if ( is_null($src) ) {
        $entry[$var] = DOMPDF_FONT_DIR . basename($normal);
        continue;
      }
      
      // Verify that the fonts exist and are readable
      if ( !is_readable($src) ) 
        throw new User_DOMPDF_Exception("Requested font '$pathname' is not readable");
      
      $dest = DOMPDF_FONT_DIR . basename($src);
      if ( !is_writeable(dirname($dest)) )
        throw new User_DOMPDF_Exception("Unable to write to destination '$dest'.");
        
      echo "Copying $src to $dest...\n";

      if ( !copy($src, $dest) )
        throw new DOMPDF_Exception("Unable to copy '$src' to '" . DOMPDF_FONT_DIR . "$dest'.");

      $entry[$var] = $dest;
    }

  } else 
    throw new DOMPDF_Exception("Unable to process fonts of type '$ext'.");
    
    
  // If the extension is a ttf, try and convert the fonts to afm too
  if ( mb_strtolower($ext) === ".ttf") {
    foreach ($fonts as $var => $font) {
      if ( is_null($font) ) {
        $entry[$var] = DOMPDF_FONT_DIR . mb_substr(basename($normal), 0, -4);
        continue;
      }
      $dest = DOMPDF_FONT_DIR . mb_substr(basename($font),0, -4);
      echo "Generating .afm for $font...\n";
      exec( _TTF2AFM . " " . escapeshellarg($font) . " " . $dest . " &> /dev/null", $output, $ret );
      
      $entry[$var] = $dest;
    }

  }

  // FIXME: how to generate afms from pfb?
  
  // Store the fonts in the lookup table
  Font_Metrics::set_font_family(mb_strtolower($fontname), $entry);
    
  // Save the changes
  Font_Metrics::save_font_families();
}


$normal = $_SERVER["argv"][2];
$bold   = isset($_SERVER["argv"][3]) ? $_SERVER["argv"][3] : null;
$italic = isset($_SERVER["argv"][4]) ? $_SERVER["argv"][4] : null;
$bold_italic = isset($_SERVER["argv"][5]) ? $_SERVER["argv"][5] : null;

install_font_family($_SERVER["argv"][1], $normal, $bold, $italic, $bold_italic);

?>