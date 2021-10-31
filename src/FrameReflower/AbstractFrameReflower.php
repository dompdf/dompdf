<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\Dompdf;
use Dompdf\Helpers;
use Dompdf\Frame;
use Dompdf\FrameDecorator\Block;
use Dompdf\Frame\Factory;

/**
 * Base reflower class
 *
 * Reflower objects are responsible for determining the width and height of
 * individual frames.  They also create line and page breaks as necessary.
 *
 * @package dompdf
 */
abstract class AbstractFrameReflower
{

    /**
     * Frame for this reflower
     *
     * @var Frame
     */
    protected $_frame;

    /**
     * Cached min/max (content) size
     *
     * @var array
     */
    protected $_min_max_cache;

    /**
     * AbstractFrameReflower constructor.
     * @param Frame $frame
     */
    function __construct(Frame $frame)
    {
        $this->_frame = $frame;
        $this->_min_max_cache = null;
    }

    function dispose()
    {
    }

    /**
     * @return Dompdf
     */
    function get_dompdf()
    {
        return $this->_frame->get_dompdf();
    }

    public function reset(): void
    {
        $this->_min_max_cache = null;
    }

    /**
     * Determine the actual containing block for absolute and fixed position.
     *
     * https://www.w3.org/TR/CSS21/visudet.html#containing-block-details
     */
    protected function determine_absolute_containing_block(): void
    {
        $frame = $this->_frame;
        $style = $frame->get_style();

        switch ($style->position) {
            case "absolute":
                $parent = $frame->find_positionned_parent();
                if ($parent !== $frame->get_root()) {
                    $parent_style = $parent->get_style();
                    $parent_padding_box = $parent->get_padding_box();
                    //FIXME: an accurate measure of the positioned parent height
                    //       is not possible until reflow has completed;
                    //       we'll fall back to the parent's containing block,
                    //       which is wrong for auto-height parents
                    if ($parent_style->height === "auto") {
                        $parent_containing_block = $parent->get_containing_block();
                        $containing_block_height = $parent_containing_block["h"] -
                            (float)$parent_style->length_in_pt([
                                $parent_style->margin_top,
                                $parent_style->margin_bottom,
                                $parent_style->border_top_width,
                                $parent_style->border_bottom_width
                            ], $parent_containing_block["w"]);
                    } else {
                        $containing_block_height = $parent_padding_box["h"];
                    }
                    $frame->set_containing_block($parent_padding_box["x"], $parent_padding_box["y"], $parent_padding_box["w"], $containing_block_height);
                    break;
                }
            case "fixed":
                $initial_cb = $frame->get_root()->get_first_child()->get_containing_block();
                $frame->set_containing_block($initial_cb["x"], $initial_cb["y"], $initial_cb["w"], $initial_cb["h"]);
                break;
            default:
                // Nothing to do, containing block already set via parent
                break;
        }
    }

    /**
     * Collapse frames margins
     * http://www.w3.org/TR/CSS21/box.html#collapsing-margins
     */
    protected function _collapse_margins()
    {
        $frame = $this->_frame;

        // Margins of float/absolutely positioned/inline-level elements do not collapse
        if (!$frame->is_in_flow() || $frame->is_inline_level()
            || $frame->get_root() === $frame || $frame->get_parent() === $frame->get_root()
        ) {
            return;
        }

        $cb = $frame->get_containing_block();
        $style = $frame->get_style();

        $t = $style->length_in_pt($style->margin_top, $cb["w"]);
        $b = $style->length_in_pt($style->margin_bottom, $cb["w"]);

        // Handle 'auto' values
        if ($t === "auto") {
            $style->margin_top = 0;
            $t = 0;
        }

        if ($b === "auto") {
            $style->margin_bottom = 0;
            $b = 0;
        }

        // Collapse vertical margins:
        $n = $frame->get_next_sibling();
        if ( $n && !($n->is_block_level() && $n->is_in_flow()) ) {
            while ($n = $n->get_next_sibling()) {
                if ($n->is_block_level() && $n->is_in_flow()) {
                    break;
                }

                if (!$n->get_first_child()) {
                    $n = null;
                    break;
                }
            }
        }

        if ($n) {
            $n_style = $n->get_style();
            $n_t = (float)$n_style->length_in_pt($n_style->margin_top, $cb["w"]);

            $b = $this->_get_collapsed_margin_length($b, $n_t);
            $style->margin_bottom = $b;
            $n_style->margin_top = 0;
        }

        // Collapse our first child's margin, if there is no border or padding
        if ($style->border_top_width == 0 && $style->length_in_pt($style->padding_top) == 0) {
            $f = $this->_frame->get_first_child();
            if ( $f && !($f->is_block_level() && $f->is_in_flow()) ) {
                while ($f = $f->get_next_sibling()) {
                    if ($f->is_block_level() && $f->is_in_flow()) {
                        break;
                    }

                    if (!$f->get_first_child()) {
                        $f = null;
                        break;
                    }
                }
            }

            // Margins are collapsed only between block-level boxes
            if ($f) {
                $f_style = $f->get_style();
                $f_t = (float)$f_style->length_in_pt($f_style->margin_top, $cb["w"]);

                $t = $this->_get_collapsed_margin_length($t, $f_t);
                $style->margin_top = $t;
                $f_style->margin_top = 0;
            }
        }

        // Collapse our last child's margin, if there is no border or padding
        if ($style->border_bottom_width == 0 && $style->length_in_pt($style->padding_bottom) == 0) {
            $l = $this->_frame->get_last_child();
            if ( $l && !($l->is_block_level() && $l->is_in_flow()) ) {
                while ($l = $l->get_prev_sibling()) {
                    if ($l->is_block_level() && $l->is_in_flow()) {
                        break;
                    }

                    if (!$l->get_last_child()) {
                        $l = null;
                        break;
                    }
                }
            }

            // Margins are collapsed only between block-level boxes
            if ($l) {
                $l_style = $l->get_style();
                $l_b = (float)$l_style->length_in_pt($l_style->margin_bottom, $cb["w"]);

                $b = $this->_get_collapsed_margin_length($b, $l_b);
                $style->margin_bottom = $b;
                $l_style->margin_bottom = 0;
            }
        }
    }

