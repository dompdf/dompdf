<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version $Id$
 */

/**
 * Base Frame_Decorator class
 *
 * @access private
 * @package dompdf
 */
abstract class Frame_Decorator extends Frame {
  const DEFAULT_COUNTER = "-dompdf-default-counter";
  
  public $_counters = array(); // array([id] => counter_value) (for generated content)
  
  /**
   * The root node of the DOM tree
   *
   * @var Frame
   */
  protected $_root;

  /**
   * The decorated frame
   *
   * @var Frame
   */
  protected $_frame;

  /**
   * Positioner object used to position this frame (Strategy pattern)
   *
   * @var Positioner
   */
  protected $_positioner;

  /**
   * Reflower object used to calculate frame dimensions (Strategy pattern)
   *
   * @var Frame_Reflower
   */
  protected $_reflower;
  
  /**
   * Reference to the current dompdf instance
   *
   * @var DOMPDF
   */
  protected $_dompdf;
  
  /**
   * First block parent
   * 
   * @var Block_Frame_Decorator
   */
  private $_block_parent;
  
  /**
   * First positionned parent (position: relative | absolute | fixed)
   * 
   * @var Frame_Decorator
   */
  private $_positionned_parent;

  /**
   * Class constructor
   *
   * @param Frame $frame the decoration target
   */
  function __construct(Frame $frame, DOMPDF $dompdf) {
    $this->_frame = $frame;
    $this->_root = null;
    $this->_dompdf = $dompdf;
    $frame->set_decorator($this);
  }

  /**
   * "Destructor": foribly free all references held by this object
   *
   * @param bool $recursive if true, call dispose on all children
   */
  function dispose($recursive = false) {
    
    if ( $recursive ) {
      while ( $child = $this->get_first_child() )
        $child->dispose(true);
    }
    
    $this->_root = null;
    unset($this->_root);
    
    $this->_frame->dispose(true);
    $this->_frame = null;
    unset($this->_frame);
    
    $this->_positioner = null;
    unset($this->_positioner);
    
    $this->_reflower = null;
    unset($this->_reflower);
  }

  /**
   * Return a copy of this frame with $node as its node
   * 
   * @param DomNode $node 
   * @return Frame
   */ 
  function copy(DomNode $node) {
    $frame = new Frame($node);
    $frame->set_style(clone $this->_frame->get_original_style());
    $deco = Frame_Factory::decorate_frame($frame, $this->_dompdf);
    $deco->set_root($this->_root);
    return $deco;
  }

  /**
   * Create a deep copy: copy this node and all children
   *
   * @return Frame
   */
  function deep_copy() {
    $frame = new Frame($this->get_node()->cloneNode());
    $frame->set_style(clone $this->_frame->get_original_style());
    $deco = Frame_Factory::decorate_frame($frame, $this->_dompdf);
    $deco->set_root($this->_root);

    foreach ($this->get_children() as $child)
      $deco->append_child($child->deep_copy());

    return $deco;
  }
  //........................................................................
  
  /**
   * Delegate calls to decorated frame object
   */
  function reset() {
    $this->_frame->reset();
    
    $this->_counters = array();

    // Reset all children
    foreach ($this->get_children() as $child)
      $child->reset();
  }
  
  // Getters -----------
  function get_id() { return $this->_frame->get_id(); }
  
  /**
   * @return Frame
   */
  function get_frame() { return $this->_frame; }
  
  /**
   * @return DomNode
   */
  function get_node() { return $this->_frame->get_node(); }
  
  /**
   * @return Style
   */
  function get_style() { return $this->_frame->get_style(); }
  
  /**
   * @return Style
   */
  function get_original_style() { return $this->_frame->get_original_style(); }
  function get_containing_block($i = null) { return $this->_frame->get_containing_block($i); }
  function get_position($i = null) { return $this->_frame->get_position($i); }
  
  /**
   * @return DOMPDF
   */
  function get_dompdf() { return $this->_dompdf; }
  
  function get_margin_height() { return $this->_frame->get_margin_height(); }
  function get_margin_width() { return $this->_frame->get_margin_width(); }
  function get_padding_box() { return $this->_frame->get_padding_box(); }
  function get_border_box() { return $this->_frame->get_border_box(); }

  // Setters -----------
  function set_id($id) { $this->_frame->set_id($id); }
  function set_style(Style $style) { $this->_frame->set_style($style); }

  function set_containing_block($x = null, $y = null, $w = null, $h = null) {
    $this->_frame->set_containing_block($x, $y, $w, $h);
  }

  function set_position($x = null, $y = null) {
    $this->_frame->set_position($x, $y);
  }
  function __toString() { return $this->_frame->__toString(); }
  
