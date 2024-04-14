<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Adapter;

use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\Helpers;
use Dompdf\Image\Cache;

/**
 * Image rendering interface
 *
 * Renders to an image format supported by GD (jpeg, gif, png, xpm).
 * Not super-useful day-to-day but handy nonetheless
 *
 * @package dompdf
 */
class GD implements Canvas
{
    /**
     * @var Dompdf
     */
    protected $_dompdf;

    /**
     * Resource handle for the image
     *
     * @var \GdImage|resource
     */
    protected $_img;

    /**
     * Resource handle for the image
     *
     * @var \GdImage[]|resource[]
     */
    protected $_imgs;

    /**
     * Apparent canvas width in pixels
     *
     * @var int
     */
    protected $_width;

    /**
     * Apparent canvas height in pixels
     *
     * @var int
     */
    protected $_height;

    /**
     * Actual image width in pixels
     *
     * @var int
     */
    protected $_actual_width;

    /**
     * Actual image height in pixels
     *
     * @var int
     */
    protected $_actual_height;

    /**
     * Current page number
     *
     * @var int
     */
    protected $_page_number;

    /**
     * Total number of pages
     *
     * @var int
     */
    protected $_page_count;

    /**
     * Image antialias factor
     *
     * @var float
     */
    protected $_aa_factor;

    /**
     * Allocated colors
     *
     * @var array
     */
    protected $_colors;

    /**
     * Background color
     *
     * @var int
     */
    protected $_bg_color;

    /**
     * Background color array
     *
     * @var array
     */
    protected $_bg_color_array;

    /**
     * Actual DPI
     *
     * @var int
     */
    protected $dpi;

    /**
     * Amount to scale font sizes
     *
     * Font sizes are 72 DPI, GD internally uses 96. Scale them proportionally.
     * 72 / 96 = 0.75.
     *
     * @var float
     */
    const FONT_SCALE = 0.75;

    /**
     * @param string|float[] $paper       The paper size to use as either a standard paper size (see {@link CPDF::$PAPER_SIZES}) or
     *                                    an array of the form `[x1, y1, x2, y2]` (typically `[0, 0, width, height]`).
     * @param string         $orientation The paper orientation, either `portrait` or `landscape`.
     * @param Dompdf|null    $dompdf      The Dompdf instance.
     * @param float          $aa_factor   Anti-aliasing factor, 1 for no AA
     * @param array          $bg_color    Image background color: array(r,g,b,a), 0 <= r,g,b,a <= 1
     */
    public function __construct($paper = "letter", string $orientation = "portrait", ?Dompdf $dompdf = null, float $aa_factor = 1.0, array $bg_color = [1, 1, 1, 0])
    {
        if (is_array($paper)) {
            $size = array_map("floatval", $paper);
        } else {
            $paper = strtolower($paper);
            $size = CPDF::$PAPER_SIZES[$paper] ?? CPDF::$PAPER_SIZES["letter"];
        }

        if (strtolower($orientation) === "landscape") {
            [$size[2], $size[3]] = [$size[3], $size[2]];
        }

        if ($dompdf === null) {
            $this->_dompdf = new Dompdf();
        } else {
            $this->_dompdf = $dompdf;
        }

        $this->dpi = $this->get_dompdf()->getOptions()->getDpi();

        if ($aa_factor < 1) {
            $aa_factor = 1;
        }

        $this->_aa_factor = $aa_factor;

        $size[2] *= $aa_factor;
        $size[3] *= $aa_factor;

        $this->_width = $size[2] - $size[0];
        $this->_height = $size[3] - $size[1];

        $this->_actual_width = $this->_upscale($this->_width);
        $this->_actual_height = $this->_upscale($this->_height);

        $this->_page_number = $this->_page_count = 0;

        if (is_null($bg_color) || !is_array($bg_color)) {
            // Pure white bg
            $bg_color = [1, 1, 1, 0];
        }

        $this->_bg_color_array = $bg_color;

        $this->new_page();
    }

    public function get_dompdf()
    {
        return $this->_dompdf;
    }

