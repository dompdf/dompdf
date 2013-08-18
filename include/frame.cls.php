<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * The main Frame class
 *
 * This class represents a single HTML element.  This class stores
 * positioning information as well as containing block location and
 * dimensions. Style information for the element is stored in a {@link
 * Style} object.  Tree structure is maintained via the parent & children
 * links.
 *
 * @access protected
 * @package dompdf
 */
class Frame {

  /**
   * The DOMElement or DOMText object this frame represents
   *
   * @var DOMElement|DOMText
   */
  protected $_node;

  /**
   * Unique identifier for this frame.  Used to reference this frame
   * via the node.
   *
   * @var string
   */
  protected $_id;

  /**
   * Unique id counter
   */
  static /*protected*/ $ID_COUNTER = 0;

  /**
   * This frame's calculated style
   *
   * @var Style
   */
  protected $_style;

  /**
   * This frame's original style.  Needed for cases where frames are
   * split across pages.
   *
   * @var Style
   */
  protected $_original_style;

  /**
   * This frame's parent in the document tree.
   *
   * @var Frame
   */
  protected $_parent;

  /**
   * This frame's children
   *
   * @var Frame[]
   */
  protected $_frame_list;

  /**
   * This frame's first child.  All children are handled as a
   * doubly-linked list.
   *
   * @var Frame
   */
  protected $_first_child;

  /**
   * This frame's last child.
   *
   * @var Frame
   */
  protected $_last_child;

  /**
   * This frame's previous sibling in the document tree.
   *
   * @var Frame
   */
  protected $_prev_sibling;

  /**
   * This frame's next sibling in the document tree.
   *
   * @var Frame
   */
  protected $_next_sibling;

  /**
   * This frame's containing block (used in layout): array(x, y, w, h)
   *
   * @var float[]
   */
  protected $_containing_block;

  /**
   * Position on the page of the top-left corner of the margin box of
   * this frame: array(x,y)
   *
   * @var float[]
   */
  protected $_position;

  /**
   * Absolute opacity of this frame
   *
   * @var float
   */
  protected $_opacity;

  /**
   * This frame's decorator
   *
   * @var Frame_Decorator
   */
  protected $_decorator;

  /**
   * This frame's containing line box
   *
   * @var Line_Box
   */
  protected $_containing_line;

  protected $_is_cache = array();

  /**
   * Tells wether the frame was already pushed to the next page
   *
   * @var bool
   */
  public $_already_pushed = false;

  public $_float_next_line = false;

  /**
   * Tells wether the frame was split
   *
   * @var bool
   */
  public $_splitted;

  static $_ws_state = self::WS_SPACE;

  const WS_TEXT = 1;
  const WS_SPACE = 2;

  /**
   * Class destructor
   */
  function __destruct() {
    clear_object($this);
  }

  /**
   * Class constructor
   *
   * @param DOMNode $node the DOMNode this frame represents
   */
  function __construct(DOMNode $node) {
    $this->_node = $node;

    $this->_parent = null;
    $this->_first_child = null;
    $this->_last_child = null;
    $this->_prev_sibling = $this->_next_sibling = null;

    $this->_style = null;
    $this->_original_style = null;

    $this->_containing_block = array(
      "x" => null,
      "y" => null,
      "w" => null,
      "h" => null,
    );

    $this->_containing_block[0] =& $this->_containing_block["x"];
    $this->_containing_block[1] =& $this->_containing_block["y"];
    $this->_containing_block[2] =& $this->_containing_block["w"];
    $this->_containing_block[3] =& $this->_containing_block["h"];

    $this->_position = array(
      "x" => null,
      "y" => null,
    );

    $this->_position[0] =& $this->_position["x"];
    $this->_position[1] =& $this->_position["y"];

    $this->_opacity = 1.0;
    $this->_decorator = null;

    $this->set_id( self::$ID_COUNTER++ );
  }

