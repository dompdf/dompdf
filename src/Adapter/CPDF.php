<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Orion Richardson <orionr@yahoo.com>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

// FIXME: Need to sanity check inputs to this class
namespace Dompdf\Adapter;

use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\Helpers;
use Dompdf\Exception;
use Dompdf\Image\Cache;
use Dompdf\PhpEvaluator;
use FontLib\Exception\FontNotFoundException;

/**
 * PDF rendering interface
 *
 * Dompdf\Adapter\CPDF provides a simple stateless interface to the stateful one
 * provided by the Cpdf class.
 *
 * Unless otherwise mentioned, all dimensions are in points (1/72 in).  The
 * coordinate origin is in the top left corner, and y values increase
 * downwards.
 *
 * See {@link http://www.ros.co.nz/pdf/} for more complete documentation
 * on the underlying {@link Cpdf} class.
 *
 * @package dompdf
 */
class CPDF implements Canvas
{

    /**
     * Dimensions of paper sizes in points
     *
     * @var array;
     */
    static $PAPER_SIZES = [
        "4a0" => [0, 0, 4767.87, 6740.79],
        "2a0" => [0, 0, 3370.39, 4767.87],
        "a0" => [0, 0, 2383.94, 3370.39],
        "a1" => [0, 0, 1683.78, 2383.94],
        "a2" => [0, 0, 1190.55, 1683.78],
        "a3" => [0, 0, 841.89, 1190.55],
        "a4" => [0, 0, 595.28, 841.89],
        "a5" => [0, 0, 419.53, 595.28],
        "a6" => [0, 0, 297.64, 419.53],
        "a7" => [0, 0, 209.76, 297.64],
        "a8" => [0, 0, 147.40, 209.76],
        "a9" => [0, 0, 104.88, 147.40],
        "a10" => [0, 0, 73.70, 104.88],
        "b0" => [0, 0, 2834.65, 4008.19],
        "b1" => [0, 0, 2004.09, 2834.65],
        "b2" => [0, 0, 1417.32, 2004.09],
        "b3" => [0, 0, 1000.63, 1417.32],
        "b4" => [0, 0, 708.66, 1000.63],
        "b5" => [0, 0, 498.90, 708.66],
        "b6" => [0, 0, 354.33, 498.90],
        "b7" => [0, 0, 249.45, 354.33],
        "b8" => [0, 0, 175.75, 249.45],
        "b9" => [0, 0, 124.72, 175.75],
        "b10" => [0, 0, 87.87, 124.72],
        "c0" => [0, 0, 2599.37, 3676.54],
        "c1" => [0, 0, 1836.85, 2599.37],
        "c2" => [0, 0, 1298.27, 1836.85],
        "c3" => [0, 0, 918.43, 1298.27],
        "c4" => [0, 0, 649.13, 918.43],
        "c5" => [0, 0, 459.21, 649.13],
        "c6" => [0, 0, 323.15, 459.21],
        "c7" => [0, 0, 229.61, 323.15],
        "c8" => [0, 0, 161.57, 229.61],
        "c9" => [0, 0, 113.39, 161.57],
        "c10" => [0, 0, 79.37, 113.39],
        "ra0" => [0, 0, 2437.80, 3458.27],
        "ra1" => [0, 0, 1729.13, 2437.80],
        "ra2" => [0, 0, 1218.90, 1729.13],
        "ra3" => [0, 0, 864.57, 1218.90],
        "ra4" => [0, 0, 609.45, 864.57],
        "sra0" => [0, 0, 2551.18, 3628.35],
        "sra1" => [0, 0, 1814.17, 2551.18],
        "sra2" => [0, 0, 1275.59, 1814.17],
        "sra3" => [0, 0, 907.09, 1275.59],
        "sra4" => [0, 0, 637.80, 907.09],
        "letter" => [0, 0, 612.00, 792.00],
        "half-letter" => [0, 0, 396.00, 612.00],
        "legal" => [0, 0, 612.00, 1008.00],
        "ledger" => [0, 0, 1224.00, 792.00],
        "tabloid" => [0, 0, 792.00, 1224.00],
        "executive" => [0, 0, 521.86, 756.00],
        "folio" => [0, 0, 612.00, 936.00],
        "commercial #10 envelope" => [0, 0, 684, 297],
        "catalog #10 1/2 envelope" => [0, 0, 648, 864],
        "8.5x11" => [0, 0, 612.00, 792.00],
        "8.5x14" => [0, 0, 612.00, 1008.0],
        "11x17" => [0, 0, 792.00, 1224.00],
    ];

