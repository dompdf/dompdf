<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

use Dompdf\Frame;

/**
 * Executes inline PHP code during the rendering process
 *
 * @package dompdf
 */
class PhpEvaluator
{

    /**
     * @var Canvas
     */
    protected $_canvas;

    /**
     * PhpEvaluator constructor.
     * @param Canvas $canvas
     */
    public function __construct(Canvas $canvas)
    {
        $this->_canvas = $canvas;
    }

    /**
     * @param $code
     * @param array $vars
     */
    public function evaluate($code, $vars = array())
    {
        if (!$this->_canvas->get_dompdf()->getOptions()->getIsPhpEnabled()) {
            return;
        }

        // Set up some variables for the inline code
        $pdf = $this->_canvas;
        $fontMetrics = $pdf->get_dompdf()->getFontMetrics();
        $PAGE_NUM = $pdf->get_page_number();
        $PAGE_COUNT = $pdf->get_page_count();

        // Override those variables if passed in
        foreach ($vars as $k => $v) {
            $$k = $v;
        }

        eval($code);
    }

    /**
     * @param \Dompdf\Frame $frame
     */
    public function render(Frame $frame)
    {
        $this->evaluate($frame->get_node()->nodeValue);
    }
}