  // WIP : preprocessing to remove all the unused whitespace
  protected function ws_trim(){
    if ( $this->ws_keep() ) {
      return;
    }

    switch(self::$_ws_state) {
      case self::WS_SPACE:
        $node = $this->_node;

        if ( $node->nodeName === "#text" ) {
          $node->nodeValue = preg_replace("/[ \t\r\n\f]+/u", " ", $node->nodeValue);

          // starts with a whitespace
          if ( isset($node->nodeValue[0]) && $node->nodeValue[0] === " " ) {
            $node->nodeValue = ltrim($node->nodeValue);
          }

          // if not empty
          if ( $node->nodeValue !== "" ) {
            // change the current state (text)
            self::$_ws_state = self::WS_TEXT;

            // ends with a whitespace
            if ( preg_match("/[ \t\r\n\f]+$/u", $node->nodeValue) ) {
              $node->nodeValue = ltrim($node->nodeValue);
            }
          }
        }
      break;

      case self::WS_TEXT:
    }
  }

  protected function ws_keep(){
    $whitespace = $this->get_style()->white_space;
    return in_array($whitespace, array("pre", "pre-wrap", "pre-line"));
  }

  protected function ws_is_text(){
    $node = $this->get_node();

    if ($node->nodeName === "img") {
      return true;
    }

    if ( !$this->is_in_flow() ) {
      return false;
    }

    if ($this->is_text_node()) {
      return trim($node->nodeValue) !== "";
    }

    return true;
  }

  /**
   * "Destructor": forcibly free all references held by this frame
   *
   * @param bool $recursive if true, call dispose on all children
   */
  function dispose($recursive = false) {

    if ( $recursive ) {
      while ( $child = $this->_first_child ) {
        $child->dispose(true);
      }
    }

    // Remove this frame from the tree
    if ( $this->_prev_sibling ) {
      $this->_prev_sibling->_next_sibling = $this->_next_sibling;
    }

    if ( $this->_next_sibling ) {
      $this->_next_sibling->_prev_sibling = $this->_prev_sibling;
    }

    if ( $this->_parent && $this->_parent->_first_child === $this ) {
      $this->_parent->_first_child = $this->_next_sibling;
    }

    if ( $this->_parent && $this->_parent->_last_child === $this ) {
      $this->_parent->_last_child = $this->_prev_sibling;
    }

    if ( $this->_parent ) {
      $this->_parent->get_node()->removeChild($this->_node);
    }

    $this->_style->dispose();
    $this->_style = null;
    unset($this->_style);

    $this->_original_style->dispose();
    $this->_original_style = null;
    unset($this->_original_style);

  }

  // Re-initialize the frame
  function reset() {
    $this->_position["x"] = null;
    $this->_position["y"] = null;

    $this->_containing_block["x"] = null;
    $this->_containing_block["y"] = null;
    $this->_containing_block["w"] = null;
    $this->_containing_block["h"] = null;

    $this->_style = null;
    unset($this->_style);
    $this->_style = clone $this->_original_style;
  }

  //........................................................................

  /**
   * @return DOMElement|DOMText
   */
  function get_node() {
    return $this->_node;
  }

  /**
   * @return string
   */
  function get_id() {
    return $this->_id;
  }

  /**
   * @return Style
   */
  function get_style() {
    return $this->_style;
  }

  /**
   * @return Style
   */
  function get_original_style() {
    return $this->_original_style;
  }

  /**
   * @return Frame
   */
  function get_parent() {
    return $this->_parent;
  }

  /**
   * @return Frame_Decorator
   */
  function get_decorator() {
    return $this->_decorator;
  }

  /**
   * @return Frame
   */
  function get_first_child() {
    return $this->_first_child;
  }

  /**
   * @return Frame
   */
  function get_last_child() {
    return $this->_last_child;
  }

  /**
   * @return Frame
   */
  function get_prev_sibling() {
    return $this->_prev_sibling;
  }

  /**
   * @return Frame
   */
  function get_next_sibling() {
    return $this->_next_sibling;
  }

  /**
   * @return FrameList|Frame[]
   */
  function get_children() {
    if ( isset($this->_frame_list) ) {
      return $this->_frame_list;
    }

    $this->_frame_list = new FrameList($this);
    return $this->_frame_list;
  }

