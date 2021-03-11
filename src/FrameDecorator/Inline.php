<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use DOMElement;
use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Exception;

/**
 * Decorates frames for inline layout
 *
 * @access  private
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

    public function split(Frame $child = null, bool $force_pagebreak = false)
    {
        if (is_null($child)) {
            $this->get_parent()->split($this, $force_pagebreak);
            return;
        }

        if ($child->get_parent() !== $this) {
            throw new Exception("Unable to split: frame is not a child of this one.");
        }

        $node = $this->_frame->get_node();

        if ($node instanceof DOMElement && $node->hasAttribute("id")) {
            $node->setAttribute("data-dompdf-original-id", $node->getAttribute("id"));
            $node->removeAttribute("id");
        }

        $split = $this->copy($node->cloneNode());
        // if this is a generated node don't propagate the content style
        if ($split->get_node()->nodeName == "dompdf_generated") {
            $split->get_style()->content = "normal";
        }
        $this->get_parent()->insert_child_after($split, $this);

        // Unset the current node's right style properties
        $style = $this->_frame->get_style();
        $style->margin_right = 0;
        $style->padding_right = 0;
        $style->border_right_width = 0;

        // Unset the split node's left style properties since we don't want them
        // to propagate
        $style = $split->get_style();
        $style->margin_left = 0;
        $style->padding_left = 0;
        $style->border_left_width = 0;

        //On continuation of inline element on next line,
        //don't repeat non-vertically repeatable background images
        //See e.g. in testcase image_variants, long descriptions
        if (($url = $style->background_image) && $url !== "none"
            && ($repeat = $style->background_repeat) && $repeat !== "repeat" && $repeat !== "repeat-y"
        ) {
            $style->background_image = "none";
        }

        // Add $child and all following siblings to the new split node
        $iter = $child;
        while ($iter) {
            $frame = $iter;
            $iter = $iter->get_next_sibling();
            $frame->reset();
            $split->append_child($frame);
        }

        $page_breaks = ["always", "left", "right"];
        $frame_style = $frame->get_style();
        if ($force_pagebreak ||
            in_array($frame_style->page_break_before, $page_breaks) ||
            in_array($frame_style->page_break_after, $page_breaks)
        ) {
            $this->get_parent()->split($split, true);
        }
    }

}