    /**
     * Get the combined (collapsed) length of two adjoining margins.
     *
     * See http://www.w3.org/TR/CSS21/box.html#collapsing-margins.
     *
     * @param float $length1
     * @param float $length2
     * @return float
     */
    private function _get_collapsed_margin_length($length1, $length2)
    {
        if ($length1 < 0 && $length2 < 0) {
            return min($length1, $length2); // min(x, y) = - max(abs(x), abs(y)), if x < 0 && y < 0
        }
        
        if ($length1 < 0 || $length2 < 0) {
            return $length1 + $length2; // x + y = x - abs(y), if y < 0
        }
        
        return max($length1, $length2);
    }

    /**
     * Handle relative positioning according to
     * https://www.w3.org/TR/CSS21/visuren.html#relative-positioning.
     *
     * @param Frame $frame The frame to handle.
     */
    protected function position_relative(Frame $frame): void
    {
        $style = $frame->get_style();

        if ($style->position === "relative") {
            $cb = $frame->get_containing_block();
            $top = $style->length_in_pt($style->top, $cb["h"]);
            $right = $style->length_in_pt($style->right, $cb["w"]);
            $bottom = $style->length_in_pt($style->bottom, $cb["h"]);
            $left = $style->length_in_pt($style->left, $cb["w"]);

            // FIXME RTL case:
            // if ($left !== "auto" && $right !== "auto") $left = -$right;
            if ($left === "auto" && $right === "auto") {
                $left = 0;
            } elseif ($left === "auto") {
                $left = -(float) $right;
            }

            if ($top === "auto" && $bottom === "auto") {
                $top = 0;
            } elseif ($top === "auto") {
                $top = -(float) $bottom;
            }

            $frame->move((float) $left, (float) $top);
        }
    }

    /**
     * @param Block|null $block
     * @return mixed
     */
    abstract function reflow(Block $block = null);

