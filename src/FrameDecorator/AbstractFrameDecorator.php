<?php

namespace Dompdf\FrameDecorator;

use DOMElement;
use DOMNode;
use Dompdf\Helpers;
use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Frame\FrameTreeList;
use Dompdf\Frame\Factory;
use Dompdf\FrameReflower\AbstractFrameReflower;
use Dompdf\Css\Style;
use Dompdf\Positioner\AbstractPositioner;
use Dompdf\Exception;

/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Base AbstractFrameDecorator class
 *
 * @package dompdf
 */
abstract class AbstractFrameDecorator extends Frame
{
    const DEFAULT_COUNTER = "-dompdf-default-counter";

    /**
     * array([id] => counter_value) (for generated content)
     *
     * @var array
     */
    public $_counters = [];

    /**
     * The root node of the DOM tree
     *
     * @var Frame
     */
    protected $_root;

    /**
     * The decorated frame
     *
     * @var Frame
     */
    protected $_frame;

    /**
     * AbstractPositioner object used to position this frame (Strategy pattern)
     *
     * @var AbstractPositioner
     */
    protected $_positioner;

    /**
     * Reflower object used to calculate frame dimensions (Strategy pattern)
     *
     * @var AbstractFrameReflower
     */
    protected $_reflower;

    /**
     * Reference to the current dompdf instance
     *
     * @var Dompdf
     */
    protected $_dompdf;

    /**
     * First block parent
     *
     * @var Block
     */
    private $_block_parent;

    /**
     * First positioned parent (position: relative | absolute | fixed)
     *
     * @var AbstractFrameDecorator
     */
    private $_positionned_parent;

    /**
     * Cache for the get_parent while loop results
     *
     * @var Frame
     */
    private $_cached_parent;

    /**
     * Whether generated content and counters have been set.
     *
     * @var bool
     */
    public $content_set = false;

    /**
     * Whether the frame has been split
     *
     * @var bool
     */
    public $is_split = false;

    /**
     * Class constructor
     *
     * @param Frame $frame   The decoration target
     * @param Dompdf $dompdf The Dompdf object
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        $this->_frame = $frame;
        $this->_root = null;
        $this->_dompdf = $dompdf;
        $frame->set_decorator($this);
    }

    /**
     * "Destructor": forcibly free all references held by this object
     *
     * @param bool $recursive if true, call dispose on all children
     */
    function dispose($recursive = false)
    {
        if ($recursive) {
            while ($child = $this->get_first_child()) {
                $child->dispose(true);
            }
        }

        $this->_root = null;
        unset($this->_root);

        $this->_frame->dispose(true);
        $this->_frame = null;
        unset($this->_frame);

        $this->_positioner = null;
        unset($this->_positioner);

        $this->_reflower = null;
        unset($this->_reflower);
    }

    /**
     * Return a copy of this frame with $node as its node
     *
     * @param DOMNode $node
     *
     * @return AbstractFrameDecorator
     */
    function copy(DOMNode $node)
    {
        $frame = new Frame($node);
        $frame->set_style(clone $this->_frame->get_original_style());

        if ($node instanceof DOMElement && $node->hasAttribute("id")) {
            $node->setAttribute("data-dompdf-original-id", $node->getAttribute("id"));
            $node->removeAttribute("id");
        }

        return Factory::decorate_frame($frame, $this->_dompdf, $this->_root);
    }

    /**
     * Create a deep copy: copy this node and all children
     *
     * @return AbstractFrameDecorator
     */
    function deep_copy()
    {
        $node = $this->_frame->get_node()->cloneNode();
        $frame = new Frame($node);
        $frame->set_style(clone $this->_frame->get_original_style());

        if ($node instanceof DOMElement && $node->hasAttribute("id")) {
            $node->setAttribute("data-dompdf-original-id", $node->getAttribute("id"));
            $node->removeAttribute("id");
        }

        $deco = Factory::decorate_frame($frame, $this->_dompdf, $this->_root);

        foreach ($this->get_children() as $child) {
            $deco->append_child($child->deep_copy());
        }

        return $deco;
    }

    function reset()
    {
        $this->_frame->reset();
        $this->_reflower->reset();
        $this->reset_generated_content();
        $this->revert_counter_increment();

        $this->content_set = false;
        $this->_counters = [];

        // clear parent lookup caches
        $this->_cached_parent = null;
        $this->_block_parent = null;
        $this->_positionned_parent = null;

        // Reset all children
        foreach ($this->get_children() as $child) {
            $child->reset();
        }
    }

