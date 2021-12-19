<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Helpers;
use Dompdf\Frame;
use Dompdf\Renderer;

/**
 * Decorates frames for page layout
 *
 * @access  private
 * @package dompdf
 */
class Page extends AbstractFrameDecorator
{
    /**
     * The y value of the bottom edge of the page area.
     *
     * https://www.w3.org/TR/CSS21/page.html#page-margins
     *
     * @var float
     */
    protected $bottom_page_edge;

    /**
     * Flag indicating page is full.
     *
     * @var bool
     */
    protected $_page_full;

    /**
     * Number of tables currently being reflowed
     *
     * @var int
     */
    protected $_in_table;

    /**
     * The pdf renderer
     *
     * @var Renderer
     */
    protected $_renderer;

    /**
     * This page's floating frames
     *
     * @var array
     */
    protected $_floating_frames = [];

    //........................................................................

    /**
     * Class constructor
     *
     * @param Frame $frame the frame to decorate
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
        $this->_page_full = false;
        $this->_in_table = 0;
        $this->bottom_page_edge = null;
    }

    /**
     * Set the renderer used for this pdf
     *
     * @param Renderer $renderer the renderer to use
     */
    function set_renderer($renderer)
    {
        $this->_renderer = $renderer;
    }

    /**
     * Return the renderer used for this pdf
     *
     * @return Renderer
     */
    function get_renderer()
    {
        return $this->_renderer;
    }

    /**
     * Set the frame's containing block.  Overridden to set $this->bottom_page_edge.
     *
     * @param float $x
     * @param float $y
     * @param float $w
     * @param float $h
     */
    function set_containing_block($x = null, $y = null, $w = null, $h = null)
    {
        parent::set_containing_block($x, $y, $w, $h);

        if (isset($h)) {
            $style = $this->get_style();
            $margin_bottom = (float) $style->length_in_pt($style->margin_bottom, $h);
            $this->bottom_page_edge = $h - $margin_bottom;
        }
    }

    /**
     * Returns true if the page is full and is no longer accepting frames.
     *
     * @return bool
     */
    function is_full()
    {
        return $this->_page_full;
    }

    /**
     * Start a new page by resetting the full flag.
     */
    function next_page()
    {
        $this->_floating_frames = [];
        $this->_renderer->new_page();
        $this->_page_full = false;
    }

    /**
     * Indicate to the page that a table is currently being reflowed.
     */
    function table_reflow_start()
    {
        $this->_in_table++;
    }

    /**
     * Indicate to the page that table reflow is finished.
     */
    function table_reflow_end()
    {
        $this->_in_table--;
    }

    /**
     * Return whether we are currently in a nested table or not
     *
     * @return bool
     */
    function in_nested_table()
    {
        return $this->_in_table > 1;
    }

    /**
     * Check if a forced page break is required before $frame.  This uses the
     * frame's page_break_before property as well as the preceding frame's
     * page_break_after property.
     *
     * @link http://www.w3.org/TR/CSS21/page.html#forced
     *
     * @param AbstractFrameDecorator $frame the frame to check
     *
     * @return bool true if a page break occurred
     */
    function check_forced_page_break(Frame $frame)
    {
        // Skip check if page is already split and for the body
        if ($this->_page_full || $frame->get_node()->nodeName === "body") {
            return false;
        }

        $page_breaks = ["always", "left", "right"];
        $style = $frame->get_style();

        if (($frame->is_block_level() || $style->display === "table-row")
            && in_array($style->page_break_before, $page_breaks, true)
        ) {
            // Prevent cascading splits
            $frame->split(null, true, true);
            // We have to grab the style again here because split() resets
            // $frame->style to the frame's original style.
            $frame->get_style()->page_break_before = "auto";
            $this->_page_full = true;
            $frame->_already_pushed = true;

            return true;
        }

        // Find the preceding block-level sibling (or table row). Inline
        // elements are treated as if wrapped in an anonymous block container
        // here. See https://www.w3.org/TR/CSS21/visuren.html#anonymous-block-level
        $prev = $frame->get_prev_sibling();
        while ($prev && (($prev->is_text_node() && $prev->get_node()->nodeValue === "")
            || $prev->get_node()->nodeName === "bullet")
        ) {
            $prev = $prev->get_prev_sibling();
        }

        if ($prev && ($prev->is_block_level() || $prev->get_style()->display === "table-row")) {
            if (in_array($prev->get_style()->page_break_after, $page_breaks, true)) {
                // Prevent cascading splits
                $frame->split(null, true, true);
                $prev->get_style()->page_break_after = "auto";
                $this->_page_full = true;
                $frame->_already_pushed = true;

                return true;
            }

            $prev_last_child = $prev->get_last_child();
            while ($prev_last_child && (($prev_last_child->is_text_node() && $prev_last_child->get_node()->nodeValue === "")
                || $prev_last_child->get_node()->nodeName === "bullet")
            ) {
                $prev_last_child = $prev_last_child->get_prev_sibling();
            }

            if ($prev_last_child
                && $prev_last_child->is_block_level()
                && in_array($prev_last_child->get_style()->page_break_after, $page_breaks, true)
            ) {
                $frame->split(null, true, true);
                $prev_last_child->get_style()->page_break_after = "auto";
                $this->_page_full = true;
                $frame->_already_pushed = true;

                return true;
            }
        }

        return false;
    }

