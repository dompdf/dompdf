<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: table_row_group_frame_decorator.cls.php,v $
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
 * @copyright 2004-6 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @package dompdf
 */

/* $Id */

/**
 * Table row group decorator
 *
 * Overrides split() method for tbody, thead & tfoot elements
 *
 * @access private
 * @package dompdf
 */
class Table_Row_Group_Frame_Decorator extends Frame_Decorator {

  /**
   * Class constructor
   *
   * @param Frame $frame   Frame to decorate
   * @param DOMPDF $dompdf Current dompdf instance
   */
  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
  }

  /**
   * Override split() to remove all child rows and this element from the cellmap
   *
   * @param Frame $child
   */
  function split($child = null) {

    if ( is_null($child) ) {
      parent::split();
      return;
    }


    // Remove child & all subsequent rows from the cellmap
    $cellmap = $this->get_parent()->get_cellmap();
    $iter = $child;

    while ( $iter ) {
      $cellmap->remove_row($iter);
      $iter = $iter->get_next_sibling();
    }

    // If we are splitting at the first child remove the
    // table-row-group from the cellmap as well
    if ( $child === $this->get_first_child() ) {
      $cellmap->remove_row_group($this);
      parent::split();
      return;
    }
    
    $cellmap->update_row_group($this, $child->get_prev_sibling());
    parent::split($child);
    
  }
}
 
?>