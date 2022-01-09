<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Inline as InlineFrameDecorator;
use Dompdf\FrameDecorator\Text as TextFrameDecorator;
use Dompdf\FontMetrics;
use Dompdf\Helpers;

/**
 * Reflows text frames.
 *
 * @package dompdf
 */
class Text extends AbstractFrameReflower
{
    /**
     * PHP string representation of HTML entity <shy>
     */
    const SOFT_HYPHEN = "\xC2\xAD";

    /**
     * The regex splits on everything that's a separator (^\S double negative),
     * excluding the following non-breaking space characters:
     * * nbsp (\xA0)
     * * narrow nbsp (\x{202F})
     * * figure space (\x{2007})
     */
    public static $_whitespace_pattern = '/([^\S\xA0\x{202F}\x{2007}]+)/u';

    /**
     * The regex splits on everything that's a separator (^\S double negative)
     * plus dashes, excluding the following non-breaking space characters:
     * * nbsp (\xA0)
     * * narrow nbsp (\x{202F})
     * * figure space (\x{2007})
     */
    public static $_wordbreak_pattern = '/([^\S\xA0\x{202F}\x{2007}\n]+|\R|\-+|\xAD+)/u';

    /**
     * @var TextFrameDecorator
     */
    protected $_frame;

    /**
     * Saves trailing whitespace trimmed after a line break, so it can be
     * restored when needed.
     *
     * @var string|null
     */
    protected $trailingWs = null;

    /**
     * @var FontMetrics
     */
    private $fontMetrics;

    /**
     * @param TextFrameDecorator $frame
     * @param FontMetrics $fontMetrics
     */
    public function __construct(TextFrameDecorator $frame, FontMetrics $fontMetrics)
    {
        parent::__construct($frame);
        $this->setFontMetrics($fontMetrics);
    }

    /**
     * Apply text transform and white-space collapse according to style.
     *
     * * http://www.w3.org/TR/CSS21/text.html#propdef-text-transform
     * * http://www.w3.org/TR/CSS21/text.html#propdef-white-space
     *
     * @param string $text
     * @return string
     */
    protected function pre_process_text(string $text): string
    {
        $style = $this->_frame->get_style();

        // Handle text transform
        switch ($style->text_transform) {
            case "capitalize":
                $text = Helpers::mb_ucwords($text);
                break;
            case "uppercase":
                $text = mb_convert_case($text, MB_CASE_UPPER);
                break;
            case "lowercase":
                $text = mb_convert_case($text, MB_CASE_LOWER);
                break;
            default:
                break;
        }

        // Handle white-space collapse
        switch ($style->white_space) {
            default:
            case "normal":
            case "nowrap":
                $text = preg_replace(self::$_whitespace_pattern, " ", $text) ?? "";
                break;

            case "pre-line":
                // Collapse white space except for line breaks
                $text = preg_replace('/([^\S\xA0\x{202F}\x{2007}\n]+)/u', " ", $text) ?? "";
                break;

            case "pre":
            case "pre-wrap":
                break;

        }

        return $text;
    }

