<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Image rendering interface
 *
 * Renders to an image format supported by GD (jpeg, gif, png, xpm).
 * Not super-useful day-to-day but handy nonetheless
 *
 * @package dompdf
 */
class GD_Adapter implements Canvas {
  /**
   * @var DOMPDF
   */
  private $_dompdf;

  /**
   * Resource handle for the image
   *
   * @var resource
   */
  private $_img;

  /**
   * Image width in pixels
   *
   * @var int
   */
  private $_width;

  /**
   * Image height in pixels
   *
   * @var int
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
   * Image antialias factor
   *
   * @var float
   */
  private $_aa_factor;

  /**
   * Allocated colors
   *
   * @var array
   */
  private $_colors;

  /**
   * Background color
   *
   * @var int
   */
  private $_bg_color;

  /**
   * Class constructor
   *
   * @param mixed  $size         The size of image to create: array(x1,y1,x2,y2) or "letter", "legal", etc.
   * @param string $orientation  The orientation of the document (either 'landscape' or 'portrait')
   * @param DOMPDF $dompdf
   * @param float  $aa_factor    Anti-aliasing factor, 1 for no AA
   * @param array  $bg_color     Image background color: array(r,g,b,a), 0 <= r,g,b,a <= 1
   */
  function __construct($size, $orientation = "portrait", DOMPDF $dompdf, $aa_factor = 1.0, $bg_color = array(1,1,1,0) ) {

    if ( !is_array($size) ) {
      $size = strtolower($size);
      
      if ( isset(CPDF_Adapter::$PAPER_SIZES[$size]) ) {
        $size = CPDF_Adapter::$PAPER_SIZES[$size];
      }
      else {
        $size = CPDF_Adapter::$PAPER_SIZES["letter"];
      }
    }

    if ( strtolower($orientation) === "landscape" ) {
      list($size[2],$size[3]) = array($size[3],$size[2]);
    }

    $this->_dompdf = $dompdf;

    if ( $aa_factor < 1 ) {
      $aa_factor = 1;
    }

    $this->_aa_factor = $aa_factor;
    
    $size[2] *= $aa_factor;
    $size[3] *= $aa_factor;
    
    $this->_width = $size[2] - $size[0];
    $this->_height = $size[3] - $size[1];

    $this->_img = imagecreatetruecolor($this->_width, $this->_height);

    if ( is_null($bg_color) || !is_array($bg_color) ) {
      // Pure white bg
      $bg_color = array(1,1,1,0);
    }

    $this->_bg_color = $this->_allocate_color($bg_color);
    imagealphablending($this->_img, true);
    imagesavealpha($this->_img, true);
    imagefill($this->_img, 0, 0, $this->_bg_color);
    
  }

  function get_dompdf(){
    return $this->_dompdf;
  }

  /**
   * Return the GF image resource
   *
   * @return resource
   */
  function get_image() { return $this->_img; }

  /**
   * Return the image's width in pixels
   *
   * @return float
   */
  function get_width() { return $this->_width / $this->_aa_factor; }

  /**
   * Return the image's height in pixels
   *
   * @return float
   */
  function get_height() { return $this->_height / $this->_aa_factor; }

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
   * Sets the opacity 
   * 
   * @param $opacity
   * @param $mode
   */
  function set_opacity($opacity, $mode = "Normal") {
    // FIXME
  }