    /**
     * Return the GD image resource
     *
     * @return \GdImage|resource
     */
    public function get_image()
    {
        return $this->_img;
    }

    /**
     * Return the image's width in pixels
     *
     * @return int
     */
    public function get_width()
    {
        return round($this->_width / $this->_aa_factor);
    }

    /**
     * Return the image's height in pixels
     *
     * @return int
     */
    public function get_height()
    {
        return round($this->_height / $this->_aa_factor);
    }

    public function get_page_number()
    {
        return $this->_page_number;
    }

    public function get_page_count()
    {
        return $this->_page_count;
    }

    /**
     * Sets the current page number
     *
     * @param int $num
     */
    public function set_page_number($num)
    {
        $this->_page_number = $num;
    }

    public function set_page_count($count)
    {
        $this->_page_count = $count;
    }

    public function set_opacity(float $opacity, string $mode = "Normal"): void
    {
        // FIXME
    }

    /**
     * Allocate a new color.  Allocate with GD as needed and store
     * previously allocated colors in $this->_colors.
     *
     * @param array $color The new current color
     * @return int The allocated color
     */
    protected function _allocate_color($color)
    {
        $a = isset($color["alpha"]) ? $color["alpha"] : 1;

        if (isset($color["c"])) {
            $color = Helpers::cmyk_to_rgb($color);
        }

        list($r, $g, $b) = $color;

        $r = round($r * 255);
        $g = round($g * 255);
        $b = round($b * 255);
        $a = round(127 - ($a * 127));

        // Clip values
        $r = $r > 255 ? 255 : $r;
        $g = $g > 255 ? 255 : $g;
        $b = $b > 255 ? 255 : $b;
        $a = $a > 127 ? 127 : $a;

        $r = $r < 0 ? 0 : $r;
        $g = $g < 0 ? 0 : $g;
        $b = $b < 0 ? 0 : $b;
        $a = $a < 0 ? 0 : $a;

        $key = sprintf("#%02X%02X%02X%02X", $r, $g, $b, $a);

        if (isset($this->_colors[$key])) {
            return $this->_colors[$key];
        }

        if ($a != 0) {
            $this->_colors[$key] = imagecolorallocatealpha($this->get_image(), $r, $g, $b, $a);
        } else {
            $this->_colors[$key] = imagecolorallocate($this->get_image(), $r, $g, $b);
        }

        return $this->_colors[$key];
    }

    /**
     * Scales value up to the current canvas DPI from 72 DPI
     *
     * @param float $length
     * @return int
     */
    protected function _upscale($length)
    {
        return round(($length * $this->dpi) / 72 * $this->_aa_factor);
    }

    /**
     * Scales value down from the current canvas DPI to 72 DPI
     *
     * @param float $length
     * @return float
     */
    protected function _downscale($length)
    {
        return round(($length / $this->dpi * 72) / $this->_aa_factor);
    }

    protected function convertStyle(array $style, int $color, int $width): array
    {
        $gdStyle = [];

        if (count($style) === 1) {
            $style[] = $style[0];
        }

        foreach ($style as $index => $s) {
            $d = $this->_upscale($s);

            for ($i = 0; $i < $d; $i++) {
                for ($j = 0; $j < $width; $j++) {
                    $gdStyle[] = $index % 2 === 0
                        ? $color
                        : IMG_COLOR_TRANSPARENT;
                }
            }
        }

        return $gdStyle;
    }

