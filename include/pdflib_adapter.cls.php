<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: pdflib_adapter.cls.php,v $
 * Created on: 2005-02-28
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
 * - Clarify temp file name, optional debug output for temp file tracking
 */

/* $Id: pdflib_adapter.cls.php,v 1.26 2009-04-29 04:11:35 benjcarson Exp $ */

/**
 * PDF rendering interface
 *
 * PDFLib_Adapter provides a simple, stateless interface to the one
 * provided by PDFLib.
 *
 * Unless otherwise mentioned, all dimensions are in points (1/72 in).
 * The coordinate origin is in the top left corner and y values
 * increase downwards.
 *
 * See {@link http://www.pdflib.com/} for more complete documentation
 * on the underlying PDFlib functions.
 *
 * @package dompdf
 */
class PDFLib_Adapter implements Canvas {

  /**
   * Dimensions of paper sizes in points
   *
   * @var array;
   */
  static public $PAPER_SIZES = array(); // Set to
                                        // CPDF_Adapter::$PAPER_SIZES below.

  /**
   * Fudge factor to adjust reported font heights
   *
   * CPDF reports larger font heights than PDFLib.  This factor
   * adjusts the height reported by get_font_height().
   *
   * @var float
   */
  const FONT_HEIGHT_SCALE = 1.2;

  /**
   * Whether to create PDFs in memory or on disk
   *
   * @var bool
   */
  static $IN_MEMORY = true;

  /**
   * Instance of PDFLib class
   *
   * @var PDFlib
   */
  private $_pdf;

  /**
   * Name of temporary file used for PDFs created on disk
   *
   * @var string
   */
  private $_file;

  /**
   * PDF width, in points
   *
   * @var float
   */
  private $_width;

  /**
   * PDF height, in points
   *
   * @var height
   */
  private $_height;

  /**
   * Last fill colour used
   *
   * @var array
   */
  private $_last_fill_color;

  /**
   * Last stroke colour used
   *
   * @var array
   */
  private $_last_stroke_color;

  /**
   * Cache of image handles
   *
   * @var array
   */
  private $_imgs;

  /**
   * Cache of font handles
   *
   * @var array
   */
  private $_fonts;

  /**
   * List of objects (templates) to add to multiple pages
   *
   * @var array
   */
  private $_objs;

  /**
   * Current page number
   *
   * @var int
   */
  private $_page_number;

  /**
   * Total number of pages
   *
   * @var int
   */
  private $_page_count;

  /**
   * Text to display on every page
   *
   * @var array
   */
  private $_page_text;

  /**
   * Array of pages for accesing after rendering is initially complete
   *
   * @var array
   */
  private $_pages;

