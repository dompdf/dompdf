<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Decorates frames for image layout and rendering
 *
 * @access private
 * @package dompdf
 */
class Image_Frame_Decorator extends Frame_Decorator {

  /**
   * The path to the image file (note that remote images are
   * downloaded locally to DOMPDF_TEMP_DIR).
   *
   * @var string
   */
  protected $_image_url;
  
  /**
   * The image's file error message
   *
   * @var string
   */
  protected $_image_msg;

  /**
   * Class constructor
   *
   * @param Frame $frame the frame to decorate
   * @param DOMPDF $dompdf the document's dompdf object (required to resolve relative & remote urls)
   */
  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
    $url = $frame->get_node()->getAttribute("src");

    $debug_png = $dompdf->get_option("debug_png");
    if ($debug_png) print '[__construct '.$url.']';

    list($this->_image_url, /*$type*/, $this->_image_msg) = Image_Cache::resolve_url(
      $url,
      $dompdf->get_protocol(),
      $dompdf->get_host(),
      $dompdf->get_base_path(),
      $dompdf
    );

    if ( Image_Cache::is_broken($this->_image_url) &&
         $alt = $frame->get_node()->getAttribute("alt") ) {
      $style = $frame->get_style();
      $style->width  = (4/3)*Font_Metrics::get_text_width($alt, $style->font_family, $style->font_size, $style->word_spacing);
      $style->height = Font_Metrics::get_font_height($style->font_family, $style->font_size);
    }
  }

  /**
   * Return the image's url
   *
   * @return string The url of this image
   */
  function get_image_url() {
    return $this->_image_url;
  }

  /**
   * Return the image's error message
   *
   * @return string The image's error message
   */
  function get_image_msg() {
    return $this->_image_msg;
  }
  
}
