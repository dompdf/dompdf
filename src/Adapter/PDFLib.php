<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace Dompdf\Adapter;

use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\Helpers;
use Dompdf\Exception;
use Dompdf\Image\Cache;
use Dompdf\PhpEvaluator;

/**
 * PDF rendering interface
 *
 * Dompdf\Adapter\PDFLib provides a simple, stateless interface to the one
 * provided by PDFLib.
 *
 * Unless otherwise mentioned, all dimensions are in points (1/72 in).
 * The coordinate origin is in the top left corner and y values
 * increase downwards.
 *
 * See {@link http://www.pdflib.com/} for more complete documentation
 * on the underlying PDFlib functions.
 *
 * @package dompdf
 */
class PDFLib implements Canvas
{

    /**
     * Dimensions of paper sizes in points
     *
     * @var array;
     */
    public static $PAPER_SIZES = array(); // Set to Dompdf\Adapter\CPDF::$PAPER_SIZES below.

    /**
     * Whether to create PDFs in memory or on disk
     *
     * @var bool
     */
    static $IN_MEMORY = true;

    /**
     * Saves the major version of PDFLib for compatibility requests
     *
     * @var null|int
     */
    private static $MAJOR_VERSION = null;


    /**
     * Transforms the list of native fonts into PDFLib compatible names (casesensitive)
     *
     * @var array
     */
    public static $nativeFontsTpPDFLib = array(
        "courier"               => "Courier",
        "courier-bold"          => "Courier-Bold",
        "courier-oblique"       => "Courier-Oblique",
        "courier-boldoblique"   => "Courier-BoldOblique",
        "helvetica"             => "Helvetica",
        "helvetica-bold"        => "Helvetica-Bold",
        "helvetica-oblique"     => "Helvetica-Oblique",
        "helvetica-boldoblique" => "Helvetica-BoldOblique",
        "times"                 => "Times-Roman",
        "times-roman"           => "Times-Roman",
        "times-bold"            => "Times-Bold",
        "times-italic"          => "Times-Italic",
        "times-bolditalic"      => "Times-BoldItalic",
        "symbol"                => "Symbol",
        "zapfdinbats"           => "ZapfDingbats",
        "zapfdingbats"          => "ZapfDingbats",
    );

    /**
     * @var \Dompdf\Dompdf
     */
    private $_dompdf;

    /**
     * Instance of PDFLib class
     *
     * @var \PDFLib
     */
    private $_pdf;

    /**
     * Name of temporary file used for PDFs created on disk
     *
     * @var string
     */
    private $_file;

    /**
     * PDF width, in points
     *
     * @var float
     */
    private $_width;

    /**
     * PDF height, in points
     *
     * @var float
     */
    private $_height;

    /**
     * Last fill color used
     *
     * @var array
     */
    private $_last_fill_color;

    /**
     * Last stroke color used
     *
     * @var array
     */
    private $_last_stroke_color;

    /**
     * The current opacity level
     *
     * @var array
     */
    private $_current_opacity;

    /**
     * Cache of image handles
     *
     * @var array
     */
    private $_imgs;

    /**
     * Cache of font handles
     *
     * @var array
     */
    private $_fonts;

    /**
     * Cache of fontFile checks
     *
     * @var array
     */
    private $_fontsFiles;

    /**
     * List of objects (templates) to add to multiple pages
     *
     * @var array
     */
    private $_objs;

    /**
     * List of gstate objects created for this PDF (for reuse)
     *
     * @var array
     */
    private $_gstates = array();

    /**
     * Current page number
     *
     * @var int
     */
    private $_page_number;

    /**
     * Total number of pages
     *
     * @var int
     */
    private $_page_count;

    /**
     * Text to display on every page
     *
     * @var array
     */
    private $_page_text;

    /**
     * Array of pages for accesing after rendering is initially complete
     *
     * @var array
     */
    private $_pages;