    /**
     * The Dompdf object
     *
     * @var Dompdf
     */
    protected $_dompdf;

    /**
     * Instance of Cpdf class
     *
     * @var Cpdf
     */
    protected $_pdf;

    /**
     * PDF width, in points
     *
     * @var float
     */
    protected $_width;

    /**
     * PDF height, in points
     *
     * @var float;
     */
    protected $_height;

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
     * Text to display on every page
     *
     * @var array
     */
    protected $_page_text;

    /**
     * Array of pages for accessing after rendering is initially complete
     *
     * @var array
     */
    protected $_pages;

    /**
     * Array of temporary cached images to be deleted when processing is complete
     *
     * @var array
     */
    protected $_image_cache;

    /**
     * Currently-applied opacity level (0 - 1)
     *
     * @var float
     */
    protected $_current_opacity = 1;

    /**
     * Class constructor
     *
     * @param mixed $paper The size of paper to use in this PDF ({@link CPDF::$PAPER_SIZES})
     * @param string $orientation The orientation of the document (either 'landscape' or 'portrait')
     * @param Dompdf $dompdf The Dompdf instance
     */
    public function __construct($paper = "letter", $orientation = "portrait", Dompdf $dompdf)
    {
        if (is_array($paper)) {
            $size = $paper;
        } else if (isset(self::$PAPER_SIZES[mb_strtolower($paper)])) {
            $size = self::$PAPER_SIZES[mb_strtolower($paper)];
        } else {
            $size = self::$PAPER_SIZES["letter"];
        }

        if (mb_strtolower($orientation) === "landscape") {
            [$size[2], $size[3]] = [$size[3], $size[2]];
        }

        $this->_dompdf = $dompdf;

        $this->_pdf = new \Dompdf\Cpdf(
            $size,
            true,
            $dompdf->getOptions()->getFontCache(),
            $dompdf->getOptions()->getTempDir()
        );

        $this->_pdf->addInfo("Producer", sprintf("%s + CPDF", $dompdf->version));
        $time = substr_replace(date('YmdHisO'), '\'', -2, 0) . '\'';
        $this->_pdf->addInfo("CreationDate", "D:$time");
        $this->_pdf->addInfo("ModDate", "D:$time");

        $this->_width = $size[2] - $size[0];
        $this->_height = $size[3] - $size[1];

        $this->_page_number = $this->_page_count = 1;
        $this->_page_text = [];

        $this->_pages = [$this->_pdf->getFirstPageId()];

        $this->_image_cache = [];
    }

    /**
     * @return Dompdf
     */
    public function get_dompdf()
    {
        return $this->_dompdf;
    }

    /**
     * Class destructor
     *
     * Deletes all temporary image files
     */
    public function __destruct()
    {
        foreach ($this->_image_cache as $img) {
            // The file might be already deleted by 3rd party tmp cleaner,
            // the file might not have been created at all
            // (if image outputting commands failed)
            // or because the destructor was called twice accidentally.
            if (!file_exists($img)) {
                continue;
            }

            if ($this->_dompdf->getOptions()->getDebugPng()) {
                print '[__destruct unlink ' . $img . ']';
            }
            if (!$this->_dompdf->getOptions()->getDebugKeepTemp()) {
                unlink($img);
            }
        }
    }

    /**
     * Returns the Cpdf instance
     *
     * @return \Dompdf\Cpdf
     */
    public function get_cpdf()
    {
        return $this->_pdf;
    }

