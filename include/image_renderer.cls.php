<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Image renderer
 *
 * @access private
 * @package dompdf
 */
class Image_Renderer extends Block_Renderer {

  function render(Frame $frame) {
    // Render background & borders
    $style = $frame->get_style();
    $cb = $frame->get_containing_block();
    list($x, $y, $w, $h) = $frame->get_border_box();
  
    $this->_set_opacity( $frame->get_opacity( $style->opacity ) );
    
    list($tl, $tr, $br, $bl) = $style->get_computed_border_radius($w, $h);
    
    $has_border_radius = $tl + $tr + $br + $bl > 0;
    
    if ( $has_border_radius ) {
      $this->_canvas->clipping_roundrectangle( $x, $y, $w, $h, $tl, $tr, $br, $bl );
    }

    if ( ($bg = $style->background_color) !== "transparent" ) {
      $this->_canvas->filled_rectangle($x, $y, $w, $h, $bg);
    }

    if ( ($url = $style->background_image) && $url !== "none" ) {
      $this->_background_image($url, $x, $y, $w, $h, $style);
    }
    
    if ( $has_border_radius ) {
      $this->_canvas->clipping_end();
    }
         
    $this->_render_border($frame);
    $this->_render_outline($frame);
    
    list($x, $y) = $frame->get_padding_box();
    
    $x += $style->length_in_pt($style->padding_left, $cb["w"]);
    $y += $style->length_in_pt($style->padding_top, $cb["h"]);
    
    $w = $style->length_in_pt($style->width, $cb["w"]);
    $h = $style->length_in_pt($style->height, $cb["h"]);
    
    if ( $has_border_radius ) {
      list($wt, $wr, $wb, $wl) = array(
        $style->border_top_width,
        $style->border_right_width,
        $style->border_bottom_width,
        $style->border_left_width,
      );
      
      // we have to get the "inner" radius
      if ( $tl > 0 ) {
        $tl -= ($wt + $wl) / 2;
      }
      if ( $tr > 0 ) {
        $tr -= ($wt + $wr) / 2;
      }
      if ( $br > 0 ) {
        $br -= ($wb + $wr) / 2;
      }
      if ( $bl > 0 ) {
        $bl -= ($wb + $wl) / 2;
      }
      
      $this->_canvas->clipping_roundrectangle( $x, $y, $w, $h, $tl, $tr, $br, $bl );
    }
    
    $src = $frame->get_image_url();
    $alt = null;

    if ( Image_Cache::is_broken($src) &&
      $alt = $frame->get_node()->getAttribute("alt") ) {
      $font = $style->font_family;
      $size = $style->font_size;
      $spacing = $style->word_spacing;
      $this->_canvas->text($x, $y, $alt,
                           $font, $size,
                           $style->color, $spacing);
    }
    else {
      $this->_canvas->image( $src, $x, $y, $w, $h, $style->image_resolution);
    }
    
    if ( $has_border_radius ) {
      $this->_canvas->clipping_end();
    }
    
    if ( $msg = $frame->get_image_msg() ) {
      $parts = preg_split("/\s*\n\s*/", $msg);
      $height = 10;
      $_y = $alt ? $y+$h-count($parts)*$height : $y;
      
      foreach($parts as $i => $_part) {
        $this->_canvas->text($x, $_y + $i*$height, $_part, "times", $height*0.8, array(0.5, 0.5, 0.5));
      }
    }
    
    if (DEBUG_LAYOUT && DEBUG_LAYOUT_BLOCKS) {
      $this->_debug_layout($frame->get_border_box(), "blue");
      if (DEBUG_LAYOUT_PADDINGBOX) {
        $this->_debug_layout($frame->get_padding_box(), "blue", array(0.5, 0.5));
      }
    }
  }
}
