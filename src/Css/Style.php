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
use Dompdf\Helpers;

/**
 * Represents CSS properties.
 *
 * The Style class is responsible for handling and storing CSS properties.
 * It includes methods to resolve colors and lengths, as well as getters &
 * setters for many CSS properites.
 *
 * Actual CSS parsing is performed in the {@link Stylesheet} class.
 *
 * @package dompdf
 */
class Style
{

    const CSS_IDENTIFIER = "-?[_a-zA-Z]+[_a-zA-Z0-9-]*";
    const CSS_INTEGER = "-?\d+";

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
     * List of all inline types.  Should really be a constant.
     *
     * @var array
     */
    static $INLINE_TYPES = ["inline"];

    /**
     * List of all block types.  Should really be a constant.
     *
     * @var array
     */
    static $BLOCK_TYPES = ["block", "inline-block", "table-cell", "list-item"];

    /**
     * List of all positionned types.  Should really be a constant.
     *
     * @var array
     */
    static $POSITIONNED_TYPES = ["relative", "absolute", "fixed"];

    /**
     * List of all table types.  Should really be a constant.
     *
     * @var array;
     */
    static $TABLE_TYPES = ["table", "inline-table"];

    /**
     * List of valid border styles.  Should also really be a constant.
     *
     * @var array
     */
    static $BORDER_STYLES = ["none", "hidden", "dotted", "dashed", "solid",
        "double", "groove", "ridge", "inset", "outset"];

    /**
     * List of CSS shorthand properties
     *
     * @var array
     */
    protected static $_props_shorthand = ["background", "border",
        "border_bottom", "border_color", "border_left", "border_radius",
        "border_right", "border_style", "border_top", "border_width",
        "flex", "font", "list_style", "margin", "padding"];

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
     * @see Stylesheet
     * @var Stylesheet
     */
    protected $_stylesheet; // stylesheet this style is attached to

    /**
     * Media queries attached to the style
     *
     * @var int
     */
    protected $_media_queries;

    /**
     * Main array of all CSS properties & values
     *
     * @var array
     */
    protected $_props = [];

    /* var instead of protected would allow access outside of class */
    protected $_important_props = [];

