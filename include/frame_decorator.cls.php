<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: frame_decorator.cls.php,v $
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
 * @package dompdf
 * @version 0.3
 */

/* $Id: frame_decorator.cls.php,v 1.1.1.1 2005-01-25 22:56:02 benjcarson Exp $ */

/**
 * Base Frame_Decorator class
 *
 * @access private
 * @package dompdf
 */
abstract class Frame_Decorator extends Frame {
  
  // protected members
  protected $_root;
  protected $_frame;
  protected $_positioner;
  protected $_reflower;
  
  //........................................................................

  function __construct(Frame $frame) {
    $this->_frame = $frame;
    $this->_root = null;
    $frame->set_decorator($this);
  }

  //........................................................................

  // Return a copy of this frame with $node as its node
  function copy(DomNode $node) {
    $frame = new Frame($node);    
    $frame->set_style(clone $this->_frame->get_original_style());
    $deco = Frame_Factory::decorate_frame($frame);
    $deco->set_root($this->_root);
    return $deco;
  }

  //........................................................................
  
  // Delegate calls to decorated frame object
  function reset() {
    $this->_frame->reset();

    // Reset all children
    foreach ($this->get_children() as $child)
      $child->reset();

  }
  
  function get_node() { return $this->_frame->get_node(); }
  function get_id() { return $this->_frame->get_id(); }
  function get_style() { return $this->_frame->get_style(); }
  function get_original_style() { return $this->_frame->get_original_style(); }
  function get_containing_block($i = null) { return $this->_frame->get_containing_block($i); }
  function get_position($i = null) { return $this->_frame->get_position($i); }
  function get_decorator() { return $this; }

  function get_margin_height() { return $this->_frame->get_margin_height(); }
  function get_margin_width() { return $this->_frame->get_margin_width(); }
  function get_padding_box() { return $this->_frame->get_padding_box(); }
  function get_border_box() { return $this->_frame->get_border_box(); }

  function set_id($id) { $this->_frame->set_id($id); }
  function set_style(Style $style) { $this->_frame->set_style($style); }

  function set_containing_block($x = null, $y = null, $w = null, $h = null) {
    $this->_frame->set_containing_block($x, $y, $w, $h);
  }

  function set_position($x = null, $y = null) {
    $this->_frame->set_position($x, $y);
  }
  function __toString() { return $this->_frame->__toString(); }
  
  function prepend_child(Frame $child, $update_node = true) {
    if ( $child instanceof Frame_Decorator )
      $child = $child->_frame;
    
    $this->_frame->prepend_child($child, $update_node);
  }

  function append_child(Frame $child, $update_node = true) {
    if ( $child instanceof Frame_Decorator )
      $child = $child->_frame;

    $this->_frame->append_child($child, $update_node);
  }

  function insert_child_before(Frame $new_child, Frame $ref, $update_node = true) {
    if ( $new_child instanceof Frame_Decorator )
      $new_child = $new_child->_frame;

    if ( $ref instanceof Frame_Decorator )
      $ref = $ref->_frame;

    $this->_frame->insert_child_before($new_child, $ref, $update_node);
  }

  function insert_child_after(Frame $new_child, Frame $ref, $update_node = true) {
    if ( $new_child instanceof Frame_Decorator )
      $new_child = $new_child->_frame;

    if ( $ref instanceof Frame_Decorator )
      $ref = $ref->_frame;

    $this->_frame->insert_child_after($new_child, $ref, $update_node);
  }

  function remove_child(Frame $child, $update_node = true) {
    if ( $child instanceof Frame_Decorator )
      $child = $new_child->_frame;

    $this->_frame->remove_child($child, $update_node);
  }
  
  //........................................................................

  function get_parent() {
    $p = $this->_frame->get_parent();
    return !is_null($p) ? $p->get_decorator() : null;
  }

  function get_first_child() {
    $c = $this->_frame->get_first_child();
    return !is_null($c) ? $c->get_decorator() : null;
  }

  function get_last_child() {
    $c = $this->_frame->get_last_child();
    return !is_null($c) ? $c->get_decorator() : null;
  }

  function get_prev_sibling() {
    $s = $this->_frame->get_prev_sibling();
    return !is_null($s) ? $s->get_decorator() : null;
  }
  
  function get_next_sibling() {
    $s = $this->_frame->get_next_sibling();
    return !is_null($s) ? $s->get_decorator() : null;
  }

  function get_children() {
    return new FrameList($this);
  }

  function get_subtree() {
    return new FrameTreeList($this);
  }
  
  //........................................................................

  function set_positioner(Positioner $posn) { $this->_positioner = $posn; }
  
  //........................................................................

  function set_reflower(Frame_Reflower $reflower) { $this->_reflower = $reflower; }
  function get_reflower() { return $this->_reflower; }

  //........................................................................
  
  function set_root(Frame $root) { $this->_root = $root; }
  function get_root() { return $this->_root; }
  
  //........................................................................

  function find_block_parent() {

    // Find our nearest block level parent
    $p = $this->get_parent();

    while ( $p ) {
      if ( in_array($p->get_style()->display, Style::$BLOCK_TYPES) )
        break;

      $p = $p->get_parent();
    }

    return $p;
  }

  //........................................................................

  // Returns true if this frame is the first on the page
  function is_first_on_page() {

    if ( $this instanceof Page_Frame_Decorator )
      return null;
    
    $iter = $this;
    while ( $iter ) {
      if ( $iter->get_parent() instanceof Page_Frame_Decorator )
        return true;
      
      if ( $iter->get_prev_sibling() ) 
        return false;
      
      $iter = $iter->get_parent();
    }

    return true;
  }
  
  //........................................................................

  /**
   * Split this frame at $child.
   *
   * The current frame is cloned and $child and all children following
   * $child are added to the clone.  The clone is then passed to the
   * current frame's parent->split() method.
   *
   * @param Frame $child
   */
  function split(Frame $child) {
    
    if ( $child->get_parent() !== $this )
      throw new DOMPDF_Exception("Unable to split: frame is not a child of this one.");
        
    $split = $this->copy( $this->_frame->get_node()->cloneNode() ); 
    $this->get_parent()->insert_child_after($split, $this);

    // Add $frame and all following siblings to the new split node
    $iter = $child;
    while ($iter) {
      $frame = $iter;      
      $iter = $iter->get_next_sibling();
      $frame->reset();
      $split->append_child($frame);
    }

    $this->get_parent()->split($split);
  }

  //........................................................................

  final function position() { $this->_positioner->position();  }
  
  final function reflow() {
    // Uncomment this to see the frames before they're laid out, instead of
    // during rendering.
    //echo $this->_frame; flush();
    return $this->_reflower->reflow();
  }

  final function get_min_max_width() { return $this->_reflower->get_min_max_width(); }
  
  //........................................................................


}

?>