    /**
     * Add meta information to the PDF
     *
     * @param string $label label of the value (Creator, Producer, etc.)
     * @param string $value the text to set
     */
    public function add_info($label, $value)
    {
        $this->_pdf->addInfo($label, $value);
    }

    /**
     * Opens a new 'object'
     *
     * While an object is open, all drawing actions are recorded in the object,
     * as opposed to being drawn on the current page.  Objects can be added
     * later to a specific page or to several pages.
     *
     * The return value is an integer ID for the new object.
     *
     * @see CPDF::close_object()
     * @see CPDF::add_object()
     *
     * @return int
     */
    public function open_object()
    {
        $ret = $this->_pdf->openObject();
        $this->_pdf->saveState();
        return $ret;
    }

    /**
     * Reopens an existing 'object'
     *
     * @see CPDF::open_object()
     * @param int $object the ID of a previously opened object
     */
    public function reopen_object($object)
    {
        $this->_pdf->reopenObject($object);
        $this->_pdf->saveState();
    }

    /**
     * Closes the current 'object'
     *
     * @see CPDF::open_object()
     */
    public function close_object()
    {
        $this->_pdf->restoreState();
        $this->_pdf->closeObject();
    }

    /**
     * Adds a specified 'object' to the document
     *
     * $object int specifying an object created with {@link
     * CPDF::open_object()}.  $where can be one of:
     * - 'add' add to current page only
     * - 'all' add to every page from the current one onwards
     * - 'odd' add to all odd numbered pages from now on
     * - 'even' add to all even numbered pages from now on
     * - 'next' add the object to the next page only
     * - 'nextodd' add to all odd numbered pages from the next one
     * - 'nexteven' add to all even numbered pages from the next one
     *
     * @see Cpdf::addObject()
     *
     * @param int $object
     * @param string $where
     */
    public function add_object($object, $where = 'all')
    {
        $this->_pdf->addObject($object, $where);
    }

    /**
     * Stops the specified 'object' from appearing in the document.
     *
     * The object will stop being displayed on the page following the current
     * one.
     *
     * @param int $object
     */
    public function stop_object($object)
    {
        $this->_pdf->stopObject($object);
    }

    /**
     * @access private
     */
    public function serialize_object($id)
    {
        // Serialize the pdf object's current state for retrieval later
        return $this->_pdf->serializeObject($id);
    }

    /**
     * @access private
     */
    public function reopen_serialized_object($obj)
    {
        return $this->_pdf->restoreSerializedObject($obj);
    }

    //........................................................................

    /**
     * Returns the PDF's width in points
     * @return float
     */
    public function get_width()
    {
        return $this->_width;
    }

    /**
     * Returns the PDF's height in points
     * @return float
     */
    public function get_height()
    {
        return $this->_height;
    }

    /**
     * Returns the current page number
     * @return int
     */
    public function get_page_number()
    {
        return $this->_page_number;
    }

    /**
     * Returns the total number of pages in the document
     * @return int
     */
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

    /**
     * Sets the page count
     *
     * @param int $count
     */
    public function set_page_count($count)
    {
        $this->_page_count = $count;
    }

    /**
     * Sets the stroke color
     *
     * See {@link Style::set_color()} for the format of the color array.
     * @param array $color
     */
    protected function _set_stroke_color($color)
    {
        $this->_pdf->setStrokeColor($color);
        $alpha = isset($color["alpha"]) ? $color["alpha"] : 1;
        if ($this->_current_opacity != 1) {
            $alpha *= $this->_current_opacity;
        }
        $this->_set_line_transparency("Normal", $alpha);
    }

    /**
     * Sets the fill colour
     *
     * See {@link Style::set_color()} for the format of the colour array.
     * @param array $color
     */
    protected function _set_fill_color($color)
    {
        $this->_pdf->setColor($color);
        $alpha = isset($color["alpha"]) ? $color["alpha"] : 1;
        if ($this->_current_opacity) {
            $alpha *= $this->_current_opacity;
        }
        $this->_set_fill_transparency("Normal", $alpha);
    }