    /**
     * @param string $text
     * @param BlockFrameDecorator $block
     * @return bool|int
     */
    protected function line_break(string $text, BlockFrameDecorator $block)
    {
        $fontMetrics = $this->getFontMetrics();
        $frame = $this->_frame;
        $style = $frame->get_style();
        $font = $style->font_family;
        $size = $style->font_size;
        $word_spacing = $style->word_spacing;
        $letter_spacing = $style->letter_spacing;

        // Determine the available width
        $current_line = $block->get_current_line_box();
        $line_width = $frame->get_containing_block("w");
        $current_line_width = $current_line->left + $current_line->w + $current_line->right;
        $available_width = $line_width - $current_line_width;

        // Determine the frame width including margin, padding & border
        $visible_text = preg_replace('/\xAD/u', "", $text);
        $text_width = $fontMetrics->getTextWidth($visible_text, $font, $size, $word_spacing, $letter_spacing);
        $mbp_width = (float) $style->length_in_pt([
            $style->margin_left,
            $style->border_left_width,
            $style->padding_left,
            $style->padding_right,
            $style->border_right_width,
            $style->margin_right
        ], $line_width);
        $frame_width = $text_width + $mbp_width;

        if (Helpers::lengthLessOrEqual($frame_width, $available_width)) {
            return false;
        }

        // Split the text into words
        $words = preg_split(self::$_wordbreak_pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $wc = count($words);

        // Determine the split point
        $width = 0;
        $str = "";

        $space_width = $fontMetrics->getTextWidth(" ", $font, $size, $word_spacing, $letter_spacing);
        $shy_width = $fontMetrics->getTextWidth(self::SOFT_HYPHEN, $font, $size);

        // @todo support <wbr>
        for ($i = 0; $i < $wc; $i += 2) {
            // Allow trailing white space to overflow. White space is always
            // collapsed to the standard space character currently, so only
            // handle that for now
            $sep = $words[$i + 1] ?? "";
            $word = $sep === " " ? $words[$i] : $words[$i] . $sep;
            $word_width = $fontMetrics->getTextWidth($word, $font, $size, $word_spacing, $letter_spacing);
            $used_width = $width + $word_width + $mbp_width;

            if (Helpers::lengthGreater($used_width, $available_width)) {
                // If the previous split happened by soft hyphen, we have to
                // append its width again because the last hyphen of a line
                // won't be removed
                if (isset($words[$i - 1]) && self::SOFT_HYPHEN === $words[$i - 1]) {
                    $width += $shy_width;
                }
                break;
            }

            // If the word is splitted by soft hyphen, but no line break is needed
            // we have to reduce the width. But the str is not modified, otherwise
            // the wrong offset is calculated at the end of this method.
            if ($sep === self::SOFT_HYPHEN) {
                $width += $word_width - $shy_width;
                $str .= $word;
            } elseif ($sep === " ") {
                $width += $word_width + $space_width;
                $str .= $word . $sep;
            } else {
                $width += $word_width;
                $str .= $word;
            }
        }

        // The first word has overflowed. Force it onto the line, or as many
        // characters as fit if breaking words is allowed
        if ($current_line_width == 0 && $width == 0) {
            if ($sep === " ") {
                $word .= $sep;
            }

            // https://www.w3.org/TR/css-text-3/#overflow-wrap-property
            $wrap = $style->overflow_wrap;
            $break_word = $wrap === "anywhere" || $wrap === "break-word";

            if ($break_word) {
                $s = "";

                for ($j = 0; $j < mb_strlen($word); $j++) {
                    $c = mb_substr($word, $j, 1);
                    $w = $fontMetrics->getTextWidth($s . $c, $font, $size, $word_spacing, $letter_spacing);

                    if (Helpers::lengthGreater($w, $available_width)) {
                        break;
                    }

                    $s .= $c;
                }

                // Always force the first character onto the line
                $str = $j === 0 ? $s . $c : $s;
            } else {
                $str = $word;
            }
        }

        $offset = mb_strlen($str);
        return $offset;
    }

    /**
     * @param string $text
     * @return bool|int
     */
    protected function newline_break(string $text)
    {
        if (($i = mb_strpos($text, "\n")) === false) {
            return false;
        }

        return $i + 1;
    }

    /**
     * @param BlockFrameDecorator $block
     * @return bool|null Whether to add a new line at the end. `null` if reflow
     *         should be stopped.
     */
    protected function layout_line(BlockFrameDecorator $block): ?bool
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $current_line = $block->get_current_line_box();
        $text = $frame->get_text();

        // Trim leading white space if this is the first text on the line
        if ($current_line->w === 0.0 && !$frame->is_pre()) {
            $text = ltrim($text, " ");
        }

        // Exclude wrapped white space. This handles white space between block
        // elements in case white space is collapsed
        if ($text === "") {
            $frame->set_text("");
            $style->set_used("width", 0.0);
            return null;
        }

        // Determine the next line break
        // http://www.w3.org/TR/CSS21/text.html#propdef-white-space
        switch ($style->white_space) {
            default:
            case "normal":
                $split = $this->line_break($text, $block);
                $add_line = false;
                break;

            case "nowrap":
                $split = false;
                $add_line = false;
                break;

            case "pre":
                $split = $this->newline_break($text);
                $add_line = $split !== false;
                break;

            case "pre-line":
            case "pre-wrap":
                $hard_split = $this->newline_break($text);
                $first_line = $hard_split !== false
                    ? mb_substr($text, 0, $hard_split)
                    : $text;
                $soft_split = $this->line_break($first_line, $block);

                $split = $soft_split !== false ? $soft_split : $hard_split;
                $add_line = $hard_split !== false;
                break;
        }

        if ($split === 0) {
            // Make sure to move text when floating frames leave no space to
            // place anything onto the line
            // TODO: Would probably be better to move just below the current
            // floating frame instead of trying to place text in line-height
            // increments
            if ($current_line->h === 0.0) {
                // Line height might be 0
                $h = max($frame->get_margin_height(), 1.0);
                $block->maximize_line_height($h, $frame);
            }

            // Break line and repeat layout
            $block->add_line();

            // Find the appropriate inline ancestor to split
            $child = $frame;
            $p = $child->get_parent();
            while ($p instanceof InlineFrameDecorator && !$child->get_prev_sibling()) {
                $child = $p;
                $p = $p->get_parent();
            }

            if ($p instanceof InlineFrameDecorator) {
                // Split parent and stop current reflow. Reflow continues
                // via child-reflow loop of split parent
                $p->split($child);
                return null;
            }

            return $this->layout_line($block);
        }

        // Final split point is determined
        if ($split !== false && $split < mb_strlen($text)) {
            // Split the line
            $frame->set_text($text);
            $frame->split_text($split);
            $add_line = true;

            // Remove inner soft hyphens
            $t = $frame->get_text();
            $shyPosition = mb_strpos($t, self::SOFT_HYPHEN);
            if (false !== $shyPosition && $shyPosition < mb_strlen($t) - 1) {
                $t = str_replace(self::SOFT_HYPHEN, "", mb_substr($t, 0, -1)) . mb_substr($t, -1);
                $frame->set_text($t);
            }
        } else {
            // No split required
            // Remove soft hyphens
            $text = str_replace(self::SOFT_HYPHEN, "", $text);
            $frame->set_text($text);
        }

        // Set our new width
        $frame->recalculate_width();

        return $add_line;
    }