    public function line($x1, $y1, $x2, $y2, $color, $width, $style = [], $cap = "butt")
    {
        // Account for the fact that round and square caps are expected to
        // extend outwards
        if ($cap === "round" || $cap === "square") {
            // Shift line by half width
            $w = $width / 2;
            $a = $x2 - $x1;
            $b = $y2 - $y1;
            $c = sqrt($a ** 2 + $b ** 2);
            $dx = $a * $w / $c;
            $dy = $b * $w / $c;

            $x1 -= $dx;
            $x2 -= $dx;
            $y1 -= $dy;
            $y2 -= $dy;

            // Adapt dash pattern
            if (is_array($style)) {
                foreach ($style as $index => &$s) {
                    $s = $index % 2 === 0 ? $s + $width : $s - $width;
                }
            }
        }

        // Scale by the AA factor and DPI
        $x1 = $this->_upscale($x1);
        $y1 = $this->_upscale($y1);
        $x2 = $this->_upscale($x2);
        $y2 = $this->_upscale($y2);
        $width = $this->_upscale($width);

        $c = $this->_allocate_color($color);

        // Convert the style array if required
        if (is_array($style) && count($style) > 0) {
            $gd_style = $this->convertStyle($style, $c, $width);

            if (!empty($gd_style)) {
                imagesetstyle($this->get_image(), $gd_style);
                $c = IMG_COLOR_STYLED;
            }
        }

        imagesetthickness($this->get_image(), $width);

        imageline($this->get_image(), $x1, $y1, $x2, $y2, $c);
    }

    public function arc($x, $y, $r1, $r2, $astart, $aend, $color, $width, $style = [], $cap = "butt")
    {
        // Account for the fact that round and square caps are expected to
        // extend outwards
        if ($cap === "round" || $cap === "square") {
            // Adapt dash pattern
            if (is_array($style)) {
                foreach ($style as $index => &$s) {
                    $s = $index % 2 === 0 ? $s + $width : $s - $width;
                }
            }
        }

        // Scale by the AA factor and DPI
        $x = $this->_upscale($x);
        $y = $this->_upscale($y);
        $w = $this->_upscale($r1 * 2);
        $h = $this->_upscale($r2 * 2);
        $width = $this->_upscale($width);

        // Adapt angles as imagearc counts clockwise
        $start = 360 - $aend;
        $end = 360 - $astart;

        $c = $this->_allocate_color($color);

        // Convert the style array if required
        if (is_array($style) && count($style) > 0) {
            $gd_style = $this->convertStyle($style, $c, $width);

            if (!empty($gd_style)) {
                imagesetstyle($this->get_image(), $gd_style);
                $c = IMG_COLOR_STYLED;
            }
        }

        imagesetthickness($this->get_image(), $width);

        imagearc($this->get_image(), $x, $y, $w, $h, $start, $end, $c);
    }

    public function rectangle($x1, $y1, $w, $h, $color, $width, $style = [], $cap = "butt")
    {
        // Account for the fact that round and square caps are expected to
        // extend outwards
        if ($cap === "round" || $cap === "square") {
            // Adapt dash pattern
            if (is_array($style)) {
                foreach ($style as $index => &$s) {
                    $s = $index % 2 === 0 ? $s + $width : $s - $width;
                }
            }
        }

        // Scale by the AA factor and DPI
        $x1 = $this->_upscale($x1);
        $y1 = $this->_upscale($y1);
        $w = $this->_upscale($w);
        $h = $this->_upscale($h);
        $width = $this->_upscale($width);

        $c = $this->_allocate_color($color);

        // Convert the style array if required
        if (is_array($style) && count($style) > 0) {
            $gd_style = $this->convertStyle($style, $c, $width);

            if (!empty($gd_style)) {
                imagesetstyle($this->get_image(), $gd_style);
                $c = IMG_COLOR_STYLED;
            }
        }

        imagesetthickness($this->get_image(), $width);

        if ($c === IMG_COLOR_STYLED) {
            $points = [
                $x1, $y1,
                $x1 + $w, $y1,
                $x1 + $w, $y1 + $h,
                $x1, $y1 + $h
            ];
            if (version_compare(PHP_VERSION, "8.1.0", "<")) {
                imagepolygon($this->get_image(), $points, count($points)/2, $c);
            } else {
                imagepolygon($this->get_image(), $points, $c);
            }
        } else {
            imagerectangle($this->get_image(), $x1, $y1, $x1 + $w, $y1 + $h, $c);
        }
    }

    public function filled_rectangle($x1, $y1, $w, $h, $color)
    {
        // Scale by the AA factor and DPI
        $x1 = $this->_upscale($x1);
        $y1 = $this->_upscale($y1);
        $w = $this->_upscale($w);
        $h = $this->_upscale($h);

        $c = $this->_allocate_color($color);

        imagefilledrectangle($this->get_image(), $x1, $y1, $x1 + $w, $y1 + $h, $c);
    }

