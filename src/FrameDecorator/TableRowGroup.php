<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;

/**
 * Table row group decorator
 *
 * Overrides split() method for tbody, thead & tfoot elements
 *
 * @package dompdf
 */
class TableRowGroup extends AbstractFrameDecorator
{

    /**
     * Class constructor
     *
     * @param Frame $frame   Frame to decorate
     * @param Dompdf $dompdf Current dompdf instance
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
    }

    /**
     * Split the row group at the given child and remove all subsequent child
     * rows and all subsequent row groups from the cellmap.
     */
    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            parent::split($child, $page_break, $forced);
            return;
        }

        // Remove child & all subsequent rows from the cellmap
        /** @var Table $parent */
        $parent = $this->get_parent();
        $cellmap = $parent->get_cellmap();
        $iter = $child;

        while ($iter) {
            $cellmap->remove_row($iter);
            $iter = $iter->get_next_sibling();
        }

        // Remove all subsequent row groups from the cellmap
        $iter = $this->get_next_sibling();

        while ($iter) {
            $cellmap->remove_row_group($iter);
            $iter = $iter->get_next_sibling();
        }

        // If we are splitting at the first child remove the
        // table-row-group from the cellmap as well
        if ($child === $this->get_first_child()) {
            $cellmap->remove_row_group($this);
            parent::split(null, $page_break, $forced);
            return;
        }

        $cellmap->update_row_group($this, $child->get_prev_sibling());
        parent::split($child, $page_break, $forced);
    }
}
