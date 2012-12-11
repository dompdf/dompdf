<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Decorates table cells for layout
 *
 * @access private
 * @package dompdf
 */
class Table_Cell_Frame_Decorator extends Block_Frame_Decorator {
  
  protected $_resolved_borders;
  protected $_content_height;
  
  //........................................................................

  function __construct(Frame $frame, DOMPDF $dompdf) {
    parent::__construct($frame, $dompdf);
    $this->_resolved_borders = array();
    $this->_content_height = 0;    
  }

  //........................................................................

  function reset() {
    parent::reset();
    $this->_resolved_borders = array();
    $this->_content_height = 0;
    $this->_frame->reset();    
  }
  
  function get_content_height() {
    return $this->_content_height;
  }

  function set_content_height($height) {
    $this->_content_height = $height;
  }
  
  function set_cell_height($height) {
    $style = $this->get_style();
    $v_space = $style->length_in_pt(array($style->margin_top,
                                          $style->padding_top,
                                          $style->border_top_width,
                                          $style->border_bottom_width,
                                          $style->padding_bottom,
                                          $style->margin_bottom),
                                    $style->width);

    $new_height = $height - $v_space;    
    $style->height = $new_height;

    if ( $new_height > $this->_content_height ) {
      $y_offset = 0;
      
      // Adjust our vertical alignment
      switch ($style->vertical_align) {
        default:
        case "baseline":
          // FIXME: this isn't right
          
        case "top":
          // Don't need to do anything
          return;
  
        case "middle":
          $y_offset = ($new_height - $this->_content_height) / 2;
          break;
  
        case "bottom":
          $y_offset = $new_height - $this->_content_height;
          break;
      }
   
      if ( $y_offset ) {
        // Move our children
        foreach ( $this->get_line_boxes() as $line ) {
          foreach ( $line->get_frames() as $frame )
            $frame->move( 0, $y_offset );
        }
      }
   }
        
  }

  function set_resolved_border($side, $border_spec) {    
    $this->_resolved_borders[$side] = $border_spec;
  }

  //........................................................................

  function get_resolved_border($side) {
    return $this->_resolved_borders[$side];
  }

  function get_resolved_borders() { return $this->_resolved_borders; }
}
