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
 * http://www.digitaljunkies.ca/dompdf
 *
 * @link http://www.digitaljunkies.ca/dompdf
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf
 * @version 0.5.1
 */

/* $Id: block_frame_decorator.cls.php,v 1.11 2007-08-22 23:02:06 benjcarson Exp $ */

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

  //........................................................................

  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
    $this->_lines = array(array("frames" => array(),
                                "wc" => 0,
                                "y" => null,
                                "w" => 0,
                                "h" => 0));
    $this->_counters = array(self::DEFAULT_COUNTER => 0);
    $this->_cl = 0;

  }

  //........................................................................

  function reset() {
    parent::reset();
    $this->_lines = array(array("frames" => array(),
                                "wc" => 0,
                                "y" => null,
                                "w" => 0,
                                "h" => 0));
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

  function get_lines() { return $this->_lines; }

  //........................................................................

  // Set methods
  function set_current_line($y = null, $w = null, $h = null) {
    $this->set_line($this->_cl, $y, $w, $h);
  }

  function clear_line($i) {
    if ( isset($this->_lines[$i]) )
      unset($this->_lines[$i]);
  }

  function set_line($lineno, $y = null, $w = null, $h = null) {

    if ( is_array($y) )
      extract($y);

    if (is_numeric($y))
      $this->_lines[$lineno]["y"] = $y;

    if (is_numeric($w))
      $this->_lines[$lineno]["w"] = $w;

    if (is_numeric($h))
      $this->_lines[$lineno]["h"] = $h;

  }


  function add_frame_to_line(Frame $frame) {

    // Handle inline frames (which are effectively wrappers)
    if ( $frame instanceof Inline_Frame_Decorator ) {

      // Handle line breaks
      if ( $frame->get_node()->nodeName == "br" ) {
        $this->maximize_line_height( $frame->get_style()->length_in_pt($frame->get_style()->line_height) );
        $this->add_line();
        return;
      }

      // Add each child of the inline frame to the line individually
      foreach ($frame->get_children() as $child)
        $this->add_frame_to_line( $child );

      return;
    }

    // Trim leading text if this is an empty line.  Kinda a hack to put it here,
    // but what can you do...
    if ( $this->_lines[$this->_cl]["w"] == 0 &&
         $frame->get_node()->nodeName == "#text" &&
         ($frame->get_style()->white_space != "pre" ||
          $frame->get_style()->white_space != "pre-wrap") ) {

      $frame->set_text( ltrim($frame->get_text()) );
      $frame->recalculate_width();

    }

    $w = $frame->get_margin_width();

    if ( $w == 0 )
      return;

    // Debugging code:
    /*
    pre_r("\nAdding frame to line:");

    //    pre_r("Me: " . $this->get_node()->nodeName . " (" . (string)$this->get_node() . ")");
    //    pre_r("Node: " . $frame->get_node()->nodeName . " (" . (string)$frame->get_node() . ")");
    if ( $frame->get_node()->nodeName == "#text" )
      pre_r($frame->get_node()->nodeValue);

    pre_r("Line width: " . $this->_lines[$this->_cl]["w"]);
    pre_r("Frame: " . get_class($frame));
    pre_r("Frame width: "  . $w);
    pre_r("Frame height: " . $frame->get_margin_height());
    pre_r("Containing block width: " . $this->get_containing_block("w"));
    */
    // End debugging

    if ($this->_lines[$this->_cl]["w"] + $w > $this->get_containing_block("w"))
      $this->add_line();

    $frame->position();


    $this->_lines[$this->_cl]["frames"][] = $frame;

    if ( $frame->get_node()->nodeName == "#text")
      $this->_lines[$this->_cl]["wc"] += count(preg_split("/\s+/", $frame->get_text()));

    $this->_lines[$this->_cl]["w"] += $w;
    $this->_lines[$this->_cl]["h"] = max($this->_lines[$this->_cl]["h"], $frame->get_margin_height());

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
      unset($this->_lines[$i]["frames"][$j++]);
      $this->_lines[$i]["w"] -= $f->get_margin_width();
    }

    // Recalculate the height of the line
    $h = 0;
    foreach ($this->_lines[$i]["frames"] as $f)
      $h = max( $h, $f->get_margin_height() );

    $this->_lines[$i]["h"] = $h;

    // Remove all lines that follow
    while ($this->_cl > $i)
      unset($this->_lines[ $this->_cl-- ]);

  }

  function increase_line_width($w) {
    $this->_lines[ $this->_cl ]["w"] += $w;
  }

  function maximize_line_height($val) {
    $this->_lines[ $this->_cl ]["h"] = max($this->_lines[ $this->_cl ]["h"], $val);
  }

  function add_line() {

//     if ( $this->_lines[$this->_cl]["h"] == 0 ) //count($this->_lines[$i]["frames"]) == 0 ||
//       return;

    $y = $this->_lines[$this->_cl]["y"] + $this->_lines[$this->_cl]["h"];

    $this->_lines[ ++$this->_cl ] = array("frames" => array(),
                                          "wc" => 0,
                                          "y" => $y, "w" => 0, "h" => 0);
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

  function counter_value($id = self::DEFAULT_COUNTER, $type = "decimal") {
    $type = mb_strtolower($type);
    if ( !isset($this->_counters[$id]) )
      $this->_counters[$id] = 0;

    switch ($type) {

    default:
    case "decimal":
      return $this->_counters[$id];

    case "decimal-leading-zero":
      return str_pad($this->_counters[$id], 2, "0");

    case "lower-roman":
      return dec2roman($this->_counters[$id]);

    case "upper-roman":
      return mb_strtoupper(dec2roman($this->_counters[$id]));

    case "lower-latin":
    case "lower-alpha":
      return chr( ($this->_counters[$id] % 26) + ord('a') - 1);

    case "upper-latin":
    case "upper-alpha":
      return chr( ($this->_counters[$id] % 26) + ord('A') - 1);

    case "lower-greek":
      return chr($this->_counters[$id] + 944);

    case "upper-greek":
      return chr($this->_counters[$id] + 912);
    }
  }
}

?>