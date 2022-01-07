<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Cellmap;
use DOMNode;
use Dompdf\Css\Style;
use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Frame\Factory;

/**
 * Decorates Frames for table layout
 *
 * @package dompdf
 */
class Table extends AbstractFrameDecorator
{
    public static $VALID_CHILDREN = Style::TABLE_INTERNAL_TYPES;

    public static $ROW_GROUPS = [
        "table-row-group",
        "table-header-group",
        "table-footer-group"
    ];

    /**
     * The Cellmap object for this table.  The cellmap maps table cells
     * to rows and columns, and aids in calculating column widths.
     *
     * @var Cellmap
     */
    protected $_cellmap;

    /**
     * The minimum width of the table, in pt
     *
     * @var float
     */
    protected $_min_width;

    /**
     * The maximum width of the table, in pt
     *
     * @var float
     */
    protected $_max_width;

    /**
     * Table header rows.  Each table header is duplicated when a table
     * spans pages.
     *
     * @var TableRowGroup[]
     */
    protected $_headers;

    /**
     * Table footer rows.  Each table footer is duplicated when a table
     * spans pages.
     *
     * @var TableRowGroup[]
     */
    protected $_footers;

    /**
     * Class constructor
     *
     * @param Frame $frame the frame to decorate
     * @param Dompdf $dompdf
     */
    public function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
        $this->_cellmap = new Cellmap($this);

        if ($frame->get_style()->table_layout === "fixed") {
            $this->_cellmap->set_layout_fixed(true);
        }

