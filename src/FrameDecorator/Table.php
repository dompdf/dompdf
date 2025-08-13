<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Cellmap;
use DOMNode;
use Dompdf\Css\Style;
use Dompdf\Dompdf;
use Dompdf\Frame;

/**
 * Decorates Frames for table layout
 *
 * @package dompdf
 */
class Table extends AbstractFrameDecorator
{
    public const VALID_CHILDREN = Style::TABLE_INTERNAL_TYPES;

    /**
     * List of all row-group display types.
     */
    public const ROW_GROUPS = [
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

        $style = $frame->get_style();
        if ($style->table_layout === "fixed" && $style->width !== "auto") {
            $this->_cellmap->set_layout_fixed(true);
        }

        $this->_headers = [];
        $this->_footers = [];
    }

    public function reset()
    {
        parent::reset();
        $this->_cellmap->reset();
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

        } elseif (in_array($child->get_style()->display, self::ROW_GROUPS, true)) {

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

    //........................................................................

    /**
     * Check for text nodes between valid table children that only contain white
     * space, except if white space is to be preserved.
     *
     * @param AbstractFrameDecorator $frame
     *
     * @return bool
     */
    private function isEmptyTextNode(AbstractFrameDecorator $frame): bool
    {
        // This is based on the white-space pattern in `FrameReflower\Text`,
        // i.e. only match on collapsible white space
        $wsPattern = '/^[^\S\xA0\x{202F}\x{2007}]*$/u';
        $validChildOrNull = function ($frame) {
            return $frame === null
                || in_array($frame->get_style()->display, self::VALID_CHILDREN, true);
        };

        return $frame instanceof Text
            && !$frame->is_pre()
            && preg_match($wsPattern, $frame->get_text())
            && $validChildOrNull($frame->get_prev_sibling())
            && $validChildOrNull($frame->get_next_sibling());
    }

    /**
     * Restructure tree so that the table has the correct structure. Misplaced
     * children are appropriately wrapped in anonymous row groups, rows, and
     * cells.
     *
     * https://www.w3.org/TR/CSS21/tables.html#anonymous-boxes
     */
    public function normalize(): void
    {
        $column_caption = ["table-column-group", "table-column", "table-caption"];
        $children = iterator_to_array($this->get_children());
        $tbody = null;

        foreach ($children as $child) {
            $display = $child->get_style()->display;

            if (in_array($display, self::ROW_GROUPS, true)) {
                // Reset anonymous tbody
                $tbody = null;

                // Add headers and footers
                if ($display === "table-header-group") {
                    $this->_headers[] = $child;
                } elseif ($display === "table-footer-group") {
                    $this->_footers[] = $child;
                }
                continue;
            }

            if (in_array($display, $column_caption, true)) {
                continue;
            }

            // Remove empty text nodes between valid children
            if ($this->isEmptyTextNode($child)) {
                $this->remove_child($child);
                continue;
            }

            // Catch consecutive misplaced frames within a single anonymous group
            if ($tbody === null) {
                $tbody = $this->create_anonymous_child("tbody", "table-row-group");
                $this->insert_child_before($tbody, $child);
            }

            $tbody->append_child($child);
        }

        // Handle empty table: Make sure there is at least one row group
        if (!$this->get_first_child()) {
            $tbody = $this->create_anonymous_child("tbody", "table-row-group");
            $this->append_child($tbody);
        }

        foreach ($this->get_children() as $child) {
            $display = $child->get_style()->display;

            if (in_array($display, self::ROW_GROUPS, true)) {
                $this->normalizeRowGroup($child);
            }
        }
    }

    private function normalizeRowGroup(AbstractFrameDecorator $frame): void
    {
        $children = iterator_to_array($frame->get_children());
        $tr = null;

        foreach ($children as $child) {
            $display = $child->get_style()->display;

            if ($display === "table-row") {
                // Reset anonymous tr
                $tr = null;
                continue;
            }

            // Remove empty text nodes between valid children
            if ($this->isEmptyTextNode($child)) {
                $frame->remove_child($child);
                continue;
            }

            // Catch consecutive misplaced frames within a single anonymous row
            if ($tr === null) {
                $tr = $frame->create_anonymous_child("tr", "table-row");
                $frame->insert_child_before($tr, $child);
            }

            $tr->append_child($child);
        }

        // Handle empty row group: Make sure there is at least one row
        if (!$frame->get_first_child()) {
            $tr = $frame->create_anonymous_child("tr", "table-row");
            $frame->append_child($tr);
        }

        foreach ($frame->get_children() as $child) {
            $this->normalizeRow($child);
        }
    }

    private function normalizeRow(AbstractFrameDecorator $frame): void
    {
        $children = iterator_to_array($frame->get_children());
        $td = null;

        foreach ($children as $child) {
            $display = $child->get_style()->display;

            if ($display === "table-cell") {
                // Reset anonymous td
                $td = null;
                continue;
            }

            // Remove empty text nodes between valid children
            if ($this->isEmptyTextNode($child)) {
                $frame->remove_child($child);
                continue;
            }

            // Catch consecutive misplaced frames within a single anonymous cell
            if ($td === null) {
                $td = $frame->create_anonymous_child("td", "table-cell");
                $frame->insert_child_before($td, $child);
            }

            $td->append_child($child);
        }

        // Handle empty row: Make sure there is at least one cell
        if (!$frame->get_first_child()) {
            $td = $frame->create_anonymous_child("td", "table-cell");
            $frame->append_child($td);
        }
    }
}
