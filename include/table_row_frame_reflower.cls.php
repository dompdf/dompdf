<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: table_row_frame_reflower.cls.php,v $
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

/* $Id$ */

/**
 * Reflows table rows
 *
 * @access private
 * @package dompdf
 */
class Table_Row_Frame_Reflower extends Frame_Reflower {


  function __construct(Table_Row_Frame_Decorator $frame) {
    parent::__construct($frame);
  }

  //........................................................................

  function reflow() {
    $page = $this->_frame->get_root();

    if ( $page->is_full() )
      return;

    $this->_frame->position();
    $style = $this->_frame->get_style();
    $cb = $this->_frame->get_containing_block();

    foreach ($this->_frame->get_children() as $child) {

      if ( $page->is_full() )
        return;

      $child->set_containing_block($cb);
      $child->reflow();

    }

    if ( $page->is_full() )
      return;

    $table = Table_Frame_Decorator::find_parent_table($this->_frame);
    $cellmap = $table->get_cellmap();
    $style->width = $cellmap->get_frame_width($this->_frame);
    $style->height = $cellmap->get_frame_height($this->_frame);

    $this->_frame->set_position($cellmap->get_frame_position($this->_frame));

  }

  //........................................................................

  function get_min_max_width() {
    throw new DOMPDF_Exception("Min/max width is undefined for table rows");
  }
}
?>