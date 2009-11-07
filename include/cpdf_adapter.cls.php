<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: cpdf_adapter.cls.php,v $
 * Created on: 2004-08-04
 * Modified on: 2008-01-05
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 * Portions copyright (c) 2008 - Orion Richardson <orionr@yahoo.com>
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
 * @contributor Orion Richardson <orionr@yahoo.com>
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf
 * @version 0.5.1
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 0.5.1.htischer.20090507
 * - On gif to png conversion tmp file creation, clarify tmp name and add to tmp deletion list only on success
 * - On gif to png conversion, when available add direct from gd without tmp file, skip image load if already cached.
 *   to safe CPU time and memory
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version dompdf_trunk_with_helmut_mods.20090524
 * - Pass temp and fontcache folders to Cpdf, to making Cpdf independent from dompdf
 * @version dompdf_trunk_with_helmut_mods.20090528
 * - fix text position according to glyph baseline to match background rectangle
 */

/* $Id$ */

// FIXME: Need to sanity check inputs to this class
require_once(DOMPDF_LIB_DIR . "/class.pdf.php");

/**
 * PDF rendering interface
 *
 * CPDF_Adapter provides a simple stateless interface to the stateful one
 * provided by the Cpdf class.
 *
 * Unless otherwise mentioned, all dimensions are in points (1/72 in).  The
 * coordinate origin is in the top left corner, and y values increase
 * downwards.
 *
 * See {@link http://www.ros.co.nz/pdf/} for more complete documentation
 * on the underlying {@link Cpdf} class.
 *
 * @package dompdf
 */
class CPDF_Adapter implements Canvas {

  /**
   * Dimensions of paper sizes in points
   *
   * @var array;
   */
  static $PAPER_SIZES = array("4a0" => array(0,0,4767.87,6740.79),
                              "2a0" => array(0,0,3370.39,4767.87),
                              "a0" => array(0,0,2383.94,3370.39),
                              "a1" => array(0,0,1683.78,2383.94),
                              "a2" => array(0,0,1190.55,1683.78),
                              "a3" => array(0,0,841.89,1190.55),
                              "a4" => array(0,0,595.28,841.89),
                              "a5" => array(0,0,419.53,595.28),
                              "a6" => array(0,0,297.64,419.53),
                              "a7" => array(0,0,209.76,297.64),
                              "a8" => array(0,0,147.40,209.76),
                              "a9" => array(0,0,104.88,147.40),
                              "a10" => array(0,0,73.70,104.88),
                              "b0" => array(0,0,2834.65,4008.19),
                              "b1" => array(0,0,2004.09,2834.65),
                              "b2" => array(0,0,1417.32,2004.09),
                              "b3" => array(0,0,1000.63,1417.32),
                              "b4" => array(0,0,708.66,1000.63),
                              "b5" => array(0,0,498.90,708.66),
                              "b6" => array(0,0,354.33,498.90),
                              "b7" => array(0,0,249.45,354.33),
                              "b8" => array(0,0,175.75,249.45),
                              "b9" => array(0,0,124.72,175.75),
                              "b10" => array(0,0,87.87,124.72),
                              "c0" => array(0,0,2599.37,3676.54),
                              "c1" => array(0,0,1836.85,2599.37),
                              "c2" => array(0,0,1298.27,1836.85),
                              "c3" => array(0,0,918.43,1298.27),
                              "c4" => array(0,0,649.13,918.43),
                              "c5" => array(0,0,459.21,649.13),
                              "c6" => array(0,0,323.15,459.21),
                              "c7" => array(0,0,229.61,323.15),
                              "c8" => array(0,0,161.57,229.61),
                              "c9" => array(0,0,113.39,161.57),
                              "c10" => array(0,0,79.37,113.39),
                              "ra0" => array(0,0,2437.80,3458.27),
                              "ra1" => array(0,0,1729.13,2437.80),
                              "ra2" => array(0,0,1218.90,1729.13),
                              "ra3" => array(0,0,864.57,1218.90),
                              "ra4" => array(0,0,609.45,864.57),
                              "sra0" => array(0,0,2551.18,3628.35),
                              "sra1" => array(0,0,1814.17,2551.18),
                              "sra2" => array(0,0,1275.59,1814.17),
                              "sra3" => array(0,0,907.09,1275.59),
                              "sra4" => array(0,0,637.80,907.09),
                              "letter" => array(0,0,612.00,792.00),
                              "legal" => array(0,0,612.00,1008.00),
                              "ledger" => array(0,0,1224.00, 792.00),
                              "tabloid" => array(0,0,792.00, 1224.00),
                              "executive" => array(0,0,521.86,756.00),
                              "folio" => array(0,0,612.00,936.00),
                              "commerical #10 envelope" => array(0,0,684,297),
                              "catalog #10 1/2 envelope" => array(0,0,648,864),
                              "8.5x11" => array(0,0,612.00,792.00),
                              "8.5x14" => array(0,0,612.00,1008.0),
                              "11x17"  => array(0,0,792.00, 1224.00));


