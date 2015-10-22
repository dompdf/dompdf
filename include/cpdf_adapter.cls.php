<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Orion Richardson <orionr@yahoo.com>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

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
  static $PAPER_SIZES = array(
    "4a0" => array(0,0,4767.87,6740.79),
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
    "commercial #10 envelope" => array(0,0,684,297),
    "catalog #10 1/2 envelope" => array(0,0,648,864),
    "8.5x11" => array(0,0,612.00,792.00),
    "8.5x14" => array(0,0,612.00,1008.0),
    "11x17"  => array(0,0,792.00, 1224.00),
  );

  /**
   * The DOMPDF object
   *
   * @var DOMPDF
   */
  private $_dompdf;

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
   * @param mixed  $paper       The size of paper to use in this PDF ({@link CPDF_Adapter::$PAPER_SIZES})
   * @param string $orientation The orientation of the document (either 'landscape' or 'portrait')
   * @param DOMPDF $dompdf      The DOMPDF instance
   */
  function __construct($paper = "letter", $orientation = "portrait", DOMPDF $dompdf) {
    if ( is_array($paper) ) {
      $size = $paper;
    }
    else if ( isset(self::$PAPER_SIZES[mb_strtolower($paper)]) ) {
      $size = self::$PAPER_SIZES[mb_strtolower($paper)];
    }
    else {
      $size = self::$PAPER_SIZES["letter"];
    }

    if ( mb_strtolower($orientation) === "landscape" ) {
      list($size[2], $size[3]) = array($size[3], $size[2]);
    }

    $this->_dompdf = $dompdf;
    
    $this->_pdf = new Cpdf(
      $size,
      $dompdf->get_option("enable_unicode"),
      $dompdf->get_option("font_cache"),
      $dompdf->get_option("temp_dir")
    );

    $this->_pdf->addInfo("Creator", "DOMPDF");
    $time = substr_replace(date('YmdHisO'), '\'', -2, 0).'\'';
    $this->_pdf->addInfo("CreationDate", "D:$time");
    $this->_pdf->addInfo("ModDate", "D:$time");

    $this->_width = $size[2] - $size[0];
    $this->_height= $size[3] - $size[1];
    
    $this->_page_number = $this->_page_count = 1;
    $this->_page_text = array();

    $this->_pages = array($this->_pdf->getFirstPageId());

    $this->_image_cache = array();
  }

  function get_dompdf(){
    return $this->_dompdf;
  }

  /**
   * Class destructor
   *
   * Deletes all temporary image files
   */
  function __destruct() {
    foreach ($this->_image_cache as $img) {
      // The file might be already deleted by 3rd party tmp cleaner,
      // the file might not have been created at all
      // (if image outputting commands failed)
      // or because the destructor was called twice accidentally.
      if (!file_exists($img)) {
        continue;
      }

      if (DEBUGPNG) print '[__destruct unlink '.$img.']';
      if (!DEBUGKEEPTEMP) unlink($img);
    }
  }
  
  /**
   * Returns the Cpdf instance
   *
   * @return Cpdf
   */
  function get_cpdf() {
    return $this->_pdf;
  }

  /**
   * Add meta information to the PDF
   *
   * @param string $label  label of the value (Creator, Producer, etc.)
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
   * Sets the stroke color
   *
   * See {@link Style::set_color()} for the format of the color array.
   * @param array $color
   */
  protected function _set_stroke_color($color) {
    $this->_pdf->setStrokeColor($color);
  }
  
  /**
   * Sets the fill colour
   *
   * See {@link Style::set_color()} for the format of the colour array.
   * @param array $color
   */
  protected function _set_fill_color($color) {
    $this->_pdf->setColor($color);
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
   * @param float  $width
   * @param string $cap
   * @param string $join
   * @param array  $dash
   */
  protected function _set_line_style($width, $cap, $join, $dash) {
    $this->_pdf->setLineStyle($width, $cap, $join, $dash);
  }
  
  /**
   * Sets the opacity 
   * 
   * @param $opacity
   * @param $mode
   */
  function set_opacity($opacity, $mode = "Normal") {
    $this->_set_line_transparency($mode, $opacity);
    $this->_set_fill_transparency($mode, $opacity);
  }
  
  function set_default_view($view, $options = array()) {
    array_unshift($options, $view);
    call_user_func_array(array($this->_pdf, "openHere"), $options);
  }

  /**
   * Remaps y coords from 4th to 1st quadrant
   *
   * @param float $y
   * @return float
   */
  protected function y($y) {
    return $this->_height - $y;
  }

  // Canvas implementation
  function line($x1, $y1, $x2, $y2, $color, $width, $style = array()) {
    $this->_set_stroke_color($color);
    $this->_set_line_style($width, "butt", "", $style);
    
    $this->_pdf->line($x1, $this->y($y1),
                      $x2, $this->y($y2));
  }
  
  function arc($x, $y, $r1, $r2, $astart, $aend, $color, $width, $style = array()) {
    $this->_set_stroke_color($color);
    $this->_set_line_style($width, "butt", "", $style);
    
    $this->_pdf->ellipse($x, $this->y($y), $r1, $r2, 0, 8, $astart, $aend, false, false, true, false);
  }
                              
  //........................................................................

  /**
   * Convert a GIF or BMP image to a PNG image
   *
   * @param string  $image_url
   * @param integer $type
   *
   * @throws DOMPDF_Exception
   * @return string The url of the newly converted image
   */
  protected function _convert_gif_bmp_to_png($image_url, $type) {
    $image_type = Image_Cache::type_to_ext($type);
    $func_name = "imagecreatefrom$image_type";
    
    if ( !function_exists($func_name) ) {
      throw new DOMPDF_Exception("Function $func_name() not found.  Cannot convert $image_type image: $image_url.  Please install the image PHP extension.");
    }

    set_error_handler("record_warnings");
    $im = $func_name($image_url);

    if ( $im ) {
      imageinterlace($im, false);

      $tmp_dir = $this->_dompdf->get_option("temp_dir");
      $tmp_name = tempnam($tmp_dir, "{$image_type}dompdf_img_");
      @unlink($tmp_name);
      $filename = "$tmp_name.png";
      $this->_image_cache[] = $filename;

      imagepng($im, $filename);
      imagedestroy($im);
    } 
    else {
      $filename = Image_Cache::$broken_image;
    }

    restore_error_handler();
    
    return $filename;
  }

  function rectangle($x1, $y1, $w, $h, $color, $width, $style = array()) {
    $this->_set_stroke_color($color);
    $this->_set_line_style($width, "butt", "", $style);
    $this->_pdf->rectangle($x1, $this->y($y1) - $h, $w, $h);
  }
  
  function filled_rectangle($x1, $y1, $w, $h, $color) {
    $this->_set_fill_color($color);
    $this->_pdf->filledRectangle($x1, $this->y($y1) - $h, $w, $h);
  }
  
  function clipping_rectangle($x1, $y1, $w, $h) {
    $this->_pdf->clippingRectangle($x1, $this->y($y1) - $h, $w, $h);
  }
  
  function clipping_roundrectangle($x1, $y1, $w, $h, $rTL, $rTR, $rBR, $rBL) {
    $this->_pdf->clippingRectangleRounded($x1, $this->y($y1) - $h, $w, $h, $rTL, $rTR, $rBR, $rBL);
  }
  
  function clipping_end() {
    $this->_pdf->clippingEnd();
  }
  
  function save() {
    $this->_pdf->saveState();
  }
  
  function restore() {
    $this->_pdf->restoreState();
  }
  
  function rotate($angle, $x, $y) {
    $this->_pdf->rotate($angle, $x, $y);
  }
  
  function skew($angle_x, $angle_y, $x, $y) {
    $this->_pdf->skew($angle_x, $angle_y, $x, $y);
  }
  
  function scale($s_x, $s_y, $x, $y) {
    $this->_pdf->scale($s_x, $s_y, $x, $y);
  }
  
  function translate($t_x, $t_y) {
    $this->_pdf->translate($t_x, $t_y);
  }
  
  function transform($a, $b, $c, $d, $e, $f) {
    $this->_pdf->transform(array($a, $b, $c, $d, $e, $f));
  }

  function polygon($points, $color, $width = null, $style = array(), $fill = false) {
    $this->_set_fill_color($color);
    $this->_set_stroke_color($color);
    
    // Adjust y values
    for ( $i = 1; $i < count($points); $i += 2) {
      $points[$i] = $this->y($points[$i]);
    }
    
    $this->_pdf->polygon($points, count($points) / 2, $fill);
  }

  function circle($x, $y, $r1, $color, $width = null, $style = null, $fill = false) {
    $this->_set_fill_color($color);
    $this->_set_stroke_color($color);

    if ( !$fill && isset($width) ) {
      $this->_set_line_style($width, "round", "round", $style);
    }

    $this->_pdf->ellipse($x, $this->y($y), $r1, 0, 0, 8, 0, 360, 1, $fill);
  }

  function image($img, $x, $y, $w, $h, $resolution = "normal") {
    list($width, $height, $type) = dompdf_getimagesize($img, $this->_dompdf->get_http_context());
    
    $debug_png = $this->_dompdf->get_option("debug_png");

    if ($debug_png) print "[image:$img|$width|$height|$type]";

    switch ($type) {
    case IMAGETYPE_JPEG:
      if ($debug_png) print '!!!jpg!!!';
      $this->_pdf->addJpegFromFile($img, $x, $this->y($y) - $h, $w, $h);
      break;
      
    case IMAGETYPE_GIF:
    case IMAGETYPE_BMP:
      if ($debug_png) print '!!!bmp or gif!!!';
      // @todo use cache for BMP and GIF
      $img = $this->_convert_gif_bmp_to_png($img, $type);

    case IMAGETYPE_PNG:
      if ($debug_png) print '!!!png!!!';

      $this->_pdf->addPngFromFile($img, $x, $this->y($y) - $h, $w, $h);
      break;

    default:
      if ($debug_png) print '!!!unknown!!!';
    }
  }

  function text($x, $y, $text, $font, $size, $color = array(0,0,0), $word_space = 0.0, $char_space = 0.0, $angle = 0.0) {
    $pdf = $this->_pdf;
    
    $pdf->setColor($color);
    
    $font .= ".afm";
    $pdf->selectFont($font);
    
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
    //print '<pre>['.$font.','.$size.','.$pdf->getFontHeight($size).','.$pdf->getFontDescender($size).','.$pdf->fonts[$pdf->currentFont]['FontBBox'][3].','.$pdf->fonts[$pdf->currentFont]['FontBBox'][1].','.$pdf->fonts[$pdf->currentFont]['FontHeightOffset'].','.$pdf->fonts[$pdf->currentFont]['Ascender'].','.$pdf->fonts[$pdf->currentFont]['Descender'].']</pre>';
    //
    //$pdf->addText($x, $this->y($y) - ($pdf->fonts[$pdf->currentFont]['FontBBox'][3]*$size)/1000, $size, $text, $angle, $word_space, $char_space);
    $pdf->addText($x, $this->y($y) - $pdf->getFontHeight($size), $size, $text, $angle, $word_space, $char_space);
  }

  //........................................................................

  function javascript($code) {
    $this->_pdf->addJavascript($code);
  }

  //........................................................................

  /**
   * Add a named destination (similar to <a name="foo">...</a> in html)
   *
   * @param string $anchorname The name of the named destination
   */
  function add_named_dest($anchorname) {
    $this->_pdf->addDestination($anchorname, "Fit");
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
      if ( $name ) {
        $this->_pdf->addInternalLink($name, $x, $y, $x + $width, $y + $height);
      }
      
    }
    else {
      $this->_pdf->addLink(rawurldecode($url), $x, $y, $x + $width, $y + $height);
    }
  }

  function get_text_width($text, $font, $size, $word_spacing = 0, $char_spacing = 0) {
    $this->_pdf->selectFont($font);

    $unicode = $this->_dompdf->get_option("enable_unicode");
    if (!$unicode) {
      $text = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
    }

    return $this->_pdf->getTextWidth($size, $text, $word_spacing, $char_spacing);
  }
  
  function register_string_subset($font, $string) {
    $this->_pdf->registerText($font, $string);
  }

  function get_font_height($font, $size) {
    $this->_pdf->selectFont($font);

    $ratio = $this->_dompdf->get_option("font_height_ratio");
    return $this->_pdf->getFontHeight($size) * $ratio;
  }
  
  /*function get_font_x_height($font, $size) {
    $this->_pdf->selectFont($font);
    $ratio = $this->_dompdf->get_option("font_height_ratio");
    return $this->_pdf->getFontXHeight($size) * $ratio;
  }*/
  
  function get_font_baseline($font, $size) {
    $ratio = $this->_dompdf->get_option("font_height_ratio");
    return $this->get_font_height($font, $size) / $ratio;
  }
  
  /**
   * Writes text at the specified x and y coordinates on every page
   *
   * The strings '{PAGE_NUM}' and '{PAGE_COUNT}' are automatically replaced
   * with their current values.
   *
   * See {@link Style::munge_color()} for the format of the colour array.
   *
   * @param float $x
   * @param float $y
   * @param string $text the text to write
   * @param string $font the font file to use
   * @param float $size the font size, in points
   * @param array $color
   * @param float $word_space word spacing adjustment
   * @param float $char_space char spacing adjustment
   * @param float $angle angle to write the text at, measured CW starting from the x-axis
   */
  function page_text($x, $y, $text, $font, $size, $color = array(0,0,0), $word_space = 0.0, $char_space = 0.0, $angle = 0.0) {
    $_t = "text";
    $this->_page_text[] = compact("_t", "x", "y", "text", "font", "size", "color", "word_space", "char_space", "angle");
  }
    
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

  function new_page() {
    $this->_page_number++;
    $this->_page_count++;

    $ret = $this->_pdf->newPage();
    $this->_pages[] = $ret;
    return $ret;
  }

  /**
   * Add text to each page after rendering is complete
   */
  protected function _add_page_text() {
    
    if ( !count($this->_page_text) ) {
      return;
    }

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
            $this->text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
            break;
            
          case "script":
            if ( !$eval ) {
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

  /**
   * Returns the PDF as a string
   * 
   * @param array $options Output options
   * @return string
   */
  function output($options = null) {
    $this->_add_page_text();

    $debug = isset($options["compress"]) && $options["compress"] != 1;
    
    return $this->_pdf->output($debug);
  }

  /**
   * Returns logging messages generated by the Cpdf class
   *
   * @return string
   */
  function get_messages() {
    return $this->_pdf->messages;
  }
}
