<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Adapter\CPDF;
use Dompdf\Css\Color;
use Dompdf\Css\Style;
use Dompdf\Dompdf;
use Dompdf\Helpers;
use Dompdf\Frame;
use Dompdf\Image\Cache;

/**
 * Base renderer class
 *
 * @package dompdf
 */
abstract class AbstractRenderer
{

    /**
     * Rendering backend
     *
     * @var \Dompdf\Canvas
     */
    protected $_canvas;

    /**
     * Current dompdf instance
     *
     * @var Dompdf
     */
    protected $_dompdf;

    /**
     * Class constructor
     *
     * @param Dompdf $dompdf The current dompdf instance
     */
    function __construct(Dompdf $dompdf)
    {
        $this->_dompdf = $dompdf;
        $this->_canvas = $dompdf->getCanvas();
    }

    /**
     * Render a frame.
     *
     * Specialized in child classes
     *
     * @param Frame $frame The frame to render
     */
    abstract function render(Frame $frame);

    /**
     * Render a background image over a rectangular area
     *
     * @param string $url   The background image to load
     * @param float $x      The left edge of the rectangular area
     * @param float $y      The top edge of the rectangular area
     * @param float $width  The width of the rectangular area
     * @param float $height The height of the rectangular area
     * @param Style $style  The associated Style object
     *
     * @throws \Exception
     */
    protected function _background_image($url, $x, $y, $width, $height, $style)
    {
        if (!function_exists("imagecreatetruecolor")) {
            throw new \Exception("The PHP GD extension is required, but is not installed.");
        }

        $sheet = $style->get_stylesheet();

        // Skip degenerate cases
        if ($width == 0 || $height == 0) {
            return;
        }

        $box_width = $width;
        $box_height = $height;

        //debugpng
        if ($this->_dompdf->getOptions()->getDebugPng()) {
            print '[_background_image ' . $url . ']';
        }

        list($img, $type, /*$msg*/) = Cache::resolve_url(
            $url,
            $sheet->get_protocol(),
            $sheet->get_host(),
            $sheet->get_base_path(),
            $this->_dompdf
        );

        // Bail if the image is no good
        if (Cache::is_broken($img)) {
            return;
        }

        //Try to optimize away reading and composing of same background multiple times
        //Postponing read with imagecreatefrom   ...()
        //final composition parameters and name not known yet
        //Therefore read dimension directly from file, instead of creating gd object first.
        //$img_w = imagesx($src); $img_h = imagesy($src);

        list($img_w, $img_h) = Helpers::dompdf_getimagesize($img, $this->_dompdf->getHttpContext());
        if (!isset($img_w) || $img_w == 0 || !isset($img_h) || $img_h == 0) {
            return;
        }

        $repeat = $style->background_repeat;
        $dpi = $this->_dompdf->getOptions()->getDpi();

        //Increase background resolution and dependent box size according to image resolution to be placed in
        //Then image can be copied in without resize
        $bg_width = round((float)($width * $dpi) / 72);
        $bg_height = round((float)($height * $dpi) / 72);

        //Need %bg_x, $bg_y as background pos, where img starts, converted to pixel

        list($bg_x, $bg_y) = $style->background_position;

        if (Helpers::is_percent($bg_x)) {
            // The point $bg_x % from the left edge of the image is placed
            // $bg_x % from the left edge of the background rectangle
            $p = ((float)$bg_x) / 100.0;
            $x1 = $p * $img_w;
            $x2 = $p * $bg_width;

            $bg_x = $x2 - $x1;
        } else {
            $bg_x = (float)($style->length_in_pt($bg_x) * $dpi) / 72;
        }

        $bg_x = round($bg_x + (float)$style->length_in_pt($style->border_left_width) * $dpi / 72);

        if (Helpers::is_percent($bg_y)) {
            // The point $bg_y % from the left edge of the image is placed
            // $bg_y % from the left edge of the background rectangle
            $p = ((float)$bg_y) / 100.0;
            $y1 = $p * $img_h;
            $y2 = $p * $bg_height;

            $bg_y = $y2 - $y1;
        } else {
            $bg_y = (float)($style->length_in_pt($bg_y) * $dpi) / 72;
        }

        $bg_y = round($bg_y + (float)$style->length_in_pt($style->border_top_width) * $dpi / 72);

        //clip background to the image area on partial repeat. Nothing to do if img off area
        //On repeat, normalize start position to the tile at immediate left/top or 0/0 of area
        //On no repeat with positive offset: move size/start to have offset==0
        //Handle x/y Dimensions separately

        if ($repeat !== "repeat" && $repeat !== "repeat-x") {
            //No repeat x
            if ($bg_x < 0) {
                $bg_width = $img_w + $bg_x;
            } else {
                $x += ($bg_x * 72) / $dpi;
                $bg_width = $bg_width - $bg_x;
                if ($bg_width > $img_w) {
                    $bg_width = $img_w;
                }
                $bg_x = 0;
            }

            if ($bg_width <= 0) {
                return;
            }

            $width = (float)($bg_width * 72) / $dpi;
        } else {
            //repeat x
            if ($bg_x < 0) {
                $bg_x = -((-$bg_x) % $img_w);
            } else {
                $bg_x = $bg_x % $img_w;
                if ($bg_x > 0) {
                    $bg_x -= $img_w;
                }
            }
        }

        if ($repeat !== "repeat" && $repeat !== "repeat-y") {
            //no repeat y
            if ($bg_y < 0) {
                $bg_height = $img_h + $bg_y;
            } else {
                $y += ($bg_y * 72) / $dpi;
                $bg_height = $bg_height - $bg_y;
                if ($bg_height > $img_h) {
                    $bg_height = $img_h;
                }
                $bg_y = 0;
            }
            if ($bg_height <= 0) {
                return;
            }
            $height = (float)($bg_height * 72) / $dpi;
        } else {
            //repeat y
            if ($bg_y < 0) {
                $bg_y = -((-$bg_y) % $img_h);
            } else {
                $bg_y = $bg_y % $img_h;
                if ($bg_y > 0) {
                    $bg_y -= $img_h;
                }
            }
        }

        //Optimization, if repeat has no effect
        if ($repeat === "repeat" && $bg_y <= 0 && $img_h + $bg_y >= $bg_height) {
            $repeat = "repeat-x";
        }

        if ($repeat === "repeat" && $bg_x <= 0 && $img_w + $bg_x >= $bg_width) {
            $repeat = "repeat-y";
        }

        if (($repeat === "repeat-x" && $bg_x <= 0 && $img_w + $bg_x >= $bg_width) ||
            ($repeat === "repeat-y" && $bg_y <= 0 && $img_h + $bg_y >= $bg_height)
        ) {
            $repeat = "no-repeat";
        }

        //Use filename as indicator only
        //different names for different variants to have different copies in the pdf
        //This is not dependent of background color of box! .'_'.(is_array($bg_color) ? $bg_color["hex"] : $bg_color)
        //Note: Here, bg_* are the start values, not end values after going through the tile loops!

        $filedummy = $img;

        $is_png = false;
        $filedummy .= '_' . $bg_width . '_' . $bg_height . '_' . $bg_x . '_' . $bg_y . '_' . $repeat;

        //Optimization to avoid multiple times rendering the same image.
        //If check functions are existing and identical image already cached,
        //then skip creation of duplicate, because it is not needed by addImagePng
        if ($this->_canvas instanceof CPDF && $this->_canvas->get_cpdf()->image_iscached($filedummy)) {
            $bg = null;
        } else {
            // Create a new image to fit over the background rectangle
            $bg = imagecreatetruecolor($bg_width, $bg_height);

            switch (strtolower($type)) {
                case "png":
                    $is_png = true;
                    imagesavealpha($bg, true);
                    imagealphablending($bg, false);
                    $src = imagecreatefrompng($img);
                    break;

                case "jpeg":
                    $src = imagecreatefromjpeg($img);
                    break;

                case "gif":
                    $src = imagecreatefromgif($img);
                    break;

                case "bmp":
                    $src = Helpers::imagecreatefrombmp($img);
                    break;

                default:
                    return; // Unsupported image type
            }

            if ($src == null) {
                return;
            }

            //Background color if box is not relevant here
            //Non transparent image: box clipped to real size. Background non relevant.
            //Transparent image: The image controls the transparency and lets shine through whatever background.
            //However on transparent image preset the composed image with the transparency color,
            //to keep the transparency when copying over the non transparent parts of the tiles.
            $ti = imagecolortransparent($src);
            $palletsize = imagecolorstotal($src);

            if ($ti >= 0 && $ti < $palletsize) {
                $tc = imagecolorsforindex($src, $ti);
                $ti = imagecolorallocate($bg, $tc['red'], $tc['green'], $tc['blue']);
                imagefill($bg, 0, 0, $ti);
                imagecolortransparent($bg, $ti);
            }

            //This has only an effect for the non repeatable dimension.
            //compute start of src and dest coordinates of the single copy
            if ($bg_x < 0) {
                $dst_x = 0;
                $src_x = -$bg_x;
            } else {
                $src_x = 0;
                $dst_x = $bg_x;
            }

            if ($bg_y < 0) {
                $dst_y = 0;
                $src_y = -$bg_y;
            } else {
                $src_y = 0;
                $dst_y = $bg_y;
            }

            //For historical reasons exchange meanings of variables:
            //start_* will be the start values, while bg_* will be the temporary start values in the loops
            $start_x = $bg_x;
            $start_y = $bg_y;

            // Copy regions from the source image to the background
            if ($repeat === "no-repeat") {
                // Simply place the image on the background
                imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $img_w, $img_h);

            } else if ($repeat === "repeat-x") {
                for ($bg_x = $start_x; $bg_x < $bg_width; $bg_x += $img_w) {
                    if ($bg_x < 0) {
                        $dst_x = 0;
                        $src_x = -$bg_x;
                        $w = $img_w + $bg_x;
                    } else {
                        $dst_x = $bg_x;
                        $src_x = 0;
                        $w = $img_w;
                    }
                    imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $w, $img_h);
                }
            } else if ($repeat === "repeat-y") {

                for ($bg_y = $start_y; $bg_y < $bg_height; $bg_y += $img_h) {
                    if ($bg_y < 0) {
                        $dst_y = 0;
                        $src_y = -$bg_y;
                        $h = $img_h + $bg_y;
                    } else {
                        $dst_y = $bg_y;
                        $src_y = 0;
                        $h = $img_h;
                    }
                    imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $img_w, $h);
                }
            } else if ($repeat === "repeat") {
                for ($bg_y = $start_y; $bg_y < $bg_height; $bg_y += $img_h) {
                    for ($bg_x = $start_x; $bg_x < $bg_width; $bg_x += $img_w) {
                        if ($bg_x < 0) {
                            $dst_x = 0;
                            $src_x = -$bg_x;
                            $w = $img_w + $bg_x;
                        } else {
                            $dst_x = $bg_x;
                            $src_x = 0;
                            $w = $img_w;
                        }

                        if ($bg_y < 0) {
                            $dst_y = 0;
                            $src_y = -$bg_y;
                            $h = $img_h + $bg_y;
                        } else {
                            $dst_y = $bg_y;
                            $src_y = 0;
                            $h = $img_h;
                        }
                        imagecopy($bg, $src, $dst_x, $dst_y, $src_x, $src_y, $w, $h);
                    }
                }
            } else {
                print 'Unknown repeat!';
            }

            imagedestroy($src);

        } /* End optimize away creation of duplicates */

        $this->_canvas->clipping_rectangle($x, $y, $box_width, $box_height);

        //img: image url string
        //img_w, img_h: original image size in px
        //width, height: box size in pt
        //bg_width, bg_height: box size in px
        //x, y: left/top edge of box on page in pt
        //start_x, start_y: placement of image relative to pattern
        //$repeat: repeat mode
        //$bg: GD object of result image
        //$src: GD object of original image
        //When using cpdf and optimization to direct png creation from gd object is available,
        //don't create temp file, but place gd object directly into the pdf
        if (!$is_png && $this->_canvas instanceof CPDF) {
            // Note: CPDF_Adapter image converts y position
            $this->_canvas->get_cpdf()->addImagePng($filedummy, $x, $this->_canvas->get_height() - $y - $height, $width, $height, $bg);
        } else {
            $tmp_dir = $this->_dompdf->getOptions()->getTempDir();
            $tmp_name = @tempnam($tmp_dir, "bg_dompdf_img_");
            @unlink($tmp_name);
            $tmp_file = "$tmp_name.png";

            //debugpng
            if ($this->_dompdf->getOptions()->getDebugPng()) {
                print '[_background_image ' . $tmp_file . ']';
            }

            imagepng($bg, $tmp_file);
            $this->_canvas->image($tmp_file, $x, $y, $width, $height);
            imagedestroy($bg);

            //debugpng
            if ($this->_dompdf->getOptions()->getDebugPng()) {
                print '[_background_image unlink ' . $tmp_file . ']';
            }

            if (!$this->_dompdf->getOptions()->getDebugKeepTemp()) {
                unlink($tmp_file);
            }
        }

        $this->_canvas->clipping_end();
    }

    /**
     * @param $style
     * @param $width
     * @return array
     */
    protected function _get_dash_pattern($style, $width)
    {
        $pattern = [];

        switch ($style) {
            default:
                /*case "solid":
                case "double":
                case "groove":
                case "inset":
                case "outset":
                case "ridge":*/
            case "none":
                break;

            case "dotted":
                if ($width <= 1) {
                    $pattern = [$width, $width * 2];
                } else {
                    $pattern = [$width];
                }
                break;

            case "dashed":
                $pattern = [3 * $width];
                break;
        }

        return $pattern;
    }

    /**
     * @param $x
     * @param $y
     * @param $length
     * @param $color
     * @param $widths
     * @param $side
     * @param string $corner_style
     * @param int $r1
     * @param int $r2
     */
    protected function _border_none($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        return;
    }

    /**
     * @param $x
     * @param $y
     * @param $length
     * @param $color
     * @param $widths
     * @param $side
     * @param string $corner_style
     * @param int $r1
     * @param int $r2
     */
    protected function _border_hidden($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        return;
    }

    // Border rendering functions

    /**
     * @param $x
     * @param $y
     * @param $length
     * @param $color
     * @param $widths
     * @param $side
     * @param string $corner_style
     * @param int $r1
     * @param int $r2
     */
    protected function _border_dotted($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        $this->_border_line($x, $y, $length, $color, $widths, $side, $corner_style, "dotted", $r1, $r2);
    }


    /**
     * @param $x
     * @param $y
     * @param $length
     * @param $color
     * @param $widths
     * @param $side
     * @param string $corner_style
     * @param int $r1
     * @param int $r2
     */
    protected function _border_dashed($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        $this->_border_line($x, $y, $length, $color, $widths, $side, $corner_style, "dashed", $r1, $r2);
    }


    /**
     * @param $x
     * @param $y
     * @param $length
     * @param $color
     * @param $widths
     * @param $side
     * @param string $corner_style
     * @param int $r1
     * @param int $r2
     */
    protected function _border_solid($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        // TODO: Solve rendering where one corner is beveled (radius == 0), one corner isn't.
        if ($corner_style !== "bevel" || $r1 > 0 || $r2 > 0) {
            // do it the simple way
            $this->_border_line($x, $y, $length, $color, $widths, $side, $corner_style, "solid", $r1, $r2);
            return;
        }

        list($top, $right, $bottom, $left) = $widths;

        // All this polygon business is for beveled corners...
        switch ($side) {
            case "top":
                $points = [$x, $y,
                    $x + $length, $y,
                    $x + $length - $right, $y + $top,
                    $x + $left, $y + $top];
                $this->_canvas->polygon($points, $color, null, null, true);
                break;

            case "bottom":
                $points = [$x, $y,
                    $x + $length, $y,
                    $x + $length - $right, $y - $bottom,
                    $x + $left, $y - $bottom];
                $this->_canvas->polygon($points, $color, null, null, true);
                break;

            case "left":
                $points = [$x, $y,
                    $x, $y + $length,
                    $x + $left, $y + $length - $bottom,
                    $x + $left, $y + $top];
                $this->_canvas->polygon($points, $color, null, null, true);
                break;

            case "right":
                $points = [$x, $y,
                    $x, $y + $length,
                    $x - $right, $y + $length - $bottom,
                    $x - $right, $y + $top];
                $this->_canvas->polygon($points, $color, null, null, true);
                break;

            default:
                return;
        }
    }

    /**
     * @param $side
     * @param $ratio
     * @param $top
     * @param $right
     * @param $bottom
     * @param $left
     * @param $x
     * @param $y
     * @param $length
     * @param $r1
     * @param $r2
     */
    protected function _apply_ratio($side, $ratio, $top, $right, $bottom, $left, &$x, &$y, &$length, &$r1, &$r2)
    {
        switch ($side) {
            case "top":
                $r1 -= $left * $ratio;
                $r2 -= $right * $ratio;
                $x += $left * $ratio;
                $y += $top * $ratio;
                $length -= $left * $ratio + $right * $ratio;
                break;

            case "bottom":
                $r1 -= $right * $ratio;
                $r2 -= $left * $ratio;
                $x += $left * $ratio;
                $y -= $bottom * $ratio;
                $length -= $left * $ratio + $right * $ratio;
                break;

            case "left":
                $r1 -= $top * $ratio;
                $r2 -= $bottom * $ratio;
                $x += $left * $ratio;
                $y += $top * $ratio;
                $length -= $top * $ratio + $bottom * $ratio;
                break;

            case "right":
                $r1 -= $bottom * $ratio;
                $r2 -= $top * $ratio;
                $x -= $right * $ratio;
                $y += $top * $ratio;
                $length -= $top * $ratio + $bottom * $ratio;
                break;

            default:
                return;
        }
    }

    /**
     * @param $x
     * @param $y
     * @param $length
     * @param $color
     * @param $widths
     * @param $side
     * @param string $corner_style
     * @param int $r1
     * @param int $r2
     */
    protected function _border_double($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        list($top, $right, $bottom, $left) = $widths;

        $third_widths = [$top / 3, $right / 3, $bottom / 3, $left / 3];

        // draw the outer border
        $this->_border_solid($x, $y, $length, $color, $third_widths, $side, $corner_style, $r1, $r2);

        $this->_apply_ratio($side, 2 / 3, $top, $right, $bottom, $left, $x, $y, $length, $r1, $r2);

        $this->_border_solid($x, $y, $length, $color, $third_widths, $side, $corner_style, $r1, $r2);
    }

    /**
     * @param $x
     * @param $y
     * @param $length
     * @param $color
     * @param $widths
     * @param $side
     * @param string $corner_style
     * @param int $r1
     * @param int $r2
     */
    protected function _border_groove($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        list($top, $right, $bottom, $left) = $widths;

        $half_widths = [$top / 2, $right / 2, $bottom / 2, $left / 2];

        $this->_border_inset($x, $y, $length, $color, $half_widths, $side, $corner_style, $r1, $r2);

        $this->_apply_ratio($side, 0.5, $top, $right, $bottom, $left, $x, $y, $length, $r1, $r2);

        $this->_border_outset($x, $y, $length, $color, $half_widths, $side, $corner_style, $r1, $r2);
    }

    /**
     * @param $x
     * @param $y
     * @param $length
     * @param $color
     * @param $widths
     * @param $side
     * @param string $corner_style
     * @param int $r1
     * @param int $r2
     */
    protected function _border_ridge($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        list($top, $right, $bottom, $left) = $widths;

        $half_widths = [$top / 2, $right / 2, $bottom / 2, $left / 2];

        $this->_border_outset($x, $y, $length, $color, $half_widths, $side, $corner_style, $r1, $r2);

        $this->_apply_ratio($side, 0.5, $top, $right, $bottom, $left, $x, $y, $length, $r1, $r2);

        $this->_border_inset($x, $y, $length, $color, $half_widths, $side, $corner_style, $r1, $r2);
    }

    /**
     * @param $c
     * @return mixed
     */
    protected function _tint($c)
    {
        if (!is_numeric($c)) {
            return $c;
        }

        return min(1, $c + 0.16);
    }

    /**
     * @param $c
     * @return mixed
     */
    protected function _shade($c)
    {
        if (!is_numeric($c)) {
            return $c;
        }

        return max(0, $c - 0.33);
    }

    /**
     * @param $x
     * @param $y
     * @param $length
     * @param $color
     * @param $widths
     * @param $side
     * @param string $corner_style
     * @param int $r1
     * @param int $r2
     */
    protected function _border_inset($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        switch ($side) {
            case "top":
            case "left":
                $shade = array_map([$this, "_shade"], $color);
                $this->_border_solid($x, $y, $length, $shade, $widths, $side, $corner_style, $r1, $r2);
                break;

            case "bottom":
            case "right":
                $tint = array_map([$this, "_tint"], $color);
                $this->_border_solid($x, $y, $length, $tint, $widths, $side, $corner_style, $r1, $r2);
                break;

            default:
                return;
        }
    }

    /**
     * @param $x
     * @param $y
     * @param $length
     * @param $color
     * @param $widths
     * @param $side
     * @param string $corner_style
     * @param int $r1
     * @param int $r2
     */
    protected function _border_outset($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $r1 = 0, $r2 = 0)
    {
        switch ($side) {
            case "top":
            case "left":
                $tint = array_map([$this, "_tint"], $color);
                $this->_border_solid($x, $y, $length, $tint, $widths, $side, $corner_style, $r1, $r2);
                break;

            case "bottom":
            case "right":
                $shade = array_map([$this, "_shade"], $color);
                $this->_border_solid($x, $y, $length, $shade, $widths, $side, $corner_style, $r1, $r2);
                break;

            default:
                return;
        }
    }

    /**
     * Draws a solid, dotted, or dashed line, observing the border radius
     *
     * @param $x
     * @param $y
     * @param $length
     * @param $color
     * @param $widths
     * @param $side
     * @param string $corner_style
     * @param $pattern_name
     * @param int $r1
     * @param int $r2
     *
     * @var $top
     */
    protected function _border_line($x, $y, $length, $color, $widths, $side, $corner_style = "bevel", $pattern_name, $r1 = 0, $r2 = 0)
    {
        /** used by $$side */
        list($top, $right, $bottom, $left) = $widths;
        $width = $$side;

        $pattern = $this->_get_dash_pattern($pattern_name, $width);

        $half_width = $width / 2;
        $r1 -= $half_width;
        $r2 -= $half_width;
        $adjust = $r1 / 80;
        $length -= $width;

        switch ($side) {
            case "top":
                $x += $half_width;
                $y += $half_width;

                if ($r1 > 0) {
                    $this->_canvas->arc($x + $r1, $y + $r1, $r1, $r1, 90 - $adjust, 135 + $adjust, $color, $width, $pattern);
                }

                $this->_canvas->line($x + $r1, $y, $x + $length - $r2, $y, $color, $width, $pattern);

                if ($r2 > 0) {
                    $this->_canvas->arc($x + $length - $r2, $y + $r2, $r2, $r2, 45 - $adjust, 90 + $adjust, $color, $width, $pattern);
                }
                break;

            case "bottom":
                $x += $half_width;
                $y -= $half_width;

                if ($r1 > 0) {
                    $this->_canvas->arc($x + $r1, $y - $r1, $r1, $r1, 225 - $adjust, 270 + $adjust, $color, $width, $pattern);
                }

                $this->_canvas->line($x + $r1, $y, $x + $length - $r2, $y, $color, $width, $pattern);

                if ($r2 > 0) {
                    $this->_canvas->arc($x + $length - $r2, $y - $r2, $r2, $r2, 270 - $adjust, 315 + $adjust, $color, $width, $pattern);
                }
                break;

            case "left":
                $y += $half_width;
                $x += $half_width;

                if ($r1 > 0) {
                    $this->_canvas->arc($x + $r1, $y + $r1, $r1, $r1, 135 - $adjust, 180 + $adjust, $color, $width, $pattern);
                }

                $this->_canvas->line($x, $y + $r1, $x, $y + $length - $r2, $color, $width, $pattern);

                if ($r2 > 0) {
                    $this->_canvas->arc($x + $r2, $y + $length - $r2, $r2, $r2, 180 - $adjust, 225 + $adjust, $color, $width, $pattern);
                }
                break;

            case "right":
                $y += $half_width;
                $x -= $half_width;

                if ($r1 > 0) {
                    $this->_canvas->arc($x - $r1, $y + $r1, $r1, $r1, 0 - $adjust, 45 + $adjust, $color, $width, $pattern);
                }

                $this->_canvas->line($x, $y + $r1, $x, $y + $length - $r2, $color, $width, $pattern);

                if ($r2 > 0) {
                    $this->_canvas->arc($x - $r2, $y + $length - $r2, $r2, $r2, 315 - $adjust, 360 + $adjust, $color, $width, $pattern);
                }
                break;
        }
    }

    /**
     * @param $opacity
     */
    protected function _set_opacity($opacity)
    {
        if (is_numeric($opacity) && $opacity <= 1.0 && $opacity >= 0.0) {
            $this->_canvas->set_opacity($opacity);
        }
    }

    /**
     * @param $box
     * @param string $color
     * @param array $style
     */
    protected function _debug_layout($box, $color = "red", $style = [])
    {
        $this->_canvas->rectangle($box[0], $box[1], $box[2], $box[3], Color::parse($color), 0.1, $style);
    }
}