  /**
   * Instance of Cpdf class
   *
   * @var Cpdf
   */
  private $_pdf;

  /**
   * PDF width, in points
   *
   * @var float
   */
  private $_width;

  /**
   * PDF height, in points
   *
   * @var float;
   */
  private $_height;

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
   * Array of temporary cached images to be deleted when processing is complete
   *
   * @var array
   */
  private $_image_cache;
  
  /**
   * Class constructor
   *
   * @param mixed  $paper  The size of paper to use in this PDF ({@link CPDF_Adapter::$PAPER_SIZES})
   * @param string $orientation The orienation of the document (either 'landscape' or 'portrait')
   */
  function __construct($paper = "letter", $orientation = "portrait") {

    if ( is_array($paper) )
      $size = $paper;
    else if ( isset(self::$PAPER_SIZES[mb_strtolower($paper)]) )
      $size = self::$PAPER_SIZES[mb_strtolower($paper)];
    else
      $size = self::$PAPER_SIZES["letter"];

    if ( mb_strtolower($orientation) === "landscape" ) {
      $a = $size[3];
      $size[3] = $size[2];
      $size[2] = $a;
    }
    
    $this->_pdf = new Cpdf($size, DOMPDF_UNICODE_ENABLED, DOMPDF_FONT_CACHE, DOMPDF_TEMP_DIR);
    $this->_pdf->addInfo("Creator", "dompdf");

    // Silence pedantic warnings about missing TZ settings
    if ( function_exists("date_default_timezone_get") ) {
      $tz = @date_default_timezone_get();
      date_default_timezone_set("UTC");
      $this->_pdf->addInfo("CreationDate", date("Y-m-d"));
      date_default_timezone_set($tz);

    } else {
      $this->_pdf->addInfo("CreationDate", date("Y-m-d"));
    }

    $this->_width = $size[2] - $size[0];
    $this->_height= $size[3] - $size[1];
    $this->_pdf->openHere('Fit');
    
    $this->_page_number = $this->_page_count = 1;
    $this->_page_text = array();

    $this->_pages = array($this->_pdf->getFirstPageId());

    $this->_image_cache = array();
  }

  /**
   * Class destructor
   *
   * Deletes all temporary image files
   */
  function __destruct() {
    foreach ($this->_image_cache as $img) {
      //debugpng
      if (DEBUGPNG) print '[__destruct unlink '.$img.']';
      if (!DEBUGKEEPTEMP)
        unlink($img);
    }
  }
  
  /**
   * Returns the Cpdf instance
   *
   * @return Cpdf
   */
  function get_cpdf() { return $this->_pdf; }

  /**
   * Add meta information to the PDF
   *
   * @param string $label  label of the value (Creator, Producter, etc.)
   * @param string $value  the text to set
   */
  function add_info($label, $value) {
    $this->_pdf->addInfo($label, $value);
  }

  /**
   * Opens a new 'object'
   *
   * While an object is open, all drawing actions are recored in the object,
   * as opposed to being drawn on the current page.  Objects can be added
   * later to a specific page or to several pages.
   *
   * The return value is an integer ID for the new object.
   *
   * @see CPDF_Adapter::close_object()
   * @see CPDF_Adapter::add_object()
   *
   * @return int
   */
  function open_object() {
    $ret = $this->_pdf->openObject();
    $this->_pdf->saveState();
    return $ret;
  }

