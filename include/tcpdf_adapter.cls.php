<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

require_once DOMPDF_LIB_DIR . '/tcpdf/tcpdf.php';

/**
 * TCPDF PDF Rendering interface
 *
 * TCPDF_Adapter provides a simple, stateless interface to TCPDF.
 *
 * Unless otherwise mentioned, all dimensions are in points (1/72 in).
 * The coordinate origin is in the top left corner and y values
 * increase downwards.
 *
 * See {@link http://tcpdf.sourceforge.net} for more information on
 * the underlying TCPDF class.
 *
 * @package dompdf
 */
class TCPDF_Adapter implements Canvas {

  /**
   * Dimensions of paper sizes in points
   *
   * @var array;
   */
  static public $PAPER_SIZES = array(); // Set to CPDF_Adapter::$PAPER_SIZES below.

  /**
   * @var DOMPDF
   */
  private $_dompdf;

  /**
   * Instance of the TCPDF class
   *
   * @var TCPDF
   */
  private $_pdf;

  /**
   * PDF width in points
   *
   * @var float
   */
  private $_width;

  /**
   * PDF height in points
   *
   * @var float
   */
  private $_height;

  /**
   * Last fill color used
   *
   * @var array
   */
  private $_last_fill_color;

  /**
   * Last stroke color used
   *
   * @var array
   */
  private $_last_stroke_color;

  /**
   * Last line width used
   *
   * @var float
   */
  private $_last_line_width;
  
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
   * Array of pages for accessing after initial rendering is complete
   *
   * @var array
   */
  private $_pages;

  /**
   * Class constructor
   *
   * @param mixed  $paper       The size of paper to use either a string (see {@link CPDF_Adapter::$PAPER_SIZES}) or
   *                            an array(xmin,ymin,xmax,ymax)
   * @param string $orientation The orientation of the document (either 'landscape' or 'portrait')
   * @param DOMPDF $dompdf
   */
  function __construct($paper = "letter", $orientation = "portrait", DOMPDF $dompdf) {
   
    if ( is_array($paper) )
      $size = $paper;
    else if ( isset(self::$PAPER_SIZES[mb_strtolower($paper)]) )
      $size = self::$PAPER_SIZES[$paper];
    else
      $size = self::$PAPER_SIZES["letter"];

    if ( mb_strtolower($orientation) === "landscape" ) {
      list($size[2], $size[3]) = array($size[3], $size[2]);
    }

    $this->_width  = $size[2] - $size[0];
    $this->_height = $size[3] - $size[1];

    $this->_dompdf = $dompdf;

    $this->_pdf = new TCPDF("P", "pt", array($this->_width, $this->_height));
    $this->_pdf->Setcreator("DOMPDF Converter");

    $this->_pdf->AddPage();

    $this->_page_number = $this->_page_count = 1;
    $this->_page_text = array();

    $this->_last_fill_color   = null;
    $this->_last_stroke_color = null;
    $this->_last_line_width   = null;
  }

  function get_dompdf(){
    return $this->_dompdf;
  }
  
  /**
   * Remaps y coords from 4th to 1st quadrant
   *
   * @param float $y
   * @return float
   */
  protected function y($y) { return $this->_height - $y; }

  /**
   * Sets the stroke color
   *
   * @param array $color
   *
   * @return void
   */
  protected function _set_stroke_color($color) {
    $color[0] = round(255 * $color[0]);
    $color[1] = round(255 * $color[1]);
    $color[2] = round(255 * $color[2]);

    if ( is_null($this->_last_stroke_color) || $color != $this->_last_stroke_color ) {
      $this->_pdf->SetDrawColor($color[0],$color[1],$color[2]);
      $this->_last_stroke_color = $color;
    }

  }

  /**
   * Sets the fill color
   *
   * @param array $color
   */
  protected function _set_fill_color($color) {
    $color[0] = round(255 * $color[0]);
    $color[1] = round(255 * $color[1]);
    $color[2] = round(255 * $color[2]);

    if ( is_null($this->_last_fill_color) || $color != $this->_last_fill_color ) {
      $this->_pdf->SetDrawColor($color[0],$color[1],$color[2]);
      $this->_last_fill_color = $color;
    }

  }

  /**
   * Return the TCPDF instance
   *
   * @return TCPDF
   */
  function get_tcpdf() { return $this->_pdf; }
  
  /**
   * Returns the current page number
   *
   * @return int
   */
  function get_page_number() {
    return $this->_page_number;
  }

  /**
   * Returns the total number of pages
   *
   * @return int
   */
  function get_page_count() {
    return $this->_page_count;
  }

  /**
   * Sets the total number of pages
   *
   * @param int $count
   */
  function set_page_count($count) {
    $this->_page_count = (int)$count;
  }