  function prepend_child(Frame $child, $update_node = true) {
    while ( $child instanceof Frame_Decorator )
      $child = $child->_frame;
    
    $this->_frame->prepend_child($child, $update_node);
  }

  function append_child(Frame $child, $update_node = true) {
    while ( $child instanceof Frame_Decorator )
      $child = $child->_frame;

    $this->_frame->append_child($child, $update_node);
  }

  function insert_child_before(Frame $new_child, Frame $ref, $update_node = true) {
    while ( $new_child instanceof Frame_Decorator )
      $new_child = $new_child->_frame;

    if ( $ref instanceof Frame_Decorator )
      $ref = $ref->_frame;

    $this->_frame->insert_child_before($new_child, $ref, $update_node);
  }

  function insert_child_after(Frame $new_child, Frame $ref, $update_node = true) {
    while ( $new_child instanceof Frame_Decorator )
      $new_child = $new_child->_frame;

    while ( $ref instanceof Frame_Decorator )
      $ref = $ref->_frame;
    
    $this->_frame->insert_child_after($new_child, $ref, $update_node);
  }

  function remove_child(Frame $child, $update_node = true) {
    while  ( $child instanceof Frame_Decorator )
      $child = $new_child->_frame;

    $this->_frame->remove_child($child, $update_node);
  }
  
  //........................................................................

  /**
   * @return Frame_Decorator
   */
  function get_parent() {
    $p = $this->_frame->get_parent();
    if ( $p && $deco = $p->get_decorator() ) {
      while ( $tmp = $deco->get_decorator() )
        $deco = $tmp;      
      return $deco;
    } else if ( $p )
      return $p;
    else
      return null;
  }

  /**
   * @return Frame_Decorator
   */
  function get_first_child() {
    $c = $this->_frame->get_first_child();
    if ( $c && $deco = $c->get_decorator() ) {
      while ( $tmp = $deco->get_decorator() )
        $deco = $tmp;      
      return $deco;
    } else if ( $c )
      return $c;
    else
      return null;
  }

  /**
   * @return Frame_Decorator
   */
  function get_last_child() {
    $c = $this->_frame->get_last_child();
    if ( $c && $deco = $c->get_decorator() ) {
      while ( $tmp = $deco->get_decorator() )
        $deco = $tmp;      
      return $deco;
    } else if ( $c )
      return $c;
    else
      return null;
  }

  /**
   * @return Frame_Decorator
   */
  function get_prev_sibling() {
    $s = $this->_frame->get_prev_sibling();
    if ( $s && $deco = $s->get_decorator() ) {
      while ( $tmp = $deco->get_decorator() )
        $deco = $tmp;      
      return $deco;
    } else if ( $s )
      return $s;
    else
      return null;
  }
  
  /**
   * @return Frame_Decorator
   */
  function get_next_sibling() {
    $s = $this->_frame->get_next_sibling();
    if ( $s && $deco = $s->get_decorator() ) {
      while ( $tmp = $deco->get_decorator() )
        $deco = $tmp;      
      return $deco;
    } else if ( $s )
      return $s;
    else
      return null;
  }

  /**
   * @return FrameTreeList
   */
  function get_subtree() {
    return new FrameTreeList($this);
  }
  
  //........................................................................

  function set_positioner(Positioner $posn) {
    $this->_positioner = $posn;
    if ( $this->_frame instanceof Frame_Decorator )
      $this->_frame->set_positioner($posn);
  }
  
  //........................................................................

  function set_reflower(Frame_Reflower $reflower) {
    $this->_reflower = $reflower;
    if ( $this->_frame instanceof Frame_Decorator )
      $this->_frame->set_reflower( $reflower );
  }
  
  /**
   * @return Frame_Reflower
   */
  function get_reflower() { return $this->_reflower; }
  
  //........................................................................
  
  function set_root(Frame $root) {
    $this->_root = $root;
      if ( $this->_frame instanceof Frame_Decorator )
        $this->_frame->set_root($root);
  }
  
  /**
   * @return Page_Frame_Decorator
   */
  function get_root() { return $this->_root; }
  
  //........................................................................

  /**
   * @return Block_Frame_Decorator
   */
  function find_block_parent() {
    /*if ( $this->_block_parent && !isset($this->_block_parent->_splitted) ) {
      return $this->_block_parent;
    }*/
    
    // Find our nearest block level parent
    $p = $this->get_parent();
    
    while ( $p ) {
      if ( $p->is_block() ) break;
      $p = $p->get_parent();
    }

    return $this->_block_parent = $p;
  }
  
