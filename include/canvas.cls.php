<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: canvas.cls.php,v $
 * Created on: 2004-06-06
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

/* $Id: canvas.cls.php,v 1.1.1.1 2005-01-25 22:56:01 benjcarson Exp $ */

/**
 * Main rendering interface
 *
 * Currently only {@link PDF_Adapter} implements this interface.  However,
 * additional implementations (e.g. using PDFlib, FPDF or even GD) would be
 * nice to see someday ;).
 *
 * Implementations should measure x and y increasing to the left and down,
 * respectively, with the origin in the top left corner.  Implementations
 * are free to use a unit other than points for length, but I can't
 * guarantee that the results will look any good.  If a different unit is
 * used, the {@link Font_Metrics} class will likely have to take this into
 * account.
 *
 * @package dompdf
 */
interface Canvas {

  /**
   * Returns the current page number
   *
   * @return int
   */
  function get_page_number();

  /**
   * Returns the total number of pages
   *
   * @return int
   */
  function get_page_count();

  /**
   * Sets the total number of pages
   *
   * @param int $count
   */
  function set_page_count($count);

  /**
   * Draws a line from x1,y1 to x2,y2
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
   * See {@link Cpdf::setLineStyle()} for a description of the format of the
   * $style parameter (aka dash).
   *
   * @param float $x1
   * @param float $y1
   * @param float $x2
   * @param float $y2
   * @param array $color
   * @param float $width
   * @param array $style
   */
  function line($x1, $y1, $x2, $y2, $color, $width, $style = null);

  /**
   * Draws a rectangle at x1,y1 with width w and height h
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
   * See {@link Cpdf::setLineStyle()} for a description of the $style
   * parameter (aka dash)
   *
   * @param float $x1
   * @param float $y1
   * @param float $w
   * @param float $h
   * @param array $color
   * @param float $width
   * @param array $style
   */   
  function rectangle($x1, $y1, $w, $h, $color, $width, $style = null);

  /**
   * Draws a filled rectangle at x1,y1 with width w and height h
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
   *
   * @param float $x1
   * @param float $y1
   * @param float $w
   * @param float $h
   * @param array $color
   */   
  function filled_rectangle($x1, $y1, $w, $h, $color);

  /**
   * Draws a polygon
   *
   * The ploygon is formed by joining all the points stored in the $points
   * array.  $points has the following structure:
   * <code>
   * array(0 => array(x, y),
   *       1 => array(x, y),
   *       ...
   *       );
   * </code>
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
   * See {@link Cpdf::setLineStyle()} for a description of the $style
   * parameter (aka dash)   
   *
   * @param array $points
   * @param array $color
   * @param float $width
   * @param array $style
   * @param bool fill  Fills the polygon if true
   */
  function polygon(&$points, $color, $width = null, $style = null, $fill = false);

  /**
   * Draws a circle at x,y with radius $r1
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
   * See {@link Cpdf::setLineStyle()} for a description of the $style
   * parameter (aka dash)
   *
   * @param float $x1
   * @param float $y1
   * @param float $r1
   * @param array $color
   * @param float $width
   * @param array $style
   * @param bool $fill Fills the circle if true   
   */   
  function circle($x, $y, $r1, $color, $width = null, $style = null, $fill = false);

  /**
   * {@internal Add an image to the page at the specifed x & y coords}}
   * @access private
   */
  function image($img_url, $img_type, $x, $y, $w, $h);

  /**
   * Writes text at the specified x and y coordinates
   *
   * See {@link Style::munge_colour()} for the format of the colour array.
   *
   * @param float $x
   * @param float $y
   * @param string $text the text to write
   * @param string $font the font file to use
   * @param float $size the font size, in points
   * @param array $color
   * @param float $adjust word spacing adjustment
   * @param float $angle angle to write the text at, measured CW starting from the x-axis
   */
  function text($x, $y, $text, $font, $size, $color = array(0,0,0), $adjust = 0);

  /**
   * Starts a new page
   *
   * Subsequent drawing operations will appear on the new page.
   */
  function new_page();
  
}
?>