  /**
   * Class constructor
   *
   * @param mixed $paper The size of paper to use either a string (see {@link CPDF_Adapter::$PAPER_SIZES}) or
   *                     an array(xmin,ymin,xmax,ymax)
   * @param string $orientation The orientation of the document (either 'landscape' or 'portrait')
   */
  function __construct($paper = "letter", $orientation = "portrait") {
    if ( is_array($paper) )
      $size = $paper;
    else if ( isset(self::$PAPER_SIZES[mb_strtolower($paper)]) )
      $size = self::$PAPER_SIZES[mb_strtolower($paper)];
    else
      $size = self::$PAPER_SIZES["letter"];

    if ( mb_strtolower($orientation) == "landscape" ) {
      $a = $size[3];
      $size[3] = $size[2];
      $size[2] = $a;
    }
    $this->_width = $size[2] - $size[0];
    $this->_height= $size[3] - $size[1];

    $this->_pdf = new PDFLib();

	if ( defined("DOMPDF_PDFLIB_LICENSE") )
      $this->_pdf->set_parameter( "license", DOMPDF_PDFLIB_LICENSE);

	$this->_pdf->set_parameter("textformat", "utf8");
    $this->_pdf->set_parameter("fontwarning", "false");

    $this->_pdf->set_info("Creator", "DOMPDF Converter");

    // Silence pedantic warnings about missing TZ settings
    if ( function_exists("date_default_timezone_get") ) {
      $tz = @date_default_timezone_get();
      date_default_timezone_set("UTC");
      $this->_pdf->set_info("Date", date("Y-m-d"));
      date_default_timezone_set($tz);
    } else {
      $this->_pdf->set_info("Date", date("Y-m-d"));
    }

    if ( self::$IN_MEMORY )
      $this->_pdf->begin_document("","");
    else {
      $this->_file = tempnam(DOMPDF_TEMP_DIR, "libdompdf_pdf_").'.pdf';
      $this->_pdf->begin_document($this->_file,"");
    }

    $this->_pdf->begin_page_ext($this->_width, $this->_height, "");

    $this->_page_number = $this->_page_count = 1;
    $this->_page_text = array();

    $this->_imgs = array();
    $this->_fonts = array();
    $this->_objs = array();

    // Set up font paths
    $families = Font_Metrics::get_font_families();
    foreach ($families as $family => $files) {
      foreach ($files as $style => $file) {
        $face = basename($file);

        // Prefer ttfs to afms
        if ( file_exists($file.".ttf") ) {
          $outline = "$file.ttf";
          $afm = null;

        } else if ( file_exists($file .".TTF") ) {
          $outline = "$file.TTF";
          $afm = null;

        } else if ( file_exists($file . ".pfb") ) {
          $outline = "$file.pfb";

          if ( file_exists($file . ".afm") )
            $afm = "$file.afm";

        } else if ( file_exists($file . ".PFB") ) {
          $outline = "$file.PFB";
          if ( file_exists($file . ".AFM") )
            $afm = "$file.AFM";
        } else
          continue;

        $this->_pdf->set_parameter("FontOutline", "\{$face\}=\{$outline\}");
        if ( !is_null($afm) )
          $this->_pdf->set_parameter("FontAFM", "\{$face\}=\{$afm\}");
      }
    }
  }

  /**
   * Close the pdf
   */
  protected function _close() {
    $this->_place_objects();

    // Close all pages
    $this->_pdf->suspend_page("");
    for ($p = 1; $p <= $this->_page_count; $p++) {
      $this->_pdf->resume_page("pagenumber=$p");
      $this->_pdf->end_page_ext("");
    }

    $this->_pdf->end_document("");
  }


  /**
   * Returns the PDFLib instance
   *
   * @return PDFLib
   */
  function get_pdflib() { return $this->_pdf; }

  /**
   * Add meta information to the PDF
   *
   * @param string $label  label of the value (Creator, Producter, etc.)
   * @param string $value  the text to set
   */
  function add_info($label, $value) {
    $this->_pdf->set_info($label, $value);
  }
  
  /**
   * Opens a new 'object' (template in PDFLib-speak)
   *
   * While an object is open, all drawing actions are recorded to the
   * object instead of being drawn on the current page.  Objects can
   * be added later to a specific page or to several pages.
   *
   * The return value is an integer ID for the new object.
   *
   * @see PDFLib_Adapter::close_object()
   * @see PDFLib_Adapter::add_object()
   *
   * @return int
   */
  function open_object() {
    $this->_pdf->suspend_page("");
    $ret = $this->_pdf->begin_template($this->_width, $this->_height);
    $this->_pdf->save();
    $this->_objs[$ret] = array("start_page" => $this->_page_number);
    return $ret;
  }

  /**
   * Reopen an existing object (NOT IMPLEMENTED)
   *
   * PDFLib does not seem to support reopening templates.
   *
   * @param int $object the ID of a previously opened object
   */
  function reopen_object($object) {
    throw new DOMPDF_Exception("PDFLib does not support reopening objects.");
  }

  /**
   * Close the current template
   *
   * @see PDFLib_Adapter::open_object()
   */
  function close_object() {
    $this->_pdf->restore();
    $this->_pdf->end_template();
    $this->_pdf->resume_page("pagenumber=".$this->_page_number);
  }