  /**
   * Reopens an existing 'object'
   *
   * @see CPDF_Adapter::open_object()
   * @param int $object  the ID of a previously opened object
   */
  function reopen_object($object) {
    $this->_pdf->reopenObject($object);
    $this->_pdf->saveState();
  }

  /**
   * Closes the current 'object'
   *
   * @see CPDF_Adapter::open_object()
   */
  function close_object() {
    $this->_pdf->restoreState();
    $this->_pdf->closeObject();
  }

  /**
   * Adds a specified 'object' to the document
   *
   * $object int specifying an object created with {@link
   * CPDF_Adapter::open_object()}.  $where can be one of:
   * - 'add' add to current page only
   * - 'all' add to every page from the current one onwards
   * - 'odd' add to all odd numbered pages from now on
   * - 'even' add to all even numbered pages from now on
   * - 'next' add the object to the next page only
   * - 'nextodd' add to all odd numbered pages from the next one
   * - 'nexteven' add to all even numbered pages from the next one
   *
   * @see Cpdf::addObject()
   *
   * @param int $object
   * @param string $where
   */
  function add_object($object, $where = 'all') {
    $this->_pdf->addObject($object, $where);
  }

  /**
   * Stops the specified 'object' from appearing in the document.
   *
   * The object will stop being displayed on the page following the current
   * one.
   *
   * @param int $object
   */
  function stop_object($object) {
    $this->_pdf->stopObject($object);
  }

  /**
   * @access private
   */
  function serialize_object($id) {
    // Serialize the pdf object's current state for retrieval later
    return $this->_pdf->serializeObject($id);
  }

  /**
   * @access private
   */
  function reopen_serialized_object($obj) {
    return $this->_pdf->restoreSerializedObject($obj);
  }
    
  //........................................................................

  /**
   * Returns the PDF's width in points
   * @return float
   */
  function get_width() { return $this->_width; }

  /**
   * Returns the PDF's height in points
   * @return float
   */
  function get_height() { return $this->_height; }

  /**
   * Returns the current page number
   * @return int
   */
  function get_page_number() { return $this->_page_number; }

  /**
   * Returns the total number of pages in the document
   * @return int
   */
  function get_page_count() { return $this->_page_count; }

  /**
   * Sets the current page number
   *
   * @param int $num
   */
  function set_page_number($num) { $this->_page_number = $num; }

  /**
   * Sets the page count
   *
   * @param int $count
   */
  function set_page_count($count) {  $this->_page_count = $count; }
    
  /**
   * Sets the stroke colour
   *
   * See {@link Style::set_colour()} for the format of the color array.
   * @param array $color
   */
  protected function _set_stroke_color($color) {
    list($r, $g, $b) = $color;
    $this->_pdf->setStrokeColor($r, $g, $b);
  }
  
  /**
   * Sets the fill colour
   *
   * See {@link Style::set_colour()} for the format of the colour array.
   * @param array $color
   */
  protected function _set_fill_color($color) {
    list($r, $g, $b) = $color;
    $this->_pdf->setColor($r, $g, $b);
  }

  /**
   * Sets line transparency
   * @see Cpdf::setLineTransparency()
   *
   * Valid blend modes are (case-sensitive):
   *
   * Normal, Multiply, Screen, Overlay, Darken, Lighten,
   * ColorDodge, ColorBurn, HardLight, SoftLight, Difference,
   * Exclusion
   *
   * @param string $mode the blending mode to use
   * @param float $opacity 0.0 fully transparent, 1.0 fully opaque
   */
  protected function _set_line_transparency($mode, $opacity) {
    $this->_pdf->setLineTransparency($mode, $opacity);
  }
  
  /**
   * Sets fill transparency
   * @see Cpdf::setFillTransparency()
   *
   * Valid blend modes are (case-sensitive):
   *
   * Normal, Multiply, Screen, Overlay, Darken, Lighten,
   * ColorDogde, ColorBurn, HardLight, SoftLight, Difference,
   * Exclusion
   *
   * @param string $mode the blending mode to use
   * @param float $opacity 0.0 fully transparent, 1.0 fully opaque
   */
  protected function _set_fill_transparency($mode, $opacity) {
    $this->_pdf->setFillTransparency($mode, $opacity);
  }

