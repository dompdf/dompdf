<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Decorates frames for list bullet rendering
 *
 * @access private
 * @package dompdf 
 */
class List_Bullet_Frame_Decorator extends Frame_Decorator {

  const BULLET_PADDING = 1; // Distance from bullet to text in pt
  // As fraction of font size (including descent). See also DECO_THICKNESS.
  const BULLET_THICKNESS = 0.04;   // Thickness of bullet outline. Screen: 0.08, print: better less, e.g. 0.04
  const BULLET_DESCENT = 0.3;  //descent of font below baseline. Todo: Guessed for now.
  const BULLET_SIZE = 0.35;   // bullet diameter. For now 0.5 of font_size without descent.
  
  static $BULLET_TYPES = array("disc", "circle", "square");
  
  //........................................................................

  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
  }
  
  function get_margin_width() {
    $style = $this->_frame->get_style();
    
    // Small hack to prevent extra indenting of list text on list_style_position === "inside"
    // and on suppressed bullet
    if ( $style->list_style_position === "outside" ||
         $style->list_style_type === "none" ) {
      return 0;
    }
    
    return $style->get_font_size() * self::BULLET_SIZE + 2 * self::BULLET_PADDING;
  }

  //hits only on "inset" lists items, to increase height of box
  function get_margin_height() {
    $style = $this->_frame->get_style();
    
    if ( $style->list_style_type === "none" ) {
      return 0;
    }
    
    return $style->get_font_size() * self::BULLET_SIZE + 2 * self::BULLET_PADDING;
  }

  function get_width() {
    return $this->get_margin_height();
  }
  
  function get_height() {
    return $this->get_margin_height();
  }
  
  //........................................................................
}
