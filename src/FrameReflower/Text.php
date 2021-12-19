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
    public static $_wordbreak_pattern = '/([^\S\xA0\x{202F}\x{2007}]+|\-+|\xAD+)/u';

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
     * @param string $text
     * @return string
     */
    protected function collapse_white_space(string $text): string
    {
        return preg_replace(self::$_whitespace_pattern, " ", $text) ?? "";
    }

    /**
     * @param string $text
     * @param BlockFrameDecorator $block
     * @return bool|int
     */
    protected function line_break(string $text, BlockFrameDecorator $block)
    {
        $frame = $this->_frame;
        $style = $frame->get_style();
        $size = $style->font_size;
        $font = $style->font_family;
        $current_line = $block->get_current_line_box();
        $fontMetrics = $this->getFontMetrics();

        // Determine the available width
        $line_width = $frame->get_containing_block("w");
        $current_line_width = $current_line->left + $current_line->w + $current_line->right;
        $available_width = $line_width - $current_line_width;

        // Account for word and letter spacing
        $word_spacing = (float) $style->length_in_pt($style->word_spacing);
        $char_spacing = (float) $style->length_in_pt($style->letter_spacing);

        // Determine the frame width including margin, padding & border
        $visible_text = preg_replace('/\xAD/u', "", $text);
        $text_width = $fontMetrics->getTextWidth($visible_text, $font, $size, $word_spacing, $char_spacing);
        $mbp_width = (float) $style->length_in_pt([
            $style->margin_left,
            $style->border_left_width,
            $style->padding_left,
            $style->padding_right,
            $style->border_right_width,
            $style->margin_right
        ], $line_width);
        $frame_width = $text_width + $mbp_width;

        if ($frame_width <= $available_width) {
            return false;
        }

        // Split the text into words
        $words = preg_split(self::$_wordbreak_pattern, $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        $wc = count($words);

        // Determine the split point
        $width = 0;
        $str = "";

        $space_width = $fontMetrics->getTextWidth(" ", $font, $size, $word_spacing, $char_spacing);
        $shy_width = $fontMetrics->getTextWidth(self::SOFT_HYPHEN, $font, $size);

        // @todo support <wbr>
        for ($i = 0; $i < $wc; $i += 2) {
            // Allow trailing white space to overflow. White space is always
            // collapsed to the standard space character currently, so only
            // handle that for now
            $sep = isset($words[$i + 1]) ? $words[$i + 1] : "";
            $word = $sep === " " ? $words[$i] : $words[$i] . $sep;
            $word_width = $fontMetrics->getTextWidth($word, $font, $size, $word_spacing, $char_spacing);

            if ($width + $word_width + $mbp_width > $available_width) {
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
                    $w = $fontMetrics->getTextWidth($s . $c, $font, $size, $word_spacing, $char_spacing);

                    if ($w > $available_width) {
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
     *         should be stopped because of a parent split.
     */
    protected function layout_line(BlockFrameDecorator $block): ?bool
    {
        $frame = $this->_frame;
        $text = $frame->get_text();
        $current_line = $block->get_current_line_box();

        // Left trim the text if this is the first text on the line and we're
        // collapsing white space
        if ($current_line->w === 0.0 && !$frame->is_pre()) {
            $text = ltrim($text);
        }

        $style = $frame->get_style();
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
                $text = $this->collapse_white_space($text);

                if ($text === "") {
                    break;
                }

                $split = $this->line_break($text, $block);
                break;

            case "nowrap":
                $text = $this->collapse_white_space($text);
                break;

            case "pre":
                $split = $this->newline_break($text);
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
                $split = $this->newline_break($text);

                if (($tmp = $this->line_break($text, $block)) !== false) {
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

        // Handle edge cases
        if ($split === 0 && !$frame->is_pre() && trim($text) === "") {
            $frame->set_text("");
        } elseif ($split === 0) {
            $block->maximize_line_height($frame->get_margin_height(), $frame);
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
            } else {
                $add_line = $this->layout_line($block);
            }
        } elseif ($split !== false && $split < mb_strlen($text)) {
            // Split the line if required
            $frame->split_text($split);
            $add_line = true;

            // Remove inner soft hyphens
            $t = $frame->get_text();
            $shyPosition = mb_strpos($t, self::SOFT_HYPHEN);
            if (false !== $shyPosition && $shyPosition < mb_strlen($t) - 1) {
                $t = str_replace(self::SOFT_HYPHEN, "", mb_substr($t, 0, -1)) . mb_substr($t, -1);
                $frame->set_text($t);
            }
        } elseif ($text !== "") {
            // Remove soft hyphens
            $t = str_replace(self::SOFT_HYPHEN, "", $text);
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
        $page->check_forced_page_break($frame);

        if ($page->is_full()) {
            return;
        }

        $add_line = $this->layout_line($block);

        if ($add_line === null) {
            return;
        }

        $frame->position();

        // Exclude wrapped white space when white space is collapsed
        if ($block && ($frame->is_pre() || $frame->get_margin_width() !== 0.0)) {
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
                $str = $this->collapse_white_space($str);
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
                $min = $fontMetrics->getTextWidth($this->collapse_white_space($str), $font, $size, $word_spacing, $char_spacing);
                break;
        }

        // clean up the frame text based on the whitespace setting and use to determine maximum text width
        switch ($style->white_space) {
            default:
            case "normal":
            case "nowrap":
                $str = $this->collapse_white_space($text);
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