    public function clipping_rectangle($x1, $y1, $w, $h)
    {
        // @todo
    }

    public function clipping_roundrectangle($x1, $y1, $w, $h, $rTL, $rTR, $rBR, $rBL)
    {
        // @todo
    }

    public function clipping_polygon(array $points): void
    {
        // @todo
    }

    public function clipping_end()
    {
        // @todo
    }

    public function save()
    {
        $this->get_dompdf()->getOptions()->setDpi(72);
    }

    public function restore()
    {
        $this->get_dompdf()->getOptions()->setDpi($this->dpi);
    }

    public function rotate($angle, $x, $y)
    {
        // @todo
    }

    public function skew($angle_x, $angle_y, $x, $y)
    {
        // @todo
    }

    public function scale($s_x, $s_y, $x, $y)
    {
        // @todo
    }

    public function translate($t_x, $t_y)
    {
        // @todo
    }

    public function transform($a, $b, $c, $d, $e, $f)
    {
        // @todo
    }

    public function polygon($points, $color, $width = null, $style = [], $fill = false)
    {
        // Scale each point by the AA factor and DPI
        foreach (array_keys($points) as $i) {
            $points[$i] = $this->_upscale($points[$i]);
        }

        $width = isset($width) ? $this->_upscale($width) : null;

        $c = $this->_allocate_color($color);

        // Convert the style array if required
        if (is_array($style) && count($style) > 0 && isset($width) && !$fill) {
            $gd_style = $this->convertStyle($style, $c, $width);

            if (!empty($gd_style)) {
                imagesetstyle($this->get_image(), $gd_style);
                $c = IMG_COLOR_STYLED;
            }
        }

        imagesetthickness($this->get_image(), isset($width) ? $width : 0);

        if ($fill) {
            if (version_compare(PHP_VERSION, "8.1.0", "<")) {
                imagefilledpolygon($this->get_image(), $points, count($points)/2, $c);
            } else {
                imagefilledpolygon($this->get_image(), $points, $c);
            }
        } else {
            if (version_compare(PHP_VERSION, "8.1.0", "<")) {
                imagepolygon($this->get_image(), $points, count($points)/2, $c);
            } else {
                imagepolygon($this->get_image(), $points, $c);
            }
        }
    }

    public function circle($x, $y, $r, $color, $width = null, $style = [], $fill = false)
    {
        // Scale by the AA factor and DPI
        $x = $this->_upscale($x);
        $y = $this->_upscale($y);
        $d = $this->_upscale(2 * $r);
        $width = isset($width) ? $this->_upscale($width) : null;

        $c = $this->_allocate_color($color);

        // Convert the style array if required
        if (is_array($style) && count($style) > 0 && isset($width) && !$fill) {
            $gd_style = $this->convertStyle($style, $c, $width);

            if (!empty($gd_style)) {
                imagesetstyle($this->get_image(), $gd_style);
                $c = IMG_COLOR_STYLED;
            }
        }

        imagesetthickness($this->get_image(), isset($width) ? $width : 0);

        if ($fill) {
            imagefilledellipse($this->get_image(), $x, $y, $d, $d, $c);
        } else {
            imageellipse($this->get_image(), $x, $y, $d, $d, $c);
        }
    }

    /**
     * @throws \Exception
     */
    public function image($img, $x, $y, $w, $h, $resolution = "normal")
    {
        $img_type = Cache::detect_type($img, $this->get_dompdf()->getHttpContext());

        if (!$img_type) {
            return;
        }

        $func_name = "imagecreatefrom$img_type";
        if (method_exists(Helpers::class, $func_name)) {
            $func_name = [Helpers::class, $func_name];
        } elseif (!function_exists($func_name)) {
            throw new \Exception("Function $func_name() not found.  Cannot convert $img_type image: $img.  Please install the image PHP extension.");
        }
        $src = @call_user_func($func_name, $img);

        if (!$src) {
            return; // Probably should add to $_dompdf_errors or whatever here
        }

        // Scale by the AA factor and DPI
        $x = $this->_upscale($x);
        $y = $this->_upscale($y);

        $w = $this->_upscale($w);
        $h = $this->_upscale($h);

        $img_w = imagesx($src);
        $img_h = imagesy($src);

        imagecopyresampled($this->get_image(), $src, $x, $y, 0, 0, $w, $h, $img_w, $img_h);
    }

