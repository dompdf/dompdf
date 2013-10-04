<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
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

    $top    = $style->length_in_pt($style->top,    $h);
    $right  = $style->length_in_pt($style->right,  $w);
    $bottom = $style->length_in_pt($style->bottom, $h);
    $left   = $style->length_in_pt($style->left,   $w);
    
    if ( $p && !($left === "auto" && $right === "auto") ) {
      // Get the parent's padding box (see http://www.w3.org/TR/CSS21/visuren.html#propdef-top)
      list($x, $y, $w, $h) = $p->get_padding_box();
    }
    
    list($width, $height) = array($frame->get_margin_width(), $frame->get_margin_height());
    
    $orig_style = $this->_frame->get_original_style();
    $orig_width = $orig_style->width;
    $orig_height = $orig_style->height;
    
    /****************************
    
    Width auto: 
    ____________| left=auto | left=fixed |
    right=auto  |     A     |     B      |
    right=fixed |     C     |     D      |
    
    Width fixed: 
    ____________| left=auto | left=fixed |
    right=auto  |     E     |     F      |
    right=fixed |     G     |     H      |
    
    *****************************/
    
    if ( $left === "auto" ) {
      if ( $right === "auto" ) {
        // A or E - Keep the frame at the same position
        $x = $x + $frame->find_block_parent()->get_current_line_box()->w;
      }
      else {
        if ( $orig_width === "auto" ) {
          // C
          $x += $w - $width - $right;
        }
        else {
          // G
          $x += $w - $width - $right;
        }
      }
    }
    else {
      if ( $right === "auto" ) {
        // B or F
        $x += $left;
      }
      else {
        if ( $orig_width === "auto" ) {
          // D - TODO change width
          $x += $left;
        }
        else {
          // H - Everything is fixed: left + width win
          $x += $left;
        }
      }
    }
    
    // The same vertically
    if ( $top === "auto" ) {
      if ( $bottom === "auto" ) {
        // A or E - Keep the frame at the same position
        $y = $frame->find_block_parent()->get_current_line_box()->y;
      }
      else {
        if ( $orig_height === "auto" ) {
          // C
          $y += $h - $height - $bottom;
        }
        else {
          // G
          $y += $h - $height - $bottom;
        }
      }
    }
    else {
      if ( $bottom === "auto" ) {
        // B or F
        $y += $top;
      }
      else {
        if ( $orig_height === "auto" ) {
          // D - TODO change height
          $y += $top;
        }
        else {
          // H - Everything is fixed: top + height win
          $y += $top;
        }
      }
    }
    
    $frame->set_position($x, $y);

  }
}