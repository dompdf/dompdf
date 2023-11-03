<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

/**
 * Main rendering interface
 *
 * Currently {@link Dompdf\Adapter\CPDF}, {@link Dompdf\Adapter\PDFLib}, and {@link Dompdf\Adapter\GD}
 * implement this interface.
 *
 * Implementations should measure x and y increasing to the left and down,
 * respectively, with the origin in the top left corner.  Implementations
 * are free to use a unit other than points for length, but I can't
 * guarantee that the results will look any good.
 *
 * @package dompdf
 */
interface Canvas
{
    /**
     * @param string|float[] $paper       The paper size to use as either a standard paper size (see {@link Dompdf\Adapter\CPDF::$PAPER_SIZES})
     *                                    or an array of the form `[x1, y1, x2, y2]` (typically `[0, 0, width, height]`).
     * @param string         $orientation The paper orientation, either `portrait` or `landscape`.
     * @param Dompdf|null    $dompdf      The Dompdf instance.
     */
    public function __construct($paper = "letter", string $orientation = "portrait", ?Dompdf $dompdf = null);

    /**
     * @return Dompdf
     */
    function get_dompdf();

    /**
     * Returns the current page number
     *
     * @return int
     */
    function get_page_number();

    /**
     * Returns the total number of pages in the document
     *
     * @return int
     */
    function get_page_count();

    /**
     * Sets the total number of pages
     *
     * @param int $count
     */
    function set_page_count($count);

    /**
     * Draws a line from x1,y1 to x2,y2
     *
     * See {@link Cpdf::setLineStyle()} for a description of the format of the
     * $style and $cap parameters (aka dash and cap).
     *
     * @param float  $x1
     * @param float  $y1
     * @param float  $x2
     * @param float  $y2
     * @param array  $color Color array in the format `[r, g, b, "alpha" => alpha]`
     *                      where r, g, b, and alpha are float values between 0 and 1
     * @param float  $width
     * @param array  $style
     * @param string $cap   `butt`, `round`, or `square`
     */
    function line($x1, $y1, $x2, $y2, $color, $width, $style = [], $cap = "butt");

    /**
     * Draws an arc
     *
     * See {@link Cpdf::setLineStyle()} for a description of the format of the
     * $style and $cap parameters (aka dash and cap).
     *
     * @param float  $x      X coordinate of the arc
     * @param float  $y      Y coordinate of the arc
     * @param float  $r1     Radius 1
     * @param float  $r2     Radius 2
     * @param float  $astart Start angle in degrees
     * @param float  $aend   End angle in degrees
     * @param array  $color  Color array in the format `[r, g, b, "alpha" => alpha]`
     *                       where r, g, b, and alpha are float values between 0 and 1
     * @param float  $width
     * @param array  $style
     * @param string $cap   `butt`, `round`, or `square`
     */
    function arc($x, $y, $r1, $r2, $astart, $aend, $color, $width, $style = [], $cap = "butt");

    /**
     * Draws a rectangle at x1,y1 with width w and height h
     *
     * See {@link Cpdf::setLineStyle()} for a description of the format of the
     * $style and $cap parameters (aka dash and cap).
     *
     * @param float  $x1
     * @param float  $y1
     * @param float  $w
     * @param float  $h
     * @param array  $color  Color array in the format `[r, g, b, "alpha" => alpha]`
     *                       where r, g, b, and alpha are float values between 0 and 1
     * @param float  $width
     * @param array  $style
     * @param string $cap   `butt`, `round`, or `square`
     */
    function rectangle($x1, $y1, $w, $h, $color, $width, $style = [], $cap = "butt");

    /**
     * Draws a filled rectangle at x1,y1 with width w and height h
     *
     * @param float $x1
     * @param float $y1
     * @param float $w
     * @param float $h
     * @param array $color Color array in the format `[r, g, b, "alpha" => alpha]`
     *                     where r, g, b, and alpha are float values between 0 and 1
     */
    function filled_rectangle($x1, $y1, $w, $h, $color);

    /**
     * Starts a clipping rectangle at x1,y1 with width w and height h
     *
     * @param float $x1
     * @param float $y1
     * @param float $w
     * @param float $h
     */
    function clipping_rectangle($x1, $y1, $w, $h);