    /**
     * Sets line transparency
     * @see Cpdf::setLineTransparency()
     *
     * Valid blend modes are (case-sensitive):
     *
     * Normal, Multiply, Screen, Overlay, Darken, Lighten,
     * ColorDodge, ColorBurn, HardLight, SoftLight, Difference,
     * Exclusion
     *
     * @param string $mode the blending mode to use
     * @param float $opacity 0.0 fully transparent, 1.0 fully opaque
     */
    protected function _set_line_transparency($mode, $opacity)
    {
        $this->_pdf->setLineTransparency($mode, $opacity);
    }

    /**
     * Sets fill transparency
     * @see Cpdf::setFillTransparency()
     *
     * Valid blend modes are (case-sensitive):
     *
     * Normal, Multiply, Screen, Overlay, Darken, Lighten,
     * ColorDogde, ColorBurn, HardLight, SoftLight, Difference,
     * Exclusion
     *
     * @param string $mode the blending mode to use
     * @param float $opacity 0.0 fully transparent, 1.0 fully opaque
     */
    protected function _set_fill_transparency($mode, $opacity)
    {
        $this->_pdf->setFillTransparency($mode, $opacity);
    }

    /**
     * Sets the line style
     *
     * @see Cpdf::setLineStyle()
     *
     * @param float $width
     * @param string $cap
     * @param string $join
     * @param array $dash
     */
    protected function _set_line_style($width, $cap, $join, $dash)
    {
        $this->_pdf->setLineStyle($width, $cap, $join, $dash);
    }

    /**
     * Sets the opacity
     *
     * @param $opacity
     * @param $mode
     */
    public function set_opacity($opacity, $mode = "Normal")
    {
        $this->_set_line_transparency($mode, $opacity);
        $this->_set_fill_transparency($mode, $opacity);
        $this->_current_opacity = $opacity;
    }

    public function set_default_view($view, $options = [])
    {
        array_unshift($options, $view);
        call_user_func_array([$this->_pdf, "openHere"], $options);
    }

    /**
     * Remaps y coords from 4th to 1st quadrant
     *
     * @param float $y
     * @return float
     */
    protected function y($y)
    {
        return $this->_height - $y;
    }

    /**
     * Canvas implementation
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param array $color
     * @param float $width
     * @param array $style
     */
    public function line($x1, $y1, $x2, $y2, $color, $width, $style = [])
    {
        $this->_set_stroke_color($color);
        $this->_set_line_style($width, "butt", "", $style);

        $this->_pdf->line($x1, $this->y($y1),
            $x2, $this->y($y2));
        $this->_set_line_transparency("Normal", $this->_current_opacity);
    }

