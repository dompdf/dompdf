<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @version $Id$
 */

/**
 * Executes inline PHP code during the rendering process
 *
 * @access private
 * @package dompdf
 */
class PHP_Evaluator {
  
  /**
   * @var Canvas
   */
  protected $_canvas;

  function __construct(Canvas $canvas) {
    $this->_canvas = $canvas;
  }

  function evaluate($code, $vars = array()) {
    if ( !DOMPDF_ENABLE_PHP )
      return;
    
    // Set up some variables for the inline code
    $pdf = $this->_canvas;
    $PAGE_NUM = $pdf->get_page_number();
    $PAGE_COUNT = $pdf->get_page_count();
    
    // Override those variables if passed in
    foreach ($vars as $k => $v) {
      $$k = $v;
    }

    //$code = html_entity_decode($code); // @todo uncomment this when tested
    eval(utf8_decode($code)); 
  }

  function render($frame) {
    $this->evaluate($frame->get_node()->nodeValue);
  }
}
