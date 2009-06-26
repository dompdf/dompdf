<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: list_bullet_frame_decorator.cls.php,v $
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
 * - bullet size proportional to font size, center position
 */

/* $Id: list_bullet_frame_decorator.cls.php,v 1.7 2006-10-26 17:07:23 benjcarson Exp $ */

/**
 * Decorates frames for list bullet rendering
 *
 * @access private
 * @package dompdf 
 */
class List_Bullet_Frame_Decorator extends Frame_Decorator {

  const BULLET_PADDING = 1; // Distance from bullet to text in pt
  // As fraction of font size (including descent). See also DECO_THICKNESS.
  const BULLET_THICKNESS = 0.04;   // Thickness of bullet outline. Screen: 0.08, print: better less, e.g. 0.04
  const BULLET_DESCENT = 0.3;  //descent of font below baseline. Todo: Guessed for now.
  const BULLET_SIZE = 0.35;   // bullet diameter. For now 0.5 of font_size without descent.
  
  static $BULLET_TYPES = array("disc", "circle", "square");
  
  //........................................................................

  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
  }
  
  function get_margin_width() {
    $style = $this->_frame->get_style();
    // Small hack to prevent extra indenting of list text on list_style_position == "inside"
    // and on suppressed bullet
    if ( $style->list_style_position == "outside" ||
         $style->list_style_type == "none" )
      return 0;
    return $style->get_font_size()*self::BULLET_SIZE + 2 * self::BULLET_PADDING;
  }

  //hits only on "inset" lists items, to increase height of box
  function get_margin_height() {
    return $this->_frame->get_style()->get_font_size()*self::BULLET_SIZE + 2 * self::BULLET_PADDING;
  }

  function get_width() {
    return $this->_frame->get_style()->get_font_size()*self::BULLET_SIZE + 2 * self::BULLET_PADDING;
  }
  
  function get_height() {
    return $this->_frame->get_style()->get_font_size()*self::BULLET_SIZE + 2 * self::BULLET_PADDING;
  }
  
  //........................................................................
}
?>