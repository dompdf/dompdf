<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Css\Content\Attr;
use Dompdf\Css\Content\CloseQuote;
use Dompdf\Css\Content\Counter;
use Dompdf\Css\Content\Counters;
use Dompdf\Css\Content\NoCloseQuote;
use Dompdf\Css\Content\NoOpenQuote;
use Dompdf\Css\Content\OpenQuote;
use Dompdf\Css\Content\StringPart;
use Dompdf\Helpers;
use Dompdf\Frame;
use Dompdf\FrameDecorator\ListBullet as ListBulletFrameDecorator;
use Dompdf\FrameDecorator\ListBulletImage;
use Dompdf\Image\Cache;

/**
 * Renders list bullets
 *
 * @package dompdf
 */
class ListBullet extends AbstractRenderer
{
    /**
     * @param $type
     * @return mixed|string
     * @deprecated
     */
    static function get_counter_chars($type)
    {
        static $cache = [];

        if (isset($cache[$type])) {
            return $cache[$type];
        }

        $uppercase = false;
        $text = "";

        switch ($type) {
            default:
            case "decimal":
            case "decimal-leading-zero":
                return "0123456789";

            case "upper-alpha":
            case "upper-latin":
                $uppercase = true;
            case "lower-alpha":
            case "lower-latin":
                $text = "abcdefghijklmnopqrstuvwxyz";
                break;

            case "upper-roman":
                $uppercase = true;
            case "lower-roman":
                $text = "ivxlcdm";
                break;

            case "lower-greek":
                for ($i = 0; $i < 24; $i++) {
                    $text .= Helpers::unichr($i + 944);
                }
                break;
        }

        if ($uppercase) {
            $text = strtoupper($text);
        }

        return $cache[$type] = "$text.";
    }

    /**
     * @param int      $n
     * @param string   $type
     * @param int|null $pad
     *
     * @return string
     */
    private function make_counter_value(int $n, string $type, ?int $pad = null): string
    {
        $text = "";

        switch ($type) {
            default:
            case "decimal":
            case "decimal-leading-zero":
                if ($pad) {
                    $text = str_pad($n, $pad, "0", STR_PAD_LEFT);
                } else {
                    $text = (string) $n;
                }
                break;

            case "upper-alpha":
            case "upper-latin":
                $text = strtoupper(Helpers::dec2base26($n));
                break;

            case "lower-alpha":
            case "lower-latin":
                $text = Helpers::dec2base26($n);
                break;

            case "upper-roman":
                $text = strtoupper(Helpers::dec2roman($n));
                break;

            case "lower-roman":
                $text = Helpers::dec2roman($n);
                break;

            case "lower-greek":
                $text = Helpers::unichr($n + 944);
                break;
        }

        return $text;
    }

    /**
     * @param int      $n
     * @param string   $type
     * @param int|null $pad
     *
     * @return string
     */
    private function make_counter(int $n, string $type, ?int $pad = null): string
    {
        return $this->make_counter_value($n, $type, $pad) . ".";
    }

    /**
     * Resolve the automatic list-item counter chain for counters(list-item).
     *
     * Each list-item owns a generated bullet frame carrying the counter value
     * assigned by Frame\Factory. Walking ancestor list-items therefore gives
     * us the complete outer-to-inner counter chain without introducing a
     * separate CSS counter implementation for list-item.
     *
     * @return string[]
     */
    private function get_list_item_counter_values(Frame $frame, string $style): array
    {
        $values = [];
        $current = $frame->get_parent();

        while ($current) {
            $node = $current->get_node();

            if ($node->nodeName === "li") {
                $bullet = $current->get_first_child();

                if ($bullet) {
                    $bullet_node = $bullet->get_node();

                    if ($bullet_node->nodeName === "bullet" && $bullet_node->hasAttribute("dompdf-counter")) {
                        $pad = null;

                        if ($style === "decimal-leading-zero") {
                            $list = $current->get_parent();
                            if ($list) {
                                $count = $list->get_node()->getAttribute("dompdf-children-count");
                                if ($count !== "") {
                                    $pad = strlen($count);
                                }
                            }
                        }

                        array_unshift(
                            $values,
                            $this->make_counter_value(
                                (int) $bullet_node->getAttribute("dompdf-counter"),
                                $style,
                                $pad
                            )
                        );
                    }
                }
            }

            $current = $current->get_parent();
        }

        return $values;
    }

