<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Css;

use Dompdf\Adapter\CPDF;
use Dompdf\Css\Content\Attr;
use Dompdf\Css\Content\CloseQuote;
use Dompdf\Css\Content\ContentPart;
use Dompdf\Css\Content\Counter;
use Dompdf\Css\Content\Counters;
use Dompdf\Css\Content\NoCloseQuote;
use Dompdf\Css\Content\NoOpenQuote;
use Dompdf\Css\Content\OpenQuote;
use Dompdf\Css\Content\StringPart;
use Dompdf\Css\Content\Url;
use Dompdf\Exception;
use Dompdf\FontMetrics;
use Dompdf\Frame;
use Dompdf\Helpers;

/**
 * Represents CSS properties.
 *
 * The Style class is responsible for handling and storing CSS properties.
 * It includes methods to resolve colors and lengths, as well as getters &
 * setters for many CSS properties.
 *
 * Access to the different CSS properties is provided by the methods
 * {@link Style::set_prop()} and {@link Style::get_specified()}, and the
 * property overload methods {@link Style::__set()} and {@link Style::__get()},
 * as well as {@link Style::set_used()}. The latter methods operate on used
 * values and permit access to any (CSS) property using the following syntax:
 *
 * ```
 * $style->margin_top = 10.0;
 * echo $style->margin_top; // Returns `10.0`
 * ```
 *
 * To declare a property from a string, use {@link Style::set_prop()}:
 *
 * ```
 * $style->set_prop("margin_top", "1em");
 * echo $style->get_specified("margin_top"); // Returns `1em`
 * echo $style->margin_top; // Returns `12.0`, assuming the default font size
 * ```
 *
 * Actual CSS parsing is performed in the {@link Stylesheet} class.
 *
 * @property string               $azimuth
 * @property string               $background_attachment
 * @property array|string         $background_color
 * @property string               $background_image            Image URL or `none`
 * @property string               $background_image_resolution
 * @property array                $background_position         Pair of `[x, y]`, each value being a length in pt or a percentage value
 * @property string               $background_repeat
 * @property array|string         $background_size             `cover`, `contain`, or `[width, height]`, each being a length, percentage, or `auto`
 * @property string               $border_collapse
 * @property string               $border_color                Only use for setting all sides to the same color
 * @property float[]              $border_spacing              Pair of `[horizontal, vertical]` spacing
 * @property string               $border_style                Only use for setting all sides to the same style
 * @property array|string         $border_top_color
 * @property array|string         $border_right_color
 * @property array|string         $border_bottom_color
 * @property array|string         $border_left_color
 * @property string               $border_top_style            Valid border style
 * @property string               $border_right_style          Valid border style
 * @property string               $border_bottom_style         Valid border style
 * @property string               $border_left_style           Valid border style
 * @property float                $border_top_width            Length in pt
 * @property float                $border_right_width          Length in pt
 * @property float                $border_bottom_width         Length in pt
 * @property float                $border_left_width           Length in pt
 * @property string               $border_width                Only use for setting all sides to the same width
 * @property float|string         $border_bottom_left_radius   Radius in pt or a percentage value
 * @property float|string         $border_bottom_right_radius  Radius in pt or a percentage value
 * @property float|string         $border_top_left_radius      Radius in pt or a percentage value
 * @property float|string         $border_top_right_radius     Radius in pt or a percentage value
 * @property string               $border_radius               Only use for setting all corners to the same radius
 * @property float|string         $bottom                      Length in pt, a percentage value, or `auto`
 * @property string               $caption_side
 * @property string               $clear
 * @property string               $clip
 * @property array|string         $color
 * @property ContentPart[]|string $content                     List of content components, `normal`, or `none`
 * @property array|string         $counter_increment           Array defining the counters to increment or `none`
 * @property array|string         $counter_reset               Array defining the counters to reset or `none`
 * @property string               $cue_after
 * @property string               $cue_before
 * @property string               $cue
 * @property string               $cursor
 * @property string               $direction
 * @property string               $display
 * @property string               $elevation
 * @property string               $empty_cells
 * @property string               $float
 * @property string               $font_family
 * @property float                $font_size                   Length in pt
 * @property string               $font_style                  `normal`, `italic`, or `oblique`
 * @property string               $font_variant
 * @property int                  $font_weight                 Number in the range [1, 1000]
 * @property float|string         $height                      Length in pt, a percentage value, or `auto`
 * @property string               $image_resolution
 * @property string               $inset                       Only use for setting all box insets to the same length
 * @property float|string         $left                        Length in pt, a percentage value, or `auto`
 * @property float                $letter_spacing              Length in pt
 * @property float                $line_height                 Length in pt
 * @property string               $list_style_image            Image URL or `none`
 * @property string               $list_style_position         `inside` or `outside`
 * @property string               $list_style_type
 * @property float|string         $margin_right                Length in pt, a percentage value, or `auto`
 * @property float|string         $margin_left                 Length in pt, a percentage value, or `auto`
 * @property float|string         $margin_top                  Length in pt, a percentage value, or `auto`
 * @property float|string         $margin_bottom               Length in pt, a percentage value, or `auto`
 * @property string               $margin                      Only use for setting all sides to the same length
 * @property float|string         $max_height                  Length in pt, a percentage value, or `none`
 * @property float|string         $max_width                   Length in pt, a percentage value, or `none`
 * @property float|string         $min_height                  Length in pt, a percentage value, or `auto`
 * @property float|string         $min_width                   Length in pt, a percentage value, or `auto`
 * @property float                $opacity                     Number in the range [0, 1]
 * @property int                  $orphans
 * @property array|string         $outline_color
 * @property string               $outline_style               Valid border style, except for `hidden`
 * @property float                $outline_width               Length in pt
 * @property float                $outline_offset              Length in pt
 * @property string               $overflow
 * @property string               $overflow_wrap
 * @property float|string         $padding_top                 Length in pt or a percentage value
 * @property float|string         $padding_right               Length in pt or a percentage value
 * @property float|string         $padding_bottom              Length in pt or a percentage value
 * @property float|string         $padding_left                Length in pt or a percentage value
 * @property string               $padding                     Only use for setting all sides to the same length
 * @property string               $page_break_after
 * @property string               $page_break_before
 * @property string               $page_break_inside
 * @property string               $pause_after
 * @property string               $pause_before
 * @property string               $pause
 * @property string               $pitch_range
 * @property string               $pitch
 * @property string               $play_during
 * @property string               $position
 * @property array|string         $quotes                      List of quote pairs, or `none`
 * @property string               $richness
 * @property float|string         $right                       Length in pt, a percentage value, or `auto`
 * @property float[]|string       $size                        Pair of `[width, height]` or `auto`
 * @property string               $speak_header
 * @property string               $speak_numeral
 * @property string               $speak_punctuation
 * @property string               $speak
 * @property string               $speech_rate
 * @property string               $src
 * @property string               $stress
 * @property string               $table_layout
 * @property string               $text_align
 * @property string               $text_decoration
 * @property float|string         $text_indent                 Length in pt or a percentage value
 * @property string               $text_transform
 * @property float|string         $top                         Length in pt, a percentage value, or `auto`
 * @property array                $transform                   List of transforms
 * @property array                $transform_origin            Triplet of `[x, y, z]`, each value being a length in pt, or a percentage value for x and y
 * @property string               $unicode_bidi
 * @property string               $unicode_range
 * @property string               $vertical_align
 * @property string               $visibility
 * @property string               $voice_family
 * @property string               $volume
 * @property string               $white_space
 * @property int                  $widows
 * @property float|string         $width                       Length in pt, a percentage value, or `auto`
 * @property string               $word_break
 * @property float                $word_spacing                Length in pt
 * @property int|string           $z_index                     Integer value or `auto`
 * @property string               $_dompdf_keep
 *
 * @package dompdf
 */
class Style
{
    protected const CSS_IDENTIFIER = "-?[_a-zA-Z]+[_a-zA-Z0-9-]*";
    protected const CSS_INTEGER = "[+-]?\d+";
    protected const CSS_NUMBER = "[+-]?\d*\.?\d+(?:[eE][+-]?\d+)?";
    protected const CSS_STRING = "" .
        '"(?>(?:\\\\["]|[^"])*)(?<!\\\\)"|' . // String ""
        "'(?>(?:\\\\[']|[^'])*)(?<!\\\\)'";   // String ''
    protected const CSS_VAR = "var\((([^()]|(?R))*)\)";

    /**
     * @link https://www.w3.org/TR/css-values-4/#calc-syntax
     */
    protected const CSS_MATH_FUNCTIONS = [
        // Basic Arithmetic
        "calc" => true,
        // Comparison Functions
        "min" => true,
        "max" => true,
        "clamp" => true,
        // Stepped Value Functions
        "round" => true,                          // Not fully supported
        "mod" => true,
        "rem" => true,
        // Trigonometric Functions
        "sin" => true,
        "cos" => true,
        "tan" => true,
        "asin" => true,
        "acos" => true,
        "atan" => true,
        "atan2" => true,
        // Exponential Functions
        "pow" => true,
        "sqrt" => true,
        "hypot" => true,
        "log" => true,
        "exp" => true,
        // Sign-Related Functions
        "abs" => true,
        "sign" => true
    ];

    /**
     * https://www.w3.org/TR/css-values-3/#custom-idents
     */
    protected const CUSTOM_IDENT_FORBIDDEN = ["inherit", "initial", "unset", "default"];

    /**
     * Default font size, in points.
     *
     * @var float
     */
    public static $default_font_size = 12;

    /**
     * Default line height, as a fraction of the font size.
     *
     * @var float
     */
    public static $default_line_height = 1.2;

    /**
     * Default "absolute" font sizes relative to the default font-size
     * https://www.w3.org/TR/css-fonts-3/#absolute-size-value
     *
     * @var array<float>
     */
    public static $font_size_keywords = [
        "xx-small" => 0.6, // 3/5
        "x-small" => 0.75, // 3/4
        "small" => 0.889, // 8/9
        "medium" => 1, // 1
        "large" => 1.2, // 6/5
        "x-large" => 1.5, // 3/2
        "xx-large" => 2.0, // 2/1
    ];

    /**
     * List of valid text-align keywords.
     */
    public const TEXT_ALIGN_KEYWORDS = ["left", "right", "center", "justify"];

    /**
     * List of valid vertical-align keywords.
     */
    public const VERTICAL_ALIGN_KEYWORDS = ["baseline", "bottom", "middle",
        "sub", "super", "text-bottom", "text-top", "top"];

    /**
     * List of all block-level (outer) display types.
     * * https://www.w3.org/TR/css-display-3/#display-type
     * * https://www.w3.org/TR/css-display-3/#block-level
     */
    public const BLOCK_LEVEL_TYPES = [
        "block",
        // "flow-root",
        "list-item",
        // "flex",
        // "grid",
        "table"
    ];

    /**
     * List of all inline-level (outer) display types.
     * * https://www.w3.org/TR/css-display-3/#display-type
     * * https://www.w3.org/TR/css-display-3/#inline-level
     */
    public const INLINE_LEVEL_TYPES = [
        "inline",
        "inline-block",
        // "inline-flex",
        // "inline-grid",
        "inline-table"
    ];

    /**
     * List of all table-internal (outer) display types.
     * * https://www.w3.org/TR/css-display-3/#layout-specific-display
     */
    public const TABLE_INTERNAL_TYPES = [
        "table-row-group",
        "table-header-group",
        "table-footer-group",
        "table-row",
        "table-cell",
        "table-column-group",
        "table-column",
        "table-caption"
    ];

    /**
     * List of all inline (inner) display types.
     */
    public const INLINE_TYPES = ["inline"];

    /**
     * List of all block (inner) display types.
     */
    public const BLOCK_TYPES = ["block", "inline-block", "table-cell", "list-item"];

    /**
     * List of all table (inner) display types.
     */
    public const TABLE_TYPES = ["table", "inline-table"];

    /**
     * Lookup table for valid display types. Initially computed from the
     * different constants.
     *
     * @var array
     */
    protected static $valid_display_types = [];

    /**
     * List of all positioned types.
     */
    public const POSITIONED_TYPES = ["relative", "absolute", "fixed"];

    /**
     * List of valid border styles.
     */
    public const BORDER_STYLES = [
        "none", "hidden",
        "dotted", "dashed", "solid",
        "double", "groove", "ridge", "inset", "outset"
    ];

    /**
     * List of valid outline-style values.
     * Same as the border styles, except `auto` is allowed, `hidden` is not.
     *
     * @link https://www.w3.org/TR/css-ui-4/#typedef-outline-line-style
     */
    protected const OUTLINE_STYLES = [
        "auto", "none",
        "dotted", "dashed", "solid",
        "double", "groove", "ridge", "inset", "outset"
    ];

    /**
     * Map of CSS shorthand properties and their corresponding sub-properties.
     * The order of the sub-properties is relevant for the fallback getter,
     * which is used in case no specific getter method is defined.
     *
     * @var array<string, string[]>
     */
    protected static $_props_shorthand = [
        "background" => [
            "background_image",
            "background_position",
            "background_size",
            "background_repeat",
            // "background_origin",
            // "background_clip",
            "background_attachment",
            "background_color"
        ],
        "border" => [
            "border_top_width",
            "border_right_width",
            "border_bottom_width",
            "border_left_width",
            "border_top_style",
            "border_right_style",
            "border_bottom_style",
            "border_left_style",
            "border_top_color",
            "border_right_color",
            "border_bottom_color",
            "border_left_color"
        ],
        "border_top" => [
            "border_top_width",
            "border_top_style",
            "border_top_color"
        ],
        "border_right" => [
            "border_right_width",
            "border_right_style",
            "border_right_color"
        ],
        "border_bottom" => [
            "border_bottom_width",
            "border_bottom_style",
            "border_bottom_color"
        ],
        "border_left" => [
            "border_left_width",
            "border_left_style",
            "border_left_color"
        ],
        "border_width" => [
            "border_top_width",
            "border_right_width",
            "border_bottom_width",
            "border_left_width"
        ],
        "border_style" => [
            "border_top_style",
            "border_right_style",
            "border_bottom_style",
            "border_left_style"
        ],
        "border_color" => [
            "border_top_color",
            "border_right_color",
            "border_bottom_color",
            "border_left_color"
        ],
        "border_radius" => [
            "border_top_left_radius",
            "border_top_right_radius",
            "border_bottom_right_radius",
            "border_bottom_left_radius"
        ],
        "font" => [
            "font_family",
            "font_size",
            // "font_stretch",
            "font_style",
            "font_variant",
            "font_weight",
            "line_height"
        ],
        "inset" => [
            "top",
            "right",
            "bottom",
            "left"
        ],
        "list_style" => [
            "list_style_image",
            "list_style_position",
            "list_style_type"
        ],
        "margin" => [
            "margin_top",
            "margin_right",
            "margin_bottom",
            "margin_left"
        ],
        "padding" => [
            "padding_top",
            "padding_right",
            "padding_bottom",
            "padding_left"
        ],
        "outline" => [
            "outline_width",
            "outline_style",
            "outline_color"
        ]
    ];