    public function text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_spacing = 0.0, $char_spacing = 0.0, $angle = 0.0)
    {
        // Scale by the AA factor and DPI
        $x = $this->_upscale($x);
        $y = $this->_upscale($y);
        $size = $this->_upscale($size) * self::FONT_SCALE;

        $h = round($this->get_font_height_actual($font, $size));
        $c = $this->_allocate_color($color);

        // imagettftext() converts numeric entities to their respective
        // character. Preserve any originally double encoded entities to be
        // represented as is.
        // eg: &amp;#160; will render &#160; rather than its character.
        $text = preg_replace('/&(#(?:x[a-fA-F0-9]+|[0-9]+);)/', '&#38;\1', $text);

        $text = mb_encode_numericentity($text, [0x0080, 0xff, 0, 0xff], 'UTF-8');

        $font = $this->get_ttf_file($font);

        // FIXME: word spacing
        imagettftext($this->get_image(), $size, $angle, $x, $y + $h, $c, $font, $text);
    }

    public function javascript($code)
    {
        // Not implemented
    }

    public function add_named_dest($anchorname)
    {
        // Not implemented
    }

    public function add_link($url, $x, $y, $width, $height)
    {
        // Not implemented
    }

    public function add_info(string $label, string $value): void
    {
        // N/A
    }

    public function set_default_view($view, $options = [])
    {
        // N/A
    }

    private function getCharMap(string $font)
    {
        static $unicodeCharMapTables = [];

        if (isset($unicodeCharMapTables[$font])) {
            return $unicodeCharMapTables[$font];
        }

        $metrics_name = "$font.ufm";
        if (!file_exists($metrics_name)) {
            $metrics_name = "$font.afm";
        }
        if (!file_exists($metrics_name)) {
            return $unicodeCharMapTables[$font] = [];
        }

        $cache_name = "$metrics_name.json";
        if (file_exists($cache_name)) {
            $cached_font_info = json_decode(file_get_contents($cache_name), true);
            $char_map = $cached_font_info['C'];
            return $unicodeCharMapTables[$font] = $char_map;
        }

        $char_map = [];
        $file = file("$metrics_name");
        foreach ($file as $rowA) {
            $row = trim($rowA);
            $pos = strpos($row, ' ');

            if ($pos) {
                // then there must be some keyword
                $key = substr($row, 0, $pos);
                switch ($key) {
                    case 'C': // Found in AFM files
                        $bits = explode(';', trim($row));
                        $dtmp = ['C' => null, 'N' => null, 'WX' => null, 'B' => []];

                        foreach ($bits as $bit) {
                            $bits2 = explode(' ', trim($bit));
                            if (mb_strlen($bits2[0], '8bit') == 0) {
                                continue;
                            }

                            if (count($bits2) > 2) {
                                $dtmp[$bits2[0]] = [];
                                for ($i = 1; $i < count($bits2); $i++) {
                                    $dtmp[$bits2[0]][] = $bits2[$i];
                                }
                            } else {
                                if (count($bits2) == 2) {
                                    $dtmp[$bits2[0]] = $bits2[1];
                                }
                            }
                        }

                        $c = (int)$dtmp['C'];
                        $n = $dtmp['N'];
                        $width = floatval($dtmp['WX']);

                        if ($c >= 0) {
                            $char_map[$c] = $width;
                        } elseif (isset($n)) {
                            $char_map[$n] = $width;
                        }
                        break;

                    // U 827 ; WX 0 ; N squaresubnosp ; G 675 ;
                    case 'U': // Found in UFM files
                        $bits = explode(';', trim($row));
                        $dtmp = ['G' => null, 'N' => null, 'U' => null, 'WX' => null];

                        foreach ($bits as $bit) {
                            $bits2 = explode(' ', trim($bit));
                            if (mb_strlen($bits2[0], '8bit') === 0) {
                                continue;
                            }

                            if (count($bits2) > 2) {
                                $dtmp[$bits2[0]] = [];
                                for ($i = 1; $i < count($bits2); $i++) {
                                    $dtmp[$bits2[0]][] = $bits2[$i];
                                }
                            } else {
                                if (count($bits2) == 2) {
                                    $dtmp[$bits2[0]] = $bits2[1];
                                }
                            }
                        }

                        $c = (int)$dtmp['U'];
                        $n = $dtmp['N'];
                        $glyph = $dtmp['G'];
                        $width = floatval($dtmp['WX']);

                        if ($c >= 0) {
                            $char_map[$c] = $width;
                        } elseif (isset($n)) {
                            $char_map[$n] = $width;
                        }

                        break;
                }
            }
        }

        return $unicodeCharMapTables[$font] = $char_map;
    }

    public function font_supports_char(string $font, string $char): bool
    {
        if ($char === "") {
            return true;
        }

        $font = $this->get_ttf_file($font);
        $charMap = $this->getCharMap($font);
        $charCode = Helpers::uniord($char, "UTF-8");

        return \array_key_exists($charCode, $charMap);
    }

    public function get_text_width($text, $font, $size, $word_spacing = 0.0, $char_spacing = 0.0)
    {
        $font = $this->get_ttf_file($font);
        $size = $this->_upscale($size) * self::FONT_SCALE;

        // imagettfbbox() converts numeric entities to their respective
        // character. Preserve any originally double encoded entities to be
        // represented as is.
        // eg: &amp;#160; will render &#160; rather than its character.
        $text = preg_replace('/&(#(?:x[a-fA-F0-9]+|[0-9]+);)/', '&#38;\1', $text);

        $text = mb_encode_numericentity($text, [0x0080, 0xffff, 0, 0xffff], 'UTF-8');

        // FIXME: word spacing
        list($x1, , $x2) = imagettfbbox($size, 0, $font, $text);

        // Add additional 1pt to prevent text overflow issues
        return $this->_downscale($x2 - $x1) + 1;
    }

    /**
     * @param string|null $font
     * @return string
     */
    public function get_ttf_file($font)
    {
        if ($font === null) {
            $font = "";
        }

        if ( stripos($font, ".ttf") === false ) {
            $font .= ".ttf";
        }

        if (!file_exists($font)) {
            $font_metrics = $this->_dompdf->getFontMetrics();
            $font = $font_metrics->getFont($this->_dompdf->getOptions()->getDefaultFont()) . ".ttf";
            if (!file_exists($font)) {
                if (strpos($font, "mono")) {
                    $font = $font_metrics->getFont("DejaVu Mono") . ".ttf";
                } elseif (strpos($font, "sans") !== false) {
                    $font = $font_metrics->getFont("DejaVu Sans") . ".ttf";
                } elseif (strpos($font, "serif")) {
                    $font = $font_metrics->getFont("DejaVu Serif") . ".ttf";
                } else {
                    $font = $font_metrics->getFont("DejaVu Sans") . ".ttf";
                }
            }
        }

        return $font;
    }

    public function get_font_height($font, $size)
    {
        $size = $this->_upscale($size) * self::FONT_SCALE;

        $height = $this->get_font_height_actual($font, $size);

        return $this->_downscale($height);
    }

    /**
     * @param string $font
     * @param float  $size
     *
     * @return float
     */
    protected function get_font_height_actual($font, $size)
    {
        $font = $this->get_ttf_file($font);
        $ratio = $this->_dompdf->getOptions()->getFontHeightRatio();

        // FIXME: word spacing
        list(, $y2, , , , $y1) = imagettfbbox($size, 0, $font, "MXjpqytfhl"); // Test string with ascenders, descenders and caps
        return ($y2 - $y1) * $ratio;
    }

    public function get_font_baseline($font, $size)
    {
        $ratio = $this->_dompdf->getOptions()->getFontHeightRatio();
        return $this->get_font_height($font, $size) / $ratio;
    }

    public function new_page()
    {
        $this->_page_number++;
        $this->_page_count++;

        $this->_img = imagecreatetruecolor($this->_actual_width, $this->_actual_height);

        $this->_bg_color = $this->_allocate_color($this->_bg_color_array);
        imagealphablending($this->_img, true);
        imagesavealpha($this->_img, true);
        imagefill($this->_img, 0, 0, $this->_bg_color);

        $this->_imgs[] = $this->_img;
    }

    public function open_object()
    {
        // N/A
    }

    public function close_object()
    {
        // N/A
    }

    public function add_object()
    {
        // N/A
    }

    public function page_script($callback): void
    {
        // N/A
    }

    public function page_text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_space = 0.0, $char_space = 0.0, $angle = 0.0)
    {
        // N/A
    }

    public function page_line($x1, $y1, $x2, $y2, $color, $width, $style = [])
    {
        // N/A
    }

    /**
     * Streams the image to the client.
     *
     * @param string $filename The filename to present to the client.
     * @param array  $options  Associative array: 'type' => jpeg|jpg|png; 'quality' => 0 - 100 (JPEG only);
     *     'page' => Number of the page to output (defaults to the first); 'Attachment': 1 or 0 (default 1).
     */
    public function stream($filename, $options = [])
    {
        if (headers_sent()) {
            die("Unable to stream image: headers already sent");
        }

        if (!isset($options["type"])) $options["type"] = "png";
        if (!isset($options["Attachment"])) $options["Attachment"] = true;
        $type = strtolower($options["type"]);

        switch ($type) {
            case "jpg":
            case "jpeg":
                $contentType = "image/jpeg";
                $extension = ".jpg";
                break;
            case "png":
            default:
                $contentType = "image/png";
                $extension = ".png";
                break;
        }

        header("Cache-Control: private");
        header("Content-Type: $contentType");

        $filename = str_replace(["\n", "'"], "", basename($filename, ".$type")) . $extension;
        $attachment = $options["Attachment"] ? "attachment" : "inline";
        header(Helpers::buildContentDispositionHeader($attachment, $filename));

        $this->_output($options);
        flush();
    }

    /**
     * Returns the image as a string.
     *
     * @param array $options Associative array: 'type' => jpeg|jpg|png; 'quality' => 0 - 100 (JPEG only);
     *     'page' => Number of the page to output (defaults to the first).
     * @return string
     */
    public function output($options = [])
    {
        ob_start();

        $this->_output($options);

        return ob_get_clean();
    }

    /**
     * Outputs the image stream directly.
     *
     * @param array $options Associative array: 'type' => jpeg|jpg|png; 'quality' => 0 - 100 (JPEG only);
     *     'page' => Number of the page to output (defaults to the first).
     */
    protected function _output($options = [])
    {
        if (!isset($options["type"])) $options["type"] = "png";
        if (!isset($options["page"])) $options["page"] = 1;
        $type = strtolower($options["type"]);

        if (isset($this->_imgs[$options["page"] - 1])) {
            $img = $this->_imgs[$options["page"] - 1];
        } else {
            $img = $this->_imgs[0];
        }

        // Perform any antialiasing
        if ($this->_aa_factor != 1) {
            $dst_w = round($this->_actual_width / $this->_aa_factor);
            $dst_h = round($this->_actual_height / $this->_aa_factor);
            $dst = imagecreatetruecolor($dst_w, $dst_h);
            imagecopyresampled($dst, $img, 0, 0, 0, 0,
                $dst_w, $dst_h,
                $this->_actual_width, $this->_actual_height);
        } else {
            $dst = $img;
        }

        switch ($type) {
            case "jpg":
            case "jpeg":
                if (!isset($options["quality"])) {
                    $options["quality"] = 75;
                }

                imagejpeg($dst, null, $options["quality"]);
                break;
            case "png":
            default:
                imagepng($dst);
                break;
        }

        if ($this->_aa_factor != 1) {
            imagedestroy($dst);
        }
    }
}