  /**
   * Adds the specified object to the document
   *
   * $where can be one of:
   * - 'add' add to current page only
   * - 'all' add to every page from the current one onwards
   * - 'odd' add to all odd numbered pages from now on
   * - 'even' add to all even numbered pages from now on
   * - 'next' add the object to the next page only
   * - 'nextodd' add to all odd numbered pages from the next one
   * - 'nexteven' add to all even numbered pages from the next one
   *
   * @param int $object the object handle returned by open_object()
   * @param string $where
   */
  function add_object($object, $where = 'all') {

    if ( mb_strpos($where, "next") !== false ) {
      $this->_objs[$object]["start_page"]++;
      $where = str_replace("next", "", $where);
      if ( $where == "" )
        $where = "add";
    }

    $this->_objs[$object]["where"] = $where;
  }

  /**
   * Stops the specified template from appearing in the document.
   *
   * The object will stop being displayed on the page following the
   * current one.
   *
   * @param int $object
   */
  function stop_object($object) {

    if ( !isset($this->_objs[$object]) )
      return;

    $start = $this->_objs[$object]["start_page"];
    $where = $this->_objs[$object]["where"];

    // Place the object on this page if required
    if ( $this->_page_number >= $start &&
         (($this->_page_number % 2 == 0 && $where == "even") ||
          ($this->_page_number % 2 == 1 && $where == "odd") ||
          ($where == "all")) )
      $this->_pdf->fit_image($object,0,0,"");

    unset($this->_objs[$object]);
  }

  /**
   * Add all active objects to the current page
   */
  protected function _place_objects() {

    foreach ( $this->_objs as $obj => $props ) {
      $start = $props["start_page"];
      $where = $props["where"];

      // Place the object on this page if required
      if ( $this->_page_number >= $start &&
           (($this->_page_number % 2 == 0 && $where == "even") ||
            ($this->_page_number % 2 == 1 && $where == "odd") ||
            ($where == "all")) ) {
        $this->_pdf->fit_image($obj,0,0,"");
      }
    }

  }

  function get_width() { return $this->_width; }

  function get_height() { return $this->_height; }

  function get_page_number() { return $this->_page_number; }

  function get_page_count() { return $this->_page_count; }

  function set_page_number($num) { $this->_page_number = (int)$num; }

  function set_page_count($count) { $this->_page_count = (int)$count; }


  /**
   * Sets the line style
   *
   * @param float width
   * @param string corner
   * @param string join
   * @param array dash
   */
  protected function _set_line_style($width, $cap, $join, $dash) {

    if ( count($dash) == 1 )
      $dash[] = $dash[0];

    if ( count($dash) > 1 )
      $this->_pdf->setdashpattern("dasharray={" . join(" ", $dash) . "}");
    else
      $this->_pdf->setdash(0,0);

    switch ( $join ) {
    case "miter":
      $this->_pdf->setlinejoin(0);
      break;

    case "round":
      $this->_pdf->setlinejoin(1);
      break;

    case "bevel":
      $this->_pdf->setlinejoin(2);
      break;

    default:
      break;
    }

    switch ( $cap ) {
    case "butt":
      $this->_pdf->setlinecap(0);
      break;

    case "round":
      $this->_pdf->setlinecap(1);
      break;

    case "square":
      $this->_pdf->setlinecap(2);
      break;

    default:
      break;
    }

    $this->_pdf->setlinewidth($width);

  }

  /**
   * Sets the line color
   *
   * @param array $color array(r,g,b)
   */
  protected function _set_stroke_color($color) {
    if($this->_last_stroke_color == $color)
    	return;

    $this->_last_stroke_color = $color;

    list($r,$g,$b) = $color;
    $this->_pdf->setcolor("stroke", "rgb", $r, $g, $b, 0);
  }

  /**
   * Sets the fill color
   *
   * @param array $color array(r,g,b)
   */
  protected function _set_fill_color($color) {
    if($this->_last_fill_color == $color)
    	return;

    $this->_last_fill_color = $color;

    list($r,$g,$b) = $color;
    $this->_pdf->setcolor("fill", "rgb", $r, $g, $b, 0);
  }

