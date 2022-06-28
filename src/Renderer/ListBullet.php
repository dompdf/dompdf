<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

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
            case "decimal-leading-zero":
            case "decimal":
            case "1":
                return "0123456789";

            case "upper-alpha":
            case "upper-latin":
            case "A":
                $uppercase = true;
            case "lower-alpha":
            case "lower-latin":
            case "a":
                $text = "abcdefghijklmnopqrstuvwxyz";
                break;

            case "upper-roman":
            case "I":
                $uppercase = true;
            case "lower-roman":
            case "i":
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
     * @param int $n
     * @param string $type
     * @param int|null $pad
     *
     * @return string
     */
    private function make_counter($n, $type, $pad = null)
    {
        $n = intval($n);
        $text = "";
        $uppercase = false;

        switch ($type) {
            case "decimal-leading-zero":
            case "decimal":
            case "1":
                if ($pad) {
                    $text = str_pad($n, $pad, "0", STR_PAD_LEFT);
                } else {
                    $text = $n;
                }
                break;

            case "upper-alpha":
            case "upper-latin":
            case "A":
                $uppercase = true;
            case "lower-alpha":
            case "lower-latin":
            case "a":
                $text = chr((($n - 1) % 26) + ord('a'));
                break;

            case "upper-roman":
            case "I":
                $uppercase = true;
            case "lower-roman":
            case "i":
                $text = Helpers::dec2roman($n);
                break;

            case "lower-greek":
                $text = Helpers::unichr($n + 944);
                break;
        }

        if ($uppercase) {
            $text = strtoupper($text);
        }

        return "$text.";
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
                default:
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

                case "decimal-leading-zero":
                case "decimal":
                case "lower-alpha":
                case "lower-latin":
                case "lower-roman":
                case "lower-greek":
                case "upper-alpha":
                case "upper-latin":
                case "upper-roman":
                case "1": // HTML 4.0 compatibility
                case "a":
                case "i":
                case "A":
                case "I":
                    $pad = null;
                    if ($bullet_style === "decimal-leading-zero") {
                        $pad = strlen($li->get_parent()->get_node()->getAttribute("dompdf-children-count"));
                    }

                    $node = $frame->get_node();

                    if (!$node->hasAttribute("dompdf-counter")) {
                        return;
                    }

                    $index = $node->getAttribute("dompdf-counter");
                    $text = $this->make_counter($index, $bullet_style, $pad);

                    if (trim($text) === "") {
                        return;
                    }

                    $word_spacing = $style->word_spacing;
                    $letter_spacing = $style->letter_spacing;
                    $text_width = $this->_dompdf->getFontMetrics()->getTextWidth($text, $font_family, $font_size, $word_spacing, $letter_spacing);

                    [$x, $y] = $frame->get_position();
                    // Correct for static frame width applied by positioner
                    $x += $frame->get_width() - $text_width;

                    $this->_canvas->text($x, $y, $text,
                        $font_family, $font_size,
                        $style->color, $word_spacing, $letter_spacing);

                case "none":
                    break;
            }
        }

        $id = $frame->get_node()->getAttribute("id");
        if (strlen($id) > 0) {
            $this->_canvas->add_named_dest($id);
        }
    }
}
