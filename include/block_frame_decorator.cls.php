<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: block_frame_decorator.cls.php,v $
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
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf

 */

/* $Id$ */

/**
 * Decorates frames for block layout
 *
 * @access private
 * @package dompdf
 */
class Block_Frame_Decorator extends Frame_Decorator {

  const DEFAULT_COUNTER = "-dompdf-default-counter";

  protected $_lines; // array( [num] => array([frames] => array(<frame list>),
                     //                 y, w, h) )
  protected $_counters; // array([id] => counter_value) (for generated content)
  protected $_cl;    // current line index
  
  static protected $_initial_line_state = array(
    "frames" => array(),
    "wc" => 0,
    "y" => null,
    "w" => 0,
    "h" => 0,
    "left" => 0,
    "right" => 0,
    "tallest_frame" => null,
    "br" => false,
  );

  //........................................................................

  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
    
    $this->_lines = array(self::$_initial_line_state);
    
    $this->_counters = array(self::DEFAULT_COUNTER => 0);
    $this->_cl = 0;
  }

  //........................................................................

  function reset() {
    parent::reset();
    
    $this->_lines = array(self::$_initial_line_state);
    
    $this->_counters = array(self::DEFAULT_COUNTER => 0);
    $this->_cl = 0;
  }

  //........................................................................

  // Accessor methods

  function get_current_line($i = null) {
    $cl = $this->_lines[$this->_cl];
    if ( isset($i) )
      return $cl[$i];
    return $cl;
  }

  function get_current_line_number() {
    return $this->_cl;
  }

  function get_lines() { return $this->_lines; }

  //........................................................................

  // Set methods
  function set_current_line($y = null, $w = null, $h = null, $tallest_frame = null, $left = null, $right = null) {
    $this->set_line($this->_cl, $y, $w, $h, $tallest_frame, $left, $right);
  }

  function clear_line($i) {
    if ( isset($this->_lines[$i]) )
      unset($this->_lines[$i]);
  }

  function set_line($lineno, $y = null, $w = null, $h = null, $tallest_frame = null, $left = null, $right = null) {

    if ( is_array($y) )
      extract($y);

    if (is_numeric($y))
      $this->_lines[$lineno]["y"] = $y;

    if (is_numeric($w))
      $this->_lines[$lineno]["w"] = $w;

    if (is_numeric($h))
      $this->_lines[$lineno]["h"] = $h;

    if ($tallest_frame && $tallest_frame instanceof Frame)
      $this->_lines[$lineno]["tallest_frame"] = $tallest_frame;

    if (is_numeric($left))
      $this->_lines[$lineno]["left"] = $left;

    if (is_numeric($right))
      $this->_lines[$lineno]["right"] = $right;
  }


  function add_frame_to_line(Frame $frame) {
    $style = $frame->get_style();
    
    if ( in_array($style->position, array("absolute", "fixed")) ||
         (DOMPDF_ENABLE_CSS_FLOAT && $style->float !== "none") ) {
      return;
    }
    
    $frame->set_containing_line($this->_lines[$this->_cl]);
    
    /*
    // Adds a new line after a block, only if certain conditions are met
    if ((($frame instanceof Inline_Frame_Decorator && $frame->get_node()->nodeName !== "br") || 
          $frame instanceof Text_Frame_Decorator && trim($frame->get_text())) && 
        ($frame->get_prev_sibling() && $frame->get_prev_sibling()->get_style()->display === "block" && 
         $this->_lines[$this->_cl]["w"] > 0 )) {
           
           $this->maximize_line_height( $style->length_in_pt($style->line_height), $frame );
           $this->add_line();
         
           // Add each child of the inline frame to the line individually
           foreach ($frame->get_children() as $child)
             $this->add_frame_to_line( $child );     
    }
    else*/

    // Handle inline frames (which are effectively wrappers)
    if ( $frame instanceof Inline_Frame_Decorator ) {

      // Handle line breaks
      if ( $frame->get_node()->nodeName === "br" ) {
        $this->maximize_line_height( $style->length_in_pt($style->line_height), $frame );
        $this->add_line(true);
      }

      return;
    }

    // Trim leading text if this is an empty line.  Kinda a hack to put it here,
    // but what can you do...
    if ( $this->_lines[$this->_cl]["w"] == 0 &&
         $frame->get_node()->nodeName === "#text" &&
         ($style->white_space !== "pre" ||
          $style->white_space !== "pre-wrap") ) {

      $frame->set_text( ltrim($frame->get_text()) );
      $frame->recalculate_width();
    }

    $w = $frame->get_margin_width();

    if ( $w == 0 )
      return;

    // Debugging code:
    /*
    pre_r("\n<h3>Adding frame to line:</h3>");

    //    pre_r("Me: " . $this->get_node()->nodeName . " (" . spl_object_hash($this->get_node()) . ")");
    //    pre_r("Node: " . $frame->get_node()->nodeName . " (" . spl_object_hash($frame->get_node()) . ")");
    if ( $frame->get_node()->nodeName === "#text" )
      pre_r('"'.$frame->get_node()->nodeValue.'"');

    pre_r("Line width: " . $this->_lines[$this->_cl]["w"]);
    pre_r("Frame: " . get_class($frame));
    pre_r("Frame width: "  . $w);
    pre_r("Frame height: " . $frame->get_margin_height());
    pre_r("Containing block width: " . $this->get_containing_block("w"));
    */
    // End debugging

    $line = $this->_lines[$this->_cl];
    if ( $line["left"] + $line["w"] + $line["right"] + $w > $this->get_containing_block("w"))
      $this->add_line();

    $frame->position();

    $current_line = &$this->_lines[$this->_cl];
    
    $current_line["frames"][] = $frame;

    if ( $frame->get_node()->nodeName === "#text")
      $current_line["wc"] += count(preg_split("/\s+/", trim($frame->get_text())));

    $this->increase_line_width($w);
    
    $this->maximize_line_height($frame->get_margin_height(), $frame);
  }

  function remove_frames_from_line(Frame $frame) {
    // Search backwards through the lines for $frame
    $i = $this->_cl;

    while ($i >= 0) {
      if ( ($j = in_array($frame, $this->_lines[$i]["frames"], true)) !== false )
        break;
      $i--;
    }

    if ( $j === false )
      return;

    // Remove $frame and all frames that follow
    while ($j < count($this->_lines[$i]["frames"])) {
      $f = $this->_lines[$i]["frames"][$j];
      $this->_lines[$i]["frames"][$j] = null;
      unset($this->_lines[$i]["frames"][$j]);
      $j++;
      $this->_lines[$i]["w"] -= $f->get_margin_width();
    }

    // Recalculate the height of the line
    $h = 0;
    foreach ($this->_lines[$i]["frames"] as $f)
      $h = max( $h, $f->get_margin_height() );

    $this->_lines[$i]["h"] = $h;

    // Remove all lines that follow
    while ($this->_cl > $i) {
      $this->_lines[ $this->_cl ] = null;
      unset($this->_lines[ $this->_cl ]);
      $this->_cl--;
    }
  }

  function increase_line_width($w) {
    $this->_lines[ $this->_cl ]["w"] += $w;
  }

  function maximize_line_height($val, Frame $frame) {
    if ( $val > $this->_lines[ $this->_cl ]["h"] ) {
      $this->_lines[ $this->_cl ]["tallest_frame"] = $frame;
      $this->_lines[ $this->_cl ]["h"] = $val;
    }
  }

  function add_line($br = false) {

//     if ( $this->_lines[$this->_cl]["h"] == 0 ) //count($this->_lines[$i]["frames"]) == 0 ||
//       return;

    $this->_lines[$this->_cl]["br"] = $br;
    $y = $this->_lines[$this->_cl]["y"] + $this->_lines[$this->_cl]["h"];

    $new_line = self::$_initial_line_state;
    $new_line["y"] = $y;
    
    $this->_lines[ ++$this->_cl ] = $new_line;
  }

  //........................................................................

  function reset_counter($id = self::DEFAULT_COUNTER, $value = 0) {
    $this->_counters[$id] = $value;
  }

  function increment_counter($id = self::DEFAULT_COUNTER, $increment = 1) {
    if ( !isset($this->_counters[$id]) )
      $this->_counters[$id] = $increment;
    else
      $this->_counters[$id] += $increment;
  }

  // TODO: What version is the best : this one or the one in List_Bullet_Renderer ?
  function counter_value($id = self::DEFAULT_COUNTER, $type = "decimal") {
    $type = mb_strtolower($type);
    
    if ( $id === "page" ) {
      $value = $this->get_dompdf()->get_canvas()->get_page_number();
    }
    elseif ( !isset($this->_counters[$id]) ) {
      $this->_counters[$id] = 0;
      $value = 0;
    }
    else {
      $value = $this->_counters[$id];
    }
    
    switch ($type) {

    default:
    case "decimal":
      return $value;

    case "decimal-leading-zero":
      return str_pad($value, 2, "0");

    case "lower-roman":
      return dec2roman($value);

    case "upper-roman":
      return mb_strtoupper(dec2roman($value));

    case "lower-latin":
    case "lower-alpha":
      return chr( ($value % 26) + ord('a') - 1);

    case "upper-latin":
    case "upper-alpha":
      return chr( ($value % 26) + ord('A') - 1);

    case "lower-greek":
      return unichr($value + 944);

    case "upper-greek":
      return unichr($value + 912);
    }
  }
}
