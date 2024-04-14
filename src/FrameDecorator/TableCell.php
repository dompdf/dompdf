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
