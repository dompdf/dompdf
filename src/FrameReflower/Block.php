<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\Frame;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\TableCell as TableCellFrameDecorator;
use Dompdf\FrameDecorator\Text as TextFrameDecorator;
use Dompdf\Exception;
use Dompdf\Css\Style;

/**
 * Reflows block frames
 *
 * @package dompdf
 */
class Block extends AbstractFrameReflower
{
    // Minimum line width to justify, as fraction of available width
    const MIN_JUSTIFY_WIDTH = 0.80;

    /**
     * @var BlockFrameDecorator
     */
    protected $_frame;

    function __construct(BlockFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    /**
     *  Calculate the ideal used value for the width property as per:
     *  http://www.w3.org/TR/CSS21/visudet.html#Computing_widths_and_margins
     *
     * @param float $width
     *
     * @return array
     */
    protected function _calculate_width($width)
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $w = $frame->get_containing_block("w");

        if ($style->position === "fixed") {
            $w = $frame->get_parent()->get_containing_block("w");
        }

        $rm = $style->length_in_pt($style->margin_right, $w);
        $lm = $style->length_in_pt($style->margin_left, $w);

        $left = $style->length_in_pt($style->left, $w);
        $right = $style->length_in_pt($style->right, $w);

        // Handle 'auto' values
        $dims = array($style->border_left_width,
            $style->border_right_width,
            $style->padding_left,
            $style->padding_right,
            $width !== "auto" ? $width : 0,
            $rm !== "auto" ? $rm : 0,
            $lm !== "auto" ? $lm : 0);

        // absolutely positioned boxes take the 'left' and 'right' properties into account
        if ($frame->is_absolute()) {
            $absolute = true;
            $dims[] = $left !== "auto" ? $left : 0;
            $dims[] = $right !== "auto" ? $right : 0;
        } else {
            $absolute = false;
        }

        $sum = (float)$style->length_in_pt($dims, $w);

        // Compare to the containing block
        $diff = $w - $sum;

        if ($diff > 0) {
            if ($absolute) {
                // resolve auto properties: see
                // http://www.w3.org/TR/CSS21/visudet.html#abs-non-replaced-width

                if ($width === "auto" && $left === "auto" && $right === "auto") {
                    if ($lm === "auto") {
                        $lm = 0;
                    }
                    if ($rm === "auto") {
                        $rm = 0;
                    }

                    // Technically, the width should be "shrink-to-fit" i.e. based on the
                    // preferred width of the content...  a little too costly here as a
                    // special case.  Just get the width to take up the slack:
                    $left = 0;
                    $right = 0;
                    $width = $diff;
                } else if ($width === "auto") {
                    if ($lm === "auto") {
                        $lm = 0;
                    }
                    if ($rm === "auto") {
                        $rm = 0;
                    }
                    if ($left === "auto") {
                        $left = 0;
                    }
                    if ($right === "auto") {
                        $right = 0;
                    }

                    $width = $diff;
                } else if ($left === "auto") {
                    if ($lm === "auto") {
                        $lm = 0;
                    }
                    if ($rm === "auto") {
                        $rm = 0;
                    }
                    if ($right === "auto") {
                        $right = 0;
                    }

                    $left = $diff;
                } else if ($right === "auto") {
                    if ($lm === "auto") {
                        $lm = 0;
                    }
                    if ($rm === "auto") {
                        $rm = 0;
                    }

                    $right = $diff;
                }

            } else {
                // Find auto properties and get them to take up the slack
                if ($width === "auto") {
                    $width = $diff;
                } else if ($lm === "auto" && $rm === "auto") {
                    $lm = $rm = round($diff / 2);
                } else if ($lm === "auto") {
                    $lm = $diff;
                } else if ($rm === "auto") {
                    $rm = $diff;
                }
            }
        } else if ($diff < 0) {
            // We are over constrained--set margin-right to the difference
            $rm = $diff;
        }

        return array(
            "width" => $width,
            "margin_left" => $lm,
            "margin_right" => $rm,
            "left" => $left,
            "right" => $right,
        );
    }

    /**
     * Call the above function, but resolve max/min widths
     *
     * @throws Exception
     * @return array
     */
    protected function _calculate_restricted_width()
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $cb = $frame->get_containing_block();

        if ($style->position === "fixed") {
            $cb = $frame->get_root()->get_containing_block();
        }

