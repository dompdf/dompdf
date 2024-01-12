<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace Dompdf\FrameReflower;

use Dompdf\Frame;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;

/**
 * Dummy reflower
 *
 * @package dompdf
 */
class NullFrameReflower extends AbstractFrameReflower
{
    /**
     * NullFrameReflower constructor.
     * @param Frame $frame
     */
    public function __construct(Frame $frame)
    {
        parent::__construct($frame);
    }

    public function reflow(BlockFrameDecorator $block = null)
    {
        return;
    }

}
