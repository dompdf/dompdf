<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: page_frame_decorator.cls.php,v $
 * Created on: 2004-06-15
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

/* $Id: page_frame_decorator.cls.php,v 1.3 2005-02-05 17:32:04 benjcarson Exp $ */

/**
 * Decorates frames for page layout
 *
 * @access private
 * @package dompdf
 */
class Page_Frame_Decorator extends Frame_Decorator {
  
  /**
   * y value of bottom page margin
   *
   * @var float
   */
  protected $_bottom_page_margin;
  
  /**
   * Flag indicating page is full.
   *
   * @var bool
   */
  protected $_page_full;
  
  //........................................................................

  /**
   * Class constructor
   *
   * @param Frame $frame the frame to decorate
   */
  function __construct(Frame $frame) {
    parent::__construct($frame);
    $this->_page_full = false;
    $this->_bottom_page_margin = null;
  }
  
  //........................................................................

  /**
   * Set the frame's containing block.  Overridden to set $this->_bottom_page_margin.
   *
   * @param float $x
   * @param float $y
   * @param float $w
   * @param float $h
   */
  function set_containing_block($x = null, $y = null, $w = null, $h = null) {
    parent::set_containing_block($x,$y,$w,$h);
    $w = $this->get_containing_block("w");
    if ( isset($h) )
      $this->_bottom_page_margin = $h; // - $this->_frame->get_style()->length_in_pt($this->_frame->get_style()->margin_bottom, $w);
  }

  //........................................................................

  /**
   * Returns true if the page is full and is no longer accepting frames.
   *
   * @return bool
   */
  function is_page_full() {
    return $this->_page_full;
  }

  //........................................................................

  /**
   * Check if a forced page break is required before $frame.  This uses the
   * frame's page_break_before property as well as the preceeding frame's
   * page_break_after property.
   *
   * @link http://www.w3.org/TR/CSS21/page.html#forced
   *
   * @param Frame $frame the frame to check
   * @return bool true if a page break occured
   */
  function check_forced_page_break(Frame $frame) {

    $block_types = array("block", "list-item", "table");
    $page_breaks = array("always", "left", "right");
    
    $style = $frame->get_style();

    if ( !in_array($style->display, $block_types) )
      return;

    // Find the previous block-level sibling
    $prev = $frame->get_prev_sibling();
    while ( $prev && !in_array($prev->get_style()->display, $block_types) )
      $prev = $prev->get_prev_sibling();

    if ( in_array($style->page_break_before, $page_breaks)
         or
         ($prev && in_array($prev->get_style()->page_break_after, $page_breaks)) ) {
      $frame->split();
      $this->_page_full = true;
      return;
    }
    
  }

