<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version $Id$
 */

/**
 * Positions absolutely positioned frames
 */
class Absolute_Positioner extends Positioner {

  function __construct(Frame_Decorator $frame) { parent::__construct($frame); }

  function position() {

    $frame = $this->_frame;
    $style = $frame->get_style();
    
    $p = $frame->find_positionned_parent();
    
    list($x, $y, $w, $h) = $frame->get_containing_block();
    
    if ( $p ) {
      // Get the parent's padding box (see http://www.w3.org/TR/CSS21/visuren.html#propdef-top)
      list($x, $y) = $p->get_padding_box();
    }

    $top    = $style->length_in_pt($style->top,    $h);
    $right  = $style->length_in_pt($style->right,  $w);
    $bottom = $style->length_in_pt($style->bottom, $h);
    $left   = $style->length_in_pt($style->left,   $w);
    
    list($width, $height) = array($frame->get_margin_width(), $frame->get_margin_height());
    
    $orig_style = $this->_frame->get_original_style();
    $orig_width = $orig_style->width;
    $orig_height = $orig_style->height;
    
    if ( $left !== "auto" ) {
      $x += $left;
    }
    elseif ( $right !== "auto" && $orig_width === "auto" ) {
      $x += $w - $width - $right;
    }
    
    if ( $top !== "auto" ) {
      $y += $top;
    }
    elseif ( $bottom !== "auto" && $orig_height === "auto" ) {
      $y += $h - $height - $bottom;
    }

    $frame->set_position($x, $y);

  }
}