<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace Dompdf\Positioner;

use Dompdf\FrameDecorator\AbstractFrameDecorator;

/**
 * Positions list bullets
 *
 * @package dompdf
 */
class ListBullet extends AbstractPositioner
{

    /**
     * @param AbstractFrameDecorator $frame
     */
    function position(AbstractFrameDecorator $frame)
    {

        // Bullets & friends are positioned an absolute distance to the left of
        // the content edge of their parent element
        $cb = $frame->get_containing_block();

        // Note: this differs from most frames in that we must position
        // ourselves after determining our width
        $x = $cb["x"] - $frame->get_width();

        $p = $frame->find_block_parent();

        $y = $p->get_current_line_box()->y;

        // This is a bit of a hack...
        $n = $frame->get_next_sibling();
        if ($n) {
            $style = $n->get_style();
            $line_height = $style->line_height;
            // TODO: should offset take into account the line height of the next sibling (per previous logic)?
            // $offset = (float)$style->length_in_pt($line_height, $n->get_containing_block("h")) - $frame->get_height();
            $offset = $line_height - $frame->get_height();
            $y += $offset / 2;
        }

        // Now the position is the left top of the block which should be marked with the bullet.
        // We tried to find out the y of the start of the first text character within the block.
        // But the top margin/padding does not fit, neither from this nor from the next sibling
        // The "bit of a hack" above does not work also.

        // Instead let's position the bullet vertically centered to the block which should be marked.
        // But for get_next_sibling() the get_containing_block is all zero, and for find_block_parent()
        // the get_containing_block is paper width and the entire list as height.

        // if ($p) {
        //   //$cb = $n->get_containing_block();
        //   $cb = $p->get_containing_block();
        //   $y += $cb["h"]/2;
        // print 'cb:'.$cb["x"].':'.$cb["y"].':'.$cb["w"].':'.$cb["h"].':';
        // }

        // Todo:
        // For now give up on the above. Use Guesswork with font y-pos in the middle of the line spacing

        /*$style = $p->get_style();
        $font_size = $style->font_size;
        $line_height = (float)$style->length_in_pt($style->line_height, $font_size);
        $y += ($line_height - $font_size) / 2;    */

        //Position is x-end y-top of character position of the bullet.
        $frame->set_position($x, $y);
    }
}