  /**
   * Determine if a page break is allowed before $frame
   *
   * @param Frame $frame the frame to check
   * @return bool true if a break is allowed, false otherwise
   */
  protected function _page_break_allowed(Frame $frame) {
    /**
     *
     * http://www.w3.org/TR/CSS21/page.html#allowed-page-breaks
     * /*
     * In the normal flow, page breaks can occur at the following places:
     * 
     *    1. In the vertical margin between block boxes. When a page
     *    break occurs here, the used values of the relevant
     *    'margin-top' and 'margin-bottom' properties are set to '0'.
     *    2. Between line boxes inside a block box.
     * 
     * These breaks are subject to the following rules:
     * 
     *   * Rule A: Breaking at (1) is allowed only if the
     *     'page-break-after' and 'page-break-before' properties of
     *     all the elements generating boxes that meet at this margin
     *     allow it, which is when at least one of them has the value
     *     'always', 'left', or 'right', or when all of them are
     *     'auto'.
     *
     *   * Rule B: However, if all of them are 'auto' and the
     *     nearest common ancestor of all the elements has a
     *     'page-break-inside' value of 'avoid', then breaking here is
     *     not allowed.
     *
     *   * Rule C: Breaking at (2) is allowed only if the number of
     *     line boxes between the break and the start of the enclosing
     *     block box is the value of 'orphans' or more, and the number
     *     of line boxes between the break and the end of the box is
     *     the value of 'widows' or more.
     *
     *   * Rule D: In addition, breaking at (2) is allowed only if
     *     the 'page-break-inside' property is 'auto'.
     * 
     * If the above doesn't provide enough break points to keep
     * content from overflowing the page boxes, then rules B and D are
     * dropped in order to find additional breakpoints.
     * 
     * If that still does not lead to sufficient break points, rules A
     * and C are dropped as well, to find still more break points.
     */

    $block_types = array("block", "list-item", "table");

    // Block Frames (1):
    if ( in_array($frame->get_style(), $block_types) ) {
      
      // Rules A & B
      
      if ( $frame->get_style()->page_break_before == "avoid" )
        return false;
      
      // Find the preceeding block-level sibling
      $prev = $frame->get_prev_sibling();
      while ( $prev && !in_array($prev->get_style()->display, $block_types) )
        $prev = $prev->get_prev_sibling();

      // Does the previous element allow a page break after?
      if ( $prev && $prev->get_style()->page_break_after == "avoid" )
        return false;

      // If both $prev & $frame have the same parent, check the parent's
      // page_break_inside property.
      $parent = $frame->get_parent();
      if ( $prev && $parent && $parent->get_style()->page_break_inside == "avoid" )
        return false;

      // If the frame is the first block-level frame, use the value from
      // $frame's parent instead.    
      if ( !$prev && $parent ) 
        return $this->page_break_allowed( $parent );
        
      return true;
    
    }

    // Inline frames (2):    
    else if ( in_array($frame->get_style(), Style::$INLINE_TYPES) ) {
      
      // Rule C
      $block_parent = $frame->find_block_parent();
      if ( count($block_parent->get_lines() ) < $frame->get_style()->orphans )
        return false;

      // FIXME: Checking widows is tricky without having laid out the
      // remaining line boxes.  Just ignore it for now...

      // Rule D
      if ( $block_parent->get_style()->page_break_inside == "avoid" )
        return false;

      return true;
            
    } else {
      // FIXME: handle tables here?
      return false;
    }
  
  }
  
  /**
   * Check if $frame will fit on the page.  If the frame does not fit,
   * the frame tree is modified so that a page break occurs in the
   * correct location.
   *
   * @param Frame $frame the frame to check
   * @return Frame the frame following the page break
   */
  function check_page_break(Frame $frame) {
    
    // Determine the frame's maximum y value
    $max_y = $frame->get_position("y") + $frame->get_margin_height();

    // If a split is to occur here, then the bottom margins & paddings all
    // parents of $frame must fit on the page as well:
    $p = $frame->get_parent();
    while ( $p ) {
      $style = $p->get_style();
      $max_y += $style->get_length_in_pt(array($style->margin_bottom,
                                               $style->padding_bottom,
                                               $style->border_bottom_width));
      $p = $p->get_parent();
    }

    
    // Check if $frame flows off the page    
    if ( $max_y <= $this->_bottom_page_margin ) 
      // no: do nothing (?)
      return;
    
    // yes: can we split here?
    if ( $this->_page_break_allowed($frame) ) {
      $frame->split();
      $this->_page_full = true;
      return;
      
    } else {
      // backtrack until we can find a suitable place to split.
      $iter = $frame->get_prev_sibling();

      while ( $iter ) {
        
        if ( $this->_page_break_allowed($iter) ) {
          $iter->split();
          $this->_page_full = true;
          return;
        }

        if ( $next = $iter->get_last_child() ) {
          $iter = $next;
          continue;
        }

        if ( $next = $iter->get_prev_sibling() ) {
          $iter = $next;
          continue;
        }

        $parent = $this->get_parent();
        if ( $parent && $next = $parent->get_prev_sibling() ) {
          $iter = $next;
          continue;
        }

        break;
        
      }
      // No valid page break found.  Just break at $frame.
      $frame->split();
      $this->_page_full = true;
      return $frame;
    }
    
  }
  
  //........................................................................

  function split($frame = null) {
    if ( !is_null($frame) )
      $frame->reset();
  }

}
?>