    /**
     * @param BlockFrameDecorator|null $block
     */
    function reflow(BlockFrameDecorator $block = null)
    {
        $frame = $this->_frame;
        $page = $frame->get_root();
        $page->check_forced_page_break($frame);

        if ($page->is_full()) {
            return;
        }

        // Determine the text height
        $style = $frame->get_style();
        $size = $style->font_size;
        $font = $style->font_family;
        $font_height = $this->getFontMetrics()->getFontHeight($font, $size);
        $style->set_used("height", $font_height);

        // Handle text transform and white space
        $text = $this->pre_process_text($frame->get_text());
        $frame->set_text($text);

        $add_line = $this->layout_line($block);

        if ($add_line === null) {
            return;
        }

        $frame->position();

        if ($block) {
            $line = $block->add_frame_to_line($frame);
            $trimmed = trim($frame->get_text());

            // Split the text into words (used to determine spacing between
            // words on justified lines)
            if ($trimmed !== "") {
                $words = preg_split(self::$_whitespace_pattern, $trimmed);
                $line->wc += count($words);
            }

            if ($add_line) {
                $block->add_line();
            }
        }
    }

    /**
     * Trim trailing white space from the frame text.
     */
    public function trim_trailing_ws(): void
    {
        $frame = $this->_frame;
        $text = $frame->get_text();
        $trailing = mb_substr($text, -1);

        // White space is always collapsed to the standard space character
        // currently, so only handle that for now
        if ($trailing === " ") {
            $this->trailingWs = $trailing;
            $frame->set_text(mb_substr($text, 0, -1));
            $frame->recalculate_width();
        }
    }

    public function reset(): void
    {
        parent::reset();

        // Restore trimmed trailing white space, as the frame will go through
        // another reflow and line breaks might be different after a split
        if ($this->trailingWs !== null) {
            $text = $this->_frame->get_text();
            $this->_frame->set_text($text . $this->trailingWs);
            $this->trailingWs = null;
        }
    }

    //........................................................................

