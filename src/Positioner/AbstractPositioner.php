<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Positioner;

use Dompdf\FrameDecorator\AbstractFrameDecorator;

/**
 * Base AbstractPositioner class
 *
 * Defines positioner interface
 *
 * @package dompdf
 */
abstract class AbstractPositioner
{

    /**
     * @param AbstractFrameDecorator $frame
     */
    abstract function position(AbstractFrameDecorator $frame): void;

    /**
     * @param AbstractFrameDecorator $frame
     * @param float                  $offset_x
     * @param float                  $offset_y
     * @param bool                   $ignore_self
     */
    function move(
        AbstractFrameDecorator $frame,
        float $offset_x,
        float $offset_y,
        bool $ignore_self = false
    ): void {
        [$x, $y] = $frame->get_position();

        if (!$ignore_self) {
            $frame->set_position($x + $offset_x, $y + $offset_y);
        }

        foreach ($frame->get_children() as $child) {
            $child->move($offset_x, $offset_y);
        }
    }
}