  // Layout property accessors

  /**
   * Containing block dimensions
   *
   * @param $i string The key of the wanted containing block's dimension (x, y, x, h)
   *
   * @return float[]|float
   */
  function get_containing_block($i = null) {
    if ( isset($i) ) {
      return $this->_containing_block[$i];
    }
    return $this->_containing_block;
  }

  /**
   * Block position
   *
   * @param $i string The key of the wanted position value (x, y)
   *
   * @return array|float
   */
  function get_position($i = null) {
    if ( isset($i) ) {
      return $this->_position[$i];
    }
    return $this->_position;
  }

  //........................................................................

  /**
   * Return the height of the margin box of the frame, in pt.  Meaningless
   * unless the height has been calculated properly.
   *
   * @return float
   */
  function get_margin_height() {
    $style = $this->_style;

    return $style->length_in_pt(array(
      $style->height,
      $style->margin_top,
      $style->margin_bottom,
      $style->border_top_width,
      $style->border_bottom_width,
      $style->padding_top,
      $style->padding_bottom
    ), $this->_containing_block["h"]);
  }

  /**
   * Return the width of the margin box of the frame, in pt.  Meaningless
   * unless the width has been calculated properly.
   *
   * @return float
   */
  function get_margin_width() {
    $style = $this->_style;

    return $style->length_in_pt(array(
      $style->width,
      $style->margin_left,
      $style->margin_right,
      $style->border_left_width,
      $style->border_right_width,
      $style->padding_left,
      $style->padding_right
    ), $this->_containing_block["w"]);
  }

  function get_break_margins(){
    $style = $this->_style;

    return $style->length_in_pt(array(
      //$style->height,
      $style->margin_top,
      $style->margin_bottom,
      $style->border_top_width,
      $style->border_bottom_width,
      $style->padding_top,
      $style->padding_bottom
    ), $this->_containing_block["h"]);
  }

  /**
   * Return the padding box (x,y,w,h) of the frame
   *
   * @return array
   */
  function get_padding_box() {
    $style = $this->_style;
    $cb = $this->_containing_block;

    $x = $this->_position["x"] +
      $style->length_in_pt(array($style->margin_left,
                                 $style->border_left_width),
                           $cb["w"]);

    $y = $this->_position["y"] +
      $style->length_in_pt(array($style->margin_top,
                                 $style->border_top_width),
                           $cb["h"]);

    $w = $style->length_in_pt(array($style->padding_left,
                                    $style->width,
                                    $style->padding_right),
                              $cb["w"]);

    $h = $style->length_in_pt(array($style->padding_top,
                                    $style->height,
                                    $style->padding_bottom),
                             $cb["h"]);

    return array(0 => $x, "x" => $x,
                 1 => $y, "y" => $y,
                 2 => $w, "w" => $w,
                 3 => $h, "h" => $h);
  }

  /**
   * Return the border box of the frame
   *
   * @return array
   */
  function get_border_box() {
    $style = $this->_style;
    $cb = $this->_containing_block;

    $x = $this->_position["x"] + $style->length_in_pt($style->margin_left, $cb["w"]);

    $y = $this->_position["y"] + $style->length_in_pt($style->margin_top,  $cb["h"]);

    $w = $style->length_in_pt(array($style->border_left_width,
                                    $style->padding_left,
                                    $style->width,
                                    $style->padding_right,
                                    $style->border_right_width),
                              $cb["w"]);

    $h = $style->length_in_pt(array($style->border_top_width,
                                    $style->padding_top,
                                    $style->height,
                                    $style->padding_bottom,
                                    $style->border_bottom_width),
                              $cb["h"]);

    return array(0 => $x, "x" => $x,
                 1 => $y, "y" => $y,
                 2 => $w, "w" => $w,
                 3 => $h, "h" => $h);
  }

  function get_opacity($opacity = null) {
    if ( $opacity !== null ) {
      $this->set_opacity($opacity);
    }
    return $this->_opacity;
  }

