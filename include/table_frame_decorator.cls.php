<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: table_frame_decorator.cls.php,v $
 * Created on: 2004-06-04
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

/* $Id: table_frame_decorator.cls.php,v 1.13 2006-12-22 18:37:45 benjcarson Exp $ */

/**
 * Decorates Frames for table layout
 *
 * @access private
 * @package dompdf
 */
class Table_Frame_Decorator extends Frame_Decorator {
  static $VALID_CHILDREN = array("table-row-group",
                                 "table-row",
                                 "table-header-group",
                                 "table-footer-group",
                                 "table-column",
                                 "table-column-group",
                                 "table-caption",
                                 "table-cell");

  static $ROW_GROUPS = array('table-row-group',
                             'table-header-group',
                             'table-footer-group');

  /**
   * The Cellmap object for this table.  The cellmap maps table cells
   * to rows and columns, and aids in calculating column widths.
   *
   * @var Cellmap
   */
  protected $_cellmap;

  /**
   * The minimum width of the table, in pt
   *
   * @var float
   */
  protected $_min_width;

  /**
   * The maximum width of the table, in pt
   *
   * @var float
   */
  protected $_max_width;

  /**
   * Table header rows.  Each table header is duplicated when a table
   * spans pages.
   *
   * @var array
   */
  protected $_headers;

  /**
   * Table footer rows.  Each table footer is duplicated when a table
   * spans pages.
   *
   * @var array
   */
  protected $_footers;

  /**
   * Class constructor
   *
   * @param Frame $frame the frame to decorate
   */
  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
    $this->_cellmap = new Cellmap($this);
    $this->_min_width = null;
    $this->_max_width = null;
    $this->_headers = array();
    $this->_footers = array();
  }


  function reset() {
    parent::reset();
    $this->_cellmap->reset();
    $this->_min_width = null;
    $this->_max_width = null;
    $this->_headers = array();
    $this->_footers = array();
    $this->_reflower->reset();
  }

  //........................................................................

  /**
   * split the table at $row.  $row and all subsequent rows will be
   * added to the clone.  This method is overidden in order to remove
   * frames from the cellmap properly.
   *
   * @param Frame $row
   */
  function split($child = null) {

    if ( is_null($child) ) {
      parent::split();
      return;
    }

    // If $child is a header or if it is the first non-header row, do
    // not duplicate headers, simply move the table to the next page.
    if ( count($this->_headers) && !in_array($child, $this->_headers, true) &&
         !in_array($child->get_prev_sibling(), $this->_headers, true) ) {

      $first_header = null;

      // Insert copies of the table headers before $child
      foreach ($this->_headers as $header) {

        $new_header = $header->deep_copy();

        if ( is_null($first_header) )
          $first_header = $new_header;

        $this->insert_child_before($new_header, $child);
      }

      parent::split($first_header);

    } else if ( in_array($child->get_style()->display, self::$ROW_GROUPS) ) {

      // Individual rows should have already been handled
      parent::split($child);

    } else {

      $iter = $child;

      while ($iter) {
        $this->_cellmap->remove_row($iter);
        $iter = $iter->get_next_sibling();
      }

      parent::split($child);
    }
  }

  /**
   * Static function to locate the parent table of a frame
   *
   * @param Frame $frame
   * @return Frame the table that is an ancestor of $frame
   */
  static function find_parent_table(Frame $frame) {

    while ( $frame = $frame->get_parent() )
      if ( in_array($frame->get_style()->display, Style::$TABLE_TYPES) )
        break;

    return $frame;
  }

  /**
   * Return this table's Cellmap
   *
   * @return Cellmap
   */
  function get_cellmap() { return $this->_cellmap; }

  /**
   * Return the minimum width of this table
   *
   * @return float
   */
  function get_min_width() { return $this->_min_width; }

  /**
   * Return the maximum width of this table
   *
   * @return float
   */
  function get_max_width() { return $this->_max_width; }

  /**
   * Set the minimum width of the table
   *
   * @param float $width the new minimum width
   */
  function set_min_width($width) { $this->_min_width = $width; }

  /**
   * Set the maximum width of the table
   *
   * @param float $width the new maximum width
   */
  function set_max_width($width) { $this->_max_width = $width; }

  /**
   * Restructure tree so that the table has the correct structure.
   * Invalid children (i.e. all non-table-rows) are moved below the
   * table.
   */
  function normalise() {

    // Store frames generated by invalid tags and move them outside the table
    $erroneous_frames = array();
    $anon_row = false;
    $iter = $this->get_first_child();
    while ( $iter ) {
      $child = $iter;
      $iter = $iter->get_next_sibling();

      $display = $child->get_style()->display;

      if ( $anon_row ) {

        if ( $display == "table-row" ) {
          // Add the previous anonymous row
          $this->insert_child_before($table_row, $child);

          $table_row->normalise();
          $child->normalise();
          $anon_row = false;
          continue;
        }

        // add the child to the anonymous row
        $table_row->append_child($child);
        continue;

      } else {

        if ( $display == "table-row" ) {
          $child->normalise();
          continue;
        }

        if ( $display == "table-cell") {
          // Create an anonymous table row
          $tr = $this->get_node()->ownerDocument->createElement("tr");

          $frame = new Frame($tr);

          $css = $this->get_style()->get_stylesheet();
          $style = $css->create_style();
          $style->inherit($this->get_style());

          // Lookup styles for tr tags.  If the user wants styles to work
          // better, they should make the tr explicit... I'm not going to
          // try to guess what they intended.
          if ( $tr_style = $css->lookup("tr") )
            $style->merge($tr_style);

          // Okay, I have absolutely no idea why I need this clone here, but
          // if it's omitted, php (as of 2004-07-28) segfaults.
          $frame->set_style(clone $style);
          $table_row = Frame_Factory::decorate_frame($frame, $this->_dompdf);
          $table_row->set_root($this->_root);

          // Add the cell to the row
          $table_row->append_child($child);

          $anon_row = true;
          continue;
        }

        if ( !in_array($display, self::$VALID_CHILDREN) ) {
          $erroneous_frames[] = $child;
          continue;
        }

        // Normalise other table parts (i.e. row groups)
        foreach ($child->get_children() as $grandchild) {
          if ( $grandchild->get_style()->display == "table-row" )
            $grandchild->normalise();
        }

        // Add headers and footers
        if ( $display == "table-header-group" )
          $this->_headers[] = $child;

        else if ( $display == "table-footer-group" )
          $this->_footers[] = $child;
      }
    }

    if ( $anon_row ) {
      // Add the row to the table
      $this->_frame->append_child($table_row);
      $table_row->normalise();
      $this->_cellmap->add_row();
    }

    foreach ($erroneous_frames as $frame)
      $this->move_after($frame);

  }

  //........................................................................

  /**
   * Moves the specified frame and it's corresponding node outside of
   * the table.
   *
   * @param Frame $frame the frame to move
   */
  function move_after(Frame $frame) {
    $this->get_parent()->insert_child_after($frame, $this);
  }

}
?>