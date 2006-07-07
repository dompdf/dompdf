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
 * @package dompdf
 * @version 0.5.1
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
    
    $this->_frame->set_position($x, $y);
    
  }
}
?>