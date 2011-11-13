<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version $Id$
 */

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
  function split($child = null, $force_pagebreak = false) {

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
 