    /**
     * Class constructor
     *
     * @param string|array $paper The size of paper to use either a string (see {@link Dompdf\Adapter\CPDF::$PAPER_SIZES}) or
     *                            an array(xmin,ymin,xmax,ymax)
     * @param string $orientation The orientation of the document (either 'landscape' or 'portrait')
     * @param Dompdf $dompdf
     */
    public function __construct($paper = "letter", $orientation = "portrait", Dompdf $dompdf)
    {
        if (is_array($paper)) {
            $size = $paper;
        } elseif (isset(self::$PAPER_SIZES[mb_strtolower($paper)])) {
            $size = self::$PAPER_SIZES[mb_strtolower($paper)];
        } else {
            $size = self::$PAPER_SIZES["letter"];
        }

        if (mb_strtolower($orientation) === "landscape") {
            list($size[2], $size[3]) = array($size[3], $size[2]);
        }

        $this->_width = $size[2] - $size[0];
        $this->_height = $size[3] - $size[1];

        $this->_dompdf = $dompdf;

        $this->_pdf = new \PDFLib();

        $license = $dompdf->getOptions()->getPdflibLicense();
        if (strlen($license) > 0) {
            $this->setPDFLibParameter("license", $license);
        }

        $this->setPDFLibParameter("textformat", "utf8");
        if ($this->getPDFLibMajorVersion() >= 7) {
            $this->setPDFLibParameter("errorpolicy", "return");
            //            $this->_pdf->set_option('logging={filename=' . \APP_PATH . '/logs/pdflib.log classes={api=1 warning=2}}');
            //            $this->_pdf->set_option('errorpolicy=exception');
        } else {
            $this->setPDFLibParameter("fontwarning", "false");
        }

        $searchPath = $this->_dompdf->getOptions()->getFontDir();
        if (empty($searchPath) === false) {
            $this->_pdf->set_option('searchpath={' . $searchPath . '}');
        }

        // fetch PDFLib version information for the producer field
        $this->_pdf->set_info("Producer Addendum", sprintf("%s + PDFLib %s", $dompdf->version, $this->getPDFLibMajorVersion()));

        // Silence pedantic warnings about missing TZ settings
        $tz = @date_default_timezone_get();
        date_default_timezone_set("UTC");
        $this->_pdf->set_info("Date", date("Y-m-d"));
        date_default_timezone_set($tz);

        if (self::$IN_MEMORY) {
            $this->_pdf->begin_document("", "");
        } else {
            $tmp_dir = $this->_dompdf->getOptions()->getTempDir();
            $tmp_name = @tempnam($tmp_dir, "libdompdf_pdf_");
            @unlink($tmp_name);
            $this->_file = "$tmp_name.pdf";
            $this->_pdf->begin_document($this->_file, "");
        }

        $this->_pdf->begin_page_ext($this->_width, $this->_height, "");

        $this->_page_number = $this->_page_count = 1;
        $this->_page_text = array();

        $this->_imgs = array();
        $this->_fonts = array();
        $this->_objs = array();
    }

    /**
     * @return Dompdf
     */
    function get_dompdf()
    {
        return $this->_dompdf;
    }

    /**
     * Close the pdf
     */
    protected function _close()
    {
        $this->_place_objects();

        // Close all pages
        $this->_pdf->suspend_page("");
        for ($p = 1; $p <= $this->_page_count; $p++) {
            $this->_pdf->resume_page("pagenumber=$p");
            $this->_pdf->end_page_ext("");
        }

        $this->_pdf->end_document("");
    }


    /**
     * Returns the PDFLib instance
     *
     * @return PDFLib
     */
    public function get_pdflib()
    {
        return $this->_pdf;
    }

    /**
     * Add meta information to the PDF
     *
     * @param string $label label of the value (Creator, Producter, etc.)
     * @param string $value the text to set
     */
    public function add_info($label, $value)
    {
        $this->_pdf->set_info($label, $value);
    }

    /**
     * Opens a new 'object' (template in PDFLib-speak)
     *
     * While an object is open, all drawing actions are recorded to the
     * object instead of being drawn on the current page.  Objects can
     * be added later to a specific page or to several pages.
     *
     * The return value is an integer ID for the new object.
     *
     * @see PDFLib::close_object()
     * @see PDFLib::add_object()
     *
     * @return int
     */
    public function open_object()
    {
        $this->_pdf->suspend_page("");
        if ($this->getPDFLibMajorVersion() >= 7) {
            $ret = $this->_pdf->begin_template_ext($this->_width, $this->_height, null);
        } else {
            $ret = $this->_pdf->begin_template($this->_width, $this->_height);
        }
        $this->_pdf->save();
        $this->_objs[$ret] = array("start_page" => $this->_page_number);

        return $ret;
    }

    /**
     * Reopen an existing object (NOT IMPLEMENTED)
     * PDFLib does not seem to support reopening templates.
     *
     * @param int $object the ID of a previously opened object
     *
     * @throws Exception
     * @return void
     */
    public function reopen_object($object)
    {
        throw new Exception("PDFLib does not support reopening objects.");
    }

    /**
     * Close the current template
     *
     * @see PDFLib::open_object()
     */
    public function close_object()
    {
        $this->_pdf->restore();
        if ($this->getPDFLibMajorVersion() >= 7) {
            $this->_pdf->end_template_ext($this->_width, $this->_height);
        } else {
            $this->_pdf->end_template();
        }
        $this->_pdf->resume_page("pagenumber=" . $this->_page_number);
    }

    /**
     * Adds the specified object to the document
     *
     * $where can be one of:
     * - 'add' add to current page only
     * - 'all' add to every page from the current one onwards
     * - 'odd' add to all odd numbered pages from now on
     * - 'even' add to all even numbered pages from now on
     * - 'next' add the object to the next page only
     * - 'nextodd' add to all odd numbered pages from the next one
     * - 'nexteven' add to all even numbered pages from the next one
     *
     * @param int    $object the object handle returned by open_object()
     * @param string $where
     */
    public function add_object($object, $where = 'all')
    {

        if (mb_strpos($where, "next") !== false) {
            $this->_objs[$object]["start_page"]++;
            $where = str_replace("next", "", $where);
            if ($where == "") {
                $where = "add";
            }
        }

        $this->_objs[$object]["where"] = $where;
    }

    /**
     * Stops the specified template from appearing in the document.
     *
     * The object will stop being displayed on the page following the
     * current one.
     *
     * @param int $object
     */
    public function stop_object($object)
    {

        if (!isset($this->_objs[$object])) {
            return;
        }

        $start = $this->_objs[$object]["start_page"];
        $where = $this->_objs[$object]["where"];

        // Place the object on this page if required
        if ($this->_page_number >= $start &&
            (($this->_page_number % 2 == 0 && $where === "even") ||
                ($this->_page_number % 2 == 1 && $where === "odd") ||
                ($where === "all"))
        ) {
            $this->_pdf->fit_image($object, 0, 0, "");
        }

        $this->_objs[$object] = null;
        unset($this->_objs[$object]);
    }