    /**
     * Maps legacy property names to actual property names.
     *
     * @var array<string, string>
     */
    protected static $_props_alias = [
        "word_wrap"                           => "overflow_wrap",
        "_dompdf_background_image_resolution" => "background_image_resolution",
        "_dompdf_image_resolution"            => "image_resolution",
        "_webkit_transform"                   => "transform",
        "_webkit_transform_origin"            => "transform_origin"
    ];

    /**
     * Default style values.
     *
     * @link https://www.w3.org/TR/CSS21/propidx.html
     *
     * @var array<string, mixed>
     */
    protected static $_defaults = null;

    /**
     * Lookup table for properties that inherit by default.
     *
     * @link https://www.w3.org/TR/CSS21/propidx.html
     *
     * @var array<string, true>
     */
    protected static $_inherited = [
        "azimuth" => true,
        "background_image_resolution" => true,
        "border_collapse" => true,
        "border_spacing" => true,
        "caption_side" => true,
        "color" => true,
        "cursor" => true,
        "direction" => true,
        "elevation" => true,
        "empty_cells" => true,
        "font_family" => true,
        "font_size" => true,
        "font_style" => true,
        "font_variant" => true,
        "font_weight" => true,
        "font" => true,
        "image_resolution" => true,
        "letter_spacing" => true,
        "line_height" => true,
        "list_style_image" => true,
        "list_style_position" => true,
        "list_style_type" => true,
        "list_style" => true,
        "orphans" => true,
        "overflow_wrap" => true,
        "pitch_range" => true,
        "pitch" => true,
        "quotes" => true,
        "richness" => true,
        "speak_header" => true,
        "speak_numeral" => true,
        "speak_punctuation" => true,
        "speak" => true,
        "speech_rate" => true,
        "stress" => true,
        "text_align" => true,
        "text_indent" => true,
        "text_transform" => true,
        "visibility" => true,
        "voice_family" => true,
        "volume" => true,
        "white_space" => true,
        "widows" => true,
        "word_break" => true,
        "word_spacing" => true
    ];

    /**
     * @var array<string, string[]>
     */
    protected static $_dependency_map = [
        "border_top_style" => [
            "border_top_width"
        ],
        "border_bottom_style" => [
            "border_bottom_width"
        ],
        "border_left_style" => [
            "border_left_width"
        ],
        "border_right_style" => [
            "border_right_width"
        ],
        "direction" => [
            "text_align"
        ],
        "font_size" => [
            "background_position",
            "background_size",
            "border_top_width",
            "border_right_width",
            "border_bottom_width",
            "border_left_width",
            "border_top_left_radius",
            "border_top_right_radius",
            "border_bottom_right_radius",
            "border_bottom_left_radius",
            "inset",
            "letter_spacing",
            "line_height",
            "margin_top",
            "margin_right",
            "margin_bottom",
            "margin_left",
            "outline_width",
            "outline_offset",
            "padding_top",
            "padding_right",
            "padding_bottom",
            "padding_left",
            "word_spacing",
            "width",
            "height",
            "min-width",
            "min-height",
            "max-width",
            "max-height"
        ],
        "float" => [
            "display"
        ],
        "position" => [
            "display"
        ],
        "outline_style" => [
            "outline_width"
        ]
    ];

    /**
     * Lookup table for dependent properties. Initially computed from the
     * dependency map.
     *
     * @var array<string, true>
     */
    protected static $_dependent_props = [];

    /**
     * Caches method_exists result
     *
     * @var array<string, bool>
     */
    protected static $_methods_cache = [];

    /**
     * The stylesheet this style belongs to
     *
     * @var Stylesheet
     */
    protected $_stylesheet;

    /**
     * Media queries attached to the style
     *
     * This is a two-dimensional array where the first dimension represents
     * the media query grouping (logic-or) and the second dimension the
     * media queries within the grouping.
     *
     * The structure of the actual query element is:
     * - media query feature
     * - media query value or condition
     * - media query operator (e.g., not)
     *
     * @var array
     */
    protected $_media_queries;

    /**
     * Properties set by an `!important` declaration.
     *
     * @var array<string, true>
     */
    protected $_important_props = [];

    /**
     * Specified (or declared) values of the CSS properties.
     *
     * https://www.w3.org/TR/css-cascade-3/#value-stages
     *
     * @var array<string, mixed>
     */
    protected $_props = [];

    /**
     * Used to track which CSS property were set directly versus
     * those set via shorthand property
     *
     * @var array<string, true>
     */
    protected $_props_specified = [];

    /**
     * Computed values of the CSS properties.
     *
     * @var array<string, mixed>
     */
    protected $_props_computed = [];

    /**
     * Used values of the CSS properties.
     *
     * @var array<string, mixed>
     */
    protected $_props_used = [];

    /**
     * Marks properties with non-final used values that should be cleared on
     * style reset.
     *
     * @var array<string, true>
     */
    protected $non_final_used = [];

    /**
     * Used to track CSS property assignment entry/exit in order to watch
     * for circular dependencies.
     *
     * @var array<int, string>
     */
    protected $_prop_stack = [];

    /**
     * Used to track CSS variable resolution entry/exit in order to watch
     * for circular dependencies.
     *
     * @var array<int, string>
     */
    protected $_var_stack = [];

    /**
     * Style of the parent element in document tree.
     *
     * @var Style
     */
    protected $parent_style;

    /**
     * @var Frame|null
     */
    protected $_frame;

    /**
     * The origin of the style
     *
     * @var int
     */
    protected $_origin = Stylesheet::ORIG_AUTHOR;

    /**
     * The computed bottom spacing
     *
     * @var float|string|null
     */
    private $_computed_bottom_spacing = null;

    /**
     * @var bool|null
     */
    private $has_border_radius_cache = null;

    /**
     * @var array|null
     */
    private $resolved_border_radius = null;

    /**
     * @var FontMetrics
     */
    private $fontMetrics;

    /**
     * @param Stylesheet $stylesheet The stylesheet the style is associated with.
     * @param int        $origin
     */
    public function __construct(Stylesheet $stylesheet, int $origin = Stylesheet::ORIG_AUTHOR)
    {
        $this->fontMetrics = $stylesheet->getFontMetrics();

        $this->_stylesheet = $stylesheet;
        $this->_media_queries = [];
        $this->_origin = $origin;
        $this->parent_style = null;

        if (!isset(self::$_defaults)) {

            // Shorthand
            $d =& self::$_defaults;

            // All CSS 2.1 properties, and their default values
            // Some properties are specified with their computed value for
            // efficiency; this only works if the computed value is not
            // dependent on another property
            $d["azimuth"] = "center";
            $d["background_attachment"] = "scroll";
            $d["background_color"] = "transparent";
            $d["background_image"] = "none";
            $d["background_image_resolution"] = "normal";
            $d["background_position"] = [0.0, 0.0];
            $d["background_repeat"] = "repeat";
            $d["background"] = "";
            $d["border_collapse"] = "separate";
            $d["border_color"] = "";
            $d["border_spacing"] = [0.0, 0.0];
            $d["border_style"] = "";
            $d["border_top"] = "";
            $d["border_right"] = "";
            $d["border_bottom"] = "";
            $d["border_left"] = "";
            $d["border_top_color"] = "currentcolor";
            $d["border_right_color"] = "currentcolor";
            $d["border_bottom_color"] = "currentcolor";
            $d["border_left_color"] = "currentcolor";
            $d["border_top_style"] = "none";
            $d["border_right_style"] = "none";
            $d["border_bottom_style"] = "none";
            $d["border_left_style"] = "none";
            $d["border_top_width"] = "medium";
            $d["border_right_width"] = "medium";
            $d["border_bottom_width"] = "medium";
            $d["border_left_width"] = "medium";
            $d["border_width"] = "";
            $d["border_bottom_left_radius"] = 0.0;
            $d["border_bottom_right_radius"] = 0.0;
            $d["border_top_left_radius"] = 0.0;
            $d["border_top_right_radius"] = 0.0;
            $d["border_radius"] = "";
            $d["border"] = "";
            $d["bottom"] = "auto";
            $d["caption_side"] = "top";
            $d["clear"] = "none";
            $d["clip"] = "auto";
            $d["color"] = "#000000";
            $d["content"] = "normal";
            $d["counter_increment"] = "none";
            $d["counter_reset"] = "none";
            $d["cue_after"] = "none";
            $d["cue_before"] = "none";
            $d["cue"] = "";
            $d["cursor"] = "auto";
            $d["direction"] = "ltr";
            $d["display"] = "inline";
            $d["elevation"] = "level";
            $d["empty_cells"] = "show";
            $d["float"] = "none";
            $d["font_family"] = $stylesheet->get_dompdf()->getOptions()->getDefaultFont();
            $d["font_size"] = "medium";
            $d["font_style"] = "normal";
            $d["font_variant"] = "normal";
            $d["font_weight"] = 400;
            $d["font"] = "";
            $d["height"] = "auto";
            $d["image_resolution"] = "normal";
            $d["inset"] = "";
            $d["left"] = "auto";
            $d["letter_spacing"] = "normal";
            $d["line_height"] = "normal";
            $d["list_style_image"] = "none";
            $d["list_style_position"] = "outside";
            $d["list_style_type"] = "disc";
            $d["list_style"] = "";
            $d["margin_right"] = 0.0;
            $d["margin_left"] = 0.0;
            $d["margin_top"] = 0.0;
            $d["margin_bottom"] = 0.0;
            $d["margin"] = "";
            $d["max_height"] = "none";
            $d["max_width"] = "none";
            $d["min_height"] = "auto";
            $d["min_width"] = "auto";
            $d["orphans"] = 2;
            $d["outline_color"] = "currentcolor"; // "invert" special color is not supported
            $d["outline_style"] = "none";
            $d["outline_width"] = "medium";
            $d["outline_offset"] = 0.0;
            $d["outline"] = "";
            $d["overflow"] = "visible";
            $d["overflow_wrap"] = "normal";
            $d["padding_top"] = 0.0;
            $d["padding_right"] = 0.0;
            $d["padding_bottom"] = 0.0;
            $d["padding_left"] = 0.0;
            $d["padding"] = "";
            $d["page_break_after"] = "auto";
            $d["page_break_before"] = "auto";
            $d["page_break_inside"] = "auto";
            $d["pause_after"] = "0";
            $d["pause_before"] = "0";
            $d["pause"] = "";
            $d["pitch_range"] = "50";
            $d["pitch"] = "medium";
            $d["play_during"] = "auto";
            $d["position"] = "static";
            $d["quotes"] = "auto";
            $d["richness"] = "50";
            $d["right"] = "auto";
            $d["size"] = "auto"; // @page
            $d["speak_header"] = "once";
            $d["speak_numeral"] = "continuous";
            $d["speak_punctuation"] = "none";
            $d["speak"] = "normal";
            $d["speech_rate"] = "medium";
            $d["stress"] = "50";
            $d["table_layout"] = "auto";
            $d["text_align"] = "";
            $d["text_decoration"] = "none";
            $d["text_indent"] = 0.0;
            $d["text_transform"] = "none";
            $d["top"] = "auto";
            $d["unicode_bidi"] = "normal";
            $d["vertical_align"] = "baseline";
            $d["visibility"] = "visible";
            $d["voice_family"] = "";
            $d["volume"] = "medium";
            $d["white_space"] = "normal";
            $d["widows"] = 2;
            $d["width"] = "auto";
            $d["word_break"] = "normal";
            $d["word_spacing"] = "normal";
            $d["z_index"] = "auto";

            // CSS3
            $d["opacity"] = 1.0;
            $d["background_size"] = ["auto", "auto"];
            $d["transform"] = [];
            $d["transform_origin"] = ["50%", "50%", 0.0];

            // for @font-face
            $d["src"] = "";
            $d["unicode_range"] = "";

            // vendor-prefixed properties
            $d["_dompdf_keep"] = "";

            // Compute dependent props from dependency map
            foreach (self::$_dependency_map as $props) {
                foreach ($props as $prop) {
                    self::$_dependent_props[$prop] = true;
                }
            }

            // Compute valid display-type lookup table
            self::$valid_display_types = [
                "none"                => true,
                "-dompdf-br"          => true,
                "-dompdf-image"       => true,
                "-dompdf-list-bullet" => true,
                "-dompdf-page"        => true
            ];
            foreach (self::BLOCK_LEVEL_TYPES as $val) {
                self::$valid_display_types[$val] = true;
            }
            foreach (self::INLINE_LEVEL_TYPES as $val) {
                self::$valid_display_types[$val] = true;
            }
            foreach (self::TABLE_INTERNAL_TYPES as $val) {
                self::$valid_display_types[$val] = true;
            }
        }
    }

    /**
     * Clear all non-final used values.
     */
    public function reset(): void
    {
        foreach (array_keys($this->non_final_used) as $prop) {
            unset($this->_props_used[$prop]);
        }

        $this->non_final_used = [];
    }

    /**
     * @param array $media_queries
     */
    public function set_media_queries(array $media_queries): void
    {
        $this->_media_queries = $media_queries;
    }

    /**
     * @return array
     */
    public function get_media_queries(): array
    {
        return $this->_media_queries;
    }

    /**
     * @param Frame $frame
     */
    public function set_frame(Frame $frame): void
    {
        $this->_frame = $frame;
    }

    /**
     * @return Frame|null
     */
    public function get_frame(): ?Frame
    {
        return $this->_frame;
    }

    /**
     * @param int $origin
     */
    public function set_origin(int $origin): void
    {
        $this->_origin = $origin;
    }

    /**
     * @return int
     */
    public function get_origin(): int
    {
        return $this->_origin;
    }

    /**
     * Returns the {@link Stylesheet} the style is associated with.
     *
     * @return Stylesheet
     */
    public function get_stylesheet(): Stylesheet
    {
        return $this->_stylesheet;
    }

    public function is_custom_property(string $prop): bool
    {
        return \substr($prop, 0, 2) === "--";
    }

    public function is_absolute(): bool
    {
        $position = $this->__get("position");
        return $position === "absolute" || $position === "fixed";
    }

    public function is_in_flow(): bool
    {
        $float = $this->__get("float");
        return $float === "none" && !$this->is_absolute();
    }

