<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: renderer.cls.php,v $
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
 * http://www.digitaljunkies.ca/dompdf
 *
 * @link http://www.digitaljunkies.ca/dompdf
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf
 * @version 0.3
 */

/* $Id: renderer.cls.php,v 1.3 2005-03-02 00:51:24 benjcarson Exp $ */

/**
 * Concrete renderer
 *
 * Instantiates several specific renderers in order to render any given
 * frame.
 *
 * @access private
 * @package dompdf
 */
class Renderer extends Abstract_Renderer {

  protected $_canvas;
  protected $_renderers;
  
  function __construct(Canvas $canvas) {
    $this->_canvas = $canvas;
    
    // FIXME: should prolly do this on demand:
    // Create a multitude of individual renderers
    $this->_renderers["block"] = new Block_Renderer($canvas);
    $this->_renderers["inline"] = new Inline_Renderer($canvas);
    $this->_renderers["text"] = new Text_Renderer($canvas);
    $this->_renderers["image"] = new Image_Renderer($canvas);
    $this->_renderers["table-cell"] = new Table_Cell_Renderer($canvas);
    $this->_renderers["list-bullet"] = new List_Bullet_Renderer($canvas);
    $this->_renderers["php"] = new PHP_Evaluator($canvas);
  }
  
  //........................................................................


  /**
   * Render all frames recursively
   *
   * @param Frame $root the root frame
   */
  function render(Frame $root) {

    // count() doesn't work on iterated elements
    // FIXME: look into the ArrayAccess interface - need 5.1 for SPL better
    // support.
    $count = 0;
    foreach ($root->get_children() as $page)
      $count++;

    $this->_canvas->set_page_count($count);


    // Create pages
    foreach ( $root->get_children() as $page ) {
      if ( $page !== $root->get_first_child() )
        $this->_canvas->new_page();

      $this->_render_frame($page);
    }
  }
  
  /**
   * Render frames recursively
   *
   * @param Frame $frame the frame to render
   */
  protected function _render_frame(Frame $frame) {    
    global $_dompdf_debug;
    
    if ( $_dompdf_debug ) {
      echo $frame;
      flush();
    }                      

    $display = $frame->get_style()->display;
    
    switch ($display) {
      
    case "block":
    case "inline-block":
    case "table":
    case "table-row-group":
    case "table-header-group":
    case "table-footer-group":
    case "inline-table":
      $this->_renderers["block"]->render($frame);
      break;

    case "inline":
      if ( $frame->get_node()->nodeName == "#text" )
        $this->_renderers["text"]->render($frame);
      else
        $this->_renderers["inline"]->render($frame);
      break;

    case "table-cell":
      $this->_renderers["table-cell"]->render($frame);
      break;

    case "-dompdf-list-bullet":
      $this->_renderers["list-bullet"]->render($frame);
      break;

    case "-dompdf-image":
      $this->_renderers["image"]->render($frame);
      break;
      
    case "none":
      $node = $frame->get_node();
          
      if ( $node->nodeName == "script" &&
           ( $node->getAttribute("type") == "text/php" ||
             $node->getAttribute("language") == "php" ) ) {
        // Evaluate embedded php scripts
        $this->_renderers["php"]->evaluate($node->nodeValue);
      }

      // Don't render children, so skip to next iter
      return;
      
    default:
      break;

    }

    foreach ($frame->get_children() as $child)
      $this->_render_frame($child);

  }
}

?>