  /**
   * Loads a specific font and stores the corresponding descriptor.
   *
   * @param string $font
   * @return int the font descriptor for the font
   */
  protected function _load_font($font, $encoding = null, $options = "") {

    // Check if the font is a native PDF font
    // Embed non-native fonts
    $native_fonts = array("courier", "courier-bold", "courier-oblique", "courier-boldoblique",
                          "helvetica", "helvetica-bold", "helvetica-oblique", "helvetica-boldoblique",
                          "times-roman", "times-bold", "times-italic", "times-bolditalic",
                          "symbol", "zapfdinbats");

    $test = strtolower(basename($font));
    if ( in_array($test, $native_fonts) ) {
      $font = basename($font);

    } else {
      // Embed non-native fonts
      $options .= " embedding=true";
    }

    if ( is_null($encoding) ) {

      // Unicode encoding is only available for the commerical
      // version of PDFlib and not PDFlib-Lite
      if ( defined("DOMPDF_PDFLIB_LICENSE") )
        $encoding = "unicode";
      else
        $encoding = "auto";

    }

    $key = $font .":". $encoding .":". $options;

    if ( isset($this->_fonts[$key]) )
      return $this->_fonts[$key];

    else {

      $this->_fonts[$key] = $this->_pdf->load_font($font, $encoding, $options);
      return $this->_fonts[$key];

    }

  }

  /**
   * Remaps y coords from 4th to 1st quadrant
   *
   * @param float $y
   * @return float
   */
  protected function y($y) { return $this->_height - $y; }

  //........................................................................

  function line($x1, $y1, $x2, $y2, $color, $width, $style = null) {
    $this->_set_line_style($width, "butt", "", $style);
    $this->_set_stroke_color($color);

    $y1 = $this->y($y1);
    $y2 = $this->y($y2);

    $this->_pdf->moveto($x1,$y1);
    $this->_pdf->lineto($x2, $y2);
    $this->_pdf->stroke();
  }

  //........................................................................

  function rectangle($x1, $y1, $w, $h, $color, $width, $style = null) {
    $this->_set_stroke_color($color);
    $this->_set_line_style($width, "square", "miter", $style);

    $y1 = $this->y($y1) - $h;

    $this->_pdf->rect($x1, $y1, $w, $h);
    $this->_pdf->stroke();
  }

  //........................................................................

  function filled_rectangle($x1, $y1, $w, $h, $color) {
    $this->_set_fill_color($color);

    $y1 = $this->y($y1) - $h;

    $this->_pdf->rect($x1, $y1, $w, $h);
    $this->_pdf->fill();
  }

  //........................................................................

  function polygon($points, $color, $width = null, $style = null, $fill = false) {

    $this->_set_fill_color($color);
    $this->_set_stroke_color($color);

    if ( !$fill && isset($width) )
      $this->_set_line_style($width, "square", "miter", $style);

    $y = $this->y(array_pop($points));
    $x = array_pop($points);
    $this->_pdf->moveto($x,$y);

    while (count($points) > 1) {
      $y = $this->y(array_pop($points));
      $x = array_pop($points);
      $this->_pdf->lineto($x,$y);
    }

    if ( $fill )
      $this->_pdf->fill();
    else
      $this->_pdf->closepath_stroke();
  }

  //........................................................................

  function circle($x, $y, $r, $color, $width = null, $style = null, $fill = false) {

    $this->_set_fill_color($color);
    $this->_set_stroke_color($color);

    if ( !$fill && isset($width) )
      $this->_set_line_style($width, "round", "round", $style);

    $y = $this->y($y);

    $this->_pdf->circle($x, $y, $r);

    if ( $fill )
      $this->_pdf->fill();
    else
      $this->_pdf->stroke();

  }

  //........................................................................

