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
     * excluding nbsp (\xa0).
     * This currently excludes the "narrow nbsp" character.
     */
    public static $_whitespace_pattern = '/([^\S\xA0]+)/u';

    /**
     * The regex splits on everything that's a separator (^\S double negative),
     * excluding nbsp (\xa0), plus dashes.
     * This currently excludes the "narrow nbsp" character.
     */
    public static $_wordbreak_pattern = '/([^\S\xA0]+|\-+|\xAD+)/u';

    /**
     * @var BlockFrameDecorator
     */
    protected $_block_parent; // Nearest block-level ancestor

    /**
     * @var TextFrameDecorator
     */
    protected $_frame;

    /**
     * Saves trailing whitespace trimmed after a line break, so it can be
     * restored when needed.
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
     * @param string $text
     * @return string
     */
    protected function _collapse_white_space(string $text): string
    {
        return preg_replace(self::$_whitespace_pattern, " ", $text) ?? "";
    }

    /**
     * @param string $text
     * @return bool|int
     */
    protected function _line_break($text)
    {
        $style = $this->_frame->get_style();
        $size = $style->font_size;
        $font = $style->font_family;
        $current_line = $this->_block_parent->get_current_line_box();

        // Determine the available width
        $line_width = $this->_frame->get_containing_block("w");
        $current_line_width = $current_line->left + $current_line->w + $current_line->right;

        $available_width = $line_width - $current_line_width;

        // Account for word-spacing
        $word_spacing = (float)$style->length_in_pt($style->word_spacing);
        $char_spacing = (float)$style->length_in_pt($style->letter_spacing);

        // Determine the frame width including margin, padding & border
        $visible_text = preg_replace('/\xAD/u', '', $text);
        $text_width = $this->getFontMetrics()->getTextWidth($visible_text, $font, $size, $word_spacing, $char_spacing);
        $mbp_width =
            (float)$style->length_in_pt([$style->margin_left,
                $style->border_left_width,
                $style->padding_left,
                $style->padding_right,
                $style->border_right_width,
                $style->margin_right], $line_width);

        $frame_width = $text_width + $mbp_width;

// Debugging:
//    Helpers::pre_r("Text: '" . htmlspecialchars($text). "'");
//    Helpers::pre_r("width: " .$frame_width);
//    Helpers::pre_r("textwidth + delta: $text_width + $mbp_width");
//    Helpers::pre_r("font-size: $size");
//    Helpers::pre_r("cb[w]: " .$line_width);
//    Helpers::pre_r("available width: " . $available_width);
//    Helpers::pre_r("current line width: " . $current_line_width);

//     Helpers::pre_r($words);

        if ($frame_width <= $available_width) {
            return false;
        }

        // split the text into words
        $words = preg_split(self::$_wordbreak_pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $wc = count($words);

        // Determine the split point
        $width = 0;
        $str = "";
        reset($words);

        $shy_width = $this->getFontMetrics()->getTextWidth(self::SOFT_HYPHEN, $font, $size);

        // @todo support <wbr>
        for ($i = 0; $i < $wc; $i += 2) {
            $word = $words[$i] . (isset($words[$i + 1]) ? $words[$i + 1] : "");
            $word_width = $this->getFontMetrics()->getTextWidth($word, $font, $size, $word_spacing, $char_spacing);
            if ($width + $word_width + $mbp_width > $available_width) {
                // If the previous split happened by soft hyphen, we have to append its width again
                // because the last hyphen of a line won't be removed.
                if (isset($words[$i - 1]) && self::SOFT_HYPHEN === $words[$i - 1]) {
                    $width += $shy_width;
                }
                break;
            }

            // If the word is splitted by soft hyphen, but no line break is needed
            // we have to reduce the width. But the str is not modified, otherwise
            // the wrong offset is calculated at the end of this method.
            if (isset($words[$i + 1]) && self::SOFT_HYPHEN === $words[$i + 1]) {
                $width += $word_width - $shy_width;
            } else {
                $width += $word_width;
            }
            $str .= $word;
        }

        // https://www.w3.org/TR/css-text-3/#overflow-wrap-property
        $wrap = $style->overflow_wrap;
        $break_word = $wrap === "anywhere" || $wrap === "break-word";

        // The first word has overflowed.   Force it onto the line
        if ($current_line_width == 0 && $width == 0) {
            $s = "";
            $last_width = 0;

            if ($break_word) {
                for ($j = 0; $j < strlen($word); $j++) {
                    $s .= $word[$j];
                    $_width = $this->getFontMetrics()->getTextWidth($s, $font, $size, $word_spacing, $char_spacing);
                    if ($_width > $available_width) {
                        break;
                    }

                    $last_width = $_width;
                }
            }

            if ($break_word && $last_width > 0) {
                //$width += $last_width;
                $str .= substr($s, 0, -1);
            } else {
                //$width += $word_width;
                $str .= $word;
            }
        }

        $offset = mb_strlen($str);

        // More debugging:
        //     var_dump($str);
        //     print_r("Width: ". $width);
        //     print_r("Offset: " . $offset);

        return $offset;
    }

    //........................................................................

    /**
     * @param string $text
     * @return bool|int
     */
    protected function _newline_break($text)
    {
        if (($i = mb_strpos($text, "\n")) === false) {
            return false;
        }

        return $i + 1;
    }

    protected function _layout_line(): bool
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $text = $frame->get_text();
        $size = $style->font_size;
        $font = $style->font_family;

        // Determine the text height
        $style->height = $this->getFontMetrics()->getFontHeight($font, $size);

        $split = false;
        $add_line = false;

        // Handle text transform:
        // http://www.w3.org/TR/CSS21/text.html#propdef-text-transform
        switch (strtolower($style->text_transform)) {
            default:
                break;
            case "capitalize":
                $text = Helpers::mb_ucwords($text);
                break;
            case "uppercase":
                $text = mb_convert_case($text, MB_CASE_UPPER);
                break;
            case "lowercase":
                $text = mb_convert_case($text, MB_CASE_LOWER);
                break;
        }

        // Handle white-space property:
        // http://www.w3.org/TR/CSS21/text.html#propdef-white-space
        switch ($style->white_space) {
            default:
            case "normal":
                $text = $this->_collapse_white_space($text);

                if ($text === "") {
                    break;
                }

                $split = $this->_line_break($text);
                break;

            case "nowrap":
                $text = $this->_collapse_white_space($text);
                break;

            case "pre":
                $split = $this->_newline_break($text);
                $add_line = $split !== false;
                break;

            /** @noinspection PhpMissingBreakStatementInspection */
            case "pre-line":
                // Collapse white-space except for \n
                $text = preg_replace("/[ \t]+/u", " ", $text);

                if ($text === "") {
                    break;
                }
            case "pre-wrap":
                $split = $this->_newline_break($text);

                if (($tmp = $this->_line_break($text)) !== false) {
                    if ($split === false || $tmp < $split) {
                        $split = $tmp;
                    } else {
                        $add_line = true;
                    }
                } elseif ($split !== false) {
                    $add_line = true;
                }

                break;
        }

        $frame->set_text($text);

        if ($split !== false) {
            // Handle edge cases
            if ($split === 0 && !$frame->is_pre() && trim($text) === "") {
                $frame->set_text("");
            } elseif ($split === 0) {
                $prev = $frame->get_prev_sibling();
                $p = $frame->get_parent();

                if ($prev && $p instanceof InlineFrameDecorator) {
                    $p->split($frame);
                }

                // Trim newlines from the beginning of the line
                //$this->_frame->set_text(ltrim($text, "\n\r"));

                $this->_block_parent->maximize_line_height($frame->get_margin_height(), $frame);
                $this->_block_parent->add_line();
                $frame->position();

                // Layout the new line
                $add_line = $this->_layout_line();
            } elseif ($split < mb_strlen($text)) {
                // Split the line if required
                $frame->split_text($split);

                // Remove inner soft hyphens
                $t = $frame->get_text();
                $shyPosition = mb_strpos($t, self::SOFT_HYPHEN);
                if (false !== $shyPosition && $shyPosition < mb_strlen($t) - 1) {
                    $t = str_replace(self::SOFT_HYPHEN, '', mb_substr($t, 0, -1)) . mb_substr($t, -1);
                    $frame->set_text($t);
                }
            }
        } elseif ($text !== "") {
            // Remove empty space from start and end of line, but only where there isn't an inline sibling
            // and the parent node isn't an inline element with siblings
            // FIXME: Include non-breaking spaces?
            $t = $text;
            $parent = $frame->get_parent();
            $is_inline_frame = $parent instanceof InlineFrameDecorator;

            if ((!$is_inline_frame && !$frame->get_next_sibling()) /* ||
            ( $is_inline_frame && !$parent->get_next_sibling())*/
            ) { // fails <b>BOLD <u>UNDERLINED</u></b> becomes <b>BOLD<u>UNDERLINED</u></b>
                $t = rtrim($t);
            }

            if ((!$is_inline_frame && !$frame->get_prev_sibling()) /* ||
            ( $is_inline_frame && !$parent->get_prev_sibling())*/
            ) { //  <span><span>A<span>B</span> C</span></span> fails (the whitespace is removed)
                $t = ltrim($t);
            }

            // Remove soft hyphens
            $t = str_replace(self::SOFT_HYPHEN, '', $t);
            $frame->set_text($t);
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
        $page->check_forced_page_break($this->_frame);

        if ($page->is_full()) {
            return;
        }

        $this->_block_parent = /*isset($block) ? $block : */
        $frame->find_block_parent();

        // Left trim the text if this is the first text on the line and we're
        // collapsing white space
//     if ( $this->_block_parent->get_current_line()->w == 0 &&
//          ($frame->get_style()->white_space !== "pre" ||
//           $frame->get_style()->white_space !== "pre-wrap") ) {
//       $frame->set_text( ltrim( $frame->get_text() ) );
//     }

        $frame->position();

        $add_line = $this->_layout_line();

        if ($block) {
            $block->add_frame_to_line($frame);

            if ($add_line === true) {
                $block->add_line();
            }
        }
    }

    /**
     * Trim trailing whitespace from the frame text.
     */
    public function trim_trailing_ws(): void
    {
        $frame = $this->_frame;
        $text = $frame->get_text();
        $trailing = mb_substr($text, -1);

        if (preg_match(self::$_whitespace_pattern, $trailing)) {
            $this->trailingWs = $trailing;
            $frame->set_text(mb_substr($text, 0, -1));
            $frame->recalculate_width();
        }
    }

    public function reset(): void
    {
        parent::reset();

        // Restore trimmed trailing whitespace, as the frame will go through
        // another reflow and line breaks might be different after a split
        if ($this->trailingWs !== null) {
            $text = $this->_frame->get_text();
            $this->_frame->set_text($text . $this->trailingWs);
            $this->trailingWs = null;
        }
    }

    //........................................................................

    function get_min_max_width(): array
    {
        /*if ( !is_null($this->_min_max_cache)  )
          return $this->_min_max_cache;*/
        $frame = $this->_frame;
        $style = $frame->get_style();
        $this->_block_parent = $frame->find_block_parent();
        $line_width = $frame->get_containing_block("w");
        $fontMetrics = $this->getFontMetrics();

        $str = $text = $frame->get_text();
        $size = $style->font_size;
        $font = $style->font_family;

        $word_spacing = (float)$style->length_in_pt($style->word_spacing);
        $char_spacing = (float)$style->length_in_pt($style->letter_spacing);

        // determine minimum text width based on the whitespace setting
        switch ($style->white_space) {
            default:
            /** @noinspection PhpMissingBreakStatementInspection */
            case "normal":
                $str = $this->_collapse_white_space($str);
            case "pre-wrap":
            case "pre-line":
                // Find the longest word (i.e. minimum length)
                // split the text into words
                $words = array_flip(preg_split(self::$_wordbreak_pattern, $str, -1, PREG_SPLIT_DELIM_CAPTURE));
                array_walk($words, function(&$chunked_text_width, $chunked_text) use ($fontMetrics, $font, $size, $word_spacing, $char_spacing) {
                    $chunked_text_width = $fontMetrics->getTextWidth($chunked_text, $font, $size, $word_spacing, $char_spacing);
                });

                arsort($words);
                $min = reset($words);
                break;

            case "pre":
                $lines = array_flip(preg_split("/\R/u", $str));
                array_walk($lines, function(&$chunked_text_width, $chunked_text) use ($fontMetrics, $font, $size, $word_spacing, $char_spacing) {
                    $chunked_text_width = $fontMetrics->getTextWidth($chunked_text, $font, $size, $word_spacing, $char_spacing);
                });

                arsort($lines);
                $min = reset($lines);
                break;

            case "nowrap":
                $min = $fontMetrics->getTextWidth($this->_collapse_white_space($str), $font, $size, $word_spacing, $char_spacing);
                break;
        }

        // clean up the frame text based on the whitespace setting and use to determine maximum text width
        switch ($style->white_space) {
            default:
            case "normal":
            case "nowrap":
                $str = $this->_collapse_white_space($text);
                break;

            case "pre-line":
                $str = preg_replace("/[ \t]+/u", " ", $text);
                break;

            case "pre-wrap":
                // Find the longest word (i.e. minimum length)
                $lines = array_flip(preg_split("/\R/u", $text));
                array_walk($lines, function(&$chunked_text_width, $chunked_text) use ($fontMetrics, $font, $size, $word_spacing, $char_spacing) {
                    $chunked_text_width = $fontMetrics->getTextWidth($chunked_text, $font, $size, $word_spacing, $char_spacing);
                });
                arsort($lines);
                reset($lines);
                $str = key($lines);
                break;
        }
        $max = $fontMetrics->getTextWidth($str, $font, $size, $word_spacing, $char_spacing);

        $delta = (float)$style->length_in_pt([$style->margin_left,
            $style->border_left_width,
            $style->padding_left,
            $style->padding_right,
            $style->border_right_width,
            $style->margin_right], $line_width);
        $min += $delta;
        $min_word = $min;
        $max += $delta;

        // https://www.w3.org/TR/css-text-3/#overflow-wrap-property
        if ($style->overflow_wrap === "anywhere"
            && !in_array($style->white_space, ["pre", "nowrap"], true)
        ) {
            // If it is allowed to break words, the min width is the widest character.
            // But for performance reasons, we only check the first character.
            $char = mb_substr($str, 0, 1);
            $min_char = $fontMetrics->getTextWidth($char, $font, $size, $word_spacing, $char_spacing);
            $min = $delta + $min_char;
        }

        return $this->_min_max_cache = [$min, $max, $min_word, "min" => $min, "max" => $max, 'min_word' => $min_word];
    }

    /**
     * Get the minimum width needed for the the first line of the text.
     *
     * @return array A triple of values: The minimum width, whether that
     * includes all text of the frame, and the margin-box delta of the width.
     */
    public function get_min_first_line_width(): array
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $line_width = $frame->get_containing_block("w");
        $fontMetrics = $this->getFontMetrics();

        $str = $frame->get_node()->textContent;
        $size = $style->font_size;
        $font = $style->font_family;

        $word_spacing = (float)$style->length_in_pt($style->word_spacing);
        $char_spacing = (float)$style->length_in_pt($style->letter_spacing);

        // https://www.w3.org/TR/css-text-3/#overflow-wrap-property
        if ($style->overflow_wrap === "anywhere"
            && !in_array($style->white_space, ["pre", "nowrap"], true)
        ) {
            // If it is allowed to break words, the min width is the widest character.
            // But for performance reasons, we only check the first character.
            $char = mb_substr($str, 0, 1);
            $min = $fontMetrics->getTextWidth($char, $font, $size, $word_spacing, $char_spacing);
            $includesAll = mb_strlen($str) <= 1;
        } else {
            switch ($style->white_space) {
                default:
                /** @noinspection PhpMissingBreakStatementInspection */
                // no break
                case "normal":
                    $str = $this->_collapse_white_space($str);
                    // no break
                case "pre-wrap":
                case "pre-line":
                    // Find the first word
                    $words = preg_split(self::$_wordbreak_pattern, $str, 2, PREG_SPLIT_DELIM_CAPTURE);
                    // `_line_break()` also considers trailing whitespace for
                    // checking the width
                    $firstWord = $words[0] . ($words[1] ?? "");
                    $min = $fontMetrics->getTextWidth($firstWord, $font, $size, $word_spacing, $char_spacing);
                    $includesAll = count($words) <= 2;
                    break;

                case "pre":
                    $lines = preg_split("/\R/u", $str, 2);
                    $min = $fontMetrics->getTextWidth($lines[0], $font, $size, $word_spacing, $char_spacing);
                    $includesAll = count($lines) <= 1;
                    break;

                case "nowrap":
                    $min = $fontMetrics->getTextWidth($this->_collapse_white_space($str), $font, $size, $word_spacing, $char_spacing);
                    $includesAll = true;
                    break;
            }
        }

        $widths = $includesAll ? [
            $style->margin_left,
            $style->border_left_width,
            $style->padding_left,
            $style->padding_right,
            $style->border_right_width,
            $style->margin_right
        ] : [
            $style->margin_left,
            $style->border_left_width,
            $style->padding_left
        ];
        $delta = (float) $style->length_in_pt($widths, $line_width);

        return [$min + $delta, $includesAll, $delta];
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
