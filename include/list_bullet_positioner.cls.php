<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: list_bullet_positioner.cls.php,v $
 * Created on: 2004-06-23
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.digitaljunkies.ca/dompdf
 *
 * @link http://www.digitaljunkies.ca/dompdf
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf
 * @version 0.5.1
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 20090622
 * - try to adjust top position of bullet to top corner of subsequent font
 */

/* $Id: list_bullet_positioner.cls.php,v 1.3 2006-07-07 21:31:03 benjcarson Exp $ */

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
    $style = $this->_frame->get_style();
    
    // Note: this differs from most frames in that we must position
    // ourselves after determining our width
    $x = $cb["x"] - $this->_frame->get_width();

    $p = $this->_frame->find_block_parent();

    $y = $p->get_current_line("y");

    // This is a bit of a hack...
    $n = $this->_frame->get_next_sibling();
    if ( $n ) {
      $style = $n->get_style();
      $y += $style->length_in_pt( array($style->margin_top, $style->padding_top),
                                  $n->get_containing_block("w") );
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

    $style = $p->get_style();
    $font_size = $style->get_font_size();
    $line_height = $style->length_in_pt($style->line_height, $font_size);
    $y += ($line_height - $font_size) / 2;  	
	 
    //Position is x-end y-top of character position of the bullet.    
    $this->_frame->set_position($x, $y);
    
  }
}
?>