  function image($img_url, $img_type, $x, $y, $w, $h) {
    $w = (int)$w;
    $h = (int)$h;

    $img_type = strtolower($img_type);

    if ( $img_type == "jpg" )
      $img_type = "jpeg";

    if ( isset($this->_imgs[$img_url]) )
      $img = $this->_imgs[$img_url];

    else {

      $img = $this->_imgs[$img_url] = $this->_pdf->load_image($img_type, $img_url, "");
    }

    $y = $this->y($y) - $h;
    $this->_pdf->fit_image($img, $x, $y, 'boxsize={'. "$w $h" .'} fitmethod=entire');

  }

  //........................................................................

  function text($x, $y, $text, $font, $size, $color = array(0,0,0), $adjust = 0, $angle = 0) {
    $fh = $this->_load_font($font);

    $this->_pdf->setfont($fh, $size);
    $this->_set_fill_color($color);

    $y = $this->y($y) - Font_Metrics::get_font_height($font, $size);

    $adjust = (float)$adjust;
    $angle = -(float)$angle;

    //$this->_pdf->fit_textline(utf8_decode($text), $x, $y, "rotate=$angle wordspacing=$adjust");
    $this->_pdf->fit_textline($text, $x, $y, "rotate=$angle wordspacing=$adjust");

  }

  //........................................................................

  /**
   * Add a named destination (similar to <a name="foo">...</a> in html)
   *
   * @param string $anchorname The name of the named destination
   */
  function add_named_dest($anchorname) {
    $this->_pdf->add_nameddest($anchorname,"");
  }

  //........................................................................

  /**
   * Add a link to the pdf
   *
   * @param string $url The url to link to
   * @param float  $x   The x position of the link
   * @param float  $y   The y position of the link
   * @param float  $width   The width of the link
   * @param float  $height   The height of the link
   */
  function add_link($url, $x, $y, $width, $height) {

    $y = $this->y($y) - $height;
    if ( strpos($url, '#') === 0 ) {
      // Local link
      $name = substr($url,1);
      if ( $name )
        $this->_pdf->create_annotation($x, $y, $x + $width, $y + $height, 'Link', "contents={$url} destname=". substr($url,1) . " linewidth=0");
    } else {

      list($proto, $host, $path, $file) = explode_url($url);

      if ( $proto == "" || $proto == "file://" )
        return; // Local links are not allowed
      $url = build_url($proto, $host, $path, $file);
      $url = '{' . rawurldecode($url) . '}';
      
      $action = $this->_pdf->create_action("URI", "url=" . $url);
      $this->_pdf->create_annotation($x, $y, $x + $width, $y + $height, 'Link', "contents={$url} action={activate=$action} linewidth=0");
    }
  }

  //........................................................................

  function get_text_width($text, $font, $size, $spacing = 0) {
    $fh = $this->_load_font($font);

    // Determine the additional width due to extra spacing
    $num_spaces = mb_substr_count($text," ");
    $delta = $spacing * $num_spaces;
    return $this->_pdf->stringwidth($text, $fh, $size) + $delta;
  }

  //........................................................................

  function get_font_height($font, $size) {

    $fh = $this->_load_font($font);

    $this->_pdf->setfont($fh, $size);

    $asc = $this->_pdf->get_value("ascender", $fh);
    $desc = $this->_pdf->get_value("descender", $fh);

    // $desc is usually < 0,
    return self::FONT_HEIGHT_SCALE * $size * ($asc - $desc);
  }

  //........................................................................

  /**
   * Writes text at the specified x and y coordinates on every page
   *
   * The strings '{PAGE_NUM}' and '{PAGE_COUNT}' are automatically replaced
   * with their current values.
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
   *
   * @param float $x
   * @param float $y
   * @param string $text the text to write
   * @param string $font the font file to use
   * @param float $size the font size, in points
   * @param array $color
   * @param float $adjust word spacing adjustment
   * @param float $angle angle to write the text at, measured CW starting from the x-axis
   */
  function page_text($x, $y, $text, $font, $size, $color = array(0,0,0),
                     $adjust = 0, $angle = 0,  $blend = "Normal", $opacity = 1.0) {
    $_t = "text";
    $this->_page_text[] = compact("_t", "x", "y", "text", "font", "size", "color", "adjust", "angle");
  }

  //........................................................................

