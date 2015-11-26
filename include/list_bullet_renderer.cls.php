<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Renders list bullets
 *
 * @access private
 * @package dompdf
 */
class List_Bullet_Renderer extends Abstract_Renderer {
  static function get_counter_chars($type) {
    static $cache = array();
    
    if ( isset($cache[$type]) ) {
      return $cache[$type];
    }
    
    $uppercase = false;
    $text = "";
    
    switch ($type) {
      case "decimal-leading-zero":
      case "decimal":
      case "1":
        return "0123456789";
      
      case "upper-alpha":
      case "upper-latin":
      case "A":
        $uppercase = true;
      case "lower-alpha":
      case "lower-latin":
      case "a":
        $text = "abcdefghijklmnopqrstuvwxyz";
        break;
        
      case "upper-roman":
      case "I":
        $uppercase = true;
      case "lower-roman":
      case "i":
        $text = "ivxlcdm";
        break;
      
      case "lower-greek":
        for($i = 0; $i < 24; $i++) {
          $text .= unichr($i+944);
        }
        break;
    }
    
    if ( $uppercase ) {
      $text = strtoupper($text);
    }
    
    return $cache[$type] = "$text.";
  }

  /**
   * @param integer $n
   * @param string  $type
   * @param integer $pad
   *
   * @return string
   */
  private function make_counter($n, $type, $pad = null){
    $n = intval($n);
    $text = "";
    $uppercase = false;
    
    switch ($type) {
      case "decimal-leading-zero":
      case "decimal":
      case "1":
        if ($pad) 
          $text = str_pad($n, $pad, "0", STR_PAD_LEFT);
        else 
          $text = $n;
        break;
      
      case "upper-alpha":
      case "upper-latin":
      case "A":
        $uppercase = true;
      case "lower-alpha":
      case "lower-latin":
      case "a":
        $text = chr( ($n % 26) + ord('a') - 1);
        break;
        
      case "upper-roman":
      case "I":
        $uppercase = true;
      case "lower-roman":
      case "i":
        $text = dec2roman($n);
        break;
      
      case "lower-greek":
        $text = unichr($n + 944);
        break;
    }
    
    if ( $uppercase ) {
      $text = strtoupper($text);
    }
    
    return "$text.";
  }
  
  function render(Frame $frame) {
    $style = $frame->get_style();
    $font_size = $style->get_font_size();
    $line_height = $style->length_in_pt($style->line_height, $frame->get_containing_block("w"));

    $this->_set_opacity( $frame->get_opacity( $style->opacity ) );

    $li = $frame->get_parent();

    // Don't render bullets twice if if was split
    if ($li->_splitted) {
      return;
    }

    // Handle list-style-image
    // If list style image is requested but missing, fall back to predefined types
    if ( $style->list_style_image !== "none" &&
         !Image_Cache::is_broken($img = $frame->get_image_url())) {

      list($x,$y) = $frame->get_position();
      
      //For expected size and aspect, instead of box size, use image natural size scaled to DPI.
      // Resample the bullet image to be consistent with 'auto' sized images
      // See also Image_Frame_Reflower::get_min_max_width
      // Tested php ver: value measured in px, suffix "px" not in value: rtrim unnecessary.
      //$w = $frame->get_width();
      //$h = $frame->get_height();
      list($width, $height) = dompdf_getimagesize($img, $this->_dompdf->get_http_context());
      $dpi = $this->_dompdf->get_option("dpi");
      $w = ((float)rtrim($width, "px") * 72) / $dpi;
      $h = ((float)rtrim($height, "px") * 72) / $dpi;
      
      $x -= $w;
      $y -= ($line_height - $font_size)/2; //Reverse hinting of list_bullet_positioner

      $this->_canvas->image( $img, $x, $y, $w, $h);

    } else {

      $bullet_style = $style->list_style_type;

      $fill = false;

      switch ($bullet_style) {

      default:
      case "disc":
        $fill = true;

      case "circle":
        list($x,$y) = $frame->get_position();
        $r = ($font_size*(List_Bullet_Frame_Decorator::BULLET_SIZE /*-List_Bullet_Frame_Decorator::BULLET_THICKNESS*/ ))/2;
        $x -= $font_size*(List_Bullet_Frame_Decorator::BULLET_SIZE/2);
        $y += ($font_size*(1-List_Bullet_Frame_Decorator::BULLET_DESCENT))/2;
        $o = $font_size*List_Bullet_Frame_Decorator::BULLET_THICKNESS;
        $this->_canvas->circle($x, $y, $r, $style->color, $o, null, $fill);
        break;

      case "square":
        list($x, $y) = $frame->get_position();
        $w = $font_size*List_Bullet_Frame_Decorator::BULLET_SIZE;
        $x -= $w;
        $y += ($font_size*(1-List_Bullet_Frame_Decorator::BULLET_DESCENT-List_Bullet_Frame_Decorator::BULLET_SIZE))/2;
        $this->_canvas->filled_rectangle($x, $y, $w, $w, $style->color);
        break;
    
      case "decimal-leading-zero":
      case "decimal":
      case "lower-alpha":
      case "lower-latin":
      case "lower-roman":
      case "lower-greek":
      case "upper-alpha":
      case "upper-latin":
      case "upper-roman":
      case "1": // HTML 4.0 compatibility
      case "a":
      case "i":
      case "A":
      case "I":
        $pad = null;
        if ( $bullet_style === "decimal-leading-zero" ) {
          $pad = strlen($li->get_parent()->get_node()->getAttribute("dompdf-children-count"));
        }

        $node = $frame->get_node();

        if ( !$node->hasAttribute("dompdf-counter") ) {
          return;
        }

        $index = $node->getAttribute("dompdf-counter");
        $text = $this->make_counter($index, $bullet_style, $pad);

        if ( trim($text) == "" ) {
          return;
        }
        
        $spacing = 0;
        $font_family = $style->font_family;
        
        $line = $li->get_containing_line();
        list($x, $y) = array($frame->get_position("x"), $line->y);

        $x -= Font_Metrics::get_text_width($text, $font_family, $font_size, $spacing);
        
        // Take line-height into account
        $line_height = $style->line_height;
        $y += ($line_height - $font_size) / 4; // FIXME I thought it should be 2, but 4 gives better results
        
        $this->_canvas->text($x, $y, $text,
                             $font_family, $font_size,
                             $style->color, $spacing);
      
      case "none":
        break;
      }
    }
  }
}
