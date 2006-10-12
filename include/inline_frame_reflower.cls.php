<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: inline_frame_reflower.cls.php,v $
 * Created on: 2004-06-17
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

/* $Id: inline_frame_reflower.cls.php,v 1.4 2006-10-12 22:02:15 benjcarson Exp $ */

/**
 * Reflows inline frames
 *
 * @access private
 * @package dompdf
 */
class Inline_Frame_Reflower extends Frame_Reflower {

  function __construct(Frame $frame) { parent::__construct($frame); }
  
  //........................................................................

  function reflow() {
    $style = $this->_frame->get_style();
    $this->_frame->position();

    $cb = $this->_frame->get_containing_block();

    // Add our margin, padding & border to the first and last children
    if ( ($f = $this->_frame->get_first_child()) && $f instanceof Text_Frame_Decorator ) {
      $f->get_style()->margin_left = $style->margin_left;
      $f->get_style()->padding_left = $style->padding_left;
      $f->get_style()->border_left = $style->border_left;
    }

    if ( ($l = $this->_frame->get_last_child()) && $l instanceof Text_Frame_Decorator ) {
      $f->get_style()->margin_right = $style->margin_right;
      $f->get_style()->padding_right = $style->padding_right;
      $f->get_style()->border_right = $style->border_right;
    }

    // Set the containing blocks and reflow each child.  The containing
    // block is not changed by line boxes.
    foreach ( $this->_frame->get_children() as $child ) {
      
      $child->set_containing_block($cb);
      $child->reflow();
    }
  }
}
?>