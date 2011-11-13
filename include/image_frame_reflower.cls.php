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
 * Image reflower class
 *
 * @access private
 * @package dompdf
 */
class Image_Frame_Reflower extends Frame_Reflower {

  function __construct(Image_Frame_Decorator $frame) {
    parent::__construct($frame);
  }

  function reflow(Frame_Decorator $block = null) {
    $this->_frame->position();
    
    //FLOAT
    //$frame = $this->_frame;
    //$page = $frame->get_root();
    //if (DOMPDF_ENABLE_CSS_FLOAT && $frame->get_style()->float !== "none" ) {
    //  $page->add_floating_frame($this);
    //}
    // Set the frame's width
    $this->get_min_max_width();
    
    if ( $block ) {
      $block->add_frame_to_line($this->_frame);
    }
  }

  function get_min_max_width() {
    if (DEBUGPNG) {
      // Determine the image's size. Time consuming. Only when really needed?
      list($img_width, $img_height) = dompdf_getimagesize($this->_frame->get_image_url());
      print "get_min_max_width() ".
        $this->_frame->get_style()->width.' '.
        $this->_frame->get_style()->height.';'.
        $this->_frame->get_parent()->get_style()->width." ".
        $this->_frame->get_parent()->get_style()->height.";".
        $this->_frame->get_parent()->get_parent()->get_style()->width.' '.
        $this->_frame->get_parent()->get_parent()->get_style()->height.';'.
        $img_width. ' '.
        $img_height.'|' ;
    }

    $style = $this->_frame->get_style();

    //own style auto or invalid value: use natural size in px
    //own style value: ignore suffix text including unit, use given number as px
    //own style %: walk up parent chain until found available space in pt; fill available space
    //
    //special ignored unit: e.g. 10ex: e treated as exponent; x ignored; 10e completely invalid ->like auto

    $width = ($style->width > 0 ? $style->width : 0);
    if ( is_percent($width) ) {
      $t = 0.0;
      for ($f = $this->_frame->get_parent(); $f; $f = $f->get_parent()) {
        $f_style = $f->get_style();
        $t = $f_style->length_in_pt($f_style->width);
        if ($t != 0) {
          break;
        }
      }
      $width = ((float)rtrim($width,"%") * $t)/100; //maybe 0
    } elseif ( !mb_strpos($width, 'pt') ) {
      // Don't set image original size if "%" branch was 0 or size not given.
      // Otherwise aspect changed on %/auto combination for width/height
      // Resample according to px per inch
      // See also List_Bullet_Image_Frame_Decorator::__construct
      $width = $style->length_in_pt($width);
    }

    $height = ($style->height > 0 ? $style->height : 0);
    if ( is_percent($height) ) {
      $t = 0.0;
      for ($f = $this->_frame->get_parent(); $f; $f = $f->get_parent()) {
        $f_style = $f->get_style();
        $t = $f_style->length_in_pt($f_style->height);
        if ($t != 0) {
          break;
        }
      }
      $height = ((float)rtrim($height,"%") * $t)/100; //maybe 0
    } elseif ( !mb_strpos($height, 'pt') ) {
      // Don't set image original size if "%" branch was 0 or size not given.
      // Otherwise aspect changed on %/auto combination for width/height
      // Resample according to px per inch
      // See also List_Bullet_Image_Frame_Decorator::__construct
      $height = $style->length_in_pt($height);
    }

    if ($width == 0 || $height == 0) {
      // Determine the image's size. Time consuming. Only when really needed!
      list($img_width, $img_height) = dompdf_getimagesize($this->_frame->get_image_url());
      
      // don't treat 0 as error. Can be downscaled or can be catched elsewhere if image not readable.
      // Resample according to px per inch
      // See also List_Bullet_Image_Frame_Decorator::__construct
      if ($width == 0 && $height == 0) {
        $width = (float)($img_width * 72) / DOMPDF_DPI;
        $height = (float)($img_height * 72) / DOMPDF_DPI;
      } elseif ($height == 0 && $width != 0) {
        $height = ($width / $img_width) * $img_height; //keep aspect ratio
      } elseif ($width == 0 && $height != 0) {
        $width = ($height / $img_height) * $img_width; //keep aspect ratio
      }
    }

    if (DEBUGPNG) print $width.' '.$height.';';

    $style->width = $width . "pt";
    $style->height = $height . "pt";

    return array( $width, $width, "min" => $width, "max" => $width);
    
  }
}
