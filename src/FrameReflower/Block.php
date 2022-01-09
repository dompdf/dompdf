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
use Dompdf\Helpers;

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
        $absolute = $frame->is_absolute();

        $cb = $frame->get_containing_block();
        $w = $cb["w"];

        $rm = $style->length_in_pt($style->margin_right, $w);
        $lm = $style->length_in_pt($style->margin_left, $w);

        $left = $style->length_in_pt($style->left, $w);
        $right = $style->length_in_pt($style->right, $w);

        // Handle 'auto' values
        $dims = [$style->border_left_width,
            $style->border_right_width,
            $style->padding_left,
            $style->padding_right,
            $width !== "auto" ? $width : 0,
            $rm !== "auto" ? $rm : 0,
            $lm !== "auto" ? $lm : 0];

        // absolutely positioned boxes take the 'left' and 'right' properties into account
        if ($absolute) {
            $dims[] = $left !== "auto" ? $left : 0;
            $dims[] = $right !== "auto" ? $right : 0;
        }

        $sum = (float)$style->length_in_pt($dims, $w);

        // Compare to the containing block
        $diff = $w - $sum;

        if ($absolute) {
            // Absolutely positioned
            // http://www.w3.org/TR/CSS21/visudet.html#abs-non-replaced-width

            if ($width === "auto" || $left === "auto" || $right === "auto") {
                // "all of the three are 'auto'" logic + otherwise case
                if ($lm === "auto") {
                    $lm = 0;
                }
                if ($rm === "auto") {
                    $rm = 0;
                }

                $block_parent = $frame->find_block_parent();
                $parent_content = $block_parent->get_content_box();
                $line = $block_parent->get_current_line_box();

                // TODO: This is the in-flow inline position. Use the in-flow
                // block position if the original display type is block-level
                $inflow_x = $parent_content["x"] - $cb["x"] + $line->left + $line->w;

                if ($width === "auto" && $left === "auto" && $right === "auto") {
                    // rule 3, per instruction preceding rule set
                    // shrink-to-fit width
                    $left = $inflow_x;
                    [$min, $max] = $this->get_min_max_child_width();
                    $width = min(max($min, $diff - $left), $max);
                    $right = $diff - $left - $width;
                } elseif ($width === "auto" && $left === "auto") {
                    // rule 1
                    // shrink-to-fit width
                    [$min, $max] = $this->get_min_max_child_width();
                    $width = min(max($min, $diff), $max);
                    $left = $diff - $width;
                } elseif ($width === "auto" && $right === "auto") {
                    // rule 3
                    // shrink-to-fit width
                    [$min, $max] = $this->get_min_max_child_width();
                    $width = min(max($min, $diff), $max);
                    $right = $diff - $width;
                } elseif ($left === "auto" && $right === "auto") {
                    // rule 2
                    $left = $inflow_x;
                    $right = $diff - $left;
                } elseif ($left === "auto") {
                    // rule 4
                    $left = $diff;
                } elseif ($width === "auto") {
                    // rule 5
                    $width = max($diff, 0);
                } else {
                    // $right === "auto"
                    // rule 6
                    $right = $diff;
                }
            } else {
                // "none of the three are 'auto'" logic described in paragraph preceding the rules
                if ($diff >= 0) {
                    if ($lm === "auto" && $rm === "auto") {
                        $lm = $rm = $diff / 2;
                    } elseif ($lm === "auto") {
                        $lm = $diff;
                    } elseif ($rm === "auto") {
                        $rm = $diff;
                    }
                } else {
                    // over-constrained, solve for right
                    $right = $right + $diff;

                    if ($lm === "auto") {
                        $lm = 0;
                    }
                    if ($rm === "auto") {
                        $rm = 0;
                    }
                }
            }
        } elseif ($style->float !== "none" || $style->display === "inline-block") {
            // Shrink-to-fit width for float and inline block
            // https://www.w3.org/TR/CSS21/visudet.html#float-width
            // https://www.w3.org/TR/CSS21/visudet.html#inlineblock-width

            if ($width === "auto") {
                [$min, $max] = $this->get_min_max_child_width();
                $width = min(max($min, $diff), $max);
            }
            if ($lm === "auto") {
                $lm = 0;
            }
            if ($rm === "auto") {
                $rm = 0;
            }
        } else {
            // Block-level, normal flow
            // https://www.w3.org/TR/CSS21/visudet.html#blockwidth

            if ($diff >= 0) {
                // Find auto properties and get them to take up the slack
                if ($width === "auto") {
                    $width = $diff;

                    if ($lm === "auto") {
                        $lm = 0;
                    }
                    if ($rm === "auto") {
                        $rm = 0;
                    }
                } elseif ($lm === "auto" && $rm === "auto") {
                    $lm = $rm = $diff / 2;
                } elseif ($lm === "auto") {
                    $lm = $diff;
                } elseif ($rm === "auto") {
                    $rm = $diff;
                }
            } else {
                // We are over constrained--set margin-right to the difference
                $rm = (float) $rm + $diff;

                if ($width === "auto") {
                    $width = 0;
                }
                if ($lm === "auto") {
                    $lm = 0;
                }
            }
        }

        return [
            "width" => $width,
            "margin_left" => $lm,
            "margin_right" => $rm,
            "left" => $left,
            "right" => $right,
        ];
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

        if (!isset($cb["w"])) {
            throw new Exception("Box property calculation requires containing block width");
        }

        $width = $style->length_in_pt($style->width, $cb["w"]);

        $values = $this->_calculate_width($width);
        $margin_left = $values["margin_left"];
        $margin_right = $values["margin_right"];
        $width = $values["width"];
        $left = $values["left"];
        $right = $values["right"];

        // Handle min/max width
        // https://www.w3.org/TR/CSS21/visudet.html#min-max-widths
        $min_width = $this->resolve_min_width($cb["w"]);
        $max_width = $this->resolve_max_width($cb["w"]);

        if ($width > $max_width) {
            $values = $this->_calculate_width($max_width);
            $margin_left = $values["margin_left"];
            $margin_right = $values["margin_right"];
            $width = $values["width"];
            $left = $values["left"];
            $right = $values["right"];
        }

        if ($width < $min_width) {
            $values = $this->_calculate_width($min_width);
            $margin_left = $values["margin_left"];
            $margin_right = $values["margin_right"];
            $width = $values["width"];
            $left = $values["left"];
            $right = $values["right"];
        }

        return [$width, $margin_left, $margin_right, $left, $right];
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
        $margin_top = $style->length_in_pt($style->margin_top, $cb["w"]);
        $margin_bottom = $style->length_in_pt($style->margin_bottom, $cb["w"]);

        $top = $style->length_in_pt($style->top, $cb["h"]);
        $bottom = $style->length_in_pt($style->bottom, $cb["h"]);

        if ($frame->is_absolute()) {
            // Absolutely positioned
            // http://www.w3.org/TR/CSS21/visudet.html#abs-non-replaced-height

            $h_dims = [
                $top !== "auto" ? $top : 0,
                $height !== "auto" ? $height : 0,
                $bottom !== "auto" ? $bottom : 0
            ];
            $w_dims = [
                $style->margin_top !== "auto" ? $style->margin_top : 0,
                $style->padding_top,
                $style->border_top_width,
                $style->border_bottom_width,
                $style->padding_bottom,
                $style->margin_bottom !== "auto" ? $style->margin_bottom : 0
            ];

            $sum = (float)$style->length_in_pt($h_dims, $cb["h"])
                + (float)$style->length_in_pt($w_dims, $cb["w"]);

            $diff = $cb["h"] - $sum;

            if ($height === "auto" || $top === "auto" || $bottom === "auto") {
                // "all of the three are 'auto'" logic + otherwise case
                if ($margin_top === "auto") {
                    $margin_top = 0;
                }
                if ($margin_bottom === "auto") {
                    $margin_bottom = 0;
                }

                $block_parent = $frame->find_block_parent();
                $current_line = $block_parent->get_current_line_box();

                // TODO: This is the in-flow inline position. Use the in-flow
                // block position if the original display type is block-level
                $inflow_y = $current_line->y - $cb["y"];

                if ($height === "auto" && $top === "auto" && $bottom === "auto") {
                    // rule 3, per instruction preceding rule set
                    $top = $inflow_y;
                    $height = $content_height;
                    $bottom = $diff - $top - $height;
                } elseif ($height === "auto" && $top === "auto") {
                    // rule 1
                    $height = $content_height;
                    $top = $diff - $height;
                } elseif ($height === "auto" && $bottom === "auto") {
                    // rule 3
                    $height = $content_height;
                    $bottom = $diff - $height;
                } elseif ($top === "auto" && $bottom === "auto") {
                    // rule 2
                    $top = $inflow_y;
                    $bottom = $diff - $top;
                } elseif ($top === "auto") {
                    // rule 4
                    $top = $diff;
                } elseif ($height === "auto") {
                    // rule 5
                    $height = max($diff, 0);
                } else {
                    // $bottom === "auto"
                    // rule 6
                    $bottom = $diff;
                }
            } else {
                // "none of the three are 'auto'" logic described in paragraph preceding the rules
                if ($diff >= 0) {
                    if ($margin_top === "auto" && $margin_bottom === "auto") {
                        $margin_top = $margin_bottom = $diff / 2;
                    } elseif ($margin_top === "auto") {
                        $margin_top = $diff;
                    } elseif ($margin_bottom === "auto") {
                        $margin_bottom = $diff;
                    }
                } else {
                    // over-constrained, solve for bottom
                    $bottom = $bottom + $diff;

                    if ($margin_top === "auto") {
                        $margin_top = 0;
                    }
                    if ($margin_bottom === "auto") {
                        $margin_bottom = 0;
                    }
                }
            }
        } else {
            // https://www.w3.org/TR/CSS21/visudet.html#normal-block
            // https://www.w3.org/TR/CSS21/visudet.html#block-root-margin

            if ($height === "auto") {
                $height = $content_height;
            }
            if ($margin_top === "auto") {
                $margin_top = 0;
            }
            if ($margin_bottom === "auto") {
                $margin_bottom = 0;
            }

            // Handle min/max height
            // https://www.w3.org/TR/CSS21/visudet.html#min-max-heights
            $min_height = $this->resolve_min_height($cb["h"]);
            $max_height = $this->resolve_max_height($cb["h"]);
            $height = Helpers::clamp($height, $min_height, $max_height);
        }

        // TODO: Need to also take min/max height into account for absolute
        // positioning, using similar logic to the `_calculate_width`/
        // `calculate_restricted_width` split above. The non-absolute case
        // can simply clamp height within min/max, as margins and offsets are
        // not affected

        return [$height, $margin_top, $margin_bottom, $top, $bottom];
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
        $text_indent = (float)$style->length_in_pt($style->text_indent, $w);

        switch ($style->text_align) {
            default:
            case "left":
                foreach ($this->_frame->get_line_boxes() as $line) {
                    if (!$line->inline) {
                        continue;
                    }

                    $line->trim_trailing_ws();

                    if ($line->left) {
                        foreach ($line->frames_to_align() as $frame) {
                            $frame->move($line->left, 0);
                        }
                    }
                }
                break;

            case "right":
                foreach ($this->_frame->get_line_boxes() as $i => $line) {
                    if (!$line->inline) {
                        continue;
                    }

                    $line->trim_trailing_ws();

                    $indent = $i === 0 ? $text_indent : 0;
                    $dx = $width - $line->w - $line->right - $indent;

                    foreach ($line->frames_to_align() as $frame) {
                        $frame->move($dx, 0);
                    }
                }
                break;

            case "justify":
                // We justify all lines except the last one, unless the frame
                // has been split, in which case the actual last line is part of
                // the split-off frame
                $lines = $this->_frame->get_line_boxes();
                $last_line_index = $this->_frame->is_split ? null : count($lines) - 1;

                foreach ($lines as $i => $line) {
                    if (!$line->inline) {
                        continue;
                    }

                    $line->trim_trailing_ws();

                    if ($line->left) {
                        foreach ($line->frames_to_align() as $frame) {
                            $frame->move($line->left, 0);
                        }
                    }

                    if ($line->br || $i === $last_line_index) {
                        continue;
                    }

                    $frames = $line->get_frames();
                    $other_frame_count = 0;

                    foreach ($frames as $frame) {
                        if (!($frame instanceof TextFrameDecorator)) {
                            $other_frame_count++;
                        }
                    }

                    $word_count = $line->wc + $other_frame_count;

                    // Set the spacing for each child
                    if ($word_count > 1) {
                        $indent = $i === 0 ? $text_indent : 0;
                        $spacing = ($width - $line->get_width() - $indent) / ($word_count - 1);
                    } else {
                        $spacing = 0;
                    }

                    $dx = 0;
                    foreach ($frames as $frame) {
                        if ($frame instanceof TextFrameDecorator) {
                            $text = $frame->get_text();
                            $spaces = mb_substr_count($text, " ");

                            $frame->move($dx, 0);
                            $frame->set_text_spacing($spacing);

                            $dx += $spaces * $spacing;
                        } else {
                            $frame->move($dx, 0);
                        }
                    }

                    // The line (should) now occupy the entire width
                    $line->w = $width;
                }
                break;

            case "center":
            case "centre":
                foreach ($this->_frame->get_line_boxes() as $i => $line) {
                    if (!$line->inline) {
                        continue;
                    }

                    $line->trim_trailing_ws();

                    $indent = $i === 0 ? $text_indent : 0;
                    $dx = ($width + $line->left - $line->w - $line->right - $indent) / 2;

                    foreach ($line->frames_to_align() as $frame) {
                        $frame->move($dx, 0);
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
        $fontMetrics = $this->get_dompdf()->getFontMetrics();

        foreach ($this->_frame->get_line_boxes() as $line) {
            $height = $line->h;

            // Move all markers to the top of the line box
            foreach ($line->get_list_markers() as $marker) {
                $x = $marker->get_position("x");
                $marker->set_position($x, $line->y);
            }

            foreach ($line->frames_to_align() as $frame) {
                $style = $frame->get_style();
                $isInlineBlock = $style->display !== "inline"
                    && $style->display !== "-dompdf-list-bullet";

                $baseline = $fontMetrics->getFontBaseline($style->font_family, $style->font_size);
                $y_offset = 0;

                //FIXME: The 0.8 ratio applied to the height is arbitrary (used to accommodate descenders?)
                if ($isInlineBlock) {
                    // Workaround: Skip vertical alignment if the frame is the
                    // only one one the line, excluding empty text frames, which
                    // may be the result of trailing white space
                    // FIXME: This special case should be removed once vertical
                    // alignment is properly fixed
                    $skip = true;

                    foreach ($line->get_frames() as $other) {
                        if ($other !== $frame
                            && !($other->is_text_node() && $other->get_node()->nodeValue === "")
                         ) {
                            $skip = false;
                            break;
                        }
                    }

                    if ($skip) {
                        continue;
                    }

                    $marginHeight = $frame->get_margin_height();
                    $imageHeightDiff = $height * 0.8 - $marginHeight;

                    $align = $frame->get_style()->vertical_align;
                    if (in_array($align, Style::VERTICAL_ALIGN_KEYWORDS, true)) {
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
                        $y_offset = $baseline - (float)$style->length_in_pt($align, $style->font_size) - $marginHeight;
                    }
                } else {
                    $parent = $frame->get_parent();
                    if ($parent instanceof TableCellFrameDecorator) {
                        $align = "baseline";
                    } else {
                        $align = $parent->get_style()->vertical_align;
                    }
                    if (in_array($align, Style::VERTICAL_ALIGN_KEYWORDS, true)) {
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

        $this->determine_absolute_containing_block();

        // Counters and generated content
        $this->_set_content();

        // Inherit any dangling list markers
        if ($block && $this->_frame->is_in_flow()) {
            $this->_frame->inherit_dangling_markers($block);
        }

        // Collapse margins if required
        $this->_collapse_margins();

        $style = $this->_frame->get_style();
        $cb = $this->_frame->get_containing_block();

        // Determine the constraints imposed by this frame: calculate the width
        // of the content area:
        [$width, $margin_left, $margin_right, $left, $right] = $this->_calculate_restricted_width();

        // Store the calculated properties
        $style->set_used("width", $width);
        $style->set_used("margin_left", $margin_left);
        $style->set_used("margin_right", $margin_right);
        $style->set_used("left", $left);
        $style->set_used("right", $right);

        $margin_top = $style->length_in_pt($style->margin_top, $cb["w"]);
        $margin_bottom = $style->length_in_pt($style->margin_bottom, $cb["w"]);

        $auto_top = $style->top === "auto";
        $auto_margin_top = $margin_top === "auto";

        // Update the position
        $this->_frame->position();
        [$x, $y] = $this->_frame->get_position();

        // Adjust the first line based on the text-indent property
        $indent = (float)$style->length_in_pt($style->text_indent, $cb["w"]);
        $this->_frame->increase_line_width($indent);

        // Determine the content edge
        $top = (float)$style->length_in_pt([
            $margin_top !== "auto" ? $margin_top : 0,
            $style->border_top_width,
            $style->padding_top
        ], $cb["w"]);
        $bottom = (float)$style->length_in_pt([
            $margin_bottom !== "auto" ? $margin_bottom : 0,
            $style->border_bottom_width,
            $style->padding_bottom
        ], $cb["w"]);

        $cb_x = $x + (float)$margin_left + (float)$style->length_in_pt([$style->border_left_width,
                $style->padding_left], $cb["w"]);

        $cb_y = $y + $top;

        $height = $style->length_in_pt($style->height, $cb["h"]);
        if ($height === "auto") {
            $height = ($cb["h"] + $cb["y"]) - $bottom - $cb_y;
        }

        // Set the y position of the first line in this block
        $line_box = $this->_frame->get_current_line_box();
        $line_box->y = $cb_y;
        $line_box->get_float_offsets();

        // Set the containing blocks and reflow each child
        foreach ($this->_frame->get_children() as $child) {
            $child->set_containing_block($cb_x, $cb_y, $width, $height);
            $this->process_clear($child);
            $child->reflow($this->_frame);

            // Check for a page break before the child
            $page->check_page_break($child);

            // Don't add the child to the line if a page break has occurred
            // before it (possibly via a descendant), in which case it has been
            // reset, including its position
            if ($page->is_full() && $child->get_position("x") === null) {
                break;
            }

            $this->process_float($child, $cb_x, $width);
        }

        // Stop reflow if a page break has occurred before the frame, in which
        // case it has been reset, including its position
        if ($page->is_full() && $this->_frame->get_position("x") === null) {
            return;
        }

        // Determine our height
        [$height, $margin_top, $margin_bottom, $top, $bottom] = $this->_calculate_restricted_height();

        $style->set_used("height", $height);
        $style->set_used("margin_top", $margin_top);
        $style->set_used("margin_bottom", $margin_bottom);
        $style->set_used("top", $top);
        $style->set_used("bottom", $bottom);

        if ($this->_frame->is_absolute()) {
            if ($auto_top) {
                $this->_frame->move(0, $top);
            }
            if ($auto_margin_top) {
                $this->_frame->move(0, $margin_top, true);
            }
        }

        $this->_text_align();
        $this->vertical_align();

        // Handle relative positioning
        foreach ($this->_frame->get_children() as $child) {
            $this->position_relative($child);
        }

        if ($block && $this->_frame->is_in_flow()) {
            $block->add_frame_to_line($this->_frame);

            if ($this->_frame->is_block_level()) {
                $block->add_line();
            }
        }
    }

    public function get_min_max_content_width(): array
    {
        // TODO: While the containing block is not set yet on the frame, it can
        // already be determined in some cases due to fixed dimensions on the
        // ancestor forming the containing block. In such cases, percentage
        // values could be resolved here
        $style = $this->_frame->get_style();
        $width = $style->width;
        $fixed_width = $width !== "auto" && !Helpers::is_percent($width);

        // If the frame has a specified width, then we don't need to check
        // its children
        if ($fixed_width) {
            $min = (float) $style->length_in_pt($width, 0);
            $max = $min;
        } else {
            [$min, $max] = $this->get_min_max_child_width();
        }

        // Handle min/max width style properties
        $min_width = $this->resolve_min_width(null);
        $max_width = $this->resolve_max_width(null);
        $min = Helpers::clamp($min, $min_width, $max_width);
        $max = Helpers::clamp($max, $min_width, $max_width);

        return [$min, $max];
    }
}