  /**
   * Sets the line style
   *
   * @see Cpdf::setLineStyle()
   *
   * @param float width
   * @param string cap
   * @param string join
   * @param array dash
   */
  protected function _set_line_style($width, $cap, $join, $dash) {
    $this->_pdf->setLineStyle($width, $cap, $join, $dash);
  }
  
  //........................................................................

  
  /**
   * Remaps y coords from 4th to 1st quadrant
   *
   * @param float $y
   * @return float
   */
  protected function y($y) { return $this->_height - $y; }

  // Canvas implementation

  function line($x1, $y1, $x2, $y2, $color, $width, $style = array(),
                $blend = "Normal", $opacity = 1.0) {
    //pre_r(compact("x1", "y1", "x2", "y2", "color", "width", "style"));

    $this->_set_stroke_color($color);
    $this->_set_line_style($width, "butt", "", $style);
    $this->_set_line_transparency($blend, $opacity);
    
    $this->_pdf->line($x1, $this->y($y1),
                      $x2, $this->y($y2));
  }
                              
  //........................................................................

  /**
   * Convert a GIF image to a PNG image
   *
   * @return string The url of the newly converted image
   */
  protected function _convert_gif_to_png($image_url) {
    
    if ( !function_exists("imagecreatefromgif") ) {
      throw new DOMPDF_Exception("Function imagecreatefromgif() not found.  Cannot convert gif image: $image_url.  Please install the image PHP extension.");
    }

    $old_err = set_error_handler("record_warnings");
    $im = imagecreatefromgif($image_url);

    if ( $im ) {
      imageinterlace($im, 0);

      $filename = tempnam(DOMPDF_TEMP_DIR, "gifdompdf_img_").'.png';
      $this->_image_cache[] = $filename;

      imagepng($im, $filename);

    } else {
      $filename = DOMPDF_LIB_DIR . "/res/broken_image.png";

    }

    restore_error_handler();

    return $filename;
    
  }

  function rectangle($x1, $y1, $w, $h, $color, $width, $style = array(),
                     $blend = "Normal", $opacity = 1.0) {

    $this->_set_stroke_color($color);
    $this->_set_line_style($width, "square", "miter", $style);
    $this->_set_line_transparency($blend, $opacity);
    
    $this->_pdf->rectangle($x1, $this->y($y1) - $h, $w, $h);
  }

  //........................................................................
  
  function filled_rectangle($x1, $y1, $w, $h, $color, $blend = "Normal", $opacity = 1.0) {

    $this->_set_fill_color($color);
    $this->_set_line_style(1, "square", "miter", array());
    $this->_set_line_transparency($blend, $opacity);
    $this->_set_fill_transparency($blend, $opacity);
    
    $this->_pdf->filledRectangle($x1, $this->y($y1) - $h, $w, $h);
  }

  //........................................................................

  function polygon($points, $color, $width = null, $style = array(),
                   $fill = false, $blend = "Normal", $opacity = 1.0) {

    $this->_set_fill_color($color);
    $this->_set_stroke_color($color);

    $this->_set_line_transparency($blend, $opacity);
    $this->_set_fill_transparency($blend, $opacity);
    
    if ( !$fill && isset($width) )
      $this->_set_line_style($width, "square", "miter", $style);
    
    // Adjust y values
    for ( $i = 1; $i < count($points); $i += 2)
      $points[$i] = $this->y($points[$i]);
    
    $this->_pdf->polygon($points, count($points) / 2, $fill);
  }

  //........................................................................

  function circle($x, $y, $r1, $color, $width = null, $style = null,
                  $fill = false, $blend = "Normal", $opacity = 1.0) {

    $this->_set_fill_color($color);
    $this->_set_stroke_color($color);
    
    $this->_set_line_transparency($blend, $opacity);
    $this->_set_fill_transparency($blend, $opacity);

    if ( !$fill && isset($width) )
      $this->_set_line_style($width, "round", "round", $style);

    $this->_pdf->ellipse($x, $this->y($y), $r1, 0, 0, 8, 0, 360, 1, $fill);
  }
  
