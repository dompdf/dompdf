<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: block_renderer.cls.php,v $
 * Created on: 2004-06-03
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
 * Renders block frames
 *
 * @access private
 * @package dompdf
 */
class Table_Row_Group_Renderer extends Block_Renderer {

  //........................................................................

  function render(Frame $frame) {
    $style = $frame->get_style(); 
    
    $this->_set_opacity( $frame->get_opacity( $style->opacity ) );

    $this->_render_border($frame);
    $this->_render_outline($frame);
    
    if (DEBUG_LAYOUT && DEBUG_LAYOUT_BLOCKS) {
      $this->_debug_layout($frame->get_border_box(), "red");
      if (DEBUG_LAYOUT_PADDINGBOX) {
        $this->_debug_layout($frame->get_padding_box(), "red", array(0.5, 0.5));
      }
    }
    
    if (DEBUG_LAYOUT && DEBUG_LAYOUT_LINES && $frame->get_decorator()) {
      foreach ($frame->get_decorator()->get_line_boxes() as $line) {
        $frame->_debug_layout(array($line->x, $line->y, $line->w, $line->h), "orange");
      }
    }
  }
}