    /**
     * Add all active objects to the current page
     */
    protected function _place_objects()
    {

        foreach ($this->_objs as $obj => $props) {
            $start = $props["start_page"];
            $where = $props["where"];

            // Place the object on this page if required
            if ($this->_page_number >= $start &&
                (($this->_page_number % 2 == 0 && $where === "even") ||
                    ($this->_page_number % 2 == 1 && $where === "odd") ||
                    ($where === "all"))
            ) {
                $this->_pdf->fit_image($obj, 0, 0, "");
            }
        }
    }

    /**
     * @return float|mixed
     */
    public function get_width()
    {
        return $this->_width;
    }

    /**
     * @return float|mixed
     */
    public function get_height()
    {
        return $this->_height;
    }

    /**
     * @return int
     */
    public function get_page_number()
    {
        return $this->_page_number;
    }

    /**
     * @return int
     */
    public function get_page_count()
    {
        return $this->_page_count;
    }

    /**
     * @param $num
     */
    public function set_page_number($num)
    {
        $this->_page_number = (int)$num;
    }

    /**
     * @param int $count
     */
    public function set_page_count($count)
    {
        $this->_page_count = (int)$count;
    }

    /**
     * Sets the line style
     *
     * @param float  $width
     * @param        $cap
     * @param string $join
     * @param array  $dash
     *
     * @return void
     */
    protected function _set_line_style($width, $cap, $join, $dash)
    {
        if (!is_array($dash)) {
            $dash = array();
        }

        if (count($dash) == 1) {
            $dash[] = $dash[0];
        }

        if ($this->getPDFLibMajorVersion() >= 9) {
            if (count($dash) > 1) {
                $this->_pdf->set_graphics_option("dasharray={" . implode(" ", $dash) . "}");
            } else {
                $this->_pdf->set_graphics_option("dasharray=none");
            }
        } else {
            if (count($dash) > 1) {
                $this->_pdf->setdashpattern("dasharray={" . implode(" ", $dash) . "}");
            } else {
                $this->_pdf->setdash(0, 0);
            }
        }

        switch ($join) {
            case "miter":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linejoin=0');
                } else {
                    $this->_pdf->setlinejoin(0);
                }
                break;

            case "round":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linejoin=1');
                } else {
                    $this->_pdf->setlinejoin(1);
                }
                break;