    /**
     * The computed values of the CSS property
     *
     * @var array
     */
    protected $_props_computed = [];

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
            "padding_top",
            "padding_right",
            "padding_bottom",
            "padding_left"
        ]
    ];

    /**
     * The used values of the CSS property
     *
     * @var array
     */
    protected $_prop_cache = [];

    /**
     * Font size of parent element in document tree.  Used for relative font
     * size resolution.
     *
     * @var float
     */
    protected $_parent_font_size;

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
     * The computed border radius
     */
    private $_computed_border_radius = null;

    /**
     * @var bool
     */
    public $_has_border_radius = false;

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

        $this->_props = [];
        $this->_important_props = [];
        $this->_stylesheet = $stylesheet;
        $this->_media_queries = [];
        $this->_origin = $origin;
        $this->_parent_font_size = null;

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
            $d["border_top_color"] = "";
            $d["border_right_color"] = "";
            $d["border_bottom_color"] = "";
            $d["border_left_color"] = "";
            $d["border_top_style"] = "none";
            $d["border_right_style"] = "none";
            $d["border_bottom_style"] = "none";
            $d["border_left_style"] = "none";
            $d["border_top_width"] = "medium";
            $d["border_right_width"] = "medium";
            $d["border_bottom_width"] = "medium";
            $d["border_left_width"] = "medium";
            $d["border_width"] = "medium";
            $d["border_bottom_left_radius"] = "";
            $d["border_bottom_right_radius"] = "";
            $d["border_top_left_radius"] = "";
            $d["border_top_right_radius"] = "";
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
            $d["min_height"] = "0";
            $d["min_width"] = "0";
            $d["orphans"] = "2";
            $d["outline_color"] = ""; // "invert" special color is not supported
            $d["outline_style"] = "none";
            $d["outline_width"] = "medium";
            $d["outline"] = "";
            $d["overflow"] = "visible";
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
            $d["quotes"] = "";
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
            $d["word_wrap"] = "normal";
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

            // vendor-previxed properties
            $d["_dompdf_background_image_resolution"] = &$d["background_image_resolution"];
            $d["_dompdf_image_resolution"] = &$d["image_resolution"];
            $d["_dompdf_keep"] = "";
            $d["_webkit_transform"] = &$d["transform"];
            $d["_webkit_transform_origin"] = &$d["transform_origin"];

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
                "page_break_inside",
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
                "word_wrap",
                "widows",
                "word_spacing",
            ];
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

    /**
     * Converts any CSS length value into an absolute length in points.
     *
     * length_in_pt() takes a single length (e.g. '1em') or an array of
     * lengths and returns an absolute length.  If an array is passed, then
     * the return value is the sum of all elements. If any of the lengths
     * provided are "auto" or "none" then that value is returned.
     *
     * If a reference size is not provided, the default font size is used
     * ({@link Style::$default_font_size}).
     *
     * @param float|string|array $length the numeric length (or string measurement) or array of lengths to resolve
     * @param float $ref_size an absolute reference size to resolve percentage lengths
     * @return float|string
     */
    function length_in_pt($length, $ref_size = null)
    {
        static $cache = [];

        if (!isset($ref_size)) {
            $ref_size = $this->__get("font_size");
        }

        if (!is_array($length)) {
            $key = $length . "/$ref_size";
            //Early check on cache, before converting $length to array
            if (isset($cache[$key])) {
                return $cache[$key];
            }
            $length = [$length];
        } else {
            $key = implode("@", $length) . "/$ref_size";
            if (isset($cache[$key])) {
                return $cache[$key];
            }
        }

        $ret = 0;
        foreach ($length as $l) {

            if ($l === "auto") {
                return "auto";
            }

            if ($l === "none") {
                return "none";
            }

            // Assume numeric values are already in points
            if (is_numeric($l)) {
                $ret += $l;
                continue;
            }

            if ($l === "normal") {
                $ret += (float)$ref_size;
                continue;
            }

            // Border lengths
            if ($l === "thin") {
                $ret += 0.5;
                continue;
            }

            if ($l === "medium") {
                $ret += 1.5;
                continue;
            }

            if ($l === "thick") {
                $ret += 2.5;
                continue;
            }

            if (($i = mb_stripos($l, "px")) !== false) {
                $dpi = $this->_stylesheet->get_dompdf()->getOptions()->getDpi();
                $ret += ((float)mb_substr($l, 0, $i) * 72) / $dpi;
                continue;
            }

            if (($i = mb_stripos($l, "pt")) !== false) {
                $ret += (float)mb_substr($l, 0, $i);
                continue;
            }

            if (($i = mb_stripos($l, "%")) !== false) {
                $ret += (float)mb_substr($l, 0, $i) / 100 * (float)$ref_size;
                continue;
            }

            if (($i = mb_stripos($l, "rem")) !== false) {
                if ($this->_stylesheet->get_dompdf()->getTree()->get_root()->get_style() === null) {
                    // Interpreting it as "em", see https://github.com/dompdf/dompdf/issues/1406
                    $ret += (float)mb_substr($l, 0, $i) * $this->__get("font_size");
                } else {
                    $ret += (float)mb_substr($l, 0, $i) * $this->_stylesheet->get_dompdf()->getTree()->get_root()->get_style()->font_size;
                }
                continue;
            }

            if (($i = mb_stripos($l, "em")) !== false) {
                $ret += (float)mb_substr($l, 0, $i) * $this->__get("font_size");
                continue;
            }

            if (($i = mb_stripos($l, "cm")) !== false) {
                $ret += (float)mb_substr($l, 0, $i) * 72 / 2.54;
                continue;
            }

            if (($i = mb_stripos($l, "mm")) !== false) {
                $ret += (float)mb_substr($l, 0, $i) * 72 / 25.4;
                continue;
            }

            // FIXME: em:ex ratio?
            if (($i = mb_stripos($l, "ex")) !== false) {
                $ret += (float)mb_substr($l, 0, $i) * $this->__get("font_size") / 2;
                continue;
            }

            if (($i = mb_stripos($l, "in")) !== false) {
                $ret += (float)mb_substr($l, 0, $i) * 72;
                continue;
            }

            if (($i = mb_stripos($l, "pc")) !== false) {
                $ret += (float)mb_substr($l, 0, $i) * 12;
                continue;
            }

            // Bogus value
            $ret += (float)$ref_size;
        }

        return $cache[$key] = $ret;
    }


    /**
     * Set inherited properties in this style using values in $parent
     *
     * @param Style $parent
     *
     * @return Style
     */
    function inherit(Style $parent)
    {
        // Set parent font size, changes affect font size of the element
        if ($this->_parent_font_size !== $parent->font_size) {
            $this->_parent_font_size = $parent->font_size;
            if (isset($this->_props["font_size"])) {
                $this->__set("font_size", $this->_props["font_size"]);
            }
        }

        foreach (self::$_inherited as $prop) {
            // don't inherit shorthand properties, the specific properties will inherit
            if (in_array($prop, self::$_props_shorthand) === true) {
                continue;
            }

            //inherit the !important property also.
            //if local property is also !important, don't inherit.

            if (isset($parent->_props_computed[$prop]) &&
                (
                    !isset($this->_props[$prop])
                    || (isset($parent->_important_props[$prop]) && !isset($this->_important_props[$prop]))
                )
            ) {
                if (isset($parent->_important_props[$prop])) {
                    $this->_important_props[$prop] = true;
                }
                if (isset($parent->_props_computed[$prop])) {
                    $this->__set($prop, $parent->_props_computed[$prop]);
                } else {
                    // parent prop not set, use the default
                    $this->__set($prop, self::$_defaults[$prop]);
                }
            }
        }

        foreach ($this->_props as $prop => $value) {
            // don't inherit shorthand properties, the specific properties will inherit
            if (in_array($prop, self::$_props_shorthand) === true) {
                continue;
            }
            if ($value === "inherit") {
                if (isset($parent->_important_props[$prop])) {
                    $this->_important_props[$prop] = true;
                }
                //do not assign direct, but
                //implicite assignment through __set, redirect to specialized, get value with __get
                //This is for computing defaults if the parent setting is also missing.
                //Therefore do not directly assign the value without __set
                //set _important_props before that to be able to propagate.
                //see __set and __get, on all assignments clear cache!
                //$this->_prop_cache[$prop] = null;
                //$this->_props[$prop] = $parent->_props[$prop];
                //props_set for more obvious explicite assignment not implemented, because
                //too many implicite uses.
                // $this->props_set($prop, $parent->$prop);
                if (isset($parent->_props_computed[$prop])) {
                    $this->__set($prop, $parent->_props_computed[$prop]);
                } else {
                    // parent prop not set, use the default
                    $this->__set($prop, self::$_defaults[$prop]);
                }
                // set the specified prop back to "inherit"
                $this->_props[$prop] = "inherit";
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
        //treat the !important attribute
        //if old rule has !important attribute, override with new rule only if
        //the new rule is also !important
        foreach ($style->_props as $prop => $val) {
            $can_merge = false;
            if (isset($style->_important_props[$prop])) {
                $this->_important_props[$prop] = true;
                $can_merge = true;
            } else if (isset($val) && !isset($this->_important_props[$prop])) {
                $can_merge = true;
            }

            if ($can_merge) {
                // Clear out "inherit" shorthand properties if a more specific property value has been set
                $shorthands = array_filter(self::$_props_shorthand, function ($el) use ($prop) {
                    return (strpos($prop, $el . "_") !== false);
                });
                foreach ($shorthands as $shorthand) {
                    if (array_key_exists($shorthand, $this->_props) && $this->_props[$shorthand] === "inherit") {
                        unset($this->_props[$shorthand]);
                        unset($this->_props_computed[$shorthand]);
                        unset($this->_prop_cache[$shorthand]);
                    }
                }
                $this->__set($prop, $val);
            }
        }
    }

    /**
     * Returns an array(r, g, b, "r"=> r, "g"=>g, "b"=>b, "hex"=>"#rrggbb")
     * based on the provided CSS color value.
     *
     * @param string $color
     * @return array
     */
    function munge_color($color)
    {
        return Color::parse($color);
    }

    /* direct access to _important_props array from outside would work only when declared as
     * 'var $_important_props;' instead of 'protected $_important_props;'
     * Don't call _set/__get on missing attribute. Therefore need a special access.
     * Assume that __set will be also called when this is called, so do not check validity again.
     * Only created, if !important exists -> always set true.
     */
    function important_set($prop)
    {
        $prop = str_replace("-", "_", $prop);
        $this->_important_props[$prop] = true;
    }

    /**
     * @param $prop
     * @return bool
     */
    function important_get($prop)
    {
        return isset($this->_important_props[$prop]);
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
        $prop = str_replace("-", "_", $prop);

        if (!isset(self::$_defaults[$prop])) {
            global $_dompdf_warnings;
            $_dompdf_warnings[] = "'$prop' is not a recognized CSS property.";
            return;
        }

        if ($prop !== "content" && is_string($val) && strlen($val) > 5 && mb_strpos($val, "url") === false) {
            $val = mb_strtolower(trim(str_replace(["\n", "\t"], [" "], $val)));
            $val = preg_replace("/([0-9]+) (pt|px|pc|em|ex|in|cm|mm|%)/S", "\\1\\2", $val);
        }

        $this->_props[$prop] = $val;
        $this->_props_computed[$prop] = null;
        $this->_prop_cache[$prop] = null;

        $method = "set_$prop";

        if (!isset(self::$_methods_cache[$method])) {
            self::$_methods_cache[$method] = method_exists($this, $method);
        }

        if (self::$_methods_cache[$method]) {
            $this->$method($val);
        }
        if (isset($this->_props_computed[$prop]) === false && isset($val) && $val !== '' && $val !== 'inherit') {
            $this->_props_computed[$prop] = $val;
        }

        if (isset($this->_props_computed[$prop])) {
            //FIXME: need to catch for circular dependencies because oops
            if (array_key_exists($prop, self::$_dependency_map)) {
                foreach (self::$_dependency_map[$prop] as $dependent) {
                    if (isset($this->_props[$dependent]) === true) {
                        $this->__set($dependent, $this->_props[$dependent]);
                    }
                }
            }
        }
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
        //FIXME: need to get shorthand from component properties
        if (!isset(self::$_defaults[$prop])) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        if (isset($this->_prop_cache[$prop])) {
            return $this->_prop_cache[$prop];
        }

        $method = "get_$prop";

        $retval = null;

        // Preview the value based on the default if the property is not cached 
        // and the computed value has not yet been set.
        $reset_value = false;
        $specified_value = null;
        $computed_value = null;
        if (!isset($this->_prop_cache[$prop]) && !isset($this->_props_computed[$prop])) {
            $reset_value = true;
            if (isset($this->_props[$prop])) {
                $specified_value = $this->_props[$prop];
            }
            if (isset($this->_props_computed[$prop])) {
                $computed_value = $this->_props_computed[$prop];
            }
            if (empty($this->_props[$prop]) || $this->_props[$prop] === "inherit") {
                $this->__set($prop, self::$_defaults[$prop]);
            }
            if (empty($this->_props_computed[$prop])) {
                // computed value should be set if the property is set, we'll recalculate it
                $this->__set($prop, $this->_props[$prop]);
            }
        }

        if (!isset(self::$_methods_cache[$method])) {
            self::$_methods_cache[$method] = method_exists($this, $method);
        }

        if (self::$_methods_cache[$method]) {
            $retval = $this->_prop_cache[$prop] = $this->$method();
        }

        if (!isset($retval)) {
            $retval = $this->_prop_cache[$prop] = $this->_props_computed[$prop];
        }

        // When previewing the value reset the specified and computed properties
        // so that we don't interfere with inheritance.
        if ($reset_value) {
            $this->_props[$prop] = $specified_value;
            $this->_props_computed[$prop] = $computed_value;
        }

        return $retval;
    }

    /**
     * Sets the property value without calculating the computed value
     *
     * @param $prop
     * @param $val
     */
    function set_prop($prop, $val)
    {
        $prop = str_replace("-", "_", $prop);

        if (!isset(self::$_defaults[$prop])) {
            global $_dompdf_warnings;
            $_dompdf_warnings[] = "'$prop' is not a recognized CSS property.";
            return;
        }

        if ($prop !== "content" && is_string($val) && strlen($val) > 5 && mb_strpos($val, "url") === false) {
            $val = mb_strtolower(trim(str_replace(["\n", "\t"], [" "], $val)));
            $val = preg_replace("/([0-9]+) (pt|px|pc|em|ex|in|cm|mm|%)/S", "\\1\\2", $val);
        }

        $this->_props[$prop] = $val;
        $this->_props_computed[$prop] = null;
        $this->_prop_cache[$prop] = null;

        //FIXME: this doesn't work for shorthand properties
    }

    /**
     * Similar to __get() without storing the result. Useful for accessing
     * properties while loading stylesheets.
     *
     * @param $prop
     * @return string
     * @throws Exception
     */
    function get_prop($prop)
    {
        if (!isset(self::$_defaults[$prop])) {
            throw new Exception("'$prop' is not a recognized CSS property.");
        }

        $method = "get_$prop";

        // Fall back on defaults if property is not set
        if (!isset($this->_props_computed[$prop])) {
            return self::$_defaults[$prop];
        }

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return $this->_props[$prop];
    }

    /**
     * Calculates the computed value of the CSS properties that have been set (the specified properties)
     */
    function compute_props()
    {
        foreach ($this->_props as $prop => $val) {
            if (in_array($prop, self::$_props_shorthand) === false) {
                $this->__set($prop, $val);
            }
        }
    }

    /**
     * @return float|null|string
     */
    function computed_bottom_spacing()
    {
        if ($this->_computed_bottom_spacing !== null) {
            return $this->_computed_bottom_spacing;
        }
        return $this->_computed_bottom_spacing = $this->length_in_pt(
            [
                $this->margin_bottom,
                $this->padding_bottom,
                $this->border_bottom_width
            ]
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
        return $this->munge_color($this->_props_computed["color"]);
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
        return $this->munge_color($this->_props_computed["background_color"]);
    }

    /**
     * Returns the background image URI, or "none"
     * 
     * @link https://www.w3.org/TR/CSS21/colors.html#propdef-background-image
     * @return string
     */
    function get_background_image()
    {
        return $this->_image($this->_props_computed["background_image"]);
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
        if (strpos($this->_props_computed["background_position"], " ") === false) {
            $this->__set("background_position", $this->_props["background_position"]);
        }
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

        if (strpos($this->_props_computed["background_size"], " ") === false) {
            $this->__set("background_size", $this->_props["background_size"]);
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
        return $this->munge_color($this->_props_computed["border_top_color"]);
    }

    /**
     * @return array
     */
    function get_border_right_color()
    {
        return $this->munge_color($this->_props_computed["border_right_color"]);
    }

    /**
     * @return array
     */
    function get_border_bottom_color()
    {
        return $this->munge_color($this->_props_computed["border_bottom_color"]);
    }

    /**
     * @return array
     */
    function get_border_left_color()
    {
        return $this->munge_color($this->_props_computed["border_left_color"]);
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
            $this->__get("border_" . $side . "_style") . " " . $color["hex"];
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

    private function _get_width($prop)
    {
        //TODO: should be handled in setter
        if (strpos($this->_props_computed[$prop], "%") !== false) {
            // calculate against width of containing block, needs to be done outside the style class
            return $this->_props_computed[$prop];
        }
        return $this->length_in_pt($this->_props_computed[$prop], $this->__get("font_size"));
    }

    function get_margin_top()
    {
        return $this->_get_width("margin_top");
    }

    function get_margin_right()
    {
        return $this->_get_width("margin_right");
    }

    function get_margin_bottom()
    {
        return $this->_get_width("margin_bottom");
    }

    function get_margin_left()
    {
        return $this->_get_width("margin_left");
    }

    function get_padding_top()
    {
        return $this->_get_width("padding_top");
    }

    function get_padding_right()
    {
        return $this->_get_width("padding_right");
    }

    function get_padding_bottom()
    {
        return $this->_get_width("padding_bottom");
    }

    function get_padding_left()
    {
        return $this->_get_width("padding_left");
    }

    /**
     * @param $w
     * @param $h
     * @return array|null
     */
    function get_computed_border_radius($w, $h)
    {
        if (!empty($this->_computed_border_radius)) {
            return $this->_computed_border_radius;
        }

        $w = (float)$w;
        $h = (float)$h;
        $rTL = (float)$this->__get("border_top_left_radius");
        $rTR = (float)$this->__get("border_top_right_radius");
        $rBL = (float)$this->__get("border_bottom_left_radius");
        $rBR = (float)$this->__get("border_bottom_right_radius");

        if ($rTL + $rTR + $rBL + $rBR == 0) {
            return $this->_computed_border_radius = [
                0, 0, 0, 0,
                "top-left" => 0,
                "top-right" => 0,
                "bottom-right" => 0,
                "bottom-left" => 0,
            ];
        }

        $t = (float)$this->__get("border_top_width");
        $r = (float)$this->__get("border_right_width");
        $b = (float)$this->__get("border_bottom_width");
        $l = (float)$this->__get("border_left_width");

        $rTL = min($rTL, $h - $rBL - $t / 2 - $b / 2, $w - $rTR - $l / 2 - $r / 2);
        $rTR = min($rTR, $h - $rBR - $t / 2 - $b / 2, $w - $rTL - $l / 2 - $r / 2);
        $rBL = min($rBL, $h - $rTL - $t / 2 - $b / 2, $w - $rBR - $l / 2 - $r / 2);
        $rBR = min($rBR, $h - $rTR - $t / 2 - $b / 2, $w - $rBL - $l / 2 - $r / 2);

        return $this->_computed_border_radius = [
            $rTL, $rTR, $rBR, $rBL,
            "top-left" => $rTL,
            "top-right" => $rTR,
            "bottom-right" => $rBR,
            "bottom-left" => $rBL,
        ];
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
        return $this->munge_color($this->_props_computed["outline_color"]);
    }

    /**#@+
     * Returns the outline width, as it is currently stored
     * @return float|string
     */
    function get_outline_width()
    {
        $style = $this->__get("outline_style");
        return $style !== "none" && $style !== "hidden" ? $this->length_in_pt($this->_props_computed["outline_width"]) : 0;
    }

    /**#@+
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
        return
            $this->__get("outline_width") . " " .
            $this->__get("outline_style") . " " .
            $color["hex"];
    }
    /**#@-*/

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
        return $this->_image($this->_props_computed["list_style_image"]);
    }

    /**
     * @param $val
     */
    function get_counter_increment()
    {
        $val = trim($this->_props_computed["counter_increment"]);
        $value = null;

        if (in_array($val, ["none", "inherit"])) {
            $value = $val;
        } else {
            if (preg_match_all("/(" . self::CSS_IDENTIFIER . ")(?:\s+(" . self::CSS_INTEGER . "))?/", $val, $matches, PREG_SET_ORDER)) {
                $value = [];
                foreach ($matches as $match) {
                    $value[$match[1]] = isset($match[2]) ? $match[2] : 1;
                }
            }
        }
        return $value;
    }


    /*==============================*/

    /*
     !important attribute
     For basic functionality of the !important attribute with overloading
     of several styles of an element, changes in inherit(), merge() and _parse_properties()
     are sufficient [helpers var $_important_props, __construct(), important_set(), important_get()]

     Only for combined attributes extra treatment needed. See below.

     div { border: 1px red; }
     div { border: solid; } // Not combined! Only one occurrence of same style per context
     //
     div { border: 1px red; }
     div a { border: solid; } // Adding to border style ok by inheritance
     //
     div { border-style: solid; } // Adding to border style ok because of different styles
     div { border: 1px red; }
     //
     div { border-style: solid; !important} // border: overrides, even though not !important
     div { border: 1px dashed red; }
     //
     div { border: 1px red; !important }
     div a { border-style: solid; } // Need to override because not set

     Special treatment:
     At individual property like border-top-width need to check whether overriding value is also !important.
     Also store the !important condition for later overrides.
     Since not known who is initiating the override, need to get passed !important as parameter.
     !important Parameter taken as in the original style in the css file.
     When property border !important given, do not mark subsets like border_style as important. Only
     individual properties.

     Note:
     Setting individual property directly from css with e.g. set_border_top_style() is not needed, because
     missing set functions handled by a generic handler __set(), including the !important.
     Setting individual property of as sub-property is handled below.

     Implementation see at _set_style_side_type()
     Callers _set_style_sides_type(), _set_style_type, _set_style_type_important()

     Related functionality for background, padding, margin, font, list_style
    */

    /**
     * Generalized set function for individual attribute of combined style.
     * With check for !important
     * Applicable for background, border, padding, margin, font, list_style
     *
     * Note: $type has a leading underscore (or is empty), the others not.
     *
     * @param $style
     * @param $side
     * @param $type
     * @param $val
     * @param $important
     */
    protected function _set_style_side_type($style, $side, $type, $val, $important)
    {
        $prop = $style;
        if (!empty($side)) {
            $prop .= "_" . $side;
        };
        if (!empty($type)) {
            $prop .= "_" . $type;
        };
        $this->_props[$prop] = $val;
        $this->_prop_cache[$prop] = null;

        if ($val === "inherit") {
            $this->_props_computed[$prop] = null;
            return;
        }

        if (!isset($this->_important_props[$prop]) || $important) {
            $val_computed = (float)$this->length_in_pt($val);
            if ($side === "bottom") {
                $this->_computed_bottom_spacing = null; //reset computed cache, border style can disable/enable border calculations
            }
            if ($important) {
                $this->_important_props[$prop] = true;
            }

            if ($val_computed < 0 && ($style === "border" || $style === "padding" || $style === "outline")) {
                $this->_props[$prop] = null; // passed-in value is invalid
            } else if (
                (($style === "border" || $style === "outline") && $type === "width" && strpos($val, "%") !== false)
                ||
                (($style === "margin" || $style === "padding") && (strpos($val, "%") !== false || $val === "auto"))
            ) {
                $this->_props_computed[$prop] = $val;
            } elseif (($style === "border" || $style === "outline") && $type === "width" && strpos($val, "%") === false) {
                $line_style_prop = $style;
                if (!empty($side)) {
                    $line_style_prop .= "_" . $side;
                };
                $line_style_prop .= "_style";
                $line_style = $this->__get($line_style_prop);
                $this->_props_computed[$prop] = ($line_style !== "none" && $line_style !== "hidden" ? $val_computed : 0);
            } elseif (($style === "margin" || $style === "padding")) {
                $this->_props_computed[$prop] = ($val !== "none" && $val !== "hidden" ? $val_computed : 0);
            } elseif ($type === "color") {
                $this->set_prop_color($prop, $val);
            } elseif (!empty($val)) {
                $this->_props_computed[$prop] = $val;
            }
        }
    }

    /**
     * @param $style
     * @param $top
     * @param $right
     * @param $bottom
     * @param $left
     * @param $type
     * @param $important
     */
    protected function _set_style_sides_type($style, $top, $right, $bottom, $left, $type, $important)
    {
        $this->_set_style_side_type($style, 'top', $type, $top, $important);
        $this->_set_style_side_type($style, 'right', $type, $right, $important);
        $this->_set_style_side_type($style, 'bottom', $type, $bottom, $important);
        $this->_set_style_side_type($style, 'left', $type, $left, $important);
    }

    /**
     * @param $style
     * @param $type
     * @param $val
     * @param $important
     */
    protected function _set_style_type($style, $type, $val, $important)
    {
        $val = preg_replace("/\s*\,\s*/", ",", $val); // when rgb() has spaces
        $arr = explode(" ", $val);

        switch (count($arr)) {
            case 1:
                $this->_set_style_sides_type($style, $arr[0], $arr[0], $arr[0], $arr[0], $type, $important);
                break;
            case 2:
                $this->_set_style_sides_type($style, $arr[0], $arr[1], $arr[0], $arr[1], $type, $important);
                break;
            case 3:
                $this->_set_style_sides_type($style, $arr[0], $arr[1], $arr[2], $arr[1], $type, $important);
                break;
            case 4:
                $this->_set_style_sides_type($style, $arr[0], $arr[1], $arr[2], $arr[3], $type, $important);
                break;
        }
    }

    /**
     * @param $style
     * @param $type
     * @param $val
     */
    protected function _set_style_type_important($style, $type, $val)
    {
        $this->_set_style_type($style, $type, $val, isset($this->_important_props[$style . $type]));
    }

    /**
     * Anyway only called if _important matches and is assigned
     * E.g. _set_style_side_type($style,$side,'',str_replace("none", "0px", $val),isset($this->_important_props[$style.'_'.$side]));
     *
     * @param $style
     * @param $side
     * @param $val
     */
    protected function _set_style_side_width_important($style, $side, $val)
    {
        $this->_set_style_side_type($style, $side, "", $val, isset($this->_important_props[$style . $side]));
    }

    /**
     * @param $style
     * @param $val
     * @param $important
     */
    protected function _set_style($style, $val, $important)
    {
        if (!isset($this->_important_props[$style]) || $important) {
            if ($important) {
                $this->_important_props[$style] = true;
            }
            $this->__set($style, $val);
        }
    }

    /**
     * @param $val
     * @return string
     */
    protected function _image($val)
    {
        $DEBUGCSS = $this->_stylesheet->get_dompdf()->getOptions()->getDebugCss();
        $parsed_url = "none";

        if (empty($val) || $val === "none") {
            $path = "none";
        } else if (mb_strpos($val, "url") === false) {
            $path = "none"; //Don't resolve no image -> otherwise would prefix path and no longer recognize as none
        } else {
            $val = preg_replace("/url\(\s*['\"]?([^'\")]+)['\"]?\s*\)/", "\\1", trim($val));

            // Resolve the url now in the context of the current stylesheet
            $parsed_url = Helpers::explode_url($val);
            $path = Helpers::build_url($this->_stylesheet->get_protocol(),
                $this->_stylesheet->get_host(),
                $this->_stylesheet->get_base_path(),
                $val);
            if ($parsed_url["protocol"] == "" && $this->_stylesheet->get_protocol() == "") {
                $path = realpath($path);
                // If realpath returns FALSE then specifically state that there is no background image
                if (!$path) {
                    $path = 'none';
                }
            }
        }
        if ($DEBUGCSS) {
            print "<pre>[_image\n";
            print_r($parsed_url);
            print $this->_stylesheet->get_protocol() . "\n" . $this->_stylesheet->get_base_path() . "\n" . $path . "\n";
            print "_image]</pre>";;
        }
        return $path;
    }

    /*======================*/

    protected function set_prop_color($prop, $color)
    {
        $munged_color = $this->munge_color($color);

        if (is_null($munged_color)) {
            return;
        }

        $this->_props[$prop] = $color;
        $this->_props_computed[$prop] = null;
        $this->_prop_cache[$prop] = null;

        $this->_props_computed[$prop] = (is_array($munged_color) ? $munged_color["hex"] : $munged_color);
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
        $this->_props["background_image"] = $val;
        $parsed_val = $this->_image($val);
        if ($parsed_val === "none") {
            $this->_props_computed["list_style_image"] = "none";
        } else {
            $this->_props_computed["list_style_image"] = "url(" . $parsed_val . ")";
        }
        $this->_prop_cache["background_image"] = null;
    }

    /**
     * Sets the background repeat
     *
     * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-repeat
     * @param string $val
     */
    function set_background_repeat($val)
    {
        $this->_props["background_repeat"] = $val;
        $this->_props_computed["background_repeat"] = null;
        $this->_prop_cache["background_repeat"] = null;

        if ($val === 'inherit') {
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
        $this->_props["background_attachment"] = $val;
        $this->_props_computed["background_attachment"] = null;
        $this->_prop_cache["background_attachment"] = null;
        
        if ($val === 'inherit') {
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
        $this->_props["background_position"] = $val;

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
        $this->_prop_cache["background_position"] = null;
    }

    /**
     * Sets the background size
     *
     * @link https://www.w3.org/TR/css3-background/#background-size
     * @param string $val
     */
    function set_background_size($val)
    {
        $this->_props["background_size"] = $val;
        $this->_prop_cache["background_size"] = null;

        $result = explode(" ", $val);
        $width = $result[0];

        switch ($width) {
            case "cover":
            case "contain":
            case "inherit":
                $this->_props_computed["background_size"] = $width;
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
     * @param string $val
     */
    function set_background($val)
    {
        $val = trim($val);
        $important = isset($this->_important_props["background"]);

        if ($val === "none") {
            $this->_set_style("background_image", "none", $important);
            $this->_set_style("background_color", "transparent", $important);
        } else {
            $pos = [];
            $tmp = preg_replace("/\s*\,\s*/", ",", $val); // when rgb() has spaces
            $tmp = preg_split("/\s+/", $tmp);

            foreach ($tmp as $attr) {
                if (mb_substr($attr, 0, 3) === "url" || $attr === "none") {
                    $this->_set_style("background_image", $attr, $important);
                } elseif ($attr === "fixed" || $attr === "scroll") {
                    $this->_set_style("background_attachment", $attr, $important);
                } elseif ($attr === "repeat" || $attr === "repeat-x" || $attr === "repeat-y" || $attr === "no-repeat") {
                    $this->_set_style("background_repeat", $attr, $important);
                } elseif (($col = $this->munge_color($attr)) != null) {
                    $this->_set_style("background_color", is_array($col) ? $col["hex"] : $col, $important);
                } else {
                    $pos[] = $attr;
                }
            }

            if (count($pos)) {
                $this->_set_style("background_position", implode(" ", $pos), $important);
            }
        }

        //see __set and __get, on all assignments clear cache, not needed on direct set through __set
        $this->_props["background"] = $val;
        $this->_props_computed["background"] = null;
        $this->_prop_cache["background"] = null;
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
        $this->_props["font_size"] = $size;
        $this->_props_computed["font_size"] = null;
        $this->_prop_cache["font_size"] = null;

        if ($size === "inherit") {
            return;
        }
        if (!isset($this->_parent_font_size)) {
            $this->_parent_font_size = self::$default_font_size;
        }

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
                $fs = 8 / 9 * $this->_parent_font_size;
                break;

            case "larger":
                $fs = 6 / 5 * $this->_parent_font_size;
                break;

            default:
                $fs = $size;
                break;
        }

        // length_in_pt uses the font size if units are em or ex (and, potentially, rem) so we'll calculate in the method
        if (($i = mb_strpos($fs, "rem")) !== false) {
            if ($this->_stylesheet->get_dompdf()->getTree()->get_root()->get_style() === null) {
                // Interpreting it as "em", see https://github.com/dompdf/dompdf/issues/1406
                $fs = (float)mb_substr($fs, 0, $i) * $this->_parent_font_size;
            } else {
                $fs = (float)mb_substr($fs, 0, $i) * $this->_stylesheet->get_dompdf()->getTree()->get_root()->get_style()->font_size;
            }
        } elseif (($i = mb_strpos($fs, "em")) !== false) {
            $fs = (float)mb_substr($fs, 0, $i) * $this->_parent_font_size;
        } elseif (($i = mb_strpos($fs, "ex")) !== false) {
            $fs = (float)mb_substr($fs, 0, $i) * $this->_parent_font_size / 2;
        } else {
            //FIXME: prefer just calling length_in_pt, when we provide a ref size to length_in_pt should em and ex use that instead of the current font size?
            $fs = (float)$this->length_in_pt($fs, $this->_parent_font_size);
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
        $this->_props["font_weight"] = $weight;
        $this->_props_computed["font_weight"] = null;
        $this->_prop_cache["font_weight"] = null;

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
     * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style
     * @param $val
     */
    function set_font($val)
    {
        //see __set and __get, on all assignments clear cache, not needed on direct set through __set
        $this->_prop_cache["font"] = null;
        $this->_props["font"] = $val;
        $this->_props_computed["font"] = null;

        $important = isset($this->_important_props["font"]);

        if (strtolower($val) === "inherit") {
            $this->_set_style("font_family", "inherit", $important);
            $this->_set_style("font_size", "inherit", $important);
            $this->_set_style("font_style", "inherit", $important);
            $this->_set_style("font_variant", "inherit", $important);
            $this->_set_style("font_weight", "inherit", $important);
            $this->_set_style("line_height", "inherit", $important);
            return;
        }

        if (preg_match("/^(italic|oblique|normal)\s*(.*)$/i", $val, $match)) {
            $this->_set_style("font_style", $match[1], $important);
            $val = $match[2];
        }

        if (preg_match("/^(small-caps|normal)\s*(.*)$/i", $val, $match)) {
            $this->_set_style("font_variant", $match[1], $important);
            $val = $match[2];
        }

        //matching numeric value followed by unit -> this is indeed a subsequent font size. Skip!
        if (preg_match("/^(bold|bolder|lighter|100|200|300|400|500|600|700|800|900|normal)\s*(.*)$/i", $val, $match) &&
            !preg_match("/^(?:pt|px|pc|em|ex|in|cm|mm|%)/", $match[2])
        ) {
            $this->_set_style("font_weight", $match[1], $important);
            $val = $match[2];
        }

        if (preg_match("/^(xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger|\d+\s*(?:pt|px|pc|em|ex|in|cm|mm|%))(?:\/|\s*)(.*)$/i", $val, $match)) {
            $this->_set_style("font_size", $match[1], $important);
            $val = $match[2];
            if (preg_match("/^(?:\/|\s*)(\d+\s*(?:pt|px|pc|em|ex|in|cm|mm|%)?)\s*(.*)$/i", $val, $match)) {
                $this->_set_style("line_height", $match[1], $important);
                $val = $match[2];
            }
        }

        if (strlen($val) != 0) {
            $this->_set_style("font_family", $val, $important);
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
        $alignment = "";
        if (in_array($val, self::$text_align_keywords)) {
            $alignment = $val;
        }
        if ($alignment === "") {
            $alignment = "left";
            if ($this->__get("direction") === "rtl") {
                $alignment = "right";
            }

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
        $this->_props["word_spacing"] = $val;
        $this->_props_computed["word_spacing"] = null;
        $this->_prop_cache["word_spacing"] = null;

        if ($val === 'inherit') {
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
        $this->_props["letter_spacing"] = $val;
        $this->_props_computed["letter_spacing"] = null;
        $this->_prop_cache["letter_spacing"] = null;

        if ($val === 'inherit') {
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
        $this->_props["line_height"] = $val;
        $this->_props_computed["line_height"] = null;
        $this->_prop_cache["line_height"] = null;

        if ($val === 'inherit') {
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
        $this->_props["page_break_before"] = $break;
        $this->_props_computed["page_break_before"] = null;
        $this->_prop_cache["page_break_before"] = null;

        if ($break === 'inherit') {
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
        $this->_props["page_break_after"] = $break;
        $this->_props_computed["page_break_after"] = null;
        $this->_prop_cache["page_break_after"] = null;

        if ($break === 'inherit') {
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
        $this->_set_style_side_width_important('margin', 'top', $val);
    }

    /**
     * @param $val
     */
    function set_margin_right($val)
    {
        $this->_set_style_side_width_important('margin', 'right', $val);
    }

    /**
     * @param $val
     */
    function set_margin_bottom($val)
    {
        $this->_set_style_side_width_important('margin', 'bottom', $val);
    }

    /**
     * @param $val
     */
    function set_margin_left($val)
    {
        $this->_set_style_side_width_important('margin', 'left', $val);
    }

    /**
     * @param $val
     */
    function set_margin($val)
    {
        $this->_set_style_type_important('margin', '', $val);
    }

    /**
     * Sets the padding size
     *
     * @link http://www.w3.org/TR/CSS21/box.html#padding-properties
     * @param $val
     */
    function set_padding_top($val)
    {
        $this->_set_style_side_width_important('padding', 'top', $val);
    }

    /**
     * @param $val
     */
    function set_padding_right($val)
    {
        $this->_set_style_side_width_important('padding', 'right', $val);
    }

    /**
     * @param $val
     */
    function set_padding_bottom($val)
    {
        $this->_set_style_side_width_important('padding', 'bottom', $val);
    }

    /**
     * @param $val
     */
    function set_padding_left($val)
    {
        $this->_set_style_side_width_important('padding', 'left', $val);
    }

    /**
     * @param $val
     */
    function set_padding($val)
    {
        $this->_set_style_type_important('padding', '', $val);
    }
    /**#@-*/

    /**
     * Sets a single border
     *
     * @param string $side
     * @param string $border_spec ([width] [style] [color])
     * @param boolean $important
     */
    protected function _set_border($side, $border_spec, $important)
    {
        $border_spec = preg_replace("/\s*\,\s*/", ",", $border_spec);
        //$border_spec = str_replace(",", " ", $border_spec); // Why did we have this ?? rbg(10, 102, 10) > rgb(10  102  10)
        $arr = explode(" ", $border_spec);

        // FIXME: handle partial values
        //For consistency of individual and combined properties, and with ie8 and firefox3
        //reset all attributes, even if only partially given
        //$this->_set_style_side_type('border', $side, 'style', self::$_defaults['border_' . $side . '_style'], $important);
        //$this->_set_style_side_type('border', $side, 'width', self::$_defaults['border_' . $side . '_width'], $important);
        //$this->_set_style_side_type('border', $side, 'color', self::$_defaults['border_' . $side . '_color'], $important);

        foreach ($arr as $value) {
            $value = trim($value);
            if (in_array($value, self::$BORDER_STYLES)) {
                $this->_set_style_side_type('border', $side, 'style', $value, $important);
            } elseif ($value === "0" || preg_match("/[.0-9]+(?:px|pt|pc|em|ex|%|in|mm|cm)|(?:thin|medium|thick)/", $value)) {
                $this->_set_style_side_type('border', $side, 'width', $value, $important);
            } elseif ($value === "inherit") {
                $this->_set_style_side_type('border', $side, 'style', $value, $important);
                $this->_set_style_side_type('border', $side, 'width', $value, $important);
                $this->_set_style_side_type('border', $side, 'color', $value, $important);
            } else {
                // must be color
                $this->_set_style_side_type('border', $side, 'color', $this->munge_color($value), $important);
            }
        }
    }

    /**
     * Sets the border styles
     *
     * @link http://www.w3.org/TR/CSS21/box.html#border-properties
     * @param string $val
     */
    function set_border_top($val)
    {
        $this->_set_border("top", $val, isset($this->_important_props['border_top']));
    }

    function set_border_top_color($val)
    {
        $color = $val;
        if ($val === "") {
            $color = $this->__get("color");
        }
        $this->_set_style_side_type('border', 'top', 'color', $color, isset($this->_important_props['border_top_color']));
    }

    function set_border_top_style($val)
    {
        $this->_set_style_side_type('border', 'top', 'style', $val, isset($this->_important_props['border_top_style']));
    }

    function set_border_top_width($val)
    {
        $this->_set_style_side_type('border', 'top', 'width', $val, isset($this->_important_props['border_top_width']));
    }

    /**
     * @param $val
     */
    function set_border_right($val)
    {
        $this->_set_border("right", $val, isset($this->_important_props['border_right']));
    }

    function set_border_right_color($val)
    {
        $color = $val;
        if ($val === "") {
            $color = $this->__get("color");
        }
        $this->_set_style_side_type('border', 'right', 'color', $color, isset($this->_important_props['border_right_color']));
    }

    function set_border_right_style($val)
    {
        $this->_set_style_side_type('border', 'right', 'style', $val, isset($this->_important_props['border_right_style']));
    }

    function set_border_right_width($val)
    {
        $this->_set_style_side_type('border', 'right', 'width', $val, isset($this->_important_props['border_right_width']));
    }

    /**
     * @param $val
     */
    function set_border_bottom($val)
    {
        $this->_set_border("bottom", $val, isset($this->_important_props['border_bottom']));
    }

    function set_border_bottom_color($val)
    {
        $color = $val;
        if ($val === "") {
            $color = $this->__get("color");
        }
        $this->_set_style_side_type('border', 'bottom', 'color', $color, isset($this->_important_props['border_bottom_color']));
    }

    function set_border_bottom_style($val)
    {
        $this->_set_style_side_type('border', 'bottom', 'style', $val, isset($this->_important_props['border_bottom_style']));
    }

    function set_border_bottom_width($val)
    {
        $this->_set_style_side_type('border', 'bottom', 'width', $val, isset($this->_important_props['border_bottom_width']));
    }

    /**
     * @param $val
     */
    function set_border_left($val)
    {
        $this->_set_border("left", $val, isset($this->_important_props['border_left']));
    }

    function set_border_left_color($val)
    {
        $color = $val;
        if ($val === "") {
            $color = $this->__get("color");
        }
        $this->_set_style_side_type('border', 'left', 'color', $color, isset($this->_important_props['border_left_color']));
    }

    function set_border_left_style($val)
    {
        $this->_set_style_side_type('border', 'left', 'style', $val, isset($this->_important_props['border_left_style']));
    }

    function set_border_left_width($val)
    {
        $this->_set_style_side_type('border', 'left', 'width', $val, isset($this->_important_props['border_left_width']));
    }

    /**
     * @param $val
     */
    function set_border($val)
    {
        $important = isset($this->_important_props["border"]);

        $this->_set_border("top", $val, $important);
        $this->_set_border("right", $val, $important);
        $this->_set_border("bottom", $val, $important);
        $this->_set_border("left", $val, $important);
    }

    /**
     * @param $val
     */
    function set_border_width($val)
    {
        $this->_set_style_type_important('border', 'width', $val);
    }

    /**
     * @param $val
     */
    function set_border_color($val)
    {
        $this->_set_style_type_important('border', 'color', $val);
    }

    /**
     * @param $val
     */
    function set_border_style($val)
    {
        $this->_set_style_type_important('border', 'style', $val);
    }

    /**
     * Sets the border radius size
     *
     * http://www.w3.org/TR/css3-background/#corners
     *
     * @param $val
     */
    function set_border_top_left_radius($val)
    {
        $this->_set_border_radius_corner($val, "top_left");
    }

    /**
     * @param $val
     */
    function set_border_top_right_radius($val)
    {
        $this->_set_border_radius_corner($val, "top_right");
    }

    /**
     * @param $val
     */
    function set_border_bottom_left_radius($val)
    {
        $this->_set_border_radius_corner($val, "bottom_left");
    }

    /**
     * @param $val
     */
    function set_border_bottom_right_radius($val)
    {
        $this->_set_border_radius_corner($val, "bottom_right");
    }

    /**
     * @param $val
     */
    function set_border_radius($val)
    {
        $val = preg_replace("/\s*\,\s*/", ",", $val); // when border-radius has spaces
        $arr = explode(" ", $val);

        switch (count($arr)) {
            case 1:
                $this->_set_border_radii($arr[0], $arr[0], $arr[0], $arr[0]);
                break;
            case 2:
                $this->_set_border_radii($arr[0], $arr[1], $arr[0], $arr[1]);
                break;
            case 3:
                $this->_set_border_radii($arr[0], $arr[1], $arr[2], $arr[1]);
                break;
            case 4:
                $this->_set_border_radii($arr[0], $arr[1], $arr[2], $arr[3]);
                break;
        }
    }

    /**
     * @param $val1
     * @param $val2
     * @param $val3
     * @param $val4
     */
    protected function _set_border_radii($val1, $val2, $val3, $val4)
    {
        $this->_set_border_radius_corner($val1, "top_left");
        $this->_set_border_radius_corner($val2, "top_right");
        $this->_set_border_radius_corner($val3, "bottom_right");
        $this->_set_border_radius_corner($val4, "bottom_left");
    }

    /**
     * @param $val
     * @param $corner
     */
    protected function _set_border_radius_corner($val, $corner)
    {
        $this->_has_border_radius = true;

        $this->_props["border_" . $corner . "_radius"] = $val;
        $this->_props_computed["border_" . $corner . "_radius"] = null;
        $this->_prop_cache["border_" . $corner . "_radius"] = null;

        if ($val === 'inherit') {
            return;
        }

        $this->_props_computed["border_" . $corner . "_radius"] = $val;
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
        if (!isset($this->_props_computed["border_" . $corner . "_radius"]) || empty($this->_props_computed["border_" . $corner . "_radius"])) {
            return 0;
        }

        return $this->length_in_pt($this->_props_computed["border_" . $corner . "_radius"]);
    }

    /**
     * Sets the outline styles
     *
     * @link http://www.w3.org/TR/CSS21/ui.html#dynamic-outlines
     * @param string $val
     */
    function set_outline($val)
    {
        $important = isset($this->_important_props["outline"]);

        $props = [
            "outline_style",
            "outline_width",
            "outline_color",
        ];

        foreach ($props as $prop) {
            $_val = self::$_defaults[$prop];

            if (!isset($this->_important_props[$prop]) || $important) {
                //see __set and __get, on all assignments clear cache!
                $this->_prop_cache[$prop] = null;
                if ($important) {
                    $this->_important_props[$prop] = true;
                }
                $this->_props[$prop] = $_val;
            }
        }

        $val = preg_replace("/\s*\,\s*/", ",", $val); // when rgb() has spaces
        $arr = explode(" ", $val);
        foreach ($arr as $value) {
            $value = trim($value);

            if (in_array($value, self::$BORDER_STYLES)) {
                $this->__set("outline_style", $value);
            } else if ($value === "0" || preg_match("/[.0-9]+(?:px|pt|pc|em|ex|%|in|mm|cm)|(?:thin|medium|thick)/", $value)) {
                $this->__set("outline_width", $value);
            } else {
                // must be color
                $this->__set("outline_color", $value);
            }
        }

        //see __set and __get, on all assignments clear cache, not needed on direct set through __set
        $this->_props["outline"] = $val;
        $this->_props_computed["outline"] = null;
        $this->_prop_cache["outline"] = null;
    }

    /**
     * @param $val
     */
    function set_outline_width($val)
    {
        $this->_set_style_side_type("outline", null, "width", $val, isset($this->_important_props["outline_width"]));
    }

    /**
     * @param $val
     */
    function set_outline_color($val)
    {
        $color = $val;
        if ($val === "") {
            $color = $this->__get("color");
        }
        $this->_set_style_side_type("outline", null, "color", $color, isset($this->_important_props["outline_color"]));
    }

    /**
     * @param $val
     */
    function set_outline_style($val)
    {
        $this->_set_style_side_type("outline", null, "style", $val, isset($this->_important_props["outline_style"]));
    }

    /**
     * Sets the border spacing
     *
     * @link http://www.w3.org/TR/CSS21/box.html#border-properties
     * @param float $val
     */
    function set_border_spacing($val)
    {
        $arr = explode(" ", $val);

        if (count($arr) == 1) {
            $arr[1] = $arr[0];
        }

        $this->_props["border_spacing"] = $val;
        $this->_props_computed["border_spacing"] = null;
        $this->_prop_cache["border_spacing"] = null;

        if ($val === 'inherit') {
            return;
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
        $this->_props["list_style_image"] = $val;
        $parsed_val = $this->_image($val);
        if ($parsed_val === "none") {
            $this->_props_computed["list_style_image"] = "none";
        } else {
            $this->_props_computed["list_style_image"] = "url(" . $parsed_val . ")";
        }
        $this->_prop_cache["list_style_image"] = null;
    }

    /**
     * Sets the list style
     *
     * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style
     * @param $val
     */
    function set_list_style($val)
    {
        $important = isset($this->_important_props["list_style"]);
        $arr = explode(" ", str_replace(",", " ", $val));

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

        static $positions = ["inside", "outside"];

        foreach ($arr as $value) {
            /* http://www.w3.org/TR/CSS21/generate.html#list-style
             * A value of 'none' for the 'list-style' property sets both 'list-style-type' and 'list-style-image' to 'none'
             */
            if ($value === "none") {
                $this->_set_style("list_style_type", $value, $important);
                $this->_set_style("list_style_image", $value, $important);
                continue;
            }

            //On setting or merging or inheriting list_style_image as well as list_style_type,
            //and url exists, then url has precedence, otherwise fall back to list_style_type
            //Firefox is wrong here (list_style_image gets overwritten on explicit list_style_type)
            //Internet Explorer 7/8 and dompdf is right.

            if (mb_substr($value, 0, 3) === "url") {
                $this->_set_style("list_style_image", $value, $important);
                continue;
            }

            if (in_array($value, $types)) {
                $this->_set_style("list_style_type", $value, $important);
            } else if (in_array($value, $positions)) {
                $this->_set_style("list_style_position", $value, $important);
            }
        }

        $this->_props["list_style"] = $val;
        $this->_props_computed["list_style"] = null;
        $this->_prop_cache["list_style"] = null;
    }

    /**
     * @param $val
     */
    function set_size($val)
    {
        $this->_props["size"] = $val;
        $this->_props_computed["size"] = null;
        $this->_prop_cache["size"] = null;

        $length_re = "/(\d+\s*(?:pt|px|pc|em|ex|in|cm|mm|%))/";

        $val = mb_strtolower($val);

        if ($val === "auto") {
            $this->_props["size"] = $val;
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
        //see __set and __get, on all assignments clear cache, not needed on direct set through __set
        $this->_props["transform"] = $val;
        $this->_props_computed["transform"] = null;
        $this->_prop_cache["transform"] = null;

        if ($val === 'inherit') {
            return;
        }
        
        $this->_props_computed["transform"] = $val;
    }

    /**
     * @param $val
     */
    function set__webkit_transform($val)
    {
        $this->__set("transform", $val);
    }

    /**
     * @param $val
     */
    function set__webkit_transform_origin($val)
    {
        $this->__set("transform_origin", $val);
    }

    /**
     * Sets the CSS3 transform-origin property
     *
     * @link http://www.w3.org/TR/css3-2d-transforms/#transform-origin
     * @param string $val
     */
    function set_transform_origin($val)
    {
        $this->_props["transform_origin"] = $val;
        $this->_props_computed["transform_origin"] = null;
        $this->_prop_cache["transform_origin"] = null;

        if ($val === 'inherit') {
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
        
        $values = preg_split("/\s+/", $this->_props_computed['transform_origin']);

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
        $this->_props["background_image_resolution"] = $val;
        $this->_props_computed["background_image_resolution"] = null;
        $this->_prop_cache["background_image_resolution"] = null;

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
        $this->_props["image_resolution"] = $val;
        $this->_props_computed["image_resolution"] = null;
        $this->_prop_cache["image_resolution"] = null;

        $parsed = $this->parse_image_resolution($val);

        $this->_props_computed["image_resolution"] = $parsed;
    }

    /**
     * @param $val
     */
    function set__dompdf_background_image_resolution($val)
    {
        $this->__set("background_image_resolution", $val);
    }

    /**
     * @param $val
     */
    function set__dompdf_image_resolution($val)
    {
        $this->__set("image_resolution", $val);
    }

    /**
     * @param $val
     */
    function set_z_index($val)
    {
        $this->_props["z_index"] = $val;
        $this->_props_computed["z_index"] = null;
        $this->_prop_cache["z_index"] = null;

        if (round($val) != $val && $val !== "auto") {
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
        return print_r(array_merge(["parent_font_size" => $this->_parent_font_size],
            $this->_props), true);
    }

    /*DEBUGCSS*/
    function debug_print()
    {
        print "    parent_font_size:" . $this->_parent_font_size . ";\n";
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
