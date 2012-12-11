<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Positions list bullets
 *
 * @access private
 * @package dompdf
 */
class List_Bullet_Positioner extends Positioner {

  function __construct(Frame_Decorator $frame) { parent::__construct($frame); }
  
  //........................................................................

  function position() {
    
    // Bullets & friends are positioned an absolute distance to the left of
    // the content edge of their parent element
    $cb = $this->_frame->get_containing_block();
    
    // Note: this differs from most frames in that we must position
    // ourselves after determining our width
    $x = $cb["x"] - $this->_frame->get_width();

    $p = $this->_frame->find_block_parent();

    $y = $p->get_current_line_box()->y;

    // This is a bit of a hack...
    $n = $this->_frame->get_next_sibling();
    if ( $n ) {
      $style = $n->get_style();
      $line_height = $style->length_in_pt($style->line_height, $style->get_font_size());
      $offset = $style->length_in_pt($line_height, $n->get_containing_block("h")) - $this->_frame->get_height();             
      $y += $offset / 2;
    }

  // Now the position is the left top of the block which should be marked with the bullet.
  // We tried to find out the y of the start of the first text character within the block.
  // But the top margin/padding does not fit, neither from this nor from the next sibling
  // The "bit of a hack" above does not work also.
  
  // Instead let's position the bullet vertically centered to the block which should be marked.
  // But for get_next_sibling() the get_containing_block is all zero, and for find_block_parent()
  // the get_containing_block is paper width and the entire list as height.
  
    // if ($p) {
    //   //$cb = $n->get_containing_block();
    //   $cb = $p->get_containing_block();
    //   $y += $cb["h"]/2;
    // print 'cb:'.$cb["x"].':'.$cb["y"].':'.$cb["w"].':'.$cb["h"].':';
    // }   

  // Todo:
  // For now give up on the above. Use Guesswork with font y-pos in the middle of the line spacing

    /*$style = $p->get_style();
    $font_size = $style->get_font_size();
    $line_height = $style->length_in_pt($style->line_height, $font_size);
    $y += ($line_height - $font_size) / 2;    */
   
    //Position is x-end y-top of character position of the bullet.    
    $this->_frame->set_position($x, $y);
    
  }
}
