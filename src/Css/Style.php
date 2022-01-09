<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace Dompdf\Css;

use Dompdf\Adapter\CPDF;
use Dompdf\Exception;
use Dompdf\FontMetrics;
use Dompdf\Frame;

/**
 * Represents CSS properties.
 *
 * The Style class is responsible for handling and storing CSS properties.
 * It includes methods to resolve colors and lengths, as well as getters &
 * setters for many CSS properties.
 *
 * Actual CSS parsing is performed in the {@link Stylesheet} class.
 *
 * @package dompdf
 */
class Style
{
    const CSS_IDENTIFIER = "-?[_a-zA-Z]+[_a-zA-Z0-9-]*";
    const CSS_INTEGER = "[+-]?\d+";
    const CSS_NUMBER = "[+-]?\d*\.?\d+";

    /**
     * Default font size, in points.
     *
     * @var float
     */
    static $default_font_size = 12;

    /**
     * Default line height, as a fraction of the font size.
     *
     * @var float
     */
    static $default_line_height = 1.2;

    /**
     * Default "absolute" font sizes relative to the default font-size
     * http://www.w3.org/TR/css3-fonts/#font-size-the-font-size-property
     * @var array<float>
     */
    static $font_size_keywords = [
        "xx-small" => 0.6, // 3/5
        "x-small" => 0.75, // 3/4
        "small" => 0.889, // 8/9
        "medium" => 1, // 1
        "large" => 1.2, // 6/5
        "x-large" => 1.5, // 3/2
        "xx-large" => 2.0, // 2/1
    ];

    /**
     * List of valid text-align keywords.  Should also really be a constant.
     *
     * @var array
     */
    static $text_align_keywords = ["left", "right", "center", "justify"];

    /**
     * List of valid vertical-align keywords.  Should also really be a constant.
     *
     * @var array
     */
    static $vertical_align_keywords = ["baseline", "bottom", "middle", "sub",
        "super", "text-bottom", "text-top", "top"];

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
     * List of all inline (inner) display types.  Should really be a constant.
     *
     * @var array
     */
    static $INLINE_TYPES = ["inline"];

    /**
     * List of all block (inner) display types.  Should really be a constant.
     *
     * @var array
     */
    static $BLOCK_TYPES = ["block", "inline-block", "table-cell", "list-item"];

    /**
     * List of all table (inner) display types.  Should really be a constant.
     *
     * @var array
     */
    static $TABLE_TYPES = ["table", "inline-table"];

    /**
     * Lookup table for valid display types. Initially computed from the
     * different constants.
     *
     * @var array
     */
    protected static $valid_display_types = [];

    /**
     * List of all positioned types.  Should really be a constant.
     *
     * @var array
     */
    static $POSITIONNED_TYPES = ["relative", "absolute", "fixed"];

    /**
     * List of valid border styles.  Should also really be a constant.
     *
     * @var array
     */
    static $BORDER_STYLES = ["none", "hidden", "dotted", "dashed", "solid",
        "double", "groove", "ridge", "inset", "outset"];

    /**
     * Map of CSS shorthand properties and their corresponding sub-properties.
     * The order of the sub-properties is relevant for the fallback getter,
     * which is used in case no specific getter method is defined.
     *
     * @var array
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
            "border_width",
            "border_style",
            "border_color"
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
     * @var array
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
     * @link http://www.w3.org/TR/CSS21/propidx.html
     *
     * @var array
     */
    protected static $_defaults = null;

    /**
     * List of inherited properties
     *
     * @link http://www.w3.org/TR/CSS21/propidx.html
     *
     * @var array
     */
    protected static $_inherited = null;

    /**
     * Caches method_exists result
     *
     * @var array<bool>
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
     * @var array
     */
    protected $_media_queries;

    /**
     * Specified (or declared) values of the CSS properties.
     * https://www.w3.org/TR/css-cascade-3/#value-stages
     *
     * @var array
     */
    protected $_props = [];

    /**
     * Properties set by an `!important` declaration.
     *
     * @var array
     */
    protected $_important_props = [];

    /**
     * Computed values of the CSS properties.
     *
     * @var array
     */
    protected $_props_computed = [];

    /**
     * Used values of the CSS properties.
     *
     * @var array
     */
    protected $_prop_cache = [];

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
            "padding_left"
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
     * @var array
     */
    protected static $_dependent_props = [];

    /**
     * Style of the parent element in document tree.
     *
     * @var Style
     */
    protected $parent_style;

    /**
     * @var Frame
     */
    protected $_frame;

    /**
     * The origin of the style
     *
     * @var int
     */
    protected $_origin = Stylesheet::ORIG_AUTHOR;

    // private members
    /**
     * The computed bottom spacing
     */
    private $_computed_bottom_spacing = null;

    /**
     * @var bool
     */
    private $has_border_radius_cache = null;

    /**
     * @var array
     */
    private $resolved_border_radius = null;

    /**
     * @var FontMetrics
     */
    private $fontMetrics;