    /**
     * Resolve explicit `content` on the ::marker pseudo-element.
     *
     * `counter(list-item)` is backed by dompdf's existing list counter, which
     * already accounts for `start` and `value` attributes.
     */
    private function resolve_marker_content(Frame $frame, int $index): ?string
    {
        $style = $frame->get_style();
        $content = $style->content;

        if ($content === "normal") {
            return null;
        }

        if ($content === "none") {
            return "";
        }

        $quotes = $style->quotes;
        $text = "";

        foreach ($content as $val) {
            if ($val instanceof StringPart) {
                $text .= $val->string;
            }

            elseif ($val instanceof OpenQuote) {
                if ($quotes !== "none" && isset($quotes[0][0])) {
                    $text .= $quotes[0][0];
                }
            }

            elseif ($val instanceof CloseQuote) {
                if ($quotes !== "none" && isset($quotes[0][1])) {
                    $text .= $quotes[0][1];
                }
            }

            elseif ($val instanceof NoOpenQuote || $val instanceof NoCloseQuote) {
                // Quote depth is not tracked by dompdf yet.
            }

            elseif ($val instanceof Attr) {
                $parent = $frame->get_parent();
                if ($parent) {
                    $text .= $parent->get_node()->getAttribute($val->attribute);
                }
            }

            elseif ($val instanceof Counter) {
                if ($val->name === "list-item") {
                    $pad = null;
                    if ($val->style === "decimal-leading-zero") {
                        $li = $frame->get_parent();
                        if ($li && $li->get_parent()) {
                            $count = $li->get_parent()->get_node()->getAttribute("dompdf-children-count");
                            if ($count !== "") {
                                $pad = strlen($count);
                            }
                        }
                    }
                    $text .= $this->make_counter_value($index, $val->style, $pad);
                } else {
                    $p = $frame->lookup_counter_frame($val->name, true);
                    if ($p) {
                        $text .= $p->counter_value($val->name, $val->style);
                    }
                }
            }

            elseif ($val instanceof Counters) {
                if ($val->name === "list-item") {
                    $values = $this->get_list_item_counter_values($frame, $val->style);

                    // Keep the current marker usable even if the frame tree is
                    // incomplete (for example during a split/reflow edge case).
                    if ($values === []) {
                        $values[] = $this->make_counter_value($index, $val->style);
                    }

                    $text .= implode($val->string, $values);
                } else {
                    $p = $frame->lookup_counter_frame($val->name, true);
                    $tmp = [];
                    while ($p) {
                        array_unshift($tmp, $p->counter_value($val->name, $val->style));
                        $p = $p->lookup_counter_frame($val->name);
                    }
                    $text .= implode($val->string, $tmp);
                }
            }
        }

        return $text;
    }

    private function render_text_marker(Frame $frame, string $text): void
    {
        $style = $frame->get_style();
        $font_family = $style->font_family;
        $font_size = $style->font_size;
        $word_spacing = $style->word_spacing;
        $letter_spacing = $style->letter_spacing;
        $text_width = $this->_dompdf->getFontMetrics()->getTextWidth(
            $text,
            $font_family,
            $font_size,
            $word_spacing,
            $letter_spacing
        );

        [$x, $y] = $frame->get_position();
        // Correct for static frame width applied by positioner
        $x += $frame->get_width() - $text_width;

        $this->_canvas->text(
            $x,
            $y,
            $text,
            $font_family,
            $font_size,
            $style->color,
            $word_spacing,
            $letter_spacing
        );
    }

    /**
     * @param ListBulletFrameDecorator $frame
     */
    function render(Frame $frame)
    {
        $li = $frame->get_parent();
        $style = $frame->get_style();

        $this->_set_opacity($frame->get_opacity($style->opacity));

        // Don't render bullets twice if the list item was split
        if ($li->is_split_off) {
            return;
        }

        $node = $frame->get_node();
        $index = $node->hasAttribute("dompdf-counter")
            ? (int) $node->getAttribute("dompdf-counter")
            : 0;

        $marker_content = $this->resolve_marker_content($frame, $index);
        if ($marker_content !== null) {
            if ($marker_content !== "") {
                $this->render_text_marker($frame, $marker_content);
            }
            return;
        }

        $font_family = $style->font_family;
        $font_size = $style->font_size;
        $baseline = $this->_canvas->get_font_baseline($font_family, $font_size);

        // Handle list-style-image
        // If list style image is requested but missing, fall back to predefined types
        if ($frame instanceof ListBulletImage && !Cache::is_broken($img = $frame->get_image_url())) {
            [$x, $y] = $frame->get_position();
            $w = $frame->get_width();
            $h = $frame->get_height();
            $y += $baseline - $h;

            $this->_canvas->image($img, $x, $y, $w, $h);
        } else {
            $bullet_style = $style->list_style_type;

            switch ($bullet_style) {
                case "disc":
                case "circle":
                    [$x, $y] = $frame->get_position();
                    $offset = $font_size * ListBulletFrameDecorator::BULLET_OFFSET;
                    $r = ($font_size * ListBulletFrameDecorator::BULLET_SIZE) / 2;
                    $x += $r;
                    $y += $baseline - $r - $offset;
                    $o = $font_size * ListBulletFrameDecorator::BULLET_THICKNESS;
                    $this->_canvas->circle($x, $y, $r, $style->color, $o, null, $bullet_style !== "circle");
                    break;

                case "square":
                    [$x, $y] = $frame->get_position();
                    $offset = $font_size * ListBulletFrameDecorator::BULLET_OFFSET;
                    $w = $font_size * ListBulletFrameDecorator::BULLET_SIZE;
                    $y += $baseline - $w - $offset;
                    $this->_canvas->filled_rectangle($x, $y, $w, $w, $style->color);
                    break;

                default:
                case "decimal":
                case "decimal-leading-zero":
                case "lower-alpha":
                case "lower-latin":
                case "lower-roman":
                case "lower-greek":
                case "upper-alpha":
                case "upper-latin":
                case "upper-roman":
                    $pad = null;
                    if ($bullet_style === "decimal-leading-zero") {
                        $pad = strlen($li->get_parent()->get_node()->getAttribute("dompdf-children-count"));
                    }

                    if (!$node->hasAttribute("dompdf-counter")) {
                        return;
                    }

                    $text = $this->make_counter($index, $bullet_style, $pad);
                    $this->render_text_marker($frame, $text);
                    break;

                case "none":
                    break;
            }
        }
    }
}
