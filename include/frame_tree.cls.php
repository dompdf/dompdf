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
 * @version 0.3
 */

/* $Id: frame_tree.cls.php,v 1.2 2005-02-28 18:46:32 benjcarson Exp $ */

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
  static protected $_HIDDEN_TAGS = array("area", "base", "basefont", "head",
                                         "meta", "style", "title",
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
  function get_frame($id) { return array_key_exists($id, $this->_registry) ? $this->_registry[$id] : null; }

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
    $frame->set_id( $id = uniqid(rand()));
    $this->_registry[ $id ] = $frame;
    
    if ( !$node->hasChildNodes() )
      return $frame;
    
    foreach ($node->childNodes as $child) {

      // Skip non-displaying nodes
      if ( in_array( $child->nodeName, self::$_HIDDEN_TAGS) )
        continue;

      // Skip empty #text nodes
      if ( $child->nodeName == "#text" && $child->nodeValue == "" )
        continue;

      // Add a container frame for images
      if ( $child->nodeName == "img" ) {
        $img_node = $child->ownerDocument->createElement("img_inner");
        
        // Move attributes to inner node
        foreach ( $child->attributes as $attr => $attr_node ) {
          // Skip style, but move all other attributes
          if ( $attr == "style" )
            continue;
          
          $img_node->setAttribute($attr, $attr_node->value);
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