    /**
     * Get the minimum and maximum width of the content of this frame.
     *
     * @return array An array [0 => min, 1 => max, "min" => min, "max" => max]
     * of the min and max width.
     */
    function get_min_max_content_width(): array
    {
        if (!is_null($this->_min_max_cache)) {
            return $this->_min_max_cache;
        }

        $cb_w = $this->_frame->get_containing_block("w");
        $style = $this->_frame->get_style();

        // Ignore percentage values for a specified width here, as the
        // containing block is not defined yet
        $display = $style->display;
        $width = $style->width;
        $fixed_width = $width !== "auto" && !Helpers::is_percent($width);

        // If the frame has a specified width, then we don't need to check its
        // children. Table cells are handled slightly differently below
        if ($fixed_width && $display !== "inline" && $display !== "table-cell") {
            $width = (float) $style->length_in_pt($width, $cb_w);
            return $this->_min_max_cache = [$width, $width, "min" => $width, "max" => $width];
        }

        $low = [];
        $high = [];

        for ($iter = $this->_frame->get_children()->getIterator(); $iter->valid(); $iter->next()) {
            $inline_min = 0;
            $inline_max = 0;

            // Add all adjacent inline widths together to calculate max width
            while ($iter->valid() && ($iter->current()->is_inline_level() || $iter->current()->get_style()->display === "-dompdf-image")) {
                $child = $iter->current();
                $child->get_reflower()->_set_content();
                $minmax = $child->get_min_max_width();

                if (in_array($child->get_style()->white_space, ["pre", "nowrap"], true)) {
                    $inline_min += $minmax["min"];
                } else {
                    $low[] = $minmax["min"];
                }

                $inline_max += $minmax["max"];
                $iter->next();
            }

            if ($inline_min > 0) {
                $low[] = $inline_min;
            }
            if ($inline_max > 0) {
                $high[] = $inline_max;
            }

            // Skip children with absolute position
            if ($iter->valid() && !$iter->current()->is_absolute()) {
                $child = $iter->current();
                $child->get_reflower()->_set_content();
                list($low[], $high[]) = $child->get_min_max_width();
            }
        }
        $min = count($low) ? max($low) : 0;
        $max = count($high) ? max($high) : 0;

        // For table cells: Use specified width if it is greater than the
        // minimum defined by the content
        if ($fixed_width && $display === "table-cell") {
            $width = (float) $style->length_in_pt($width, $cb_w);
            $min = max($width, $min);
            $max = $min;
        }

        return $this->_min_max_cache = [$min, $max, "min" => $min, "max" => $max];
    }

    /**
     * Required for table layout: Get the minimum and maximum width of this
     * frame.  This provides a basic implementation.  Child classes should
     * override this if necessary.
     *
     * @return array An array [0 => min, 1 => max, "min" => min, "max" => max]
     * of the min and max width.
     */
    function get_min_max_width(): array
    {
        $style = $this->_frame->get_style();

        // Account for margins & padding
        $dims = [$style->padding_left,
            $style->padding_right,
            $style->border_left_width,
            $style->border_right_width,
            $style->margin_left,
            $style->margin_right];

        $cb_w = $this->_frame->get_containing_block("w");
        $delta = (float)$style->length_in_pt($dims, $cb_w);

        [$min, $max] = $this->get_min_max_content_width();

        $min += $delta;
        $max += $delta;
        return [$min, $max, "min" => $min, "max" => $max];
    }

    /**
     * Parses a CSS string containing quotes and escaped hex characters
     *
     * @param $string string The CSS string to parse
     * @param $single_trim
     * @return string
     */
    protected function _parse_string($string, $single_trim = false)
    {
        if ($single_trim) {
            $string = preg_replace('/^[\"\']/', "", $string);
            $string = preg_replace('/[\"\']$/', "", $string);
        } else {
            $string = trim($string, "'\"");
        }

        $string = str_replace(["\\\n", '\\"', "\\'"],
            ["", '"', "'"], $string);

        // Convert escaped hex characters into ascii characters (e.g. \A => newline)
        $string = preg_replace_callback("/\\\\([0-9a-fA-F]{0,6})/",
            function ($matches) { return \Dompdf\Helpers::unichr(hexdec($matches[1])); },
            $string);
        return $string;
    }

    /**
     * Parses a CSS "quotes" property
     *
     * @return array|null An array of pairs of quotes
     */
    protected function _parse_quotes()
    {
        // Matches quote types
        $re = '/(\'[^\']*\')|(\"[^\"]*\")/';

        $quotes = $this->_frame->get_style()->quotes;

        // split on spaces, except within quotes
        if (!preg_match_all($re, "$quotes", $matches, PREG_SET_ORDER)) {
            return null;
        }

        $quotes_array = [];
        foreach ($matches as $_quote) {
            $quotes_array[] = $this->_parse_string($_quote[0], true);
        }

        if (empty($quotes_array)) {
            $quotes_array = ['"', '"'];
        }

        return array_chunk($quotes_array, 2);
    }

