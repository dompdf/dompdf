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
 * Positions fixely positioned frames
 */
class Fixed_Positioner extends Positioner {

  function __construct(Frame_Decorator $frame) { parent::__construct($frame); }

  function position() {

    $frame = $this->_frame;
    $style = $frame->get_original_style();
    $root = $frame->get_root();
    $initialcb = $root->get_containing_block();
    $initialcb_style = $root->get_style();

    $p = $frame->find_block_parent();
    if ( $p ) {
      $p->add_line();
    }

    // Compute the margins of the @page style
    $margin_top    = $initialcb_style->length_in_pt($initialcb_style->margin_top,    $initialcb["h"]);
    $margin_right  = $initialcb_style->length_in_pt($initialcb_style->margin_right,  $initialcb["w"]);
    $margin_bottom = $initialcb_style->length_in_pt($initialcb_style->margin_bottom, $initialcb["h"]);
    $margin_left   = $initialcb_style->length_in_pt($initialcb_style->margin_left,   $initialcb["w"]);
    
    // The needed computed style of the element
    $height = $style->length_in_pt($style->height, $initialcb["h"]);
    $width  = $style->length_in_pt($style->width,  $initialcb["w"]);
    
    $top    = $style->length_in_pt($style->top,    $initialcb["h"]);
    $right  = $style->length_in_pt($style->right,  $initialcb["w"]);
    $bottom = $style->length_in_pt($style->bottom, $initialcb["h"]);
    $left   = $style->length_in_pt($style->left,   $initialcb["w"]);

    $y = $margin_top;
    if ( isset($top) ) {
      $y = $top + $margin_top;
      if ( $top === "auto" ) {
        $y = $margin_top;
        if ( isset($bottom) && $bottom !== "auto" ) {
          $y = $initialcb["h"] - $bottom - $margin_bottom;
          $margin_height = $this->_frame->get_margin_height();
          if ( $margin_height !== "auto" ) {
            $y -= $margin_height;
          } else {
            $y -= $height;
          }
        }
      }
    }

    $x = $margin_left;
    if ( isset($left) ) {
      $x = $left + $margin_left;
      if ( $left === "auto" ) {
        $x = $margin_left;
        if ( isset($right) && $right !== "auto" ) {
          $x = $initialcb["w"] - $right - $margin_right;
          $margin_width = $this->_frame->get_margin_width();
          if ( $margin_width !== "auto" ) {
            $x -= $margin_width;
          } else {
            $x -= $width;
          }
        }
      }
    }
    
    $frame->set_position($x, $y);

    $children = $frame->get_children();
    foreach($children as $child) {
      $child->set_position($x, $y);
    }
  }
}