    /**
     * Check for a gap between the top content edge of a frame and its child
     * content.
     *
     * Additionally, the top margin, border, and padding of the frame must fit
     * on the current page.
     *
     * @param float $childPos The top margin or line-box edge of the child content.
     * @param Frame $frame The parent frame to check.
     * @return bool
     */
    protected function hasGap(float $childPos, Frame $frame): bool
    {
        $style = $frame->get_style();
        $cbw = $frame->get_containing_block("w");
        $contentEdge = $frame->get_position("y") + (float) $style->length_in_pt([
            $style->margin_top,
            $style->border_top_width,
            $style->padding_top
        ], $cbw);

        return $childPos > $contentEdge && $contentEdge <= $this->bottom_page_edge;
    }

    /**
     * Determine if a page break is allowed before $frame
     * http://www.w3.org/TR/CSS21/page.html#allowed-page-breaks
     *
     * In the normal flow, page breaks can occur at the following places:
     *
     *    1. In the vertical margin between block boxes. When an
     *    unforced page break occurs here, the used values of the
     *    relevant 'margin-top' and 'margin-bottom' properties are set
     *    to '0'. When a forced page break occurs here, the used value
     *    of the relevant 'margin-bottom' property is set to '0'; the
     *    relevant 'margin-top' used value may either be set to '0' or
     *    retained.
     *    2. Between line boxes inside a block container box.
     *    3. Between the content edge of a block container box and the
     *    outer edges of its child content (margin edges of block-level
     *    children or line box edges for inline-level children) if there
     *    is a (non-zero) gap between them.
     *
     * These breaks are subject to the following rules:
     *
     *   * Rule A: Breaking at (1) is allowed only if the
     *     'page-break-after' and 'page-break-before' properties of all
     *     the elements generating boxes that meet at this margin allow
     *     it, which is when at least one of them has the value
     *     'always', 'left', or 'right', or when all of them are 'auto'.
     *
     *   * Rule B: However, if all of them are 'auto' and a common
     *     ancestor of all the elements has a 'page-break-inside' value
     *     of 'avoid', then breaking here is not allowed.
     *
     *   * Rule C: Breaking at (2) is allowed only if the number of line
     *     boxes between the break and the start of the enclosing block
     *     box is the value of 'orphans' or more, and the number of line
     *     boxes between the break and the end of the box is the value
     *     of 'widows' or more.
     *
     *   * Rule D: In addition, breaking at (2) or (3) is allowed only
     *     if the 'page-break-inside' property of the element and all
     *     its ancestors is 'auto'.
     *
     * If the above does not provide enough break points to keep content
     * from overflowing the page boxes, then rules A, B and D are
     * dropped in order to find additional breakpoints.
     *
     * If that still does not lead to sufficient break points, rule C is
     * dropped as well, to find still more break points.
     *
     * We also allow breaks between table rows.
     *
     * @param AbstractFrameDecorator $frame the frame to check
     *
     * @return bool true if a break is allowed, false otherwise
     */
    protected function _page_break_allowed(Frame $frame)
    {
        Helpers::dompdf_debug("page-break", "_page_break_allowed(" . $frame->get_node()->nodeName . ")");
        $display = $frame->get_style()->display;

        // Block Frames (1):
        if ($frame->is_block_level() || $display === "-dompdf-image") {

            // Avoid breaks within table-cells
            if ($this->_in_table > ($display === "table" ? 1 : 0)) {
                Helpers::dompdf_debug("page-break", "In table: " . $this->_in_table);

                return false;
            }

            // Rule A
            if ($frame->get_style()->page_break_before === "avoid") {
                Helpers::dompdf_debug("page-break", "before: avoid");

                return false;
            }

            // Find the preceding block-level sibling. Inline elements are
            // treated as if wrapped in an anonymous block container here. See
            // https://www.w3.org/TR/CSS21/visuren.html#anonymous-block-level
            $prev = $frame->get_prev_sibling();
            while ($prev && (($prev->is_text_node() && $prev->get_node()->nodeValue === "")
                || $prev->get_node()->nodeName === "bullet")
            ) {
                $prev = $prev->get_prev_sibling();
            }

            // Does the previous element allow a page break after?
            if ($prev && ($prev->is_block_level() || $prev->get_style()->display === "-dompdf-image")
                && $prev->get_style()->page_break_after === "avoid"
            ) {
                Helpers::dompdf_debug("page-break", "after: avoid");

                return false;
            }

            // Rules B & D
            $parent = $frame->get_parent();
            $p = $parent;
            while ($p) {
                if ($p->get_style()->page_break_inside === "avoid") {
                    Helpers::dompdf_debug("page-break", "parent->inside: avoid");

                    return false;
                }
                $p = $p->find_block_parent();
            }

            // To prevent cascading page breaks when a top-level element has
            // page-break-inside: avoid, ensure that at least one frame is
            // on the page before splitting.
            if ($parent->get_node()->nodeName === "body" && !$prev) {
                // We are the body's first child
                Helpers::dompdf_debug("page-break", "Body's first child.");

                return false;
            }

            // Check for a possible type (3) break
            if (!$prev && $parent && !$this->hasGap($frame->get_position("y"), $parent)) {
                Helpers::dompdf_debug("page-break", "First block-level frame, no gap");

                return false;
            }

            Helpers::dompdf_debug("page-break", "block: break allowed");

            return true;

        } // Inline frames (2):
        else {
            if ($frame->is_inline_level()) {

                // Avoid breaks within table-cells
                if ($this->_in_table) {
                    Helpers::dompdf_debug("page-break", "In table: " . $this->_in_table);

                    return false;
                }

                // Rule C
                $block_parent = $frame->find_block_parent();
                $parent_style = $block_parent->get_style();
                $line = $block_parent->get_current_line_box();
                $line_count = count($block_parent->get_line_boxes());
                $line_number = $frame->get_containing_line() && empty($line->get_frames())
                    ? $line_count - 1
                    : $line_count;

                // The line number of the frame can be less than the current
                // number of line boxes, in case we are backtracking. As long as
                // we are not checking for widows yet, just checking against the
                // number of line boxes is sufficient in most cases, though.
                if ($line_number <= $parent_style->orphans) {
                    Helpers::dompdf_debug("page-break", "orphans");

                    return false;
                }

                // FIXME: Checking widows is tricky without having laid out the
                // remaining line boxes.  Just ignore it for now...

                // Rule D
                $p = $block_parent;
                while ($p) {
                    if ($p->get_style()->page_break_inside === "avoid") {
                        Helpers::dompdf_debug("page-break", "parent->inside: avoid");

                        return false;
                    }
                    $p = $p->find_block_parent();
                }

                // To prevent cascading page breaks when a top-level element has
                // page-break-inside: avoid, ensure that at least one frame with
                // some content is on the page before splitting.
                $prev = $frame->get_prev_sibling();
                while ($prev && ($prev->is_text_node() && trim($prev->get_node()->nodeValue) == "")) {
                    $prev = $prev->get_prev_sibling();
                }

                if ($block_parent->get_node()->nodeName === "body" && !$prev) {
                    // We are the body's first child
                    Helpers::dompdf_debug("page-break", "Body's first child.");

                    return false;
                }

                Helpers::dompdf_debug("page-break", "inline: break allowed");

                return true;

            // Table-rows
            } else {
                if ($display === "table-row") {

                    // If this is a nested table, prevent the page from breaking
                    if ($this->_in_table > 1) {
                        Helpers::dompdf_debug("page-break", "table: nested table");

                        return false;
                    }

                    // Rule A (table row)
                    if ($frame->get_style()->page_break_before === "avoid") {
                        Helpers::dompdf_debug("page-break", "before: avoid");

                        return false;
                    }

                    // Find the preceding row
                    $prev = $frame->get_prev_sibling();

                    if (!$prev) {
                        $prev_group = $frame->get_parent()->get_prev_sibling();

                        if ($prev_group
                            && in_array($prev_group->get_style()->display, Table::$ROW_GROUPS, true)
                        ) {
                            $prev = $prev_group->get_last_child();
                        }
                    }

                    // Check if a page break is allowed after the preceding row
                    if ($prev && $prev->get_style()->page_break_after === "avoid") {
                        Helpers::dompdf_debug("page-break", "after: avoid");

                        return false;
                    }

                    // Avoid breaking before the first row of a table
                    if (!$prev) {
                        Helpers::dompdf_debug("page-break", "table: first-row");

                        return false;
                    }

                    // Rule B (table row)
                    // Check if the page_break_inside property is not 'avoid'
                    // for the parent table or any of its ancestors
                    $table = Table::find_parent_table($frame);

                    $p = $table;
                    while ($p) {
                        if ($p->get_style()->page_break_inside === "avoid") {
                            Helpers::dompdf_debug("page-break", "parent->inside: avoid");

                            return false;
                        }
                        $p = $p->find_block_parent();
                    }

                    Helpers::dompdf_debug("page-break", "table-row: break allowed");

                    return true;
                } else {
                    if (in_array($display, Table::$ROW_GROUPS, true)) {

                        // Disallow breaks at row-groups: only split at row boundaries
                        return false;

                    } else {
                        Helpers::dompdf_debug("page-break", "? " . $display);

                        return false;
                    }
                }
            }
        }
    }