  //........................................................................

  function image($img_url, $img_type, $x, $y, $w, $h) {
    //debugpng
    if (DEBUGPNG) print '[image:'.$img_url.'|'.$img_type.']';

    $img_type = mb_strtolower($img_type);

    switch ($img_type) {
    case "jpeg":
    case "jpg":
      //debugpng
      if (DEBUGPNG)  print '!!!jpg!!!';

      $this->_pdf->addJpegFromFile($img_url, $x, $this->y($y) - $h, $w, $h);
      break;

    case "png":
      //debugpng
      if (DEBUGPNG)  print '!!!png!!!';

      $this->_pdf->addPngFromFile($img_url, $x, $this->y($y) - $h, $w, $h);
      break;

    case "gif":
      // Convert gifs to pngs
      //DEBUG_IMG_TEMP
      //if (0) {
      if ( method_exists( $this->_pdf, "addImagePng" ) ) {
        //debugpng
        if (DEBUGPNG)  print '!!!gif addImagePng!!!';

      	//If optimization to direct png creation from gd object is available,
        //don't create temp file, but place gd object directly into the pdf
	    if ( method_exists( $this->_pdf, "image_iscached" ) &&
	         $this->_pdf->image_iscached($img_url) ) {
	      //If same image has occured already before, no need to load because
	      //duplicate will anyway be eliminated.
	      $img = null;
	    } else {
    	  $img = @imagecreatefromgif($img_url);
    	  if (!$img) {
      	    return;
    	  }
    	  imageinterlace($img, 0);
    	}
    	$this->_pdf->addImagePng($img_url, $x, $this->y($y) - $h, $w, $h, $img);
      } else {
        //debugpng
        if (DEBUGPNG)  print '!!!gif addPngFromFile!!!';
        $img_url = $this->_convert_gif_to_png($img_url);
        $this->_pdf->addPngFromFile($img_url, $x, $this->y($y) - $h, $w, $h);
      }
      break;

    default:
      //debugpng
      if (DEBUGPNG) print '!!!unknown!!!';
      break;
    }
    
    return;
  }

  //........................................................................

  function text($x, $y, $text, $font, $size, $color = array(0,0,0),
                $adjust = 0, $angle = 0, $blend = "Normal", $opacity = 1.0) {

    list($r, $g, $b) = $color;
    $this->_pdf->setColor($r, $g, $b);

    $this->_set_line_transparency($blend, $opacity);
    $this->_set_fill_transparency($blend, $opacity);
    $font .= ".afm";
    
    $this->_pdf->selectFont($font);
    
    //Font_Metrics::get_font_height($font, $size) ==
    //$this->get_font_height($font, $size) ==
    //$this->_pdf->selectFont($font),$this->_pdf->getFontHeight($size)
    //- FontBBoxheight+FontHeightOffset, scaled to $size, in pt
    //$this->_pdf->getFontDescender($size)
    //- Descender scaled to size
    //
    //$this->_pdf->fonts[$this->_pdf->currentFont] sizes:
    //['FontBBox'][0] left, ['FontBBox'][1] bottom, ['FontBBox'][2] right, ['FontBBox'][3] top
    //Maximum extent of all glyphs of the font from the baseline point
    //['Ascender'] maximum height above baseline except accents
    //['Descender'] maximum depth below baseline, negative number means below baseline
    //['FontHeightOffset'] manual enhancement of .afm files to trim windows fonts. currently not used.
    //Values are in 1/1000 pt for a font size of 1 pt
    //
    //['FontBBox'][1] should be close to ['Descender']
    //['FontBBox'][3] should be close to ['Ascender']+Accents
    //in practice, FontBBox values are a little bigger
    //
    //The text position is referenced to the baseline, not to the lower corner of the FontBBox,
    //for what the left,top corner is given.
    //FontBBox spans also the background box for the text.
    //If the lower corner would be used as reference point, the Descents of the glyphs would
    //hang over the background box border.
    //Therefore compensate only the extent above the Baseline.
    //
    //print '<pre>['.$font.','.$size.','.$this->_pdf->getFontHeight($size).','.$this->_pdf->getFontDescender($size).','.$this->_pdf->fonts[$this->_pdf->currentFont]['FontBBox'][3].','.$this->_pdf->fonts[$this->_pdf->currentFont]['FontBBox'][1].','.$this->_pdf->fonts[$this->_pdf->currentFont]['FontHeightOffset'].','.$this->_pdf->fonts[$this->_pdf->currentFont]['Ascender'].','.$this->_pdf->fonts[$this->_pdf->currentFont]['Descender'].']</pre>';
    //
    //$this->_pdf->addText($x, $this->y($y) - Font_Metrics::get_font_height($font, $size), $size, $text, $angle, $adjust);
	//$this->_pdf->addText($x, $this->y($y) - $size, $size, $text, $angle, $adjust);
	//$this->_pdf->addText($x, $this->y($y) - $this->_pdf->getFontHeight($size)-$this->_pdf->getFontDescender($size), $size, $text, $angle, $adjust);
	$this->_pdf->addText($x, $this->y($y) - ($this->_pdf->fonts[$this->_pdf->currentFont]['FontBBox'][3]*$size)/1000, $size, $text, $angle, $adjust);
  }