  /**
   * @return Line_Box
   */
  function &get_containing_line() {
    return $this->_containing_line;
  }

  //........................................................................

  // Set methods
  function set_id($id) {
    $this->_id = $id;

    // We can only set attributes of DOMElement objects (nodeType == 1).
    // Since these are the only objects that we can assign CSS rules to,
    // this shortcoming is okay.
    if ( $this->_node->nodeType == XML_ELEMENT_NODE ) {
      $this->_node->setAttribute("frame_id", $id);
    }
  }

  function set_style(Style $style) {
    if ( is_null($this->_style) ) {
      $this->_original_style = clone $style;
    }

    //$style->set_frame($this);
    $this->_style = $style;
  }

  function set_decorator(Frame_Decorator $decorator) {
    $this->_decorator = $decorator;
  }

  function set_containing_block($x = null, $y = null, $w = null, $h = null) {
    if ( is_array($x) ){
      foreach($x as $key => $val){
        $$key = $val;
      }
    }

    if (is_numeric($x)) {
      $this->_containing_block["x"] = $x;
    }

    if (is_numeric($y)) {
      $this->_containing_block["y"] = $y;
    }

    if (is_numeric($w)) {
      $this->_containing_block["w"] = $w;
    }

    if (is_numeric($h)) {
      $this->_containing_block["h"] = $h;
    }
  }

  function set_position($x = null, $y = null) {
    if ( is_array($x) ) {
      list($x, $y) = array($x["x"], $x["y"]);
    }

    if ( is_numeric($x) ) {
      $this->_position["x"] = $x;
    }

    if ( is_numeric($y) ) {
      $this->_position["y"] = $y;
    }
  }

  function set_opacity($opacity) {
    $parent = $this->get_parent();
    $base_opacity = (($parent && $parent->_opacity !== null) ? $parent->_opacity : 1.0);
    $this->_opacity = $base_opacity * $opacity;
  }

  function set_containing_line(Line_Box $line) {
    $this->_containing_line = $line;
  }

  //........................................................................

  /**
   * Tells if the frame is a text node
   * @return bool
   */
  function is_text_node() {
    if ( isset($this->_is_cache["text_node"]) ) {
      return $this->_is_cache["text_node"];
    }

    return $this->_is_cache["text_node"] = ($this->get_node()->nodeName === "#text");
  }

  function is_positionned() {
    if ( isset($this->_is_cache["positionned"]) ) {
      return $this->_is_cache["positionned"];
    }

    $position = $this->get_style()->position;

    return $this->_is_cache["positionned"] = in_array($position, Style::$POSITIONNED_TYPES);
  }

  function is_absolute() {
    if ( isset($this->_is_cache["absolute"]) ) {
      return $this->_is_cache["absolute"];
    }

    $position = $this->get_style()->position;

    return $this->_is_cache["absolute"] = ($position === "absolute" || $position === "fixed");
  }

  function is_block() {
    if ( isset($this->_is_cache["block"]) ) {
      return $this->_is_cache["block"];
    }

    return $this->_is_cache["block"] = in_array($this->get_style()->display, Style::$BLOCK_TYPES);
  }

  function is_in_flow() {
    if ( isset($this->_is_cache["in_flow"]) ) {
      return $this->_is_cache["in_flow"];
    }

    $enable_css_float = $this->get_style()->get_stylesheet()->get_dompdf()->get_option("enable_css_float");
    return $this->_is_cache["in_flow"] = !($enable_css_float && $this->get_style()->float !== "none" || $this->is_absolute());
  }

  function is_pre(){
    if ( isset($this->_is_cache["pre"]) ) {
      return $this->_is_cache["pre"];
    }

    $white_space = $this->get_style()->white_space;

    return $this->_is_cache["pre"] = in_array($white_space, array("pre", "pre-wrap"));
  }

  function is_table(){
    if ( isset($this->_is_cache["table"]) ) {
      return $this->_is_cache["table"];
    }

    $display = $this->get_style()->display;

    return $this->_is_cache["table"] = in_array($display, Style::$TABLE_TYPES);
  }


