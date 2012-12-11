<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Reflows table cells
 *
 * @access private
 * @package dompdf
 */
class Table_Cell_Frame_Reflower extends Block_Frame_Reflower {

  //........................................................................

  function __construct(Block_Frame_Decorator $frame) {
    parent::__construct($frame);
  }

  //........................................................................

  function reflow(Block_Frame_Decorator $block = null) {

    $style = $this->_frame->get_style();

    $table = Table_Frame_Decorator::find_parent_table($this->_frame);
    $cellmap = $table->get_cellmap();

    list($x, $y) = $cellmap->get_frame_position($this->_frame);
    $this->_frame->set_position($x, $y);

    $cells = $cellmap->get_spanned_cells($this->_frame);

    $w = 0;
    foreach ( $cells["columns"] as $i ) {
      $col = $cellmap->get_column( $i );
      $w += $col["used-width"];
    }

    //FIXME?
    $h = $this->_frame->get_containing_block("h");

    $left_space = $style->length_in_pt(array($style->margin_left,
                                             $style->padding_left,
                                             $style->border_left_width),
                                       $w);

    $right_space = $style->length_in_pt(array($style->padding_right,
                                              $style->margin_right,
                                              $style->border_right_width),
                                        $w);

    $top_space = $style->length_in_pt(array($style->margin_top,
                                            $style->padding_top,
                                            $style->border_top_width),
                                      $h);
    $bottom_space = $style->length_in_pt(array($style->margin_bottom,
                                               $style->padding_bottom,
                                               $style->border_bottom_width),
                                      $h);

    $style->width = $cb_w = $w - $left_space - $right_space;

    $content_x = $x + $left_space;
    $content_y = $line_y = $y + $top_space;

    // Adjust the first line based on the text-indent property
    $indent = $style->length_in_pt($style->text_indent, $w);
    $this->_frame->increase_line_width($indent);

    $page = $this->_frame->get_root();
    
    // Set the y position of the first line in the cell
    $line_box = $this->_frame->get_current_line_box();
    $line_box->y = $line_y;
    
    // Set the containing blocks and reflow each child
    foreach ( $this->_frame->get_children() as $child ) {
      
      if ( $page->is_full() )
        break;
    
      $child->set_containing_block($content_x, $content_y, $cb_w, $h);
      
      $this->process_clear($child);
      
      $child->reflow($this->_frame);
    
      $this->process_float($child, $x + $left_space, $w - $right_space - $left_space);
    }

    // Determine our height
    $style_height = $style->length_in_pt($style->height, $h);

    $this->_frame->set_content_height($this->_calculate_content_height());

    $height = max($style_height, $this->_frame->get_content_height());

    // Let the cellmap know our height
    $cell_height = $height / count($cells["rows"]);

    if ($style_height <= $height)
      $cell_height += $top_space + $bottom_space;

    foreach ($cells["rows"] as $i)
      $cellmap->set_row_height($i, $cell_height);

    $style->height = $height;

    $this->_text_align();

    $this->vertical_align();

  }

}
