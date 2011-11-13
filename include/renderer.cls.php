<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version $Id$
 */

/**
 * Concrete renderer
 *
 * Instantiates several specific renderers in order to render any given
 * frame.
 *
 * @access private
 * @package dompdf
 */
class Renderer extends Abstract_Renderer {

  /**
   * Array of renderers for specific frame types
   *
   * @var array
   */
  protected $_renderers;
    
  /**
   * Cache of the callbacks array
   * 
   * @var array
   */
  private $_callbacks;
  
  /**
   * true when a stacking context is currently built
   * @var bool
   */
  public static $stacking_first_pass = true;
  
  /**
   * Class destructor
   */
  function __destruct() {
    clear_object($this);
  }
  
  /**
   * Advance the canvas to the next page
   */  
  function new_page() {
    $this->_canvas->new_page();
  }

  /**
   * Render frames recursively
   *
   * @param Frame $frame the frame to render
   */
  function render(Frame $frame, $stacking = false) {
    global $_dompdf_debug;

    if ( $_dompdf_debug ) {
      echo $frame;
      flush();
    }
    
    $style = $frame->get_style();
    
    if ( in_array($style->visibility, array("hidden", "collapse")) ) {
      return;
    }

    $render_self = self::$stacking_first_pass && !$stacking || !self::$stacking_first_pass;
    
    if ( $render_self ) {
      $display = $style->display;
      
      // Starts the CSS transformation
      if ( $style->transform && is_array($style->transform) ) {
        $this->_canvas->save();
        list($x, $y, $w, $h) = $frame->get_padding_box();
        $origin = $style->transform_origin;
        
        foreach($style->transform as $transform) {
          list($function, $values) = $transform;
          if ( $function === "matrix" ) {
            $function = "transform";
          }
          
          $values = array_map("floatval", $values);
          $values[] = $x + $style->length_in_pt($origin[0], $style->width);
          $values[] = $y + $style->length_in_pt($origin[1], $style->height);
          
          call_user_func_array(array($this->_canvas, $function), $values);
        }
      }
    
      switch ($display) {
        
      case "block":
      case "list-item":
      case "inline-block":
      case "table":
      case "inline-table":
        $this->_render_frame("block", $frame);
        break;
  
      case "inline":
        if ( $frame->is_text_node() )
          $this->_render_frame("text", $frame);
        else
          $this->_render_frame("inline", $frame);
        break;
  
      case "table-cell":
        $this->_render_frame("table-cell", $frame);
        break;
  
      case "table-row-group":
      case "table-header-group":
      case "table-footer-group":
        $this->_render_frame("table-row-group", $frame);
        break;
  
      case "-dompdf-list-bullet":
        $this->_render_frame("list-bullet", $frame);
        break;
  
      case "-dompdf-image":
        $this->_render_frame("image", $frame);
        break;
        
      case "none":
        $node = $frame->get_node();
            
        if ( $node->nodeName === "script" ) {
          if ( $node->getAttribute("type") === "text/php" ||
               $node->getAttribute("language") === "php" ) {
            // Evaluate embedded php scripts
            $this->_render_frame("php", $frame);
          }
          
          elseif ( $node->getAttribute("type") === "text/javascript" ||
               $node->getAttribute("language") === "javascript" ) {
            // Insert JavaScript
            $this->_render_frame("javascript", $frame);
          }
        }
  
        // Don't render children, so skip to next iter
        return;
        
      default:
        break;
  
      }
  
      // Check for begin frame callback
      $this->_check_callbacks("begin_frame", $frame);
      
      // Starts the overflow: hidden box
      if ( $style->overflow === "hidden" ) {
        list($x, $y, $w, $h) = $frame->get_padding_box();
        $this->_canvas->clipping_rectangle($x, $y, $w, $h);
      }
    }
  
    $page = $frame->get_root()->get_reflower();
    
    foreach ($frame->get_children() as $child) {
      $child_style = $child->get_style();
      $_stacking = $stacking;
      
      // Stacking context
      if ( self::$stacking_first_pass && (
           $child_style->z_index !== "auto" || 
           $child_style->float !== "none" || 
           $child->is_positionned()) ) {
        $z_index = ($child_style->z_index === "auto") ? 0 : intval($child_style->z_index);
        $page->add_frame_to_stacking_context($child, $z_index);
        $_stacking = true;
      }
      
      $this->render($child, $_stacking);
    }
     
    if ( $render_self ) {
      // Ends the overflow: hidden box
      if ( $style->overflow === "hidden" ) {
        $this->_canvas->clipping_end();
      }
  
      if ( $style->transform && is_array($style->transform) ) {
        $this->_canvas->restore();
      }
  
      // Check for end frame callback
      $this->_check_callbacks("end_frame", $frame);
    }
  }
  
  /**
   * Check for callbacks that need to be performed when a given event
   * gets triggered on a frame
   *
   * @param string $event the type of event
   * @param Frame $frame the frame that event is triggered on
   */
  protected function _check_callbacks($event, $frame) {
    if (!isset($this->_callbacks)) {
      $this->_callbacks = $this->_dompdf->get_callbacks();
    }
    
    if (is_array($this->_callbacks) && isset($this->_callbacks[$event])) {
      $info = array(0 => $this->_canvas, "canvas" => $this->_canvas,
                    1 => $frame, "frame" => $frame);
      $fs = $this->_callbacks[$event];
      foreach ($fs as $f) {
        if (is_callable($f)) {
          if (is_array($f)) {
            $f[0]->$f[1]($info);
          } else {
            $f($info);
          }
        }
      }
    }
  }

  /**
   * Render a single frame
   *
   * Creates Renderer objects on demand
   *
   * @param string $type type of renderer to use
   * @param Frame $frame the frame to render
   */
  protected function _render_frame($type, $frame) {

    if ( !isset($this->_renderers[$type]) ) {
      
      switch ($type) {
      case "block":
        $this->_renderers[$type] = new Block_Renderer($this->_dompdf);
        break;

      case "inline":
        $this->_renderers[$type] = new Inline_Renderer($this->_dompdf);
        break;

      case "text":
        $this->_renderers[$type] = new Text_Renderer($this->_dompdf);
        break;

      case "image":
        $this->_renderers[$type] = new Image_Renderer($this->_dompdf);
        break;
      
      case "table-cell":
        $this->_renderers[$type] = new Table_Cell_Renderer($this->_dompdf);
        break;
      
      case "table-row-group":
        $this->_renderers[$type] = new Table_Row_Group_Renderer($this->_dompdf);
        break;

      case "list-bullet":
        $this->_renderers[$type] = new List_Bullet_Renderer($this->_dompdf);
        break;

      case "php":
        $this->_renderers[$type] = new PHP_Evaluator($this->_canvas);
        break;

      case "javascript":
        $this->_renderers[$type] = new Javascript_Embedder($this->_dompdf);
        break;
        
      }
    }
    
    $this->_renderers[$type]->render($frame);

  }
}
