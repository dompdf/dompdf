<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: inline_frame_decorator.cls.php,v $
 * Created on: 2004-06-02
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
 * @version 20090610
 * - don't repeat non repeatable background images after a line break
 */

/* $Id: inline_frame_decorator.cls.php,v 1.5 2007-08-22 23:02:07 benjcarson Exp $ */

/**
 * Decorates frames for inline layout
 *
 * @access private
 * @package dompdf
 */
class Inline_Frame_Decorator extends Frame_Decorator {
  
  function __construct(Frame $frame, DOMPDF $dompdf) { parent::__construct($frame, $dompdf); }

  function split($frame = null) {

    if ( is_null($frame) ) {
      $this->get_parent()->split($this);
      return;
    }
    
    if ( $frame->get_parent() !== $this )
      throw new DOMPDF_Exception("Unable to split: frame is not a child of this one.");
        
    $split = $this->copy( $this->_frame->get_node()->cloneNode() ); 
    $this->get_parent()->insert_child_after($split, $this);

    // Unset the current node's right style properties
    $style = $this->_frame->get_style();
    $style->margin_right = "0";
    $style->padding_right = "0";
    $style->border_right_width = "0";

    // Unset the split node's left style properties since we don't want them
    // to propagate
    $style = $split->get_style();
    $style->margin_left = "0";
    $style->padding_left = "0";
    $style->border_left_width = "0";

    //On continuation of inline element on next line,
    //don't repeat non-vertically repeatble background images
    //See e.g. in testcase image_variants, long desriptions
    if ( ($url = $style->background_image) && $url !== "none"
         && ($repeat = $style->background_repeat) && $repeat != "repeat" &&  $repeat != "repeat-y"
       ) {
      $style->background_image = "none";
    }           

    // Add $frame and all following siblings to the new split node
    $iter = $frame;
    while ($iter) {
      $frame = $iter;      
      $iter = $iter->get_next_sibling();
      $frame->reset();
      $split->append_child($frame);
    }
  }
  
} 
?>