  /**
   * Inserts a new child at the beginning of the Frame
   *
   * @param $child Frame The new Frame to insert
   * @param $update_node boolean Whether or not to update the DOM
   */
  function prepend_child(Frame $child, $update_node = true) {
    if ( $update_node ) {
      $this->_node->insertBefore($child->_node, $this->_first_child ? $this->_first_child->_node : null);
    }

    // Remove the child from its parent
    if ( $child->_parent ) {
      $child->_parent->remove_child($child, false);
    }

    $child->_parent = $this;
    $child->_prev_sibling = null;

    // Handle the first child
    if ( !$this->_first_child ) {
      $this->_first_child = $child;
      $this->_last_child = $child;
      $child->_next_sibling = null;
    }
    else {
      $this->_first_child->_prev_sibling = $child;
      $child->_next_sibling = $this->_first_child;
      $this->_first_child = $child;
    }
  }

  /**
   * Inserts a new child at the end of the Frame
   *
   * @param $child Frame The new Frame to insert
   * @param $update_node boolean Whether or not to update the DOM
   */
  function append_child(Frame $child, $update_node = true) {
    if ( $update_node ) {
      $this->_node->appendChild($child->_node);
    }

    // Remove the child from its parent
    if ( $child->_parent ) {
      $child->_parent->remove_child($child, false);
    }

    $child->_parent = $this;
    $child->_next_sibling = null;

    // Handle the first child
    if ( !$this->_last_child ) {
      $this->_first_child = $child;
      $this->_last_child = $child;
      $child->_prev_sibling = null;
    }
    else {
      $this->_last_child->_next_sibling = $child;
      $child->_prev_sibling = $this->_last_child;
      $this->_last_child = $child;
    }
  }

  /**
   * Inserts a new child immediately before the specified frame
   *
   * @param $new_child   Frame The new Frame to insert
   * @param $ref         Frame The Frame after the new Frame
   * @param $update_node boolean Whether or not to update the DOM
   *
   * @throws DOMPDF_Exception
   */
  function insert_child_before(Frame $new_child, Frame $ref, $update_node = true) {
    if ( $ref === $this->_first_child ) {
      $this->prepend_child($new_child, $update_node);
      return;
    }

    if ( is_null($ref) ) {
      $this->append_child($new_child, $update_node);
      return;
    }

    if ( $ref->_parent !== $this ) {
      throw new DOMPDF_Exception("Reference child is not a child of this node.");
    }

    // Update the node
    if ( $update_node ) {
      $this->_node->insertBefore($new_child->_node, $ref->_node);
    }

    // Remove the child from its parent
    if ( $new_child->_parent ) {
      $new_child->_parent->remove_child($new_child, false);
    }

    $new_child->_parent = $this;
    $new_child->_next_sibling = $ref;
    $new_child->_prev_sibling = $ref->_prev_sibling;

    if ( $ref->_prev_sibling ) {
      $ref->_prev_sibling->_next_sibling = $new_child;
    }

    $ref->_prev_sibling = $new_child;
  }

  /**
   * Inserts a new child immediately after the specified frame
   *
   * @param $new_child   Frame The new Frame to insert
   * @param $ref         Frame The Frame before the new Frame
   * @param $update_node boolean Whether or not to update the DOM
   *
   * @throws DOMPDF_Exception
   */
  function insert_child_after(Frame $new_child, Frame $ref, $update_node = true) {
    if ( $ref === $this->_last_child ) {
      $this->append_child($new_child, $update_node);
      return;
    }

    if ( is_null($ref) ) {
      $this->prepend_child($new_child, $update_node);
      return;
    }

    if ( $ref->_parent !== $this ) {
      throw new DOMPDF_Exception("Reference child is not a child of this node.");
    }

    // Update the node
    if ( $update_node ) {
      if ( $ref->_next_sibling ) {
        $next_node = $ref->_next_sibling->_node;
        $this->_node->insertBefore($new_child->_node, $next_node);
      }
      else {
        $new_child->_node = $this->_node->appendChild($new_child->_node);
      }
    }

    // Remove the child from its parent
    if ( $new_child->_parent ) {
      $new_child->_parent->remove_child($new_child, false);
    }

    $new_child->_parent = $this;
    $new_child->_prev_sibling = $ref;
    $new_child->_next_sibling = $ref->_next_sibling;

    if ( $ref->_next_sibling ) {
      $ref->_next_sibling->_prev_sibling = $new_child;
    }

    $ref->_next_sibling = $new_child;
  }