    /**
     * Starts a rounded clipping rectangle at x1,y1 with width w and height h
     *
     * @param float $x1
     * @param float $y1
     * @param float $w
     * @param float $h
     * @param float $tl
     * @param float $tr
     * @param float $br
     * @param float $bl
     */
    function clipping_roundrectangle($x1, $y1, $w, $h, $tl, $tr, $br, $bl);

    /**
     * Starts a clipping polygon
     *
     * @param float[] $points
     */
    public function clipping_polygon(array $points): void;

    /**
     * Ends the last clipping shape
     */
    function clipping_end();

    /**
     * Processes a callback on every page.
     *
     * The callback function receives the four parameters `int $pageNumber`,
     * `int $pageCount`, `Canvas $canvas`, and `FontMetrics $fontMetrics`, in
     * that order.
     *
     * This function can be used to add page numbers to all pages after the
     * first one, for example.
     *
     * @param callable $callback The callback function to process on every page
     */
    public function page_script($callback): void;

    /**
     * Writes text at the specified x and y coordinates on every page.
     *
     * The strings '{PAGE_NUM}' and '{PAGE_COUNT}' are automatically replaced
     * with their current values.
     *
     * @param float  $x
     * @param float  $y
     * @param string $text       The text to write
     * @param string $font       The font file to use
     * @param float  $size       The font size, in points
     * @param array  $color      Color array in the format `[r, g, b, "alpha" => alpha]`
     *                           where r, g, b, and alpha are float values between 0 and 1
     * @param float  $word_space Word spacing adjustment
     * @param float  $char_space Char spacing adjustment
     * @param float  $angle      Angle to write the text at, measured clockwise starting from the x-axis
     */
    public function page_text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_space = 0.0, $char_space = 0.0, $angle = 0.0);

    /**
     * Draws a line at the specified coordinates on every page.
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param array $color Color array in the format `[r, g, b, "alpha" => alpha]`
     *                     where r, g, b, and alpha are float values between 0 and 1
     * @param float $width
     * @param array $style
     */
    public function page_line($x1, $y1, $x2, $y2, $color, $width, $style = []);

    /**
     * Save current state
     */
    function save();

    /**
     * Restore last state
     */
    function restore();

    /**
     * Rotate
     *
     * @param float $angle angle in degrees for counter-clockwise rotation
     * @param float $x     Origin abscissa
     * @param float $y     Origin ordinate
     */
    function rotate($angle, $x, $y);

    /**
     * Skew
     *
     * @param float $angle_x
     * @param float $angle_y
     * @param float $x       Origin abscissa
     * @param float $y       Origin ordinate
     */
    function skew($angle_x, $angle_y, $x, $y);

    /**
     * Scale
     *
     * @param float $s_x scaling factor for width as percent
     * @param float $s_y scaling factor for height as percent
     * @param float $x   Origin abscissa
     * @param float $y   Origin ordinate
     */
    function scale($s_x, $s_y, $x, $y);

    /**
     * Translate
     *
     * @param float $t_x movement to the right
     * @param float $t_y movement to the bottom
     */
    function translate($t_x, $t_y);

    /**
     * Transform
     *
     * @param float $a
     * @param float $b
     * @param float $c
     * @param float $d
     * @param float $e
     * @param float $f
     */
    function transform($a, $b, $c, $d, $e, $f);

    /**
     * Draws a polygon
     *
     * The polygon is formed by joining all the points stored in the $points
     * array.  $points has the following structure:
     * ```
     * array(0 => x1,
     *       1 => y1,
     *       2 => x2,
     *       3 => y2,
     *       ...
     *       );
     * ```
     *
     * See {@link Cpdf::setLineStyle()} for a description of the format of the
     * $style parameter (aka dash).
     *
     * @param array $points
     * @param array $color  Color array in the format `[r, g, b, "alpha" => alpha]`
     *                      where r, g, b, and alpha are float values between 0 and 1
     * @param float $width
     * @param array $style
     * @param bool  $fill   Fills the polygon if true
     */
    function polygon($points, $color, $width = null, $style = [], $fill = false);

    /**
     * Draws a circle at $x,$y with radius $r
     *
     * See {@link Cpdf::setLineStyle()} for a description of the format of the
     * $style parameter (aka dash).
     *
     * @param float $x
     * @param float $y
     * @param float $r
     * @param array $color Color array in the format `[r, g, b, "alpha" => alpha]`
     *                     where r, g, b, and alpha are float values between 0 and 1
     * @param float $width
     * @param array $style
     * @param bool  $fill  Fills the circle if true
     */
    function circle($x, $y, $r, $color, $width = null, $style = [], $fill = false);

    /**
     * Add an image to the pdf.
     *
     * The image is placed at the specified x and y coordinates with the
     * given width and height.
     *
     * @param string $img        The path to the image
     * @param float  $x          X position
     * @param float  $y          Y position
     * @param float  $w          Width
     * @param float  $h          Height
     * @param string $resolution The resolution of the image
     */
    function image($img, $x, $y, $w, $h, $resolution = "normal");

    /**
     * Writes text at the specified x and y coordinates
     *
     * @param float  $x
     * @param float  $y
     * @param string $text        The text to write
     * @param string $font        The font file to use
     * @param float  $size        The font size, in points
     * @param array  $color       Color array in the format `[r, g, b, "alpha" => alpha]`
     *                            where r, g, b, and alpha are float values between 0 and 1
     * @param float  $word_space  Word spacing adjustment
     * @param float  $char_space  Char spacing adjustment
     * @param float  $angle       Angle to write the text at, measured clockwise starting from the x-axis
     */
    function text($x, $y, $text, $font, $size, $color = [0, 0, 0], $word_space = 0.0, $char_space = 0.0, $angle = 0.0);

    /**
     * Add a named destination (similar to <a name="foo">...</a> in html)
     *
     * @param string $anchorname The name of the named destination
     */
    function add_named_dest($anchorname);

    /**
     * Add a link to the pdf
     *
     * @param string $url    The url to link to
     * @param float  $x      The x position of the link
     * @param float  $y      The y position of the link
     * @param float  $width  The width of the link
     * @param float  $height The height of the link
     */
    function add_link($url, $x, $y, $width, $height);

    /**
     * Add meta information to the PDF.
     *
     * @param string $label Label of the value (Creator, Producer, etc.)
     * @param string $value The text to set
     */
    public function add_info(string $label, string $value): void;

    /**
     * Determines if the font supports the given character
     *
     * @param string $font The font file to use
     * @param string $char The character to check
     *
     * @return bool
     */
    function font_supports_char(string $font, string $char): bool;

    /**
     * Calculates text size, in points
     *
     * @param string $text         The text to be sized
     * @param string $font         The font file to use
     * @param float  $size         The font size, in points
     * @param float  $word_spacing Word spacing, if any
     * @param float  $char_spacing Char spacing, if any
     *
     * @return float
     */
    function get_text_width($text, $font, $size, $word_spacing = 0.0, $char_spacing = 0.0);

    /**
     * Calculates font height, in points
     *
     * @param string $font The font file to use
     * @param float  $size The font size, in points
     *
     * @return float
     */
    function get_font_height($font, $size);

    /**
     * Returns the font x-height, in points
     *
     * @param string $font The font file to use
     * @param float  $size The font size, in points
     *
     * @return float
     */
    //function get_font_x_height($font, $size);

    /**
     * Calculates font baseline, in points
     *
     * @param string $font The font file to use
     * @param float  $size The font size, in points
     *
     * @return float
     */
    function get_font_baseline($font, $size);

    /**
     * Returns the PDF's width in points
     *
     * @return float
     */
    function get_width();

    /**
     * Returns the PDF's height in points
     *
     * @return float
     */
    function get_height();

    /**
     * Sets the opacity
     *
     * @param float  $opacity
     * @param string $mode
     */
    public function set_opacity(float $opacity, string $mode = "Normal"): void;

    /**
     * Sets the default view
     *
     * @param string $view
     * 'XYZ'  left, top, zoom
     * 'Fit'
     * 'FitH' top
     * 'FitV' left
     * 'FitR' left,bottom,right
     * 'FitB'
     * 'FitBH' top
     * 'FitBV' left
     * @param array $options
     */
    function set_default_view($view, $options = []);

    /**
     * @param string $code
     */
    function javascript($code);

    /**
     * Starts a new page
     *
     * Subsequent drawing operations will appear on the new page.
     */
    function new_page();

    /**
     * Streams the PDF to the client.
     *
     * @param string $filename The filename to present to the client.
     * @param array  $options  Associative array: 'compress' => 1 or 0 (default 1); 'Attachment' => 1 or 0 (default 1).
     */
    function stream($filename, $options = []);

    /**
     * Returns the PDF as a string.
     *
     * @param array $options Associative array: 'compress' => 1 or 0 (default 1).
     *
     * @return string
     */
    function output($options = []);
}
