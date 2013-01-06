<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Embeds Javascript into the PDF document
 *
 * @access private
 * @package dompdf
 */
class Javascript_Embedder {
  
  /**
   * @var DOMPDF
   */
  protected $_dompdf;

  function __construct(DOMPDF $dompdf) {
    $this->_dompdf = $dompdf;
  }

  function insert($script) {
    $this->_dompdf->get_canvas()->javascript($script);
  }

  function render(Frame $frame) {
    if ( !$this->_dompdf->get_option("enable_javascript") ) {
      return;
    }
      
    $this->insert($frame->get_node()->nodeValue);
  }
}