    /**
     * Parses the CSS "content" property
     *
     * @return string|null The resulting string
     */
    protected function _parse_content()
    {
        // Matches generated content
        $re = "/\n" .
            "\s(counters?\\([^)]*\\))|\n" .
            "\A(counters?\\([^)]*\\))|\n" .
            "\s([\"']) ( (?:[^\"']|\\\\[\"'])+ )(?<!\\\\)\\3|\n" .
            "\A([\"']) ( (?:[^\"']|\\\\[\"'])+ )(?<!\\\\)\\5|\n" .
            "\s([^\s\"']+)|\n" .
            "\A([^\s\"']+)\n" .
            "/xi";

        $content = $this->_frame->get_style()->content;

        $quotes = $this->_parse_quotes();

        // split on spaces, except within quotes
        if (!preg_match_all($re, $content, $matches, PREG_SET_ORDER)) {
            return null;
        }

        $text = "";

        foreach ($matches as $match) {
            if (isset($match[2]) && $match[2] !== "") {
                $match[1] = $match[2];
            }

            if (isset($match[6]) && $match[6] !== "") {
                $match[4] = $match[6];
            }

            if (isset($match[8]) && $match[8] !== "") {
                $match[7] = $match[8];
            }

            if (isset($match[1]) && $match[1] !== "") {
                // counters?(...)
                $match[1] = mb_strtolower(trim($match[1]));

                // Handle counter() references:
                // http://www.w3.org/TR/CSS21/generate.html#content

                $i = mb_strpos($match[1], ")");
                if ($i === false) {
                    continue;
                }

                preg_match('/(counters?)(^\()*?\(\s*([^\s,]+)\s*(,\s*["\']?([^"\'\)]*)["\']?\s*(,\s*([^\s)]+)\s*)?)?\)/i', $match[1], $args);
                $counter_id = $args[3];
                if (strtolower($args[1]) == 'counter') {
                    // counter(name [,style])
                    if (isset($args[5])) {
                        $type = trim($args[5]);
                    } else {
                        $type = null;
                    }
                    $p = $this->_frame->lookup_counter_frame($counter_id);

                    $text .= $p->counter_value($counter_id, $type);

                } else if (strtolower($args[1]) == 'counters') {
                    // counters(name, string [,style])
                    if (isset($args[5])) {
                        $string = $this->_parse_string($args[5]);
                    } else {
                        $string = "";
                    }

                    if (isset($args[7])) {
                        $type = trim($args[7]);
                    } else {
                        $type = null;
                    }

                    $p = $this->_frame->lookup_counter_frame($counter_id);
                    $tmp = [];
                    while ($p) {
                        // We only want to use the counter values when they actually increment the counter
                        if (array_key_exists($counter_id, $p->_counters)) {
                            array_unshift($tmp, $p->counter_value($counter_id, $type));
                        }
                        $p = $p->lookup_counter_frame($counter_id);
                    }
                    $text .= implode($string, $tmp);
                } else {
                    // countertops?
                    continue;
                }

            } else if (isset($match[4]) && $match[4] !== "") {
                // String match
                $text .= $this->_parse_string($match[4]);
            } else if (isset($match[7]) && $match[7] !== "") {
                // Directive match

                if ($match[7] === "open-quote") {
                    // FIXME: do something here
                    $text .= $quotes[0][0];
                } else if ($match[7] === "close-quote") {
                    // FIXME: do something else here
                    $text .= $quotes[0][1];
                } else if ($match[7] === "no-open-quote") {
                    // FIXME:
                } else if ($match[7] === "no-close-quote") {
                    // FIXME:
                } else if (mb_strpos($match[7], "attr(") === 0) {
                    $i = mb_strpos($match[7], ")");
                    if ($i === false) {
                        continue;
                    }

                    $attr = mb_substr($match[7], 5, $i - 5);
                    if ($attr == "") {
                        continue;
                    }

                    $text .= $this->_frame->get_parent()->get_node()->getAttribute($attr);
                } else {
                    continue;
                }
            }
        }

        return $text;
    }

    /**
     * Sets the generated content of a generated frame
     */
    protected function _set_content()
    {
        $frame = $this->_frame;

        if ($frame->content_set) {
            return;
        }

        $style = $frame->get_style();

        if ($style->counter_reset && ($reset = $style->counter_reset) !== "none") {
            $vars = preg_split('/\s+/', trim($reset), 2);
            $frame->reset_counter($vars[0], isset($vars[1]) ? $vars[1] : 0);
        }

        if ($style->counter_increment && ($increment = $style->counter_increment) !== "none") {
            $frame->increment_counters($increment);
        }

        if ($style->content && $frame->get_node()->nodeName === "dompdf_generated") {
            $content = $this->_parse_content();
            $node = $frame->get_node()->ownerDocument->createTextNode($content);

            $new_style = $style->get_stylesheet()->create_style();
            $new_style->inherit($style);

            $new_frame = new Frame($node);
            $new_frame->set_style($new_style);

            Factory::decorate_frame($new_frame, $frame->get_dompdf(), $frame->get_root());
            $frame->append_child($new_frame);
        }

        $frame->content_set = true;
    }
}
