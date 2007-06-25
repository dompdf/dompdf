<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: table_frame_reflower.cls.php,v $
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
 * @version 0.5.1
 */

/* $Id: table_frame_reflower.cls.php,v 1.14 2007-06-25 02:45:12 benjcarson Exp $ */

/**
 * Reflows tables
 *
 * @access private
 * @package dompdf
 */
class Table_Frame_Reflower extends Frame_Reflower {

  /**
   * Cache of results between call to get_min_max_width and assign_widths
   *
   * @var array
   */
  protected $_state;

  function __construct(Table_Frame_Decorator $frame) {
    $this->_state = null;
    parent::__construct($frame);
  }

  /**
   * State is held here so it needs to be reset along with the decorator
   */
  function reset() {
    $this->_state = null;
    $this->_min_max_cache = null;
  }

  //........................................................................

  protected function _assign_widths() {
    $style = $this->_frame->get_style();

    // Find the min/max width of the table and sort the columns into
    // absolute/percent/auto arrays
    $min_width = $this->_state["min_width"];
    $max_width = $this->_state["max_width"];
    $percent_used = $this->_state["percent_used"];
    $absolute_used = $this->_state["absolute_used"];
    $auto_min = $this->_state["auto_min"];

    $absolute =& $this->_state["absolute"];
    $percent =& $this->_state["percent"];
    $auto =& $this->_state["auto"];

    // Determine the actual width of the table
    $cb = $this->_frame->get_containing_block();
    $columns =& $this->_frame->get_cellmap()->get_columns();

    $width = $style->width;

    // Calcluate padding & border fudge factor
    $left = $style->margin_left;
    $right = $style->margin_right;

    $left = $left == "auto" ? 0 : $style->length_in_pt($left, $cb["w"]);
    $right = $right == "auto" ? 0 : $style->length_in_pt($right, $cb["w"]);

    $delta = $left + $right + $style->length_in_pt(array($style->padding_left,
                                                         $style->border_left_width,
                                                         $style->border_right_width,
                                                         $style->padding_right), $cb["w"]);

    $min_table_width = $style->length_in_pt( $style->min_width, $cb["w"] - $delta );

    // min & max widths already include borders & padding
    $min_width -= $delta;
    $max_width -= $delta;
    
    if ( $width !== "auto" ) {

      $preferred_width = $style->length_in_pt($width, $cb["w"]) - $delta;

      if ( $preferred_width < $min_table_width )
        $preferred_width = $min_table_width;

      if ( $preferred_width > $min_width )
        $width = $preferred_width;
      else
        $width = $min_width;

    } else {

      if ( $max_width + $delta < $cb["w"] )
        $width = $max_width;
      else if ( $cb["w"] - $delta > $min_width )
        $width = $cb["w"] - $delta;
      else
        $width = $min_width;

      if ( $width < $min_table_width )
        $width = $min_table_width;

    }

    // Store our resolved width
    $style->width = $width;

    $cellmap = $this->_frame->get_cellmap();

    // If the whole table fits on the page, then assign each column it's max width
    if ( $width == $max_width ) {

      foreach (array_keys($columns) as $i)
        $cellmap->set_column_width($i, $columns[$i]["max-width"]);

      return;
    }

    // Determine leftover and assign it evenly to all columns
    if ( $width > $min_width ) {

      // We have four cases to deal with:
      //
      // 1. All columns are auto--no widths have been specified.  In this
      // case we distribute extra space across all columns weighted by max-width.
      //
      // 2. Only absolute widths have been specified.  In this case we
      // distribute any extra space equally among 'width: auto' columns, or all
      // columns if no auto columns have been specified.
      //
      // 3. Only percentage widths have been specified.  In this case we
      // normalize the percentage values and distribute any remaining % to
      // width: auto columns.  We then proceed to assign widths as fractions
      // of the table width.
      //
      // 4. Both absolute and percentage widths have been specified.

      // Case 1:
      if ( $absolute_used == 0 && $percent_used == 0 ) {
        $increment = $width - $min_width;

        foreach (array_keys($columns) as $i)
          $cellmap->set_column_width($i, $columns[$i]["min-width"] + $increment * ($columns[$i]["max-width"] / $max_width));
        return;
      }


      // Case 2
      if ( $absolute_used > 0 && $percent_used == 0 ) {

        if ( count($auto) > 0 )
          $increment = ($width - $auto_min - $absolute_used) / count($auto);

        // Use the absolutely specified width or the increment
        foreach (array_keys($columns) as $i) {

          if ( $columns[$i]["absolute"] > 0 && count($auto) )
            $cellmap->set_column_width($i, $columns[$i]["min-width"]);
          else if ( count($auto) ) 
            $cellmap->set_column_width($i, $columns[$i]["min-width"] + $increment);
          else {
            // All absolute columns
            $increment = ($width - $absolute_used) * $columns[$i]["absolute"] / $absolute_used;

            $cellmap->set_column_width($i, $columns[$i]["min-width"] + $increment);
          }

        }
        return;
      }


      // Case 3:
      if ( $absolute_used == 0 && $percent_used > 0 ) {

        $scale = null;
        $remaining = null;

        // Scale percent values if the total percentage is > 100, or if all
        // values are specified as percentages.
        if ( $percent_used > 100 || count($auto) == 0)
          $scale = 100 / $percent_used;
        else
          $scale = 1;

        // Account for the minimum space used by the unassigned auto columns
        $used_width = $auto_min;

        foreach ($percent as $i) {
          $columns[$i]["percent"] *= $scale;

          $slack = $width - $used_width;

          $w = min($columns[$i]["percent"] * $width/100, $slack);

          if ( $w < $columns[$i]["min-width"] )
            $w = $columns[$i]["min-width"];

          $cellmap->set_column_width($i, $w);
          $used_width += $w;

        }

        // This works because $used_width includes the min-width of each
        // unassigned column
        if ( count($auto) > 0 ) {
          $increment = ($width - $used_width) / count($auto);

          foreach ($auto as $i)
            $cellmap->set_column_width($i, $columns[$i]["min-width"] + $increment);

        }
        return;
      }

      // Case 4:

      // First-come, first served
      if ( $absolute_used > 0 && $percent_used > 0 ) {

        $used_width = $auto_min;

        foreach ($absolute as $i) {
          $cellmap->set_column_width($i, $columns[$i]["min-width"]);
          $used_width +=  $columns[$i]["min-width"];
        }

        // Scale percent values if the total percentage is > 100 or there
        // are no auto values to take up slack
        if ( $percent_used > 100 || count($auto) == 0 )
          $scale = 100 / $percent_used;
        else
          $scale = 1;

        $remaining_width = $width - $used_width;

        foreach ($percent as $i) {
          $slack = $remaining_width - $used_width;

          $columns[$i]["percent"] *= $scale;
          $w = min($columns[$i]["percent"] * $remaining_width / 100, $slack);

          if ( $w < $columns[$i]["min-width"] )
            $w = $columns[$i]["min-width"];

          $columns[$i]["used-width"] = $w;
          $used_width += $w;
        }

        if ( count($auto) > 0 ) {
          $increment = ($width - $used_width) / count($auto);

          foreach ($auto as $i)
            $cellmap->set_column_width($i, $columns[$i]["min-width"] + $increment);

        }

        return;
      }


    } else { // we are over constrained

      // Each column gets its minimum width
      foreach (array_keys($columns) as $i)
        $cellmap->set_column_width($i, $columns[$i]["min-width"]);

    }
  }

