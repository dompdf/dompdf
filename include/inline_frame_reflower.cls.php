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
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf

 */

/* $Id$ */

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
    $frame = $this->_frame;
    
  	// Check if a page break is forced
    $page = $frame->get_root();
    $page->check_forced_page_break($frame);
    
    if ( $page->is_full() )
      return;
      
    $style = $frame->get_style();
    
    // Generated content
    $this->_set_content();
    
    $frame->position();

    $cb = $frame->get_containing_block();

    // Add our margin, padding & border to the first and last children
    if ( ($f = $frame->get_first_child()) && $f instanceof Text_Frame_Decorator ) {
      $f_style = $f->get_style();
      $f_style->margin_left  = $style->margin_left;
      $f_style->padding_left = $style->padding_left;
      $f_style->border_left  = $style->border_left;
    }

    if ( ($l = $frame->get_last_child()) && $l instanceof Text_Frame_Decorator ) {
      $l_style = $l->get_style();
      $l_style->margin_right  = $style->margin_right;
      $l_style->padding_right = $style->padding_right;
      $l_style->border_right  = $style->border_right;
    }

    // Set the containing blocks and reflow each child.  The containing
    // block is not changed by line boxes.
    foreach ( $frame->get_children() as $child ) {
      $child->set_containing_block($cb);
      $child->reflow();
    }
  }
}