  /**
   * Draws a line from x1,y1 to x2,y2
   *
   * See {@link Style::munge_color()} for the format of the color array.
   * See {@link Cpdf::setLineStyle()} for a description of the format of the
   * $style parameter (aka dash).
   *
   * @param float $x1
   * @param float $y1
   * @param float $x2
   * @param float $y2
   * @param array $color
   * @param float $width
   * @param array $style
   */
  function line($x1, $y1, $x2, $y2, $color, $width, $style = null) {

    if ( is_null($this->_last_line_width) || $width != $this->_last_line_width ) {
      $this->_pdf->SetLineWidth($width);
      $this->_last_line_width = $width;
    }

    $this->_set_stroke_color($color);

    // FIXME: ugh, need to handle different styles here
    $this->_pdf->line($x1, $y1, $x2, $y2);
  }

  /**
   * Draws a rectangle at x1,y1 with width w and height h
   *
   * See {@link Style::munge_color()} for the format of the color array.
   * See {@link Cpdf::setLineStyle()} for a description of the $style
   * parameter (aka dash)
   *
   * @param float $x1
   * @param float $y1
   * @param float $w
   * @param float $h
   * @param array $color
   * @param float $width
   * @param array $style
   */   
  function rectangle($x1, $y1, $w, $h, $color, $width, $style = null) {

    if ( is_null($this->_last_line_width) || $width != $this->_last_line_width ) {
      $this->_pdf->SetLineWidth($width);
      $this->_last_line_width = $width;
    }

    $this->_set_stroke_color($color);
    
    // FIXME: ugh, need to handle styles here
    $this->_pdf->rect($x1, $y1, $w, $h);
    
  }

  /**
   * Draws a filled rectangle at x1,y1 with width w and height h
   *
   * See {@link Style::munge_color()} for the format of the color array.
   *
   * @param float $x1
   * @param float $y1
   * @param float $w
   * @param float $h
   * @param array $color
   */   
  function filled_rectangle($x1, $y1, $w, $h, $color) {

    $this->_set_fill_color($color);
    
    // FIXME: ugh, need to handle styles here
    $this->_pdf->rect($x1, $y1, $w, $h, "F");
  }

  /**
   * Draws a polygon
   *
   * The polygon is formed by joining all the points stored in the $points
   * array.  $points has the following structure:
   * <code>
   * array(0 => x1,
   *       1 => y1,
   *       2 => x2,
   *       3 => y2,
   *       ...
   *       );
   * </code>
   *
   * See {@link Style::munge_color()} for the format of the color array.
   * See {@link Cpdf::setLineStyle()} for a description of the $style
   * parameter (aka dash)   
   *
   * @param array $points
   * @param array $color
   * @param float $width
   * @param array $style
   * @param bool  $fill  Fills the polygon if true
   */
  function polygon($points, $color, $width = null, $style = null, $fill = false) {
    // FIXME
  }

  /**
   * Draws a circle at $x,$y with radius $r
   *
   * See {@link Style::munge_color()} for the format of the color array.
   * See {@link Cpdf::setLineStyle()} for a description of the $style
   * parameter (aka dash)
   *
   * @param float $x
   * @param float $y
   * @param float $r
   * @param array $color
   * @param float $width
   * @param array $style
   * @param bool $fill Fills the circle if true   
   */   
  function circle($x, $y, $r, $color, $width = null, $style = null, $fill = false) {
    // FIXME
  }

  /**
   * Add an image to the pdf.
   * The image is placed at the specified x and y coordinates with the
   * given width and height.
   *
   * @param string $img_url the path to the image
   * @param float  $x       x position
   * @param float  $y       y position
   * @param int    $w       width (in pixels)
   * @param int    $h       height (in pixels)
   * @param string $resolution
   *
   * @return void
   */
  function image($img_url, $x, $y, $w, $h, $resolution = "normal") {
    // FIXME
  }

  /**
   * Writes text at the specified x and y coordinates
   * See {@link Style::munge_color()} for the format of the color array.
   *
   * @param float  $x
   * @param float  $y
   * @param string $text the text to write
   * @param string $font the font file to use
   * @param float  $size the font size, in points
   * @param array  $color
   * @param float  $word_space word spacing adjustment
   * @param float  $char_space
   * @param float  $angle
   *
   * @return void
   */
  function text($x, $y, $text, $font, $size, $color = array(0,0,0), $word_space = 0.0, $char_space = 0.0, $angle = 0.0) {
    // FIXME
  }

  function javascript($code) {
    // FIXME
  }
  
