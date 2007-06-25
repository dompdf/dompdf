<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: frame_tree.cls.php,v $
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
 * @version 0.5.1
 */

/* $Id: frame_tree.cls.php,v 1.12 2007-06-25 02:45:12 benjcarson Exp $ */

/**
 * Represents an entire document as a tree of frames
 *
 * The Frame_Tree consists of {@link Frame} objects each tied to specific
 * DomNode objects in a specific DomDocument.  The Frame_Tree has the same
 * structure as the DomDocument, but adds additional capabalities for
 * styling and layout.
 *
 * @package dompdf
 * @access protected
 */
class Frame_Tree {
    
  /**
   * Tags to ignore while parsing the tree
   *
   * @var array
   */
  static protected $_HIDDEN_TAGS = array("area", "base", "basefont", "head", "style",
                                         "meta", "title", "colgroup",
                                         "noembed", "noscript", "param", "#comment");  
  /**
   * The main DomDocument
   *
   * @see http://ca2.php.net/manual/en/ref.dom.php
   * @var DomDocument
   */
  protected $_dom;

  /**
   * The root node of the FrameTree.
   *
   * @var Frame
   */
  protected $_root;

  /**
   * Subtrees of absolutely positioned elements
   *
   * @var array of Frames
   */
  protected $_absolute_frames;

  /**
   * A mapping of {@link Frame} objects to DomNode objects
   *
   * @var array
   */
  protected $_registry;
  

  /**
   * Class constructor
   *
   * @param DomDocument $dom the main DomDocument object representing the current html document
   */
  function __construct(DomDocument $dom) {
    $this->_dom = $dom;
    $this->_root = null;
    $this->_registry = array();
  }

  /**
   * Returns the DomDocument object representing the curent html document
   *
   * @return DomDocument
   */
  function get_dom() { return $this->_dom; }

  /**
   * Returns the root frame of the tree
   *
   * @return Frame
   */
  function get_root() { return $this->_root; }

  /**
   * Returns a specific frame given its id
   *
   * @param string $id
   * @return Frame
   */
  function get_frame($id) { return isset($this->_registry[$id]) ? $this->_registry[$id] : null; }

  /**
   * Returns a post-order iterator for all frames in the tree
   *
   * @return FrameTreeList
   */
  function get_frames() { return new FrameTreeList($this->_root); }
      
  /**
   * Builds the tree
   */
  function build_tree() {
    $html = $this->_dom->getElementsByTagName("html")->item(0);
    if ( is_null($html) )
      $html = $this->_dom->firstChild;

    if ( is_null($html) )
      throw new DOMPDF_Exception("Requested HTML document contains no data.");

    $this->_root = $this->_build_tree_r($html);

  }

  /**
   * Recursively adds {@link Frame} objects to the tree
   *
   * Recursively build a tree of Frame objects based on a dom tree.
   * No layout information is calculated at this time, although the
   * tree may be adjusted (i.e. nodes and frames for generated content
   * and images may be created).
   *
   * @param DomNode $node the current DomNode being considered
   * @return Frame
   */
  protected function _build_tree_r(DomNode $node) {
    
    $frame = new Frame($node);
    $id = $frame->get_id();
    $this->_registry[ $id ] = $frame;
    
    if ( !$node->hasChildNodes() )
      return $frame;

    // Fixes 'cannot access undefined property for object with
    // overloaded access', fix by Stefan radulian
    // <stefan.radulian@symbion.at>    
    //foreach ($node->childNodes as $child) {

    // Store the children in an array so that the tree can be modified
    $children = array();
    for ($i = 0; $i < $node->childNodes->length; $i++)
      $children[] = $node->childNodes->item($i);

    foreach ($children as $child) {
      // Skip non-displaying nodes
      if ( in_array( mb_strtolower($child->nodeName), self::$_HIDDEN_TAGS) )  {
        if ( mb_strtolower($child->nodeName) != "head" &&
             mb_strtolower($child->nodeName) != "style" ) 
          $child->parentNode->removeChild($child);
        continue;
      }

      // Skip empty text nodes
      if ( $child->nodeName == "#text" && $child->nodeValue == "" ) {
        $child->parentNode->removeChild($child);
        continue;
      }

      // Skip empty image nodes
      if ( $child->nodeName == "img" && $child->getAttribute("src") == "" ) {
        $child->parentNode->removeChild($child);
        continue;
      }

      // Add a container frame for images
      if ( $child->nodeName == "img" ) {
        $img_node = $child->ownerDocument->createElement("img_inner");
     
        // Move attributes to inner node        
        foreach ( $child->attributes as $attr => $attr_node ) {
          // Skip style, but move all other attributes
          if ( $attr == "style" )
            continue;
       
          $img_node->setAttribute($attr, $attr_node->value);
        }

        foreach ( $child->attributes as $attr => $node ) {
          if ( $attr == "style" )
            continue;
          $child->removeAttribute($attr);
        }

        $child->appendChild($img_node);
      }
      
      $frame->append_child($this->_build_tree_r($child), false);

    }
    
    return $frame;
  }
}

?>