  /**
   * Processes a script on every page
   * 
   * The variables $pdf, $PAGE_NUM, and $PAGE_COUNT are available.
   * 
   * This function can be used to add page numbers to all pages
   * after the first one, for example.
   *
   * @param string $code the script code
   * @param string $type the language type for script
   */
  function page_script($code, $type = "text/php") {
    $_t = "script";
    $this->_page_text[] = compact("_t", "code", "type");
  }

  //........................................................................

  function new_page() {

    // Add objects to the current page
    $this->_place_objects();

    $this->_pdf->suspend_page("");
    $this->_pdf->begin_page_ext($this->_width, $this->_height, "");
    $this->_page_number = ++$this->_page_count;

  }

  //........................................................................

  /**
   * Add text to each page after rendering is complete
   */
  protected function _add_page_text() {

    if ( !count($this->_page_text) )
      return;

    $this->_pdf->suspend_page("");

    for ($p = 1; $p <= $this->_page_count; $p++) {
      $this->_pdf->resume_page("pagenumber=$p");

      foreach ($this->_page_text as $pt) {
        extract($pt);

        switch ($_t) {

        case "text":
          $text = str_replace(array("{PAGE_NUM}","{PAGE_COUNT}"),
                              array($p, $this->_page_count), $text);
          $this->text($x, $y, $text, $font, $size, $color, $adjust, $angle);
          break;

        case "script":
          if (!$eval) {
            $eval = new PHP_Evaluator($this);  
          }
          $eval->evaluate($code, array('PAGE_NUM' => $p, 'PAGE_COUNT' => $this->_page_count));
          break;
        }
      }

      $this->_pdf->suspend_page("");
    }

    $this->_pdf->resume_page("pagenumber=".$this->_page_number);
  }

  //........................................................................

  function stream($filename, $options = null) {

    // Add page text
    $this->_add_page_text();

    if ( isset($options["compress"]) && $options["compress"] != 1 )
      $this->_pdf->set_value("compress", 0);
    else
      $this->_pdf->set_value("compress", 6);

    $this->_close();

    if ( self::$IN_MEMORY ) {
      $data = $this->_pdf->get_buffer();
      $size = strlen($data);

    } else
      $size = filesize($this->_file);


    $filename = str_replace(array("\n","'"),"", $filename);
    $attach = (isset($options["Attachment"]) && $options["Attachment"]) ? "attachment" : "inline";

    header("Cache-Control: private");
    header("Content-type: application/pdf");
    header("Content-Disposition: $attach; filename=\"$filename\"");

    //header("Content-length: " . $size);

    if ( self::$IN_MEMORY )
      echo $data;

    else {

      // Chunked readfile()
      $chunk = (1 << 21); // 2 MB
      $fh = fopen($this->_file, "rb");
      if ( !$fh )
        throw new DOMPDF_Exception("Unable to load temporary PDF file: " . $this->_file);

      while ( !feof($fh) )
        echo fread($fh,$chunk);
      fclose($fh);

      //debugpng
      if (DEBUGPNG) print '[pdflib stream unlink '.$this->_file.']';
      if (!DEBUGKEEPTEMP)

      unlink($this->_file);
      $this->_file = null;
    }

    flush();


  }

  //........................................................................

  function output($options = null) {

    // Add page text
    $this->_add_page_text();

    if ( isset($options["compress"]) && $options["compress"] != 1 )
      $this->_pdf->set_value("compress", 0);
    else
      $this->_pdf->set_value("compress", 6);

    $this->_close();

    if ( self::$IN_MEMORY )
      $data = $this->_pdf->get_buffer();

    else {
      $data = file_get_contents($this->_file);

      //debugpng
      if (DEBUGPNG) print '[pdflib output unlink '.$this->_file.']';
      if (!DEBUGKEEPTEMP)

      unlink($this->_file);
      $this->_file = null;
    }

    return $data;
  }
}

// Workaround for idiotic limitation on statics...
PDFLib_Adapter::$PAPER_SIZES = CPDF_Adapter::$PAPER_SIZES;
?>