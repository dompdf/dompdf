<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: block_frame_reflower.cls.php,v $
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
 * @version 0.3
 */

/* $Id: block_frame_reflower.cls.php,v 1.3 2005-02-05 17:32:04 benjcarson Exp $ */

/**
 * Reflows block frames
 *
 * @access private
 * @package dompdf
 */
class Block_Frame_Reflower extends Frame_Reflower {
  const MIN_JUSTIFY_WIDTH = 0.77;  // (Minimum line width to justify, as
                                   // fraction of available width)

  function __construct(Block_Frame_Decorator $frame) { parent::__construct($frame); }

  //........................................................................

  // Calculate the ideal used value for the width property as per:
  // http://www.w3.org/TR/CSS21/visudet.html#Computing_widths_and_margins

  protected function _calculate_width($width) {
    $style = $this->_frame->get_style();
    $cb = $this->_frame->get_containing_block();
    
    $r = $style->length_in_pt($style->margin_right, $cb["w"]);
    $l = $style->length_in_pt($style->margin_left, $cb["w"]);    

    // Handle 'auto' values
    $dims = array($style->border_left_width,
                  $style->border_right_width,
                  $style->padding_left,
                  $style->padding_right,
                  $width !== "auto" ? $width : 0,
                  $l !== "auto" ? $l : 0,
                  $r !== "auto" ? $r : 0);
    
    $sum = $style->length_in_pt($dims, $cb["w"]);

    // Compare to the containing block
    $diff = $cb["w"] - $sum;

    if ( $diff > 0 ) {

      // Find auto properties and get them to take up the slack
      if ( $width === "auto" ) 
        $width = $diff;
        
      else if ( $l === "auto" && $r === "auto" ) 
        $l = $r = round($diff / 2);

      else if ( $l === "auto" )
        $l = $diff;

      else if ( $r === "auto" )
        $r = $diff;
      
    } else if ($diff < 0) {

      // We are over constrained--set margin-right to the difference
      $r = $diff;

    }
    
    return array("width"=> $width, "margin_left" => $l, "margin_right" => $r);
  }

  // Call the above function, but resolve max/min widths
  protected function _calculate_restricted_width() {
    $style = $this->_frame->get_style();
    $cb = $this->_frame->get_containing_block();

    if ( !isset($cb["w"]) )
      throw new DOMPDF_Exception("Box property calculation requires containing block width");

    $width = $style->length_in_pt($style->width, $cb["w"]);

    extract($this->_calculate_width($width));

    // Handle min/max width
    $min_width = $style->length_in_pt($style->min_width, $cb["w"]);
    $max_width = $style->length_in_pt($style->max_width, $cb["w"]);

    if ( $max_width !== "none" && $min_width > $max_width)
      // Swap 'em
      list($max_width, $min_width) = array($min_width, $max_width);
    
    if ( $max_width !== "none" && $width > $max_width )
      extract($this->_calculate_width($max_width));
    
    if ( $width < $min_width )
      extract($this->_calculate_width($min_width));    
      
    return array($width, $margin_left, $margin_right);

  }

  //........................................................................

  // Determine the unrestricted height of content within the block
  protected function _calculate_content_height() {
    $style = $this->_frame->get_style();
    $cb = $this->_frame->get_containing_block();

    $height = $style->height;

    // Handle percentage heights
    if ( isset($cb["h"]) ) 
      $height = $style->length_in_pt($height, $cb["h"]);
      
    else if ( strpos($height, "%") !== false )
      $height = "auto";

    else
      $height = $style->length_in_pt($height, $cb["w"]);

    // FIXME: not sure about this...  What if height is specified precisely,
    // but overflow is visible?
    if ( $style->overflow === "visible" ||
         ($style->overflow === "hidden" && $height === "auto") ) {

      // Calculate the actual height
      $height = 0;

      // Add the height of all lines
      foreach ($this->_frame->get_lines() as $line)
        $height += $line["h"];

    }
    
    return $height;
  }
  