  //........................................................................

  // Determine the frame's height based on min/max height
  protected function _calculate_height() {

    $style = $this->_frame->get_style();
    $height = $style->height;

    $cellmap = $this->_frame->get_cellmap();
    $cellmap->assign_frame_heights();
    $rows = $cellmap->get_rows();

    // Determine our content height
    $content_height = 0;
    foreach ( $rows as $r )
      $content_height += $r["height"];

    $cb = $this->_frame->get_containing_block();

    if ( !($style->overflow === "visible" ||
           ($style->overflow === "hidden" && $height === "auto")) ) {

      // Only handle min/max height if the height is independent of the frame's content

      $min_height = $style->min_height;
      $max_height = $style->max_height;

      if ( isset($cb["h"]) ) {
        $min_height = $style->length_in_pt($min_height, $cb["h"]);
        $max_height = $style->length_in_pt($max_height, $cb["h"]);

      } else if ( isset($cb["w"]) ) {

        if ( mb_strpos($min_height, "%") !== false )
          $min_height = 0;
        else
          $min_height = $style->length_in_pt($min_height, $cb["w"]);

        if ( mb_strpos($max_height, "%") !== false )
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

    } else {

      // Use the content height or the height value, whichever is greater
      if ( $height !== "auto" ) {
        $height = $style->length_in_pt($height, $cb["h"]);

        if ( $height <= $content_height )
          $height = $content_height;
        else
          $cellmap->set_frame_heights($height,$content_height);

      } else
        $height = $content_height;

    }

    return $height;

  }
  //........................................................................

  function reflow() {

    // Check if a page break is forced
    $page = $this->_frame->get_root();
    $page->check_forced_page_break($this->_frame);

    // Bail if the page is full
    if ( $page->is_full() )
      return;
    
    // Let the page know that we're reflowing a table so that splits
    // are suppressed (simply setting page-break-inside: avoid won't
    // work because we may have an arbitrary number of block elements
    // inside tds.)
    $page->table_reflow_start();

    
    // Collapse vertical margins, if required
    $this->_collapse_margins();

    $this->_frame->position();

    // Table layout algorithm:
    // http://www.w3.org/TR/CSS21/tables.html#auto-table-layout

    if ( is_null($this->_state) )
      $this->get_min_max_width();

    $cb = $this->_frame->get_containing_block();
    $style = $this->_frame->get_style();

    // This is slightly inexact, but should be okay.  Add half the
    // border-spacing to the table as padding.  The other half is added to
    // the cells themselves.
    if ( $style->border_collapse === "separate" ) {
      list($h, $v) = $style->border_spacing;

      $v = $style->length_in_pt($v) / 2;
      $h = $style->length_in_pt($h) / 2;

      $style->padding_left = $style->length_in_pt($style->padding_left, $cb["w"]) + $h;
      $style->padding_right = $style->length_in_pt($style->padding_right, $cb["w"]) + $h;
      $style->padding_top = $style->length_in_pt($style->padding_top, $cb["w"]) + $v;
      $style->padding_bottom = $style->length_in_pt($style->padding_bottom, $cb["w"]) + $v;

    }

    $this->_assign_widths();

    // Adjust left & right margins, if they are auto
    $width = $style->width;
    $left = $style->margin_left;
    $right = $style->margin_right;

    $diff = $cb["w"] - $width;

    if ( $left === "auto" && $right === "auto" && $diff > 0 ) {
      $left = $right = $diff / 2;
      $style->margin_left = "$left pt";
      $style->margin_right = "$right pt";

    } else {
      $left = $style->length_in_pt($left, $cb["w"]);
      $right = $style->length_in_pt($right, $cb["w"]);
    }


    list($x, $y) = $this->_frame->get_position();

    // Determine the content edge
    $content_x = $x + $left + $style->length_in_pt(array($style->padding_left,
                                                         $style->border_left_width), $cb["w"]);
    $content_y = $y + $style->length_in_pt(array($style->margin_top,
                                                 $style->border_top_width,
                                                 $style->padding_top), $cb["w"]);

    if ( isset($cb["h"]) )
      $h = $cb["h"];
    else
      $h = null;


    $cellmap = $this->_frame->get_cellmap();
    $col =& $cellmap->get_column(0);
    $col["x"] = $content_x;

    $row =& $cellmap->get_row(0);
    $row["y"] = $content_y;

    $cellmap->assign_x_positions();

    // Set the containing block of each child & reflow
    foreach ( $this->_frame->get_children() as $child ) {

      // Bail if the page is full
      if ( !$page->in_nested_table() && $page->is_full() )
        break;

      $child->set_containing_block($content_x, $content_y, $width, $h);
      $child->reflow();

      if ( !$page->in_nested_table() )
        // Check if a split has occured
        $page->check_page_break($child);

    }

    // Assign heights to our cells:
    $style->height = $this->_calculate_height();

    if ( $style->border_collapse === "collapse" ) {
      // Unset our borders because our cells are now using them
      $style->border_style = "none";
    }

    $page->table_reflow_end();

    // Debugging:
    //echo ($this->_frame->get_cellmap());
  }

  //........................................................................

  function get_min_max_width() {

    if ( !is_null($this->_min_max_cache)  )
      return $this->_min_max_cache;

    $style = $this->_frame->get_style();

    $this->_frame->normalise();

    // Add the cells to the cellmap (this will calcluate column widths as
    // frames are added)
    $this->_frame->get_cellmap()->add_frame($this->_frame);

    // Find the min/max width of the table and sort the columns into
    // absolute/percent/auto arrays
    $this->_state = array();
    $this->_state["min_width"] = 0;
    $this->_state["max_width"] = 0;

    $this->_state["percent_used"] = 0;
    $this->_state["absolute_used"] = 0;
    $this->_state["auto_min"] = 0;

    $this->_state["absolute"] = array();
    $this->_state["percent"] = array();
    $this->_state["auto"] = array();

    $columns =& $this->_frame->get_cellmap()->get_columns();
    foreach (array_keys($columns) as $i) {
      $this->_state["min_width"] += $columns[$i]["min-width"];
      $this->_state["max_width"] += $columns[$i]["max-width"];

      if ( $columns[$i]["absolute"] > 0 ) {
        $this->_state["absolute"][] = $i;
        $this->_state["absolute_used"] += $columns[$i]["absolute"];

      } else if ( $columns[$i]["percent"] > 0 ) {
        $this->_state["percent"][] = $i;
        $this->_state["percent_used"] += $columns[$i]["percent"];

      } else {
        $this->_state["auto"][] = $i;
        $this->_state["auto_min"] += $columns[$i]["min-width"];
      }
    }

    // Account for margins & padding
    $dims = array($style->border_left_width,
                  $style->border_right_width,
                  $style->padding_left,
                  $style->padding_right,
                  $style->margin_left,
                  $style->margin_right);

    if ( $style->border_collapse != "collapse" ) 
      list($dims[]) = $style->border_spacing;

    $delta = $style->length_in_pt($dims, $this->_frame->get_containing_block("w"));

    $this->_state["min_width"] += $delta;
    $this->_state["max_width"] += $delta;

    return $this->_min_max_cache = array($this->_state["min_width"], $this->_state["max_width"],
                 "min" => $this->_state["min_width"], "max" => $this->_state["max_width"]);
  }
}

?>