    /**
     * Class constructor
     *
     * @param Stylesheet $stylesheet the stylesheet this Style is associated with.
     * @param int $origin
     */
    public function __construct(Stylesheet $stylesheet, $origin = Stylesheet::ORIG_AUTHOR)
    {
        $this->setFontMetrics($stylesheet->getFontMetrics());

        $this->_stylesheet = $stylesheet;
        $this->_media_queries = [];
        $this->_origin = $origin;
        $this->parent_style = null;

        if (!isset(self::$_defaults)) {

            // Shorthand
            $d =& self::$_defaults;

            // All CSS 2.1 properties, and their default values
            $d["azimuth"] = "center";
            $d["background_attachment"] = "scroll";
            $d["background_color"] = "transparent";
            $d["background_image"] = "none";
            $d["background_image_resolution"] = "normal";
            $d["background_position"] = "0% 0%";
            $d["background_repeat"] = "repeat";
            $d["background"] = "";
            $d["border_collapse"] = "separate";
            $d["border_color"] = "";
            $d["border_spacing"] = "0";
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
            $d["border_bottom_left_radius"] = "0";
            $d["border_bottom_right_radius"] = "0";
            $d["border_top_left_radius"] = "0";
            $d["border_top_right_radius"] = "0";
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
            $d["font_weight"] = "normal";
            $d["font"] = "";
            $d["height"] = "auto";
            $d["image_resolution"] = "normal";
            $d["left"] = "auto";
            $d["letter_spacing"] = "normal";
            $d["line_height"] = "normal";
            $d["list_style_image"] = "none";
            $d["list_style_position"] = "outside";
            $d["list_style_type"] = "disc";
            $d["list_style"] = "";
            $d["margin_right"] = "0";
            $d["margin_left"] = "0";
            $d["margin_top"] = "0";
            $d["margin_bottom"] = "0";
            $d["margin"] = "";
            $d["max_height"] = "none";
            $d["max_width"] = "none";
            $d["min_height"] = "auto";
            $d["min_width"] = "auto";
            $d["orphans"] = "2";
            $d["outline_color"] = "currentcolor"; // "invert" special color is not supported
            $d["outline_style"] = "none";
            $d["outline_width"] = "medium";
            $d["outline_offset"] = "0";
            $d["outline"] = "";
            $d["overflow"] = "visible";
            $d["overflow_wrap"] = "normal";
            $d["padding_top"] = "0";
            $d["padding_right"] = "0";
            $d["padding_bottom"] = "0";
            $d["padding_left"] = "0";
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
            $d["text_indent"] = "0";
            $d["text_transform"] = "none";
            $d["top"] = "auto";
            $d["unicode_bidi"] = "normal";
            $d["vertical_align"] = "baseline";
            $d["visibility"] = "visible";
            $d["voice_family"] = "";
            $d["volume"] = "medium";
            $d["white_space"] = "normal";
            $d["widows"] = "2";
            $d["width"] = "auto";
            $d["word_spacing"] = "normal";
            $d["z_index"] = "auto";

            // CSS3
            $d["opacity"] = "1.0";
            $d["background_size"] = "auto auto";
            $d["transform"] = "none";
            $d["transform_origin"] = "50% 50%";

            // for @font-face
            $d["src"] = "";
            $d["unicode_range"] = "";

            // vendor-prefixed properties
            $d["_dompdf_keep"] = "";

            // Properties that inherit by default
            self::$_inherited = [
                "azimuth",
                "background_image_resolution",
                "border_collapse",
                "border_spacing",
                "caption_side",
                "color",
                "cursor",
                "direction",
                "elevation",
                "empty_cells",
                "font_family",
                "font_size",
                "font_style",
                "font_variant",
                "font_weight",
                "font",
                "image_resolution",
                "letter_spacing",
                "line_height",
                "list_style_image",
                "list_style_position",
                "list_style_type",
                "list_style",
                "orphans",
                "overflow_wrap",
                "pitch_range",
                "pitch",
                "quotes",
                "richness",
                "speak_header",
                "speak_numeral",
                "speak_punctuation",
                "speak",
                "speech_rate",
                "stress",
                "text_align",
                "text_indent",
                "text_transform",
                "visibility",
                "voice_family",
                "volume",
                "white_space",
                "widows",
                "word_spacing",
            ];

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
     * "Destructor": forcibly free all references held by this object
     */
    function dispose()
    {
    }

    /**
     * @param $media_queries
     */
    function set_media_queries($media_queries)
    {
        $this->_media_queries = $media_queries;
    }

    /**
     * @return array|int
     */
    function get_media_queries()
    {
        return $this->_media_queries;
    }

    /**
     * @param Frame $frame
     */
    function set_frame(Frame $frame)
    {
        $this->_frame = $frame;
    }

    /**
     * @return Frame
     */
    function get_frame()
    {
        return $this->_frame;
    }

    /**
     * @param $origin
     */
    function set_origin($origin)
    {
        $this->_origin = $origin;
    }

    /**
     * @return int
     */
    function get_origin()
    {
        return $this->_origin;
    }

    /**
     * returns the {@link Stylesheet} this Style is associated with.
     *
     * @return Stylesheet
     */
    function get_stylesheet()
    {
        return $this->_stylesheet;
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
    function length_in_pt($length, ?float $ref_size = null)
    {
        $font_size = $this->__get("font_size");
        $ref_size = $ref_size ?? $font_size;

        if (!is_array($length)) {
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

            // FIXME: Using the ref size as fallback here currently ensures that
            // invalid widths or heights are treated as the corresponding
            // containing-block dimension, which can look like the declaration
            // is being ignored. Implement proper compute methods instead, and
            // fall back to 0 here
            $ret += $val ?? $ref_size;
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

        $key = "$l/$ref_size/$font_size";

        if (isset($cache[$key])) {
            return $cache[$key];
        }

        if (is_numeric($l)) {
            // Legacy support for unitless values, not covered by spec. Might
            // want to restrict this to unitless `0` in the future
            $value = (float) $l;
        }

        elseif (($i = mb_stripos($l, "%")) !== false) {
            $value = (float)mb_substr($l, 0, $i) / 100 * $ref_size;
        }

        elseif (($i = mb_stripos($l, "px")) !== false) {
            $dpi = $this->_stylesheet->get_dompdf()->getOptions()->getDpi();
            $value = ((float)mb_substr($l, 0, $i) * 72) / $dpi;
        }

        elseif (($i = mb_stripos($l, "pt")) !== false) {
            $value = (float)mb_substr($l, 0, $i);
        }

        elseif (($i = mb_stripos($l, "rem")) !== false) {
            $root_style = $this->_stylesheet->get_dompdf()->getTree()->get_root()->get_style();
            $root_font_size = $root_style === null || $root_style === $this
                ? $font_size
                : $root_style->font_size;
            $value = (float)mb_substr($l, 0, $i) * $root_font_size;
        }

        elseif (($i = mb_stripos($l, "em")) !== false) {
            $value = (float)mb_substr($l, 0, $i) * $font_size;
        }

        elseif (($i = mb_stripos($l, "cm")) !== false) {
            $value = (float)mb_substr($l, 0, $i) * 72 / 2.54;
        }

        elseif (($i = mb_stripos($l, "mm")) !== false) {
            $value = (float)mb_substr($l, 0, $i) * 72 / 25.4;
        }

        elseif (($i = mb_stripos($l, "ex")) !== false) {
            // FIXME: em:ex ratio?
            $value = (float)mb_substr($l, 0, $i) * $font_size / 2;
        }

        elseif (($i = mb_stripos($l, "in")) !== false) {
            $value = (float)mb_substr($l, 0, $i) * 72;
        }

        elseif (($i = mb_stripos($l, "pc")) !== false) {
            $value = (float)mb_substr($l, 0, $i) * 12;
        }

        else {
            // Invalid or unsupported declaration
            $value = null;
        }

        return $cache[$key] = $value;
    }


    /**
     * Set inherited properties in this style using values in $parent
     *
     * https://www.w3.org/TR/css-cascade-3/#inheriting
     *
     * @param Style $parent
     *
     * @return Style
     */
    function inherit(Style $parent)
    {
        $this->parent_style = $parent;

        // Clear the computed value font size, as is might depend on the parent
        // font size
        unset($this->_props_computed["font_size"]);
        unset($this->_prop_cache["font_size"]);

        foreach (self::$_inherited as $prop) {
            // For properties that inherit by default: When the cascade did not
            // result in a value, inherit the parent value. Inheritance is
            // handled via the specific sub-properties for shorthands
            if (isset($this->_props[$prop]) || isset(self::$_props_shorthand[$prop])) {
                continue;
            }

            if (isset($parent->_props[$prop])) {
                $parent_val = \array_key_exists($prop, $parent->_props_computed)
                    ? $parent->_props_computed[$prop]
                    : $parent->compute_prop($prop, $parent->_props[$prop]);

                $this->_props[$prop] = $parent_val;
                $this->_props_computed[$prop] = $parent_val;
                $this->_prop_cache[$prop] = null;
            }
        }

        foreach ($this->_props as $prop => $val) {
            if ($val === "inherit") {
                if (isset($parent->_props[$prop])) {
                    $parent_val = \array_key_exists($prop, $parent->_props_computed)
                        ? $parent->_props_computed[$prop]
                        : $parent->compute_prop($prop, $parent->_props[$prop]);

                    $this->_props[$prop] = $parent_val;
                    $this->_props_computed[$prop] = $parent_val;
                    $this->_prop_cache[$prop] = null;
                } else {
                    // Parent prop not set, use default
                    $this->_props[$prop] = self::$_defaults[$prop];
                    unset($this->_props_computed[$prop]);
                    unset($this->_prop_cache[$prop]);
                }
            }
        }

        return $this;
    }

    /**
     * Override properties in this style with those in $style
     *
     * @param Style $style
     */
    function merge(Style $style)
    {
        foreach ($style->_props as $prop => $val) {
            $important = isset($style->_important_props[$prop]);

            // `!important` declarations take precedence over normal ones
            if (!$important && isset($this->_important_props[$prop])) {
                continue;
            }

            $computed = \array_key_exists($prop, $style->_props_computed)
                ? $style->_props_computed[$prop]
                : $style->compute_prop($prop, $val);

            // Skip invalid declarations. Because styles are merged into an
            // initially empty style object during stylesheet loading, this
            // handles all invalid declarations
            if ($computed === null) {
                continue;
            }

            if ($important) {
                $this->_important_props[$prop] = true;
            }

            $this->_props[$prop] = $val;

            // Don't use the computed value for dependent properties; they will
            // be computed on-demand during inheritance or property access
            // instead
            if (isset(self::$_dependent_props[$prop])) {
                unset($this->_props_computed[$prop]);
                unset($this->_prop_cache[$prop]);
            } else {
                $this->_props_computed[$prop] = $computed;
                $this->_prop_cache[$prop] = null;
            }
        }
    }

    /**
     * Returns an array(r, g, b, "r"=> r, "g"=>g, "b"=>b, "alpha"=>alpha, "hex"=>"#rrggbb")
     * based on the provided CSS color value.
     *
     * @param string $color
     * @return array|string|null
     */
    function munge_color($color)
    {
        return Color::parse($color);
    }

    /**
     * @deprecated
     * @param string $prop
     */
    function important_set($prop)
    {
        $prop = str_replace("-", "_", $prop);
        $this->_important_props[$prop] = true;
    }

    /**
     * @deprecated
     * @param string $prop
     * @return bool
     */
    function important_get($prop)
    {
        return isset($this->_important_props[$prop]);
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
     * Set the specified value of a property.
     *
     * Setting `$clear_dependencies` to `false` is useful for saving a bit of
     * unnecessary work while loading stylesheets.
     *
     * @param string $prop               The property to set.
     * @param mixed  $val                The value declaration.
     * @param bool   $important          Whether the declaration is important.
     * @param bool   $clear_dependencies Whether to clear computed values of dependent properties.
     */
    function set_prop(string $prop, $val, bool $important = false, bool $clear_dependencies = true): void
    {
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

        if ($prop !== "content" && is_string($val) && mb_strpos($val, "url") === false) {
            $val = mb_strtolower(trim(str_replace(["\n", "\t"], [" "], $val)));
            $val = preg_replace("/([0-9]+) (pt|px|pc|rem|em|ex|in|cm|mm|%)/S", "\\1\\2", $val);
        }

        if (isset(self::$_props_shorthand[$prop])) {
            // Shorthand properties directly set their respective sub-properties
            // https://www.w3.org/TR/css-cascade-3/#shorthand
            if ($val === "initial" || $val === "inherit" || $val === "unset") {
                foreach (self::$_props_shorthand[$prop] as $sub_prop) {
                    $this->set_prop($sub_prop, $val, $important, $clear_dependencies);
                }
            } else {
                $method = "set_$prop";
    
                if (!isset(self::$_methods_cache[$method])) {
                    self::$_methods_cache[$method] = method_exists($this, $method);
                }
    
                if (self::$_methods_cache[$method]) {
                    $this->$method($val, $important);
                }
            }
        } else {
            // `!important` declarations take precedence over normal ones
            if (!$important && isset($this->_important_props[$prop])) {
                return;
            }

            if ($important) {
                $this->_important_props[$prop] = true;
            }

            // https://www.w3.org/TR/css-cascade-3/#inherit-initial
            if ($val === "unset") {
                $val = in_array($prop, self::$_inherited, true)
                    ? "inherit"
                    : "initial";
            }

            // https://www.w3.org/TR/css-cascade-3/#valdef-all-initial
            if ($val === "initial") {
                $val = self::$_defaults[$prop];
            }

            $this->_props[$prop] = $val;
            unset($this->_props_computed[$prop]);
            unset($this->_prop_cache[$prop]);

            if ($clear_dependencies) {
                // Clear the computed values of any dependent properties, so
                // they can be re-computed
                if (isset(self::$_dependency_map[$prop])) {
                    foreach (self::$_dependency_map[$prop] as $dependent) {
                        unset($this->_props_computed[$dependent]);
                        unset($this->_prop_cache[$dependent]);
                    }
                }

                // Clear border-radius cache on setting any border-radius
                // property
                if ($prop === "border_top_left_radius"
                    || $prop === "border_top_right_radius"
                    || $prop === "border_bottom_left_radius"
                    || $prop === "border_bottom_right_radius"
                ) {
                    $this->has_border_radius_cache = null;
                }
            }

            // FIXME: temporary hack around lack of persistence of base href for
            // URLs. Compute value immediately, before the original base href is
            // no longer available
            if ($prop === "background_image" || $prop === "list_style_image") {
                $this->compute_prop($prop, $val);
            }
        }
    }

    /**
     * Similar to __get() without storing the result. Useful for accessing
     * properties while loading stylesheets.
     *
     * @param string $prop
     *
     * @return mixed
     * @throws Exception
     */
    function get_prop(string $prop)
    {
        // Legacy property aliases
        if (isset(self::$_props_alias[$prop])) {
            $prop = self::$_props_alias[$prop];
        }

        if (!isset(self::$_defaults[$prop])) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        $method = "get_$prop";

        if (isset($this->_props_computed[$prop])) {
            if (method_exists($this, $method)) {
                return $this->$method();
            }

            return $this->_props_computed[$prop];
        }

        // Fall back on defaults if property is not set
        return $this->_props[$prop] ?? self::$_defaults[$prop];
    }

    /**
     * PHP5 overloaded setter
     *
     * This function along with {@link Style::__get()} permit a user of the
     * Style class to access any (CSS) property using the following syntax:
     * <code>
     *  Style->margin_top = "1em";
     *  echo (Style->margin_top);
     * </code>
     *
     * __set() automatically calls the provided set function, if one exists,
     * otherwise it sets the property directly.  Typically, __set() is not
     * called directly from outside of this class.
     *
     * On each modification clear cache to return accurate setting.
     * Also affects direct settings not using __set
     * For easier finding all assignments, attempted to allowing only explicite assignment:
     * Very many uses, e.g. AbstractFrameReflower.php -> for now leave as it is
     * function __set($prop, $val) {
     *   throw new Exception("Implicit replacement of assignment by __set.  Not good.");
     * }
     * function props_set($prop, $val) { ... }
     *
     * @param string $prop the property to set
     * @param mixed $val the value of the property
     *
     */
    function __set($prop, $val)
    {
        $this->set_prop($prop, $val);
    }

    /**
     * PHP5 overloaded getter
     * Along with {@link Style::__set()} __get() provides access to all CSS
     * properties directly.  Typically __get() is not called directly outside
     * of this class.
     * On each modification clear cache to return accurate setting.
     * Also affects direct settings not using __set
     *
     * @param string $prop
     *
     * @return mixed
     * @throws Exception
     */
    function __get($prop)
    {
        // Legacy property aliases
        if (isset(self::$_props_alias[$prop])) {
            $prop = self::$_props_alias[$prop];
        }

        if (!isset(self::$_defaults[$prop])) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        if (isset($this->_prop_cache[$prop])) {
            return $this->_prop_cache[$prop];
        }

        $method = "get_$prop";
    
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
                    return is_array($val) ? implode(" ", $val) : $val;
                }, self::$_props_shorthand[$prop]));
            }
        } else {
            // Compute the value if needed
            if (!\array_key_exists($prop, $this->_props_computed)) {
                $val = $this->_props[$prop] ?? self::$_defaults[$prop];
                $this->compute_prop($prop, $val);
            }

            // Invalid declarations are skipped on style merge, but during
            // style parsing, styles might contain invalid declarations. Fall
            // back to the default value in that case
            $computed = $this->_props_computed[$prop]
                ?? $this->compute_prop($prop, self::$_defaults[$prop]);
            $used = self::$_methods_cache[$method]
                ? $this->$method()
                : $computed;

            $this->_prop_cache[$prop] = $used;
            return $used;
        }
    }