    /**
     * If this represents a generated node then child nodes represent generated
     * content. Remove the children since the content will be generated next
     * time this frame is reflowed.
     */
    protected function reset_generated_content(): void
    {
        if ($this->content_set
            && $this->get_node()->nodeName === "dompdf_generated"
            && $this->get_style()->content !== "normal"
            && $this->get_style()->content !== "none"
        ) {
            foreach ($this->get_children() as $child) {
                $this->remove_child($child);
            }
        }
    }

    /**
     * Decrement any counters that were incremented on the current node, unless
     * that node is the body.
     */
    protected function revert_counter_increment(): void
    {
        if ($this->content_set
            && $this->get_node()->nodeName !== "body"
            && ($decrement = $this->get_style()->counter_increment) !== "none"
        ) {
            $this->decrement_counters($decrement);
        }
    }

    // Getters -----------

    function get_id()
    {
        return $this->_frame->get_id();
    }

    /**
     * @return Frame
     */
    function get_frame()
    {
        return $this->_frame;
    }

    function get_node()
    {
        return $this->_frame->get_node();
    }

    function get_style()
    {
        return $this->_frame->get_style();
    }

    function get_original_style()
    {
        return $this->_frame->get_original_style();
    }

    function get_containing_block($i = null)
    {
        return $this->_frame->get_containing_block($i);
    }

    function get_position($i = null)
    {
        return $this->_frame->get_position($i);
    }

    /**
     * @return Dompdf
     */
    function get_dompdf()
    {
        return $this->_dompdf;
    }

    public function get_margin_width(): float
    {
        return $this->_frame->get_margin_width();
    }

    public function get_margin_height(): float
    {
        return $this->_frame->get_margin_height();
    }

    public function get_content_box(): array
    {
        return $this->_frame->get_content_box();
    }

    public function get_padding_box(): array
    {
        return $this->_frame->get_padding_box();
    }

    public function get_border_box(): array
    {
        return $this->_frame->get_border_box();
    }

    function set_id($id)
    {
        $this->_frame->set_id($id);
    }

    function set_style(Style $style)
    {
        $this->_frame->set_style($style);
    }

    function set_containing_block($x = null, $y = null, $w = null, $h = null)
    {
        $this->_frame->set_containing_block($x, $y, $w, $h);
    }

    function set_position($x = null, $y = null)
    {
        $this->_frame->set_position($x, $y);
    }

    function is_auto_height()
    {
        return $this->_frame->is_auto_height();
    }

    function is_auto_width()
    {
        return $this->_frame->is_auto_width();
    }

    function __toString()
    {
        return $this->_frame->__toString();
    }

    function prepend_child(Frame $child, $update_node = true)
    {
        while ($child instanceof AbstractFrameDecorator) {
            $child = $child->_frame;
        }

        $this->_frame->prepend_child($child, $update_node);
    }

    function append_child(Frame $child, $update_node = true)
    {
        while ($child instanceof AbstractFrameDecorator) {
            $child = $child->_frame;
        }

        $this->_frame->append_child($child, $update_node);
    }

    function insert_child_before(Frame $new_child, Frame $ref, $update_node = true)
    {
        while ($new_child instanceof AbstractFrameDecorator) {
            $new_child = $new_child->_frame;
        }

        if ($ref instanceof AbstractFrameDecorator) {
            $ref = $ref->_frame;
        }

        $this->_frame->insert_child_before($new_child, $ref, $update_node);
    }

    function insert_child_after(Frame $new_child, Frame $ref, $update_node = true)
    {
        $insert_frame = $new_child;
        while ($insert_frame instanceof AbstractFrameDecorator) {
            $insert_frame = $insert_frame->_frame;
        }

        $reference_frame = $ref;
        while ($reference_frame instanceof AbstractFrameDecorator) {
            $reference_frame = $reference_frame->_frame;
        }

        $this->_frame->insert_child_after($insert_frame, $reference_frame, $update_node);
    }

    function remove_child(Frame $child, $update_node = true)
    {
        while ($child instanceof AbstractFrameDecorator) {
            $child = $child->_frame;
        }

        return $this->_frame->remove_child($child, $update_node);
    }

    /**
     * @param bool $use_cache
     * @return AbstractFrameDecorator
     */
    function get_parent($use_cache = true)
    {
        if ($use_cache && $this->_cached_parent) {
            return $this->_cached_parent;
        }
        $p = $this->_frame->get_parent();
        if ($p && $deco = $p->get_decorator()) {
            while ($tmp = $deco->get_decorator()) {
                $deco = $tmp;
            }

            return $this->_cached_parent = $deco;
        } else {
            return $this->_cached_parent = $p;
        }
    }