  /**
   * @return Frame_Decorator
   */
  function find_positionned_parent() {
    /*if ( $this->_positionned_parent && !isset($this->_block_parent->_splitted) ) {
      return $this->_positionned_parent;
    }*/

    // Find our nearest relative positionned parent
    $p = $this->get_parent();
    while ( $p ) {
      if ( $p->is_positionned() ) break;
      $p = $p->get_parent();
    }
    
    if ( !$p ) {
      $p = $this->_root->get_first_child(); // <body>
    }

    return $this->_positionned_parent = $p;
  }

  //........................................................................

  /**
   * split this frame at $child.
   *
   * The current frame is cloned and $child and all children following
   * $child are added to the clone.  The clone is then passed to the
   * current frame's parent->split() method.
   *
   * @param Frame $child
   * @param boolean $force_pagebreak
   */
  function split($child = null, $force_pagebreak = false) {
    if ( is_null( $child ) ) {
      $this->get_parent()->split($this, $force_pagebreak);
      return;
    }

    if ( $child->get_parent() !== $this )
      throw new DOMPDF_Exception("Unable to split: frame is not a child of this one.");

    $node = $this->_frame->get_node();
    
    // mark the frame as splitted (don't use the find_***_parent cache)
    //$this->_splitted = true;
    
    $split = $this->copy( $node->cloneNode() );
    $split->reset();
    $split->get_original_style()->text_indent = 0;
    
    // The body's properties must be kept
    if ( $node->nodeName !== "body" ) {
      // Style reset on the first and second parts
      $style = $this->_frame->get_style();
      $style->margin_bottom = 0;
      $style->padding_bottom = 0;
      $style->border_bottom = 0;
      
      // second
      $orig_style = $split->get_original_style();
      $orig_style->text_indent = 0;
      $orig_style->margin_top = 0;
      $orig_style->padding_top = 0;
      $orig_style->border_top = 0;
    }
    
    $this->get_parent()->insert_child_after($split, $this);

    // Add $frame and all following siblings to the new split node
    $iter = $child;
    while ($iter) {
      $frame = $iter;      
      $iter = $iter->get_next_sibling();
      $frame->reset();
      $split->append_child($frame);
    }

    $this->get_parent()->split($split, $force_pagebreak);
  }

  function reset_counter($id = self::DEFAULT_COUNTER, $value = 0) {
    $this->get_parent()->_counters[$id] = $value;
  }
  
  function increment_counters($counters) {
    foreach($counters as $id => $increment) {
      $this->increment_counter($id, $increment);
    }
  }

  function increment_counter($id = self::DEFAULT_COUNTER, $increment = 1) {
    $counter_frame = $this->lookup_counter_frame($id);

    if ( $counter_frame ) {
      if ( !isset($counter_frame->_counters[$id]) ) {
        $counter_frame->_counters[$id] = 0;
      }
      
      $counter_frame->_counters[$id] += $increment;
    }
  }
  
  function lookup_counter_frame($id = self::DEFAULT_COUNTER) {
    $f = $this->get_parent();
    
    while( $f ) {
      if( isset($f->_counters[$id]) ) {
        return $f;
      }
      $fp = $f->get_parent();
      
      if ( !$fp ) {
        return $f;
      }
      
      $f = $fp;
    }
  }

  // TODO: What version is the best : this one or the one in List_Bullet_Renderer ?
  function counter_value($id = self::DEFAULT_COUNTER, $type = "decimal") {
    $type = mb_strtolower($type);
    
    if ( !isset($this->_counters[$id]) ) {
      $value = $this->_counters[$id] = 0;
    }
    else {
      $value = $this->_counters[$id];
    }
    
    switch ($type) {

    default:
    case "decimal":
      return $value;

    case "decimal-leading-zero":
      return str_pad($value, 2, "0");

    case "lower-roman":
      return dec2roman($value);

    case "upper-roman":
      return mb_strtoupper(dec2roman($value));

    case "lower-latin":
    case "lower-alpha":
      return chr( ($value % 26) + ord('a') - 1);

    case "upper-latin":
    case "upper-alpha":
      return chr( ($value % 26) + ord('A') - 1);

    case "lower-greek":
      return unichr($value + 944);

    case "upper-greek":
      return unichr($value + 912);
    }
  }

  //........................................................................

  final function position() { $this->_positioner->position();  }
  
  final function move($offset_x, $offset_y, $ignore_self = false) { 
    $this->_positioner->move($offset_x, $offset_y, $ignore_self); 
  }
  
  final function reflow(Frame_Decorator $block = null) {
    // Uncomment this to see the frames before they're laid out, instead of
    // during rendering.
    //echo $this->_frame; flush();
    $this->_reflower->reflow($block);
  }

  final function get_min_max_width() { return $this->_reflower->get_min_max_width(); }
  
  //........................................................................
}