  /**
   * Remove a child frame
   *
   * @param Frame   $child
   * @param boolean $update_node  Whether or not to remove the DOM node
   *
   * @throws DOMPDF_Exception
   * @return Frame The removed child frame
   */
  function remove_child(Frame $child, $update_node = true) {
    if ( $child->_parent !== $this ) {
      throw new DOMPDF_Exception("Child not found in this frame");
    }

    if ( $update_node ) {
      $this->_node->removeChild($child->_node);
    }

    if ( $child === $this->_first_child ) {
      $this->_first_child = $child->_next_sibling;
    }

    if ( $child === $this->_last_child ) {
      $this->_last_child = $child->_prev_sibling;
    }

    if ( $child->_prev_sibling ) {
      $child->_prev_sibling->_next_sibling = $child->_next_sibling;
    }

    if ( $child->_next_sibling ) {
      $child->_next_sibling->_prev_sibling = $child->_prev_sibling;
    }

    $child->_next_sibling = null;
    $child->_prev_sibling = null;
    $child->_parent = null;
    return $child;
  }

  //........................................................................

  // Debugging function:
  function __toString() {
    // Skip empty text frames
//     if ( $this->is_text_node() &&
//          preg_replace("/\s/", "", $this->_node->data) === "" )
//       return "";


    $str = "<b>" . $this->_node->nodeName . ":</b><br/>";
    //$str .= spl_object_hash($this->_node) . "<br/>";
    $str .= "Id: " .$this->get_id() . "<br/>";
    $str .= "Class: " .get_class($this) . "<br/>";

    if ( $this->is_text_node() ) {
      $tmp = htmlspecialchars($this->_node->nodeValue);
      $str .= "<pre>'" .  mb_substr($tmp,0,70) .
        (mb_strlen($tmp) > 70 ? "..." : "") . "'</pre>";
    }
    elseif ( $css_class = $this->_node->getAttribute("class") ) {
      $str .= "CSS class: '$css_class'<br/>";
    }

    if ( $this->_parent ) {
      $str .= "\nParent:" . $this->_parent->_node->nodeName .
        " (" . spl_object_hash($this->_parent->_node) . ") " .
        "<br/>";
    }

    if ( $this->_prev_sibling ) {
      $str .= "Prev: " . $this->_prev_sibling->_node->nodeName .
        " (" . spl_object_hash($this->_prev_sibling->_node) . ") " .
        "<br/>";
    }

    if ( $this->_next_sibling ) {
      $str .= "Next: " . $this->_next_sibling->_node->nodeName .
        " (" . spl_object_hash($this->_next_sibling->_node) . ") " .
        "<br/>";
    }

    $d = $this->get_decorator();
    while ($d && $d != $d->get_decorator()) {
      $str .= "Decorator: " . get_class($d) . "<br/>";
      $d = $d->get_decorator();
    }

    $str .= "Position: " . pre_r($this->_position, true);
    $str .= "\nContaining block: " . pre_r($this->_containing_block, true);
    $str .= "\nMargin width: " . pre_r($this->get_margin_width(), true);
    $str .= "\nMargin height: " . pre_r($this->get_margin_height(), true);

    $str .= "\nStyle: <pre>". $this->_style->__toString() . "</pre>";

    if ( $this->_decorator instanceof Block_Frame_Decorator ) {
      $str .= "Lines:<pre>";
      foreach ($this->_decorator->get_line_boxes() as $line) {
        foreach ($line->get_frames() as $frame) {
          if ($frame instanceof Text_Frame_Decorator) {
            $str .= "\ntext: ";
            $str .= "'". htmlspecialchars($frame->get_text()) ."'";
          }
          else {
            $str .= "\nBlock: " . $frame->get_node()->nodeName . " (" . spl_object_hash($frame->get_node()) . ")";
          }
        }

        $str .=
          "\ny => " . $line->y . "\n" .
          "w => " . $line->w . "\n" .
          "h => " . $line->h . "\n" .
          "left => " . $line->left . "\n" .
          "right => " . $line->right . "\n";
      }
      $str .= "</pre>";
    }
    
    $str .= "\n";
    if ( php_sapi_name() === "cli" ) {
      $str = strip_tags(str_replace(array("<br/>","<b>","</b>"),
                                    array("\n","",""),
                                    $str));
    }

    return $str;
  }
}