            case "bevel":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linejoin=2');
                } else {
                    $this->_pdf->setlinejoin(2);
                }
                break;

            default:
                break;
        }

        switch ($cap) {
            case "butt":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linecap=0');
                } else {
                    $this->_pdf->setlinecap(0);
                }
                break;

            case "round":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linecap=1');
                } else {
                    $this->_pdf->setlinecap(1);
                }
                break;

            case "square":
                if ($this->getPDFLibMajorVersion() >= 9) {
                    $this->_pdf->set_graphics_option('linecap=2');
                } else {
                    $this->_pdf->setlinecap(2);
                }
                break;

            default:
                break;
        }

        $this->_pdf->setlinewidth($width);
    }

    /**
     * Sets the line color
     *
     * @param array $color array(r,g,b)
     */
    protected function _set_stroke_color($color)
    {
        // TODO: we should check the current PDF stroke color
        // instead of the cached value
        if ($this->_last_stroke_color == $color) {
            // FIXME: do nothing, this optimization is broken by the
            // stroke being set as a side effect of other operations
            //return;
        }

        $alpha = isset($color["alpha"]) ? $color["alpha"] : 1;
        if (isset($this->_current_opacity)) {
            $alpha *= $this->_current_opacity;
        }

        $this->_last_stroke_color = $color;

        if (isset($color[3])) {
            $type = "cmyk";
            list($c1, $c2, $c3, $c4) = array($color[0], $color[1], $color[2], $color[3]);
        } elseif (isset($color[2])) {
            $type = "rgb";
            list($c1, $c2, $c3, $c4) = array($color[0], $color[1], $color[2], null);
        } else {
            $type = "gray";
            list($c1, $c2, $c3, $c4) = array($color[0], $color[1], null, null);
        }

        $this->_set_stroke_opacity($alpha, "Normal");
        $this->_pdf->setcolor("stroke", $type, $c1, $c2, $c3, $c4);
    }

    /**
     * Sets the fill color
     *
     * @param array $color array(r,g,b)
     */
    protected function _set_fill_color($color)
    {
        // TODO: we should check the current PDF fill color
        // instead of the cached value
        if ($this->_last_fill_color == $color) {
            // FIXME: do nothing, this optimization is broken by the
            // fill being set as a side effect of other operations
            //return;
        }

        $alpha = isset($color["alpha"]) ? $color["alpha"] : 1;
        if (isset($this->_current_opacity)) {
            $alpha *= $this->_current_opacity;
        }

        $this->_last_fill_color = $color;

        if (isset($color[3])) {
            $type = "cmyk";
            list($c1, $c2, $c3, $c4) = array($color[0], $color[1], $color[2], $color[3]);
        } elseif (isset($color[2])) {
            $type = "rgb";
            list($c1, $c2, $c3, $c4) = array($color[0], $color[1], $color[2], null);
        } else {
            $type = "gray";
            list($c1, $c2, $c3, $c4) = array($color[0], $color[1], null, null);
        }

        $this->_set_fill_opacity($alpha, "Normal");
        $this->_pdf->setcolor("fill", $type, $c1, $c2, $c3, $c4);
    }

    /**
     * Sets the fill opacity
     *
     * @param $opacity
     * @param $mode
     */
    public function _set_fill_opacity($opacity, $mode = "Normal")
    {
        if ($mode === "Normal" && is_null($opacity) === false) {
            $this->_set_gstate("opacityfill=$opacity");
        }
    }

    /**
     * Sets the stroke opacity
     *
     * @param $opacity
     * @param $mode
     */
    public function _set_stroke_opacity($opacity, $mode = "Normal")
    {
        if ($mode === "Normal" && is_null($opacity) === false) {
            $this->_set_gstate("opacitystroke=$opacity");
        }
    }

    /**
     * Sets the opacity
     *
     * @param $opacity
     * @param $mode
     */
    public function set_opacity($opacity, $mode = "Normal")
    {
        if ($mode === "Normal" && is_null($opacity) === false) {
            $this->_set_gstate("opacityfill=$opacity opacitystroke=$opacity");
            $this->_current_opacity = $opacity;
        }
    }

    /**
     * Sets the gstate
     *
     * @param $gstate_options
     * @return int
     */
    public function _set_gstate($gstate_options)
    {
        if (($gstate = array_search($gstate_options, $this->_gstates)) === false) {
            $gstate = $this->_pdf->create_gstate($gstate_options);
            $this->_gstates[$gstate] = $gstate_options;
        }

        return $this->_pdf->set_gstate($gstate);
    }

    public function set_default_view($view, $options = array())
    {
        // TODO
        // http://www.pdflib.com/fileadmin/pdflib/pdf/manuals/PDFlib-8.0.2-API-reference.pdf
        /**
         * fitheight Fit the page height to the window, with the x coordinate left at the left edge of the window.
         * fitrect Fit the rectangle specified by left, bottom, right, and top to the window.
         * fitvisible Fit the visible contents of the page (the ArtBox) to the window.
         * fitvisibleheight Fit the visible contents of the page to the window with the x coordinate left at the left edge of the window.
         * fitvisiblewidth Fit the visible contents of the page to the window with the y coordinate top at the top edge of the window.
         * fitwidth Fit the page width to the window, with the y coordinate top at the top edge of the window.
         * fitwindow Fit the complete page to the window.
         * fixed
         */
        //$this->setPDFLibParameter("openaction", $view);
    }

    /**
     * Loads a specific font and stores the corresponding descriptor.
     *
     * @param string $font
     * @param string $encoding
     * @param string $options
     *
     * @return int the font descriptor for the font
     */
    protected function _load_font($font, $encoding = null, $options = "")
    {
        // Fix for PDFLibs case-sensitive font names
        $baseFont = basename($font);
        $isNativeFont = false;
        if (isset(self::$nativeFontsTpPDFLib[$baseFont])) {
            $font = self::$nativeFontsTpPDFLib[$baseFont];
            $isNativeFont = true;
        }

        // Check if the font is a native PDF font
        // Embed non-native fonts
        $test = strtolower($baseFont);
        if (in_array($test, DOMPDF::$nativeFonts)) {
            $font = basename($font);
        } else {
            // Embed non-native fonts
            $options .= " embedding=true";
        }

        if (is_null($encoding)) {
            // Unicode encoding is only available for the commerical
            // version of PDFlib and not PDFlib-Lite
            if (strlen($this->_dompdf->getOptions()->getPdflibLicense()) > 0) {
                $encoding = "unicode";
            } else {
                $encoding = "auto";
            }
        }

        $key = "$font:$encoding:$options";
        if (isset($this->_fonts[$key])) {
            return $this->_fonts[$key];
        }

        // Native fonts are build in, just load it
        if ($isNativeFont) {
            $this->_fonts[$key] = $this->_pdf->load_font($font, $encoding, $options);

            return $this->_fonts[$key];
        }

        $fontOutline = $this->getPDFLibParameter("FontOutline", 1);
        if ($fontOutline === "" || $fontOutline <= 0) {
            $families = $this->_dompdf->getFontMetrics()->getFontFamilies();
            foreach ($families as $files) {
                foreach ($files as $file) {
                    $face = basename($file);
                    $afm = null;

                    if (isset($this->_fontsFiles[$face])) {
                        continue;
                    }

                    // Prefer ttfs to afms
                    if (file_exists("$file.ttf")) {
                        $outline = "$file.ttf";
                    } elseif (file_exists("$file.TTF")) {
                        $outline = "$file.TTF";
                    } elseif (file_exists("$file.pfb")) {
                        $outline = "$file.pfb";
                        if (file_exists("$file.afm")) {
                            $afm = "$file.afm";
                        }
                    } elseif (file_exists("$file.PFB")) {
                        $outline = "$file.PFB";
                        if (file_exists("$file.AFM")) {
                            $afm = "$file.AFM";
                        }
                    } else {
                        continue;
                    }

                    $this->_fontsFiles[$face] = true;

                    if ($this->getPDFLibMajorVersion() >= 9) {
                        $this->setPDFLibParameter("FontOutline", '{' . "$face=$outline" . '}');
                    } else {
                        $this->setPDFLibParameter("FontOutline", "\{$face\}=\{$outline\}");
                    }

                    if (is_null($afm)) {
                        continue;
                    }
                    if ($this->getPDFLibMajorVersion() >= 9) {
                        $this->setPDFLibParameter("FontAFM", '{' . "$face=$afm" . '}');
                    } else {
                        $this->setPDFLibParameter("FontAFM", "\{$face\}=\{$afm\}");
                    }
                }
            }
        }

        $this->_fonts[$key] = $this->_pdf->load_font($font, $encoding, $options);

        return $this->_fonts[$key];
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
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param array $color
     * @param float $width
     * @param array $style
     */
    public function line($x1, $y1, $x2, $y2, $color, $width, $style = null)
    {
        $this->_set_line_style($width, "butt", "", $style);
        $this->_set_stroke_color($color);

        $y1 = $this->y($y1);
        $y2 = $this->y($y2);

        $this->_pdf->moveto($x1, $y1);
        $this->_pdf->lineto($x2, $y2);
        $this->_pdf->stroke();

        $this->_set_stroke_opacity($this->_current_opacity, "Normal");
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
    public function page_line($x1, $y1, $x2, $y2, $color, $width, $style = array())
    {
        $_t = 'line';
        $this->_page_text[] = compact('_t', 'x1', 'y1', 'x2', 'y2', 'color', 'width', 'style');
    }

    /**
     * @param float $x1
     * @param float $y1
     * @param float $r1
     * @param float $r2
     * @param float $astart
     * @param float $aend
     * @param array $color
     * @param float $width
     * @param array $style
     */
    public function arc($x1, $y1, $r1, $r2, $astart, $aend, $color, $width, $style = array())
    {
        $this->_set_line_style($width, "butt", "", $style);
        $this->_set_stroke_color($color);

        $y1 = $this->y($y1);

        $this->_pdf->arc($x1, $y1, $r1, $astart, $aend);
        $this->_pdf->stroke();

        $this->_set_stroke_opacity($this->_current_opacity, "Normal");
    }

    /**
     * @param float $x1
     * @param float $y1
     * @param float $w
     * @param float $h
     * @param array $color
     * @param float $width
     * @param null  $style
     */
    public function rectangle($x1, $y1, $w, $h, $color, $width, $style = null)
    {
        $this->_set_stroke_color($color);
        $this->_set_line_style($width, "butt", "", $style);

        $y1 = $this->y($y1) - $h;

        $this->_pdf->rect($x1, $y1, $w, $h);
        $this->_pdf->stroke();

        $this->_set_stroke_opacity($this->_current_opacity, "Normal");
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

        $y1 = $this->y($y1) - $h;

        $this->_pdf->rect(floatval($x1), floatval($y1), floatval($w), floatval($h));
        $this->_pdf->fill();

        $this->_set_fill_opacity($this->_current_opacity, "Normal");
    }

    /**
     * @param float $x1
     * @param float $y1
     * @param float $w
     * @param float $h
     */
    public function clipping_rectangle($x1, $y1, $w, $h)
    {
        $this->_pdf->save();

        $y1 = $this->y($y1) - $h;

        $this->_pdf->rect(floatval($x1), floatval($y1), floatval($w), floatval($h));
        $this->_pdf->clip();
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
        if ($this->getPDFLibMajorVersion() < 9) {
            //TODO: add PDFLib7 support
            $this->clipping_rectangle($x1, $y1, $w, $h);
            return;
        }

        $this->_pdf->save();

        // we use 0,0 for the base coordinates for the path points
        // since we're drawing the path at the $x1,$y1 coordinates

        $path = 0;
        //start: left edge, top end
        $path = $this->_pdf->add_path_point($path, 0, 0 - $rTL + $h, "move", "");
        // line: left edge, bottom end
        $path = $this->_pdf->add_path_point($path, 0, 0 + $rBL, "line", "");
        // curve: bottom-left corner
        $path = $this->_pdf->add_path_point($path, 0 + $rBL, 0, "elliptical", "radius=$rBL clockwise=false");
        // line: bottom edge, left end
        $path = $this->_pdf->add_path_point($path, 0 - $rBR + $w, 0, "line", "");
        // curve: bottom-right corner
        $path = $this->_pdf->add_path_point($path, 0 + $w, 0 + $rBR, "elliptical", "radius=$rBR clockwise=false");
        // line: right edge, top end
        $path = $this->_pdf->add_path_point($path, 0 + $w, 0 - $rTR + $h, "line", "");
        // curve: top-right corner
        $path = $this->_pdf->add_path_point($path, 0 - $rTR + $w, 0 +$h, "elliptical", "radius=$rTR clockwise=false");
        // line: top edge, left end
        $path = $this->_pdf->add_path_point($path, 0 + $rTL, 0 + $h, "line", "");
        // curve: top-left corner
        $path = $this->_pdf->add_path_point($path, 0, 0 - $rTL + $h, "elliptical", "radius=$rTL clockwise=false");
        $this->_pdf->draw_path($path, $x1, $this->_height-$y1-$h, "clip=true");
    }

    /**
     *
     */
    public function clipping_end()
    {
        $this->_pdf->restore();
    }

    /**
     *
     */
    public function save()
    {
        $this->_pdf->save();
    }

    function restore()
    {
        $this->_pdf->restore();
    }

    /**
     * @param $angle
     * @param $x
     * @param $y
     */
    public function rotate($angle, $x, $y)
    {
        $pdf = $this->_pdf;
        $pdf->translate($x, $this->_height - $y);
        $pdf->rotate(-$angle);
        $pdf->translate(-$x, -$this->_height + $y);
    }

    /**
     * @param $angle_x
     * @param $angle_y
     * @param $x
     * @param $y
     */
    public function skew($angle_x, $angle_y, $x, $y)
    {
        $pdf = $this->_pdf;
        $pdf->translate($x, $this->_height - $y);
        $pdf->skew($angle_y, $angle_x); // Needs to be inverted
        $pdf->translate(-$x, -$this->_height + $y);
    }

    /**
     * @param $s_x
     * @param $s_y
     * @param $x
     * @param $y
     */
    public function scale($s_x, $s_y, $x, $y)
    {
        $pdf = $this->_pdf;
        $pdf->translate($x, $this->_height - $y);
        $pdf->scale($s_x, $s_y);
        $pdf->translate(-$x, -$this->_height + $y);
    }

    /**
     * @param $t_x
     * @param $t_y
     */
    public function translate($t_x, $t_y)
    {
        $this->_pdf->translate($t_x, -$t_y);
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
        $this->_pdf->concat($a, $b, $c, $d, $e, $f);
    }

    /**
     * @param array $points
     * @param array $color
     * @param null  $width
     * @param null  $style
     * @param bool  $fill
     */
    public function polygon($points, $color, $width = null, $style = null, $fill = false)
    {
        $this->_set_fill_color($color);
        $this->_set_stroke_color($color);

        if (!$fill && isset($width)) {
            $this->_set_line_style($width, "square", "miter", $style);
        }

        $y = $this->y(array_pop($points));
        $x = array_pop($points);
        $this->_pdf->moveto($x, $y);

        while (count($points) > 1) {
            $y = $this->y(array_pop($points));
            $x = array_pop($points);
            $this->_pdf->lineto($x, $y);
        }

        if ($fill) {
            $this->_pdf->fill();
        } else {
            $this->_pdf->closepath_stroke();
        }

        $this->_set_fill_opacity($this->_current_opacity, "Normal");
        $this->_set_stroke_opacity($this->_current_opacity, "Normal");
    }

    /**
     * @param float $x
     * @param float $y
     * @param float $r
     * @param array $color
     * @param null  $width
     * @param null  $style
     * @param bool  $fill
     */
    public function circle($x, $y, $r, $color, $width = null, $style = null, $fill = false)
    {
        $this->_set_fill_color($color);
        $this->_set_stroke_color($color);

        if (!$fill && isset($width)) {
            $this->_set_line_style($width, "round", "round", $style);
        }

        $y = $this->y($y);

        $this->_pdf->circle($x, $y, $r);

        if ($fill) {
            $this->_pdf->fill();
        } else {
            $this->_pdf->stroke();
        }

        $this->_set_fill_opacity($this->_current_opacity, "Normal");
        $this->_set_stroke_opacity($this->_current_opacity, "Normal");
    }

    /**
     * @param string $img_url
     * @param float  $x
     * @param float  $y
     * @param int    $w
     * @param int    $h
     * @param string $resolution
     */
    public function image($img_url, $x, $y, $w, $h, $resolution = "normal")
    {
        $w = (int)$w;
        $h = (int)$h;

        $img_type = Cache::detect_type($img_url, $this->get_dompdf()->getHttpContext());

        if (!isset($this->_imgs[$img_url])) {
            if (strtolower($img_type) === "svg") {
                //FIXME: PDFLib loads SVG but returns error message "Function must not be called in 'page' scope"
                $image_load_response = $this->_pdf->load_graphics($img_type, $img_url, "");
            } else {
                $image_load_response = $this->_pdf->load_image($img_type, $img_url, "");
            }
            if ($image_load_response === 0) {
                //TODO: should do something with the error message
                $error = $this->_pdf->get_errmsg();
                return;
            }
            $this->_imgs[$img_url] = $image_load_response;
        }

        $img = $this->_imgs[$img_url];

        $y = $this->y($y) - $h;
        if (strtolower($img_type) === "svg") {
            $this->_pdf->fit_graphics($img, $x, $y, 'boxsize={' . "$w $h" . '} fitmethod=entire');
        } else {
            $this->_pdf->fit_image($img, $x, $y, 'boxsize={' . "$w $h" . '} fitmethod=entire');
        }
    }

    /**
     * @param float  $x
     * @param float  $y
     * @param string $text
     * @param string $font
     * @param float  $size
     * @param array  $color
     * @param int    $word_spacing
     * @param int    $char_spacing
     * @param int    $angle
     */
    public function text($x, $y, $text, $font, $size, $color = array(0, 0, 0), $word_spacing = 0, $char_spacing = 0, $angle = 0)
    {
        $fh = $this->_load_font($font);

        $this->_pdf->setfont($fh, $size);
        $this->_set_fill_color($color);

        $y = $this->y($y) - $this->get_font_height($font, $size);

        $word_spacing = (float)$word_spacing;
        $char_spacing = (float)$char_spacing;
        $angle = -(float)$angle;

        $this->_pdf->fit_textline($text, $x, $y, "rotate=$angle wordspacing=$word_spacing charspacing=$char_spacing ");

        $this->_set_fill_opacity($this->_current_opacity, "Normal");
    }

    /**
     * @param string $code
     */
    public function javascript($code)
    {
        if (strlen($this->_dompdf->getOptions()->getPdflibLicense()) > 0) {
            $this->_pdf->create_action("JavaScript", $code);
        }
    }

    /**
     * Add a named destination (similar to <a name="foo">...</a> in html)
     *
     * @param string $anchorname The name of the named destination
     */
    public function add_named_dest($anchorname)
    {
        $this->_pdf->add_nameddest($anchorname, "");
    }

    /**
     * Add a link to the pdf
     *
     * @param string $url    The url to link to
     * @param float  $x      The x position of the link
     * @param float  $y      The y position of the link
     * @param float  $width  The width of the link
     * @param float  $height The height of the link
     */
    public function add_link($url, $x, $y, $width, $height)
    {
        $y = $this->y($y) - $height;
        if (strpos($url, '#') === 0) {
            // Local link
            $name = substr($url, 1);
            if ($name) {
                $this->_pdf->create_annotation($x, $y, $x + $width, $y + $height, 'Link',
                    "contents={$url} destname=" . substr($url, 1) . " linewidth=0");
            }
        } else {
            list($proto, $host, $path, $file) = Helpers::explode_url($url);

            if ($proto == "" || $proto === "file://") {
                return; // Local links are not allowed
            }
            $url = Helpers::build_url($proto, $host, $path, $file);
            $url = '{' . rawurldecode($url) . '}';

            $action = $this->_pdf->create_action("URI", "url=" . $url);
            $this->_pdf->create_annotation($x, $y, $x + $width, $y + $height, 'Link', "contents={$url} action={activate=$action} linewidth=0");
        }
    }

    /**
     * @param string $text
     * @param string $font
     * @param float  $size
     * @param int    $word_spacing
     * @param int    $letter_spacing
     * @return mixed
     */
    public function get_text_width($text, $font, $size, $word_spacing = 0, $letter_spacing = 0)
    {
        $fh = $this->_load_font($font);

        // Determine the additional width due to extra spacing
        $num_spaces = mb_substr_count($text, " ");
        $delta = $word_spacing * $num_spaces;

        if ($letter_spacing) {
            $num_chars = mb_strlen($text);
            $delta += ($num_chars - $num_spaces) * $letter_spacing;
        }

        return $this->_pdf->stringwidth($text, $fh, $size) + $delta;
    }

    /**
     * @param string $font
     * @param float  $size
     * @return float
     */
    public function get_font_height($font, $size)
    {
        $fh = $this->_load_font($font);

        $this->_pdf->setfont($fh, $size);

        $asc = $this->_pdf->info_font($fh, "ascender", "fontsize=$size");
        $desc = $this->_pdf->info_font($fh, "descender", "fontsize=$size");

        // $desc is usually < 0,
        $ratio = $this->_dompdf->getOptions()->getFontHeightRatio();

        return (abs($asc) + abs($desc)) * $ratio;
    }

    /**
     * @param string $font
     * @param float  $size
     * @return float
     */
    public function get_font_baseline($font, $size)
    {
        $ratio = $this->_dompdf->getOptions()->getFontHeightRatio();

        return $this->get_font_height($font, $size) / $ratio * 1.1;
    }

    /**
     * Writes text at the specified x and y coordinates on every page
     *
     * The strings '{PAGE_NUM}' and '{PAGE_COUNT}' are automatically replaced
     * with their current values.
     *
     * See {@link Style::munge_color()} for the format of the color array.
     *
     * @param float  $x
     * @param float  $y
     * @param string $text       the text to write
     * @param string $font       the font file to use
     * @param float  $size       the font size, in points
     * @param array  $color
     * @param float  $word_space word spacing adjustment
     * @param float  $char_space char spacing adjustment
     * @param float  $angle      angle to write the text at, measured CW starting from the x-axis
     */
    public function page_text($x, $y, $text, $font, $size, $color = array(0, 0, 0), $word_space = 0.0, $char_space = 0.0, $angle = 0.0)
    {
        $_t = "text";
        $this->_page_text[] = compact("_t", "x", "y", "text", "font", "size", "color", "word_space", "char_space", "angle");
    }

    //........................................................................

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
     *
     */
    public function new_page()
    {
        // Add objects to the current page
        $this->_place_objects();

        $this->_pdf->suspend_page("");
        $this->_pdf->begin_page_ext($this->_width, $this->_height, "");
        $this->_page_number = ++$this->_page_count;
    }

    /**
     * Add text to each page after rendering is complete
     */
    protected function _add_page_text()
    {
        if (count($this->_page_text) === 0) {
            return;
        }

        $eval = null;
        $this->_pdf->suspend_page("");

        for ($p = 1; $p <= $this->_page_count; $p++) {
            $this->_pdf->resume_page("pagenumber=$p");

            foreach ($this->_page_text as $pt) {
                extract($pt);

                switch ($_t) {
                    case "text":
                        $text = str_replace(array("{PAGE_NUM}", "{PAGE_COUNT}"),
                            array($p, $this->_page_count), $text);
                        $this->text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
                        break;

                    case "script":
                        if (!$eval) {
                            $eval = new PHPEvaluator($this);
                        }
                        $eval->evaluate($code, array('PAGE_NUM' => $p, 'PAGE_COUNT' => $this->_page_count));
                        break;

                    case 'line':
                        $this->line( $x1, $y1, $x2, $y2, $color, $width, $style );
                        break;

                }
            }

            $this->_pdf->suspend_page("");
        }

        $this->_pdf->resume_page("pagenumber=" . $this->_page_number);
    }

    /**
     * Streams the PDF to the client.
     *
     * @param string $filename The filename to present to the client.
     * @param array  $options  Associative array: 'compress' => 1 or 0 (default 1); 'Attachment' => 1 or 0 (default 1).
     * @throws Exception
     */
    public function stream($filename = "document.pdf", $options = array())
    {
        if (headers_sent()) {
            die("Unable to stream pdf: headers already sent");
        }

        if (!isset($options["compress"])) {
            $options["compress"] = true;
        }
        if (!isset($options["Attachment"])) {
            $options["Attachment"] = true;
        }

        $this->_add_page_text();

        if ($options["compress"]) {
            $this->setPDFLibValue("compress", 6);
        } else {
            $this->setPDFLibValue("compress", 0);
        }

        $this->_close();

        $data = "";

        if (self::$IN_MEMORY) {
            $data = $this->_pdf->get_buffer();
            $size = mb_strlen($data, "8bit");
        } else {
            $size = filesize($this->_file);
        }

        header("Cache-Control: private");
        header("Content-Type: application/pdf");
        header("Content-Length: " . $size);

        $filename = str_replace(array("\n", "'"), "", basename($filename, ".pdf")) . ".pdf";
        $attachment = $options["Attachment"] ? "attachment" : "inline";
        header(Helpers::buildContentDispositionHeader($attachment, $filename));

        if (self::$IN_MEMORY) {
            echo $data;
        } else {
            // Chunked readfile()
            $chunk = (1 << 21); // 2 MB
            $fh = fopen($this->_file, "rb");
            if (!$fh) {
                throw new Exception("Unable to load temporary PDF file: " . $this->_file);
            }

            while (!feof($fh)) {
                echo fread($fh, $chunk);
            }
            fclose($fh);

            //debugpng
            if ($this->_dompdf->getOptions()->getDebugPng()) {
                print '[pdflib stream unlink ' . $this->_file . ']';
            }
            if (!$this->_dompdf->getOptions()->getDebugKeepTemp()) {
                unlink($this->_file);
            }
            $this->_file = null;
            unset($this->_file);
        }

        flush();
    }

    /**
     * Returns the PDF as a string.
     *
     * @param array $options Associative array: 'compress' => 1 or 0 (default 1).
     * @return string
     */
    public function output($options = array())
    {
        if (!isset($options["compress"])) {
            $options["compress"] = true;
        }

        $this->_add_page_text();

        if ($options["compress"]) {
            $this->setPDFLibValue("compress", 6);
        } else {
            $this->setPDFLibValue("compress", 0);
        }

        $this->_close();

        if (self::$IN_MEMORY) {
            $data = $this->_pdf->get_buffer();
        } else {
            $data = file_get_contents($this->_file);

            //debugpng
            if ($this->_dompdf->getOptions()->getDebugPng()) {
                print '[pdflib output unlink ' . $this->_file . ']';
            }
            if (!$this->_dompdf->getOptions()->getDebugKeepTemp()) {
                unlink($this->_file);
            }
            $this->_file = null;
            unset($this->_file);
        }

        return $data;
    }

    /**
     * @param string $keyword
     * @param string $optlist
     * @return mixed
     */
    protected function getPDFLibParameter($keyword, $optlist = "")
    {
        if ($this->getPDFLibMajorVersion() >= 9) {
            return $this->_pdf->get_option($keyword, "");
        }

        return $this->_pdf->get_parameter($keyword, $optlist);
    }

    /**
     * @param string $keyword
     * @param string $value
     * @return mixed
     */
    protected function setPDFLibParameter($keyword, $value)
    {
        if ($this->getPDFLibMajorVersion() >= 9) {
            return $this->_pdf->set_option($keyword . "=" . $value);
        }

        return $this->_pdf->set_parameter($keyword, $value);
    }

    /**
     * @param string $keyword
     * @param string $optlist
     * @return mixed
     */
    protected function getPDFLibValue($keyword, $optlist = "")
    {
        if ($this->getPDFLibMajorVersion() >= 9) {
            return $this->getPDFLibParameter($keyword, $optlist);
        }

        return $this->_pdf->get_value($keyword);
    }

    /**
     * @param string $keyword
     * @param string $value
     * @return mixed
     */
    protected function setPDFLibValue($keyword, $value)
    {
        if ($this->getPDFLibMajorVersion() >= 9) {
            return $this->setPDFLibParameter($keyword, $value);
        }

        return $this->_pdf->set_value($keyword, $value);
    }

    /**
     * @return int
     */
    private function getPDFLibMajorVersion()
    {
        if (is_null(self::$MAJOR_VERSION)) {
            if (method_exists($this->_pdf, "get_option")) {
                self::$MAJOR_VERSION = abs(intval($this->_pdf->get_option("major", "")));
            } else {
                self::$MAJOR_VERSION = abs(intval($this->_pdf->get_value("major", "")));
            }
        }

        return self::$MAJOR_VERSION;
    }
}

// Workaround for idiotic limitation on statics...
PDFLib::$PAPER_SIZES = CPDF::$PAPER_SIZES;