  // Determine the frame's restricted height
  protected function _calculate_restricted_height() {
    $style = $this->_frame->get_style();
    $height = $this->_calculate_content_height();
    
    if ( !($style->overflow === "visible" ||
           ($style->overflow === "hidden" && $height === "auto")) ) {

      // Only handle min/max height if the height is independent of the frame's content
    
      $min_height = $style->min_height;
      $max_height = $style->max_height;

      if ( isset($cb["h"]) ) {
        $min_height = $style->length_in_pt($min_height, $cb["h"]);
        $max_height = $style->length_in_pt($max_height, $cb["h"]);

      } else {

        if ( strpos($min_height, "%") !== false )
          $min_height = 0;
        else
          $min_height = $style->length_in_pt($min_height, $cb["w"]);
      
        if ( strpos($max_height, "%") !== false )
          $max_height = "none";
        else
          $max_height = $style->length_in_pt($max_height, $cb["w"]);
      }

      if ( $max_height !== "none" && $min_height > $max_height )
        // Swap 'em
        list($max_height, $min_height) = array($min_height, $max_height);
      
      if ( $max_height !== "none" && $height > $max_height )
        $height = $max_height;

      if ( $height < $min_height )
        $height = $min_height;
    }

    return $height;
    
  }

  //........................................................................

  protected function _text_align() {
    $style = $this->_frame->get_style();
    
    // Adjust the justification of each of our lines.
    // http://www.w3.org/TR/CSS21/text.html#propdef-text-align
    switch ($style->text_align) {

    default:
    case "left":
      return;

    case "right":
      foreach ($this->_frame->get_lines() as $line) {

        // Move each child over by $dx
        $dx = $style->width - $line["w"];        
        foreach($line["frames"] as $frame) 
          $frame->set_position( $frame->get_position("x") + $dx );

      }
      break;

      
    case "justify":
      foreach ($this->_frame->get_lines() as $i => $line) {
        
        // Only set the spacing if the line is long enough.  This is really
        // just an aesthetic choice ;)
        if ( $line["w"] > self::MIN_JUSTIFY_WIDTH * $style->width ) {
          // Set the spacing for each child
          if ( $line["wc"] > 0 )
            $spacing = ($style->width - $line["w"]) / $line["wc"];
          else
            $spacing = 0;
          
          $dx = 0;
          foreach($line["frames"] as $frame) {
            if ( !$frame instanceof Text_Frame_Decorator )
              continue;
            
            $frame->set_position( $frame->get_position("x") + $dx );
            $frame->set_text_spacing($spacing);
            $dx += substr_count($frame->get_text(), " ") * $spacing;
          }

          // The line (should) now occupy the entire width
          $this->_frame->set_line($i, null, $style->width);

        }
      }
      break;

    case "center":
    case "centre":
      foreach ($this->_frame->get_lines() as $i => $line) {
        // Centre each line by moving each frame in the line by:
        $dx = ($style->width - $line["w"]) / 2;
        foreach ($line["frames"] as $frame) 
          $frame->set_position( $frame->get_position("x") + $dx );
      }
      break;
    }

  }

  //........................................................................

  protected function _resolve_page_break() {
    // Resolve -before and -after page break properties so that we only have
    // to check -before
    $style = $this->_frame->get_style();

    $next = $this->_frame->get_next_sibling();
    while ( $next && $next->get_node()->nodeType != 1 ) // DOMElement
      $next = $next->get_next_sibling();
    
    if ( !$next )
      return;
    
    $next_style = $next->get_style();
    
    if ( $style->page_break_after == "always" ||
         $next_style->page_break_before == "always" )
      $next_style->page_break_before = "always";

    else if ( $style->page_break_after == "avoid" ||
              $next_style->page_break_after == "avoid" )
      $next_style->page_break_before = "avoid";

    else
      $next_style->page_break_before = "auto";
    
  }
  
