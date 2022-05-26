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
 * @package dompdf
 */
class Style
{
    protected const CSS_IDENTIFIER = "-?[_a-zA-Z]+[_a-zA-Z0-9-]*";
    protected const CSS_INTEGER = "[+-]?\d+";
    protected const CSS_NUMBER = "[+-]?\d*\.?\d+";

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
    public const BORDER_STYLES = ["none", "hidden", "dotted", "dashed", "solid",
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
     * @link https://www.w3.org/TR/CSS21/propidx.html
     *
     * @var array
     */
    protected static $_defaults = null;

    /**
     * List of inherited properties
     *
     * @link https://www.w3.org/TR/CSS21/propidx.html
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
     * Properties set by an `!important` declaration.
     *
     * @var array
     */
    protected $_important_props = [];

    /**
     * Specified (or declared) values of the CSS properties.
     *
     * https://www.w3.org/TR/css-cascade-3/#value-stages
     *
     * @var array
     */
    protected $_props = [];

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
    protected $_props_used = [];

    /**
     * Marks properties with non-final used values that should be cleared on
     * style reset.
     *
     * @var array
     */
    protected $non_final_used = [];

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
            "word_spacing"
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
            $d["inset"] = "";
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
            $d["word_break"] = "normal";
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
                "word_break",
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
     * Clear all non-final used values.
     *
     * @return void
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
                : $root_style->__get("font_size");
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
            foreach (self::$_inherited as $prop) {
                // For properties that inherit by default: When the cascade did
                // not result in a value, inherit the parent value. Inheritance
                // is handled via the specific sub-properties for shorthands
                if (isset($this->_props[$prop]) || isset(self::$_props_shorthand[$prop])) {
                    continue;
                }

                if (isset($parent->_props[$prop])) {
                    $parent_val = \array_key_exists($prop, $parent->_props_computed)
                        ? $parent->_props_computed[$prop]
                        : $parent->compute_prop($prop, $parent->_props[$prop]);

                    $this->_props[$prop] = $parent_val;
                    $this->_props_computed[$prop] = $parent_val;
                    $this->_props_used[$prop] = null;
                }
            }
        }