        //if ( $style->position === "absolute" )
        //  $cb = $frame->find_positionned_parent()->get_containing_block();

        if (!isset($cb["w"])) {
            throw new Exception("Box property calculation requires containing block width");
        }

        // Treat width 100% as auto
        if ($style->width === "100%") {
            $width = "auto";
        } else {
            $width = $style->length_in_pt($style->width, $cb["w"]);
        }

        $calculate_width = $this->_calculate_width($width);
        $margin_left = $calculate_width['margin_left'];
        $margin_right = $calculate_width['margin_right'];
        $width =  $calculate_width['width'];
        $left =  $calculate_width['left'];
        $right =  $calculate_width['right'];

        // Handle min/max width
        $min_width = $style->length_in_pt($style->min_width, $cb["w"]);
        $max_width = $style->length_in_pt($style->max_width, $cb["w"]);

        if ($max_width !== "none" && $min_width > $max_width) {
            list($max_width, $min_width) = array($min_width, $max_width);
        }

        if ($max_width !== "none" && $width > $max_width) {
            extract($this->_calculate_width($max_width));
        }

        if ($width < $min_width) {
            $calculate_width = $this->_calculate_width($min_width);
            $margin_left = $calculate_width['margin_left'];
            $margin_right = $calculate_width['margin_right'];
            $width =  $calculate_width['width'];
            $left =  $calculate_width['left'];
            $right =  $calculate_width['right'];
        }