    public function get_min_max_width(): array
    {
        $fontMetrics = $this->getFontMetrics();
        $frame = $this->_frame;
        $style = $frame->get_style();
        $text = $frame->get_text();
        $font = $style->font_family;
        $size = $style->font_size;
        $word_spacing = $style->word_spacing;
        $letter_spacing = $style->letter_spacing;

        // Handle text transform and white space
        $text = $this->pre_process_text($frame->get_text());

        if (!$frame->is_pre()) {
            // Determine whether the frame is at the start of its parent block.
            // Trim leading white space in that case
            $child = $frame;
            $p = $frame->get_parent();
            while (!$p->is_block() && !$child->get_prev_sibling()) {
                $child = $p;
                $p = $p->get_parent();
            }

            if (!$child->get_prev_sibling()) {
                $text = ltrim($text, " ");
            }

            // Determine whether the frame is at the end of its parent block.
            // Trim trailing white space in that case
            $child = $frame;
            $p = $frame->get_parent();
            while (!$p->is_block() && !$child->get_next_sibling()) {
                $child = $p;
                $p = $p->get_parent();
            }

            if (!$child->get_next_sibling()) {
                $text = rtrim($text, " ");
            }
        }

        // Strip soft hyphens for max-line-width calculations
        $visible_text = preg_replace('/\xAD/u', "", $text);

        // Determine minimum text width
        switch ($style->white_space) {
            default:
            case "normal":
            case "pre-line":
            case "pre-wrap":
                // The min width is the longest word or, if breaking words is
                // allowed with the `anywhere` keyword, the widest character.
                // For performance reasons, we only check the first character in
                // the latter case.
                // https://www.w3.org/TR/css-text-3/#overflow-wrap-property
                if ($style->overflow_wrap === "anywhere") {
                    $char = mb_substr($visible_text, 0, 1);
                    $min = $fontMetrics->getTextWidth($char, $font, $size, $word_spacing, $letter_spacing);
                } else {
                    // Find the longest word
                    $words = preg_split(self::$_wordbreak_pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
                    $lengths = array_map(function ($chunk) use ($fontMetrics, $font, $size, $word_spacing, $letter_spacing) {
                        // Allow trailing white space to overflow. As in actual
                        // layout above, only handle a single space for now
                        $sep = $chunk[1] ?? "";
                        $word = $sep === " " ? $chunk[0] : $chunk[0] . $sep;
                        return $fontMetrics->getTextWidth($word, $font, $size, $word_spacing, $letter_spacing);
                    }, array_chunk($words, 2));
                    $min = max($lengths);
                }
                break;

            case "pre":
                // Find the longest line
                $lines = array_flip(preg_split("/\R/u", $visible_text));
                array_walk($lines, function(&$chunked_text_width, $chunked_text) use ($fontMetrics, $font, $size, $word_spacing, $letter_spacing) {
                    $chunked_text_width = $fontMetrics->getTextWidth($chunked_text, $font, $size, $word_spacing, $letter_spacing);
                });
                arsort($lines);
                $min = reset($lines);
                break;

            case "nowrap":
                $min = $fontMetrics->getTextWidth($visible_text, $font, $size, $word_spacing, $letter_spacing);
                break;
        }

        // Determine maximum text width
        switch ($style->white_space) {
            default:
            case "normal":
                $max = $fontMetrics->getTextWidth($visible_text, $font, $size, $word_spacing, $letter_spacing);
                break;

            case "pre-line":
            case "pre-wrap":
                // Find the longest line
                $lines = array_flip(preg_split("/\R/u", $visible_text));
                array_walk($lines, function(&$chunked_text_width, $chunked_text) use ($fontMetrics, $font, $size, $word_spacing, $letter_spacing) {
                    $chunked_text_width = $fontMetrics->getTextWidth($chunked_text, $font, $size, $word_spacing, $letter_spacing);
                });
                arsort($lines);
                $max = reset($lines);
                break;

            case "pre":
            case "nowrap":
                $max = $min;
                break;
        }

        // Account for margins, borders, and padding
        $dims = [
            $style->padding_left,
            $style->padding_right,
            $style->border_left_width,
            $style->border_right_width,
            $style->margin_left,
            $style->margin_right
        ];

        // The containing block is not defined yet, treat percentages as 0
        $delta = (float) $style->length_in_pt($dims, 0);
        $min += $delta;
        $max += $delta;

        return [$min, $max, "min" => $min, "max" => $max];
    }

    /**
     * @param FontMetrics $fontMetrics
     * @return $this
     */
    public function setFontMetrics(FontMetrics $fontMetrics)
    {
        $this->fontMetrics = $fontMetrics;
        return $this;
    }

    /**
     * @return FontMetrics
     */
    public function getFontMetrics()
    {
        return $this->fontMetrics;
    }
}