        foreach ($this->_props as $prop => $val) {
            if ($val === "inherit") {
                if ($parent && isset($parent->_props[$prop])) {
                    $parent_val = \array_key_exists($prop, $parent->_props_computed)
                        ? $parent->_props_computed[$prop]
                        : $parent->compute_prop($prop, $parent->_props[$prop]);

                    $this->_props[$prop] = $parent_val;
                    $this->_props_computed[$prop] = $parent_val;
                    $this->_props_used[$prop] = null;
                } else {
                    // Parent prop not set, use default
                    $this->_props[$prop] = self::$_defaults[$prop];
                    unset($this->_props_computed[$prop]);
                    unset($this->_props_used[$prop]);
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
                unset($this->_props_used[$prop]);
            } else {
                $this->_props_computed[$prop] = $computed;
                $this->_props_used[$prop] = null;
            }
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
     * @param string $val                The value declaration.
     * @param bool   $important          Whether the declaration is important.
     * @param bool   $clear_dependencies Whether to clear computed values of dependent properties.
     */
    public function set_prop(string $prop, string $val, bool $important = false, bool $clear_dependencies = true): void
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

        if ($prop !== "content" && mb_strpos($val, "url") === false && mb_strlen($val) > 1) {
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
                $method = "_set_$prop";

                if (!isset(self::$_methods_cache[$method])) {
                    self::$_methods_cache[$method] = method_exists($this, $method);
                }

                if (self::$_methods_cache[$method]) {
                    $this->$method($val, $important, $clear_dependencies);
                }
            }
        } else {
            // Legacy support for `word-break: break-word`
            // https://www.w3.org/TR/css-text-3/#valdef-word-break-break-word
            if ($prop === "word_break" && $val === "break-word") {
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
            unset($this->_props_used[$prop]);

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

            // FIXME: temporary hack around lack of persistence of base href for
            // URLs. Compute value immediately, before the original base href is
            // no longer available
            if ($prop === "background_image" || $prop === "list_style_image") {
                $this->compute_prop($prop, $val);
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

        if (!isset(self::$_defaults[$prop])) {
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

        if (!isset(self::$_defaults[$prop])) {
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

        if (!isset(self::$_defaults[$prop])) {
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
                ? $this->$method($computed)
                : $computed;

            $this->_props_used[$prop] = $used;
            return $used;
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
        $method = "_compute_$prop";

        if (!isset(self::$_methods_cache[$method])) {
            self::$_methods_cache[$method] = method_exists($this, $method);
        }

        // During style merge, the parent style is not available yet, so
        // temporarily use the initial value for `inherit` properties. The
        // keyword is properly resolved during inheritance
        if ($val === "inherit") {
            $val = self::$_defaults[$prop];
        }

        if (self::$_methods_cache[$method]) {
            $computed = $this->$method($val);
        } elseif ($val !== "") {
            $computed = $val;
        } else {
            $computed = null;
        }

        $this->_props_computed[$prop] = $computed;
        $this->_props_used[$prop] = null;

        return $computed;
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
     * Getter for the `font-family` CSS property.
     *
     * Uses the {@link FontMetrics} class to resolve the font family into an
     * actual font file.
     *
     * @param string $computed
     * @return string
     * @throws Exception
     *
     * @link https://www.w3.org/TR/CSS21/fonts.html#propdef-font-family
     */
    protected function _get_font_family($computed): string
    {
        //TODO: we should be using the calculated prop rather than perform the entire family parsing operation again

        $fontMetrics = $this->getFontMetrics();
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
        $subtype = $fontMetrics->getType($weight . ' ' . $font_style);

        $families = preg_split("/\s*,\s*/", $computed);

        $font = null;
        foreach ($families as $family) {
            //remove leading and trailing string delimiters, e.g. on font names with spaces;
            //remove leading and trailing whitespace
            $family = trim($family, " \t\n\r\x0B\"'");
            if ($DEBUGCSS) {
                print '(' . $family . ')';
            }
            $font = $fontMetrics->getFont($family, $subtype);

            if ($font) {
                if ($DEBUGCSS) {
                    print "<pre>[get_font_family:";
                    print '(' . $computed . '.' . $font_style . '.' . $weight . '.' . $subtype . ')';
                    print '(' . $font . ")get_font_family]\n</pre>";
                }
                return $font;
            }
        }

        $family = null;
        if ($DEBUGCSS) {
            print '(default)';
        }
        $font = $fontMetrics->getFont($family, $subtype);

        if ($font) {
            if ($DEBUGCSS) {
                print '(' . $font . ")get_font_family]\n</pre>";
            }
            return $font;
        }

        throw new Exception("Unable to find a suitable font replacement for: '" . $computed . "'");
    }

    /**
     * @param float|string $computed
     * @return float
     *
     * @link https://www.w3.org/TR/css-text-4/#word-spacing-property
     */
    protected function _get_word_spacing($computed)
    {
        if (is_float($computed)) {
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
        if (is_float($computed)) {
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
        if (is_float($computed)) {
            return $computed;
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
        return $this->_stylesheet->resolve_url($computed);
    }

    /**
     * Returns the background position as an array
     *
     * The returned array has the following format:
     * `array(x, y, "x" => x, "y" => y)`
     *
     * @param string $computed
     * @return array
     *
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-position
     */
    protected function _get_background_position($computed): array
    {
        $tmp = explode(" ", $computed);

        return [
            0 => $tmp[0], "x" => $tmp[0],
            1 => $tmp[1], "y" => $tmp[1],
        ];
    }

    /**
     * Returns the background size as an array
     *
     * The return value has one of the following formats:
     * * `cover`
     * * `contain`
     * * `array(width, height)`
     *
     * @param string $computed
     * @return array|string
     *
     * @link https://www.w3.org/TR/css3-background/#background-size
     */
    protected function _get_background_size($computed)
    {
        if ($computed === "cover" || $computed === "contain") {
            return $computed;
        }

        $result = explode(" ", $computed);
        return [$result[0], $result[1]];
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
            (is_array($color) ? $color["hex"] : $color);
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
     * See {@link Style::_get_color()} for format of the color array.
     *
     * @param string $computed
     * @return array|string
     *
     * @link https://www.w3.org/TR/CSS21/box.html#border-color-properties
     */
    protected function _get_outline_color($computed)
    {
        return $this->get_color_value($computed);
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
            (is_array($color) ? $color["hex"] : $color);
    }

    /**
     * Returns border spacing as an array
     *
     * The array has the format (h_space, v_space)
     *
     * @param string $computed
     * @return array
     *
     * @link https://www.w3.org/TR/CSS21/tables.html#propdef-border-spacing
     */
    protected function _get_border_spacing($computed): array
    {
        $arr = explode(" ", $computed);

        if (count($arr) === 1) {
            $arr[1] = $arr[0];
        }

        return $arr;
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
        return $this->_stylesheet->resolve_url($computed);
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
     * @param string $computed
     * @return array|string
     *
     * @link https://www.w3.org/TR/CSS21/generate.html#propdef-counter-increment
     */
    protected function _get_counter_increment($computed)
    {
        if ($computed === "none") {
            return $computed;
        }

        return $this->parse_counter_prop($computed, 1);
    }

    /**
     * @param string $computed
     * @return array|string
     *
     * @link https://www.w3.org/TR/CSS21/generate.html#propdef-counter-reset
     */
    protected function _get_counter_reset($computed)
    {
        if ($computed === "none") {
            return $computed;
        }

        return $this->parse_counter_prop($computed, 0);
    }

    /**
     * @param string $computed
     * @return string[]|string
     *
     * @link https://www.w3.org/TR/CSS21/generate.html#propdef-content
     */
    protected function _get_content($computed)
    {
        if ($computed === "normal" || $computed === "none") {
            return $computed;
        }

        return $this->parse_property_value($computed);
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

    /**
     * Parse a property value with 1 to 4 components into 4 values, as required
     * by shorthand properties such as `margin`, `padding`, and `border-radius`.
     *
     * @param string $value
     *
     * @return string[]|null An array with 4 elements, or `null` if the declaration is invalid.
     */
    protected function parse_quad_shorthand(string $value): ?array
    {
        $v = $this->parse_property_value($value);

        switch (count($v)) {
            case 1:
                return [$v[0], $v[0], $v[0], $v[0]];
            case 2:
                return [$v[0], $v[1], $v[0], $v[1]];
            case 3:
                return [$v[0], $v[1], $v[2], $v[1]];
            case 4:
                return [$v[0], $v[1], $v[2], $v[3]];
            default:
                return null;
        }
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
        $munged_color = $val !== "currentcolor"
            ? $this->munge_color($val)
            : $val;

        if ($munged_color === null) {
            return null;
        }

        return is_array($munged_color) ? $munged_color["hex"] : $munged_color;
    }

    /**
     * @param string $val
     * @return string|null
     */
    protected function compute_border_style(string $val): ?string
    {
        return in_array($val, self::BORDER_STYLES, true) ? $val : null;
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
     * Compute margin, border, padding, outline width.
     *
     * @param string $style
     * @param string $side
     * @param string $val
     *
     * @return float|string|null
     */
    protected function compute_style_side_width(string $style, string $side, string $val)
    {
        if ($style === "border" || $style === "outline") {
            // Border-width keywords
            if ($val === "thin") {
                $computed = 0.5;
            } elseif ($val === "medium") {
                $computed = 1.5;
            } elseif ($val === "thick") {
                $computed = 2.5;
            } elseif (mb_strpos($val, "%") !== false) {
                $computed = null;
            } else {
                $computed = $this->single_length_in_pt($val);

                if ($computed < 0) {
                    $computed = null;
                }
            }

            if ($computed === null) {
                return null;
            } else {
                $line_style_prop = $this->prop_name($style, $side, "style");
                $line_style = $this->__get($line_style_prop);
                $has_line_style = $line_style !== "none" && $line_style !== "hidden";

                return $has_line_style ? $computed : 0.0;
            }
        } elseif ($style === "margin" || $style === "padding") {
            if ($val === "none") {
                // Legacy support for `none` keyword, not covered by spec
                $computed = 0.0;
            } elseif ($style === "margin" && $val === "auto") {
                $computed = $val;
            } elseif (mb_strpos($val, "%") !== false) {
                $computed = $val;
            } else {
                $computed = $this->single_length_in_pt($val);

                if ($style === "padding" && $computed < 0) {
                    $computed = null;
                }
            }

            return $computed;
        } elseif ($val !== "") {
            return $val;
        }

        return null;
    }

    /**
     * @param string $style
     * @param string $type
     * @param string $val
     * @param bool   $important
     * @param bool   $clear_dependencies
     */
    protected function set_style_type(string $style, string $type, string $val, bool $important, bool $clear_dependencies): void
    {
        $v = $this->parse_quad_shorthand($val);

        if ($v === null) {
            return;
        }

        [$top, $right, $bottom, $left] = $v;

        $this->set_prop($this->prop_name($style, "top", $type), $top, $important, $clear_dependencies);
        $this->set_prop($this->prop_name($style, "right", $type), $right, $important, $clear_dependencies);
        $this->set_prop($this->prop_name($style, "bottom", $type), $bottom, $important, $clear_dependencies);
        $this->set_prop($this->prop_name($style, "left", $type), $left, $important, $clear_dependencies);
    }

    /*======================*/

    /**
     * @link https://www.w3.org/TR/CSS21/visuren.html#display-prop
     */
    protected function _compute_display(string $val)
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
            return "url($parsed_val)";
        }
    }

    /**
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-repeat
     */
    protected function _compute_background_repeat(string $val)
    {
        $keywords = ["repeat", "repeat-x", "repeat-y", "no-repeat"];
        return in_array($val, $keywords, true) ? $val : null;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-attachment
     */
    protected function _compute_background_attachment(string $val)
    {
        $keywords = ["scroll", "fixed"];
        return in_array($val, $keywords, true) ? $val : null;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-position
     */
    protected function _compute_background_position(string $val)
    {
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

        return "$x $y";
    }

    /**
     * @link https://www.w3.org/TR/css3-background/#background-size
     */
    protected function _compute_background_size(string $val)
    {
        if ($val === "cover" || $val === "contain") {
            return $val;
        }

        $result = explode(" ", $val);

        $width = $result[0];
        if ($width !== "auto" && mb_strpos($width, "%") === false) {
            $width = (float)$this->length_in_pt($width);
        }

        $height = $result[1] ?? "auto";
        if ($height !== "auto" && mb_strpos($height, "%") === false) {
            $height = (float)$this->length_in_pt($height);
        }

        return "$width $height";
    }

    /**
     * @param string $value
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background
     */
    protected function _set_background(string $value, bool $important, bool $clear_dependencies): void
    {
        if ($value === "none") {
            $this->set_prop("background_image", "none", $important, $clear_dependencies);
            $this->set_prop("background_color", "transparent", $important, $clear_dependencies);
        } else {
            $components = $this->parse_property_value($value);
            $pos_size = [];

            foreach ($components as $val) {
                if ($val === "none" || mb_substr($val, 0, 4) === "url(") {
                    $this->set_prop("background_image", $val, $important, $clear_dependencies);
                } elseif ($val === "scroll" || $val === "fixed") {
                    $this->set_prop("background_attachment", $val, $important, $clear_dependencies);
                } elseif ($val === "repeat" || $val === "repeat-x" || $val === "repeat-y" || $val === "no-repeat") {
                    $this->set_prop("background_repeat", $val, $important, $clear_dependencies);
                } elseif ($this->is_color_value($val)) {
                    $this->set_prop("background_color", $val, $important, $clear_dependencies);
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

                $this->set_prop("background_position", implode(" ", $pos), $important, $clear_dependencies);

                if (count($size)) {
                    $this->set_prop("background_size", implode(" ", $size), $important, $clear_dependencies);
                }
            }
        }
    }

    /**
     * @link https://www.w3.org/TR/CSS21/fonts.html#propdef-font-size
     */
    protected function _compute_font_size(string $size)
    {
        $parent_font_size = isset($this->parent_style)
            ? $this->parent_style->__get("font_size")
            : self::$default_font_size;

        switch ($size) {
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

        return $fs;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/fonts.html#font-boldness
     */
    protected function _compute_font_weight(string $weight)
    {
        $computed_weight = $weight;

        if ($weight === "bolder") {
            //TODO: One font weight heavier than the parent element (among the available weights of the font).
            $computed_weight = "bold";
        } elseif ($weight === "lighter") {
            //TODO: One font weight lighter than the parent element (among the available weights of the font).
            $computed_weight = "normal";
        }

        return $computed_weight;
    }

    /**
     * Handle the `font` shorthand property.
     *
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
     * @param string $val
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/CSS21/fonts.html#font-shorthand
     */
    protected function _set_font(string $val, bool $important, bool $clear_dependencies): void
    {
        if (preg_match("/^(italic|oblique|normal)\s*(.*)$/i", $val, $match)) {
            $this->set_prop("font_style", $match[1], $important, $clear_dependencies);
            $val = $match[2];
        }

        if (preg_match("/^(small-caps|normal)\s*(.*)$/i", $val, $match)) {
            $this->set_prop("font_variant", $match[1], $important, $clear_dependencies);
            $val = $match[2];
        }

        //matching numeric value followed by unit -> this is indeed a subsequent font size. Skip!
        if (preg_match("/^(bold|bolder|lighter|100|200|300|400|500|600|700|800|900|normal)\s*(.*)$/i", $val, $match) &&
            !preg_match("/^(?:pt|px|pc|rem|em|ex|in|cm|mm|%)/", $match[2])
        ) {
            $this->set_prop("font_weight", $match[1], $important, $clear_dependencies);
            $val = $match[2];
        }

        if (preg_match("/^(xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger|\d+\s*(?:pt|px|pc|rem|em|ex|in|cm|mm|%))(?:\/|\s*)(.*)$/i", $val, $match)) {
            $this->set_prop("font_size", $match[1], $important, $clear_dependencies);
            $val = $match[2];
            if (preg_match("/^(?:\/|\s*)(\d+\s*(?:pt|px|pc|rem|em|ex|in|cm|mm|%)?)\s*(.*)$/i", $val, $match)) {
                $this->set_prop("line_height", $match[1], $important, $clear_dependencies);
                $val = $match[2];
            }
        }

        if (strlen($val) != 0) {
            $this->set_prop("font_family", $val, $important, $clear_dependencies);
        }
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
        $alignment = $val;
        if ($alignment === "") {
            $alignment = "left";
            if ($this->__get("direction") === "rtl") {
                $alignment = "right";
            }
        }

        if (!in_array($alignment, self::TEXT_ALIGN_KEYWORDS, true)) {
            return null;
        }

        return $alignment;
    }

    /**
     * @link https://www.w3.org/TR/css-text-4/#word-spacing-property
     */
    protected function _compute_word_spacing(string $val)
    {
        if ($val === "normal") {
            return 0.0;
        }

        return mb_strpos($val, "%") === false
            ? $this->single_length_in_pt($val)
            : $val;
    }

    /**
     * @link https://www.w3.org/TR/css-text-4/#letter-spacing-property
     */
    protected function _compute_letter_spacing(string $val)
    {
        if ($val === "normal") {
            return 0.0;
        }

        return mb_strpos($val, "%") === false
            ? $this->single_length_in_pt($val)
            : $val;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/visudet.html#propdef-line-height
     */
    protected function _compute_line_height(string $val)
    {
        if ($val === "normal") {
            return $val;
        }

        // Compute number values to string and lengths to float (in pt)
        if (is_numeric($val)) {
            return (string) $val;
        }

        $font_size = $this->__get("font_size");
        return $this->single_length_in_pt($val, $font_size);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/page.html#propdef-page-break-before
     */
    protected function _compute_page_break_before(string $break)
    {
        if ($break === "left" || $break === "right") {
            $break = "always";
        }

        return $break;
    }

    /**
     * @link https://www.w3.org/TR/CSS21/page.html#propdef-page-break-after
     */
    protected function _compute_page_break_after(string $break)
    {
        if ($break === "left" || $break === "right") {
            $break = "always";
        }

        return $break;
    }

    /**
     * @param string $val
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/css-position-3/#propdef-inset
     */
    protected function _set_inset(string $val, bool $important, bool $clear_dependencies): void
    {
        $v = $this->parse_quad_shorthand($val);

        if ($v === null) {
            return;
        }

        [$top, $right, $bottom, $left] = $v;

        $this->set_prop("top", $top, $important, $clear_dependencies);
        $this->set_prop("right", $right, $important, $clear_dependencies);
        $this->set_prop("bottom", $bottom, $important, $clear_dependencies);
        $this->set_prop("left", $left, $important, $clear_dependencies);
    }

    /**
     * @param string $val
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/CSS21/box.html#margin-properties
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-margin
     */
    protected function _set_margin(string $val, bool $important, bool $clear_dependencies): void
    {
        $this->set_style_type("margin", "", $val, $important, $clear_dependencies);
    }

    protected function _compute_margin_top(string $val)
    {
        return $this->compute_style_side_width("margin", "top", $val);
    }

    protected function _compute_margin_right(string $val)
    {
        return $this->compute_style_side_width("margin", "right", $val);
    }

    protected function _compute_margin_bottom(string $val)
    {
        return $this->compute_style_side_width("margin", "bottom", $val);
    }

    protected function _compute_margin_left(string $val)
    {
        return $this->compute_style_side_width("margin", "left", $val);
    }

    /**
     * @param string $val
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/CSS21/box.html#padding-properties
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-padding
     */
    protected function _set_padding(string $val, bool $important, bool $clear_dependencies): void
    {
        $this->set_style_type("padding", "", $val, $important, $clear_dependencies);
    }

    protected function _compute_padding_top(string $val)
    {
        return $this->compute_style_side_width("padding", "top", $val);
    }

    protected function _compute_padding_right(string $val)
    {
        return $this->compute_style_side_width("padding", "right", $val);
    }

    protected function _compute_padding_bottom(string $val)
    {
        return $this->compute_style_side_width("padding", "bottom", $val);
    }

    protected function _compute_padding_left(string $val)
    {
        return $this->compute_style_side_width("padding", "left", $val);
    }

    /**
     * @param string $value
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/CSS21/box.html#border-properties
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-border
     */
    protected function _set_border(string $value, bool $important, bool $clear_dependencies): void
    {
        $this->set_border_side("top", $value, $important, $clear_dependencies);
        $this->set_border_side("right", $value, $important, $clear_dependencies);
        $this->set_border_side("bottom", $value, $important, $clear_dependencies);
        $this->set_border_side("left", $value, $important, $clear_dependencies);
    }

    /**
     * Set a single border side property.
     *
     * @param string $side
     * @param string $value     `[width] [style] [color]`
     * @param bool   $important
     * @param bool   $clear_dependencies
     */
    protected function set_border_side(string $side, string $value, bool $important, bool $clear_dependencies): void
    {
        $components = $this->parse_property_value($value);

        foreach ($components as $val) {
            if (in_array($val, self::BORDER_STYLES, true)) {
                $this->set_prop("border_{$side}_style", $val, $important, $clear_dependencies);
            } elseif ($this->is_color_value($val)) {
                $this->set_prop("border_{$side}_color", $val, $important, $clear_dependencies);
            } else {
                // Assume width
                $this->set_prop("border_{$side}_width", $val, $important, $clear_dependencies);
            }
        }
    }

    protected function _set_border_top(string $val, bool $important, bool $clear_dependencies): void
    {
        $this->set_border_side("top", $val, $important, $clear_dependencies);
    }

    protected function _set_border_right(string $val, bool $important, bool $clear_dependencies): void
    {
        $this->set_border_side("right", $val, $important, $clear_dependencies);
    }

    protected function _set_border_bottom(string $val, bool $important, bool $clear_dependencies): void
    {
        $this->set_border_side("bottom", $val, $important, $clear_dependencies);
    }

    protected function _set_border_left(string $val, bool $important, bool $clear_dependencies): void
    {
        $this->set_border_side("left", $val, $important, $clear_dependencies);
    }

    /**
     * @param string $val
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-border-color
     */
    protected function _set_border_color(string $val, bool $important, bool $clear_dependencies): void
    {
        $this->set_style_type("border", "color", $val, $important, $clear_dependencies);
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
     * @param string $val
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-border-style
     */
    protected function _set_border_style(string $val, bool $important, bool $clear_dependencies): void
    {
        $this->set_style_type("border", "style", $val, $important, $clear_dependencies);
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
     * @param string $val
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/CSS21/box.html#propdef-border-width
     */
    protected function _set_border_width(string $val, bool $important, bool $clear_dependencies): void
    {
        $this->set_style_type("border", "width", $val, $important, $clear_dependencies);
    }

    protected function _compute_border_top_width(string $val)
    {
        return $this->compute_style_side_width("border", "top", $val);
    }

    protected function _compute_border_right_width(string $val)
    {
        return $this->compute_style_side_width("border", "right", $val);
    }

    protected function _compute_border_bottom_width(string $val)
    {
        return $this->compute_style_side_width("border", "bottom", $val);
    }

    protected function _compute_border_left_width(string $val)
    {
        return $this->compute_style_side_width("border", "left", $val);
    }

    /**
     * @param string $val
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/css-backgrounds-3/#corners
     * @link https://www.w3.org/TR/css-backgrounds-3/#propdef-border-radius
     */
    protected function _set_border_radius(string $val, bool $important, bool $clear_dependencies): void
    {
        $v = $this->parse_quad_shorthand($val);

        if ($v === null) {
            return;
        }

        [$tl, $tr, $br, $bl] = $v;

        $this->set_prop("border_top_left_radius", $tl, $important, $clear_dependencies);
        $this->set_prop("border_top_right_radius", $tr, $important, $clear_dependencies);
        $this->set_prop("border_bottom_right_radius", $br, $important, $clear_dependencies);
        $this->set_prop("border_bottom_left_radius", $bl, $important, $clear_dependencies);
    }

    protected function compute_border_radius_corner(string $val)
    {
        return mb_strpos($val, "%") === false
            ? $this->single_length_in_pt($val)
            : $val;
    }

    protected function _compute_border_top_left_radius(string $val)
    {
        return $this->compute_border_radius_corner($val);
    }

    protected function _compute_border_top_right_radius(string $val)
    {
        return $this->compute_border_radius_corner($val);
    }

    protected function _compute_border_bottom_right_radius(string $val)
    {
        return $this->compute_border_radius_corner($val);
    }

    protected function _compute_border_bottom_left_radius(string $val)
    {
        return $this->compute_border_radius_corner($val);
    }

    /**
     * @param string $value
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/CSS21/ui.html#dynamic-outlines
     * @link https://www.w3.org/TR/CSS21/ui.html#propdef-outline
     */
    protected function _set_outline(string $value, bool $important, bool $clear_dependencies): void
    {
        $components = $this->parse_property_value($value);

        foreach ($components as $val) {
            if (in_array($val, self::BORDER_STYLES, true)) {
                $this->set_prop("outline_style", $val, $important, $clear_dependencies);
            } elseif ($this->is_color_value($val)) {
                $this->set_prop("outline_color", $val, $important, $clear_dependencies);
            } else {
                // Assume width
                $this->set_prop("outline_width", $val, $important, $clear_dependencies);
            }
        }
    }

    protected function _compute_outline_color(string $val)
    {
        return $this->compute_color_value($val);
    }

    protected function _compute_outline_style(string $val)
    {
        return $this->compute_border_style($val);
    }

    protected function _compute_outline_width(string $val)
    {
        return $this->compute_style_side_width("outline", "", $val);
    }

    /**
     * @link https://www.w3.org/TR/CSS21/tables.html#propdef-border-spacing
     */
    protected function _compute_border_spacing(string $val)
    {
        $arr = explode(" ", $val);

        if (count($arr) === 1) {
            $arr[1] = $arr[0];
        }

        return "$arr[0] $arr[1]";
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
            return "url($parsed_val)";
        }
    }

    /**
     * @param string $value
     * @param bool   $important
     * @param bool   $clear_dependencies
     *
     * @link https://www.w3.org/TR/CSS21/generate.html#propdef-list-style
     */
    protected function _set_list_style(string $value, bool $important, bool $clear_dependencies): void
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
            /* https://www.w3.org/TR/CSS21/generate.html#list-style
             * A value of 'none' for the 'list-style' property sets both 'list-style-type' and 'list-style-image' to 'none'
             */
            if ($val === "none") {
                $this->set_prop("list_style_type", $val, $important, $clear_dependencies);
                $this->set_prop("list_style_image", $val, $important, $clear_dependencies);
                continue;
            }

            //On setting or merging or inheriting list_style_image as well as list_style_type,
            //and url exists, then url has precedence, otherwise fall back to list_style_type
            //Firefox is wrong here (list_style_image gets overwritten on explicit list_style_type)
            //Internet Explorer 7/8 and dompdf is right.

            if (mb_substr($val, 0, 4) === "url(") {
                $this->set_prop("list_style_image", $val, $important, $clear_dependencies);
                continue;
            }

            if (in_array($val, $types, true)) {
                $this->set_prop("list_style_type", $val, $important, $clear_dependencies);
            } elseif (in_array($val, $positions, true)) {
                $this->set_prop("list_style_position", $val, $important, $clear_dependencies);
            }
        }
    }

    /**
     * @link https://www.w3.org/TR/css-page-3/#page-size-prop
     */
    protected function _compute_size(string $val)
    {
        $length_re = "/(\d+\s*(?:pt|px|pc|rem|em|ex|in|cm|mm|%))/";

        if ($val === "auto") {
            return $val;
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
            return null;
        }

        return $computed;
    }

    /**
     * @param string $computed
     * @return array|null
     *
     * @link https://www.w3.org/TR/css3-2d-transforms/#transform-property
     */
    protected function _get_transform($computed)
    {
        //TODO: should be handled in setter (lengths set to absolute)

        $number = "\s*([^,\s]+)\s*";
        $tr_value = "\s*([^,\s]+)\s*";
        $angle = "\s*([^,\s]+(?:deg|rad)?)\s*";

        if (!preg_match_all("/[a-z]+\([^\)]+\)/i", $computed, $parts, PREG_SET_ORDER)) {
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
     * @param string $computed
     * @return array
     *
     * @link https://www.w3.org/TR/css3-2d-transforms/#transform-origin
     */
    protected function _get_transform_origin($computed)
    {
        //TODO: should be handled in setter

        $values = preg_split("/\s+/", $computed);

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
     * @param string $val
     * @return string|null
     */
    protected function parse_image_resolution(string $val): ?string
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
     * @link https://www.w3.org/TR/CSS21//visuren.html#propdef-z-index
     */
    protected function _compute_z_index(string $val)
    {
        if ($val === "auto") {
            return $val;
        }

        $integer = self::CSS_INTEGER;

        if (preg_match("/^$integer$/", $val)) {
            return (int) $val;
        }

        return null;
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