  /**
   * Add a named destination (similar to <a name="foo">...</a> in html)
   *
   * @param string $anchorname The name of the named destination
   */
  function add_named_dest($anchorname) {
    // FIXME
  }

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
    // FIXME
  }
  
  /**
   * Add meta information to the PDF
   *
   * @param string $label  label of the value (Creator, Producer, etc.)
   * @param string $value  the text to set
   */
  function add_info($label, $value) {
    $method = "Set$label";
    if ( in_array("Title", "Author", "Keywords", "Subject") && method_exists($this->_pdf, $method) ) {
      $this->_pdf->$method($value);
    }
  }

  /**
   * Calculates text size, in points
   *
   * @param string $text the text to be sized
   * @param string $font the desired font
   * @param float  $size the desired font size
   * @param float  $word_spacing word spacing, if any
   * @param float  $char_spacing
   *
   * @return float
   */
  function get_text_width($text, $font, $size, $word_spacing = 0.0, $char_spacing = 0.0) {
    // FIXME
  }

  /**
   * Calculates font height, in points
   *
   * @param string $font
   * @param float $size
   * @return float
   */
  function get_font_height($font, $size) {
    // FIXME
  }

  
  /**
   * Starts a new page
   *
   * Subsequent drawing operations will appear on the new page.
   */
  function new_page() {
    // FIXME
  }

  /**
   * Streams the PDF directly to the browser
   *
   * @param string $filename the name of the PDF file
   * @param array  $options associative array, 'Attachment' => 0 or 1, 'compress' => 1 or 0
   */
  function stream($filename, $options = null) {
    // FIXME
  }

  /**
   * Returns the PDF as a string
   *
   * @param array  $options associative array: 'compress' => 1 or 0
   * @return string
   */
  function output($options = null) {
    // FIXME
  }

  /**
   * Starts a clipping rectangle at x1,y1 with width w and height h
   *
   * @param float $x1
   * @param float $y1
   * @param float $w
   * @param float $h
   */
  function clipping_rectangle($x1, $y1, $w, $h) {
    // TODO: Implement clipping_rectangle() method.
  }

  /**
   * Starts a rounded clipping rectangle at x1,y1 with width w and height h
   *
   * @param float $x1
   * @param float $y1
   * @param float $w
   * @param float $h
   * @param float $tl
   * @param float $tr
   * @param float $br
   * @param float $bl
   *
   * @return void
   */
  function clipping_roundrectangle($x1, $y1, $w, $h, $tl, $tr, $br, $bl) {
    // TODO: Implement clipping_roundrectangle() method.
  }

  /**
   * Ends the last clipping shape
   */
  function clipping_end() {
    // TODO: Implement clipping_end() method.
  }

  /**
   * Save current state
   */
  function save() {
    // TODO: Implement save() method.
  }

  /**
   * Restore last state
   */
  function restore() {
    // TODO: Implement restore() method.
  }

  /**
   * Rotate
   */
  function rotate($angle, $x, $y) {
    // TODO: Implement rotate() method.
  }

  /**
   * Skew
   */
  function skew($angle_x, $angle_y, $x, $y) {
    // TODO: Implement skew() method.
  }

  /**
   * Scale
   */
  function scale($s_x, $s_y, $x, $y) {
    // TODO: Implement scale() method.
  }

  /**
   * Translate
   */
  function translate($t_x, $t_y) {
    // TODO: Implement translate() method.
  }

  /**
   * Transform
   */
  function transform($a, $b, $c, $d, $e, $f) {
    // TODO: Implement transform() method.
  }

  /**
   * Add an arc to the PDF
   * See {@link Style::munge_color()} for the format of the color array.
   *
   * @param float $x      X coordinate of the arc
   * @param float $y      Y coordinate of the arc
   * @param float $r1     Radius 1
   * @param float $r2     Radius 2
   * @param float $astart Start angle in degrees
   * @param float $aend   End angle in degrees
   * @param array $color  Color
   * @param float $width
   * @param array $style
   *
   * @return void
   */
  function arc($x, $y, $r1, $r2, $astart, $aend, $color, $width, $style = array()) {
    // TODO: Implement arc() method.
  }

  /**
   * Calculates font baseline, in points
   *
   * @param string $font
   * @param float  $size
   *
   * @return float
   */
  function get_font_baseline($font, $size) {
    // TODO: Implement get_font_baseline() method.
  }

  /**
   * Sets the opacity
   *
   * @param float  $opacity
   * @param string $mode
   */
  function set_opacity($opacity, $mode = "Normal") {
    // TODO: Implement set_opacity() method.
  }

  /**
   * Sets the default view
   *
   * @param string $view
   * 'XYZ'  left, top, zoom
   * 'Fit'
   * 'FitH' top
   * 'FitV' left
   * 'FitR' left,bottom,right
   * 'FitB'
   * 'FitBH' top
   * 'FitBV' left
   * @param array  $options
   *
   * @return void
   */
  function set_default_view($view, $options = array()) {
    // TODO: Implement set_default_view() method.
  }}
    
// Workaround for idiotic limitation on statics...
TCPDF_Adapter::$PAPER_SIZES = CPDF_Adapter::$PAPER_SIZES;