  protected function _locate_page_break(Frame $child) {

    // Presumably $child flows off the page; we just need to figure out
    // where exactly the page break should be.
    $c_style = $child->get_style();
    
    if ( $c_style->page_break_before !== "avoid" )
      return $child;

    // Find the first available location to split the page

    $p = $child->get_prev_sibling();
    while ($p && $p->get_node()->nodeType != 1 )
      $p = $p->get_prev_sibling();
    
    while ($p) {
      if ( $p->get_style()->page_break_before !== "avoid" )
        break;

      // Find the next block level sibling
      while ($p) {
        if ( in_array($p->get_style()->display, Style::$BLOCK_TYPES) )
          break;
        $p = $p->get_prev_sibling();
      }
    }

    if ($p)
      return $p;
    
    return null;
  }
  
  //........................................................................

  function reflow() {

    // Check if a page break is forced
    $page = $this->_frame->get_root();
    if ( $page->check_forced_page_break( $this->_frame ) )
      return;
    
    // Collapse margins if required
    $this->_collapse_margins();
    
    $this->_frame->position();

    $style = $this->_frame->get_style();    
    $cb = $this->_frame->get_containing_block();
    list($x, $y) = $this->_frame->get_position();

    //$page_margin = $cb["h"] + $cb["y"];
        
    // Determine the constraints imposed by this frame: calculate the width
    // of the content area:
    list($w, $left, $right) = $this->_calculate_restricted_width();

    // Store the calculated properties
    $style->width = $w;
    $style->margin_left = $left."pt";
    $style->margin_right = $right."pt";


    // Adjust the first line based on the text-indent property
    $indent = $style->length_in_pt($style->text_indent, $cb["w"]);
    $this->_frame->increase_line_width($indent);


    // Resolve page break properties
    //$this->_resolve_page_break();
    
    // Determine the content edge
    $top = $style->length_in_pt(array($style->margin_top,
                                      $style->padding_top,
                                      $style->border_top_width), $cb["h"]);

    $bottom = $style->length_in_pt(array($style->border_bottom_width,
                                         $style->margin_bottom,
                                         $style->padding_bottom), $cb["w"]);

    $cb_x = $x + $left +
      $style->length_in_pt($style->border_left_width, $cb["w"]) +
      $style->length_in_pt($style->padding_left, $cb["w"]);

    $cb_y = $line_y = $y + $top;

    $cb_h = ($cb["h"] + $cb["y"]) - $bottom - $cb_y;
    
    // Set the y position of the first line in this block
    $this->_frame->set_current_line($line_y);
    
    // Set the containing blocks and reflow each child
    //$split_flg = false;
    foreach ( $this->_frame->get_children() as $child ) {
      
      $child->set_containing_block($cb_x, $cb_y, $w, $cb_h);
      $child->reflow();
      
      // Don't add the child to the line if a page break has occurred
      if ( $page->check_page_break($child) )
        break;      
      
//       if ( $child->reflow() ) 
//         // The child split, so we shouldn't have to
//         continue;
      
//       $y = $child->get_position("y");
//       $h = $child->get_margin_height();

//       // Find the previous block element
//       $p = $child->get_prev_sibling();
//       while ($p) {
//         if (in_array($p->get_style()->display, Style::$BLOCK_TYPES))
//           break;
//         $p = $p->get_prev_sibling();
//       }
      
//       // Check if we need to create a new page      
//       if ( $style->page_break_inside !== "avoid" && $y + $h > $page_margin ) {

//         if ( $split = $this->_locate_page_break( $child ) ) {
//           // Remove split from the frame's line
//           $this->_frame->remove_frames_from_line($split);
          
//           $this->_frame->split($split);
//           $split_flg = true;
//           break;
//         }

//       }
      
      // It's okay to add the frame to the line
      $this->_frame->add_frame_to_line( $child );
    }

    // Determine our height
    $style->height = $this->_calculate_restricted_height();
    
    $this->_text_align();
    
    //return $split_flg;
  }

  //........................................................................

}
?>