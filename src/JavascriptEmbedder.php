<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

use Dompdf\Frame;

/**
 * Embeds Javascript into the PDF document
 *
 * @package dompdf
 */
class JavascriptEmbedder
{

    /**
     * @var Dompdf
     */
    protected $_dompdf;

    function __construct(Dompdf $dompdf)
    {
        $this->_dompdf = $dompdf;
    }

    function insert($script)
    {
        $this->_dompdf->get_canvas()->javascript($script);
    }

    function render(Frame $frame)
    {
        if (!$this->_dompdf->getOptions()->getIsJavascriptEnabled()) {
            return;
        }

        $this->insert($frame->get_node()->nodeValue);
    }
}
