<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\LineBox;

/**
 * Decorates frames for block layout
 *
 * @package dompdf
 */
class Block extends AbstractFrameDecorator
{
    /**
     * Current line index
     *
     * @var int
     */
    protected $_cl;

    /**
     * The block's line boxes
     *
     * @var LineBox[]
     */
    protected $_line_boxes;

    /**
     * List of markers that have not found their line box to vertically align
     * with yet. Markers are collected by nested block containers until an
     * inline line box is found at the start of the block.
     *
     * @var ListBullet[]
     */
    protected $dangling_markers;

    /**
     * Block constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);

        $this->_line_boxes = [new LineBox($this)];
        $this->_cl = 0;
        $this->dangling_markers = [];
    }

    function reset()
    {
        parent::reset();

        $this->_line_boxes = [new LineBox($this)];
        $this->_cl = 0;
        $this->dangling_markers = [];
    }

    /**
     * @return LineBox
     */
    function get_current_line_box()
    {
        return $this->_line_boxes[$this->_cl];
    }

    /**
     * @return int
     */
    function get_current_line_number()
    {
        return $this->_cl;
    }

    /**
     * @return LineBox[]
     */
    function get_line_boxes()
    {
        return $this->_line_boxes;
    }

    /**
     * @param int $line_number
     * @return int
     */
    function set_current_line_number($line_number)
    {
        $line_boxes_count = count($this->_line_boxes);
        $cl = max(min($line_number, $line_boxes_count), 0);
        return ($this->_cl = $cl);
    }

    /**
     * @param int $i
     */
    function clear_line($i)
    {
        if (isset($this->_line_boxes[$i])) {
            unset($this->_line_boxes[$i]);
        }
    }

    /**
     * @param Frame $frame
     * @return LineBox|null
     */
    public function add_frame_to_line(Frame $frame): ?LineBox
    {
        $current_line = $this->_line_boxes[$this->_cl];
        $frame->set_containing_line($current_line);

        // Inline frames are currently treated as wrappers, and are not actually
        // added to the line
        if ($frame instanceof Inline) {
            return null;
        }

        $current_line->add_frame($frame);

        $this->increase_line_width($frame->get_margin_width());
        $this->maximize_line_height($frame->get_margin_height(), $frame);

        // Add any dangling list markers to the first line box if it is inline
        if ($this->_cl === 0 && $current_line->inline
            && $this->dangling_markers !== []
        ) {
            foreach ($this->dangling_markers as $marker) {
                $current_line->add_list_marker($marker);
                $this->maximize_line_height($marker->get_margin_height(), $marker);
            }

            $this->dangling_markers = [];
        }

        return $current_line;
    }

    /**
     * Remove the given frame and all following frames and lines from the block.
     *
     * @param Frame $frame
     */
    public function remove_frames_from_line(Frame $frame): void
    {
        // Inline frames are not added to line boxes themselves, only their
        // text frame children
        $actualFrame = $frame;
        while ($actualFrame !== null && $actualFrame instanceof Inline) {
            $actualFrame = $actualFrame->get_first_child();
        }

        if ($actualFrame === null) {
            return;
        }

        // Search backwards through the lines for $frame
        $frame = $actualFrame;
        $i = $this->_cl;
        $j = null;

        while ($i > 0) {
            $line = $this->_line_boxes[$i];
            foreach ($line->get_frames() as $index => $f) {
                if ($frame === $f) {
                    $j = $index;
                    break 2;
                }
            }
            $i--;
        }

        if ($j === null) {
            return;
        }

        // Remove all lines that follow
        for ($k = $this->_cl; $k > $i; $k--) {
            unset($this->_line_boxes[$k]);
        }

        // Remove the line, if it is empty
        if ($j > 0) {
            $line->remove_frames($j);
        } else {
            unset($this->_line_boxes[$i]);
        }

        // Reset array indices
        $this->_line_boxes = array_values($this->_line_boxes);
        $this->_cl = count($this->_line_boxes) - 1;
    }

    /**
     * @param float $w
     */
    public function increase_line_width(float $w): void
    {
        $this->_line_boxes[$this->_cl]->w += $w;
    }

    /**
     * @param float $val
     * @param Frame $frame
     */
    public function maximize_line_height(float $val, Frame $frame): void
    {
        if ($val > $this->_line_boxes[$this->_cl]->h) {
            $this->_line_boxes[$this->_cl]->tallest_frame = $frame;
            $this->_line_boxes[$this->_cl]->h = $val;
        }
    }

    /**
     * @param bool $br
     */
    public function add_line(bool $br = false): void
    {
        $line = $this->_line_boxes[$this->_cl];

        $line->br = $br;
        $y = $line->y + $line->h;

        $new_line = new LineBox($this, $y);

        $this->_line_boxes[++$this->_cl] = $new_line;
    }

    /**
     * @param ListBullet $marker
     */
    public function add_dangling_marker(ListBullet $marker): void
    {
        $this->dangling_markers[] = $marker;
    }

    /**
     * Inherit any dangling markers from the parent block.
     *
     * @param Block $block
     */
    public function inherit_dangling_markers(self $block): void
    {
        if ($block->dangling_markers !== []) {
            $this->dangling_markers = $block->dangling_markers;
            $block->dangling_markers = [];
        }
    }
}