        $this->_min_width = null;
        $this->_max_width = null;
        $this->_headers = [];
        $this->_footers = [];
    }

    public function reset()
    {
        parent::reset();
        $this->_cellmap->reset();
        $this->_min_width = null;
        $this->_max_width = null;
        $this->_headers = [];
        $this->_footers = [];
        $this->_reflower->reset();
    }

    //........................................................................

    /**
     * Split the table at $row.  $row and all subsequent rows will be
     * added to the clone.  This method is overridden in order to remove
     * frames from the cellmap properly.
     */
    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            parent::split($child, $page_break, $forced);
            return;
        }

        // If $child is a header or if it is the first non-header row, do
        // not duplicate headers, simply move the table to the next page.
        if (count($this->_headers)
            && !in_array($child, $this->_headers, true)
            && !in_array($child->get_prev_sibling(), $this->_headers, true)
        ) {
            $first_header = null;

            // Insert copies of the table headers before $child
            foreach ($this->_headers as $header) {

                $new_header = $header->deep_copy();

                if (is_null($first_header)) {
                    $first_header = $new_header;
                }

                $this->insert_child_before($new_header, $child);
            }

            parent::split($first_header, $page_break, $forced);

        } elseif (in_array($child->get_style()->display, self::$ROW_GROUPS, true)) {

            // Individual rows should have already been handled
            parent::split($child, $page_break, $forced);

        } else {

            $iter = $child;

            while ($iter) {
                $this->_cellmap->remove_row($iter);
                $iter = $iter->get_next_sibling();
            }

            parent::split($child, $page_break, $forced);
        }
    }

    public function copy(DOMNode $node)
    {
        $deco = parent::copy($node);

        // In order to keep columns' widths through pages
        $deco->_cellmap->set_columns($this->_cellmap->get_columns());
        $deco->_cellmap->lock_columns();

        return $deco;
    }

    /**
     * Static function to locate the parent table of a frame
     *
     * @param Frame $frame
     *
     * @return Table the table that is an ancestor of $frame
     */
    public static function find_parent_table(Frame $frame)
    {
        while ($frame = $frame->get_parent()) {
            if ($frame->is_table()) {
                break;
            }
        }

        return $frame;
    }

    /**
     * Return this table's Cellmap
     *
     * @return Cellmap
     */
    public function get_cellmap()
    {
        return $this->_cellmap;
    }

    /**
     * Return the minimum width of this table
     *
     * @return float
     */
    public function get_min_width()
    {
        return $this->_min_width;
    }

    /**
     * Return the maximum width of this table
     *
     * @return float
     */
    public function get_max_width()
    {
        return $this->_max_width;
    }

    /**
     * Set the minimum width of the table
     *
     * @param float $width the new minimum width
     */
    public function set_min_width($width)
    {
        $this->_min_width = $width;
    }

    /**
     * Set the maximum width of the table
     *
     * @param float $width the new maximum width
     */
    public function set_max_width($width)
    {
        $this->_max_width = $width;
    }

    /**
     * Restructure tree so that the table has the correct structure.
     * Invalid children (i.e. all non-table-rows) are moved below the
     * table.
     *
     * @fixme #1363 Method has some bugs. $table_row has not been initialized and lookup most likely could return an
     * array of Style instead a Style Object
     */
    public function normalise()
    {
        // Store frames generated by invalid tags and move them outside the table
        $erroneous_frames = [];
        $anon_row = false;
        $iter = $this->get_first_child();
        while ($iter) {
            $child = $iter;
            $iter = $iter->get_next_sibling();

            $display = $child->get_style()->display;

            if ($anon_row) {

                if ($display === "table-row") {
                    // Add the previous anonymous row
                    $this->insert_child_before($table_row, $child);

                    $table_row->normalise();
                    $child->normalise();
                    $this->_cellmap->add_row();
                    $anon_row = false;
                    continue;
                }

                // add the child to the anonymous row
                $table_row->append_child($child);
                continue;

            } else {

                if ($display === "table-row") {
                    $child->normalise();
                    continue;
                }

                if ($display === "table-cell") {
                    $css = $this->get_style()->get_stylesheet();

                    // Create an anonymous table row group
                    $tbody = $this->get_node()->ownerDocument->createElement("tbody");

                    $frame = new Frame($tbody);

                    $style = $css->create_style();
                    $style->inherit($this->get_style());

                    // Lookup styles for tbody tags.  If the user wants styles to work
                    // better, they should make the tbody explicit... I'm not going to
                    // try to guess what they intended.
                    foreach ($css->lookup("tbody") as $tbody_style) {
                        $style->merge($tbody_style);
                    }
                    $style->display = "table-row-group";

                    // Okay, I have absolutely no idea why I need this clone here, but
                    // if it's omitted, php (as of 2004-07-28) segfaults.
                    $frame->set_style($style);
                    $table_row_group = Factory::decorate_frame($frame, $this->_dompdf, $this->_root);

                    // Create an anonymous table row
                    $tr = $this->get_node()->ownerDocument->createElement("tr");

                    $frame = new Frame($tr);

                    $style = $css->create_style();
                    $style->inherit($this->get_style());

                    // Lookup styles for tr tags.  If the user wants styles to work
                    // better, they should make the tr explicit... I'm not going to
                    // try to guess what they intended.
                    foreach ($css->lookup("tr") as $tr_style) {
                        $style->merge($tr_style);
                    }
                    $style->display = "table-row";

                    // Okay, I have absolutely no idea why I need this clone here, but
                    // if it's omitted, php (as of 2004-07-28) segfaults.
                    $frame->set_style(clone $style);
                    $table_row = Factory::decorate_frame($frame, $this->_dompdf, $this->_root);

                    // Add the cell to the row
                    $table_row->append_child($child, true);

                    // Add the tr to the tbody
                    $table_row_group->append_child($table_row, true);

                    $anon_row = true;
                    continue;
                }

                if (!in_array($display, self::$VALID_CHILDREN)) {
                    $erroneous_frames[] = $child;
                    continue;
                }

                // Normalise other table parts (i.e. row groups)
                foreach ($child->get_children() as $grandchild) {
                    if ($grandchild->get_style()->display === "table-row") {
                        $grandchild->normalise();
                    }
                }

                // Add headers and footers
                if ($display === "table-header-group") {
                    $this->_headers[] = $child;
                } elseif ($display === "table-footer-group") {
                    $this->_footers[] = $child;
                }
            }
        }

        if ($anon_row && $table_row_group instanceof AbstractFrameDecorator) {
            // Add the row to the table
            $this->_frame->append_child($table_row_group->_frame);
            $table_row->normalise();
        }

        foreach ($erroneous_frames as $frame) {
            $this->move_after($frame);
        }
    }

    //........................................................................

    /**
     * Moves the specified frame and it's corresponding node outside of
     * the table.
     *
     * @param Frame $frame the frame to move
     */
    public function move_after(Frame $frame)
    {
        $this->get_parent()->insert_child_after($frame, $this);
    }
}
