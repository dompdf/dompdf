<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: absolute_positioner.cls.php,v $
 * Created on: 2004-06-08
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

/* $Id */

/**
 * Positions absolutly positioned frames
 */
class Absolute_Positioner extends Positioner {

  function __construct(Frame_Decorator $frame) { parent::__construct($frame); }

  function position() {

    $frame = $this->_frame;
    $style = $frame->get_style();
    $cb = $frame->get_containing_block();

    $top =    $style->length_in_pt($style->top,    $cb["h"]);
    $right =  $style->length_in_pt($style->right,  $cb["w"]);
    $bottom = $style->length_in_pt($style->bottom, $cb["h"]);
    $left =   $style->length_in_pt($style->left,   $cb["w"]);
    
    $p = $frame->find_positionned_parent();

    if ( $p ) {
      // Get the parent's padding box (see http://www.w3.org/TR/CSS21/visuren.html#propdef-top)
      list($x, $y, $w, $h) = $p->get_padding_box();
    } else {
      $x = $cb["x"];
      $y = $cb["y"];
    }

    if ( $top !== "auto" ) {
      $y += $top;
    } else if ( $bottom !== "auto" ) {
      // FIXME: need to know this frame's height before we can do this correctly
    }

    if ( $left !== "auto" ) {
      $x += $left;
    } else if ( $right !== "auto" ) {
      // FIXME: need to know this frame's width before we can do this correctly
    }

    $frame->set_position($x, $y);

  }
}