    /**
     * Draw line at the specified coordinates on every page.
     *
     * See {@link Style::munge_color()} for the format of the colour array.
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param array $color
     * @param float $width
     * @param array $style optional
     */
    public function page_line($x1, $y1, $x2, $y2, $color, $width, $style = [])
    {
        $_t = 'line';
        $this->_page_text[] = compact('_t', 'x1', 'y1', 'x2', 'y2', 'color', 'width', 'style');
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $r1
     * @param float $r2
     * @param float $astart
     * @param float $aend
     * @param array $color
     * @param float $width
     * @param array $style
     */
    public function arc($x, $y, $r1, $r2, $astart, $aend, $color, $width, $style = [])
    {
        $this->_set_stroke_color($color);
        $this->_set_line_style($width, "butt", "", $style);

        $this->_pdf->ellipse($x, $this->y($y), $r1, $r2, 0, 8, $astart, $aend, false, false, true, false);
        $this->_set_line_transparency("Normal", $this->_current_opacity);
    }

    /**
     * Convert a GIF or BMP image to a PNG image
     *
     * @param string $image_url
     * @param integer $type
     *
     * @throws Exception
     * @return string The url of the newly converted image
     */
    protected function _convert_gif_bmp_to_png($image_url, $type)
    {
        $func_name = "imagecreatefrom$type";

        if (!function_exists($func_name)) {
            if (!method_exists(Helpers::class, $func_name)) {
                throw new Exception("Function $func_name() not found.  Cannot convert $type image: $image_url.  Please install the image PHP extension.");
            }
            $func_name = "\\Dompdf\\Helpers::" . $func_name;
        }

        set_error_handler([Helpers::class, 'record_warnings']);

        try {
            $im = call_user_func($func_name, $image_url);

            if ($im) {
                imageinterlace($im, false);

                $tmp_dir = $this->_dompdf->getOptions()->getTempDir();
                $tmp_name = @tempnam($tmp_dir, "{$type}dompdf_img_");
                @unlink($tmp_name);
                $filename = "$tmp_name.png";
                $this->_image_cache[] = $filename;

                imagepng($im, $filename);
                imagedestroy($im);
            } else {
                $filename = Cache::$broken_image;
            }
        } finally {
            restore_error_handler();
        }

        return $filename;
    }

    /**
     * @param float $x1
     * @param float $y1
     * @param float $w
     * @param float $h
     * @param array $color
     * @param float $width
     * @param array $style
     */
    public function rectangle($x1, $y1, $w, $h, $color, $width, $style = [])
    {
        $this->_set_stroke_color($color);
        $this->_set_line_style($width, "butt", "", $style);
        $this->_pdf->rectangle($x1, $this->y($y1) - $h, $w, $h);
        $this->_set_line_transparency("Normal", $this->_current_opacity);
    }

    /**
     * @param float $x1
     * @param float $y1
     * @param float $w
     * @param float $h
     * @param array $color
     */
    public function filled_rectangle($x1, $y1, $w, $h, $color)
    {
        $this->_set_fill_color($color);
        $this->_pdf->filledRectangle($x1, $this->y($y1) - $h, $w, $h);
        $this->_set_fill_transparency("Normal", $this->_current_opacity);
    }

    /**
     * @param float $x1
     * @param float $y1
     * @param float $w
     * @param float $h
     */
    public function clipping_rectangle($x1, $y1, $w, $h)
    {
        $this->_pdf->clippingRectangle($x1, $this->y($y1) - $h, $w, $h);
    }

    /**
     * @param float $x1
     * @param float $y1
     * @param float $w
     * @param float $h
     * @param float $rTL
     * @param float $rTR
     * @param float $rBR
     * @param float $rBL
     */
    public function clipping_roundrectangle($x1, $y1, $w, $h, $rTL, $rTR, $rBR, $rBL)
    {
        $this->_pdf->clippingRectangleRounded($x1, $this->y($y1) - $h, $w, $h, $rTL, $rTR, $rBR, $rBL);
    }

    /**
     *
     */
    public function clipping_end()
    {
        $this->_pdf->clippingEnd();
    }

    /**
     *
     */
    public function save()
    {
        $this->_pdf->saveState();
    }

    /**
     *
     */
    public function restore()
    {
        $this->_pdf->restoreState();
    }

    /**
     * @param $angle
     * @param $x
     * @param $y
     */
    public function rotate($angle, $x, $y)
    {
        $this->_pdf->rotate($angle, $x, $y);
    }

    /**
     * @param $angle_x
     * @param $angle_y
     * @param $x
     * @param $y
     */
    public function skew($angle_x, $angle_y, $x, $y)
    {
        $this->_pdf->skew($angle_x, $angle_y, $x, $y);
    }

    /**
     * @param $s_x
     * @param $s_y
     * @param $x
     * @param $y
     */
    public function scale($s_x, $s_y, $x, $y)
    {
        $this->_pdf->scale($s_x, $s_y, $x, $y);
    }

    /**
     * @param $t_x
     * @param $t_y
     */
    public function translate($t_x, $t_y)
    {
        $this->_pdf->translate($t_x, $t_y);
    }

    /**
     * @param $a
     * @param $b
     * @param $c
     * @param $d
     * @param $e
     * @param $f
     */
    public function transform($a, $b, $c, $d, $e, $f)
    {
        $this->_pdf->transform([$a, $b, $c, $d, $e, $f]);
    }

    /**
     * @param array $points
     * @param array $color
     * @param null $width
     * @param array $style
     * @param bool $fill
     */
    public function polygon($points, $color, $width = null, $style = [], $fill = false)
    {
        $this->_set_fill_color($color);
        $this->_set_stroke_color($color);

        // Adjust y values
        for ($i = 1; $i < count($points); $i += 2) {
            $points[$i] = $this->y($points[$i]);
        }

        $this->_pdf->polygon($points, count($points) / 2, $fill);

        $this->_set_fill_transparency("Normal", $this->_current_opacity);
        $this->_set_line_transparency("Normal", $this->_current_opacity);
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $r1
     * @param array $color
     * @param null $width
     * @param null $style
     * @param bool $fill
     */
    public function circle($x, $y, $r1, $color, $width = null, $style = null, $fill = false)
    {
        $this->_set_fill_color($color);
        $this->_set_stroke_color($color);

        if (!$fill && isset($width)) {
            $this->_set_line_style($width, "round", "round", $style);
        }

        $this->_pdf->ellipse($x, $this->y($y), $r1, 0, 0, 8, 0, 360, 1, $fill);

        $this->_set_fill_transparency("Normal", $this->_current_opacity);
        $this->_set_line_transparency("Normal", $this->_current_opacity);
    }

    /**
     * @param string $img
     * @param float $x
     * @param float $y
     * @param int $w
     * @param int $h
     * @param string $resolution
     */
    public function image($img, $x, $y, $w, $h, $resolution = "normal")
    {
        [$width, $height, $type] = Helpers::dompdf_getimagesize($img, $this->get_dompdf()->getHttpContext());

        $debug_png = $this->_dompdf->getOptions()->getDebugPng();

        if ($debug_png) {
            print "[image:$img|$width|$height|$type]";
        }

        switch ($type) {
            case "jpeg":
                if ($debug_png) {
                    print '!!!jpg!!!';
                }
                $this->_pdf->addJpegFromFile($img, $x, $this->y($y) - $h, $w, $h);
                break;

            case "gif":
            /** @noinspection PhpMissingBreakStatementInspection */
            case "bmp":
                if ($debug_png) print '!!!bmp or gif!!!';
                // @todo use cache for BMP and GIF
                $img = $this->_convert_gif_bmp_to_png($img, $type);

            case "png":
                if ($debug_png) print '!!!png!!!';

                $this->_pdf->addPngFromFile($img, $x, $this->y($y) - $h, $w, $h);
                break;

            case "svg":
                if ($debug_png) print '!!!SVG!!!';

                $this->_pdf->addSvgFromFile($img, $x, $this->y($y) - $h, $w, $h);
                break;

            default:
                if ($debug_png) print '!!!unknown!!!';
        }
    }

    public function select($x, $y, $w, $h, $font, $size, $color = [0, 0, 0], $opts = [])
    {
        $pdf = $this->_pdf;

        $font .= ".afm";
        $pdf->selectFont($font);

        if (!isset($pdf->acroFormId)) {
            $pdf->addForm();
        }

        $ft = \Dompdf\Cpdf::ACROFORM_FIELD_CHOICE;
        $ff = \Dompdf\Cpdf::ACROFORM_FIELD_CHOICE_COMBO;

        $id = $pdf->addFormField($ft, rand(), $x, $this->y($y) - $h, $x + $w, $this->y($y), $ff, $size, $color);
        $pdf->setFormFieldOpt($id, $opts);
    }

    public function textarea($x, $y, $w, $h, $font, $size, $color = [0, 0, 0])
    {
        $pdf = $this->_pdf;

        $font .= ".afm";
        $pdf->selectFont($font);

        if (!isset($pdf->acroFormId)) {
            $pdf->addForm();
        }

        $ft = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT;
        $ff = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT_MULTILINE;

        $pdf->addFormField($ft, rand(), $x, $this->y($y) - $h, $x + $w, $this->y($y), $ff, $size, $color);
    }

    public function input($x, $y, $w, $h, $type, $font, $size, $color = [0, 0, 0])
    {
        $pdf = $this->_pdf;

        $font .= ".afm";
        $pdf->selectFont($font);

        if (!isset($pdf->acroFormId)) {
            $pdf->addForm();
        }

        $ft = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT;
        $ff = 0;

        switch($type) {
            case 'text':
                $ft = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT;
                break;
            case 'password':
                $ft = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT;
                $ff = \Dompdf\Cpdf::ACROFORM_FIELD_TEXT_PASSWORD;
                break;
            case 'submit':
                $ft = \Dompdf\Cpdf::ACROFORM_FIELD_BUTTON;
                break;
        }

        $pdf->addFormField($ft, rand(), $x, $this->y($y) - $h, $x + $w, $this->y($y), $ff, $size, $color);
    }

    /**
     * @param float $x
     * @param float $y
     * @param string $text
     * @param string $font
     * @param float $size
     * @param array $color
     * @param float $word_space
     * @param float $char_space
     * @param float $angle
     */
    public function text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_space = 0.0, $char_space = 0.0, $angle = 0.0)
    {
        $pdf = $this->_pdf;

        $this->_set_fill_color($color);

        $is_font_subsetting = $this->_dompdf->getOptions()->getIsFontSubsettingEnabled();
        $pdf->selectFont($font . '.afm', '', true, $is_font_subsetting);

        $pdf->addText($x, $this->y($y) - $pdf->getFontHeight($size), $size, $text, $angle, $word_space, $char_space);

        $this->_set_fill_transparency("Normal", $this->_current_opacity);
    }

    /**
     * @param string $code
     */
    public function javascript($code)
    {
        $this->_pdf->addJavascript($code);
    }

    //........................................................................

    /**
     * Add a named destination (similar to <a name="foo">...</a> in html)
     *
     * @param string $anchorname The name of the named destination
     */
    public function add_named_dest($anchorname)
    {
        $this->_pdf->addDestination($anchorname, "Fit");
    }

    /**
     * Add a link to the pdf
     *
     * @param string $url The url to link to
     * @param float $x The x position of the link
     * @param float $y The y position of the link
     * @param float $width The width of the link
     * @param float $height The height of the link
     */
    public function add_link($url, $x, $y, $width, $height)
    {
        $y = $this->y($y) - $height;

        if (strpos($url, '#') === 0) {
            // Local link
            $name = substr($url, 1);
            if ($name) {
                $this->_pdf->addInternalLink($name, $x, $y, $x + $width, $y + $height);
            }
        } else {
            $this->_pdf->addLink(rawurldecode($url), $x, $y, $x + $width, $y + $height);
        }
    }

    /**
     * @param string $text
     * @param string $font
     * @param float $size
     * @param int $word_spacing
     * @param int $char_spacing
     * @return float|int
     */
    public function get_text_width($text, $font, $size, $word_spacing = 0, $char_spacing = 0)
    {
        $this->_pdf->selectFont($font, '', true, $this->_dompdf->getOptions()->getIsFontSubsettingEnabled());
        return $this->_pdf->getTextWidth($size, $text, $word_spacing, $char_spacing);
    }

    /**
     * @param $font
     * @param $string
     */
    public function register_string_subset($font, $string)
    {
        $this->_pdf->registerText($font, $string);
    }

    /**
     * @param string $font
     * @param float $size
     * @return float|int
     * @throws FontNotFoundException
     */
    public function get_font_height($font, $size)
    {
        $options = $this->_dompdf->getOptions();
        $this->_pdf->selectFont($font, '', true, $options->getIsFontSubsettingEnabled());

        return $this->_pdf->getFontHeight($size) * $options->getFontHeightRatio();
    }

    /*function get_font_x_height($font, $size) {
      $this->_pdf->selectFont($font);
      $ratio = $this->_dompdf->getOptions()->getFontHeightRatio();
      return $this->_pdf->getFontXHeight($size) * $ratio;
    }*/

    /**
     * @param string $font
     * @param float $size
     * @return float
     */
    public function get_font_baseline($font, $size)
    {
        $ratio = $this->_dompdf->getOptions()->getFontHeightRatio();
        return $this->get_font_height($font, $size) / $ratio;
    }

    /**
     * Writes text at the specified x and y coordinates on every page
     *
     * The strings '{PAGE_NUM}' and '{PAGE_COUNT}' are automatically replaced
     * with their current values.
     *
     * See {@link Style::munge_color()} for the format of the colour array.
     *
     * @param float $x
     * @param float $y
     * @param string $text the text to write
     * @param string $font the font file to use
     * @param float $size the font size, in points
     * @param array $color
     * @param float $word_space word spacing adjustment
     * @param float $char_space char spacing adjustment
     * @param float $angle angle to write the text at, measured CW starting from the x-axis
     */
    public function page_text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_space = 0.0, $char_space = 0.0, $angle = 0.0)
    {
        $_t = "text";
        $this->_page_text[] = compact("_t", "x", "y", "text", "font", "size", "color", "word_space", "char_space", "angle");
    }

