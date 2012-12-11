<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Positions block frames
 *
 * @access private
 * @package dompdf
 */
class Block_Positioner extends Positioner {


  function __construct(Frame_Decorator $frame) { parent::__construct($frame); }
  
  //........................................................................

  function position() {
    $frame = $this->_frame;
    $style = $frame->get_style();
    $cb = $frame->get_containing_block();
    $p = $frame->find_block_parent();
    
    if ( $p ) {
      $float = $style->float;

      $enable_css_float = $frame->get_dompdf()->get_option("enable_css_float");
      if ( !$enable_css_float || !$float || $float === "none" ) {
        $p->add_line(true);
      }
      $y = $p->get_current_line_box()->y;
      
    }
    else {
      $y = $cb["y"];
    }

    $x = $cb["x"];

    // Relative positionning
    if ( $style->position === "relative" ) {
      $top =    $style->length_in_pt($style->top,    $cb["h"]);
      //$right =  $style->length_in_pt($style->right,  $cb["w"]);
      //$bottom = $style->length_in_pt($style->bottom, $cb["h"]);
      $left =   $style->length_in_pt($style->left,   $cb["w"]);
      
      $x += $left;
      $y += $top;
    }
    
    $frame->set_position($x, $y);
  }
}