    /**
     * @return AbstractFrameDecorator
     */
    function get_first_child()
    {
        $c = $this->_frame->get_first_child();
        if ($c && $deco = $c->get_decorator()) {
            while ($tmp = $deco->get_decorator()) {
                $deco = $tmp;
            }

            return $deco;
        } else {
            if ($c) {
                return $c;
            }
        }

        return null;
    }

    /**
     * @return AbstractFrameDecorator
     */
    function get_last_child()
    {
        $c = $this->_frame->get_last_child();
        if ($c && $deco = $c->get_decorator()) {
            while ($tmp = $deco->get_decorator()) {
                $deco = $tmp;
            }

            return $deco;
        } else {
            if ($c) {
                return $c;
            }
        }

        return null;
    }

    /**
     * @return AbstractFrameDecorator
     */
    function get_prev_sibling()
    {
        $s = $this->_frame->get_prev_sibling();
        if ($s && $deco = $s->get_decorator()) {
            while ($tmp = $deco->get_decorator()) {
                $deco = $tmp;
            }

            return $deco;
        } else {
            if ($s) {
                return $s;
            }
        }

        return null;
    }

    /**
     * @return AbstractFrameDecorator
     */
    function get_next_sibling()
    {
        $s = $this->_frame->get_next_sibling();
        if ($s && $deco = $s->get_decorator()) {
            while ($tmp = $deco->get_decorator()) {
                $deco = $tmp;
            }

            return $deco;
        } else {
            if ($s) {
                return $s;
            }
        }

        return null;
    }

    /**
     * @return FrameTreeList
     */
    function get_subtree()
    {
        return new FrameTreeList($this);
    }

    function set_positioner(AbstractPositioner $posn)
    {
        $this->_positioner = $posn;
        if ($this->_frame instanceof AbstractFrameDecorator) {
            $this->_frame->set_positioner($posn);
        }
    }

    function set_reflower(AbstractFrameReflower $reflower)
    {
        $this->_reflower = $reflower;
        if ($this->_frame instanceof AbstractFrameDecorator) {
            $this->_frame->set_reflower($reflower);
        }
    }

    /**
     * @return AbstractPositioner
     */
    function get_positioner()
    {
        return $this->_positioner;
    }

    /**
     * @return AbstractFrameReflower
     */
    function get_reflower()
    {
        return $this->_reflower;
    }

    /**
     * @param Frame $root
     */
    function set_root(Frame $root)
    {
        $this->_root = $root;

        if ($this->_frame instanceof AbstractFrameDecorator) {
            $this->_frame->set_root($root);
        }
    }

    /**
     * @return Page
     */
    function get_root()
    {
        return $this->_root;
    }

    /**
     * @return Block
     */
    function find_block_parent()
    {
        // Find our nearest block level parent
        if (isset($this->_block_parent)) {
            return $this->_block_parent;
        }

        $p = $this->get_parent();

        while ($p) {
            if ($p->is_block()) {
                break;
            }

            $p = $p->get_parent();
        }

        return $this->_block_parent = $p;
    }

    /**
     * @return AbstractFrameDecorator
     */
    function find_positionned_parent()
    {
        // Find our nearest relative positioned parent
        if (isset($this->_positionned_parent)) {
            return $this->_positionned_parent;
        }

        $p = $this->get_parent();
        while ($p) {
            if ($p->is_positionned()) {
                break;
            }

            $p = $p->get_parent();
        }

        if (!$p) {
            $p = $this->_root;
        }

        return $this->_positionned_parent = $p;
    }