    /**
     * Experimental fast setter for used values.
     *
     * If a shorthand property is specified, all of its sub-properties are set
     * to the same value.
     *
     * @param string $prop
     * @param mixed  $val
     */
    function set_used(string $prop, $val): void
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
            $this->_prop_cache[$prop] = $val;
        }
    }

    /**
     * @param string $prop The property to compute.
     * @param mixed  $val  The value to compute.
     *
     * @return mixed The computed value.
     */
    protected function compute_prop(string $prop, $val)
    {
        $this->_props_computed[$prop] = null;
        $this->_prop_cache[$prop] = null;

        $method = "set_$prop";

        if (!isset(self::$_methods_cache[$method])) {
            self::$_methods_cache[$method] = method_exists($this, $method);
        }

        // During style merge, let `inherit` compute to `inherit`, because the
        // parent style is not available yet. The keyword is resolved during
        // inheritance
        if ($val === "inherit") {
            $this->_props_computed[$prop] = $val;
        } elseif (self::$_methods_cache[$method]) {
            $this->$method($val);
        } elseif ($val !== "") {
            $this->_props_computed[$prop] = $val;
        }

        return $this->_props_computed[$prop];
    }

    /**
     * @param float $cbw The width of the containing block.
     * @return float|null|string
     */
    function computed_bottom_spacing(float $cbw)
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
     * @return string
     */
    function get_font_family_raw()
    {
        return trim($this->_props["font_family"], " \t\n\r\x0B\"'");
    }

    /**
     * Getter for the 'font-family' CSS property.
     * Uses the {@link FontMetrics} class to resolve the font family into an
     * actual font file.
     *
     * @link http://www.w3.org/TR/CSS21/fonts.html#propdef-font-family
     * @throws Exception
     *
     * @return string
     */
    function get_font_family()
    {
        //TODO: we should be using the calculated prop rather than perform the entire family parsing operation again

        $DEBUGCSS = $this->_stylesheet->get_dompdf()->getOptions()->getDebugCss();

        // Select the appropriate font.  First determine the subtype, then check
        // the specified font-families for a candidate.

        // Resolve font-weight
        $weight = $this->__get("font_weight");
        if ($weight === 'bold') {
            $weight = 700;
        } elseif (preg_match('/^[0-9]+$/', $weight, $match)) {
            $weight = (int)$match[0];
        } else {
            $weight = 400;
        }

        // Resolve font-style
        $font_style = $this->__get("font_style");
        $subtype = $this->getFontMetrics()->getType($weight . ' ' . $font_style);

        $families = preg_split("/\s*,\s*/", $this->_props_computed["font_family"]);

        $font = null;
        foreach ($families as $family) {
            //remove leading and trailing string delimiters, e.g. on font names with spaces;
            //remove leading and trailing whitespace
            $family = trim($family, " \t\n\r\x0B\"'");
            if ($DEBUGCSS) {
                print '(' . $family . ')';
            }
            $font = $this->getFontMetrics()->getFont($family, $subtype);

            if ($font) {
                if ($DEBUGCSS) {
                    print "<pre>[get_font_family:";
                    print '(' . $this->_props_computed["font_family"] . '.' . $font_style . '.' . $weight . '.' . $subtype . ')';
                    print '(' . $font . ")get_font_family]\n</pre>";
                }
                return $font;
            }
        }

        $family = null;
        if ($DEBUGCSS) {
            print '(default)';
        }
        $font = $this->getFontMetrics()->getFont($family, $subtype);

        if ($font) {
            if ($DEBUGCSS) {
                print '(' . $font . ")get_font_family]\n</pre>";
            }
            return $font;
        }

        throw new Exception("Unable to find a suitable font replacement for: '" . $this->_props_computed["font_family"] . "'");
    }

    /**
     * @link http://www.w3.org/TR/CSS21/text.html#propdef-word-spacing
     * @return float
     */
    function get_word_spacing()
    {
        $word_spacing = $this->_props_computed["word_spacing"];

        if ($word_spacing === "normal") {
            return 0;
        }

        if (strpos($word_spacing, "%") !== false) {
            return $word_spacing;
        }

        return (float)$this->length_in_pt($word_spacing, $this->__get("font_size"));
    }

    /**
     * @link http://www.w3.org/TR/CSS21/text.html#propdef-letter-spacing
     * @return float
     */
    function get_letter_spacing()
    {
        $letter_spacing = $this->_props_computed["letter_spacing"];

        if ($letter_spacing === "normal") {
            return 0;
        }

        return (float)$this->length_in_pt($letter_spacing, $this->__get("font_size"));
    }

    /**
     * @link http://www.w3.org/TR/CSS21/visudet.html#propdef-line-height
     * @return float
     */
    function get_line_height()
    {
        $line_height = $this->_props_computed["line_height"];

        if ($line_height === "normal") {
            return self::$default_line_height * $this->__get("font_size");
        }

        if (is_numeric($line_height)) {
            return $line_height * $this->__get("font_size");
        }

        return (float)$this->length_in_pt($line_height, $this->__get("font_size"));
    }

    /**
     * @param string $prop
     * @param bool $current_is_parent
     * @return array|string
     */
    protected function get_prop_color(string $prop, bool $current_is_parent = false)
    {
        $val = $this->_props_computed[$prop];

        if ($val === "currentcolor") {
            // https://www.w3.org/TR/css-color-4/#resolving-other-colors
            if ($current_is_parent) {
                // Use the `color` value from the parent for the `color`
                // property itself
                return isset($this->parent_style)
                    ? $this->parent_style->__get("color")
                    : $this->munge_color(self::$_defaults[$prop]);
            }

            return $this->__get("color");
        }

        return $this->munge_color($val) ?? "transparent";
    }

    /**
     * Returns the color as an array
     *
     * The array has the following format:
     * <code>array(r,g,b, "r" => r, "g" => g, "b" => b, "hex" => "#rrggbb")</code>
     *
     * @link http://www.w3.org/TR/CSS21/colors.html#propdef-color
     * @return array
     */
    function get_color()
    {
        return $this->get_prop_color("color", true);
    }

    /**
     * Returns the background color as an array
     *
     * The returned array has the same format as {@link Style::get_color()}
     *
     * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-color
     * @return array
     */
    function get_background_color()
    {
        return $this->get_prop_color("background_color");
    }

    /**
     * Returns the background image URI, or "none"
     *
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-image
     * @return string
     */
    function get_background_image()
    {
        return $this->_stylesheet->resolve_url($this->_props_computed["background_image"]);
    }

    /**
     * Returns the background position as an array
     *
     * The returned array has the following format:
     * <code>array(x,y, "x" => x, "y" => y)</code>
     *
     * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-position
     * @return array
     */
    function get_background_position()
    {
        $tmp = explode(" ", $this->_props_computed["background_position"]);

        return [
            0 => $tmp[0], "x" => $tmp[0],
            1 => $tmp[1], "y" => $tmp[1],
        ];
    }


    /**
     * Returns the background size as an array
     *
     * The return value has one of the following formats:
     * <code>"cover"</code>
     * <code>"contain"</code>
     * <code>array(width,height)</code>
     *
     * @link https://www.w3.org/TR/css3-background/#background-size
     * @return string|array
     */
    function get_background_size()
    {
        switch ($this->_props_computed["background_size"]) {
            case "cover":
                return "cover";
            case "contain":
                return "contain";
            default:
                break;
        }

        $result = explode(" ", $this->_props_computed["background_size"]);
        return [$result[0], $result[1]];
    }

    /**#@+
     * Returns the border color as an array
     *
     * See {@link Style::get_color()}
     *
     * @link http://www.w3.org/TR/CSS21/box.html#border-color-properties
     * @return array
     */
    function get_border_top_color()
    {
        return $this->get_prop_color("border_top_color");
    }

    /**
     * @return array
     */
    function get_border_right_color()
    {
        return $this->get_prop_color("border_right_color");
    }

    /**
     * @return array
     */
    function get_border_bottom_color()
    {
        return $this->get_prop_color("border_bottom_color");
    }

    /**
     * @return array
     */
    function get_border_left_color()
    {
        return $this->get_prop_color("border_left_color");
    }

    /**#@-*/

    /**
     * Return an array of all border properties.
     *
     * The returned array has the following structure:
     * <code>
     * array("top" => array("width" => [border-width],
     *                      "style" => [border-style],
     *                      "color" => [border-color (array)]),
     *       "bottom" ... )
     * </code>
     *
     * @return array
     */
    function get_border_properties()
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
     * Return a single border property
     *
     * @param string $side
     *
     * @return mixed
     */
    protected function _get_border($side)
    {
        $color = $this->__get("border_" . $side . "_color");

        return $this->__get("border_" . $side . "_width") . " " .
            $this->__get("border_" . $side . "_style") . " " .
            (is_array($color) ? $color["hex"] : $color);
    }

    /**#@+
     * Return full border properties as a string
     *
     * Border properties are returned just as specified in CSS:
     * <pre>[width] [style] [color]</pre>
     * e.g. "1px solid blue"
     *
     * @link http://www.w3.org/TR/CSS21/box.html#border-shorthand-properties
     * @return string
     */
    function get_border_top()
    {
        return $this->_get_border("top");
    }

    /**
     * @return mixed
     */
    function get_border_right()
    {
        return $this->_get_border("right");
    }

    /**
     * @return mixed
     */
    function get_border_bottom()
    {
        return $this->_get_border("bottom");
    }

    /**
     * @return mixed
     */
    function get_border_left()
    {
        return $this->_get_border("left");
    }

    /**
     * @deprecated
     * @param float $w
     * @param float $h
     * @return float[]
     */
    function get_computed_border_radius($w, $h)
    {
        return $this->resolve_border_radius([0, 0, $w, $h]);
    }

    public function has_border_radius(): bool
    {
        if (isset($this->has_border_radius_cache)) {
            return $this->has_border_radius_cache;
        }

        // Use a fixed ref size here. We don't know the border-box width here
        // and font size might be 0. Since we are only interested in whether
        // there is any border radius at all, this should do
        $tl = (float) $this->length_in_pt($this->border_top_left_radius, 10);
        $tr = (float) $this->length_in_pt($this->border_top_right_radius, 10);
        $br = (float) $this->length_in_pt($this->border_bottom_right_radius, 10);
        $bl = (float) $this->length_in_pt($this->border_bottom_left_radius, 10);

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
     * See {@link Style::get_color()}
     *
     * @link http://www.w3.org/TR/CSS21/box.html#border-color-properties
     * @return array
     */
    function get_outline_color()
    {
        return $this->get_prop_color("outline_color");
    }

    /**
     * Return full outline properties as a string
     *
     * Outline properties are returned just as specified in CSS:
     * <pre>[width] [style] [color]</pre>
     * e.g. "1px solid blue"
     *
     * @link http://www.w3.org/TR/CSS21/box.html#border-shorthand-properties
     * @return string
     */
    function get_outline()
    {
        $color = $this->__get("outline_color");

        return $this->__get("outline_width") . " " .
            $this->__get("outline_style") . " " .
            (is_array($color) ? $color["hex"] : $color);
    }

    /**
     * Returns border spacing as an array
     *
     * The array has the format (h_space,v_space)
     *
     * @link http://www.w3.org/TR/CSS21/tables.html#propdef-border-spacing
     * @return array
     */
    function get_border_spacing()
    {
        $arr = explode(" ", $this->_props_computed["border_spacing"]);
        if (count($arr) == 1) {
            $arr[1] = $arr[0];
        }
        return $arr;
    }

    /**
     * Returns the list style image URI, or "none"
     *
     * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style-image
     * @return string
     */
    function get_list_style_image()
    {
        return $this->_stylesheet->resolve_url($this->_props_computed["list_style_image"]);
    }

    /**
     * @param string $value
     * @param int    $default
     *
     * @return array|string
     */
    protected function parse_counter_prop(string $value, int $default)
    {
        $ident = self::CSS_IDENTIFIER;
        $integer = self::CSS_INTEGER;
        $pattern = "/($ident)(?:\s+($integer))?/";

        if (!preg_match_all($pattern, $value, $matches, PREG_SET_ORDER)) {
            return "none";
        }

        $counters = [];

        foreach ($matches as $match) {
            $counter = $match[1];
            $value = isset($match[2]) ? (int) $match[2] : $default;
            $counters[$counter] = $value;
        }

        return $counters;
    }

    /**
     * @return array|string
     */
    function get_counter_increment()
    {
        $val = $this->_props_computed["counter_increment"];

        if ($val === "none" || $val === "inherit") {
            return "none";
        }

        return $this->parse_counter_prop($val, 1);
    }

    /**
     * @return array|string
     */
    protected function get_counter_reset()
    {
        $val = $this->_props_computed["counter_reset"];

        if ($val === "none") {
            return "none";
        }

        return $this->parse_counter_prop($val, 0);
    }

    /**
     * @return string[]|string
     */
    protected function get_content()
    {
        $val = $this->_props_computed["content"];

        if ($val === "normal" || $val === "none") {
            return $val;
        }

        return $this->parse_property_value($val);
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
        $ident = self::CSS_IDENTIFIER;
        $number = self::CSS_NUMBER;

        $pattern = "/\n" .
            "\s* \" ( (?:[^\"]|\\\\[\"])* ) (?<!\\\\)\" |\n" . // String ""
            "\s* '  ( (?:[^']|\\\\['])* )   (?<!\\\\)'  |\n" . // String ''
            "\s* ($ident \\([^)]*\\) )                  |\n" . // Functional
            "\s* ($ident)                               |\n" . // Keyword
            "\s* (\#[0-9a-fA-F]*)                       |\n" . // Hex value
            "\s* ($number [a-zA-Z%]*)                   |\n" . // Number (+ unit/percentage)
            "\s* ([\/,;])                                \n" . // Delimiter
            "/Sx";

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

    protected function prop_name(string $style, string $side, string $type): string
    {
        $prop = $style;
        if ($side !== "") {
            $prop .= "_" . $side;
        };
        if ($type !== "") {
            $prop .= "_" . $type;
        };
        return $prop;
    }

    /**
     * Generalized set function for individual attribute of combined style.
     *
     * Applicable for margin, border, padding, outline.
     *
     * @param string $style
     * @param string $side
     * @param string $type
     * @param mixed $val
     */
    protected function _set_style_side_type($style, $side, $type, $val)
    {
        $prop = $this->prop_name($style, $side, $type);

        $this->_prop_cache[$prop] = null;

        if ($val === "inherit") {
            $this->_props_computed[$prop] = null;
            return;
        }

        if ($side === "bottom") {
            $this->_computed_bottom_spacing = null; //reset computed cache, border style can disable/enable border calculations
        }

        if ($type === "color") {
            $this->set_prop_color($prop, $val);
        } elseif (($style === "border" || $style === "outline") && $type === "width") {
            // Border-width keywords
            if ($val === "thin") {
                $val_computed = 0.5;
            } elseif ($val === "medium") {
                $val_computed = 1.5;
            } elseif ($val === "thick") {
                $val_computed = 2.5;
            } elseif (mb_strpos($val, "%") !== false) {
                $val_computed = null;
            } else {
                $val_computed = $this->single_length_in_pt($val);

                if ($val_computed < 0) {
                    $val_computed = null;
                }
            }

            if ($val_computed === null) {
                $this->_props_computed[$prop] = null;
            } else {
                $line_style_prop = $this->prop_name($style, $side, "style");
                $line_style = $this->__get($line_style_prop);
                $has_line_style = $line_style !== "none" && $line_style !== "hidden";

                $this->_props_computed[$prop] = $has_line_style ? $val_computed : 0;
            }
        } elseif ($style === "margin" || $style === "padding") {
            if ($val === "none") {
                // Legacy support for `none` keyword, not covered by spec
                $val_computed = 0;
            } elseif ($style === "margin" && $val === "auto") {
                $val_computed = $val;
            } elseif (mb_strpos($val, "%") !== false) {
                $val_computed = $val;
            } else {
                $val_computed = $this->single_length_in_pt($val);

                if ($style === "padding" && $val_computed < 0) {
                    $val_computed = null;
                }
            }

            $this->_props_computed[$prop] = $val_computed;
        } elseif ($val !== "") {
            $this->_props_computed[$prop] = $val;
        } else {
            $this->_props_computed[$prop] = null;
        }
    }

    /**
     * @param string $style
     * @param string $type
     * @param mixed $val
     * @param bool $important
     */
    protected function _set_style_type($style, $type, $val, $important)
    {
        $v = $this->parse_property_value($val);

        switch (count($v)) {
            case 1:
                [$top, $right, $bottom, $left] = [$v[0], $v[0], $v[0], $v[0]];
                break;
            case 2:
                [$top, $right, $bottom, $left] = [$v[0], $v[1], $v[0], $v[1]];
                break;
            case 3:
                [$top, $right, $bottom, $left] = [$v[0], $v[1], $v[2], $v[1]];
                break;
            case 4:
                [$top, $right, $bottom, $left] = [$v[0], $v[1], $v[2], $v[3]];
                break;
            default:
                return;
        }

        $this->set_prop($this->prop_name($style, "top", $type), $top, $important);
        $this->set_prop($this->prop_name($style, "right", $type), $right, $important);
        $this->set_prop($this->prop_name($style, "bottom", $type), $bottom, $important);
        $this->set_prop($this->prop_name($style, "left", $type), $left, $important);
    }

    /*======================*/

    /**
     * https://www.w3.org/TR/CSS21/visuren.html#display-prop
     *
     * @param string $val
     */
    protected function set_display(string $val): void
    {
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
            return;
        }

        // https://www.w3.org/TR/CSS21/visuren.html#dis-pos-flo
        if ($this->is_in_flow()) {
            $computed = $val;
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
                    $computed = "block";
                    break;
                case "inline-table":
                    $computed = "table";
                    break;
                default:
                    $computed = $val;
                    break;
            }
        }

        $this->_props_computed["display"] = $computed;
    }

    protected function set_prop_color($prop, $val)
    {
        $this->_prop_cache[$prop] = null;

        // https://www.w3.org/TR/css-color-4/#resolving-other-colors
        $munged_color = $val !== "currentcolor"
            ? $this->munge_color($val)
            : $val;

        if (is_null($munged_color)) {
            $this->_props_computed[$prop] = null;
            return;
        }

        $this->_props_computed[$prop] = is_array($munged_color) ? $munged_color["hex"] : $munged_color;
    }

    /**
     * Sets color
     *
     * The color parameter can be any valid CSS color value
     *
     * @link http://www.w3.org/TR/CSS21/colors.html#propdef-color
     * @param string $color
     */
    function set_color($color)
    {
        $this->set_prop_color("color", $color);
    }

    /**
     * Sets the background color
     *
     * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-color
     * @param string $color
     */
    function set_background_color($color)
    {
        $this->set_prop_color("background_color", $color);
    }

    /**
     * Set the background image url
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-image
     *
     * @param string $val
     */
    function set_background_image($val)
    {
        $this->_prop_cache["background_image"] = null;

        $parsed_val = $this->_stylesheet->resolve_url($val);

        if ($parsed_val === "none") {
            $this->_props_computed["background_image"] = "none";
        } else {
            $this->_props_computed["background_image"] = "url(" . $parsed_val . ")";
        }
    }

    /**
     * Sets the background repeat
     *
     * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-repeat
     * @param string $val
     */
    function set_background_repeat($val)
    {
        $this->_prop_cache["background_repeat"] = null;

        if ($val === "inherit") {
            $this->_props_computed["background_repeat"] = null;
            return;
        }

        $this->_props_computed["background_repeat"] = $val;
    }

    /**
     * Sets the background attachment
     *
     * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-attachment
     * @param string $val
     */
    function set_background_attachment($val)
    {
        $this->_prop_cache["background_attachment"] = null;

        if ($val === "inherit") {
            $this->_props_computed["background_attachment"] = null;
            return;
        }

        $this->_props_computed["background_attachment"] = $val;
    }

    /**
     * Sets the background position
     *
     * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-position
     * @param string $val
     */
    function set_background_position($val)
    {
        $this->_prop_cache["background_position"] = null;

        $tmp = explode(" ", $val);

        switch ($tmp[0]) {
            case "left":
                $x = "0%";
                break;

            case "right":
                $x = "100%";
                break;

            case "top":
                $y = "0%";
                break;

            case "bottom":
                $y = "100%";
                break;

            case "center":
                $x = "50%";
                $y = "50%";
                break;

            default:
                $x = $tmp[0];
                break;
        }

        if (isset($tmp[1])) {
            switch ($tmp[1]) {
                case "left":
                    $x = "0%";
                    break;

                case "right":
                    $x = "100%";
                    break;

                case "top":
                    $y = "0%";
                    break;

                case "bottom":
                    $y = "100%";
                    break;

                case "center":
                    if ($tmp[0] === "left" || $tmp[0] === "right" || $tmp[0] === "center") {
                        $y = "50%";
                    } else {
                        $x = "50%";
                    }
                    break;

                default:
                    $y = $tmp[1];
                    break;
            }
        } else {
            $y = "50%";
        }

        if (!isset($x)) {
            $x = "0%";
        }

        if (!isset($y)) {
            $y = "0%";
        }
        
        $this->_props_computed["background_position"] = "$x $y";
    }

    /**
     * Sets the background size
     *
     * @link https://www.w3.org/TR/css3-background/#background-size
     * @param string $val
     */
    function set_background_size($val)
    {
        $this->_prop_cache["background_size"] = null;

        $result = explode(" ", $val);
        $width = $result[0];

        switch ($width) {
            case "cover":
            case "contain":
                $this->_props_computed["background_size"] = $width;
                return;
            case "inherit":
                $this->_props_computed["background_size"] = null;
                return;
        }

        if ($width !== "auto" && strpos($width, "%") === false) {
            $width = (float)$this->length_in_pt($width);
        }

        $height = $result[1] ?? "auto";
        if ($height !== "auto" && strpos($height, "%") === false) {
            $height = (float)$this->length_in_pt($height);
        }

        $this->_props_computed["background_size"] = "$width $height";
    }

    /**
     * Sets the background - combined options
     *
     * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background
     * @param string $value
     * @param bool $important
     */
    function set_background($value, bool $important = false)
    {
        if ($value === "none") {
            $this->set_prop("background_image", "none", $important);
            $this->set_prop("background_color", "transparent", $important);
        } else {
            $components = $this->parse_property_value($value);
            $pos_size = [];

            foreach ($components as $val) {
                if ($val === "none" || mb_substr($val, 0, 4) === "url(") {
                    $this->set_prop("background_image", $val, $important);
                } elseif ($val === "fixed" || $val === "scroll") {
                    $this->set_prop("background_attachment", $val, $important);
                } elseif ($val === "repeat" || $val === "repeat-x" || $val === "repeat-y" || $val === "no-repeat") {
                    $this->set_prop("background_repeat", $val, $important);
                } elseif ($this->is_color_value($val)) {
                    $this->set_prop("background_color", $val, $important);
                } else {
                    $pos_size[] = $val;
                }
            }

            if (count($pos_size)) {
                // Split value list at "/"
                $index = array_search("/", $pos_size, true);

                if ($index !== false) {
                    $pos = array_slice($pos_size, 0, $index);
                    $size = array_slice($pos_size, $index + 1);
                } else {
                    $pos = $pos_size;
                    $size = [];
                }

                $this->set_prop("background_position", implode(" ", $pos), $important);

                if (count($size)) {
                    $this->set_prop("background_size", implode(" ", $size), $important);
                }
            }
        }
    }

    /**
     * Sets the font size
     *
     * $size can be any acceptable CSS size
     *
     * @link http://www.w3.org/TR/CSS21/fonts.html#propdef-font-size
     * @param string|float $size
     */
    function set_font_size($size)
    {
        $this->_prop_cache["font_size"] = null;

        if ($size === "inherit") {
            $this->_props_computed["font_size"] = null;
            return;
        }

        $parent_font_size = isset($this->parent_style)
            ? $this->parent_style->__get("font_size")
            : self::$default_font_size;

        switch ((string)$size) {
            case "xx-small":
            case "x-small":
            case "small":
            case "medium":
            case "large":
            case "x-large":
            case "xx-large":
                $fs = self::$default_font_size * self::$font_size_keywords[$size];
                break;

            case "smaller":
                $fs = 8 / 9 * $parent_font_size;
                break;

            case "larger":
                $fs = 6 / 5 * $parent_font_size;
                break;

            default:
                $fs = $this->single_length_in_pt($size, $parent_font_size, $parent_font_size);
                break;
        }

        $this->_props_computed["font_size"] = $fs;
    }

    /**
     * Sets the font weight
     *
     * @param string|int $weight
     */
    function set_font_weight($weight)
    {
        $this->_prop_cache["font_weight"] = null;

        if ($weight === "inherit") {
            $this->_props_computed["font_weight"] = null;
            return;
        }

        $computed_weight = $weight;

        if ($weight === "bolder") {
            //TODO: One font weight heavier than the parent element (among the available weights of the font).
            $computed_weight = "bold";
        } elseif ($weight === "lighter") {
            //TODO: One font weight lighter than the parent element (among the available weights of the font).
            $computed_weight = "normal";
        }

        $this->_props_computed["font_weight"] = $computed_weight;
    }

    /**
     * Sets the font style
     *
     * combined attributes
     * set individual attributes also, respecting !important mark
     * exactly this order, separate by space. Multiple fonts separated by comma:
     * font-style, font-variant, font-weight, font-size, line-height, font-family
     *
     * Other than with border and list, existing partial attributes should
     * reset when starting here, even when not mentioned.
     * If individual attribute is !important and explicit or implicit replacement is not,
     * keep individual attribute
     *
     * require whitespace as delimiters for single value attributes
     * On delimiter "/" treat first as font height, second as line height
     * treat all remaining at the end of line as font
     * font-style, font-variant, font-weight, font-size, line-height, font-family
     *
     * missing font-size and font-family might be not allowed, but accept it here and
     * use default (medium size, empty font name)
     *
     * @link https://www.w3.org/TR/CSS21/fonts.html#font-shorthand
     * @param string $val
     * @param bool $important
     */
    function set_font($val, bool $important = false)
    {
        if (preg_match("/^(italic|oblique|normal)\s*(.*)$/i", $val, $match)) {
            $this->set_prop("font_style", $match[1], $important);
            $val = $match[2];
        }

        if (preg_match("/^(small-caps|normal)\s*(.*)$/i", $val, $match)) {
            $this->set_prop("font_variant", $match[1], $important);
            $val = $match[2];
        }

        //matching numeric value followed by unit -> this is indeed a subsequent font size. Skip!
        if (preg_match("/^(bold|bolder|lighter|100|200|300|400|500|600|700|800|900|normal)\s*(.*)$/i", $val, $match) &&
            !preg_match("/^(?:pt|px|pc|rem|em|ex|in|cm|mm|%)/", $match[2])
        ) {
            $this->set_prop("font_weight", $match[1], $important);
            $val = $match[2];
        }

        if (preg_match("/^(xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger|\d+\s*(?:pt|px|pc|rem|em|ex|in|cm|mm|%))(?:\/|\s*)(.*)$/i", $val, $match)) {
            $this->set_prop("font_size", $match[1], $important);
            $val = $match[2];
            if (preg_match("/^(?:\/|\s*)(\d+\s*(?:pt|px|pc|rem|em|ex|in|cm|mm|%)?)\s*(.*)$/i", $val, $match)) {
                $this->set_prop("line_height", $match[1], $important);
                $val = $match[2];
            }
        }

        if (strlen($val) != 0) {
            $this->set_prop("font_family", $val, $important);
        }
    }

    /**
     * Sets the text alignment
     *
     * If no alignment is set on the element and the direction is rtl then
     * the property is set to "right", otherwise it is set to "left".
     *
     * @link https://www.w3.org/TR/CSS21/text.html#propdef-text-align
     */
    public function set_text_align($val)
    {
        $this->_prop_cache["text_align"] = null;

        $alignment = $val;
        if ($alignment === "") {
            $alignment = "left";
            if ($this->__get("direction") === "rtl") {
                $alignment = "right";
            }
        }

        if (!in_array($alignment, self::$text_align_keywords, true)) {
            $this->_props_computed["text_align"] = null;
            return;
        }

        $this->_props_computed["text_align"] = $alignment;
    }

    /**
     * Sets word spacing property
     *
     * @link http://www.w3.org/TR/CSS21/text.html#propdef-word-spacing
     * @param $val
     */
    function set_word_spacing($val)
    {
        $this->_prop_cache["word_spacing"] = null;

        if ($val === "inherit") {
            $this->_props_computed["word_spacing"] = null;
            return;
        }

        if ($val === "normal" || strpos($val, "%") !== false) {
            $this->_props_computed["word_spacing"] = $val;
        } else {
            $this->_props_computed["word_spacing"] = ((float)$this->length_in_pt($val, $this->__get("font_size"))) . "pt";
        }
    }

    /**
     * Sets letter spacing property
     *
     * @link http://www.w3.org/TR/CSS21/text.html#propdef-letter-spacing
     * @param $val
     */
    function set_letter_spacing($val)
    {
        $this->_prop_cache["letter_spacing"] = null;

        if ($val === "inherit") {
            $this->_props_computed["letter_spacing"] = null;
            return;
        }

        if ($val === "normal") {
            $this->_props_computed["letter_spacing"] = $val;
        } else {
            $this->_props_computed["letter_spacing"] = ((float)$this->length_in_pt($val, $this->__get("font_size"))) . "pt";
        }
    }

    /**
     * Sets line height property
     *
     * @link http://www.w3.org/TR/CSS21/visudet.html#propdef-line-height
     * @param $val
     */
    function set_line_height($val)
    {
        $this->_prop_cache["line_height"] = null;

        if ($val === "inherit") {
            $this->_props_computed["line_height"] = null;
            return;
        }

        if ($val === "normal" || is_numeric($val)) {
            $this->_props_computed["line_height"] = $val;
        } else {
            $this->_props_computed["line_height"] = ((float)$this->length_in_pt($val, $this->__get("font_size"))) . "pt";
        }
    }

    /**
     * Sets page break properties
     *
     * @link http://www.w3.org/TR/CSS21/page.html#page-breaks
     * @param string $break
     */
    function set_page_break_before($break)
    {
        $this->_prop_cache["page_break_before"] = null;

        if ($break === "inherit") {
            $this->_props_computed["page_break_before"] = null;
            return;
        }

        if ($break === "left" || $break === "right") {
            $break = "always";
        }

        $this->_props_computed["page_break_before"] = $break;
    }

    /**
     * @param $break
     */
    function set_page_break_after($break)
    {
        $this->_prop_cache["page_break_after"] = null;

        if ($break === "inherit") {
            $this->_props_computed["page_break_after"] = null;
            return;
        }

        if ($break === "left" || $break === "right") {
            $break = "always";
        }

        $this->_props_computed["page_break_after"] = $break;
    }

    /**
     * Sets the margin size
     *
     * @link http://www.w3.org/TR/CSS21/box.html#margin-properties
     * @param $val
     */
    function set_margin_top($val)
    {
        $this->_set_style_side_type("margin", "top", "", $val);
    }

    /**
     * @param $val
     */
    function set_margin_right($val)
    {
        $this->_set_style_side_type("margin", "right", "", $val);
    }

    /**
     * @param $val
     */
    function set_margin_bottom($val)
    {
        $this->_set_style_side_type("margin", "bottom", "", $val);
    }

    /**
     * @param $val
     */
    function set_margin_left($val)
    {
        $this->_set_style_side_type("margin", "left", "", $val);
    }

    /**
     * @param string $val
     * @param bool $important
     */
    function set_margin($val, bool $important = false)
    {
        $this->_set_style_type("margin", "", $val, $important);
    }

    /**
     * Sets the padding size
     *
     * @link http://www.w3.org/TR/CSS21/box.html#padding-properties
     * @param $val
     */
    function set_padding_top($val)
    {
        $this->_set_style_side_type("padding", "top", "", $val);
    }

    /**
     * @param $val
     */
    function set_padding_right($val)
    {
        $this->_set_style_side_type("padding", "right", "", $val);
    }

    /**
     * @param $val
     */
    function set_padding_bottom($val)
    {
        $this->_set_style_side_type("padding", "bottom", "", $val);
    }

    /**
     * @param $val
     */
    function set_padding_left($val)
    {
        $this->_set_style_side_type("padding", "left", "", $val);
    }

    /**
     * @param string $val
     * @param bool $important
     */
    function set_padding($val, bool $important = false)
    {
        $this->_set_style_type("padding", "", $val, $important);
    }

    /**
     * Sets a single border
     *
     * @param string $side
     * @param string $border_spec ([width] [style] [color])
     * @param bool $important
     */
    protected function _set_border($side, $border_spec, bool $important)
    {
        $components = $this->parse_property_value($border_spec);

        foreach ($components as $val) {
            if (in_array($val, self::$BORDER_STYLES, true)) {
                $this->set_prop("border_${side}_style", $val, $important);
            } elseif ($this->is_color_value($val)) {
                $this->set_prop("border_${side}_color", $val, $important);
            } else {
                // Assume width
                $this->set_prop("border_${side}_width", $val, $important);
            }
        }
    }

    /**
     * @link http://www.w3.org/TR/CSS21/box.html#border-properties
     * @param string $val
     * @param bool $important
     */
    function set_border_top($val, bool $important = false)
    {
        $this->_set_border("top", $val, $important);
    }

    function set_border_top_color($val)
    {
        $this->_set_style_side_type("border", "top", "color", $val);
    }

    function set_border_top_style($val)
    {
        $this->_set_style_side_type("border", "top", "style", $val);
    }

    function set_border_top_width($val)
    {
        $this->_set_style_side_type("border", "top", "width", $val);
    }

    /**
     * @param string $val
     * @param bool $important
     */
    function set_border_right($val, bool $important = false)
    {
        $this->_set_border("right", $val, $important);
    }

    function set_border_right_color($val)
    {
        $this->_set_style_side_type("border", "right", "color", $val);
    }

    function set_border_right_style($val)
    {
        $this->_set_style_side_type("border", "right", "style", $val);
    }

    function set_border_right_width($val)
    {
        $this->_set_style_side_type("border", "right", "width", $val);
    }

    /**
     * @param string $val
     * @param bool $important
     */
    function set_border_bottom($val, bool $important = false)
    {
        $this->_set_border("bottom", $val, $important);
    }

    function set_border_bottom_color($val)
    {
        $this->_set_style_side_type("border", "bottom", "color", $val);
    }

    function set_border_bottom_style($val)
    {
        $this->_set_style_side_type("border", "bottom", "style", $val);
    }

    function set_border_bottom_width($val)
    {
        $this->_set_style_side_type("border", "bottom", "width", $val);
    }

    /**
     * @param string $val
     * @param bool $important
     */
    function set_border_left($val, bool $important = false)
    {
        $this->_set_border("left", $val, $important);
    }

    function set_border_left_color($val)
    {
        $this->_set_style_side_type("border", "left", "color", $val);
    }

    function set_border_left_style($val)
    {
        $this->_set_style_side_type("border", "left", "style", $val);
    }

    function set_border_left_width($val)
    {
        $this->_set_style_side_type("border", "left", "width", $val);
    }

    /**
     * @param string $val
     * @param bool $important
     */
    function set_border($val, bool $important = false)
    {
        $this->_set_border("top", $val, $important);
        $this->_set_border("right", $val, $important);
        $this->_set_border("bottom", $val, $important);
        $this->_set_border("left", $val, $important);
    }

    /**
     * @param string $val
     * @param bool $important
     */
    function set_border_width($val, bool $important = false)
    {
        $this->_set_style_type("border", "width", $val, $important);
    }

    /**
     * @param string $val
     * @param bool $important
     */
    function set_border_color($val, bool $important = false)
    {
        $this->_set_style_type("border", "color", $val, $important);
    }

    /**
     * @param string $val
     * @param bool $important
     */
    function set_border_style($val, bool $important = false)
    {
        $this->_set_style_type("border", "style", $val, $important);
    }

    /**
     * Sets the border radius size
     *
     * http://www.w3.org/TR/css3-background/#corners
     *
     * @param string $val
     */
    function set_border_top_left_radius($val)
    {
        $this->_set_border_radius_corner($val, "top_left");
    }

    /**
     * @param string $val
     */
    function set_border_top_right_radius($val)
    {
        $this->_set_border_radius_corner($val, "top_right");
    }

    /**
     * @param string $val
     */
    function set_border_bottom_left_radius($val)
    {
        $this->_set_border_radius_corner($val, "bottom_left");
    }

    /**
     * @param string $val
     */
    function set_border_bottom_right_radius($val)
    {
        $this->_set_border_radius_corner($val, "bottom_right");
    }

    /**
     * @param string $val
     * @param bool $important
     */
    function set_border_radius($val, bool $important = false)
    {
        $r = $this->parse_property_value($val);

        switch (count($r)) {
            case 1:
                [$tl, $tr, $br, $bl] = [$r[0], $r[0], $r[0], $r[0]];
                break;
            case 2:
                [$tl, $tr, $br, $bl] = [$r[0], $r[1], $r[0], $r[1]];
                break;
            case 3:
                [$tl, $tr, $br, $bl] = [$r[0], $r[1], $r[2], $r[1]];
                break;
            case 4:
                [$tl, $tr, $br, $bl] = [$r[0], $r[1], $r[2], $r[3]];
                break;
            default:
                return;
        }

        $this->set_prop("border_top_left_radius", $tl, $important);
        $this->set_prop("border_top_right_radius", $tr, $important);
        $this->set_prop("border_bottom_right_radius", $br, $important);
        $this->set_prop("border_bottom_left_radius", $bl, $important);
    }

    /**
     * @param string $val
     * @param string $corner
     */
    protected function _set_border_radius_corner($val, $corner)
    {
        $prop = "border_" . $corner . "_radius";

        $this->_prop_cache[$prop] = null;

        if ($val === "inherit") {
            $this->_props_computed[$prop] = null;
            return;
        }

        $computed = mb_strpos($val, "%") === false
            ? $this->single_length_in_pt($val)
            : $val;

        $this->_props_computed[$prop] = $computed;
    }

    /**
     * @return float|int|string
     */
    function get_border_top_left_radius()
    {
        return $this->_get_border_radius_corner("top_left");
    }

    /**
     * @return float|int|string
     */
    function get_border_top_right_radius()
    {
        return $this->_get_border_radius_corner("top_right");
    }

    /**
     * @return float|int|string
     */
    function get_border_bottom_left_radius()
    {
        return $this->_get_border_radius_corner("bottom_left");
    }

    /**
     * @return float|int|string
     */
    function get_border_bottom_right_radius()
    {
        return $this->_get_border_radius_corner("bottom_right");
    }

    /**
     * @param $corner
     * @return float|int|string
     */
    protected function _get_border_radius_corner($corner)
    {
        $prop = "border_" . $corner . "_radius";

        if (!isset($this->_props_computed[$prop])) {
            return 0;
        }

        return $this->_props_computed[$prop];
    }

    /**
     * Sets the outline styles
     *
     * @link http://www.w3.org/TR/CSS21/ui.html#dynamic-outlines
     * @param string $value
     * @param bool $important
     */
    function set_outline($value, bool $important = false)
    {
        $components = $this->parse_property_value($value);

        foreach ($components as $val) {
            if (in_array($val, self::$BORDER_STYLES, true)) {
                $this->set_prop("outline_style", $val, $important);
            } elseif ($this->is_color_value($val)) {
                $this->set_prop("outline_color", $val, $important);
            } else {
                // Assume width
                $this->set_prop("outline_width", $val, $important);
            }
        }
    }

    /**
     * @param $val
     */
    function set_outline_width($val)
    {
        $this->_set_style_side_type("outline", "", "width", $val);
    }

    /**
     * @param $val
     */
    function set_outline_color($val)
    {
        $this->_set_style_side_type("outline", "", "color", $val);
    }

    /**
     * @param $val
     */
    function set_outline_style($val)
    {
        $this->_set_style_side_type("outline", "", "style", $val);
    }

    /**
     * Sets the border spacing
     *
     * @link http://www.w3.org/TR/CSS21/box.html#border-properties
     * @param float $val
     */
    function set_border_spacing($val)
    {
        $this->_prop_cache["border_spacing"] = null;

        if ($val === "inherit") {
            $this->_props_computed["border_spacing"] = null;
            return;
        }

        $arr = explode(" ", $val);

        if (count($arr) === 1) {
            $arr[1] = $arr[0];
        }

        $this->_props_computed["border_spacing"] = "$arr[0] $arr[1]";
    }

    /**
     * Sets the list style image
     *
     * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style-image
     * @param $val
     */
    function set_list_style_image($val)
    {
        $this->_prop_cache["list_style_image"] = null;

        if ($val === "inherit") {
            $this->_props_computed["list_style_image"] = null;
            return;
        }

        $parsed_val = $this->_stylesheet->resolve_url($val);

        if ($parsed_val === "none") {
            $this->_props_computed["list_style_image"] = "none";
        } else {
            $this->_props_computed["list_style_image"] = "url(" . $parsed_val . ")";
        }
    }

    /**
     * Sets the list style
     *
     * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style
     * @param string $value
     * @param bool $important
     */
    function set_list_style($value, bool $important = false)
    {
        static $positions = ["inside", "outside"];
        static $types = [
            "disc", "circle", "square",
            "decimal-leading-zero", "decimal", "1",
            "lower-roman", "upper-roman", "a", "A",
            "lower-greek",
            "lower-latin", "upper-latin",
            "lower-alpha", "upper-alpha",
            "armenian", "georgian", "hebrew",
            "cjk-ideographic", "hiragana", "katakana",
            "hiragana-iroha", "katakana-iroha", "none"
        ];

        $components = $this->parse_property_value($value);

        foreach ($components as $val) {
            /* http://www.w3.org/TR/CSS21/generate.html#list-style
             * A value of 'none' for the 'list-style' property sets both 'list-style-type' and 'list-style-image' to 'none'
             */
            if ($val === "none") {
                $this->set_prop("list_style_type", $val, $important);
                $this->set_prop("list_style_image", $val, $important);
                continue;
            }

            //On setting or merging or inheriting list_style_image as well as list_style_type,
            //and url exists, then url has precedence, otherwise fall back to list_style_type
            //Firefox is wrong here (list_style_image gets overwritten on explicit list_style_type)
            //Internet Explorer 7/8 and dompdf is right.

            if (mb_substr($val, 0, 4) === "url(") {
                $this->set_prop("list_style_image", $val, $important);
                continue;
            }

            if (in_array($val, $types, true)) {
                $this->set_prop("list_style_type", $val, $important);
            } elseif (in_array($val, $positions, true)) {
                $this->set_prop("list_style_position", $val, $important);
            }
        }
    }

    /**
     * @param $val
     */
    function set_size($val)
    {
        $this->_prop_cache["size"] = null;

        $length_re = "/(\d+\s*(?:pt|px|pc|rem|em|ex|in|cm|mm|%))/";

        $val = mb_strtolower($val);

        if ($val === "auto") {
            $this->_props_computed["size"] = $val;
            return;
        }

        $parts = preg_split("/\s+/", $val);

        $computed = [];
        if (preg_match($length_re, $parts[0])) {
            $computed[] = $this->length_in_pt($parts[0]);

            if (isset($parts[1]) && preg_match($length_re, $parts[1])) {
                $computed[] = $this->length_in_pt($parts[1]);
            } else {
                $computed[] = $computed[0];
            }

            if (isset($parts[2]) && $parts[2] === "landscape") {
                $computed = array_reverse($computed);
            }
        } elseif (isset(CPDF::$PAPER_SIZES[$parts[0]])) {
            $computed = array_slice(CPDF::$PAPER_SIZES[$parts[0]], 2, 2);

            if (isset($parts[1]) && $parts[1] === "landscape") {
                $computed = array_reverse($computed);
            }
        } else {
            $this->_props_computed["size"] = null;
            return;
        }

        $this->_props_computed["size"] = $computed;
    }

    /**
     * Gets the CSS3 transform property
     *
     * @link http://www.w3.org/TR/css3-2d-transforms/#transform-property
     * @return array|null
     */
    function get_transform()
    {
        //TODO: should be handled in setter (lengths set to absolute)

        $number = "\s*([^,\s]+)\s*";
        $tr_value = "\s*([^,\s]+)\s*";
        $angle = "\s*([^,\s]+(?:deg|rad)?)\s*";

        if (!preg_match_all("/[a-z]+\([^\)]+\)/i", $this->_props_computed["transform"], $parts, PREG_SET_ORDER)) {
            return null;
        }

        $functions = [
            //"matrix"     => "\($number,$number,$number,$number,$number,$number\)",

            "translate" => "\($tr_value(?:,$tr_value)?\)",
            "translateX" => "\($tr_value\)",
            "translateY" => "\($tr_value\)",

            "scale" => "\($number(?:,$number)?\)",
            "scaleX" => "\($number\)",
            "scaleY" => "\($number\)",

            "rotate" => "\($angle\)",

            "skew" => "\($angle(?:,$angle)?\)",
            "skewX" => "\($angle\)",
            "skewY" => "\($angle\)",
        ];

        $transforms = [];

        foreach ($parts as $part) {
            $t = $part[0];

            foreach ($functions as $name => $pattern) {
                if (preg_match("/$name\s*$pattern/i", $t, $matches)) {
                    $values = array_slice($matches, 1);

                    switch ($name) {
                        // <angle> units
                        case "rotate":
                        case "skew":
                        case "skewX":
                        case "skewY":

                            foreach ($values as $i => $value) {
                                if (strpos($value, "rad")) {
                                    $values[$i] = rad2deg(floatval($value));
                                } else {
                                    $values[$i] = floatval($value);
                                }
                            }

                            switch ($name) {
                                case "skew":
                                    if (!isset($values[1])) {
                                        $values[1] = 0;
                                    }
                                    break;
                                case "skewX":
                                    $name = "skew";
                                    $values = [$values[0], 0];
                                    break;
                                case "skewY":
                                    $name = "skew";
                                    $values = [0, $values[0]];
                                    break;
                            }
                            break;

                        // <translation-value> units
                        case "translate":
                            $values[0] = $this->length_in_pt($values[0], (float)$this->length_in_pt($this->width));

                            if (isset($values[1])) {
                                $values[1] = $this->length_in_pt($values[1], (float)$this->length_in_pt($this->height));
                            } else {
                                $values[1] = 0;
                            }
                            break;

                        case "translateX":
                            $name = "translate";
                            $values = [$this->length_in_pt($values[0], (float)$this->length_in_pt($this->width)), 0];
                            break;

                        case "translateY":
                            $name = "translate";
                            $values = [0, $this->length_in_pt($values[0], (float)$this->length_in_pt($this->height))];
                            break;

                        // <number> units
                        case "scale":
                            if (!isset($values[1])) {
                                $values[1] = $values[0];
                            }
                            break;

                        case "scaleX":
                            $name = "scale";
                            $values = [$values[0], 1.0];
                            break;

                        case "scaleY":
                            $name = "scale";
                            $values = [1.0, $values[0]];
                            break;
                    }

                    $transforms[] = [
                        $name,
                        $values,
                    ];
                }
            }
        }

        return $transforms;
    }

    /**
     * @param $val
     */
    function set_transform($val)
    {
        $this->_prop_cache["transform"] = null;

        if ($val === "inherit") {
            $this->_props_computed["transform"] = null;
            return;
        }
        
        $this->_props_computed["transform"] = $val;
    }

    /**
     * Sets the CSS3 transform-origin property
     *
     * @link http://www.w3.org/TR/css3-2d-transforms/#transform-origin
     * @param string $val
     */
    function set_transform_origin($val)
    {
        $this->_prop_cache["transform_origin"] = null;

        if ($val === "inherit") {
            $this->_props_computed["transform_origin"] = null;
            return;
        }

        $this->_props_computed["transform_origin"] = $val;
    }

    /**
     * Gets the CSS3 transform-origin property
     *
     * @link http://www.w3.org/TR/css3-2d-transforms/#transform-origin
     * @return mixed[]
     */
    function get_transform_origin()
    {
        //TODO: should be handled in setter
        
        $values = preg_split("/\s+/", $this->_props_computed["transform_origin"]);

        $values = array_map(function ($value) {
            if (in_array($value, ["top", "left"])) {
                return 0;
            } else if (in_array($value, ["bottom", "right"])) {
                return "100%";
            } else {
                return $value;
            }
        }, $values);

        if (!isset($values[1])) {
            $values[1] = $values[0];
        }

        return $values;
    }

    /**
     * @param $val
     * @return null
     */
    protected function parse_image_resolution($val)
    {
        // If exif data could be get:
        // $re = '/^\s*(\d+|normal|auto)(?:\s*,\s*(\d+|normal))?\s*$/';

        $re = '/^\s*(\d+|normal|auto)\s*$/';

        if (!preg_match($re, $val, $matches)) {
            return null;
        }

        return $matches[1];
    }

    /**
     * auto | normal | dpi
     *
     * @param $val
     */
    function set_background_image_resolution($val)
    {
        $this->_prop_cache["background_image_resolution"] = null;

        if ($val === "inherit") {
            $this->_props_computed["background_image_resolution"] = null;
            return;
        }

        $parsed = $this->parse_image_resolution($val);

        $this->_props_computed["background_image_resolution"] = $parsed;
    }

    /**
     * auto | normal | dpi
     *
     * @param $val
     */
    function set_image_resolution($val)
    {
        $this->_prop_cache["image_resolution"] = null;

        if ($val === "inherit") {
            $this->_props_computed["image_resolution"] = null;
            return;
        }

        $parsed = $this->parse_image_resolution($val);

        $this->_props_computed["image_resolution"] = $parsed;
    }

    /**
     * @param $val
     */
    function set_z_index($val)
    {
        $this->_prop_cache["z_index"] = null;

        if ($val === "inherit") {
            $this->_props_computed["z_index"] = null;
            return;
        }

        if ($val !== "auto" && round((float) $val) != $val) {
            $this->_props_computed["z_index"] = null;
            return;
        }

        $this->_props_computed["z_index"] = $val;
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
    function __toString()
    {
        $parent_font_size = $this->parent_style
            ? $this->parent_style->font_size
            : self::$default_font_size;

        return print_r(array_merge(["parent_font_size" => $parent_font_size ],
            $this->_props), true);
    }

    /*DEBUGCSS*/
    function debug_print()
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
        foreach ($this->_prop_cache as $prop => $val) {
            print '        ' . $prop . ': ' . preg_replace("/\r\n/", ' ', print_r($val, true));
            print ";\n";
        }
        print "      ]\n";
        print "    ]\n";
    }
}
