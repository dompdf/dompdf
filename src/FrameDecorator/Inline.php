<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Exception;

/**
 * Decorates frames for inline layout
 *
 * @package dompdf
 */
class Inline extends AbstractFrameDecorator
{

    /**
     * Inline constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
    }

    /**
     * Vertical padding, border, and margin do not apply when determining the
     * height for inline frames.
     *
     * http://www.w3.org/TR/CSS21/visudet.html#inline-non-replaced
     *
     * The vertical padding, border and margin of an inline, non-replaced box
     * start at the top and bottom of the content area, not the
     * 'line-height'. But only the 'line-height' is used to calculate the
     * height of the line box.
     *
     * @return float
     */
    public function get_margin_height(): float
    {
        $style = $this->get_style();
        $font = $style->font_family;
        $size = $style->font_size;
        $fontHeight = $this->_dompdf->getFontMetrics()->getFontHeight($font, $size);

        return ($style->line_height / ($size > 0 ? $size : 1)) * $fontHeight;
    }

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
        $split_style = $split->get_style();

        // Unset the current node's right style properties
        $style->margin_right = 0.0;
        $style->padding_right = 0.0;
        $style->border_right_width = 0.0;
        $style->border_top_right_radius = 0.0;
        $style->border_bottom_right_radius = 0.0;

        // Unset the split node's left style properties since we don't want them
        // to propagate
        $split_style->margin_left = 0.0;
        $split_style->padding_left = 0.0;
        $split_style->border_left_width = 0.0;
        $split_style->border_top_left_radius = 0.0;
        $split_style->border_bottom_left_radius = 0.0;

        // If this is a generated node don't propagate the content style
        if ($split->get_node()->nodeName == "dompdf_generated") {
            $split_style->content = "normal";
        }

        //On continuation of inline element on next line,
        //don't repeat non-horizontally repeatable background images
        //See e.g. in testcase image_variants, long descriptions
        if (($url = $style->background_image) && $url !== "none"
            && ($repeat = $style->background_repeat) && $repeat !== "repeat" && $repeat !== "repeat-x"
        ) {
            $split_style->background_image = "none";
        }

        $this->get_parent()->insert_child_after($split, $this);

        // Add $child and all following siblings to the new split node
        $iter = $child;
        while ($iter) {
            $frame = $iter;
            $iter = $iter->get_next_sibling();
            $frame->reset();
            $split->append_child($frame);
        }

        $parent = $this->get_parent();

        if ($page_break) {
            $parent->split($split, $page_break, $forced);
        } elseif ($parent instanceof Inline) {
            $parent->split($split);
        }
    }

}