  /**
   * Allocate a new color.  Allocate with GD as needed and store
   * previously allocated colors in $this->_colors.
   *
   * @param array $color  The new current color
   * @return int           The allocated color
   */
  private function _allocate_color($color) {
    
    if ( isset($color["c"]) ) {
      $color = cmyk_to_rgb($color);
    }
    
    // Full opacity if no alpha set
    if ( !isset($color[3]) ) 
      $color[3] = 0;
    
    list($r,$g,$b,$a) = $color;
    
    $r *= 255;
    $g *= 255;
    $b *= 255;
    $a *= 127;
    
    // Clip values
    $r = $r > 255 ? 255 : $r;
    $g = $g > 255 ? 255 : $g;
    $b = $b > 255 ? 255 : $b;
    $a = $a > 127 ? 127 : $a;
      
    $r = $r < 0 ? 0 : $r;
    $g = $g < 0 ? 0 : $g;
    $b = $b < 0 ? 0 : $b;
    $a = $a < 0 ? 0 : $a;
      
    $key = sprintf("#%02X%02X%02X%02X", $r, $g, $b, $a);
      
    if ( isset($this->_colors[$key]) )
      return $this->_colors[$key];

    if ( $a != 0 ) 
      $this->_colors[$key] = imagecolorallocatealpha($this->_img, $r, $g, $b, $a);
    else
      $this->_colors[$key] = imagecolorallocate($this->_img, $r, $g, $b);
      
    return $this->_colors[$key];
    
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

    // Scale by the AA factor
    $x1 *= $this->_aa_factor;
    $y1 *= $this->_aa_factor;
    $x2 *= $this->_aa_factor;
    $y2 *= $this->_aa_factor;
    $width *= $this->_aa_factor;

    $c = $this->_allocate_color($color);

    // Convert the style array if required
    if ( !is_null($style) ) {
      $gd_style = array();

      if ( count($style) == 1 ) {
        for ($i = 0; $i < $style[0] * $this->_aa_factor; $i++) {
          $gd_style[] = $c;
        }

        for ($i = 0; $i < $style[0] * $this->_aa_factor; $i++) {
          $gd_style[] = $this->_bg_color;
        }

      } else {

        $i = 0;
        foreach ($style as $length) {

          if ( $i % 2 == 0 ) {
            // 'On' pattern
            for ($i = 0; $i < $style[0] * $this->_aa_factor; $i++) 
              $gd_style[] = $c;
            
          } else {
            // Off pattern
            for ($i = 0; $i < $style[0] * $this->_aa_factor; $i++) 
              $gd_style[] = $this->_bg_color;
            
          }
          $i++;
        }
      }
      
      imagesetstyle($this->_img, $gd_style);
      $c = IMG_COLOR_STYLED;
    }
    
    imagesetthickness($this->_img, $width);

    imageline($this->_img, $x1, $y1, $x2, $y2, $c);
    
  }

  function arc($x1, $y1, $r1, $r2, $astart, $aend, $color, $width, $style = array()) {
    // @todo
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

    // Scale by the AA factor
    $x1 *= $this->_aa_factor;
    $y1 *= $this->_aa_factor;
    $w *= $this->_aa_factor;
    $h *= $this->_aa_factor;

    $c = $this->_allocate_color($color);

    // Convert the style array if required
    if ( !is_null($style) ) {
      $gd_style = array();

      foreach ($style as $length) {
        for ($i = 0; $i < $length; $i++) {
          $gd_style[] = $c;
        }
      }

      imagesetstyle($this->_img, $gd_style);
      $c = IMG_COLOR_STYLED;
    }

    imagesetthickness($this->_img, $width);

    imagerectangle($this->_img, $x1, $y1, $x1 + $w, $y1 + $h, $c);
    
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

    // Scale by the AA factor
    $x1 *= $this->_aa_factor;
    $y1 *= $this->_aa_factor;
    $w *= $this->_aa_factor;
    $h *= $this->_aa_factor;

    $c = $this->_allocate_color($color);

    imagefilledrectangle($this->_img, $x1, $y1, $x1 + $w, $y1 + $h, $c);

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
    // @todo
  }
  
  function clipping_roundrectangle($x1, $y1, $w, $h, $rTL, $rTR, $rBR, $rBL) {
    // @todo
  }
  
  /**
   * Ends the last clipping shape
   */  
  function clipping_end() {
    // @todo
  }
  
  function save() {
    // @todo
  }
  
  function restore() {
    // @todo
  }
  
  function rotate($angle, $x, $y) {
    // @todo
  }
  
  function skew($angle_x, $angle_y, $x, $y) {
    // @todo
  }
  
  function scale($s_x, $s_y, $x, $y) {
    // @todo
  }
  
  function translate($t_x, $t_y) {
    // @todo
  }
  
