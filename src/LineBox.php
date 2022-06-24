<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameDecorator\Block;
use Dompdf\FrameDecorator\ListBullet;
use Dompdf\FrameDecorator\Page;
use Dompdf\FrameReflower\Text as TextFrameReflower;
use Dompdf\Positioner\Inline as InlinePositioner;

/**
 * The line box class
 *
 * This class represents a line box
 * http://www.w3.org/TR/CSS2/visuren.html#line-box
 *
 * @package dompdf
 */
class LineBox
{

    /**
     * @var Block
     */
    protected $_block_frame;

    /**
     * @var AbstractFrameDecorator[]
     */
    protected $_frames = [];

    /**
     * @var ListBullet[]
     */
    protected $list_markers = [];

    /**
     * @var int
     */
    public $wc = 0;

    /**
     * @var float
     */
    public $y = null;

    /**
     * @var float
     */
    public $w = 0.0;

    /**
     * @var float
     */
    public $h = 0.0;

    /**
     * @var float
     */
    public $left = 0.0;

    /**
     * @var float
     */
    public $right = 0.0;

    /**
     * @var AbstractFrameDecorator
     */
    public $tallest_frame = null;

    /**
     * @var bool[]
     */
    public $floating_blocks = [];

    /**
     * @var bool
     */
    public $br = false;

    /**
     * Whether the line box contains any inline-positioned frames.
     *
     * @var bool
     */
    public $inline = false;

    /**
     * Class constructor
     *
     * @param Block $frame the Block containing this line
     * @param int $y
     */
    public function __construct(Block $frame, $y = 0)
    {
        $this->_block_frame = $frame;
        $this->_frames = [];
        $this->y = $y;

        $this->get_float_offsets();
    }

    /**
     * Returns the floating elements inside the first floating parent
     *
     * @param Page $root
     *
     * @return Frame[]
     */
    public function get_floats_inside(Page $root)
    {
        $floating_frames = $root->get_floating_frames();

        if (count($floating_frames) == 0) {
            return $floating_frames;
        }

        // Find nearest floating element
        $p = $this->_block_frame;
        while ($p->get_style()->float === "none") {
            $parent = $p->get_parent();

            if (!$parent) {
                break;
            }

            $p = $parent;
        }

        if ($p == $root) {
            return $floating_frames;
        }

        $parent = $p;

        $childs = [];

        foreach ($floating_frames as $_floating) {
            $p = $_floating->get_parent();

            while (($p = $p->get_parent()) && $p !== $parent);

            if ($p) {
                $childs[] = $p;
            }
        }

        return $childs;
    }

    public function get_float_offsets()
    {
        static $anti_infinite_loop = 10000; // FIXME smelly hack

        $reflower = $this->_block_frame->get_reflower();

        if (!$reflower) {
            return;
        }

        $cb_w = null;

        $block = $this->_block_frame;
        $root = $block->get_root();

        if (!$root) {
            return;
        }

        $style = $this->_block_frame->get_style();
        $floating_frames = $this->get_floats_inside($root);
        $inside_left_floating_width = 0;
        $inside_right_floating_width = 0;
        $outside_left_floating_width = 0;
        $outside_right_floating_width = 0;

        foreach ($floating_frames as $child_key => $floating_frame) {
            $floating_frame_parent = $floating_frame->get_parent();
            $id = $floating_frame->get_id();

            if (isset($this->floating_blocks[$id])) {
                continue;
            }

            $float = $floating_frame->get_style()->float;
            $floating_width = $floating_frame->get_margin_width();

            if (!$cb_w) {
                $cb_w = $floating_frame->get_containing_block("w");
            }

            $line_w = $this->get_width();

            if (!$floating_frame->_float_next_line && ($cb_w <= $line_w + $floating_width) && ($cb_w > $line_w)) {
                $floating_frame->_float_next_line = true;
                continue;
            }

            // If the child is still shifted by the floating element
            if ($anti_infinite_loop-- > 0 &&
                $floating_frame->get_position("y") + $floating_frame->get_margin_height() >= $this->y &&
                $block->get_position("x") + $block->get_margin_width() >= $floating_frame->get_position("x")
            ) {
                if ($float === "left") {
                    if ($floating_frame_parent === $this->_block_frame) {
                        $inside_left_floating_width += $floating_width;
                    } else {
                        $outside_left_floating_width += $floating_width;
                    }
                } elseif ($float === "right") {
                    if ($floating_frame_parent === $this->_block_frame) {
                        $inside_right_floating_width += $floating_width;
                    } else {
                        $outside_right_floating_width += $floating_width;
                    }
                }

                $this->floating_blocks[$id] = true;
            } // else, the floating element won't shift anymore
            else {
                $root->remove_floating_frame($child_key);
            }
        }

        $this->left += $inside_left_floating_width;
        if ($outside_left_floating_width > 0 && $outside_left_floating_width > ((float)$style->length_in_pt($style->margin_left) + (float)$style->length_in_pt($style->padding_left))) {
            $this->left += $outside_left_floating_width - (float)$style->length_in_pt($style->margin_left) - (float)$style->length_in_pt($style->padding_left);
        }
        $this->right += $inside_right_floating_width;
        if ($outside_right_floating_width > 0 && $outside_right_floating_width > ((float)$style->length_in_pt($style->margin_left) + (float)$style->length_in_pt($style->padding_right))) {
            $this->right += $outside_right_floating_width - (float)$style->length_in_pt($style->margin_right) - (float)$style->length_in_pt($style->padding_right);
        }
    }

