<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\AbstractFrameDecorator;

/**
 * Reflows list bullets
 *
 * @package dompdf
 */
class ListBullet extends AbstractFrameReflower
{

    /**
     * ListBullet constructor.
     * @param AbstractFrameDecorator $frame
     */
    function __construct(AbstractFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    /**
     * @param BlockFrameDecorator|null $block
     */
    function reflow(BlockFrameDecorator $block = null)
    {
        $frame = $this->_frame;
        $style = $frame->get_style();

        $style->width = $frame->get_width();
        $frame->position();

        if ($style->list_style_position === "inside") {
            $p = $frame->find_block_parent();
            $p->add_frame_to_line($frame);
        }
    }
}
