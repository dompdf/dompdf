<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: dompdf.php,v $
 * Created on: 2004-06-22
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
 * dompdf.php is a simple script to drive DOMPDF.  It can be executed from
 * a browser or from the command line.
 * 
 * @link http://www.digitaljunkies.ca/dompdf
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf
 * @version 0.3
 */

/* $Id: dompdf.php,v 1.5 2005-05-18 21:25:17 benjcarson Exp $ */

/**
 * Display command line usage:
 *
 * Usage: ./dompdf.php [options] html_file
 * 
 * html_file can be a filename, a url if fopen_wrappers are enabled, or the '-'
 * character to read from standard input.
 * 
 * Options:
 *  -h             Show this message
 *  -l             list available paper sizes
 *  -p size        paper size; something like 'letter', 'A4', 'legal', etc.  The default is
 *                 'letter'
 *  -o orientation either 'portrait' or 'landscape'.  Default is 'portrait'.
 *  -b path        set the 'document root' of the html_file.  Relative urls (for
 *                 stylesheets) are resolved using this directory.  Default is the
 *                 directory of html_file.
 *  -f file        the output filename.  Default is the input [html_file].pdf.
 *  -v             verbose: display html parsing warnings and file not found errors.
 *  -d             very verbose: display oodles of debugging output: every frame in the
 *                 tree is printed to stdout.
 * 
 *
 */

function usage() {
  echo
    "\nUsage: {$_SERVER["argv"][0]} [options] html_file\n\n".
    "html_file can be a filename, a url if fopen_wrappers are enabled, or the '-' \n".
    "character to read from standard input.\n\n".
    "Options:\n".
    " -h\t\tShow this message\n".
    " -l\t\tlist available paper sizes\n".
    " -p size\tpaper size; something like 'letter', 'A4', 'legal', etc.  The default is\n".
    "   \t\t'" . DOMPDF_DEFAULT_PAPER_SIZE . "'\n".
    " -o orientation\teither 'portrait' or 'landscape'.  Default is 'portrait'.\n".
    " -b path\tset the 'document root' of the html_file.  Relative urls (for \n".
    "        \tstylesheets) are resolved using this directory.  Default is the \n".
    "        \tdirectory of html_file.\n".
    " -f file\tthe output filename.  Default is the input [html_file].pdf.\n".
    " -v     \tverbose: display html parsing warnings and file not found errors.\n".
    " -d     \tDebugging.  Produce of debugging output and complain about invalid\n".
    "        \tevery frame in the is printed to stdout.\n\n";
  
}

function getoptions() {

  $opts = array();

  if ( $_SERVER["argc"] == 1 )
    return $opts;
  
  $i = 0;
  while ($i < $_SERVER["argc"]) {

    switch ($_SERVER["argv"][$i]) {

    case "--help":
    case "-h":
      $opts["h"] = true;
      $i++;
      break;

    case "-l":
      $opts["l"] = true;
      $i++;
      break;
      
    case "-p":
      if ( !isset($_SERVER["argv"][$i+1]) )
        die("-p switch requires a size parameter\n");
      $opts["p"] = $_SERVER["argv"][$i+1];
      $i += 2;
      break;

    case "-o":
      if ( !isset($_SERVER["argv"][$i+1]) )
        die("-o switch requires an orientation parameter\n");
      $opts["o"] = $_SERVER["argv"][$i+1];
      $i += 2;
      break;

    case "-b":
      if ( !isset($_SERVER["argv"][$i+1]) )
        die("-b switch requires an path parameter\n");
      $opts["b"] = $_SERVER["argv"][$i+1];
      $i += 2;
      break;

    case "-f":
      if ( !isset($_SERVER["argv"][$i+1]) )
        die("-f switch requires an filename parameter\n");
      $opts["f"] = $_SERVER["argv"][$i+1];
      $i += 2;
      break;
      
    case "-v":
      $opts["v"] = true;
      $i++;
      break;

    case "-d":
      $opts["d"] = true;
      $i++;
      break;

    default:
      $opts["filename"] = $_SERVER["argv"][$i];
      $i++;
      break;
    }
    
  }
  return $opts;  
}

require_once("dompdf_config.inc.php");
global $_dompdf_show_warnings;
global $_dompdf_debug;

$old_limit = ini_set("memory_limit", "32M");

$sapi = php_sapi_name();

switch ( $sapi ) {

 case "cli":
   
  $opts = getoptions();
 
  if ( isset($opts["h"]) || (!isset($opts["filename"]) && !isset($opts["l"])) ) {
    usage();
    exit;
  }

  if ( isset($opts["l"]) ) {
    echo "\nUnderstood paper sizes:\n";
    
    foreach (array_keys(CPDF_Adapter::$PAPER_SIZES) as $size)
      echo "  " . strtoupper($size) . "\n";
    exit;
  }
  $file = $opts["filename"];
  
  if ( isset($opts["p"]) )
    $paper = $opts["p"];
  else
    $paper = DOMPDF_DEFAULT_PAPER_SIZE;

  if ( isset($opts["o"]) )
    $orientation = $opts["o"];
  else
    $orientation = "portrait";

  if ( isset($opts["b"]) )
    $base_path = $opts["b"];

  if ( isset($opts["f"]) )
    $outfile = $opts["f"];
  else {
    if ( $file == "-" )
      $outfile = "dompdf_out.pdf";
    else
      $outfile = str_ireplace(array(".html", ".htm", ".php"), "", $file) . ".pdf";
  }

  if ( isset($opts["v"]) ) 
    $_dompdf_show_warnings = true;

  if ( isset($opts["d"]) ) 
    $_dompdf_debug = true;

  $delete  = false;
  $save_file = true;
  
  break;

 default:

   if ( isset($_GET["input_file"]) )
     $file = rawurldecode($_GET["input_file"]);
   else
     throw new DOMPDF_Exception("An input file is required (i.e. input_file _GET variable).");
   
   if ( isset($_GET["paper"]) )
     $paper = rawurldecode($_GET["paper"]);
   else
     $paper = DOMPDF_DEFAULT_PAPER_SIZE;

   if ( isset($_GET["orientation"]) )
     $orientation = rawurldecode($_GET["orientation"]);
   else
     $orientation = "portrait";

   if ( isset($_GET["base_path"]) )
     $base_path = rawurldecode($_GET["base_path"]);

   if ( isset($_GET["output_file"]) )
     $outfile = rawurldecode($_GET["output_file"]);
   else
     $outfile = "dompdf_out.pdf";

   if ( isset($_GET["save_file"]) )
     $save_file = true;
   else
     $save_file = false;

   if ( isset($_GET["delete"]) )
     $delete = true;
   else
     $delete = false;

   break;
}

$dompdf = new DOMPDF();

if ( $file == "-" ) {
  $str = "";
  while ( !feof(STDIN) )
    $str .= fread(STDIN, 4096);

  $dompdf->load_html($str);

} else 
  $dompdf->load_html_file($file);

if ( isset($base_path) ) {
  $dompdf->set_base_path($base_path);
}

$dompdf->set_paper($paper, $orientation);

$dompdf->render();

if ( $save_file ) {
//   if ( !is_writable($outfile) ) 
//     throw new DOMPDF_Exception("'$outfile' is not writable.");

  file_put_contents($outfile, $dompdf->output());
  exit(0);
}

if ( !headers_sent() ) {
  $dompdf->stream($outfile);
  if ( $delete && dirname($file) === "/tmp")
    unlink($file);
}
?>