    /**
     * Split this frame at $child.
     * The current frame is cloned and $child and all children following
     * $child are added to the clone.  The clone is then passed to the
     * current frame's parent->split() method.
     *
     * @param Frame|null $child
     * @param bool $page_break
     * @param bool $forced Whether the page break is forced.
     *
     * @throws Exception
     */
    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            $this->get_parent()->split($this, $page_break, $forced);
            return;
        }

        if ($child->get_parent() !== $this) {
            throw new Exception("Unable to split: frame is not a child of this one.");
        }

        $this->revert_counter_increment();
        $node = $this->_frame->get_node();
        $split = $this->copy($node->cloneNode());

        $style = $this->_frame->get_style();
        $split_style = $split->get_original_style();

        // Truncate the box decoration at the split, except for the body
        if ($node->nodeName !== "body") {
            // Style reset on the first and second parts
            $style->margin_bottom = 0;
            $style->padding_bottom = 0;
            $style->border_bottom = 0;
            $style->border_bottom_left_radius = 0;
            $style->border_bottom_right_radius = 0;

            // second
            $split_style->margin_top = 0;
            $split_style->padding_top = 0;
            $split_style->border_top = 0;
            $split_style->border_top_left_radius = 0;
            $split_style->border_top_right_radius = 0;
            $split_style->page_break_before = "auto";
        }

        $split_style->text_indent = 0;
        $split_style->counter_reset = "none";

        $split->set_style(clone $split_style);
        $this->is_split = true;
        $split->_splitted = true;
        $split->_already_pushed = true;

        $this->get_parent()->insert_child_after($split, $this);

        if ($this instanceof Block) {
            // Remove the frames that will be moved to the new split node from
            // the line boxes
            $this->remove_frames_from_line($child);

            // recalculate the float offsets after paging
            foreach ($this->get_line_boxes() as $line_box) {
                $line_box->get_float_offsets();
            }
        }

        if (!$forced) {
            // Reset top margin in case of an unforced page break
            // https://www.w3.org/TR/CSS21/page.html#allowed-page-breaks
            $child->get_original_style()->margin_top = 0;
        }

        // Add $child and all following siblings to the new split node
        $iter = $child;
        while ($iter) {
            $frame = $iter;
            $iter = $iter->get_next_sibling();
            $frame->reset();
            $frame->_parent = $split;
            $split->append_child($frame);

            // recalculate the float offsets
            if ($frame instanceof Block) {
                foreach ($frame->get_line_boxes() as $line_box) {
                    $line_box->get_float_offsets();
                }
            }
        }

        $this->get_parent()->split($split, $page_break, $forced);

        // Preserve the current counter values. This must be done after the
        // parent split, as counters get reset on frame reset
        $split->_counters = $this->_counters;
    }

    /**
     * @param string $id
     * @param int $value
     */
    function reset_counter($id = self::DEFAULT_COUNTER, $value = 0)
    {
        $this->get_parent()->_counters[$id] = intval($value);
    }

    /**
     * @param $counters
     */
    function decrement_counters($counters)
    {
        foreach ($counters as $id => $increment) {
            $this->increment_counter($id, intval($increment) * -1);
        }
    }

    /**
     * @param $counters
     */
    function increment_counters($counters)
    {
        foreach ($counters as $id => $increment) {
            $this->increment_counter($id, intval($increment));
        }
    }

    /**
     * @param string $id
     * @param int $increment
     */
    function increment_counter($id = self::DEFAULT_COUNTER, $increment = 1)
    {
        $counter_frame = $this->lookup_counter_frame($id);

        if ($counter_frame) {
            if (!isset($counter_frame->_counters[$id])) {
                $counter_frame->_counters[$id] = 0;
            }

            $counter_frame->_counters[$id] += $increment;
        }
    }

    /**
     * @param string $id
     * @return AbstractFrameDecorator|null
     */
    function lookup_counter_frame($id = self::DEFAULT_COUNTER)
    {
        $f = $this->get_parent();

        while ($f) {
            if (isset($f->_counters[$id])) {
                return $f;
            }
            $fp = $f->get_parent();

            if (!$fp) {
                return $f;
            }

            $f = $fp;
        }

        return null;
    }

    /**
     * @param string $id
     * @param string $type
     * @return bool|string
     *
     * TODO: What version is the best : this one or the one in ListBullet ?
     */
    function counter_value($id = self::DEFAULT_COUNTER, $type = "decimal")
    {
        $type = mb_strtolower($type);

        if (!isset($this->_counters[$id])) {
            $this->_counters[$id] = 0;
        }

        $value = $this->_counters[$id];

        switch ($type) {
            default:
            case "decimal":
                return $value;

            case "decimal-leading-zero":
                return str_pad($value, 2, "0", STR_PAD_LEFT);

            case "lower-roman":
                return Helpers::dec2roman($value);

            case "upper-roman":
                return mb_strtoupper(Helpers::dec2roman($value));

            case "lower-latin":
            case "lower-alpha":
                return chr((($value - 1) % 26) + ord('a'));

            case "upper-latin":
            case "upper-alpha":
                return chr((($value - 1) % 26) + ord('A'));

            case "lower-greek":
                return Helpers::unichr($value + 944);

            case "upper-greek":
                return Helpers::unichr($value + 912);
        }
    }

    final function position()
    {
        $this->_positioner->position($this);
    }

    /**
     * @param float $offset_x
     * @param float $offset_y
     * @param bool $ignore_self
     */
    final function move($offset_x, $offset_y, $ignore_self = false)
    {
        $this->_positioner->move($this, $offset_x, $offset_y, $ignore_self);
    }

    /**
     * @param Block|null $block
     */
    final function reflow(Block $block = null)
    {
        // Uncomment this to see the frames before they're laid out, instead of
        // during rendering.
        //echo $this->_frame; flush();
        $this->_reflower->reflow($block);
    }

    /**
     * @return array
     */
    final function get_min_max_width(): array
    {
        return $this->_reflower->get_min_max_width();
    }
}
