<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version $Id$
 */

/**
 * Reflows list bullets
 *
 * @access private
 * @package dompdf
 */
class List_Bullet_Frame_Reflower extends Frame_Reflower {

  function __construct(Frame_Decorator $frame) { parent::__construct($frame); }
    
  //........................................................................

  function reflow(Frame_Decorator $block = null) {
    $style = $this->_frame->get_style();

    $style->width = $this->_frame->get_width();
    $this->_frame->position();

    if ( $style->list_style_position === "inside" ) {
      $p = $this->_frame->find_block_parent();
      $p->add_frame_to_line($this->_frame);
    }

  }
}
