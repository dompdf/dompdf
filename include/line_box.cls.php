<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: line_box.cls.php,v $
 * Created on: 2011-03-27
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
 * The line box class
 *
 * This class represents a line box
 * http://www.w3.org/TR/CSS2/visuren.html#line-box
 *
 * @access protected
 * @package dompdf
 */
class Line_Box {

  /**
   * @var Block_Frame_Decorator
   */
  protected $_block_frame;

  /**
   * @var array
   */
  protected $_frames = array();
  
  /**
   * @var integer
   */
  public $wc = 0;
  
  /**
   * @var float
   */
  public $y = null;
  
  /**
   * @var float
   */
  public $w = 0.0;
  
  /**
   * @var float
   */
  public $h = 0.0;
  
  /**
   * @var float
   */
  public $left = 0.0;
  
  /**
   * @var float
   */
  public $right = 0.0;
  
  /**
   * @var Frame
   */
  public $tallest_frame = null;
  
  public $floating_blocks = array();
  
  /**
   * @var bool
   */
  public $br = false;
  
  /**
   * Class constructor
   *
   * @param Block_Frame_Decorator $frale the Block_Frame_Decorator containing this line
   */
  function __construct(Block_Frame_Decorator $frame, $y = 0) {
    $this->_block_frame = $frame;
    $this->_frames = array();
    $this->y = $y;
    
    $this->get_float_offsets();
  }
  
  function get_float_offsets() {
    $reflower = $this->_block_frame->get_reflower();
    
    if ( !$reflower ) return;
    
    $floating_children = $reflower->get_floating_children();
    
    if ( DOMPDF_ENABLE_CSS_FLOAT && !empty($floating_children) ) {
      foreach ( $floating_children as $child_key => $floating_child ) {
        $id = $floating_child->get_id();
        
        if ( isset($this->floating_blocks[$id]) ) continue;
        
        $float = $floating_child->get_style()->float;
        
        $floating_width = $floating_child->get_margin_width();
        
        // If the child is still shifted by the floating element
        if ( $floating_child->get_position("y") + $floating_child->get_margin_height() > $this->y ) {
          if ( $float === "left" )
            $this->left  += $floating_width;
          else
            $this->right += $floating_width;
            
          $this->floating_blocks[$id] = true;
        }
        
        // else, the floating element won't shift anymore
        else {
          $reflower->remove_floating_child($child_key);
        }
      }
    }
  }

  /**
   * @return Block_Frame_Decorator
   */
  function get_block_frame() { return $this->_block_frame; }

  /**
   * @return array
   */
  function &get_frames() { return $this->_frames; }
  
  function add_frame(Frame $frame) {
    $this->_frames[] = $frame;
  }
  
  function __toString(){
    $props = array("wc", "y", "w", "h", "left", "right", "br");
    $s = "";
    foreach($props as $prop) {
      $s .= "$prop: ".$this->$prop."\n";
    }
    $s .= count($this->_frames)." frames\n";
    return $s;
  }
  /*function __get($prop) {
    if (!isset($this->{"_$prop"})) return;
    return $this->{"_$prop"};
  }*/
}

/*
class LineBoxList implements Iterator {
  private $_p = 0;
  private $_lines = array();
  
}
*/