    /**
     * Processes a script on every page
     *
     * The variables $pdf, $PAGE_NUM, and $PAGE_COUNT are available.
     *
     * This function can be used to add page numbers to all pages
     * after the first one, for example.
     *
     * @param string $code the script code
     * @param string $type the language type for script
     */
    public function page_script($code, $type = "text/php")
    {
        $_t = "script";
        $this->_page_text[] = compact("_t", "code", "type");
    }

    /**
     * @return int
     */
    public function new_page()
    {
        $this->_page_number++;
        $this->_page_count++;

        $ret = $this->_pdf->newPage();
        $this->_pages[] = $ret;
        return $ret;
    }

    /**
     * Add text to each page after rendering is complete
     */
    protected function _add_page_text()
    {
        if (!count($this->_page_text)) {
            return;
        }

        $page_number = 1;
        $eval = null;

        foreach ($this->_pages as $pid) {
            $this->reopen_object($pid);

            foreach ($this->_page_text as $pt) {
                extract($pt);

                switch ($_t) {
                    case "text":
                        $text = str_replace(["{PAGE_NUM}", "{PAGE_COUNT}"],
                            [$page_number, $this->_page_count], $text);
                        $this->text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
                        break;

                    case "script":
                        if (!$eval) {
                            $eval = new PhpEvaluator($this);
                        }
                        $eval->evaluate($code, ['PAGE_NUM' => $page_number, 'PAGE_COUNT' => $this->_page_count]);
                        break;

                    case 'line':
                        $this->line( $x1, $y1, $x2, $y2, $color, $width, $style );
                        break;
                }
            }

            $this->close_object();
            $page_number++;
        }
    }

