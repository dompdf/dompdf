<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Decorates Frames for table row layout
 *
 * @access private
 * @package dompdf
 */
class Table_Row_Frame_Decorator extends Frame_Decorator {

  // protected members
  
  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
  }
  
  //........................................................................ 

  /**
   * Remove all non table-cell frames from this row and move them after
   * the table.
   */
  function normalise() {

    // Find our table parent
    $p = Table_Frame_Decorator::find_parent_table($this);
    
    $erroneous_frames = array();
    foreach ($this->get_children() as $child) {      
      $display = $child->get_style()->display;

      if ( $display !== "table-cell" )
        $erroneous_frames[] = $child;
    }
    
    //  dump the extra nodes after the table.
    foreach ($erroneous_frames as $frame) 
      $p->move_after($frame);
  }
  
  
}