        return array($width, $margin_left, $margin_right, $left, $right);
    }

    /**
     * Determine the unrestricted height of content within the block
     * not by adding each line's height, but by getting the last line's position.
     * This because lines could have been pushed lower by a clearing element.
     *
     * @return float
     */
    protected function _calculate_content_height()
    {
        $height = 0;
        $lines = $this->_frame->get_line_boxes();
        if (count($lines) > 0) {
            $last_line = end($lines);
            $content_box = $this->_frame->get_content_box();
            $height = $last_line->y + $last_line->h - $content_box["y"];
        }
        return $height;
    }

    /**
     * Determine the frame's restricted height
     *
     * @return array
     */
    protected function _calculate_restricted_height()
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $content_height = $this->_calculate_content_height();
        $cb = $frame->get_containing_block();

        $height = $style->length_in_pt($style->height, $cb["h"]);

        $top = $style->length_in_pt($style->top, $cb["h"]);
        $bottom = $style->length_in_pt($style->bottom, $cb["h"]);

        $margin_top = $style->length_in_pt($style->margin_top, $cb["h"]);
        $margin_bottom = $style->length_in_pt($style->margin_bottom, $cb["h"]);

        if ($frame->is_absolute()) {

            // see http://www.w3.org/TR/CSS21/visudet.html#abs-non-replaced-height

            $dims = array($top !== "auto" ? $top : 0,
                $style->margin_top !== "auto" ? $style->margin_top : 0,
                $style->padding_top,
                $style->border_top_width,
                $height !== "auto" ? $height : 0,
                $style->border_bottom_width,
                $style->padding_bottom,
                $style->margin_bottom !== "auto" ? $style->margin_bottom : 0,
                $bottom !== "auto" ? $bottom : 0);

            $sum = (float)$style->length_in_pt($dims, $cb["h"]);

            $diff = $cb["h"] - $sum;

            if ($diff > 0) {
                if ($height === "auto" && $top === "auto" && $bottom === "auto") {
                    if ($margin_top === "auto") {
                        $margin_top = 0;
                    }
                    if ($margin_bottom === "auto") {
                        $margin_bottom = 0;
                    }

                    $height = $diff;
                } else if ($height === "auto" && $top === "auto") {
                    if ($margin_top === "auto") {
                        $margin_top = 0;
                    }
                    if ($margin_bottom === "auto") {
                        $margin_bottom = 0;
                    }

                    $height = $content_height;
                    $top = $diff - $content_height;
                } else if ($height === "auto" && $bottom === "auto") {
                    if ($margin_top === "auto") {
                        $margin_top = 0;
                    }
                    if ($margin_bottom === "auto") {
                        $margin_bottom = 0;
                    }

                    $height = $content_height;
                    $bottom = $diff - $content_height;
                } else if ($top === "auto" && $bottom === "auto") {
                    if ($margin_top === "auto") {
                        $margin_top = 0;
                    }
                    if ($margin_bottom === "auto") {
                        $margin_bottom = 0;
                    }

                    $bottom = $diff;
                } else if ($top === "auto") {
                    if ($margin_top === "auto") {
                        $margin_top = 0;
                    }
                    if ($margin_bottom === "auto") {
                        $margin_bottom = 0;
                    }

                    $top = $diff;
                } else if ($height === "auto") {
                    if ($margin_top === "auto") {
                        $margin_top = 0;
                    }
                    if ($margin_bottom === "auto") {
                        $margin_bottom = 0;
                    }

                    $height = $diff;
                } else if ($bottom === "auto") {
                    if ($margin_top === "auto") {
                        $margin_top = 0;
                    }
                    if ($margin_bottom === "auto") {
                        $margin_bottom = 0;
                    }

                    $bottom = $diff;
                } else {
                    if ($style->overflow === "visible") {
                        // set all autos to zero
                        if ($margin_top === "auto") {
                            $margin_top = 0;
                        }
                        if ($margin_bottom === "auto") {
                            $margin_bottom = 0;
                        }
                        if ($top === "auto") {
                            $top = 0;
                        }
                        if ($bottom === "auto") {
                            $bottom = 0;
                        }
                        if ($height === "auto") {
                            $height = $content_height;
                        }
                    }

                    // FIXME: overflow hidden
                }
            }

        } else {
            // Expand the height if overflow is visible
            if ($height === "auto" && $content_height > $height /* && $style->overflow === "visible" */) {
                $height = $content_height;
            }

            // FIXME: this should probably be moved to a seperate function as per
            // _calculate_restricted_width

            // Only handle min/max height if the height is independent of the frame's content
            if (!($style->overflow === "visible" || ($style->overflow === "hidden" && $height === "auto"))) {
                $min_height = $style->min_height;
                $max_height = $style->max_height;

                if (isset($cb["h"])) {
                    $min_height = $style->length_in_pt($min_height, $cb["h"]);
                    $max_height = $style->length_in_pt($max_height, $cb["h"]);
                } else if (isset($cb["w"])) {
                    if (mb_strpos($min_height, "%") !== false) {
                        $min_height = 0;
                    } else {
                        $min_height = $style->length_in_pt($min_height, $cb["w"]);
                    }

                    if (mb_strpos($max_height, "%") !== false) {
                        $max_height = "none";
                    } else {
                        $max_height = $style->length_in_pt($max_height, $cb["w"]);
                    }
                }

                if ($max_height !== "none" && $min_height > $max_height) {
                    // Swap 'em
                    list($max_height, $min_height) = array($min_height, $max_height);
                }

                if ($max_height !== "none" && $height > $max_height) {
                    $height = $max_height;
                }

                if ($height < $min_height) {
                    $height = $min_height;
                }
            }
        }

        return array($height, $margin_top, $margin_bottom, $top, $bottom);
    }

    /**
     * Adjust the justification of each of our lines.
     * http://www.w3.org/TR/CSS21/text.html#propdef-text-align
     */
    protected function _text_align()
    {
        $style = $this->_frame->get_style();
        $w = $this->_frame->get_containing_block("w");
        $width = (float)$style->length_in_pt($style->width, $w);

        switch ($style->text_align) {
            default:
            case "left":
                foreach ($this->_frame->get_line_boxes() as $line) {
                    if (!$line->left) {
                        continue;
                    }

                    foreach ($line->get_frames() as $frame) {
                        if ($frame instanceof BlockFrameDecorator) {
                            continue;
                        }
                        $frame->set_position($frame->get_position("x") + $line->left);
                    }
                }
                return;

            case "right":
                foreach ($this->_frame->get_line_boxes() as $line) {
                    // Move each child over by $dx
                    $dx = $width - $line->w - $line->right;

                    foreach ($line->get_frames() as $frame) {
                        // Block frames are not aligned by text-align
                        if ($frame instanceof BlockFrameDecorator) {
                            continue;
                        }

                        $frame->set_position($frame->get_position("x") + $dx);
                    }
                }
                break;

            case "justify":
                // We justify all lines except the last one
                $lines = $this->_frame->get_line_boxes(); // needs to be a variable (strict standards)
                $last_line = array_pop($lines);

                foreach ($lines as $i => $line) {
                    if ($line->br) {
                        unset($lines[$i]);
                    }
                }

                // One space character's width. Will be used to get a more accurate spacing
                $space_width = $this->get_dompdf()->getFontMetrics()->getTextWidth(" ", $style->font_family, $style->font_size);

                foreach ($lines as $line) {
                    if ($line->left) {
                        foreach ($line->get_frames() as $frame) {
                            if (!$frame instanceof TextFrameDecorator) {
                                continue;
                            }

                            $frame->set_position($frame->get_position("x") + $line->left);
                        }
                    }

                    // Set the spacing for each child
                    if ($line->wc > 1) {
                        $spacing = ($width - ($line->left + $line->w + $line->right) + $space_width) / ($line->wc - 1);
                    } else {
                        $spacing = 0;
                    }

                    $dx = 0;
                    foreach ($line->get_frames() as $frame) {
                        if (!$frame instanceof TextFrameDecorator) {
                            continue;
                        }

                        $text = $frame->get_text();
                        $spaces = mb_substr_count($text, " ");

                        $char_spacing = (float)$style->length_in_pt($style->letter_spacing);
                        $_spacing = $spacing + $char_spacing;

                        $frame->set_position($frame->get_position("x") + $dx);
                        $frame->set_text_spacing($_spacing);

                        $dx += $spaces * $_spacing;
                    }

                    // The line (should) now occupy the entire width
                    $line->w = $width;
                }

                // Adjust the last line if necessary
                if ($last_line->left) {
                    foreach ($last_line->get_frames() as $frame) {
                        if ($frame instanceof BlockFrameDecorator) {
                            continue;
                        }
                        $frame->set_position($frame->get_position("x") + $last_line->left);
                    }
                }
                break;

            case "center":
            case "centre":
                foreach ($this->_frame->get_line_boxes() as $line) {
                    // Centre each line by moving each frame in the line by:
                    $dx = ($width + $line->left - $line->w - $line->right) / 2;

                    foreach ($line->get_frames() as $frame) {
                        // Block frames are not aligned by text-align
                        if ($frame instanceof BlockFrameDecorator) {
                            continue;
                        }

                        $frame->set_position($frame->get_position("x") + $dx);
                    }
                }
                break;
        }
    }

    /**
     * Align inline children vertically.
     * Aligns each child vertically after each line is reflowed
     */
    function vertical_align()
    {
        $canvas = null;

        foreach ($this->_frame->get_line_boxes() as $line) {

            $height = $line->h;

            foreach ($line->get_frames() as $frame) {
                $style = $frame->get_style();
                $isInlineBlock = (
                    '-dompdf-image' === $style->display
                    || 'inline-block' === $style->display
                    || 'inline-table' === $style->display
                );
                if (!$isInlineBlock && $style->display !== "inline") {
                    continue;
                }

                if (!isset($canvas)) {
                    $canvas = $frame->get_root()->get_dompdf()->get_canvas();
                }

                $baseline = $canvas->get_font_baseline($style->font_family, $style->font_size);
                $y_offset = 0;

                //FIXME: The 0.8 ratio applied to the height is arbitrary (used to accommodate descenders?)
                if($isInlineBlock) {
                    $lineFrames = $line->get_frames();
                    if (count($lineFrames) == 1) {
                        continue;
                    }
                    $frameBox = $frame->get_frame()->get_border_box();
                    $imageHeightDiff = $height * 0.8 - (float)$frameBox['h'];

                    $align = $frame->get_style()->vertical_align;
                    if (in_array($align, Style::$vertical_align_keywords) === true) {
                        switch ($align) {
                            case "middle":
                                $y_offset = $imageHeightDiff / 2;
                                break;

                            case "sub":
                                $y_offset = 0.3 * $height + $imageHeightDiff;
                                break;

                            case "super":
                                $y_offset = -0.2 * $height + $imageHeightDiff;
                                break;

                            case "text-top": // FIXME: this should be the height of the frame minus the height of the text
                                $y_offset = $height - $style->line_height;
                                break;

                            case "top":
                                break;

                            case "text-bottom": // FIXME: align bottom of image with the descender?
                            case "bottom":
                                $y_offset = 0.3 * $height + $imageHeightDiff;
                                break;

                            case "baseline":
                            default:
                                $y_offset = $imageHeightDiff;
                                break;
                        }
                    } else {
                        $y_offset = $baseline - (float)$style->length_in_pt($align, $style->font_size) - (float)$frameBox['h'];
                    }
                } else {
                    $parent = $frame->get_parent();
                    if ($parent instanceof TableCellFrameDecorator) {
                        $align = "baseline";
                    } else {
                        $align = $parent->get_style()->vertical_align;
                    }
                    if (in_array($align, Style::$vertical_align_keywords) === true) {
                        switch ($align) {
                            case "middle":
                                $y_offset = ($height * 0.8 - $baseline) / 2;
                                break;

                            case "sub":
                                $y_offset = $height * 0.8 - $baseline * 0.5;
                                break;

                            case "super":
                                $y_offset = $height * 0.8 - $baseline * 1.4;
                                break;

                            case "text-top":
                            case "top": // Not strictly accurate, but good enough for now
                                break;

                            case "text-bottom":
                            case "bottom":
                                $y_offset = $height * 0.8 - $baseline;
                                break;

                            case "baseline":
                            default:
                                $y_offset = $height * 0.8 - $baseline;
                                break;
                        }
                    } else {
                        $y_offset = $height * 0.8 - $baseline - (float)$style->length_in_pt($align, $style->font_size);
                    }
                }

                if ($y_offset !== 0) {
                    $frame->move(0, $y_offset);
                }
            }
        }
    }

    /**
     * @param Frame $child
     */
    function process_clear(Frame $child)
    {
        $child_style = $child->get_style();
        $root = $this->_frame->get_root();

        // Handle "clear"
        if ($child_style->clear !== "none") {
            //TODO: this is a WIP for handling clear/float frames that are in between inline frames
            if ($child->get_prev_sibling() !== null) {
                $this->_frame->add_line();
            }
            if ($child_style->float !== "none" && $child->get_next_sibling()) {
                $this->_frame->set_current_line_number($this->_frame->get_current_line_number() - 1);
            }

            $lowest_y = $root->get_lowest_float_offset($child);

            // If a float is still applying, we handle it
            if ($lowest_y) {
                if ($child->is_in_flow()) {
                    $line_box = $this->_frame->get_current_line_box();
                    $line_box->y = $lowest_y + $child->get_margin_height();
                    $line_box->left = 0;
                    $line_box->right = 0;
                }

                $child->move(0, $lowest_y - $child->get_position("y"));
            }
        }
    }

    /**
     * @param Frame $child
     * @param float $cb_x
     * @param float $cb_w
     */
    function process_float(Frame $child, $cb_x, $cb_w)
    {
        $child_style = $child->get_style();
        $root = $this->_frame->get_root();

        // Handle "float"
        if ($child_style->float !== "none") {
            $root->add_floating_frame($child);

            // Remove next frame's beginning whitespace
            $next = $child->get_next_sibling();
            if ($next && $next instanceof TextFrameDecorator) {
                $next->set_text(ltrim($next->get_text()));
            }

            $line_box = $this->_frame->get_current_line_box();
            list($old_x, $old_y) = $child->get_position();

            $float_x = $cb_x;
            $float_y = $old_y;
            $float_w = $child->get_margin_width();

            if ($child_style->clear === "none") {
                switch ($child_style->float) {
                    case "left":
                        $float_x += $line_box->left;
                        break;
                    case "right":
                        $float_x += ($cb_w - $line_box->right - $float_w);
                        break;
                }
            } else {
                if ($child_style->float === "right") {
                    $float_x += ($cb_w - $float_w);
                }
            }

            if ($cb_w < $float_x + $float_w - $old_x) {
                // TODO handle when floating elements don't fit
            }

            $line_box->get_float_offsets();

            if ($child->_float_next_line) {
                $float_y += $line_box->h;
            }

            $child->set_position($float_x, $float_y);
            $child->move($float_x - $old_x, $float_y - $old_y, true);
        }
    }

    /**
     * @param BlockFrameDecorator $block
     * @return mixed|void
     */
    function reflow(BlockFrameDecorator $block = null)
    {

        // Check if a page break is forced
        $page = $this->_frame->get_root();
        $page->check_forced_page_break($this->_frame);

        // Bail if the page is full
        if ($page->is_full()) {
            return;
        }

        // Generated content
        $this->_set_content();

        // Collapse margins if required
        $this->_collapse_margins();

        $style = $this->_frame->get_style();
        $cb = $this->_frame->get_containing_block();

        if ($style->position === "fixed") {
            $cb = $this->_frame->get_root()->get_containing_block();
        }

        // Determine the constraints imposed by this frame: calculate the width
        // of the content area:
        list($w, $left_margin, $right_margin, $left, $right) = $this->_calculate_restricted_width();

        // Store the calculated properties
        $style->width = $w;
        $style->margin_left = $left_margin;
        $style->margin_right = $right_margin;
        $style->left = $left;
        $style->right = $right;

        // Update the position
        $this->_frame->position();
        list($x, $y) = $this->_frame->get_position();

        // Adjust the first line based on the text-indent property
        $indent = (float)$style->length_in_pt($style->text_indent, $cb["w"]);
        $this->_frame->increase_line_width($indent);

        // Determine the content edge
        $top = (float)$style->length_in_pt(array($style->margin_top,
            $style->padding_top,
            $style->border_top_width), $cb["h"]);

        $bottom = (float)$style->length_in_pt(array($style->border_bottom_width,
            $style->margin_bottom,
            $style->padding_bottom), $cb["h"]);

        $cb_x = $x + (float)$left_margin + (float)$style->length_in_pt(array($style->border_left_width,
                $style->padding_left), $cb["w"]);

        $cb_y = $y + $top;

        $cb_h = ($cb["h"] + $cb["y"]) - $bottom - $cb_y;

        // Set the y position of the first line in this block
        $line_box = $this->_frame->get_current_line_box();
        $line_box->y = $cb_y;
        $line_box->get_float_offsets();

        // Set the containing blocks and reflow each child
        foreach ($this->_frame->get_children() as $child) {

            // Bail out if the page is full
            if ($page->is_full()) {
                break;
            }

            $child->set_containing_block($cb_x, $cb_y, $w, $cb_h);

            $this->process_clear($child);

            $child->reflow($this->_frame);

            // Don't add the child to the line if a page break has occurred
            if ($page->check_page_break($child)) {
                break;
            }

            $this->process_float($child, $cb_x, $w);
        }

        // Determine our height
        list($height, $margin_top, $margin_bottom, $top, $bottom) = $this->_calculate_restricted_height();
        $style->height = $height;
        $style->margin_top = $margin_top;
        $style->margin_bottom = $margin_bottom;
        $style->top = $top;
        $style->bottom = $bottom;

        $orig_style = $this->_frame->get_original_style();

        $needs_reposition = ($style->position === "absolute" && ($style->right !== "auto" || $style->bottom !== "auto"));

        // Absolute positioning measurement
        if ($needs_reposition) {
            if ($orig_style->width === "auto" && ($orig_style->left === "auto" || $orig_style->right === "auto")) {
                $width = 0;
                foreach ($this->_frame->get_line_boxes() as $line) {
                    $width = max($line->w, $width);
                }
                $style->width = $width;
            }

            $style->left = $orig_style->left;
            $style->right = $orig_style->right;
        }

        // Calculate inline-block / float auto-widths
        if (($style->display === "inline-block" || $style->float !== 'none') && $orig_style->width === 'auto') {
            $width = 0;

            foreach ($this->_frame->get_line_boxes() as $line) {
                $line->recalculate_width();

                $width = max($line->w, $width);
            }

            if ($width === 0) {
                foreach ($this->_frame->get_children() as $child) {
                    $width += $child->calculate_auto_width();
                }
            }

            $style->width = $width;
        }

        $this->_text_align();
        $this->vertical_align();

        // Absolute positioning
        if ($needs_reposition) {
            list($x, $y) = $this->_frame->get_position();
            $this->_frame->position();
            list($new_x, $new_y) = $this->_frame->get_position();
            $this->_frame->move($new_x - $x, $new_y - $y, true);
        }

        if ($block && $this->_frame->is_in_flow()) {
            $block->add_frame_to_line($this->_frame);

            // May be inline-block
            if ($style->display === "block") {
                $block->add_line();
            }
        }
    }

    /**
     * Determine current frame width based on contents
     *
     * @return float
     */
    public function calculate_auto_width()
    {
        $width = 0;

        foreach ($this->_frame->get_line_boxes() as $line) {
            $line_width = 0;

            foreach ($line->get_frames() as $frame) {
                if ($frame->get_original_style()->width == 'auto') {
                    $line_width += $frame->calculate_auto_width();
                } else {
                    $line_width += $frame->get_margin_width();
                }
            }

            $width = max($line_width, $width);
        }

        $this->_frame->get_style()->width = $width;

        return $this->_frame->get_margin_width();
    }
}