  function transform($a, $b, $c, $d, $e, $f) {
    // @todo
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

    // Scale each point by the AA factor
    foreach (array_keys($points) as $i)
      $points[$i] *= $this->_aa_factor;

    $c = $this->_allocate_color($color);

    // Convert the style array if required
    if ( !is_null($style) && !$fill ) {
      $gd_style = array();

      foreach ($style as $length) {
        for ($i = 0; $i < $length; $i++) {
          $gd_style[] = $c;
        }
      }

      imagesetstyle($this->_img, $gd_style);
      $c = IMG_COLOR_STYLED;
    }

    imagesetthickness($this->_img, $width);

    if ( $fill ) 
      imagefilledpolygon($this->_img, $points, count($points) / 2, $c);
    else
      imagepolygon($this->_img, $points, count($points) / 2, $c);
        
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

    // Scale by the AA factor
    $x *= $this->_aa_factor;
    $y *= $this->_aa_factor;
    $r *= $this->_aa_factor;

    $c = $this->_allocate_color($color);

    // Convert the style array if required
    if ( !is_null($style) && !$fill ) {
      $gd_style = array();

      foreach ($style as $length) {
        for ($i = 0; $i < $length; $i++) {
          $gd_style[] = $c;
        }
      }

      imagesetstyle($this->_img, $gd_style);
      $c = IMG_COLOR_STYLED;
    }

    imagesetthickness($this->_img, $width);

    if ( $fill )
      imagefilledellipse($this->_img, $x, $y, $r, $r, $c);
    else
      imageellipse($this->_img, $x, $y, $r, $r, $c);
        
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
   * @internal param string $img_type the type (e.g. extension) of the image
   */
  function image($img_url, $x, $y, $w, $h, $resolution = "normal") {
    $img_type = Image_Cache::detect_type($img_url, $this->_dompdf->get_http_context());
    $img_ext  = Image_Cache::type_to_ext($img_type);

    if ( !$img_ext ) {
      return;
    }
    
    $func = "imagecreatefrom$img_ext";
    $src = @$func($img_url);

    if ( !$src ) {
      return; // Probably should add to $_dompdf_errors or whatever here
    }
    
    // Scale by the AA factor
    $x *= $this->_aa_factor;
    $y *= $this->_aa_factor;

    $w *= $this->_aa_factor;
    $h *= $this->_aa_factor;
    
    $img_w = imagesx($src);
    $img_h = imagesy($src);
    
    imagecopyresampled($this->_img, $src, $x, $y, 0, 0, $w, $h, $img_w, $img_h);
    
  }

  /**
   * Writes text at the specified x and y coordinates
   * See {@link Style::munge_color()} for the format of the color array.
   *
   * @param float  $x
   * @param float  $y
   * @param string $text  the text to write
   * @param string $font  the font file to use
   * @param float  $size  the font size, in points
   * @param array  $color
   * @param float  $word_spacing word spacing adjustment
   * @param float  $char_spacing
   * @param float  $angle Text angle
   *
   * @return void
   */
  function text($x, $y, $text, $font, $size, $color = array(0,0,0), $word_spacing = 0.0, $char_spacing = 0.0, $angle = 0.0) {

    // Scale by the AA factor
    $x *= $this->_aa_factor;
    $y *= $this->_aa_factor;
    $size *= $this->_aa_factor;
    
    $h = $this->get_font_height($font, $size);
    $c = $this->_allocate_color($color);
    
    $text = mb_encode_numericentity($text, array(0x0080, 0xff, 0, 0xff), 'UTF-8');

    $font = $this->get_ttf_file($font);

    // FIXME: word spacing
    @imagettftext($this->_img, $size, $angle, $x, $y + $h, $c, $font, $text);
    
  }
  
  function javascript($code) {
    // Not implemented
  }

  /**
   * Add a named destination (similar to <a name="foo">...</a> in html)
   *
   * @param string $anchorname The name of the named destination
   */
  function add_named_dest($anchorname) {
    // Not implemented
  }

  /**
   * Add a link to the pdf
   *
   * @param string $url    The url to link to
   * @param float  $x      The x position of the link
   * @param float  $y      The y position of the link
   * @param float  $width  The width of the link
   * @param float  $height The height of the link
   */
  function add_link($url, $x, $y, $width, $height) {
    // Not implemented
  }

  /**
   * Add meta information to the PDF
   *
   * @param string $label  label of the value (Creator, Producer, etc.)
   * @param string $value  the text to set
   */
  function add_info($label, $value) {
    // N/A
  }
  
  function set_default_view($view, $options = array()) {
    // N/A
  }

  /**
   * Calculates text size, in points
   *
   * @param string $text the text to be sized
   * @param string $font the desired font
   * @param float  $size the desired font size
   * @param float  $word_spacing word spacing, if any
   * @param float  $char_spacing char spacing, if any
   *
   * @return float
   */
  function get_text_width($text, $font, $size, $word_spacing = 0.0, $char_spacing = 0.0) {
    $font = $this->get_ttf_file($font);
      
    $text = mb_encode_numericentity($text, array(0x0080, 0xffff, 0, 0xffff), 'UTF-8');

    // FIXME: word spacing
    list($x1,,$x2) = @imagettfbbox($size, 0, $font, $text);
    return $x2 - $x1;
  }
  
  function get_ttf_file($font) {
    if ( strpos($font, '.ttf') === false )
      $font .= ".ttf";
    
    /*$filename = substr(strtolower(basename($font)), 0, -4);
    
    if ( in_array($filename, DOMPDF::$native_fonts) ) {
      return "arial.ttf";
    }*/
    
    return $font;
  }

  /**
   * Calculates font height, in points
   *
   * @param string $font
   * @param float $size
   * @return float
   */
  function get_font_height($font, $size) {
    $font = $this->get_ttf_file($font);
    $ratio = $this->_dompdf->get_option("font_height_ratio");

    // FIXME: word spacing
    list(,$y2,,,,$y1) = imagettfbbox($size, 0, $font, "MXjpqytfhl");  // Test string with ascenders, descenders and caps
    return ($y2 - $y1) * $ratio;
  }
  
  function get_font_baseline($font, $size) {
    $ratio = $this->_dompdf->get_option("font_height_ratio");
    return $this->get_font_height($font, $size) / $ratio;
  }
  
  /**
   * Starts a new page
   *
   * Subsequent drawing operations will appear on the new page.
   */
  function new_page() {
    $this->_page_number++;
    $this->_page_count++;
  }    

  function open_object(){
    // N/A
  }

  function close_object(){
    // N/A
  }

  function add_object(){
    // N/A
  }

  function page_text(){
    // N/A
  }
  
  /**
   * Streams the image directly to the browser
   *
   * @param string $filename the name of the image file (ignored)
   * @param array  $options associative array, 'type' => jpeg|jpg|png, 'quality' => 0 - 100 (jpeg only)
   */
  function stream($filename, $options = null) {

    // Perform any antialiasing
    if ( $this->_aa_factor != 1 ) {
      $dst_w = $this->_width / $this->_aa_factor;
      $dst_h = $this->_height / $this->_aa_factor;
      $dst = imagecreatetruecolor($dst_w, $dst_h);
      imagecopyresampled($dst, $this->_img, 0, 0, 0, 0,
                         $dst_w, $dst_h,
                         $this->_width, $this->_height);
    } else {
      $dst = $this->_img;
    }

    if ( !isset($options["type"]) )
      $options["type"] = "png";

    $type = strtolower($options["type"]);
    
    header("Cache-Control: private");
    
    switch ($type) {

    case "jpg":
    case "jpeg":
      if ( !isset($options["quality"]) )
        $options["quality"] = 75;
      
      header("Content-type: image/jpeg");
      imagejpeg($dst, '', $options["quality"]);
      break;

    case "png":
    default:
      header("Content-type: image/png");
      imagepng($dst);
      break;
    }

    if ( $this->_aa_factor != 1 ) 
      imagedestroy($dst);
  }

  /**
   * Returns the PNG as a string
   *
   * @param array  $options associative array, 'type' => jpeg|jpg|png, 'quality' => 0 - 100 (jpeg only)
   * @return string
   */
  function output($options = null) {

    if ( $this->_aa_factor != 1 ) {
      $dst_w = $this->_width / $this->_aa_factor;
      $dst_h = $this->_height / $this->_aa_factor;
      $dst = imagecreatetruecolor($dst_w, $dst_h);
      imagecopyresampled($dst, $this->_img, 0, 0, 0, 0,
                         $dst_w, $dst_h,
                         $this->_width, $this->_height);
    } else {
      $dst = $this->_img;
    }
    
    if ( !isset($options["type"]) )
      $options["type"] = "png";

    $type = $options["type"];
    
    ob_start();

    switch ($type) {

    case "jpg":
    case "jpeg":
      if ( !isset($options["quality"]) )
        $options["quality"] = 75;
      
      imagejpeg($dst, '', $options["quality"]);
      break;

    case "png":
    default:
      imagepng($dst);
      break;
    }

    $image = ob_get_clean();

    if ( $this->_aa_factor != 1 )
      imagedestroy($dst);
    
    return $image;
  }
  
  
}
