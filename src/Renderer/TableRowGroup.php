<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;

/**
 * Renders block frames
 *
 * @package dompdf
 */
class TableRowGroup extends Block
{

    //........................................................................

    function render(Frame $frame)
    {
        $style = $frame->get_style();

        $this->_set_opacity($frame->get_opacity($style->opacity));

        $this->_render_border($frame);
        $this->_render_outline($frame);

        if ($this->_dompdf->get_option("debugLayout") && $this->_dompdf->get_option("debugLayoutBlocks")) {
            $this->_debug_layout($frame->get_border_box(), "red");
            if ($this->_dompdf->get_option("debugLayoutPaddingBox")) {
                $this->_debug_layout($frame->get_padding_box(), "red", array(0.5, 0.5));
            }
        }

        if ($this->_dompdf->get_option("debugLayout") && $this->_dompdf->get_option("debugLayoutLines") && $frame->get_decorator()) {
            foreach ($frame->get_decorator()->get_line_boxes() as $line) {
                $frame->_debug_layout(array($line->x, $line->y, $line->w, $line->h), "orange");
            }
        }
    }
}