  //........................................................................

  /**
   * Add a named destination (similar to <a name="foo">...</a> in html)
   *
   * @param string $anchorname The name of the named destination
   */
  function add_named_dest($anchorname) {
    $this->_pdf->addDestination($anchorname,"Fit");
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
        $this->_pdf->addInternalLink($name, $x, $y, $x + $width, $y + $height);

    } else {
      $this->_pdf->addLink(rawurldecode($url), $x, $y, $x + $width, $y + $height);
    }
    
  }

  //........................................................................

  function get_text_width($text, $font, $size, $spacing = 0) {
    $this->_pdf->selectFont($font);
    $ascii = utf8_decode($text);
//     // Hack for &nbsp;
//     $ascii = str_replace("\xA0", " ", $ascii);

    return $this->_pdf->getTextWidth($size, $ascii, $spacing);
  }

  //........................................................................

  function get_font_height($font, $size) {
    $this->_pdf->selectFont($font);
    return $this->_pdf->getFontHeight($size);
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
                     $adjust = 0, $angle = 0) {
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
    $this->_page_count++;

    $ret = $this->_pdf->newPage();
    $this->_pages[] = $ret;
    return $ret;
  }
  
  //........................................................................

  /**
   * Add text to each page after rendering is complete
   */
  protected function _add_page_text() {
    
    if ( !count($this->_page_text) )
      return;

    $page_number = 1;
    $eval = null;

    foreach ($this->_pages as $pid) {
      $this->reopen_object($pid);

      foreach ($this->_page_text as $pt) {
        extract($pt);

        switch ($_t) {
            
        case "text":
        $text = str_replace(array("{PAGE_NUM}","{PAGE_COUNT}"),
                            array($page_number, $this->_page_count), $text);
        $this->text($x, $y, $text, $font, $size, $color, $adjust, $angle);
          break;
          
        case "script":
          if (!$eval) {
            $eval = new PHP_Evaluator($this);
          }
          $eval->evaluate($code, array('PAGE_NUM' => $page_number, 'PAGE_COUNT' => $this->_page_count));
          break;
        }
      }

      $this->close_object();
      $page_number++;
    }
  }
  
  /**
   * Streams the PDF directly to the browser
   *
   * @param string $filename the name of the PDF file
   * @param array  $options associative array, 'Attachment' => 0 or 1, 'compress' => 1 or 0
   */
  function stream($filename, $options = null) {
    // Add page text
    $this->_add_page_text();
    
    $options["Content-Disposition"] = $filename;
    $this->_pdf->stream($options);
  }

  //........................................................................

  /**
   * Returns the PDF as a string
   *
   * @return string
   */
  function output($options = null) {
    // Add page text
    $this->_add_page_text();

    if ( isset($options["compress"]) && $options["compress"] != 1 )
      $debug = 1;
    else
      $debug = 0;
    
    return $this->_pdf->output($debug);
    
  }
  
  //........................................................................

  /**
   * Returns logging messages generated by the Cpdf class
   *
   * @return string
   */
  function get_messages() { return $this->_pdf->messages; }
  
}

?>
