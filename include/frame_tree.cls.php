<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Represents an entire document as a tree of frames
 *
 * The Frame_Tree consists of {@link Frame} objects each tied to specific
 * DOMNode objects in a specific DomDocument.  The Frame_Tree has the same
 * structure as the DomDocument, but adds additional capabalities for
 * styling and layout.
 *
 * @package dompdf
 * @access protected
 */
class Frame_Tree {
    
  /**
   * Tags to ignore while parsing the tree
   *
   * @var array
   */
  static protected $_HIDDEN_TAGS = array("area", "base", "basefont", "head", "style",
                                         "meta", "title", "colgroup",
                                         "noembed", "noscript", "param", "#comment");  
  /**
   * The main DomDocument
   *
   * @see http://ca2.php.net/manual/en/ref.dom.php
   * @var DomDocument
   */
  protected $_dom;

  /**
   * The root node of the FrameTree.
   *
   * @var Frame
   */
  protected $_root;

  /**
   * Subtrees of absolutely positioned elements
   *
   * @var array of Frames
   */
  protected $_absolute_frames;

  /**
   * A mapping of {@link Frame} objects to DOMNode objects
   *
   * @var array
   */
  protected $_registry;
  

  /**
   * Class constructor
   *
   * @param DomDocument $dom the main DomDocument object representing the current html document
   */
  function __construct(DomDocument $dom) {
    $this->_dom = $dom;
    $this->_root = null;
    $this->_registry = array();
  }
  
  function __destruct() {
    clear_object($this);
  }

  /**
   * Returns the DomDocument object representing the curent html document
   *
   * @return DOMDocument
   */
  function get_dom() {
    return $this->_dom;
  }

  /**
   * Returns the root frame of the tree
   * 
   * @return Page_Frame_Decorator
   */
  function get_root() {
    return $this->_root;
  }

  /**
   * Returns a specific frame given its id
   *
   * @param string $id
   * @return Frame
   */
  function get_frame($id) {
    return isset($this->_registry[$id]) ? $this->_registry[$id] : null;
  }

  /**
   * Returns a post-order iterator for all frames in the tree
   *
   * @return FrameTreeList|Frame[]
   */
  function get_frames() {
    return new FrameTreeList($this->_root);
  }
      
  /**
   * Builds the tree
   */
  function build_tree() {
    $html = $this->_dom->getElementsByTagName("html")->item(0);
    if ( is_null($html) ) {
      $html = $this->_dom->firstChild;
    }

    if ( is_null($html) ) {
      throw new DOMPDF_Exception("Requested HTML document contains no data.");
    }

    $this->fix_tables();
    
    $this->_root = $this->_build_tree_r($html);
  }
  
  /**
   * Adds missing TBODYs around TR
   */
  protected function fix_tables(){
    $xp = new DOMXPath($this->_dom);
    
    // Move table caption before the table
    // FIXME find a better way to deal with it...
    $captions = $xp->query("//table/caption");
    foreach($captions as $caption) {
      $table = $caption->parentNode;
      $table->parentNode->insertBefore($caption, $table);
    }
    
    $rows = $xp->query("//table/tr");
    foreach($rows as $row) {
      $tbody = $this->_dom->createElement("tbody");
      $tbody = $row->parentNode->insertBefore($tbody, $row);
      $tbody->appendChild($row);
    }
  }

  /**
   * Recursively adds {@link Frame} objects to the tree
   *
   * Recursively build a tree of Frame objects based on a dom tree.
   * No layout information is calculated at this time, although the
   * tree may be adjusted (i.e. nodes and frames for generated content
   * and images may be created).
   *
   * @param DOMNode $node the current DOMNode being considered
   * @return Frame
   */
  protected function _build_tree_r(DOMNode $node) {
    
    $frame = new Frame($node);
    $id = $frame->get_id();
    $this->_registry[ $id ] = $frame;
    
    if ( !$node->hasChildNodes() ) {
      return $frame;
    }

    // Fixes 'cannot access undefined property for object with
    // overloaded access', fix by Stefan radulian
    // <stefan.radulian@symbion.at>    
    //foreach ($node->childNodes as $child) {

    // Store the children in an array so that the tree can be modified
    $children = array();
    for ($i = 0; $i < $node->childNodes->length; $i++) {
      $children[] = $node->childNodes->item($i);
    }

    foreach ($children as $child) {
      $node_name = mb_strtolower($child->nodeName);
      
      // Skip non-displaying nodes
      if ( in_array($node_name, self::$_HIDDEN_TAGS) )  {
        if ( $node_name !== "head" && $node_name !== "style" ) {
          $child->parentNode->removeChild($child);
        }
        
        continue;
      }

      // Skip empty text nodes
      if ( $node_name === "#text" && $child->nodeValue == "" ) {
        $child->parentNode->removeChild($child);
        continue;
      }

      // Skip empty image nodes
      if ( $node_name === "img" && $child->getAttribute("src") == "" ) {
        $child->parentNode->removeChild($child);
        continue;
      }
      
      $frame->append_child($this->_build_tree_r($child), false);
    }
    
    return $frame;
  }
  
  public function insert_node(DOMNode $node, DOMNode $new_node, $pos) {
    if ( $pos === "after" || !$node->firstChild ) {
      $node->appendChild($new_node);
    }
    else {
      $node->insertBefore($new_node, $node->firstChild);
    }
    
    $this->_build_tree_r($new_node);
    
    $frame_id = $new_node->getAttribute("frame_id");
    $frame = $this->get_frame($frame_id);
    
    $parent_id = $node->getAttribute("frame_id");
    $parent = $this->get_frame($parent_id);
    
    if ( $pos === "before" ) {
      $parent->prepend_child($frame, false);
    }
    else {
      $parent->append_child($frame, false);
    }
      
    return $frame_id;
  }
}
