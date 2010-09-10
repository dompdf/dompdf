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
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf

 */

/* $Id$ */

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

  /**
   * Array of renderers for specific frame types
   *
   * @var array
   */
  protected $_renderers;
    
  /**
   * Cache of the callbacks array
   * 
   * @var array
   */
  private $_callbacks;
  
  /**
   * Class destructor
   */
  function __destruct() {
  	clear_object($this);
  }
  
  /**
   * Advance the canvas to the next page
   */  
  function new_page() {
    $this->_canvas->new_page();
  }

  /**
   * Render frames recursively
   *
   * @param Frame $frame the frame to render
   */
  function render(Frame $frame) {    
    global $_dompdf_debug;

    if ( $_dompdf_debug ) {
      echo $frame;
      flush();
    }

    $style = $frame->get_style();
    $display = $style->display;
  
    switch ($display) {
      
    case "block":
    case "list-item":
    case "inline-block":
    case "table":
    case "table-row-group":
    case "table-header-group":
    case "table-footer-group":
    case "inline-table":
      $this->_render_frame("block", $frame);
      break;

    case "inline":
      if ( $frame->get_node()->nodeName === "#text" )
        $this->_render_frame("text", $frame);
      else
        $this->_render_frame("inline", $frame);
      break;

    case "table-cell":
      $this->_render_frame("table-cell", $frame);
      break;

    case "-dompdf-list-bullet":
      $this->_render_frame("list-bullet", $frame);
      break;

    case "-dompdf-image":
      $this->_render_frame("image", $frame);
      break;
      
    case "none":
      $node = $frame->get_node();
          
      if ( $node->nodeName === "script" ) {
        if ( $node->getAttribute("type") === "text/php" ||
             $node->getAttribute("language") === "php" ) {
          // Evaluate embedded php scripts
          $this->_render_frame("php", $frame);
        }
        
        elseif ( $node->getAttribute("type") === "text/javascript" ||
             $node->getAttribute("language") === "javascript" ) {
          // Insert JavaScript
          $this->_render_frame("javascript", $frame);
        }
      }

      // Don't render children, so skip to next iter
      return;
      
    default:
      break;

    }

    // Check for begin frame callback
    $this->_check_callbacks("begin_frame", $frame);
    
    // Starts the overflow: hidden box
    if ( $style->overflow === "hidden" ) {
      list($x, $y, $w, $h) = $frame->get_padding_box();
      $this->_canvas->clipping_rectangle($x, $y, $w, $h);
    }
    
    foreach ($frame->get_children() as $child)
      $this->render($child);
      
    // Ends the overflow: hidden box
    if ( $style->overflow === "hidden" ) {
      $this->_canvas->clipping_end();
    }

    // Check for end frame callback
    $this->_check_callbacks("end_frame", $frame);
    
  }
  
  /**
   * Check for callbacks that need to be performed when a given event
   * gets triggered on a frame
   *
   * @param string $event the type of event
   * @param Frame $frame the frame that event is triggered on
   */
  protected function _check_callbacks($event, $frame) {
    if (!isset($this->_callbacks)) {
      $this->_callbacks = $this->_dompdf->get_callbacks();
    }
    
    if (is_array($this->_callbacks) && isset($this->_callbacks[$event])) {
      $info = array(0 => $this->_canvas, "canvas" => $this->_canvas,
                    1 => $frame, "frame" => $frame);
      $fs = $this->_callbacks[$event];
      foreach ($fs as $f) {
        if (is_callable($f)) {
          if (is_array($f)) {
            $f[0]->$f[1]($info);
          } else {
            $f($info);
          }
        }
      }
    }
  }

  /**
   * Render a single frame
   *
   * Creates Renderer objects on demand
   *
   * @param string $type type of renderer to use
   * @param Frame $frame the frame to render
   */
  protected function _render_frame($type, $frame) {

    if ( !isset($this->_renderers[$type]) ) {
      
      switch ($type) {
      case "block":
        $this->_renderers[$type] = new Block_Renderer($this->_dompdf);
        break;

      case "inline":
        $this->_renderers[$type] = new Inline_Renderer($this->_dompdf);
        break;

      case "text":
        $this->_renderers[$type] = new Text_Renderer($this->_dompdf);
        break;

      case "image":
        $this->_renderers[$type] = new Image_Renderer($this->_dompdf);
        break;
      
      case "table-cell":
        $this->_renderers[$type] = new Table_Cell_Renderer($this->_dompdf);
        break;

      case "list-bullet":
        $this->_renderers[$type] = new List_Bullet_Renderer($this->_dompdf);
        break;

      case "php":
        $this->_renderers[$type] = new PHP_Evaluator($this->_canvas);
        break;

      case "javascript":
        $this->_renderers[$type] = new Javascript_Embedder($this->_dompdf);
        break;
        
      }
    }
    
    $this->_renderers[$type]->render($frame);

  }
}