    /**
     * Converts any CSS length value into an absolute length in points.
     *
     * length_in_pt() takes a single length (e.g. '1em') or an array of
     * lengths and returns an absolute length.  If an array is passed, then
     * the return value is the sum of all elements. If any of the lengths
     * provided are "auto" or "none" then that value is returned.
     *
     * If a reference size is not provided, the current font size is used.
     *
     * @param float|string|array $length   The numeric length (or string measurement) or array of lengths to resolve.
     * @param float|null         $ref_size An absolute reference size to resolve percentage lengths.
     *
     * @return float|string
     */
    public function length_in_pt($length, ?float $ref_size = null)
    {
        $font_size = $this->__get("font_size");
        $ref_size = $ref_size ?? $font_size;

        if (!\is_array($length)) {
            $length = [$length];
        }

        $ret = 0.0;

        foreach ($length as $l) {
            if ($l === "auto" || $l === "none") {
                return $l;
            }

            // Assume numeric values are already in points
            if (is_numeric($l)) {
                $ret += (float) $l;
                continue;
            }

            $val = $this->single_length_in_pt((string) $l, $ref_size, $font_size);
            $ret += $val ?? 0;
        }

        return $ret;
    }

    /**
     * Convert a length declaration to pt.
     *
     * @param string     $l         The length declaration.
     * @param float      $ref_size  Reference size for percentage declarations.
     * @param float|null $font_size Font size for resolving font-size relative units.
     *
     * @return float|null The length in pt, or `null` for invalid declarations.
     */
    protected function single_length_in_pt(string $l, float $ref_size = 0, ?float $font_size = null): ?float
    {
        static $cache = [];

        $font_size = $font_size ?? $this->__get("font_size");
        $dpi = $this->_stylesheet->get_dompdf()->getOptions()->getDpi();

        $key = "$l/$dpi/$ref_size/$font_size";

        if (\array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        $number = self::CSS_NUMBER;
        $pattern = "/^($number)([a-zA-Z%]*)?$/";

        if (!preg_match($pattern, $l, $matches)) {
            $ident = self::CSS_IDENTIFIER;
            $pattern = "/^($ident)\(.*\)$/i";
            if (preg_match($pattern, $l)) {
                $value = $this->evaluate_func($this->parse_func($l), $ref_size, $font_size);
                return $cache[$key] = $value;
            }
            return null;
        }

        $v = (float) $matches[1];
        $unit = strtolower($matches[2]);

        if ($unit === "") {
            // Legacy support for unitless values, not covered by spec. Might
            // want to restrict this to unitless `0` in the future
            $value = $v;
        }

        elseif ($unit === "%") {
            $value = $v / 100 * $ref_size;
        }

        elseif ($unit === "px") {
            $value = ($v * 72) / $dpi;
        }

        elseif ($unit === "pt") {
            $value = $v;
        }

        elseif ($unit === "rem") {
            $tree = $this->_stylesheet->get_dompdf()->getTree();
            $root_style = $tree !== null ? $tree->get_root()->get_style() : null;
            $root_font_size = $root_style === null || $root_style === $this
                ? $font_size
                : $root_style->__get("font_size");
            $value = $v * $root_font_size;

            // Skip caching if the root style is not available yet, as to avoid
            // incorrectly cached values if the root font size is different from
            // the default
            if ($root_style === null) {
                return $value;
            }
        }

        elseif ($unit === "em") {
            $value = $v * $font_size;
        }

        elseif ($unit === "cm") {
            $value = $v * 72 / 2.54;
        }

        elseif ($unit === "mm") {
            $value = $v * 72 / 25.4;
        }

        elseif ($unit === "ex") {
            // FIXME: em:ex ratio?
            $value = $v * $font_size / 2;
        }

        elseif ($unit === "in") {
            $value = $v * 72;
        }

        elseif ($unit === "pc") {
            $value = $v * 12;
        }

        else {
            // Invalid or unsupported declaration
            $value = null;
        }

        return $cache[$key] = $value;
    }

    /**
     * Shunting-yard Algorithm
     * @param string $expr infix expression
     * @return array
     */
    private function parse_func(string $expr): array
    {
        if (substr_count($expr, '(') !== substr_count($expr, ')')) {
            return [];
        }

        $expr = str_replace(['(', ')', '*', '/', ','], [' ( ', ' ) ', ' * ', ' / ', ' , '], $expr);
        $expr = trim(preg_replace('/\s+/', ' ', $expr));

        if ($expr === '') {
            return [];
        }

        $precedence = ['*' => 3, '/' => 3, '+' => 2, '-' => 2, ',' => 1];

        $opStack = [];
        $queue = [];

        $parts = explode(' ', $expr);

        foreach ($parts as $part) {
            if ($part === '(') {
                $opStack[] = $part;
            } elseif (\array_key_exists(strtolower($part), self::CSS_MATH_FUNCTIONS)) {
                $opStack[] = strtolower($part);
            } elseif ($part === ')') {
                while (\count($opStack) > 0 && end($opStack) !== '(' && !\array_key_exists(end($opStack), self::CSS_MATH_FUNCTIONS)) {
                    $queue[] = array_pop($opStack);
                }
                if (end($opStack) === '(') {
                    array_pop($opStack);
                }
                if (\count($opStack) > 0 && \array_key_exists(end($opStack), self::CSS_MATH_FUNCTIONS)) {
                    $queue[] = array_pop($opStack);
                }
            } elseif (\array_key_exists($part, $precedence)) {
                while (\count($opStack) > 0 && end($opStack) !== '(' && $precedence[end($opStack)] >= $precedence[$part]) {
                    $queue[] = array_pop($opStack);
                }
                $opStack[] = $part;
            } else {
                $queue[] = $part;
            }
        }

        while (\count($opStack) > 0) {
            $queue[] = array_pop($opStack);
        }

        return $queue;
    }

    /**
     * Reverse Polish Notation
     * @param array $rpn
     * @param float $ref_size
     * @param float|null $font_size
     * @return float|null
     */
    private function evaluate_func(array $rpn, float $ref_size = 0, ?float $font_size = null): ?float
    {
        if (\count($rpn) === 0) {
            return null;
        }

        $ops = ['*', '/', '+', '-', ','];

        $stack = [];

        foreach ($rpn as $part) {
            if (\array_key_exists($part, self::CSS_MATH_FUNCTIONS)) {
                $argv = array_pop($stack);
                if (!is_array($argv)) {
                    $argv = [$argv];
                }
                $argc = \count($argv);
                switch ($part) {
                    case 'abs':
                    case 'acos':
                    case 'asin':
                    case 'atan':
                    case 'cos':
                    case 'exp':
                    case 'sin':
                    case 'sqrt':
                    case 'tan':
                        if ($argc !== 1) {
                            return null;
                        }
                        $stack[] = call_user_func_array($part, $argv);
                        break;
                    case 'atan2':
                    case 'hypot':
                    case 'pow':
                        if ($argc !== 2) {
                            return null;
                        }
                        $stack[] = call_user_func_array($part, $argv);
                        break;
                    case 'log':
                        if ($argc === 1) {
                            $stack[] = log($argv[0]);
                        } elseif ($argc === 2) {
                            $stack[] = log($argv[0], $argv[1]);
                        } else {
                            return null;
                        }
                        break;
                    case 'max':
                        $stack[] = max($argv);
                        break;
                    case 'min':
                        $stack[] = min($argv);
                        break;
                    case 'mod':
                        if ($argc !== 2 || $argv[1] === 0.0) {
                            return null;
                        }
                        if ($argv[1] > 0) {
                            $stack[] = $argv[0] - floor($argv[0] / $argv[1]) * $argv[1];
                        } else {
                            $stack[] = $argv[0] - ceil($argv[0] * -1 / $argv[1]) * $argv[1] * -1 ;
                        }
                        break;
                    case 'rem':
                        if ($argc !== 2 || $argv[1] === 0.0) {
                            return null;
                        }
                        $stack[] = $argv[0] - (intval($argv[0] / $argv[1]) * $argv[1]);
                        break;
                    case 'round':
                        if ($argc !== 2 || $argv[1] === 0.0) {
                            return null;
                        }
                        if ($argv[0] >= 0) {
                            $stack[] = round($argv[0] / $argv[1], 0, PHP_ROUND_HALF_UP) * $argv[1];
                        } else {
                            $stack[] = round($argv[0] / $argv[1], 0, PHP_ROUND_HALF_DOWN) * $argv[1];
                        }
                        break;
                    case 'calc':
                        if ($argc !== 1) {
                            return null;
                        }
                        $stack[] = $argv[0];
                        break;
                    case 'clamp':
                        if ($argc !== 3) {
                            return null;
                        }
                        $stack[] = max($argv[0], min($argv[1], $argv[2]));
                        break;
                    case 'sign':
                        if ($argc !== 1) {
                            return null;
                        }
                        $stack[] = $argv[0] == 0 ? 0.0 : ($argv[0] / abs($argv[0]));
                        break;
                    default:
                        return null;
                }
            } elseif (\in_array($part, $ops, true)) {
                $rightValue = array_pop($stack);
                $leftValue = array_pop($stack);
                if ($rightValue === null || $leftValue === null) {
                    return null;
                }
                switch ($part) {
                    case '*':
                        $stack[] = $leftValue * $rightValue;
                        break;
                    case '/':
                        if ($rightValue === 0.0) {
                            return null;
                        }
                        $stack[] = $leftValue / $rightValue;
                        break;
                    case '+':
                        $stack[] = $leftValue + $rightValue;
                        break;
                    case '-':
                        $stack[] = $leftValue - $rightValue;
                        break;
                    case ',':
                        if (is_array($leftValue)) {
                            $leftValue[] = $rightValue;
                            $stack[] = $leftValue;
                        } else {
                            $stack[] = [$leftValue, $rightValue];
                        }
                        break;
                }
            } else {
                $val = $this->single_length_in_pt($part, $ref_size, $font_size);
                if ($val === null) {
                    return null;
                }
                $stack[] = $val;
            }
        }

        if (\count($stack) > 1) {
            return null;
        }

        return floatval(end($stack));
    }

    /**
     * Resolves the actual values for used CSS custom properties.
     *
     * This function receives the whole content of the var() function, which
     * can also include a fallback value.
     */
    private function parse_var($matches) {
        $variable = is_array($matches) ? $matches[1] : $matches;

        if (\in_array($variable, $this->_var_stack, true)) {
            return null;
        }
        array_push($this->_var_stack, $variable);

        // Split property name and an optional fallback value.
        [$custom_prop, $fallback] = explode(',', $variable, 2) + ['', ''];
        $fallback = trim($fallback);

        // Try to retrieve the custom property value, or use the fallback value
        // if the value could not be resolved.
        $value = $this->computed($custom_prop) ?? $fallback;

        // If the resolved value also has vars in it, resolve again.
        $pattern = self::CSS_VAR;
        $value = preg_replace_callback(
            "/$pattern/",
            [$this, "parse_var"],
            $value);

        array_pop($this->_var_stack);
        return $value ?: null;
    }

    /**
     * Resolve inherited property values using the provided parent style or the
     * default values, in case no parent style exists.
     *
     * https://www.w3.org/TR/css-cascade-3/#inheriting
     *
     * @param Style|null $parent
     */
    public function inherit(?Style $parent = null): void
    {
        $this->parent_style = $parent;

        // Clear the computed font size, as it might depend on the parent
        // font size
        unset($this->_props_computed["font_size"]);
        unset($this->_props_used["font_size"]);

        if ($parent) {
            // For properties that inherit by default: When the cascade did
            // not result in a value, inherit the parent value. Inheritance
            // is handled via the specific sub-properties for shorthands. Custom
            // properties (variables) are selected by the -- prefix.
            foreach ($parent->_props as $prop => $val) {
                if (
                    !isset($this->_props[$prop])
                    && (
                        isset(self::$_inherited[$prop])
                        || $this->is_custom_property($prop)
                    )
                ) {
                    $parent_val = $parent->computed($prop);

                    if ($this->is_custom_property($prop)) {
                        $this->set_prop($prop, $parent_val);
                    } else {
                        $this->_props[$prop] = $parent_val;
                        $this->_props_computed[$prop] = $parent_val;
                        $this->_props_used[$prop] = null;
                    }
                }
            }
        }

        foreach ($this->_props as $prop => $val) {
            if ($val === "inherit") {
                if ($parent && isset($parent->_props[$prop])) {
                    $parent_val = $parent->computed($prop);

                    if ($this->is_custom_property($prop)) {
                        $this->set_prop($prop, $parent_val);
                    } else {
                        $this->_props[$prop] = $parent_val;
                        $this->_props_computed[$prop] = $parent_val;
                        $this->_props_used[$prop] = null;
                    }
                } else {
                    if ($this->is_custom_property($prop)) {
                        $this->set_prop($prop, "unset");
                    } else {
                        // Parent prop not set, use default
                        $this->_props[$prop] = self::$_defaults[$prop];
                        unset($this->_props_computed[$prop]);
                        unset($this->_props_used[$prop]);
                    }
                }
            }
        }
    }

    /**
     * Override properties in this style with those in $style
     *
     * @param Style $style
     */
    public function merge(Style $style): void
    {
        foreach ($style->_props as $prop => $val) {
            $important = isset($style->_important_props[$prop]);

            // `!important` declarations take precedence over normal ones
            if (!$important && isset($this->_important_props[$prop])) {
                continue;
            }

            if ($important) {
                $this->_important_props[$prop] = true;
            }

            if ($this->is_custom_property($prop)) {
                $this->set_prop($prop, $val, $important);
            } else {
                $this->_props[$prop] = $val;
            }

            // Copy an existing computed value only for non-dependent
            // properties; otherwise it may be invalid for the current style
            if (!isset(self::$_dependent_props[$prop])
                && \array_key_exists($prop, $style->_props_computed)
            ) {
                $this->_props_computed[$prop] = $style->_props_computed[$prop];
                $this->_props_used[$prop] = null;
            } else {
                unset($this->_props_computed[$prop]);
                unset($this->_props_used[$prop]);
            }

            if (\array_key_exists($prop, $style->_props_specified)) {
                $this->_props_specified[$prop] = true;
            }
        }

        // re-evalutate CSS variables
        foreach (array_keys($this->_props) as $prop) {
            if (!$this->is_custom_property($prop)) {
                continue;
            }
            $this->set_prop($prop, $this->_props[$prop], isset($this->_important_props[$prop]));
        }
    }

    /**
     * Clear information about important declarations after the style has been
     * finalized during stylesheet loading.
     */
    public function clear_important(): void
    {
        $this->_important_props = [];
    }

    /**
     * Clear border-radius and bottom-spacing cache as necessary when a given
     * property is set.
     *
     * @param string $prop The property that is set.
     */
    protected function clear_cache(string $prop): void
    {
        // Clear border-radius cache on setting any border-radius
        // property
        if ($prop === "border_top_left_radius"
            || $prop === "border_top_right_radius"
            || $prop === "border_bottom_left_radius"
            || $prop === "border_bottom_right_radius"
        ) {
            $this->has_border_radius_cache = null;
            $this->resolved_border_radius = null;
        }

        // Clear bottom-spacing cache if necessary. Border style can
        // disable/enable border calculations
        if ($prop === "margin_bottom"
            || $prop === "padding_bottom"
            || $prop === "border_bottom_width"
            || $prop === "border_bottom_style"
        ) {
            $this->_computed_bottom_spacing = null;
        }
    }

    /**
     * Set a style property from a value declaration.
     *
     * Setting `$clear_dependencies` to `false` is useful for saving a bit of
     * unnecessary work while loading stylesheets.
     *
     * @param string $prop               The property to set.
     * @param mixed  $val                The value declaration or computed value.
     * @param bool   $important          Whether the declaration is important.
     * @param bool   $clear_dependencies Whether to clear computed values of dependent properties.
     */
    public function set_prop(string $prop, $val, bool $important = false, bool $clear_dependencies = true): void
    {
        // Skip some checks for CSS custom properties.
        if (!$this->is_custom_property($prop)) {

            $prop = str_replace("-", "_", $prop);

            // Legacy property aliases
            if (isset(self::$_props_alias[$prop])) {
                $prop = self::$_props_alias[$prop];
            }

            if (!isset(self::$_defaults[$prop])) {
                global $_dompdf_warnings;
                $_dompdf_warnings[] = "'$prop' is not a recognized CSS property.";
                return;
            }
        }
        $this->_props_specified[$prop] = true;

        // Trim declarations unconditionally, but only lower-case for comparison
        // with the general keywords. Properties must handle case-insensitive
        // comparisons individually
        if (\is_string($val)) {
            $val = trim($val);
            $lower = strtolower($val);

            if ($lower === "initial" || $lower === "inherit" || $lower === "unset") {
                $val = $lower;
            }
        }

        if (isset(self::$_props_shorthand[$prop])) {
            // Shorthand properties directly set their respective sub-properties
            // https://www.w3.org/TR/css-cascade-3/#shorthand
            if ($val === "initial" || $val === "inherit" || $val === "unset") {
                foreach (self::$_props_shorthand[$prop] as $sub_prop) {
                    $this->set_prop($sub_prop, $val, $important, $clear_dependencies);
                }
            } else {
                $method = "_set_$prop";

                // Resolve the CSS custom property value(s).
                $pattern = self::CSS_VAR;

                // Always set the specified value for properties that use CSS variables
                // so that an invalid initial value does not prevent re-computation later.
                $this->_props[$prop] = $val;

                //TODO: we shouldn't need to parse this twice
                preg_match_all("/$pattern/", $val, $matches, PREG_SET_ORDER);
                foreach ($matches as $match) {
                    if ($this->parse_var($match) === null) {
                        // unset specified as for specific prop under expectation it will be overridden
                        foreach (self::$_props_shorthand[$prop] as $sub_prop) {
                            unset($this->_props_specified[$sub_prop]);
                        }
                        return;
                    }
                }
                $val = preg_replace_callback(
                    "/$pattern/",
                    [$this, "parse_var"],
                    $val);

                if (!isset(self::$_methods_cache[$method])) {
                    self::$_methods_cache[$method] = method_exists($this, $method);
                }

                if (self::$_methods_cache[$method]) {
                    $values = $this->$method($val);

                    if ($values === []) {
                        return;
                    }

                    // Each missing sub-property is assigned its initial value
                    // https://www.w3.org/TR/css-cascade-3/#shorthand
                    foreach (self::$_props_shorthand[$prop] as $sub_prop) {
                        $sub_val = $values[$sub_prop] ?? self::$_defaults[$sub_prop];
                        $this->set_prop($sub_prop, $sub_val, $important, $clear_dependencies);
                        unset($this->_props_specified[$sub_prop]);
                    }
                }
            }
        } else {
            // Legacy support for `word-break: break-word`
            // https://www.w3.org/TR/css-text-3/#valdef-word-break-break-word
            if ($prop === "word_break"
                && \is_string($val) && strcasecmp($val, "break-word") === 0
            ) {
                $val = "normal";
                $this->set_prop("overflow_wrap", "anywhere", $important, $clear_dependencies);
            }

            // `!important` declarations take precedence over normal ones
            if (!$important && isset($this->_important_props[$prop])) {
                return;
            }

            if ($important) {
                $this->_important_props[$prop] = true;
            }

            // https://www.w3.org/TR/css-cascade-3/#inherit-initial
            if ($val === "unset") {
                $val = isset(self::$_inherited[$prop]) || $this->is_custom_property($prop) ? "inherit" : "initial";
            }

            // https://www.w3.org/TR/css-cascade-3/#valdef-all-initial
            if ($val === "initial" && !$this->is_custom_property($prop)) {
                $val = self::$_defaults[$prop];
            }

            // Always set the specified value for properties that use CSS variables
            // so that an invalid initial value does not prevent re-computation later.
            if (\is_string($val) && \preg_match("/" . self::CSS_VAR . "/", $val)) {
                $this->_props[$prop] = $val;
            }

            $computed = $this->compute_prop($prop, $val);

            // Skip invalid declarations
            if ($computed === null) {
                return;
            }

            $this->_props[$prop] = $val;
            $this->_props_computed[$prop] = $computed;
            $this->_props_used[$prop] = null;

            //TODO: this should be a directed dependency map
            if ($this->is_custom_property($prop) && !\in_array($prop, $this->_prop_stack, true)) {
                array_push($this->_prop_stack, $prop);
                $specified_props = array_filter($this->_props, function($key) {
                    return \array_key_exists($key, $this->_props_specified);
                }, ARRAY_FILTER_USE_KEY); // copy existing props filtered by those set explicitly before parsing vars
                foreach ($specified_props as $specified_prop => $specified_value) {
                    if (!$this->is_custom_property($specified_prop) || strpos($specified_value, "var($prop") !== false) {
                        $this->set_prop($specified_prop, $specified_value, isset($this->_important_props[$specified_prop]), true);
                        if (isset(self::$_props_shorthand[$specified_prop])) {
                            foreach (self::$_props_shorthand[$specified_prop] as $sub_prop) {
                                if (\array_key_exists($sub_prop, $specified_props)) {
                                    $this->set_prop($sub_prop, $specified_props[$sub_prop], isset($this->_important_props[$sub_prop]), true);
                                }
                            }
                        }
                    }
                }
                array_pop($this->_prop_stack);
            }

            if ($clear_dependencies) {
                // Clear the computed values of any dependent properties, so
                // they can be re-computed
                if (isset(self::$_dependency_map[$prop])) {
                    foreach (self::$_dependency_map[$prop] as $dependent) {
                        unset($this->_props_computed[$dependent]);
                        unset($this->_props_used[$dependent]);
                    }
                }

                $this->clear_cache($prop);
            }
        }
    }

    /**
     * Get the specified value of a style property.
     *
     * @param string $prop
     *
     * @return mixed
     * @throws Exception
     */
    public function get_specified(string $prop)
    {
        // Legacy property aliases
        if (isset(self::$_props_alias[$prop])) {
            $prop = self::$_props_alias[$prop];
        }

        if (!isset(self::$_defaults[$prop]) && !$this->is_custom_property($prop)) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        return $this->_props[$prop] ?? self::$_defaults[$prop];
    }

    /**
     * Set a style property to its final value.
     *
     * This sets the specified and used value of the style property to the given
     * value, meaning the value is not parsed and thus should have a type
     * compatible with the property.
     *
     * If a shorthand property is specified, all of its sub-properties are set
     * to the given value.
     *
     * @param string $prop The property to set.
     * @param mixed  $val  The final value of the property.
     *
     * @throws Exception
     */
    public function __set(string $prop, $val)
    {
        // Legacy property aliases
        if (isset(self::$_props_alias[$prop])) {
            $prop = self::$_props_alias[$prop];
        }

        if (!isset(self::$_defaults[$prop]) && !$this->is_custom_property($prop)) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        if (isset(self::$_props_shorthand[$prop])) {
            foreach (self::$_props_shorthand[$prop] as $sub_prop) {
                $this->__set($sub_prop, $val);
            }
        } else {
            $this->_props[$prop] = $val;
            $this->_props_computed[$prop] = $val;
            $this->_props_used[$prop] = $val;

            $this->clear_cache($prop);
        }
    }

    /**
     * Set the used value of a style property.
     *
     * Used values are cleared on style reset.
     *
     * If a shorthand property is specified, all of its sub-properties are set
     * to the given value.
     *
     * @param string $prop The property to set.
     * @param mixed  $val  The used value of the property.
     *
     * @throws Exception
     */
    public function set_used(string $prop, $val): void
    {
        // Legacy property aliases
        if (isset(self::$_props_alias[$prop])) {
            $prop = self::$_props_alias[$prop];
        }

        if (!isset(self::$_defaults[$prop])) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        if (isset(self::$_props_shorthand[$prop])) {
            foreach (self::$_props_shorthand[$prop] as $sub_prop) {
                $this->set_used($sub_prop, $val);
            }
        } else {
            $this->_props_used[$prop] = $val;
            $this->non_final_used[$prop] = true;
        }
    }

    /**
     * Get the used or computed value of a style property, depending on whether
     * the used value has been determined yet.
     *
     * @param string $prop
     *
     * @return mixed
     * @throws Exception
     */
    public function __get(string $prop)
    {
        // Legacy property aliases
        if (isset(self::$_props_alias[$prop])) {
            $prop = self::$_props_alias[$prop];
        }

        if (!isset(self::$_defaults[$prop]) && !$this->is_custom_property($prop)) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        if (isset($this->_props_used[$prop])) {
            return $this->_props_used[$prop];
        }

        $method = "_get_$prop";

        if (!isset(self::$_methods_cache[$method])) {
            self::$_methods_cache[$method] = method_exists($this, $method);
        }

        if (isset(self::$_props_shorthand[$prop])) {
            // Don't cache shorthand values, always use getter. If no dedicated
            // getter exists, use a simple fallback getter concatenating all
            // sub-property values
            if (self::$_methods_cache[$method]) {
                return $this->$method();
            } else {
                return implode(" ", array_map(function ($sub_prop) {
                    $val = $this->__get($sub_prop);
                    return \is_array($val) ? implode(" ", $val) : $val;
                }, self::$_props_shorthand[$prop]));
            }
        } else {
            $computed = $this->computed($prop);
            $used = self::$_methods_cache[$method]
                ? $this->$method($computed)
                : $computed;

            $this->_props_used[$prop] = $used;
            return $used;
        }
    }

    /**
     * @param string $prop The property to compute.
     * @param mixed  $val  The value to compute. Non-string values are treated as already computed.
     *
     * @return mixed The computed value.
     */
    protected function compute_prop(string $prop, $val)
    {
        // During style merge, the parent style is not available yet, so
        // temporarily use the initial value for `inherit` properties. The
        // keyword is properly resolved during inheritance
        if ($val === "inherit" && !$this->is_custom_property($prop)) {
            $val = self::$_defaults[$prop];
        }

        // Check for values which are already computed
        if (!\is_string($val)) {
            return $val;
        }

        // Resolve the CSS custom property value(s).
        $pattern = self::CSS_VAR;
        $val = preg_replace_callback(
            "/$pattern/",
            [$this, "parse_var"],
            $val);

        $method = "_compute_$prop";

        if (!isset(self::$_methods_cache[$method])) {
            self::$_methods_cache[$method] = method_exists($this, $method);
        }

        if (self::$_methods_cache[$method]) {
            return $this->$method($val);
        } elseif ($val !== "") {
            return strtolower($val);
        } else {
            return null;
        }
    }

    /**
     * Get the computed value for the given property.
     *
     * @param string $prop The property to get the computed value of.
     *
     * @return mixed The computed value.
     */
    protected function computed(string $prop)
    {
        if (!\array_key_exists($prop, $this->_props_computed)) {
            if (!\array_key_exists($prop, $this->_props) && $this->is_custom_property($prop)) {
                return null;
            }
            $val = $this->_props[$prop] ?? self::$_defaults[$prop];
            $computed = $this->compute_prop($prop, $val);

            if ($computed === null) {
                if ($this->is_custom_property($prop)) {
                    return null;
                }
                $computed = $this->compute_prop($prop, self::$_defaults[$prop]);
            }

            $this->_props_computed[$prop] = $computed;
        }

        return $this->_props_computed[$prop];
    }

    /**
     * @param float $cbw The width of the containing block.
     * @return float|string|null
     */
    public function computed_bottom_spacing(float $cbw)
    {
        // Caching the bottom spacing independently of the given width is a bit
        // iffy, but should be okay, as the containing block should only
        // potentially change after a page break, and the style is reset in that
        // case
        if ($this->_computed_bottom_spacing !== null) {
            return $this->_computed_bottom_spacing;
        }
        return $this->_computed_bottom_spacing = $this->length_in_pt(
            [
                $this->margin_bottom,
                $this->padding_bottom,
                $this->border_bottom_width
            ],
            $cbw
        );
    }

    /**
     * Returns an `array(r, g, b, "r" => r, "g" => g, "b" => b, "alpha" => alpha, "hex" => "#rrggbb")`
     * based on the provided CSS color value.
     *
     * @param string|null $color
     * @return array|string|null
     */
    public function munge_color($color)
    {
        return Color::parse($color);
    }

    /**
     * @return string
     */
    public function get_font_family_raw(): string
    {
        return trim($this->_props["font_family"], " \t\n\r\x0B\"'");
    }

    /**
     * @return string[]
     */
    public function get_font_family_computed(): array
    {
        return $this->computed("font_family");
    }

    /**
     * Getter for the `font-family` CSS property.
     *
     * Uses the {@link FontMetrics} class to resolve the font family into an
     * actual font file.
     *
     * @param string[] $computed
     * @return string
     *
     * @throws Exception
     *
     * @link https://www.w3.org/TR/CSS21/fonts.html#propdef-font-family
     */
    protected function _get_font_family($computed): string
    {
        // TODO: It probably makes sense to perform the font selection outside
        // the Style class completely. It is now done primarily in
        // `FrameDecorator\Text::apply_font_mapping`

        // Select the appropriate font.  First determine the subtype, then check
        // the specified font-families for a candidate.

        $fontMetrics = $this->getFontMetrics();
        $weight = $this->__get("font_weight");
        $fontStyle = $this->__get("font_style");
        $subtype = $fontMetrics->getType($weight . ' ' . $fontStyle);

        foreach ($computed as $family) {
            $font = $fontMetrics->getFont($family, $subtype);

            if ($font !== null) {
                return $font;
            }
        }

        $font = $fontMetrics->getFont(null, $subtype);

        if ($font !== null) {
            return $font;
        }

        $specified = implode(", ", $computed);
        throw new Exception("Unable to find a suitable font replacement for: '$specified'");
    }

    /**
     * @param float $computed
     * @return float
     *
     * @link https://www.w3.org/TR/CSS21/fonts.html#propdef-font-size
     */
    protected function _get_font_size($computed)
    {
        // Computed value may be negative when specified via `calc()`
        return max($computed, 0.0);
    }

    /**
     * @param float|string $computed
     * @return float
     *
     * @link https://www.w3.org/TR/css-text-4/#word-spacing-property
     */
    protected function _get_word_spacing($computed)
    {
        if (\is_float($computed)) {
            return $computed;
        }

        // Resolve percentage values
        $font_size = $this->__get("font_size");
        return $this->single_length_in_pt($computed, $font_size);
    }

    /**
     * @param float|string $computed
     * @return float
     *
     * @link https://www.w3.org/TR/css-text-4/#letter-spacing-property
     */
    protected function _get_letter_spacing($computed)
    {
        if (\is_float($computed)) {
            return $computed;
        }

        // Resolve percentage values
        $font_size = $this->__get("font_size");
        return $this->single_length_in_pt($computed, $font_size);
    }

    /**
     * @param float|string $computed
     * @return float
     *
     * @link https://www.w3.org/TR/CSS21/visudet.html#propdef-line-height
     */
    protected function _get_line_height($computed)
    {
        // Lengths have been computed to float, number values to string
        if (\is_float($computed)) {
            // Computed value may be negative when specified via `calc()`
            return max($computed, 0.0);
        }

        $font_size = $this->__get("font_size");
        $factor = $computed === "normal"
            ? self::$default_line_height
            : (float) $computed;

        return $factor * $font_size;
    }

    /**
     * @param string $computed
     * @param bool   $current_is_parent
     *
     * @return array|string
     */
    protected function get_color_value($computed, bool $current_is_parent = false)
    {
        if ($computed === "currentcolor") {
            // https://www.w3.org/TR/css-color-4/#resolving-other-colors
            if ($current_is_parent) {
                // Use the `color` value from the parent for the `color`
                // property itself
                return isset($this->parent_style)
                    ? $this->parent_style->__get("color")
                    : $this->munge_color(self::$_defaults["color"]);
            }

            return $this->__get("color");
        }

        return $this->munge_color($computed) ?? "transparent";
    }

    /**
     * Returns the color as an array
     *
     * The array has the following format:
     * `array(r, g, b, "r" => r, "g" => g, "b" => b, "alpha" => alpha, "hex" => "#rrggbb")`
     *
     * @param string $computed
     * @return array|string
     *
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-color
     */
    protected function _get_color($computed)
    {
        return $this->get_color_value($computed, true);
    }

    /**
     * Returns the background color as an array
     *
     * See {@link Style::_get_color()} for format of the color array.
     *
     * @param string $computed
     * @return array|string
     *
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-color
     */
    protected function _get_background_color($computed)
    {
        return $this->get_color_value($computed);
    }

    /**
     * Returns the background image URI, or "none"
     *
     * @param string $computed
     * @return string
     *
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-image
     */
    protected function _get_background_image($computed): string
    {
        return $this->_stylesheet->resolve_url($computed, true);
    }

    /**
     * Returns the border color as an array
     *
     * See {@link Style::_get_color()} for format of the color array.
     *
     * @param string $computed
     * @return array|string
     *
     * @link https://www.w3.org/TR/CSS21/box.html#border-color-properties
     */
    protected function _get_border_top_color($computed)
    {
        return $this->get_color_value($computed);
    }

    /**
     * @param string $computed
     * @return array|string
     */
    protected function _get_border_right_color($computed)
    {
        return $this->get_color_value($computed);
    }

    /**
     * @param string $computed
     * @return array|string
     */
    protected function _get_border_bottom_color($computed)
    {
        return $this->get_color_value($computed);
    }

    /**
     * @param string $computed
     * @return array|string
     */
    protected function _get_border_left_color($computed)
    {
        return $this->get_color_value($computed);
    }

    /**
     * Return an array of all border properties.
     *
     * The returned array has the following structure:
     *
     * ```
     * array("top" => array("width" => [border-width],
     *                      "style" => [border-style],
     *                      "color" => [border-color (array)]),
     *       "bottom" ... )
     * ```
     *
     * @return array
     */
    public function get_border_properties(): array
    {
        return [
            "top" => [
                "width" => $this->__get("border_top_width"),
                "style" => $this->__get("border_top_style"),
                "color" => $this->__get("border_top_color"),
            ],
            "bottom" => [
                "width" => $this->__get("border_bottom_width"),
                "style" => $this->__get("border_bottom_style"),
                "color" => $this->__get("border_bottom_color"),
            ],
            "right" => [
                "width" => $this->__get("border_right_width"),
                "style" => $this->__get("border_right_style"),
                "color" => $this->__get("border_right_color"),
            ],
            "left" => [
                "width" => $this->__get("border_left_width"),
                "style" => $this->__get("border_left_style"),
                "color" => $this->__get("border_left_color"),
            ],
        ];
    }

    /**
     * Return a single border-side property
     *
     * @param string $side
     * @return string
     */
    protected function get_border_side(string $side): string
    {
        $color = $this->__get("border_{$side}_color");

        return $this->__get("border_{$side}_width") . " " .
            $this->__get("border_{$side}_style") . " " .
            (\is_array($color) ? $color["hex"] : $color);
    }

    /**
     * Return full border properties as a string
     *
     * Border properties are returned just as specified in CSS:
     * `[width] [style] [color]`
     * e.g. "1px solid blue"
     *
     * @return string
     *
     * @link https://www.w3.org/TR/CSS21/box.html#border-shorthand-properties
     */
    protected function _get_border_top(): string
    {
        return $this->get_border_side("top");
    }

    /**
     * @return string
     */
    protected function _get_border_right(): string
    {
        return $this->get_border_side("right");
    }

    /**
     * @return string
     */
    protected function _get_border_bottom(): string
    {
        return $this->get_border_side("bottom");
    }

    /**
     * @return string
     */
    protected function _get_border_left(): string
    {
        return $this->get_border_side("left");
    }

    public function has_border_radius(): bool
    {
        if (isset($this->has_border_radius_cache)) {
            return $this->has_border_radius_cache;
        }

        // Use a fixed ref size here. We don't know the border-box width here
        // and font size might be 0. Since we are only interested in whether
        // there is any border radius at all, this should do
        $tl = (float) $this->length_in_pt($this->border_top_left_radius, 12);
        $tr = (float) $this->length_in_pt($this->border_top_right_radius, 12);
        $br = (float) $this->length_in_pt($this->border_bottom_right_radius, 12);
        $bl = (float) $this->length_in_pt($this->border_bottom_left_radius, 12);

        $this->has_border_radius_cache = $tl + $tr + $br + $bl > 0;
        return $this->has_border_radius_cache;
    }

    /**
     * Get the final border-radius values to use.
     *
     * Percentage values are resolved relative to the width of the border box.
     * The border radius is additionally scaled for the given render box, and
     * constrained by its width and height.
     *
     * @param float[]      $border_box The border box of the frame.
     * @param float[]|null $render_box The box to resolve the border radius for.
     *
     * @return float[] A 4-tuple of top-left, top-right, bottom-right, and bottom-left radius.
     */
    public function resolve_border_radius(
        array $border_box,
        ?array $render_box = null
    ): array {
        $render_box = $render_box ?? $border_box;
        $use_cache = $render_box === $border_box;

        if ($use_cache && isset($this->resolved_border_radius)) {
            return $this->resolved_border_radius;
        }

        [$x, $y, $w, $h] = $border_box;

        // Resolve percentages relative to width, as long as we have no support
        // for per-axis radii
        $tl = (float) $this->length_in_pt($this->border_top_left_radius, $w);
        $tr = (float) $this->length_in_pt($this->border_top_right_radius, $w);
        $br = (float) $this->length_in_pt($this->border_bottom_right_radius, $w);
        $bl = (float) $this->length_in_pt($this->border_bottom_left_radius, $w);

        if ($tl + $tr + $br + $bl > 0) {
            [$rx, $ry, $rw, $rh] = $render_box;

            $t_offset = $y - $ry;
            $r_offset = $rx + $rw - $x - $w;
            $b_offset = $ry + $rh - $y - $h;
            $l_offset = $x - $rx;

            if ($tl > 0) {
                $tl = max($tl + ($t_offset + $l_offset) / 2, 0);
            }
            if ($tr > 0) {
                $tr = max($tr + ($t_offset + $r_offset) / 2, 0);
            }
            if ($br > 0) {
                $br = max($br + ($b_offset + $r_offset) / 2, 0);
            }
            if ($bl > 0) {
                $bl = max($bl + ($b_offset + $l_offset) / 2, 0);
            }

            if ($tl + $bl > $rh) {
                $f = $rh / ($tl + $bl);
                $tl = $f * $tl;
                $bl = $f * $bl;
            }
            if ($tr + $br > $rh) {
                $f = $rh / ($tr + $br);
                $tr = $f * $tr;
                $br = $f * $br;
            }
            if ($tl + $tr > $rw) {
                $f = $rw / ($tl + $tr);
                $tl = $f * $tl;
                $tr = $f * $tr;
            }
            if ($bl + $br > $rw) {
                $f = $rw / ($bl + $br);
                $bl = $f * $bl;
                $br = $f * $br;
            }
        }

        $values = [$tl, $tr, $br, $bl];

        if ($use_cache) {
            $this->resolved_border_radius = $values;
        }

        return $values;
    }

    /**
     * Returns the outline color as an array
     *
     * See {@link Style::_get_color()} for format of the color array.
     *
     * @param string $computed
     * @return array|string
     *
     * @link https://www.w3.org/TR/css-ui-4/#propdef-outline-color
     */
    protected function _get_outline_color($computed)
    {
        return $this->get_color_value($computed);
    }

    /**
     * @param string $computed
     * @return string
     *
     * @link https://www.w3.org/TR/css-ui-4/#propdef-outline-style
     */
    protected function _get_outline_style($computed): string
    {
        return $computed === "auto" ? "solid" : $computed;
    }

    /**
     * Return full outline properties as a string
     *
     * Outline properties are returned just as specified in CSS:
     * `[width] [style] [color]`
     * e.g. "1px solid blue"
     *
     * @return string
     *
     * @link https://www.w3.org/TR/CSS21/box.html#border-shorthand-properties
     */
    protected function _get_outline(): string
    {
        $color = $this->__get("outline_color");

        return $this->__get("outline_width") . " " .
            $this->__get("outline_style") . " " .
            (\is_array($color) ? $color["hex"] : $color);
    }

    /**
     * Returns the list style image URI, or "none"
     *
     * @param string $computed
     * @return string
     *
     * @link https://www.w3.org/TR/CSS21/generate.html#propdef-list-style-image
     */
    protected function _get_list_style_image($computed): string
    {
        return $this->_stylesheet->resolve_url($computed, true);
    }

    /**
     * @param array|string $computed
     * @return array|string
     *
     * @link https://www.w3.org/TR/css-content-3/#quotes
     */
    protected function _get_quotes($computed)
    {
        if ($computed === "auto") {
            // TODO: Use typographically appropriate quotes for the current
            // language here
            return [['"', '"'], ["'", "'"]];
        }

        return $computed;
    }

    /*==============================*/

    /**
     * Parse a property value into its components.
     *
     * @param string $value
     *
     * @return string[]
     */
    protected function parse_property_value(string $value): array
    {
        $string = self::CSS_STRING;
        $ident = self::CSS_IDENTIFIER;
        $number = self::CSS_NUMBER;

        $pattern = "/\n" .
            "\s* (?<string>$string)                                        |\n" . // String
            "\s* (url \( (?> (\\\\[\"'()] | [^\"'()])* ) (?<!\\\\)\) )     |\n" . // URL without quotes
            "\s* ($ident (\( ((?> \g<string> | [^\"'()]+ ) | (?-2))* \)) ) |\n" . // Function (with balanced parentheses)
            "\s* ($ident)                                                  |\n" . // Keyword
            "\s* (\#[0-9a-fA-F]*)                                          |\n" . // Hex value
            "\s* ($number [a-zA-Z%]*)                                      |\n" . // Number (+ unit/percentage)
            "\s* ([\/,;])                                                   \n" . // Delimiter
            "/iSx";

        if (!preg_match_all($pattern, $value, $matches)) {
            return [];
        }

        return array_map("trim", $matches[0]);
    }

    protected function is_color_value(string $val): bool
    {
        return $val === "currentcolor"
            || $val === "transparent"
            || isset(Color::$cssColorNames[$val])
            || preg_match("/^#|rgb\(|rgba\(|cmyk\(/", $val);
    }

    /**
     * @param string $val
     * @return string|null
     */
    protected function compute_color_value(string $val): ?string
    {
        // https://www.w3.org/TR/css-color-4/#resolving-other-colors
        $val = strtolower($val);
        $munged_color = $val !== "currentcolor"
            ? $this->munge_color($val)
            : $val;

        if ($munged_color === null) {
            return null;
        }

        return \is_array($munged_color) ? $munged_color["hex"] : $munged_color;
    }

    /**
     * @param string $val
     * @return int|null
     */
    protected function compute_integer(string $val): ?int
    {
        $integer = self::CSS_INTEGER;
        return preg_match("/^$integer$/", $val)
            ? (int) $val
            : null;
    }

    /**
     * @param string $val
     * @return float|null
     */
    protected function compute_number(string $val): ?float
    {
        $number = self::CSS_NUMBER;
        return preg_match("/^$number$/", $val)
            ? (float) $val
            : null;
    }

    /**
     * @param string $val
     * @return float|null
     */
    protected function compute_length(string $val): ?float
    {
        return strpos($val, "%") === false
            ? $this->single_length_in_pt($val)
            : null;
    }

    /**
     * @param string $val
     * @return float|null
     */
    protected function compute_length_positive(string $val): ?float
    {
        $computed = $this->compute_length($val);

        // Negative non-`calc` values are invalid
        if ($computed === null
            || ($computed < 0 && !preg_match("/^-?[_a-zA-Z]/", $val))
        ) {
            return null;
        }

        return $computed;
    }

    /**
     * @param string $val
     * @return float|string|null
     */
    protected function compute_length_percentage(string $val)
    {
        // Compute with a fixed ref size to decide whether percentage values
        // are valid
        $computed = $this->single_length_in_pt($val, 12);

        if ($computed === null) {
            return null;
        }

        // Retain valid percentage declarations
        return strpos($val, "%") === false ? $computed : $val;
    }

    /**
     * @param string $val
     * @return float|string|null
     */
    protected function compute_length_percentage_positive(string $val)
    {
        // Compute with a fixed ref size to decide whether percentage values
        // are valid
        $computed = $this->single_length_in_pt($val, 12);

        // Negative non-`calc` values are invalid
        if ($computed === null
            || ($computed < 0 && !preg_match("/^-?[_a-zA-Z]/", $val))
        ) {
            return null;
        }

        // Retain valid percentage declarations
        return strpos($val, "%") === false ? $computed : $val;
    }

    /**
     * @param string $val
     * @param string $style_prop The corresponding border-/outline-style property.
     *
     * @return float|null
     *
     * @link https://www.w3.org/TR/css-backgrounds-3/#typedef-line-width
     */
    protected function compute_line_width(string $val, string $style_prop): ?float
    {
        $val = strtolower($val);

        // Border-width keywords
        if ($val === "thin") {
            $computed = 0.5;
        } elseif ($val === "medium") {
            $computed = 1.5;
        } elseif ($val === "thick") {
            $computed = 2.5;
        } else {
            $computed = $this->compute_length_positive($val);
        }

        if ($computed === null) {
            return null;
        }

        // Computed width is 0 if the line style is `none` or `hidden`
        // https://www.w3.org/TR/css-backgrounds-3/#border-width
        // https://www.w3.org/TR/css-ui-4/#outline-width
        $lineStyle = $this->__get($style_prop);
        $hasLineStyle = $lineStyle !== "none" && $lineStyle !== "hidden";

        return $hasLineStyle ? $computed : 0.0;
    }

    /**
     * @param string $val
     * @return string|null
     */
    protected function compute_border_style(string $val): ?string
    {
        $val = strtolower($val);
        return \in_array($val, self::BORDER_STYLES, true) ? $val : null;
    }

    /**
     * @param string $val
     * @return float|null
     *
     * @link https://www.w3.org/TR/css3-values/#angles
     */
    protected function compute_angle_or_zero(string $val): ?float
    {
        $number = self::CSS_NUMBER;
        $pattern = "/^($number)(deg|grad|rad|turn)?$/i";

        if (!preg_match($pattern, $val, $matches)) {
            return null;
        }

        $v = (float) $matches[1];
        $unit = strtolower($matches[2] ?? "");

        switch ($unit) {
            case "deg":
                return $v;
            case "grad":
                return $v * 0.9;
            case "rad":
                return rad2deg($v);
            case "turn":
                return $v * 360;
            default:
                return $v === 0.0 ? $v : null;
        }
    }

    /**
     * Common computation logic for `background-position` and `transform-origin`.
     *
     * @param string $v1
     * @param string $v2
     *
     * @return (float|string|null)[]
     */
    protected function computeBackgroundPositionTransformOrigin(string $v1, string $v2): array
    {
        $x = null;
        $y = null;

        switch ($v1) {
            case "left":
                $x = 0.0;
                break;
            case "right":
                $x = "100%";
                break;
            case "top":
                $y = 0.0;
                break;
            case "bottom":
                $y = "100%";
                break;
            case "center":
                if ($v2 === "left" || $v2 === "right") {
                    $y = "50%";
                } else {
                    $x = "50%";
                }
                break;
            default:
                $x = $this->compute_length_percentage($v1);
                break;
        }

        switch ($v2) {
            case "left":
                $x = 0.0;
                break;
            case "right":
                $x = "100%";
                break;
            case "top":
                $y = 0.0;
                break;
            case "bottom":
                $y = "100%";
                break;
            case "center":
                if ($v1 === "top" || $v1 === "bottom") {
                    $x = "50%";
                } else {
                    $y = "50%";
                }
                break;
            default:
                $y = $this->compute_length_percentage($v2);
                break;
        }

        return [$x, $y];
    }

    /**
     * @link https://www.w3.org/TR/css-lists-3/#typedef-counter-name
     */
    protected function isValidCounterName(string $name): bool
    {
        return $name !== "none"
            && !in_array($name, self::CUSTOM_IDENT_FORBIDDEN, true);
    }

    /**
     * @link https://www.w3.org/TR/css-counter-styles-3/#typedef-counter-style-name
     */
    protected function isValidCounterStyleName(string $name): bool
    {
        return $name !== "none"
            && !in_array($name, self::CUSTOM_IDENT_FORBIDDEN, true);
    }

    /**
     * Parse a property value with 1 to 4 components into 4 values, as required
     * by shorthand properties such as `margin`, `padding`, and `border-radius`.
     *
     * @param string $prop  The shorthand property with exactly 4 sub-properties to handle.
     * @param string $value The property value to parse.
     *
     * @return string[]
     */
    protected function set_quad_shorthand(string $prop, string $value): array
    {
        $v = $this->parse_property_value($value);

        switch (\count($v)) {
            case 1:
                $values = [$v[0], $v[0], $v[0], $v[0]];
                break;
            case 2:
                $values = [$v[0], $v[1], $v[0], $v[1]];
                break;
            case 3:
                $values = [$v[0], $v[1], $v[2], $v[1]];
                break;
            case 4:
                $values = [$v[0], $v[1], $v[2], $v[3]];
                break;
            default:
                return [];
        }

        return array_combine(self::$_props_shorthand[$prop], $values);
    }

    /*======================*/

    /**
     * @link https://www.w3.org/TR/CSS21/visuren.html#display-prop
     */
    protected function _compute_display(string $val)
    {
        $val = strtolower($val);

        // Make sure that common valid, but unsupported display types have an
        // appropriate fallback display type
        switch ($val) {
            case "flow-root":
            case "flex":
            case "grid":
            case "table-caption":
                $val = "block";
                break;
            case "inline-flex":
            case "inline-grid":
                $val = "inline-block";
                break;
        }

        if (!isset(self::$valid_display_types[$val])) {
            return null;
        }

        // https://www.w3.org/TR/CSS21/visuren.html#dis-pos-flo
        if ($this->is_in_flow()) {
            return $val;
        } else {
            switch ($val) {
                case "inline":
                case "inline-block":
                // case "table-row-group":
                // case "table-header-group":
                // case "table-footer-group":
                // case "table-row":
                // case "table-cell":
                // case "table-column-group":
                // case "table-column":
                // case "table-caption":
                    return "block";
                case "inline-table":
                    return "table";
                default:
                    return $val;
            }
        }
    }

    /**
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-color
     */
    protected function _compute_color(string $color)
    {
        return $this->compute_color_value($color);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-color
     */
    protected function _compute_background_color(string $color)
    {
        return $this->compute_color_value($color);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-image
     */
    protected function _compute_background_image(string $val)
    {
        $parsed_val = $this->_stylesheet->resolve_url($val);

        if ($parsed_val === "none") {
            return "none";
        } else {
            return "url(\"" . str_replace("\"", "\\\"", $parsed_val) . "\")";
        }
    }

    /**
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-repeat
     */
    protected function _compute_background_repeat(string $val)
    {
        $keywords = ["repeat", "repeat-x", "repeat-y", "no-repeat"];
        $val = strtolower($val);
        return \in_array($val, $keywords, true) ? $val : null;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-attachment
     */
    protected function _compute_background_attachment(string $val)
    {
        $keywords = ["scroll", "fixed"];
        $val = strtolower($val);
        return \in_array($val, $keywords, true) ? $val : null;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-position
     */
    protected function _compute_background_position(string $val)
    {
        $val = strtolower($val);
        $parts = $this->parse_property_value($val);
        $count = \count($parts);

        if ($count === 0 || $count > 2) {
            return null;
        }

        $v1 = $parts[0];
        $v2 = $parts[1] ?? "center";
        [$x, $y] = $this->computeBackgroundPositionTransformOrigin($v1, $v2);

        if ($x === null || $y === null) {
            return null;
        }

        return [$x, $y];
    }

    /**
     * Compute `background-size`.
     *
     * Computes to one of the following values:
     * * `cover`
     * * `contain`
     * * `[width, height]`, each being a length, percentage, or `auto`
     *
     * @link https://www.w3.org/TR/css-backgrounds-3/#background-size
     */
    protected function _compute_background_size(string $val)
    {
        $val = strtolower($val);

        if ($val === "cover" || $val === "contain") {
            return $val;
        }

        $parts = $this->parse_property_value($val);
        $count = \count($parts);

        if ($count === 0 || $count > 2) {
            return null;
        }

        $width = $parts[0];
        if ($width !== "auto") {
            $width = $this->compute_length_percentage_positive($width);
        }

        $height = $parts[1] ?? "auto";
        if ($height !== "auto") {
            $height = $this->compute_length_percentage_positive($height);
        }

        if ($width === null || $height === null) {
            return null;
        }

        return [$width, $height];
    }

    /**
     * @link https://www.w3.org/TR/css-backgrounds-3/#propdef-background
     */
    protected function _set_background(string $value): array
    {
        $components = $this->parse_property_value($value);
        $props = [];
        $pos_size = [];

        foreach ($components as $val) {
            $lower = strtolower($val);

            if ($lower === "none") {
                $props["background_image"] = $lower;
            } elseif (strncmp($lower, "url(", 4) === 0) {
                $props["background_image"] = $val;
            } elseif ($lower === "scroll" || $lower === "fixed") {
                $props["background_attachment"] = $lower;
            } elseif ($lower === "repeat" || $lower === "repeat-x" || $lower === "repeat-y" || $lower === "no-repeat") {
                $props["background_repeat"] = $lower;
            } elseif ($this->is_color_value($lower)) {
                $props["background_color"] = $lower;
            } else {
                $pos_size[] = $lower;
            }
        }

        if (\count($pos_size)) {
            // Split value list at "/"
            $index = array_search("/", $pos_size, true);

            if ($index !== false) {
                $pos = \array_slice($pos_size, 0, $index);
                $size = \array_slice($pos_size, $index + 1);
            } else {
                $pos = $pos_size;
                $size = [];
            }

            $props["background_position"] = implode(" ", $pos);

            if (\count($size)) {
                $props["background_size"] = implode(" ", $size);
            }
        }

        return $props;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/fonts.html#propdef-font-family
     */
    protected function _compute_font_family(string $val)
    {
        return array_map(
            function ($name) {
                return trim($name, " '\"");
            },
            preg_split("/\s*,\s*/", $val)
        );
    }

    /**
     * @link https://www.w3.org/TR/CSS21/fonts.html#propdef-font-size
     */
    protected function _compute_font_size(string $val)
    {
        $val = strtolower($val);
        $parentFontSize = isset($this->parent_style)
            ? $this->parent_style->__get("font_size")
            : self::$default_font_size;

        switch ($val) {
            case "xx-small":
            case "x-small":
            case "small":
            case "medium":
            case "large":
            case "x-large":
            case "xx-large":
                $computed = self::$default_font_size * self::$font_size_keywords[$val];
                break;

            case "smaller":
                $computed = 8 / 9 * $parentFontSize;
                break;

            case "larger":
                $computed = 6 / 5 * $parentFontSize;
                break;

            default:
                $computed = $this->single_length_in_pt($val, $parentFontSize, $parentFontSize);

                // Negative non-`calc` values are invalid
                if ($computed === null
                    || ($computed < 0 && !preg_match("/^-?[_a-zA-Z]/", $val))
                ) {
                    return null;
                }
                break;
        }

        return $computed;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/fonts.html#propdef-font-style
     */
    protected function _compute_font_style(string $val)
    {
        $val = strtolower($val);
        return $val === "normal" || $val === "italic" || $val === "oblique"
            ? $val
            : null;
    }

    /**
     * @link https://www.w3.org/TR/css-fonts-4/#propdef-font-weight
     */
    protected function _compute_font_weight(string $val)
    {
        $val = strtolower($val);

        switch ($val) {
            case "normal":
                return 400;

            case "bold":
                return 700;

            case "bolder":
                // https://www.w3.org/TR/css-fonts-4/#relative-weights
                $w = isset($this->parent_style)
                    ? $this->parent_style->__get("font_weight")
                    : 400;

                if ($w < 350) {
                    return 400;
                } elseif ($w < 550) {
                    return 700;
                } elseif ($w < 900) {
                    return 900;
                } else {
                    return $w;
                }

            case "lighter":
                // https://www.w3.org/TR/css-fonts-4/#relative-weights
                $w = isset($this->parent_style)
                    ? $this->parent_style->__get("font_weight")
                    : 400;

                if ($w < 100) {
                    return $w;
                } elseif ($w < 550) {
                    return 100;
                } elseif ($w < 750) {
                    return 400;
                } else {
                    return 700;
                }

            default:
                $number = self::CSS_NUMBER;
                $weight = preg_match("/^$number$/", $val)
                    ? (int) $val
                    : null;
                return $weight !== null && $weight >= 1 && $weight <= 1000
                    ? $weight
                    : null;
        }
    }

    /**
     * @link https://www.w3.org/TR/css-fonts-4/#src-desc
     */
    protected function _compute_src(string $val)
    {
        return $val;
    }

    /**
     * Handle the `font` shorthand property.
     *
     * `[ font-style || font-variant || font-weight ] font-size [ / line-height ] font-family`
     *
     * @link https://www.w3.org/TR/CSS21/fonts.html#font-shorthand
     */
    protected function _set_font(string $value): array
    {
        $value = strtolower($value);
        $components = $this->parse_property_value($value);
        $props = [];

        $number = self::CSS_NUMBER;
        $unit = "pt|px|pc|rem|em|ex|in|cm|mm|%";
        $sizePattern = "/^(xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger|$number(?:$unit)|0)$/";
        $sizeIndex = null;

        // Find index of font-size to split the component list
        foreach ($components as $i => $val) {
            if (preg_match($sizePattern, $val)) {
                $sizeIndex = $i;
                $props["font_size"] = $val;
                break;
            }
        }

        // `font-size` is mandatory
        if ($sizeIndex === null) {
            return [];
        }

        // `font-style`, `font-variant`, `font-weight` in any order
        $styleVariantWeight = \array_slice($components, 0, $sizeIndex);
        $stylePattern = "/^(italic|oblique)$/";
        $variantPattern = "/^(small-caps)$/";
        $weightPattern = "/^(bold|bolder|lighter|$number)$/";

        if (\count($styleVariantWeight) > 3) {
            return [];
        }

        foreach ($styleVariantWeight as $val) {
            if ($val === "normal") {
                // Ignore any `normal` value, as it is valid and the initial
                // value for all three properties
            } elseif (!isset($props["font_style"]) && preg_match($stylePattern, $val)) {
                $props["font_style"] = $val;
            } elseif (!isset($props["font_variant"]) && preg_match($variantPattern, $val)) {
                $props["font_variant"] = $val;
            } elseif (!isset($props["font_weight"]) && preg_match($weightPattern, $val)) {
                $props["font_weight"] = $val;
            } else {
                // Duplicates and other values disallowed here
                return [];
            }
        }

        // Optional slash + `line-height` followed by mandatory `font-family`
        $lineFamily = \array_slice($components, $sizeIndex + 1);
        $hasLineHeight = $lineFamily !== [] && $lineFamily[0] === "/";
        $lineHeight = $hasLineHeight ? \array_slice($lineFamily, 1, 1) : [];
        $fontFamily = $hasLineHeight ? \array_slice($lineFamily, 2) : $lineFamily;
        $lineHeightPattern = "/^(normal|$number(?:$unit)?)$/";

        // Missing `font-family` or `line-height` after slash
        if ($fontFamily === []
            || ($hasLineHeight && !preg_match($lineHeightPattern, $lineHeight[0]))
        ) {
            return [];
        }

        if ($hasLineHeight) {
            $props["line_height"] = $lineHeight[0];
        }

        $props["font_family"] = implode("", $fontFamily);

        return $props;
    }

    /**
     * Compute `text-align`.
     *
     * If no alignment is set on the element and the direction is rtl then
     * the property is set to "right", otherwise it is set to "left".
     *
     * @link https://www.w3.org/TR/CSS21/text.html#propdef-text-align
     */
    protected function _compute_text_align(string $val)
    {
        $alignment = strtolower($val);

        if ($alignment === "") {
            $alignment = $this->__get("direction") === "rtl"
                ? "right"
                : "left";
        }

        if (!\in_array($alignment, self::TEXT_ALIGN_KEYWORDS, true)) {
            return null;
        }

        return $alignment;
    }

    /**
     * @link https://www.w3.org/TR/css-text-4/#word-spacing-property
     */
    protected function _compute_word_spacing(string $val)
    {
        $val = strtolower($val);

        if ($val === "normal") {
            return 0.0;
        }

        return $this->compute_length_percentage($val);
    }

    /**
     * @link https://www.w3.org/TR/css-text-4/#letter-spacing-property
     */
    protected function _compute_letter_spacing(string $val)
    {
        $val = strtolower($val);

        if ($val === "normal") {
            return 0.0;
        }

        return $this->compute_length_percentage($val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/visudet.html#propdef-line-height
     */
    protected function _compute_line_height(string $val)
    {
        $val = strtolower($val);

        if ($val === "normal") {
            return $val;
        }

        // Compute number values to string and lengths to float (in pt)
        if (is_numeric($val)) {
            return (string) $val;
        }

        $font_size = $this->__get("font_size");
        $computed = $this->single_length_in_pt($val, $font_size);

        // Negative non-`calc` values are invalid
        if ($computed === null
            || ($computed < 0 && !preg_match("/^-?[_a-zA-Z]/", $val))
        ) {
            return null;
        }

        return $computed;
    }

    /**
     * @link https://www.w3.org/TR/css-text-3/#text-indent-property
     */
    protected function _compute_text_indent(string $val)
    {
        return $this->compute_length_percentage($val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/page.html#propdef-page-break-before
     */
    protected function _compute_page_break_before(string $val)
    {
        $break = strtolower($val);

        if ($break === "left" || $break === "right") {
            $break = "always";
        }

        return $break;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/page.html#propdef-page-break-after
     */
    protected function _compute_page_break_after(string $val)
    {
        $break = strtolower($val);

        if ($break === "left" || $break === "right") {
            $break = "always";
        }

        return $break;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/visudet.html#propdef-width
     */
    protected function _compute_width(string $val)
    {
        $val = strtolower($val);

        if ($val === "auto") {
            return $val;
        }

        return $this->compute_length_percentage_positive($val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/visudet.html#propdef-height
     */
    protected function _compute_height(string $val)
    {
        $val = strtolower($val);

        if ($val === "auto") {
            return $val;
        }

        return $this->compute_length_percentage_positive($val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/visudet.html#propdef-min-width
     */
    protected function _compute_min_width(string $val)
    {
        $val = strtolower($val);

        // Legacy support for `none`, not covered by spec
        if ($val === "auto" || $val === "none") {
            return "auto";
        }

        return $this->compute_length_percentage_positive($val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/visudet.html#propdef-min-height
     */
    protected function _compute_min_height(string $val)
    {
        $val = strtolower($val);

        // Legacy support for `none`, not covered by spec
        if ($val === "auto" || $val === "none") {
            return "auto";
        }

        return $this->compute_length_percentage_positive($val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/visudet.html#propdef-max-width
     */
    protected function _compute_max_width(string $val)
    {
        $val = strtolower($val);

        // Legacy support for `auto`, not covered by spec
        if ($val === "none" || $val === "auto") {
            return "none";
        }

        return $this->compute_length_percentage_positive($val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/visudet.html#propdef-max-height
     */
    protected function _compute_max_height(string $val)
    {
        $val = strtolower($val);

        // Legacy support for `auto`, not covered by spec
        if ($val === "none" || $val === "auto") {
            return "none";
        }

        return $this->compute_length_percentage_positive($val);
    }

    /**
     * @link https://www.w3.org/TR/css-position-3/#inset-properties
     * @link https://www.w3.org/TR/css-position-3/#propdef-inset
     */
    protected function _set_inset(string $val): array
    {
        return $this->set_quad_shorthand("inset", $val);
    }

    /**
     * @param string $val
     * @return float|string|null
     */
    protected function compute_box_inset(string $val)
    {
        $val = strtolower($val);

        if ($val === "auto") {
            return $val;
        }

        return $this->compute_length_percentage($val);
    }

    protected function _compute_top(string $val)
    {
        return $this->compute_box_inset($val);
    }

    protected function _compute_right(string $val)
    {
        return $this->compute_box_inset($val);
    }

    protected function _compute_bottom(string $val)
    {
        return $this->compute_box_inset($val);
    }

    protected function _compute_left(string $val)
    {
        return $this->compute_box_inset($val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/box.html#margin-properties
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-margin
     */
    protected function _set_margin(string $val): array
    {
        return $this->set_quad_shorthand("margin", $val);
    }

    /**
     * @param string $val
     * @return float|string|null
     */
    protected function compute_margin(string $val)
    {
        $val = strtolower($val);

        // Legacy support for `none` keyword, not covered by spec
        if ($val === "none") {
            return 0.0;
        }

        if ($val === "auto") {
            return $val;
        }

        return $this->compute_length_percentage($val);
    }

    protected function _compute_margin_top(string $val)
    {
        return $this->compute_margin($val);
    }

    protected function _compute_margin_right(string $val)
    {
        return $this->compute_margin($val);
    }

    protected function _compute_margin_bottom(string $val)
    {
        return $this->compute_margin($val);
    }

    protected function _compute_margin_left(string $val)
    {
        return $this->compute_margin($val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/box.html#padding-properties
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-padding
     */
    protected function _set_padding(string $val): array
    {
        return $this->set_quad_shorthand("padding", $val);
    }

    /**
     * @param string $val
     * @return float|string|null
     */
    protected function compute_padding(string $val)
    {
        $val = strtolower($val);

        // Legacy support for `none` keyword, not covered by spec
        if ($val === "none") {
            return 0.0;
        }

        return $this->compute_length_percentage_positive($val);
    }

    protected function _compute_padding_top(string $val)
    {
        return $this->compute_padding($val);
    }

    protected function _compute_padding_right(string $val)
    {
        return $this->compute_padding($val);
    }

    protected function _compute_padding_bottom(string $val)
    {
        return $this->compute_padding($val);
    }

    protected function _compute_padding_left(string $val)
    {
        return $this->compute_padding($val);
    }

    /**
     * @param string   $value  `width || style || color`
     * @param string[] $styles The list of border styles to accept.
     *
     * @return string[]|null Array of `[width, style, color]`, or `null` if the declaration is invalid.
     */
    protected function parse_border_side(string $value, array $styles = self::BORDER_STYLES): ?array
    {
        $value = strtolower($value);
        $components = $this->parse_property_value($value);
        $width = null;
        $style = null;
        $color = null;

        foreach ($components as $val) {
            if ($style === null && \in_array($val, $styles, true)) {
                $style = $val;
            } elseif ($color === null && $this->is_color_value($val)) {
                $color = $val;
            } elseif ($width === null) {
                // Assume width
                $width = $val;
            } else {
                // Duplicates are not allowed
                return null;
            }
        }

        return [$width, $style, $color];
    }

    /**
     * @link https://www.w3.org/TR/CSS21/box.html#border-properties
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-border
     */
    protected function _set_border(string $value): array
    {
        $values = $this->parse_border_side($value);

        if ($values === null) {
            return [];
        }

        return array_merge(
            array_combine(self::$_props_shorthand["border_top"], $values),
            array_combine(self::$_props_shorthand["border_right"], $values),
            array_combine(self::$_props_shorthand["border_bottom"], $values),
            array_combine(self::$_props_shorthand["border_left"], $values)
        );
    }

    /**
     * @param string $prop
     * @param string $value
     * @return array
     */
    protected function set_border_side(string $prop, string $value): array
    {
        $values = $this->parse_border_side($value);

        if ($values === null) {
            return [];
        }

        return array_combine(self::$_props_shorthand[$prop], $values);
    }

    protected function _set_border_top(string $val): array
    {
        return $this->set_border_side("border_top", $val);
    }

    protected function _set_border_right(string $val): array
    {
        return $this->set_border_side("border_right", $val);
    }

    protected function _set_border_bottom(string $val): array
    {
        return $this->set_border_side("border_bottom", $val);
    }

    protected function _set_border_left(string $val): array
    {
        return $this->set_border_side("border_left", $val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-border-color
     */
    protected function _set_border_color(string $val): array
    {
        return $this->set_quad_shorthand("border_color", $val);
    }

    protected function _compute_border_top_color(string $val)
    {
        return $this->compute_color_value($val);
    }

    protected function _compute_border_right_color(string $val)
    {
        return $this->compute_color_value($val);
    }

    protected function _compute_border_bottom_color(string $val)
    {
        return $this->compute_color_value($val);
    }

    protected function _compute_border_left_color(string $val)
    {
        return $this->compute_color_value($val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-border-style
     */
    protected function _set_border_style(string $val): array
    {
        return $this->set_quad_shorthand("border_style", $val);
    }

    protected function _compute_border_top_style(string $val)
    {
        return $this->compute_border_style($val);
    }

    protected function _compute_border_right_style(string $val)
    {
        return $this->compute_border_style($val);
    }

    protected function _compute_border_bottom_style(string $val)
    {
        return $this->compute_border_style($val);
    }

    protected function _compute_border_left_style(string $val)
    {
        return $this->compute_border_style($val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-border-width
     */
    protected function _set_border_width(string $val): array
    {
        return $this->set_quad_shorthand("border_width", $val);
    }

    protected function _compute_border_top_width(string $val)
    {
        return $this->compute_line_width($val, "border_top_style");
    }

    protected function _compute_border_right_width(string $val)
    {
        return $this->compute_line_width($val, "border_right_style");
    }

    protected function _compute_border_bottom_width(string $val)
    {
        return $this->compute_line_width($val, "border_bottom_style");
    }

    protected function _compute_border_left_width(string $val)
    {
        return $this->compute_line_width($val, "border_left_style");
    }

    /**
     * @link https://www.w3.org/TR/css-backgrounds-3/#corners
     * @link https://www.w3.org/TR/css-backgrounds-3/#propdef-border-radius
     */
    protected function _set_border_radius(string $val): array
    {
        return $this->set_quad_shorthand("border_radius", $val);
    }

    protected function _compute_border_top_left_radius(string $val)
    {
        return $this->compute_length_percentage_positive($val);
    }

    protected function _compute_border_top_right_radius(string $val)
    {
        return $this->compute_length_percentage_positive($val);
    }

    protected function _compute_border_bottom_right_radius(string $val)
    {
        return $this->compute_length_percentage_positive($val);
    }

    protected function _compute_border_bottom_left_radius(string $val)
    {
        return $this->compute_length_percentage_positive($val);
    }

    /**
     * @link https://www.w3.org/TR/css-ui-4/#outline-props
     * @link https://www.w3.org/TR/css-ui-4/#propdef-outline
     */
    protected function _set_outline(string $value): array
    {
        $values = $this->parse_border_side($value, self::OUTLINE_STYLES);

        if ($values === null) {
            return [];
        }

        return array_combine(self::$_props_shorthand["outline"], $values);
    }

    protected function _compute_outline_color(string $val)
    {
        return $this->compute_color_value($val);
    }

    protected function _compute_outline_style(string $val)
    {
        $val = strtolower($val);
        return \in_array($val, self::OUTLINE_STYLES, true) ? $val : null;
    }

    protected function _compute_outline_width(string $val)
    {
        return $this->compute_line_width($val, "outline_style");
    }

    /**
     * @link https://www.w3.org/TR/css-ui-4/#propdef-outline-offset
     */
    protected function _compute_outline_offset(string $val)
    {
        return $this->compute_length($val);
    }

    /**
     * Compute `border-spacing` to two lengths of the form
     * `[horizontal, vertical]`.
     *
     * @link https://www.w3.org/TR/CSS21/tables.html#propdef-border-spacing
     */
    protected function _compute_border_spacing(string $val)
    {
        $val = strtolower($val);
        $parts = $this->parse_property_value($val);
        $count = \count($parts);

        if ($count === 0 || $count > 2) {
            return null;
        }

        $h = $this->compute_length_positive($parts[0]);
        $v = isset($parts[1])
            ? $this->compute_length_positive($parts[1])
            : $h;

        if ($h === null || $v === null) {
            return null;
        }

        return [$h, $v];
    }

    /**
     * @link https://www.w3.org/TR/CSS21/generate.html#propdef-list-style-image
     */
    protected function _compute_list_style_image(string $val)
    {
        $parsed_val = $this->_stylesheet->resolve_url($val);

        if ($parsed_val === "none") {
            return "none";
        } else {
            return "url(\"" . str_replace("\"", "\\\"", $parsed_val) . "\")";
        }
    }

    /**
     * @link https://www.w3.org/TR/CSS21/generate.html#propdef-list-style-type
     */
    protected function _compute_list_style_position(string $val)
    {
        $val = strtolower($val);
        return $val === "inside" || $val === "outside" ? $val : null;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/generate.html#propdef-list-style-type
     */
    protected function _compute_list_style_type(string $val)
    {
        $val = strtolower($val);

        if ($val === "none") {
            return $val;
        }

        $ident = self::CSS_IDENTIFIER;
        return $val !== "default" && preg_match("/^$ident$/", $val)
            ? $val
            : null;
    }

    /**
     * Handle the `list-style` shorthand property.
     *
     * `[ list-style-position || list-style-image || list-style-type ]`
     *
     * @link https://www.w3.org/TR/css-lists-3/#list-style-property
     */
    protected function _set_list_style(string $value): array
    {
        $components = $this->parse_property_value($value);
        $none = 0;
        $position = null;
        $image = null;
        $type = null;

        foreach ($components as $val) {
            $lower = strtolower($val);

            // `none` can occur max 2 times (for image and type each)
            if ($none < 2 && $lower === "none") {
                $none++;
            } elseif ($position === null && ($lower === "inside" || $lower === "outside")) {
                $position = $lower;
            } elseif ($image === null && strncmp($lower, "url(", 4) === 0) {
                $image = $val;
            } elseif ($type === null) {
                $type = $val;
            } else {
                // Duplicates are not allowed
                return [];
            }
        }

        // From the spec:
        // Using a value of `none` in the shorthand is potentially ambiguous, as
        // `none` is a valid value for both `list-style-image` and `list-style-type`.
        // To resolve this ambiguity, a value of `none` in the shorthand must be
        // applied to whichever of the two properties aren’t otherwise set by
        // the shorthand.
        if ($none === 2) {
            if ($image !== null || $type !== null) {
                return [];
            }

            $image = "none";
            $type = "none";
        } elseif ($none === 1) {
            if ($image !== null && $type !== null) {
                return [];
            }

            $image = $image ?? "none";
            $type = $type ?? "none";
        }

        return [
            "list_style_position" => $position,
            "list_style_image" => $image,
            "list_style_type" => $type
        ];
    }

    /**
     * @param string $value
     * @param int    $default
     * @param bool   $sumDuplicates
     *
     * @return array|string|null
     */
    protected function compute_counter_prop(string $value, int $default, bool $sumDuplicates = false)
    {
        $lower = strtolower($value);

        if ($lower === "none") {
            return $lower;
        }

        $ident = self::CSS_IDENTIFIER;
        $integer = self::CSS_INTEGER;
        $counterDef = "($ident)(?:\s+($integer))?";
        $validationPattern = "/^$counterDef(\s+$counterDef)*$/";

        if (!preg_match($validationPattern, $value)) {
            return null;
        }

        preg_match_all("/$counterDef/", $value, $matches, PREG_SET_ORDER);
        $counters = [];

        foreach ($matches as $match) {
            $name = $match[1];

            if (!$this->isValidCounterName($name)) {
                return null;
            }

            $value = isset($match[2]) ? (int) $match[2] : $default;
            $counters[$name] = $sumDuplicates
                ? ($counters[$name] ?? 0) + $value
                : $value;
        }

        return $counters;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/generate.html#propdef-counter-increment
     */
    protected function _compute_counter_increment(string $val)
    {
        return $this->compute_counter_prop($val, 1, true);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/generate.html#propdef-counter-reset
     */
    protected function _compute_counter_reset(string $val)
    {
        return $this->compute_counter_prop($val, 0);
    }

    /**
     * @link https://www.w3.org/TR/css-content-3/#quotes
     */
    protected function _compute_quotes(string $val)
    {
        $lower = strtolower($val);

        // `auto` is resolved in the getter, so it can inherit as is
        if ($lower === "none" || $lower === "auto") {
            return $lower;
        }

        $components = $this->parse_property_value($val);
        $quotes = [];

        foreach ($components as $value) {
            if (strncmp($value, '"', 1) !== 0
                && strncmp($value, "'", 1) !== 0
            ) {
                return null;
            }

            $quotes[] = $this->_stylesheet->parse_string($value);
        }

        if ($quotes === [] || \count($quotes) % 2 !== 0) {
            return null;
        }

        return array_chunk($quotes, 2);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/generate.html#propdef-content
     * @link https://www.w3.org/TR/css-content-3/#propdef-content
     */
    protected function _compute_content(string $val)
    {
        $lower = strtolower($val);

        if ($lower === "normal" || $lower === "none") {
            return $lower;
        }

        $components = $this->parse_property_value($val);
        $parts = [];

        if ($components === []) {
            return null;
        }

        foreach ($components as $value) {
            // String
            if (strncmp($value, '"', 1) === 0 || strncmp($value, "'", 1) === 0) {
                $parts[] = new StringPart($this->_stylesheet->parse_string($value));
                continue;
            }

            $lower = strtolower($value);

            // Keywords
            if ($lower === "open-quote") {
                $parts[] = new OpenQuote;
                continue;
            } elseif ($lower === "close-quote") {
                $parts[] = new CloseQuote;
                continue;
            } elseif ($lower === "no-open-quote") {
                $parts[] = new NoOpenQuote;
                continue;
            } elseif ($lower === "no-close-quote") {
                $parts[] = new NoCloseQuote;
                continue;
            }

            // Functional components
            $pos = strpos($lower, "(");

            if ($pos === false) {
                return null;
            }

            // `parse_property_value` ensures that the value is of the form
            // `function(arguments)` at this point
            $function = substr($lower, 0, $pos);
            $arguments = trim(substr($value, $pos + 1, -1));

            // attr()
            if ($function === "attr") {
                $attr = strtolower($arguments);

                if ($attr === "") {
                    return null;
                }

                $parts[] = new Attr($attr);
            }

            // counter(name [, style])
            elseif ($function === "counter") {
                $ident = self::CSS_IDENTIFIER;

                if (!preg_match("/^($ident)(?:\s*,\s*($ident))?$/", $arguments, $matches)) {
                    return null;
                }

                $name = $matches[1];
                $type = isset($matches[2]) ? strtolower($matches[2]) : "decimal";

                if (!$this->isValidCounterName($name)
                    || !$this->isValidCounterStyleName($type)
                ) {
                    return null;
                }

                $parts[] = new Counter($name, $type);
            }

            // counters(name, string [, style])
            elseif ($function === "counters") {
                $ident = self::CSS_IDENTIFIER;
                $string = self::CSS_STRING;

                if (!preg_match("/^($ident)\s*,\s*($string)(?:\s*,\s*($ident))?$/", $arguments, $matches)) {
                    return null;
                }

                $name = $matches[1];
                $string = $this->_stylesheet->parse_string($matches[2]);
                $type = isset($matches[3]) ? strtolower($matches[3]) : "decimal";

                if (!$this->isValidCounterName($name)
                    || !$this->isValidCounterStyleName($type)
                ) {
                    return null;
                }

                $parts[] = new Counters($name, $string, $type);
            }

            // url()
            elseif ($function === "url") {
                $url = $this->_stylesheet->parse_string($arguments);
                $parts[] = new Url($url);
            }

            else {
                return null;
            }
        }

        return $parts;
    }

    /**
     * @link https://www.w3.org/TR/css-page-3/#page-size-prop
     */
    protected function _compute_size(string $val)
    {
        $val = strtolower($val);

        if ($val === "auto") {
            return $val;
        }

        $parts = $this->parse_property_value($val);
        $count = \count($parts);

        if ($count === 0 || $count > 3) {
            return null;
        }

        $size = null;
        $orientation = null;
        $lengths = [];

        foreach ($parts as $part) {
            if ($size === null && isset(CPDF::$PAPER_SIZES[$part])) {
                $size = $part;
            } elseif ($orientation === null && ($part === "portrait" || $part === "landscape")) {
                $orientation = $part;
            } else {
                $lengths[] = $part;
            }
        }

        if ($size !== null && $lengths !== []) {
            return null;
        }

        if ($size !== null) {
            // Standard paper size
            [$l1, $l2] = \array_slice(CPDF::$PAPER_SIZES[$size], 2, 2);
        } elseif ($lengths === []) {
            // Orientation only, use default paper size
            $dims = $this->_stylesheet->get_dompdf()->getPaperSize();
            [$l1, $l2] = \array_slice($dims, 2, 2);
        } else {
            // Custom paper size
            $l1 = $this->compute_length_positive($lengths[0]);
            $l2 = isset($lengths[1]) ? $this->compute_length_positive($lengths[1]) : $l1;

            if ($l1 === null || $l2 === null) {
                return null;
            }
        }

        if (($orientation === "portrait" && $l1 > $l2)
            || ($orientation === "landscape" && $l2 > $l1)
        ) {
            return [$l2, $l1];
        }

        return [$l1, $l2];
    }

    /**
     * @link https://www.w3.org/TR/css-transforms-1/#transform-property
     */
    protected function _compute_transform(string $val)
    {
        $val = strtolower($val);

        if ($val === "none") {
            return [];
        }

        $parts = $this->parse_property_value($val);
        $transforms = [];

        if ($parts === []) {
            return null;
        }

        foreach ($parts as $part) {
            if (!preg_match("/^([a-z]+)\((.+)\)$/s", $part, $matches)) {
                return null;
            }

            $name = $matches[1];
            $arguments = trim($matches[2]);
            $values = $this->parse_property_value($arguments);
            $values = array_values(array_filter($values, function ($v) {
                return $v !== ",";
            }));
            $count = \count($values);

            if ($count === 0) {
                return null;
            }

            switch ($name) {
                // case "matrix":
                //     if ($count !== 6) {
                //         return null;
                //     }

                //     $values = array_map([$this, "compute_number"], $values);
                //     break;

                // <length-percentage> units
                case "translate":
                    if ($count > 2) {
                        return null;
                    }

                    $values = [
                        $this->compute_length_percentage($values[0]),
                        isset($values[1]) ? $this->compute_length_percentage($values[1]) : 0.0
                    ];
                    break;

                case "translatex":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "translate";
                    $values = [$this->compute_length_percentage($values[0]), 0.0];
                    break;

                case "translatey":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "translate";
                    $values = [0.0, $this->compute_length_percentage($values[0])];
                    break;

                // <number> units
                case "scale":
                    if ($count > 2) {
                        return null;
                    }

                    $v0 = $this->compute_number($values[0]);
                    $v1 = isset($values[1]) ? $this->compute_number($values[1]) : $v0;
                    $values = [$v0, $v1];
                    break;

                case "scalex":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "scale";
                    $values = [$this->compute_number($values[0]), 1.0];
                    break;

                case "scaley":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "scale";
                    $values = [1.0, $this->compute_number($values[0])];
                    break;

                // <angle> units
                case "rotate":
                    if ($count > 1) {
                        return null;
                    }

                    $values = [$this->compute_angle_or_zero($values[0])];
                    break;

                case "skew":
                    if ($count > 2) {
                        return null;
                    }

                    $values = [
                        $this->compute_angle_or_zero($values[0]),
                        isset($values[1]) ? $this->compute_angle_or_zero($values[1]) : 0.0
                    ];
                    break;

                case "skewx":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "skew";
                    $values = [$this->compute_angle_or_zero($values[0]), 0.0];
                    break;

                case "skewy":
                    if ($count > 1) {
                        return null;
                    }

                    $name = "skew";
                    $values = [0.0, $this->compute_angle_or_zero($values[0])];
                    break;

                default:
                    return null;
            }

            foreach ($values as $v) {
                if ($v === null) {
                    return null;
                }
            }

            $transforms[] = [$name, $values];
        }

        return $transforms;
    }

    /**
     * @link https://www.w3.org/TR/css-transforms-1/#transform-origin-property
     */
    protected function _compute_transform_origin(string $val)
    {
        $val = strtolower($val);
        $parts = $this->parse_property_value($val);
        $count = \count($parts);

        if ($count === 0 || $count > 3) {
            return null;
        }

        $v1 = $parts[0];
        $v2 = $parts[1] ?? "center";
        [$x, $y] = $this->computeBackgroundPositionTransformOrigin($v1, $v2);
        $z = $count === 3 ? $this->compute_length($parts[2]) : 0.0;

        if ($x === null || $y === null || $z === null) {
            return null;
        }

        return [$x, $y, $z];
    }

    /**
     * @param string $val
     * @return string|null
     */
    protected function parse_image_resolution(string $val): ?string
    {
        // If exif data could be get:
        // $re = '/^\s*(\d+|normal|auto)(?:\s*,\s*(\d+|normal))?\s*$/';

        $val = strtolower($val);
        $re = '/^\s*(\d+|normal|auto)\s*$/';

        if (!preg_match($re, $val, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * auto | normal | dpi
     */
    protected function _compute_background_image_resolution(string $val)
    {
        return $this->parse_image_resolution($val);
    }

    /**
     * auto | normal | dpi
     */
    protected function _compute_image_resolution(string $val)
    {
        return $this->parse_image_resolution($val);
    }

    /**
     * @link https://www.w3.org/TR/css-break-3/#propdef-orphans
     */
    protected function _compute_orphans(string $val)
    {
        return $this->compute_integer($val);
    }

    /**
     * @link https://www.w3.org/TR/css-break-3/#propdef-widows
     */
    protected function _compute_widows(string $val)
    {
        return $this->compute_integer($val);
    }

    /**
     * @link https://www.w3.org/TR/css-color-4/#propdef-opacity
     */
    protected function _compute_opacity(string $val)
    {
        $number = self::CSS_NUMBER;
        $pattern = "/^($number)(%?)$/";

        if (!preg_match($pattern, $val, $matches)) {
            return null;
        }

        $v = (float) $matches[1];
        $percent = $matches[2] === "%";
        $opacity = $percent ? ($v / 100) : $v;

        return max(0.0, min($opacity, 1.0));
    }

    /**
     * @link https://www.w3.org/TR/CSS21//visuren.html#propdef-z-index
     */
    protected function _compute_z_index(string $val)
    {
        $val = strtolower($val);

        if ($val === "auto") {
            return $val;
        }

        return $this->compute_integer($val);
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

    /**
     * Generate a string representation of the Style
     *
     * This dumps the entire property array into a string via print_r.  Useful
     * for debugging.
     *
     * @return string
     */
    /*DEBUGCSS print: see below additional debugging util*/
    public function __toString(): string
    {
        $parent_font_size = $this->parent_style
            ? $this->parent_style->font_size
            : self::$default_font_size;

        return print_r(array_merge(["parent_font_size" => $parent_font_size],
            $this->_props), true);
    }

    /*DEBUGCSS*/
    public function debug_print(): void
    {
        $parent_font_size = $this->parent_style
            ? $this->parent_style->font_size
            : self::$default_font_size;

        print "    parent_font_size:" . $parent_font_size . ";\n";
        print "    Props [\n";
        print "      specified [\n";
        foreach ($this->_props as $prop => $val) {
            print '        ' . $prop . ': ' . preg_replace("/\r\n/", ' ', print_r($val, true));
            if (isset($this->_important_props[$prop])) {
                print ' !important';
            }
            print ";\n";
        }
        print "      ]\n";
        print "      computed [\n";
        foreach ($this->_props_computed as $prop => $val) {
            print '        ' . $prop . ': ' . preg_replace("/\r\n/", ' ', print_r($val, true));
            print ";\n";
        }
        print "      ]\n";
        print "      cached [\n";
        foreach ($this->_props_used as $prop => $val) {
            print '        ' . $prop . ': ' . preg_replace("/\r\n/", ' ', print_r($val, true));
            print ";\n";
        }
        print "      ]\n";
        print "    ]\n";
    }
}
