<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: frame_reflower.cls.php,v $
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

/* $Id: frame_reflower.cls.php,v 1.7 2008-02-07 07:31:05 benjcarson Exp $ */

/**
 * Base reflower class
 *
 * Reflower objects are responsible for determining the width and height of
 * individual frames.  They also create line and page breaks as necessary.
 *
 * @access private
 * @package dompdf
 */
abstract class Frame_Reflower {

  /**
   * Frame for this reflower
   *
   * @var Frame
   */
  protected $_frame;

  /**
   * Cached min/max size
   *
   * @var array
   */
  protected $_min_max_cache;
  
  function __construct(Frame $frame) {
    $this->_frame = $frame;
    $this->_min_max_cache = null;
  }

  function dispose() {
    unset($this->_frame);
  }

  protected function _collapse_margins() {
    $cb = $this->_frame->get_containing_block();
    $style = $this->_frame->get_style();

    $t = $style->length_in_pt($style->margin_top, $cb["h"]);
    $b = $style->length_in_pt($style->margin_bottom, $cb["w"]);

    // Handle 'auto' values
    if ( $t === "auto" ) {
      $style->margin_top = "0pt";
      $t = 0;
    }

    if ( $b === "auto" ) {
      $style->margin_bottom = "0pt";
      $b = 0;
    }

    // Collapse vertical margins:
    $n = $this->_frame->get_next_sibling();
    while ( $n && !in_array($n->get_style()->display, Style::$BLOCK_TYPES) )
      $n = $n->get_next_sibling();

    if ( $n ) { // && !$n instanceof Page_Frame_Decorator ) {

      $b = max($b, $style->length_in_pt($n->get_style()->margin_top, $cb["w"]));

      $n->get_style()->margin_top = "$b pt";
      $style->margin_bottom = "0 pt";

    }

    // Collapse our first child's margin
    $f = $this->_frame->get_first_child();
    while ( $f && !in_array($f->get_style()->display, Style::$BLOCK_TYPES) )
      $f = $f->get_next_sibling();

    if ( $f ) {
      $t = max( $t, $style->length_in_pt($f->get_style()->margin_top, $cb["w"]));
      $style->margin_top = "$t pt";
      $f->get_style()->margin_top = "0 pt";
    }

  }

  // Returns true if a new page is required
  protected function _check_new_page() {
    $y = $this->_frame->get_position("y");
    $h = $style->length_in_pt($style->height);
    // Check if we need to move to a new page
    if ( $y + $h >= $this->_frame->get_root()->get_page_height() )
      return true;

  }

  //........................................................................

  abstract function reflow();

  //........................................................................

  // Required for table layout: Returns an array(0 => min, 1 => max, "min"
  // => min, "max" => max) of the minimum and maximum widths of this frame.
  // This provides a basic implementation.  Child classes should override
  // this if necessary.
  function get_min_max_width() {
    if ( !is_null($this->_min_max_cache) ) {
      return $this->_min_max_cache;
    }
    
    $style = $this->_frame->get_style();

    // Account for margins & padding
    $dims = array($style->padding_left,
                  $style->padding_right,
                  $style->border_left_width,
                  $style->border_right_width,
                  $style->margin_left,
                  $style->margin_right);

    $cb_w = $this->_frame->get_containing_block("w");
    $delta = $style->length_in_pt($dims, $cb_w);

    // Handle degenerate case
    if ( !$this->_frame->get_first_child() )
      return $this->_min_max_cache = array($delta, $delta,"min" => $delta, "max" => $delta);

    $low = array();
    $high = array();

    for ( $iter = $this->_frame->get_children()->getIterator();
          $iter->valid();
          $iter->next() ) {

      $inline_min = 0;
      $inline_max = 0;

      // Add all adjacent inline widths together to calculate max width
      while ( $iter->valid() && in_array( $iter->current()->get_style()->display, Style::$INLINE_TYPES ) ) {

        $child = $iter->current();

        $minmax = $child->get_min_max_width();

        if ( in_array( $iter->current()->get_style()->white_space, array("pre", "nowrap") ) )
          $inline_min += $minmax["min"];
        else
          $low[] = $minmax["min"];

        $inline_max += $minmax["max"];
        $iter->next();

      }

      if ( $inline_max > 0 )
        $high[] = $inline_max;

      if ( $inline_min > 0 )
        $low[] = $inline_min;

      if ( $iter->valid() ) {
        list($low[], $high[]) = $iter->current()->get_min_max_width();
        continue;
      }

    }
    $min = count($low) ? max($low) : 0;
    $max = count($high) ? max($high) : 0;

    // Use specified width if it is greater than the minimum defined by the
    // content.  If the width is a percentage ignore it for now.
    $width = $style->width;
    if ( $width !== "auto" && !is_percent($width) ) {
      $width = $style->length_in_pt($width, $cb_w);
      if ( $min < $width )
        $min = $width;
      if ( $max < $width )
        $max = $width;
    }

    $min += $delta;
    $max += $delta;
    return $this->_min_max_cache = array($min, $max, "min"=>$min, "max"=>$max);
  }

}

?>