//------------------------------------------------------------------------

/**
 * Linked-list IteratorAggregate
 *
 * @access private
 * @package dompdf
 */
class FrameList implements IteratorAggregate {
  protected $_frame;

  function __construct($frame) { $this->_frame = $frame; }
  function getIterator() { return new FrameListIterator($this->_frame); }
}

/**
 * Linked-list Iterator
 *
 * Returns children in order and allows for list to change during iteration,
 * provided the changes occur to or after the current element
 *
 * @access private
 * @package dompdf
 */
class FrameListIterator implements Iterator {

  /**
   * @var Frame
   */
  protected $_parent;

  /**
   * @var Frame
   */
  protected $_cur;

  /**
   * @var int
   */
  protected $_num;

  function __construct(Frame $frame) {
    $this->_parent = $frame;
    $this->_cur = $frame->get_first_child();
    $this->_num = 0;
  }

  function rewind() {
    $this->_cur = $this->_parent->get_first_child();
    $this->_num = 0;
  }

  /**
   * @return bool
   */
  function valid() {
    return isset($this->_cur);// && ($this->_cur->get_prev_sibling() === $this->_prev);
  }

  function key() { return $this->_num; }

  /**
   * @return Frame
   */
  function current() { return $this->_cur; }

  /**
   * @return Frame
   */
  function next() {

    $ret = $this->_cur;
    if ( !$ret ) {
      return null;
    }

    $this->_cur = $this->_cur->get_next_sibling();
    $this->_num++;
    return $ret;
  }
}

//------------------------------------------------------------------------

/**
 * Pre-order IteratorAggregate
 *
 * @access private
 * @package dompdf
 */
class FrameTreeList implements IteratorAggregate {
  /**
   * @var Frame
   */
  protected $_root;

  function __construct(Frame $root) { $this->_root = $root; }

  /**
   * @return FrameTreeIterator
   */
  function getIterator() { return new FrameTreeIterator($this->_root); }
}

/**
 * Pre-order Iterator
 *
 * Returns frames in preorder traversal order (parent then children)
 *
 * @access private
 * @package dompdf
 */
class FrameTreeIterator implements Iterator {
  /**
   * @var Frame
   */
  protected $_root;
  protected $_stack = array();

  /**
   * @var int
   */
  protected $_num;

  function __construct(Frame $root) {
    $this->_stack[] = $this->_root = $root;
    $this->_num = 0;
  }

  function rewind() {
    $this->_stack = array($this->_root);
    $this->_num = 0;
  }

  /**
   * @return bool
   */
  function valid() {
    return count($this->_stack) > 0;
  }

  /**
   * @return int
   */
  function key() {
    return $this->_num;
  }

  /**
   * @return Frame
   */
  function current() {
    return end($this->_stack);
  }

  /**
   * @return Frame
   */
  function next() {
    $b = end($this->_stack);

    // Pop last element
    unset($this->_stack[ key($this->_stack) ]);
    $this->_num++;

    // Push all children onto the stack in reverse order
    if ( $c = $b->get_last_child() ) {
      $this->_stack[] = $c;
      while ( $c = $c->get_prev_sibling() ) {
        $this->_stack[] = $c;
      }
    }

    return $b;
  }
}