    /**
     * Check if $frame will fit on the page.  If the frame does not fit,
     * the frame tree is modified so that a page break occurs in the
     * correct location.
     *
     * @param AbstractFrameDecorator $frame the frame to check
     *
     * @return bool
     */
    function check_page_break(Frame $frame)
    {
        if ($this->_page_full || $frame->_already_pushed
            // Never check for breaks on empty text nodes
            || ($frame->is_text_node() && $frame->get_node()->nodeValue === "")
        ) {
            return false;
        }

        $p = $frame;
        do {
            $display = $p->get_style()->display;
            if ($display == "table-row") {
                if ($p->_already_pushed) { return false; }
            }
        } while ($p = $p->get_parent());

        // If the frame is absolute or fixed it shouldn't break
        $p = $frame;
        do {
            if ($p->is_absolute()) {
                return false;
            }
        } while ($p = $p->get_parent());

        $margin_height = $frame->get_margin_height();

        // Determine the frame's maximum y value
        $max_y = (float)$frame->get_position("y") + $margin_height;

        // If a split is to occur here, then the bottom margins & paddings of all
        // parents of $frame must fit on the page as well:
        $p = $frame->get_parent();
        while ($p && $p !== $this) {
            $cbw = $p->get_containing_block("w");
            $max_y += (float) $p->get_style()->computed_bottom_spacing($cbw);
            $p = $p->get_parent();
        }

        // Check if $frame flows off the page
        if ($max_y <= $this->bottom_page_edge) {
            // no: do nothing
            return false;
        }

        Helpers::dompdf_debug("page-break", "check_page_break");
        Helpers::dompdf_debug("page-break", "in_table: " . $this->_in_table);

        // yes: determine page break location
        $iter = $frame;
        $flg = false;
        $pushed_flg = false;

        $in_table = $this->_in_table;

        Helpers::dompdf_debug("page-break", "Starting search");
        while ($iter) {
            // echo "\nbacktrack: " .$iter->get_node()->nodeName ." ".spl_object_hash($iter->get_node()). "";
            if ($iter === $this) {
                Helpers::dompdf_debug("page-break", "reached root.");
                // We've reached the root in our search.  Just split at $frame.
                break;
            }

            if ($iter->_already_pushed) {
                $pushed_flg = true;
            } elseif ($this->_page_break_allowed($iter)) {
                Helpers::dompdf_debug("page-break", "break allowed, splitting.");
                $iter->split(null, true);
                $this->_page_full = true;
                $this->_in_table = $in_table;
                $iter->_already_pushed = true;
                $frame->_already_pushed = true;

                return true;
            }

            if (!$flg && $next = $iter->get_last_child()) {
                Helpers::dompdf_debug("page-break", "following last child.");

                if ($next->is_table()) {
                    $this->_in_table++;
                }

                $iter = $next;
                $pushed_flg = false;
                continue;
            }

            if ($pushed_flg) {
                // The frame was already pushed, avoid breaking on a previous page
                break;
            }

            $next = $iter->get_prev_sibling();
            // Skip empty text nodes
            while ($next && $next->is_text_node() && $next->get_node()->nodeValue === "") {
                $next = $next->get_prev_sibling();
            }

            if ($next) {
                Helpers::dompdf_debug("page-break", "following prev sibling.");

                if ($next->is_table() && !$iter->is_table()) {
                    $this->_in_table++;
                } else if (!$next->is_table() && $iter->is_table()) {
                    $this->_in_table--;
                }

                $iter = $next;
                $flg = false;
                continue;
            }

            if ($next = $iter->get_parent()) {
                Helpers::dompdf_debug("page-break", "following parent.");

                if ($iter->is_table()) {
                    $this->_in_table--;
                }

                $iter = $next;
                $flg = true;
                continue;
            }

            break;
        }

        $this->_in_table = $in_table;

        // No valid page break found.  Just break at $frame.
        Helpers::dompdf_debug("page-break", "no valid break found, just splitting.");

        // If we are in a table, backtrack to the nearest top-level table row
        if ($this->_in_table) {
            $iter = $frame;
            while ($iter && $iter->get_style()->display !== "table-row" && $iter->get_style()->display !== 'table-row-group' && $iter->_already_pushed === false) {
                $iter = $iter->get_parent();
            }

            if ($iter) {
                $iter->split(null, true);
                $iter->_already_pushed = true;
            } else {
                return false;
            }
        } else {
            $frame->split(null, true);
        }

        $this->_page_full = true;
        $frame->_already_pushed = true;

        return true;
    }

    //........................................................................

    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        // Do nothing
    }

    /**
     * Add a floating frame
     *
     * @param Frame $frame
     *
     * @return void
     */
    function add_floating_frame(Frame $frame)
    {
        array_unshift($this->_floating_frames, $frame);
    }

    /**
     * @return Frame[]
     */
    function get_floating_frames()
    {
        return $this->_floating_frames;
    }

    /**
     * @param $key
     */
    public function remove_floating_frame($key)
    {
        unset($this->_floating_frames[$key]);
    }

    /**
     * @param Frame $child
     * @return int|mixed
     */
    public function get_lowest_float_offset(Frame $child)
    {
        $style = $child->get_style();
        $side = $style->clear;
        $float = $style->float;

        $y = 0;

        if ($float === "none") {
            foreach ($this->_floating_frames as $key => $frame) {
                if ($side === "both" || $frame->get_style()->float === $side) {
                    $y = max($y, $frame->get_position("y") + $frame->get_margin_height());
                }
                $this->remove_floating_frame($key);
            }
        }

        if ($y > 0) {
            $y++; // add 1px buffer from float
        }

        return $y;
    }
}
