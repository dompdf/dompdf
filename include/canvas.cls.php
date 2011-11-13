<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version $Id$
 */

/**
 * Main rendering interface
 *
 * Currently {@link CPDF_Adapter}, {@link PDFLib_Adapter}, {@link TCPDF_Adapter}, and {@link GD_Adapter}
 * implement this interface.
 *
 * Implementations should measure x and y increasing to the left and down,
 * respectively, with the origin in the top left corner.  Implementations
 * are free to use a unit other than points for length, but I can't
 * guarantee that the results will look any good.
 *
 * @package dompdf
 */
interface Canvas {

  /**
   * Returns the current page number
   *
   * @return int
   */
  function get_page_number();

  /**
   * Returns the total number of pages
   *
   * @return int
   */
  function get_page_count();

  /**
   * Sets the total number of pages
   *
   * @param int $count
   */
  function set_page_count($count);

  /**
   * Draws a line from x1,y1 to x2,y2
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
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
  function line($x1, $y1, $x2, $y2, $color, $width, $style = null);

  /**
   * Draws a rectangle at x1,y1 with width w and height h
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
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
  function rectangle($x1, $y1, $w, $h, $color, $width, $style = null);

  /**
   * Draws a filled rectangle at x1,y1 with width w and height h
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
   *
   * @param float $x1
   * @param float $y1
   * @param float $w
   * @param float $h
   * @param array $color
   */   
  function filled_rectangle($x1, $y1, $w, $h, $color);

  /**
   * Starts a clipping rectangle at x1,y1 with width w and height h
   *
   * @param float $x1
   * @param float $y1
   * @param float $w
   * @param float $h
   */   
  function clipping_rectangle($x1, $y1, $w, $h);
  
  /**
   * Ends the last clipping shape
   */  
  function clipping_end();
  
  /**
   * Save current state
   */
  function save();
  
  /**
   * Restore last state
   */
  function restore();
  
  /**
   * Rotate
   */
  function rotate($angle, $x, $y);
  
  /**
   * Skew
   */
  function skew($angle_x, $angle_y, $x, $y);
  
  /**
   * Scale
   */
  function scale($s_x, $s_y, $x, $y);
  
  /**
   * Translate
   */
  function translate($t_x, $t_y);
  
  /**
   * Transform
   */
  function transform($a, $b, $c, $d, $e, $f);
  
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
   * See {@link Style::munge_colour()} for the format of the colour array.
   * See {@link Cpdf::setLineStyle()} for a description of the $style
   * parameter (aka dash)   
   *
   * @param array $points
   * @param array $color
   * @param float $width
   * @param array $style
   * @param bool  $fill  Fills the polygon if true
   */
  function polygon($points, $color, $width = null, $style = null, $fill = false);

  /**
   * Draws a circle at $x,$y with radius $r
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
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
  function circle($x, $y, $r, $color, $width = null, $style = null, $fill = false);

  /**
   * Add an image to the pdf.
   *
   * The image is placed at the specified x and y coordinates with the
   * given width and height.
   *
   * @param string $img_url the path to the image
   * @param string $img_type the type (e.g. extension) of the image
   * @param float $x x position
   * @param float $y y position
   * @param int $w width (in pixels)
   * @param int $h height (in pixels)
   */
  function image($img_url, $x, $y, $w, $h, $resolution = "normal");

  /**
   * Writes text at the specified x and y coordinates
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
   *
   * @param float $x
   * @param float $y
   * @param string $text the text to write
   * @param string $font the font file to use
   * @param float $size the font size, in points
   * @param array $color
   * @param float $word_space word spacing adjustment
   * @param float $char_space whar spacing adjustment
   * @param float $angle angle
   */
  function text($x, $y, $text, $font, $size, $color = array(0,0,0), $word_space = 0, $char_space = 0, $angle = 0);

  /**
   * Add a named destination (similar to <a name="foo">...</a> in html)
   *
   * @param string $anchorname The name of the named destination
   */
  function add_named_dest($anchorname);

  /**
   * Add a link to the pdf
   *
   * @param string $url The url to link to
   * @param float  $x   The x position of the link
   * @param float  $y   The y position of the link
   * @param float  $width   The width of the link
   * @param float  $height   The height of the link
   */
  function add_link($url, $x, $y, $width, $height);
  
  /**
   * Add meta information to the pdf
   * 
   * @param string $label  label of the value (Creator, Producer, etc.)
   * @param string $value  the text to set
   */
  function add_info($name, $value);
  
  /**
   * Calculates text size, in points
   *
   * @param string $text the text to be sized
   * @param string $font the desired font
   * @param float  $size the desired font size
   * @param float  $spacing word spacing, if any
   * @return float
   */
  function get_text_width($text, $font, $size, $word_spacing = 0, $char_spacing = 0);

  /**
   * Calculates font height, in points
   *
   * @param string $font
   * @param float $size
   * @return float
   */
  function get_font_height($font, $size);
  
  function get_font_baseline($font, $size);
  
  /**
   * Returns the font x-height, in points
   *
   * @param string $font
   * @param float $size
   * @return float
   */
  //function get_font_x_height($font, $size);
  
  /**
   * Sets the opacity
   *
   * @param float $opacity
   * @param string $mode
   * @return float
   */
  function set_opacity($opacity, $mode = "Normal");
  
  /**
   * Sets the default view
   *
   * @param string $view
   * 
   * 'XYZ'  left, top, zoom
   * 'Fit'
   * 'FitH' top
   * 'FitV' left
   * 'FitR' left,bottom,right
   * 'FitB'
   * 'FitBH' top
   * 'FitBV' left
   */
  function set_default_view($view, $options = array());
  
  /**
   * Starts a new page
   *
   * Subsequent drawing operations will appear on the new page.
   */
  function new_page();

  /**
   * Streams the PDF directly to the browser
   *
   * @param string $filename the name of the PDF file
   * @param array  $options associative array, 'Attachment' => 0 or 1, 'compress' => 1 or 0
   */
  function stream($filename, $options = null);

  /**
   * Returns the PDF as a string
   *
   * @param array  $options associative array: 'compress' => 1 or 0
   * @return string
   */
  function output($options = null);
  
}
