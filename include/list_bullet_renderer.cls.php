<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: list_bullet_renderer.cls.php,v $
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
 * @version 0.3
 */

/* $Id: list_bullet_renderer.cls.php,v 1.1.1.1 2005-01-25 22:56:02 benjcarson Exp $ */

/**
 * Renders list bullets
 *
 * @access private
 * @package dompdf
 */
class List_Bullet_Renderer extends Abstract_Renderer {
  
  function __construct(Canvas $canvas) {
    parent::__construct($canvas);
  }
  
  //........................................................................

  function render(Frame $frame) {

    $style = $frame->get_style();

    $bullet_style = $style->list_style_type;
    $bullet_size = List_Bullet_Frame_Decorator::BULLET_SIZE;
    $line_height = $style->length_in_pt($style->line_height, $frame->get_containing_block("w"));

    $fill = false;
    
    switch ($bullet_style) {
      
    default:
    case "disc":
      $fill = true;

    case "circle":
      if ( !$fill )
        $fill = false;
      
      list($x,$y) = $frame->get_position();
      $x += $bullet_size /2;
      $y += $line_height /2 + $bullet_size / 4;
      $r = $bullet_size / 4;
      $this->_canvas->circle($x, $y, $r, $style->color, null, null, $fill);
      break;

    case "square":
      list($x, $y) = $frame->get_position();
      $w = $bullet_size / 2;
      $x += $bullet_size / 2 - $w / 2;
      $y += $line_height / 2;
      $this->_canvas->filled_rectangle($x, $y, $w, $w, $style->color);
      break;

    }
  }
}
?>