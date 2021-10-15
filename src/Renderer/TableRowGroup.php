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

    /**
     * @param Frame $frame
     */
    function render(Frame $frame)
    {
        $style = $frame->get_style();

        $this->_set_opacity($frame->get_opacity($style->opacity));

        $border_box = $frame->get_border_box();

        $this->_render_border($frame, $border_box);
        $this->_render_outline($frame, $border_box);

        $id = $frame->get_node()->getAttribute("id");
        if (strlen($id) > 0) {
            $this->_canvas->add_named_dest($id);
        }

        $this->debugBlockLayout($frame, "red");
    }
}
