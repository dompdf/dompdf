<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;

/**
 * Decorates table cells for layout
 *
 * @package dompdf
 */
class TableCell extends BlockFrameDecorator
{
    /**
     * @var float
     */
    protected $content_height;

    public $_split_frame = null;

    //........................................................................

    /**
     * TableCell constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
        $this->content_height = 0.0;
    }

    function reset()
    {
        parent::reset();
        $this->content_height = 0.0;
    }

    public function split(?Frame $child = null, bool $page_break = false, bool $forced = false): void
    {
        if (is_null($child)) {
            $this->get_parent()->split(null, $page_break, $forced);
            return;
        }

        if ($child->get_parent() !== $this) {
            throw new Exception("Unable to split: frame is not a child of this one.");
        }

        $this->revert_counter_increment();

        $node = $this->_frame->get_node();
        $split = $this->copy($node->cloneNode());

        $style = $this->_frame->get_style();
        $split_style = $split->get_style();

        // Truncate the box decoration at the split

        // Clear bottom decoration of original frame
        $style->margin_bottom = 0.0;
        $style->padding_bottom = 0.0;
        $style->border_bottom_width = 0.0;
        $style->border_bottom_left_radius = 0.0;
        $style->border_bottom_right_radius = 0.0;

        // Clear top decoration of split frame
        $split_style->margin_top = 0.0;
        $split_style->padding_top = 0.0;
        $split_style->border_top_width = 0.0;
        $split_style->border_top_left_radius = 0.0;
        $split_style->border_top_right_radius = 0.0;
        $split_style->page_break_before = "auto";

        $split_style->text_indent = 0.0;
        $split_style->counter_reset = "none";

        $this->is_split = true;
        $split->is_split_off = true;
        $split->_already_pushed = false;

        $iter = $child;
        while ($iter) {
            $frame = $iter;
            $iter = $iter->get_next_sibling();
            $frame->reset();
            $split->append_child($frame);
        }


        $this->_split_frame = $split;

        // Reset top margin in case of an unforced page break
        // https://www.w3.org/TR/CSS21/page.html#allowed-page-breaks
        $child->get_style()->margin_top = 0.0;

        // don't split the parent yet since we may have more columns to render
    }

    /**
     * @return float
     */
    public function get_content_height(): float
    {
        return $this->content_height;
    }

    /**
     * @param float $height
     */
    public function set_content_height(float $height): void
    {
        $this->content_height = $height;
    }

    /**
     * @param float $height
     */
    public function set_cell_height(float $height): void
    {
        $style = $this->get_style();
        $v_space = (float)$style->length_in_pt(
            [
                $style->margin_top,
                $style->padding_top,
                $style->border_top_width,
                $style->border_bottom_width,
                $style->padding_bottom,
                $style->margin_bottom
            ],
            (float)$style->length_in_pt($style->height)
        );

        $new_height = $height - $v_space;
        $style->set_used("height", $new_height);

        if ($new_height > $this->content_height) {
            $y_offset = 0;

            // Adjust our vertical alignment
            switch ($style->vertical_align) {
                default:
                case "baseline":
                    // FIXME: this isn't right

                case "top":
                    // Don't need to do anything
                    return;

                case "middle":
                    $y_offset = ($new_height - $this->content_height) / 2;
                    break;

                case "bottom":
                    $y_offset = $new_height - $this->content_height;
                    break;
            }

            if ($y_offset) {
                // Move our children
                foreach ($this->get_line_boxes() as $line) {
                    foreach ($line->get_frames() as $frame) {
                        $frame->move(0, $y_offset);
                    }
                }
            }
        }
    }
}