    /**
     * @return float
     */
    public function get_width()
    {
        return $this->left + $this->w + $this->right;
    }

    /**
     * @return Block
     */
    public function get_block_frame()
    {
        return $this->_block_frame;
    }

    /**
     * @return AbstractFrameDecorator[]
     */
    function &get_frames()
    {
        return $this->_frames;
    }

    /**
     * @param AbstractFrameDecorator $frame
     */
    public function add_frame(Frame $frame): void
    {
        $this->_frames[] = $frame;

        if ($frame->get_positioner() instanceof InlinePositioner) {
            $this->inline = true;
        }
    }

    /**
     * Remove the frame at the given index and all following frames from the
     * line.
     *
     * @param int $index
     */
    public function remove_frames(int $index): void
    {
        $lastIndex = count($this->_frames) - 1;

        if ($index < 0 || $index > $lastIndex) {
            return;
        }

        for ($i = $lastIndex; $i >= $index; $i--) {
            $f = $this->_frames[$i];
            unset($this->_frames[$i]);
            $this->w -= $f->get_margin_width();
        }

        // Reset array indices
        $this->_frames = array_values($this->_frames);

        // Recalculate the height of the line
        $h = 0.0;
        $this->inline = false;

        foreach ($this->_frames as $f) {
            $h = max($h, $f->get_margin_height());

            if ($f->get_positioner() instanceof InlinePositioner) {
                $this->inline = true;
            }
        }

        $this->h = $h;
    }

    /**
     * Get the `outside` positioned list markers to be vertically aligned with
     * the line box.
     *
     * @return ListBullet[]
     */
    public function get_list_markers(): array
    {
        return $this->list_markers;
    }

    /**
     * Add a list marker to the line box.
     *
     * The list marker is only added for the purpose of vertical alignment, it
     * is not actually added to the list of frames of the line box.
     */
    public function add_list_marker(ListBullet $marker): void
    {
        $this->list_markers[] = $marker;
    }

    /**
     * An iterator of all list markers and inline positioned frames of the line
     * box.
     *
     * @return \Iterator<AbstractFrameDecorator>
     */
    public function frames_to_align(): \Iterator
    {
        yield from $this->list_markers;

        foreach ($this->_frames as $frame) {
            if ($frame->get_positioner() instanceof InlinePositioner) {
                yield $frame;
            }
        }
    }

    /**
     * Trim trailing whitespace from the line.
     */
    public function trim_trailing_ws(): void
    {
        $lastIndex = count($this->_frames) - 1;

        if ($lastIndex < 0) {
            return;
        }

        $lastFrame = $this->_frames[$lastIndex];
        $reflower = $lastFrame->get_reflower();

        if ($reflower instanceof TextFrameReflower && !$lastFrame->is_pre()) {
            $reflower->trim_trailing_ws();
            $this->recalculate_width();
        }
    }

    /**
     * Recalculate LineBox width based on the contained frames total width.
     *
     * @return float
     */
    public function recalculate_width(): float
    {
        $width = 0.0;

        foreach ($this->_frames as $frame) {
            $width += $frame->get_margin_width();
        }

        return $this->w = $width;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        $props = ["wc", "y", "w", "h", "left", "right", "br"];
        $s = "";
        foreach ($props as $prop) {
            $s .= "$prop: " . $this->$prop . "\n";
        }
        $s .= count($this->_frames) . " frames\n";

        return $s;
    }
}

/*
class LineBoxList implements Iterator {
  private $_p = 0;
  private $_lines = array();

}
*/