    /**
     * Streams the PDF to the client.
     *
     * @param string $filename The filename to present to the client.
     * @param array $options Associative array: 'compress' => 1 or 0 (default 1); 'Attachment' => 1 or 0 (default 1).
     */
    public function stream($filename = "document.pdf", $options = [])
    {
        if (headers_sent()) {
            die("Unable to stream pdf: headers already sent");
        }

        if (!isset($options["compress"])) $options["compress"] = true;
        if (!isset($options["Attachment"])) $options["Attachment"] = true;

        $this->_add_page_text();

        $debug = !$options['compress'];
        $tmp = ltrim($this->_pdf->output($debug));

        header("Cache-Control: private");
        header("Content-Type: application/pdf");
        header("Content-Length: " . mb_strlen($tmp, "8bit"));

        $filename = str_replace(["\n", "'"], "", basename($filename, ".pdf")) . ".pdf";
        $attachment = $options["Attachment"] ? "attachment" : "inline";
        header(Helpers::buildContentDispositionHeader($attachment, $filename));

        echo $tmp;
        flush();
    }

    /**
     * Returns the PDF as a string.
     *
     * @param array $options Associative array: 'compress' => 1 or 0 (default 1).
     * @return string
     */
    public function output($options = [])
    {
        if (!isset($options["compress"])) $options["compress"] = true;

        $this->_add_page_text();

        $debug = !$options['compress'];

        return $this->_pdf->output($debug);
    }

    /**
     * Returns logging messages generated by the Cpdf class
     *
     * @return string
     */
    public function get_messages()
    {
        return $this->_pdf->messages;
    }
}
