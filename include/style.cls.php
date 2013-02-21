<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

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
class Style {
  
  const CSS_IDENTIFIER = "-?[_a-zA-Z]+[_a-zA-Z0-9-]*";
  const CSS_INTEGER    = "-?\d+";

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
  static $font_size_keywords = array(
    "xx-small" => 0.6,   // 3/5
    "x-small"  => 0.75,  // 3/4
    "small"    => 0.889, // 8/9
    "medium"   => 1,     // 1
    "large"    => 1.2,   // 6/5
    "x-large"  => 1.5,   // 3/2
    "xx-large" => 2.0,   // 2/1
  );
  
  /**
   * List of all inline types.  Should really be a constant.
   *
   * @var array
   */
  static $INLINE_TYPES = array("inline");

  /**
   * List of all block types.  Should really be a constant.
   *
   * @var array
   */
  static $BLOCK_TYPES = array("block", "inline-block", "table-cell", "list-item");
  
  /**
   * List of all positionned types.  Should really be a constant.
   *
   * @var array
   */
  static $POSITIONNED_TYPES = array("relative", "absolute", "fixed");

  /**
   * List of all table types.  Should really be a constant.
   *
   * @var array;
   */
  static $TABLE_TYPES = array("table", "inline-table");

  /**
   * List of valid border styles.  Should also really be a constant.
   *
   * @var array
   */
  static $BORDER_STYLES = array("none", "hidden", "dotted", "dashed", "solid",
                                "double", "groove", "ridge", "inset", "outset");

  /**
   * Default style values.
   *
   * @link http://www.w3.org/TR/CSS21/propidx.html
   *
   * @var array
   */
  static protected $_defaults = null;

  /**
   * List of inherited properties
   *
   * @link http://www.w3.org/TR/CSS21/propidx.html
   *
   * @var array
   */
  static protected $_inherited = null;
  
  /**
   * Caches method_exists result
   * 
   * @var array<bool>
   */
  static protected $_methods_cache = array();

  /**
   * The stylesheet this style belongs to
   *
   * @see Stylesheet
   * @var Stylesheet
   */
  protected $_stylesheet; // stylesheet this style is attached to

  /**
   * Main array of all CSS properties & values
   *
   * @var array
   */
  protected $_props;

  /* var instead of protected would allow access outside of class */
  protected $_important_props;

  /**
   * Cached property values
   *
   * @var array
   */
  protected $_prop_cache;
  
  /**
   * Font size of parent element in document tree.  Used for relative font
   * size resolution.
   *
   * @var float
   */
  protected $_parent_font_size; // Font size of parent element
  
  protected $_font_family;
  
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
   * True once the font size is resolved absolutely
   *
   * @var bool
   */
  private $__font_size_calculated; // Cache flag
  
  /**
   * The computed border radius
   */
  private $_computed_border_radius = null;

  /**
   * @var bool
   */
  public $_has_border_radius = false;

  /**
   * Class constructor
   *
   * @param Stylesheet $stylesheet the stylesheet this Style is associated with.
   * @param int        $origin
   */
  function __construct(Stylesheet $stylesheet, $origin = Stylesheet::ORIG_AUTHOR) {
    $this->_props = array();
    $this->_important_props = array();
    $this->_stylesheet = $stylesheet;
    $this->_origin = $origin;
    $this->_parent_font_size = null;
    $this->__font_size_calculated = false;
    
    if ( !isset(self::$_defaults) ) {
    
      // Shorthand
      $d =& self::$_defaults;
    
      // All CSS 2.1 properties, and their default values
      $d["azimuth"] = "center";
      $d["background_attachment"] = "scroll";
      $d["background_color"] = "transparent";
      $d["background_image"] = "none";
      $d["background_image_resolution"] = "normal";
      $d["_dompdf_background_image_resolution"] = $d["background_image_resolution"];
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
      $d["font_family"] = $stylesheet->get_dompdf()->get_option("default_font");
      $d["font_size"] = "medium";
      $d["font_style"] = "normal";
      $d["font_variant"] = "normal";
      $d["font_weight"] = "normal";
      $d["font"] = "";
      $d["height"] = "auto";
      $d["image_resolution"] = "normal";
      $d["_dompdf_image_resolution"] = $d["image_resolution"];
      $d["_dompdf_keep"] = "";
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
      $d["opacity"] = "1.0"; // CSS3
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
      $d["text_align"] = "left";
      $d["text_decoration"] = "none";
      $d["text_indent"] = "0";
      $d["text_transform"] = "none";
      $d["top"] = "auto";
      $d["transform"] = "none"; // CSS3
      $d["transform_origin"] = "50% 50%"; // CSS3
      $d["_webkit_transform"] = $d["transform"]; // CSS3
      $d["_webkit_transform_origin"] = $d["transform_origin"]; // CSS3
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
      
      // for @font-face
      $d["src"] = "";
      $d["unicode_range"] = "";

      // Properties that inherit by default
      self::$_inherited = array(
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
      );
    }
  }

  /**
   * "Destructor": forcibly free all references held by this object
   */
  function dispose() {
    clear_object($this);
  }
  
  function set_frame(Frame $frame) {
    $this->_frame = $frame;
  }
  
  function get_frame() {
    return $this->_frame;
  }
  
  function set_origin($origin) {
    $this->_origin = $origin;
  }
  
  function get_origin() {
    return $this->_origin;
  }
  
  /**
   * returns the {@link Stylesheet} this Style is associated with.
   *
   * @return Stylesheet
   */
  function get_stylesheet() { return $this->_stylesheet; }
  
  /**
   * Converts any CSS length value into an absolute length in points.
   *
   * length_in_pt() takes a single length (e.g. '1em') or an array of
   * lengths and returns an absolute length.  If an array is passed, then
   * the return value is the sum of all elements.
   *
   * If a reference size is not provided, the default font size is used
   * ({@link Style::$default_font_size}).
   *
   * @param float|array $length   the length or array of lengths to resolve
   * @param float       $ref_size  an absolute reference size to resolve percentage lengths
   * @return float
   */
  function length_in_pt($length, $ref_size = null) {
    static $cache = array();
    
    if ( !is_array($length) ) {
      $length = array($length);
    }

    if ( !isset($ref_size) ) {
      $ref_size = self::$default_font_size;
    }
      
    $key = implode("@", $length)."/$ref_size";
    
    if ( isset($cache[$key]) ) {
      return $cache[$key];
    }

    $ret = 0;
    foreach ($length as $l) {

      if ( $l === "auto" ) {
        return "auto";
      }
      
      if ( $l === "none" ) {
        return "none";
      }

      // Assume numeric values are already in points
      if ( is_numeric($l) ) {
        $ret += $l;
        continue;
      }
        
      if ( $l === "normal" ) {
        $ret += $ref_size;
        continue;
      }

      // Border lengths
      if ( $l === "thin" ) {
        $ret += 0.5;
        continue;
      }
      
      if ( $l === "medium" ) {
        $ret += 1.5;
        continue;
      }
    
      if ( $l === "thick" ) {
        $ret += 2.5;
        continue;
      }

      if ( ($i = mb_strpos($l, "px"))  !== false ) {
        $dpi = $this->_stylesheet->get_dompdf()->get_option("dpi");
        $ret += ( mb_substr($l, 0, $i)  * 72 ) / $dpi;
        continue;
      }
      
      if ( ($i = mb_strpos($l, "pt"))  !== false ) {
        $ret += (float)mb_substr($l, 0, $i);
        continue;
      }
      
      if ( ($i = mb_strpos($l, "%"))  !== false ) {
        $ret += (float)mb_substr($l, 0, $i)/100 * $ref_size;
        continue;
      }

      if ( ($i = mb_strpos($l, "rem"))  !== false ) {
        $ret += (float)mb_substr($l, 0, $i) * $this->_stylesheet->get_dompdf()->get_tree()->get_root()->get_style()->font_size;
        continue;
      }

      if ( ($i = mb_strpos($l, "em"))  !== false ) {
        $ret += (float)mb_substr($l, 0, $i) * $this->__get("font_size");
        continue;
      }
          
      if ( ($i = mb_strpos($l, "cm")) !== false ) {
        $ret += mb_substr($l, 0, $i) * 72 / 2.54;
        continue;
      }

      if ( ($i = mb_strpos($l, "mm")) !== false ) {
        $ret += mb_substr($l, 0, $i) * 72 / 25.4;
        continue;
      }
      
      // FIXME: em:ex ratio?
      if ( ($i = mb_strpos($l, "ex"))  !== false ) {
        $ret += mb_substr($l, 0, $i) * $this->__get("font_size") / 2;
        continue;
      }
      
      if ( ($i = mb_strpos($l, "in")) !== false ) {
        $ret += (float)mb_substr($l, 0, $i) * 72;
        continue;
      }
          
      if ( ($i = mb_strpos($l, "pc")) !== false ) {
        $ret += (float)mb_substr($l, 0, $i) * 12;
        continue;
      }
          
      // Bogus value
      $ret += $ref_size;
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
  function inherit(Style $parent) {

    // Set parent font size
    $this->_parent_font_size = $parent->get_font_size();
    
    foreach (self::$_inherited as $prop) {
      //inherit the !important property also.
      //if local property is also !important, don't inherit.
      if ( isset($parent->_props[$prop]) &&
           ( !isset($this->_props[$prop]) ||
             ( isset($parent->_important_props[$prop]) && !isset($this->_important_props[$prop]) )
           )
         ) {
        if ( isset($parent->_important_props[$prop]) ) {
          $this->_important_props[$prop] = true;
        }
        //see __set and __get, on all assignments clear cache!
        $this->_prop_cache[$prop] = null;
        $this->_props[$prop] = $parent->_props[$prop];
      }
    }
      
    foreach ($this->_props as $prop => $value) {
      if ( $value === "inherit" ) {
        if ( isset($parent->_important_props[$prop]) ) {
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
        $this->__set($prop, $parent->__get($prop));
      }
    }
          
    return $this;
  }
  
  /**
   * Override properties in this style with those in $style
   *
   * @param Style $style
   */
  function merge(Style $style) {
    //treat the !important attribute
    //if old rule has !important attribute, override with new rule only if
    //the new rule is also !important
    foreach($style->_props as $prop => $val ) {
      if (isset($style->_important_props[$prop])) {
        $this->_important_props[$prop] = true;
        //see __set and __get, on all assignments clear cache!
        $this->_prop_cache[$prop] = null;
        $this->_props[$prop] = $val;
      }
      else if ( !isset($this->_important_props[$prop]) ) {
        //see __set and __get, on all assignments clear cache!
        $this->_prop_cache[$prop] = null;
        $this->_props[$prop] = $val;
      }
    }

    if ( isset($style->_props["font_size"]) ) {
      $this->__font_size_calculated = false;
    }
  }

  /**
   * Returns an array(r, g, b, "r"=> r, "g"=>g, "b"=>b, "hex"=>"#rrggbb")
   * based on the provided CSS color value.
   *
   * @param string $color
   * @return array
   */
  function munge_color($color) {
    return CSS_Color::parse($color);
  }

  /* direct access to _important_props array from outside would work only when declared as
   * 'var $_important_props;' instead of 'protected $_important_props;'
   * Don't call _set/__get on missing attribute. Therefore need a special access.
   * Assume that __set will be also called when this is called, so do not check validity again.
   * Only created, if !important exists -> always set true.
   */
  function important_set($prop) {
    $prop = str_replace("-", "_", $prop);
    $this->_important_props[$prop] = true;
  }

  function important_get($prop) {
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
   * Very many uses, e.g. frame_reflower.cls.php -> for now leave as it is
   * function __set($prop, $val) {
   *   throw new DOMPDF_Exception("Implicite replacement of assignment by __set.  Not good.");
   * }
   * function props_set($prop, $val) { ... }
   *
   * @param string $prop  the property to set
   * @param mixed  $val   the value of the property
   *
   */
  function __set($prop, $val) {
    $prop = str_replace("-", "_", $prop);
    $this->_prop_cache[$prop] = null;
    
    if ( !isset(self::$_defaults[$prop]) ) {
      global $_dompdf_warnings;
      $_dompdf_warnings[] = "'$prop' is not a valid CSS2 property.";
      return;
    }
    
    if ( $prop !== "content" && is_string($val) && strlen($val) > 5 && mb_strpos($val, "url") === false ) {
      $val = mb_strtolower(trim(str_replace(array("\n", "\t"), array(" "), $val)));
      $val = preg_replace("/([0-9]+) (pt|px|pc|em|ex|in|cm|mm|%)/S", "\\1\\2", $val);
    }
    
    $method = "set_$prop";
    
    if ( !isset(self::$_methods_cache[$method]) ) {
      self::$_methods_cache[$method] = method_exists($this, $method);
    }

    if ( self::$_methods_cache[$method] ) {
      $this->$method($val);
    }
    else {
      $this->_props[$prop] = $val;
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
   * @throws DOMPDF_Exception
   * @return mixed
   */
  function __get($prop) {
    if ( !isset(self::$_defaults[$prop]) ) {
      throw new DOMPDF_Exception("'$prop' is not a valid CSS2 property.");
    }

    if ( isset($this->_prop_cache[$prop]) && $this->_prop_cache[$prop] != null ) {
      return $this->_prop_cache[$prop];
    }
    
    $method = "get_$prop";

    // Fall back on defaults if property is not set
    if ( !isset($this->_props[$prop]) ) {
      $this->_props[$prop] = self::$_defaults[$prop];
    }

    if ( !isset(self::$_methods_cache[$method]) ) {
      self::$_methods_cache[$method] = method_exists($this, $method);
    }
    
    if ( self::$_methods_cache[$method] ) {
      return $this->_prop_cache[$prop] = $this->$method();
    }

    return $this->_prop_cache[$prop] = $this->_props[$prop];
  }

  function get_font_family_raw(){
    return trim($this->_props["font_family"], " \t\n\r\x0B\"'");
  }

  /**
   * Getter for the 'font-family' CSS property.
   * Uses the {@link Font_Metrics} class to resolve the font family into an
   * actual font file.
   *
   * @link http://www.w3.org/TR/CSS21/fonts.html#propdef-font-family
   * @throws DOMPDF_Exception
   *
   * @return string
   */
  function get_font_family() {
    if ( isset($this->_font_family) ) {
      return $this->_font_family;
    }
    
    $DEBUGCSS=DEBUGCSS; //=DEBUGCSS; Allow override of global setting for ad hoc debug

    // Select the appropriate font.  First determine the subtype, then check
    // the specified font-families for a candidate.

    // Resolve font-weight
    $weight = $this->__get("font_weight");
    
    if ( is_numeric($weight) ) {
      if ( $weight < 600 ) {
        $weight = "normal";
      }
      else {
        $weight = "bold";
      }
    }
    else if ( $weight === "bold" || $weight === "bolder" ) {
      $weight = "bold";
    }
    else {
      $weight = "normal";
    }

    // Resolve font-style
    $font_style = $this->__get("font_style");

    if ( $weight === "bold" && ($font_style === "italic" || $font_style === "oblique") ) {
      $subtype = "bold_italic";
    }
    else if ( $weight === "bold" && $font_style !== "italic" && $font_style !== "oblique" ) {
      $subtype = "bold";
    }
    else if ( $weight !== "bold" && ($font_style === "italic" || $font_style === "oblique") ) {
      $subtype = "italic";
    }
    else {
      $subtype = "normal";
    }
    
    // Resolve the font family
    if ( $DEBUGCSS ) {
      print "<pre>[get_font_family:";
      print '('.$this->_props["font_family"].'.'.$font_style.'.'.$this->__get("font_weight").'.'.$weight.'.'.$subtype.')';
    }
    
    $families = preg_split("/\s*,\s*/", $this->_props["font_family"]);

    $font = null;
    foreach($families as $family) {
      //remove leading and trailing string delimiters, e.g. on font names with spaces;
      //remove leading and trailing whitespace
      $family = trim($family, " \t\n\r\x0B\"'");
      if ( $DEBUGCSS ) {
        print '('.$family.')';
      }
      $font = Font_Metrics::get_font($family, $subtype);

      if ( $font ) {
        if ($DEBUGCSS) print '('.$font.")get_font_family]\n</pre>";
        return $this->_font_family = $font;
      }
    }

    $family = null;
    if ( $DEBUGCSS ) {
      print '(default)';
    }
    $font = Font_Metrics::get_font($family, $subtype);

    if ( $font ) {
      if ( $DEBUGCSS ) print '('.$font.")get_font_family]\n</pre>";
      return$this->_font_family = $font;
    }
    
    throw new DOMPDF_Exception("Unable to find a suitable font replacement for: '" . $this->_props["font_family"] ."'");
    
  }

  /**
   * Returns the resolved font size, in points
   *
   * @link http://www.w3.org/TR/CSS21/fonts.html#propdef-font-size
   * @return float
   */
  function get_font_size() {

    if ( $this->__font_size_calculated ) {
      return $this->_props["font_size"];
    }
    
    if ( !isset($this->_props["font_size"]) ) {
      $fs = self::$_defaults["font_size"];
    }
    else {
      $fs = $this->_props["font_size"];
    }
    
    if ( !isset($this->_parent_font_size) ) {
      $this->_parent_font_size = self::$default_font_size;
    }
    
    switch ($fs) {
      case "xx-small":
      case "x-small":
      case "small":
      case "medium":
      case "large":
      case "x-large":
      case "xx-large":
        $fs = self::$default_font_size * self::$font_size_keywords[$fs];
        break;
  
      case "smaller":
        $fs = 8/9 * $this->_parent_font_size;
        break;
        
      case "larger":
        $fs = 6/5 * $this->_parent_font_size;
        break;
        
      default:
        break;
    }

    // Ensure relative sizes resolve to something
    if ( ($i = mb_strpos($fs, "em")) !== false ) {
      $fs = mb_substr($fs, 0, $i) * $this->_parent_font_size;
    }
    else if ( ($i = mb_strpos($fs, "ex")) !== false ) {
      $fs = mb_substr($fs, 0, $i) * $this->_parent_font_size;
    }
    else {
      $fs = $this->length_in_pt($fs);
    }

    //see __set and __get, on all assignments clear cache!
    $this->_prop_cache["font_size"] = null;
    $this->_props["font_size"] = $fs;
    $this->__font_size_calculated = true;
    return $this->_props["font_size"];

  }

  /**
   * @link http://www.w3.org/TR/CSS21/text.html#propdef-word-spacing
   * @return float
   */
  function get_word_spacing() {
    if ( $this->_props["word_spacing"] === "normal" ) {
      return 0;
    }

    return $this->_props["word_spacing"];
  }

  /**
   * @link http://www.w3.org/TR/CSS21/text.html#propdef-letter-spacing
   * @return float
   */
  function get_letter_spacing() {
    if ( $this->_props["letter_spacing"] === "normal" ) {
      return 0;
    }

    return $this->_props["letter_spacing"];
  }

  /**
   * @link http://www.w3.org/TR/CSS21/visudet.html#propdef-line-height
   * @return float
   */
  function get_line_height() {
    $line_height = $this->_props["line_height"];
    
    if ( $line_height === "normal" ) {
      return self::$default_line_height * $this->get_font_size();
    }

    if ( is_numeric($line_height) ) {
      return $this->length_in_pt( $line_height . "em", $this->get_font_size());
    }
    
    return $this->length_in_pt( $line_height, $this->_parent_font_size );
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
  function get_color() {
    return $this->munge_color( $this->_props["color"] );
  }

  /**
   * Returns the background color as an array
   *
   * The returned array has the same format as {@link Style::get_color()}
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-color
   * @return array
   */
  function get_background_color() {
    return $this->munge_color( $this->_props["background_color"] );
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
  function get_background_position() {
    $tmp = explode(" ", $this->_props["background_position"]);

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

    if ( isset($tmp[1]) ) {

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
          if ( $tmp[0] === "left" || $tmp[0] === "right" || $tmp[0] === "center" ) {
            $y = "50%";
          }
          else {
            $x = "50%";
          }
          break;
          
        default:
          $y = $tmp[1];
          break;
      }

    }
    else {
      $y = "50%";
    }

    if ( !isset($x) ) {
      $x = "0%";
    }

    if ( !isset($y) ) {
      $y = "0%";
    }

    return array(
      0 => $x, "x" => $x,
      1 => $y, "y" => $y,
    );
  }


  /**
   * Returns the background as it is currently stored
   *
   * (currently anyway only for completeness.
   * not used for further processing)
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-attachment
   * @return string
   */
  function get_background_attachment() {
    return $this->_props["background_attachment"];
  }


  /**
   * Returns the background_repeat as it is currently stored
   *
   * (currently anyway only for completeness.
   * not used for further processing)
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-repeat
   * @return string
   */
  function get_background_repeat() {
    return $this->_props["background_repeat"];
  }


  /**
   * Returns the background as it is currently stored
   *
   * (currently anyway only for completeness.
   * not used for further processing, but the individual get_background_xxx)
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background
   * @return string
   */
  function get_background() {
    return $this->_props["background"];
  }


  /**#@+
   * Returns the border color as an array
   *
   * See {@link Style::get_color()}
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-color-properties
   * @return array
   */
  function get_border_top_color() {
    if ( $this->_props["border_top_color"] === "" ) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache["border_top_color"] = null;
      $this->_props["border_top_color"] = $this->__get("color");
    }
    
    return $this->munge_color($this->_props["border_top_color"]);
  }

  function get_border_right_color() {
    if ( $this->_props["border_right_color"] === "" ) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache["border_right_color"] = null;
      $this->_props["border_right_color"] = $this->__get("color");
    }
    
    return $this->munge_color($this->_props["border_right_color"]);
  }

  function get_border_bottom_color() {
    if ( $this->_props["border_bottom_color"] === "" ) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache["border_bottom_color"] = null;
      $this->_props["border_bottom_color"] = $this->__get("color");
    }
    
    return $this->munge_color($this->_props["border_bottom_color"]);
  }

  function get_border_left_color() {
    if ( $this->_props["border_left_color"] === "" ) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache["border_left_color"] = null;
      $this->_props["border_left_color"] = $this->__get("color");
    }
    
    return $this->munge_color($this->_props["border_left_color"]);
  }
  
  /**#@-*/

 /**#@+
   * Returns the border width, as it is currently stored
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-width-properties
   * @return float|string
   */
  function get_border_top_width() {
    $style = $this->__get("border_top_style");
    return $style !== "none" && $style !== "hidden" ? $this->length_in_pt($this->_props["border_top_width"]) : 0;
  }
  
  function get_border_right_width() {
    $style = $this->__get("border_right_style");
    return $style !== "none" && $style !== "hidden" ? $this->length_in_pt($this->_props["border_right_width"]) : 0;
  }

  function get_border_bottom_width() {
    $style = $this->__get("border_bottom_style");
    return $style !== "none" && $style !== "hidden" ? $this->length_in_pt($this->_props["border_bottom_width"]) : 0;
  }

  function get_border_left_width() {
    $style = $this->__get("border_left_style");
    return $style !== "none" && $style !== "hidden" ? $this->length_in_pt($this->_props["border_left_width"]) : 0;
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
  function get_border_properties() {
    return array(
      "top" => array(
        "width" => $this->__get("border_top_width"),
        "style" => $this->__get("border_top_style"),
        "color" => $this->__get("border_top_color"),
      ),
      "bottom" => array(
        "width" => $this->__get("border_bottom_width"),
        "style" => $this->__get("border_bottom_style"),
        "color" => $this->__get("border_bottom_color"),
      ),
      "right" => array(
        "width" => $this->__get("border_right_width"),
        "style" => $this->__get("border_right_style"),
        "color" => $this->__get("border_right_color"),
      ),
      "left" => array(
        "width" => $this->__get("border_left_width"),
        "style" => $this->__get("border_left_style"),
        "color" => $this->__get("border_left_color"),
      ),
    );
  }

  /**
   * Return a single border property
   *
   * @param string $side
   *
   * @return mixed
   */
  protected function _get_border($side) {
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
  function get_border_top() {
    return $this->_get_border("top");
  }
  
  function get_border_right() {
    return $this->_get_border("right");
  }
  
  function get_border_bottom() {
    return $this->_get_border("bottom");
  }
  
  function get_border_left() {
    return $this->_get_border("left");
  }
  /**#@-*/
  
  function get_computed_border_radius($w, $h) {
    if ( !empty($this->_computed_border_radius) ) {
      return $this->_computed_border_radius;
    }
    
    $rTL = $this->__get("border_top_left_radius");
    $rTR = $this->__get("border_top_right_radius");
    $rBL = $this->__get("border_bottom_left_radius");
    $rBR = $this->__get("border_bottom_right_radius");
    
    if ( $rTL + $rTR + $rBL + $rBR == 0 ) {
      return $this->_computed_border_radius = array(
        0, 0, 0, 0,
        "top-left"     => 0, 
        "top-right"    => 0, 
        "bottom-right" => 0, 
        "bottom-left"  => 0, 
      );
    }
    
    $t = $this->__get("border_top_width");
    $r = $this->__get("border_right_width");
    $b = $this->__get("border_bottom_width");
    $l = $this->__get("border_left_width");
    
    $rTL = min($rTL, $h - $rBL - $t/2 - $b/2, $w - $rTR - $l/2 - $r/2);
    $rTR = min($rTR, $h - $rBR - $t/2 - $b/2, $w - $rTL - $l/2 - $r/2);
    $rBL = min($rBL, $h - $rTL - $t/2 - $b/2, $w - $rBR - $l/2 - $r/2);
    $rBR = min($rBR, $h - $rTR - $t/2 - $b/2, $w - $rBL - $l/2 - $r/2);
    
    return $this->_computed_border_radius = array(
      $rTL, $rTR, $rBR, $rBL,
      "top-left"     => $rTL, 
      "top-right"    => $rTR, 
      "bottom-right" => $rBR, 
      "bottom-left"  => $rBL, 
    );
  }
  /**#@-*/


  /**
   * Returns the outline color as an array
   *
   * See {@link Style::get_color()}
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-color-properties
   * @return array
   */
  function get_outline_color() {
    if ( $this->_props["outline_color"] === "" ) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache["outline_color"] = null;
      $this->_props["outline_color"] = $this->__get("color");
    }
    
    return $this->munge_color($this->_props["outline_color"]);
  }

 /**#@+
   * Returns the outline width, as it is currently stored
   * @return float|string
   */
  function get_outline_width() {
    $style = $this->__get("outline_style");
    return $style !== "none" && $style !== "hidden" ? $this->length_in_pt($this->_props["outline_width"]) : 0;
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
  function get_outline() {
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
  function get_border_spacing() {
    return explode(" ", $this->_props["border_spacing"]);
  }

/*==============================*/

  /*
   !important attribute
   For basic functionality of the !important attribute with overloading
   of several styles of an element, changes in inherit(), merge() and _parse_properties()
   are sufficient [helpers var $_important_props, __construct(), important_set(), important_get()]

   Only for combined attributes extra treatment needed. See below.

   div { border: 1px red; }
   div { border: solid; } // Not combined! Only one occurence of same style per context
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
   !important Paramter taken as in the original style in the css file.
   When property border !important given, do not mark subsets like border_style as important. Only
   individual properties.

   Note:
   Setting individual property directly from css with e.g. set_border_top_style() is not needed, because
   missing set funcions handled by a generic handler __set(), including the !important.
   Setting individual property of as sub-property is handled below.

   Implementation see at _set_style_side_type()
   Callers _set_style_sides_type(), _set_style_type, _set_style_type_important()

   Related functionality for background, padding, margin, font, list_style
  */

  /* Generalized set function for individual attribute of combined style.
   * With check for !important
   * Applicable for background, border, padding, margin, font, list_style
   * Note: $type has a leading underscore (or is empty), the others not.
   */
  protected function _set_style_side_type($style, $side, $type, $val, $important) {
    $prop = $style.'_'.$side.$type;
    
    if ( !isset($this->_important_props[$prop]) || $important) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache[$prop] = null;
      if ( $important ) {
        $this->_important_props[$prop] = true;
      }
      $this->_props[$prop] = $val;
    }
  }

  protected function _set_style_sides_type($style,$top,$right,$bottom,$left,$type,$important) {
    $this->_set_style_side_type($style,'top',$type,$top,$important);
    $this->_set_style_side_type($style,'right',$type,$right,$important);
    $this->_set_style_side_type($style,'bottom',$type,$bottom,$important);
    $this->_set_style_side_type($style,'left',$type,$left,$important);
  }

  protected function _set_style_type($style,$type,$val,$important) {
    $val = preg_replace("/\s*\,\s*/", ",", $val); // when rgb() has spaces
    $arr = explode(" ", $val);
    
    switch (count($arr)) {
      case 1: $this->_set_style_sides_type($style,$arr[0],$arr[0],$arr[0],$arr[0],$type,$important); break;
      case 2: $this->_set_style_sides_type($style,$arr[0],$arr[1],$arr[0],$arr[1],$type,$important); break;
      case 3: $this->_set_style_sides_type($style,$arr[0],$arr[1],$arr[2],$arr[1],$type,$important); break;
      case 4: $this->_set_style_sides_type($style,$arr[0],$arr[1],$arr[2],$arr[3],$type,$important); break;
    }
    
    //see __set and __get, on all assignments clear cache!
    $this->_prop_cache[$style.$type] = null;
    $this->_props[$style.$type] = $val;
  }

  protected function _set_style_type_important($style,$type,$val) {
    $this->_set_style_type($style,$type,$val,isset($this->_important_props[$style.$type]));
  }

  /* Anyway only called if _important matches and is assigned
   * E.g. _set_style_side_type($style,$side,'',str_replace("none", "0px", $val),isset($this->_important_props[$style.'_'.$side]));
   */
  protected function _set_style_side_width_important($style,$side,$val) {
    //see __set and __get, on all assignments clear cache!
    $this->_prop_cache[$style.'_'.$side] = null;
    $this->_props[$style.'_'.$side] = str_replace("none", "0px", $val);
  }

  protected function _set_style($style,$val,$important) {
    if ( !isset($this->_important_props[$style]) || $important) {
      if ( $important ) {
        $this->_important_props[$style] = true;
      }
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache[$style] = null;
      $this->_props[$style] = $val;
    }
  }

  protected function _image($val) {
    $DEBUGCSS=DEBUGCSS;
    $parsed_url = "none";
    
    if ( mb_strpos($val, "url") === false ) {
      $path = "none"; //Don't resolve no image -> otherwise would prefix path and no longer recognize as none
    }
    else {
      $val = preg_replace("/url\(['\"]?([^'\")]+)['\"]?\)/","\\1", trim($val));

      // Resolve the url now in the context of the current stylesheet
      $parsed_url = explode_url($val);
      if ( $parsed_url["protocol"] == "" && $this->_stylesheet->get_protocol() == "" ) {
        if ($parsed_url["path"][0] === '/' || $parsed_url["path"][0] === '\\' ) {
          $path = $_SERVER["DOCUMENT_ROOT"].'/';
        }
        else {
          $path = $this->_stylesheet->get_base_path();
        }
        
        $path .= $parsed_url["path"] . $parsed_url["file"];
        $path = realpath($path);
        // If realpath returns FALSE then specifically state that there is no background image
        if ( !$path ) {
          $path = 'none';
        }
      }
      else {
        $path = build_url($this->_stylesheet->get_protocol(),
                          $this->_stylesheet->get_host(),
                          $this->_stylesheet->get_base_path(),
                          $val);
      }
    }
    if ($DEBUGCSS) {
      print "<pre>[_image\n";
      print_r($parsed_url);
      print $this->_stylesheet->get_protocol()."\n".$this->_stylesheet->get_base_path()."\n".$path."\n";
      print "_image]</pre>";;
    }
    return $path;
  }

/*======================*/

  /**
   * Sets color
   *
   * The color parameter can be any valid CSS color value
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-color
   * @param string $color
   */
  function set_color($color) {
    $col = $this->munge_color($color);

    if ( is_null($col) || !isset($col["hex"]) ) {
      $color = "inherit";
    }
    else {
      $color = $col["hex"];
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["color"] = null;
    $this->_props["color"] = $color;
  }

  /**
   * Sets the background color
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-color
   * @param string $color
   */
  function set_background_color($color) {
    $col = $this->munge_color($color);
    
    if ( is_null($col) ) {
      return;
      //$col = self::$_defaults["background_color"];
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["background_color"] = null;
    $this->_props["background_color"] = is_array($col) ? $col["hex"] : $col;
  }

  /**
   * Set the background image url
   * @link     http://www.w3.org/TR/CSS21/colors.html#background-properties
   *
   * @param string $val
   */
  function set_background_image($val) {
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["background_image"] = null;
    $this->_props["background_image"] = $this->_image($val);
  }

  /**
   * Sets the background repeat
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-repeat
   * @param string $val
   */
  function set_background_repeat($val) {
    if ( is_null($val) ) {
      $val = self::$_defaults["background_repeat"];
    }
      
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["background_repeat"] = null;
    $this->_props["background_repeat"] = $val;
  }

  /**
   * Sets the background attachment
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-attachment
   * @param string $val
   */
  function set_background_attachment($val) {
    if ( is_null($val) ) {
      $val = self::$_defaults["background_attachment"];
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["background_attachment"] = null;
    $this->_props["background_attachment"] = $val;
  }

  /**
   * Sets the background position
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-position
   * @param string $val
   */
  function set_background_position($val) {
    if ( is_null($val) ) {
      $val = self::$_defaults["background_position"];
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["background_position"] = null;
    $this->_props["background_position"] = $val;
  }

  /**
   * Sets the background - combined options
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background
   * @param string $val
   */
  function set_background($val) {
    $val = trim($val);
    $important = isset($this->_important_props["background"]);
    
    if ( $val === "none" ) {
      $this->_set_style("background_image", "none", $important);
      $this->_set_style("background_color", "transparent", $important);
    }
    else {
      $pos = array();
      $tmp = preg_replace("/\s*\,\s*/", ",", $val); // when rgb() has spaces
      $tmp = preg_split("/\s+/", $tmp);
      
      foreach($tmp as $attr) {
        if ( mb_substr($attr, 0, 3) === "url" || $attr === "none" ) {
          $this->_set_style("background_image", $this->_image($attr), $important);
        } 
        elseif ( $attr === "fixed" || $attr === "scroll" ) {
          $this->_set_style("background_attachment", $attr, $important);
        } 
        elseif ( $attr === "repeat" || $attr === "repeat-x" || $attr === "repeat-y" || $attr === "no-repeat" ) {
          $this->_set_style("background_repeat", $attr, $important);
        }
        elseif ( ($col = $this->munge_color($attr)) != null ) {
          $this->_set_style("background_color", is_array($col) ? $col["hex"] : $col, $important);
        } 
        else {
          $pos[] = $attr;
        }
      }
      
      if (count($pos)) {
        $this->_set_style("background_position", implode(" ", $pos), $important);
      }
    }
    
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["background"] = null;
    $this->_props["background"] = $val;
  }

  /**
   * Sets the font size
   *
   * $size can be any acceptable CSS size
   *
   * @link http://www.w3.org/TR/CSS21/fonts.html#propdef-font-size
   * @param string|float $size
   */
  function set_font_size($size) {
    $this->__font_size_calculated = false;
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["font_size"] = null;
    $this->_props["font_size"] = $size;
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
   * If individual attribute is !important and explicite or implicite replacement is not,
   * keep individual attribute
   *
   * require whitespace as delimiters for single value attributes
   * On delimiter "/" treat first as font height, second as line height
   * treat all remaining at the end of line as font
   * font-style, font-variant, font-weight, font-size, line-height, font-family
   *
   * missing font-size and font-family might be not allowed, but accept it here and
   * use default (medium size, enpty font name)
   *
   * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style
   * @param $val
   */
  function set_font($val) {
    $this->__font_size_calculated = false;
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["font"] = null;
    $this->_props["font"] = $val;

    $important = isset($this->_important_props["font"]);

    if ( preg_match("/^(italic|oblique|normal)\s*(.*)$/i",$val,$match) ) {
      $this->_set_style("font_style", $match[1], $important);
      $val = $match[2];
    }
    else {
      $this->_set_style("font_style", self::$_defaults["font_style"], $important);
    }

    if ( preg_match("/^(small-caps|normal)\s*(.*)$/i",$val,$match) ) {
      $this->_set_style("font_variant", $match[1], $important);
      $val = $match[2];
    }
    else {
      $this->_set_style("font_variant", self::$_defaults["font_variant"], $important);
    }

    //matching numeric value followed by unit -> this is indeed a subsequent font size. Skip!
    if ( preg_match("/^(bold|bolder|lighter|100|200|300|400|500|600|700|800|900|normal)\s*(.*)$/i", $val, $match) &&
         !preg_match("/^(?:pt|px|pc|em|ex|in|cm|mm|%)/",$match[2])
       ) {
      $this->_set_style("font_weight", $match[1], $important);
      $val = $match[2];
    }
    else {
      $this->_set_style("font_weight", self::$_defaults["font_weight"], $important);
    }

    if ( preg_match("/^(xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger|\d+\s*(?:pt|px|pc|em|ex|in|cm|mm|%))\s*(.*)$/i",$val,$match) ) {
      $this->_set_style("font_size", $match[1], $important);
      $val = $match[2];
      if ( preg_match("/^\/\s*(\d+\s*(?:pt|px|pc|em|ex|in|cm|mm|%))\s*(.*)$/i", $val, $match ) ) {
        $this->_set_style("line_height", $match[1], $important);
        $val = $match[2];
      }
      else {
        $this->_set_style("line_height", self::$_defaults["line_height"], $important);
      }
    }
    else {
      $this->_set_style("font_size", self::$_defaults["font_size"], $important);
      $this->_set_style("line_height", self::$_defaults["line_height"], $important);
    }

    if( strlen($val) != 0 ) {
      $this->_set_style("font_family", $val, $important);
    }
    else {
      $this->_set_style("font_family", self::$_defaults["font_family"], $important);
    }
  }

  /**#@+
   * Sets page break properties
   *
   * @link http://www.w3.org/TR/CSS21/page.html#page-breaks
   * @param string $break
   */
  function set_page_break_before($break) {
    if ( $break === "left" || $break === "right" ) {
      $break = "always";
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["page_break_before"] = null;
    $this->_props["page_break_before"] = $break;
  }

  function set_page_break_after($break) {
    if ( $break === "left" || $break === "right" ) {
      $break = "always";
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["page_break_after"] = null;
    $this->_props["page_break_after"] = $break;
  }
  /**#@-*/
    
  //........................................................................

  /**#@+
   * Sets the margin size
   *
   * @link http://www.w3.org/TR/CSS21/box.html#margin-properties
   * @param $val
   */
  function set_margin_top($val) {
    $this->_set_style_side_width_important('margin','top',$val);
  }

  function set_margin_right($val) {
    $this->_set_style_side_width_important('margin','right',$val);
  }

  function set_margin_bottom($val) {
    $this->_set_style_side_width_important('margin','bottom',$val);
  }

  function set_margin_left($val) {
    $this->_set_style_side_width_important('margin','left',$val);
  }
  
  function set_margin($val) {
    $val = str_replace("none", "0px", $val);
    $this->_set_style_type_important('margin','',$val);
  }
  /**#@-*/

  /**#@+
   * Sets the padding size
   *
   * @link http://www.w3.org/TR/CSS21/box.html#padding-properties
   * @param $val
   */
  function set_padding_top($val) {
    $this->_set_style_side_width_important('padding','top',$val);
  }

  function set_padding_right($val) {
    $this->_set_style_side_width_important('padding','right',$val);
  }

  function set_padding_bottom($val) {
    $this->_set_style_side_width_important('padding','bottom',$val);
  }

  function set_padding_left($val) {
    $this->_set_style_side_width_important('padding','left',$val);
  }

  function set_padding($val) {
    $val = str_replace("none", "0px", $val);
    $this->_set_style_type_important('padding','',$val);
  }
  /**#@-*/

  /**
   * Sets a single border
   *
   * @param string  $side
   * @param string  $border_spec ([width] [style] [color])
   * @param boolean $important
   */
  protected function _set_border($side, $border_spec, $important) {
    $border_spec = preg_replace("/\s*\,\s*/", ",", $border_spec);
    //$border_spec = str_replace(",", " ", $border_spec); // Why did we have this ?? rbg(10, 102, 10) > rgb(10  102  10)
    $arr = explode(" ", $border_spec);

    // FIXME: handle partial values
 
    //For consistency of individal and combined properties, and with ie8 and firefox3
    //reset all attributes, even if only partially given
    $this->_set_style_side_type('border',$side,'_style',self::$_defaults['border_'.$side.'_style'],$important);
    $this->_set_style_side_type('border',$side,'_width',self::$_defaults['border_'.$side.'_width'],$important);
    $this->_set_style_side_type('border',$side,'_color',self::$_defaults['border_'.$side.'_color'],$important);

    foreach ($arr as $value) {
      $value = trim($value);
      if ( in_array($value, self::$BORDER_STYLES) ) {
        $this->_set_style_side_type('border',$side,'_style',$value,$important);
      }
      else if ( preg_match("/[.0-9]+(?:px|pt|pc|em|ex|%|in|mm|cm)|(?:thin|medium|thick)/", $value ) ) {
        $this->_set_style_side_type('border',$side,'_width',$value,$important);
      }
      else {
        // must be color
        $this->_set_style_side_type('border',$side,'_color',$value,$important);
      }
    }

    //see __set and __get, on all assignments clear cache!
    $this->_prop_cache['border_'.$side] = null;
    $this->_props['border_'.$side] = $border_spec;
  }

  /**
   * Sets the border styles
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-properties
   * @param string $val
   */
  function set_border_top($val) {
    $this->_set_border("top", $val, isset($this->_important_props['border_top'])); 
  }

  function set_border_right($val) {
    $this->_set_border("right", $val, isset($this->_important_props['border_right']));
  }
  
  function set_border_bottom($val) {
    $this->_set_border("bottom", $val, isset($this->_important_props['border_bottom']));
  }
  
  function set_border_left($val) {
    $this->_set_border("left", $val, isset($this->_important_props['border_left']));
  }

  function set_border($val) {
    $important = isset($this->_important_props["border"]);
    $this->_set_border("top", $val, $important);
    $this->_set_border("right", $val, $important);
    $this->_set_border("bottom", $val, $important);
    $this->_set_border("left", $val, $important);
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["border"] = null;
    $this->_props["border"] = $val;
  }

  function set_border_width($val) {
    $this->_set_style_type_important('border','_width',$val);
  }

  function set_border_color($val) {
    $this->_set_style_type_important('border','_color',$val);
  }

  function set_border_style($val) {
    $this->_set_style_type_important('border','_style',$val);
  }

  /**
   * Sets the border radius size
   * 
   * http://www.w3.org/TR/css3-background/#corners
   */
  function set_border_top_left_radius($val) {
    $this->_set_border_radius_corner($val, "top_left");
  }
  
  function set_border_top_right_radius($val) {
    $this->_set_border_radius_corner($val, "top_right");
  }
  
  function set_border_bottom_left_radius($val) {
    $this->_set_border_radius_corner($val, "bottom_left");
  }
  
  function set_border_bottom_right_radius($val) {
    $this->_set_border_radius_corner($val, "bottom_right");
  }
  
  function set_border_radius($val) {
    $val = preg_replace("/\s*\,\s*/", ",", $val); // when border-radius has spaces
    $arr = explode(" ", $val);
    
    switch (count($arr)) {
      case 1: $this->_set_border_radii($arr[0],$arr[0],$arr[0],$arr[0]); break;
      case 2: $this->_set_border_radii($arr[0],$arr[1],$arr[0],$arr[1]); break;
      case 3: $this->_set_border_radii($arr[0],$arr[1],$arr[2],$arr[1]); break;
      case 4: $this->_set_border_radii($arr[0],$arr[1],$arr[2],$arr[3]); break;
    }
  }

  protected function _set_border_radii($val1, $val2, $val3, $val4) {
    $this->_set_border_radius_corner($val1, "top_left");
    $this->_set_border_radius_corner($val2, "top_right");
    $this->_set_border_radius_corner($val3, "bottom_right");
    $this->_set_border_radius_corner($val4, "bottom_left");
  }
  
  protected function _set_border_radius_corner($val, $corner) {
    $this->_has_border_radius = true;
    
    //see __set and __get, on all assignments clear cache!
    $this->_prop_cache["border_" . $corner . "_radius"] = null;
    
    $this->_props["border_" . $corner . "_radius"] = $this->length_in_pt($val);
  }

  /**
   * Sets the outline styles
   *
   * @link http://www.w3.org/TR/CSS21/ui.html#dynamic-outlines
   * @param string $val
   */
  function set_outline($val) {
    $important = isset($this->_important_props["outline"]);
    
    $props = array(
      "outline_style", 
      "outline_width", 
      "outline_color",
    );
    
    foreach($props as $prop) {
      $_val = self::$_defaults[$prop];
      
      if ( !isset($this->_important_props[$prop]) || $important) {
        //see __set and __get, on all assignments clear cache!
        $this->_prop_cache[$prop] = null;
        if ( $important ) {
          $this->_important_props[$prop] = true;
        }
        $this->_props[$prop] = $_val;
      }
    }
    
    $val = preg_replace("/\s*\,\s*/", ",", $val); // when rgb() has spaces
    $arr = explode(" ", $val);
    foreach ($arr as $value) {
      $value = trim($value);
      
      if ( in_array($value, self::$BORDER_STYLES) ) {
        $this->set_outline_style($value);
      }
      else if ( preg_match("/[.0-9]+(?:px|pt|pc|em|ex|%|in|mm|cm)|(?:thin|medium|thick)/", $value ) ) {
        $this->set_outline_width($value);
      }
      else {
        // must be color
        $this->set_outline_color($value);
      }
    }
    
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["outline"] = null;
    $this->_props["outline"] = $val;
  }

  function set_outline_width($val) {
    $this->_set_style_type_important('outline','_width',$val);
  }

  function set_outline_color($val) {
    $this->_set_style_type_important('outline','_color',$val);
  }

  function set_outline_style($val) {
    $this->_set_style_type_important('outline','_style',$val);
  }

  /**
   * Sets the border spacing
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-properties
   * @param float $val
   */
  function set_border_spacing($val) {
    $arr = explode(" ", $val);

    if ( count($arr) == 1 ) {
      $arr[1] = $arr[0];
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["border_spacing"] = null;
    $this->_props["border_spacing"] = "$arr[0] $arr[1]";
  }

  /**
   * Sets the list style image
   *
   * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style-image
   * @param $val
   */
  function set_list_style_image($val) {
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["list_style_image"] = null;
    $this->_props["list_style_image"] = $this->_image($val);
  }

  /**
   * Sets the list style
   *
   * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style
   * @param $val
   */
  function set_list_style($val) {
    $important = isset($this->_important_props["list_style"]);
    $arr = explode(" ", str_replace(",", " ", $val));

    static $types = array(
      "disc", "circle", "square", 
      "decimal-leading-zero", "decimal", "1",
      "lower-roman", "upper-roman", "a", "A",
      "lower-greek", 
      "lower-latin", "upper-latin", 
      "lower-alpha", "upper-alpha", 
      "armenian", "georgian", "hebrew",
      "cjk-ideographic", "hiragana", "katakana",
      "hiragana-iroha", "katakana-iroha", "none"
    );

    static $positions = array("inside", "outside");

    foreach ($arr as $value) {
      /* http://www.w3.org/TR/CSS21/generate.html#list-style
       * A value of 'none' for the 'list-style' property sets both 'list-style-type' and 'list-style-image' to 'none'
       */
      if ( $value === "none" ) {
         $this->_set_style("list_style_type", $value, $important);
         $this->_set_style("list_style_image", $value, $important);
        continue;
      }

      //On setting or merging or inheriting list_style_image as well as list_style_type,
      //and url exists, then url has precedence, otherwise fall back to list_style_type
      //Firefox is wrong here (list_style_image gets overwritten on explicite list_style_type)
      //Internet Explorer 7/8 and dompdf is right.
       
      if ( mb_substr($value, 0, 3) === "url" ) {
        $this->_set_style("list_style_image", $this->_image($value), $important);
        continue;
      }

      if ( in_array($value, $types) ) {
        $this->_set_style("list_style_type", $value, $important);
      }
      else if ( in_array($value, $positions) ) {
        $this->_set_style("list_style_position", $value, $important);
      }
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["list_style"] = null;
    $this->_props["list_style"] = $val;
  }
  
  function set_size($val) {
    $length_re = "/(\d+\s*(?:pt|px|pc|em|ex|in|cm|mm|%))/";

    $val = mb_strtolower($val);
    
    if ( $val === "auto" ) {
      return;
    }
        
    $parts = preg_split("/\s+/", $val);
    
    $computed = array();
    if ( preg_match($length_re, $parts[0]) ) {
      $computed[] = $this->length_in_pt($parts[0]);
      
      if ( isset($parts[1]) && preg_match($length_re, $parts[1]) ) {
        $computed[] = $this->length_in_pt($parts[1]);
      }
      else {
        $computed[] = $computed[0];
      }
    }
    elseif ( isset(CPDF_Adapter::$PAPER_SIZES[$parts[0]]) ) {
      $computed = array_slice(CPDF_Adapter::$PAPER_SIZES[$parts[0]], 2, 2);
      
      if ( isset($parts[1]) && $parts[1] === "landscape" ) {
        $computed = array_reverse($computed);
      }
    }
    else {
      return;
    }
    
    $this->_props["size"] = $computed;
  }
  
  /**
   * Sets the CSS3 transform property
   *
   * @link http://www.w3.org/TR/css3-2d-transforms/#transform-property
   * @param string $val
   */
  function set_transform($val) {
    $number   = "\s*([^,\s]+)\s*";
    $tr_value = "\s*([^,\s]+)\s*";
    $angle    = "\s*([^,\s]+(?:deg|rad)?)\s*";
    
    if ( !preg_match_all("/[a-z]+\([^\)]+\)/i", $val, $parts, PREG_SET_ORDER) ) {
      return;
    }
    
    $functions = array(
      //"matrix"     => "\($number,$number,$number,$number,$number,$number\)",
    
      "translate"  => "\($tr_value(?:,$tr_value)?\)",
      "translateX" => "\($tr_value\)",
      "translateY" => "\($tr_value\)",
    
      "scale"      => "\($number(?:,$number)?\)",
      "scaleX"     => "\($number\)",
      "scaleY"     => "\($number\)",
    
      "rotate"     => "\($angle\)",
    
      "skew"       => "\($angle(?:,$angle)?\)",
      "skewX"      => "\($angle\)",
      "skewY"      => "\($angle\)",
    );
    
    $transforms = array();
    
    foreach($parts as $part) {
      $t = $part[0];
      
      foreach($functions as $name => $pattern) {
        if ( preg_match("/$name\s*$pattern/i", $t, $matches) ) {
          $values = array_slice($matches, 1);
          
          switch($name) {
            // <angle> units
            case "rotate":
            case "skew":
            case "skewX":
            case "skewY":
              
              foreach($values as $i => $value) {
                if ( strpos($value, "rad") ) {
                  $values[$i] = rad2deg(floatval($value));
                }
                else {
                  $values[$i] = floatval($value);
                }
              }
              
              switch($name) {
                case "skew":
                  if ( !isset($values[1]) ) {
                    $values[1] = 0;
                  }
                break;
                case "skewX":
                  $name = "skew";
                  $values = array($values[0], 0);
                break;
                case "skewY":
                  $name = "skew";
                  $values = array(0, $values[0]);
                break;
              }
            break;
            
            // <translation-value> units
            case "translate":
              $values[0] = $this->length_in_pt($values[0], $this->width);
              
              if ( isset($values[1]) ) {
                $values[1] = $this->length_in_pt($values[1], $this->height);
              }
              else {
                $values[1] = 0;
              }
            break;
            
            case "translateX":
              $name = "translate";
              $values = array($this->length_in_pt($values[0], $this->width), 0);
            break;
            
            case "translateY":
              $name = "translate";
              $values = array(0, $this->length_in_pt($values[0], $this->height));
            break;
            
            // <number> units
            case "scale":
              if ( !isset($values[1]) ) {
                $values[1] = $values[0];
              }
            break;
            
            case "scaleX":
              $name = "scale";
              $values = array($values[0], 1.0);
            break;
            
            case "scaleY":
              $name = "scale";
              $values = array(1.0, $values[0]);
            break;
          }
          
          $transforms[] = array(
            $name, 
            $values,
          );
        }
      }
    }
    
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["transform"] = null;
    $this->_props["transform"] = $transforms;
  }
  
  function set__webkit_transform($val) {
    $this->set_transform($val);
  }
  
  function set__webkit_transform_origin($val) {
    $this->set_transform_origin($val);
  }
  
  /**
   * Sets the CSS3 transform-origin property
   *
   * @link http://www.w3.org/TR/css3-2d-transforms/#transform-origin
   * @param string $val
   */
  function set_transform_origin($val) {
    $values = preg_split("/\s+/", $val);
    
    if ( count($values) === 0) {
      return;
    }
    
    foreach($values as &$value) {
      if ( in_array($value, array("top", "left")) ) {
        $value = 0;
      }
      
      if ( in_array($value, array("bottom", "right")) ) {
        $value = "100%";
      }
    }
    
    if ( !isset($values[1]) ) {
      $values[1] = $values[0];
    }
    
    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
    $this->_prop_cache["transform_origin"] = null;
    $this->_props["transform_origin"] = $values;
  }
  
  protected function parse_image_resolution($val) {
    // If exif data could be get: 
    // $re = '/^\s*(\d+|normal|auto)(?:\s*,\s*(\d+|normal))?\s*$/';
    
    $re = '/^\s*(\d+|normal|auto)\s*$/';
    
    if ( !preg_match($re, $val, $matches) ) {
      return null;
    }
    
    return $matches[1];
  }
  
  // auto | normal | dpi
  function set_background_image_resolution($val) {
    $parsed = $this->parse_image_resolution($val);
    
    $this->_prop_cache["background_image_resolution"] = null;
    $this->_props["background_image_resolution"] = $parsed;
  }
  
  // auto | normal | dpi
  function set_image_resolution($val) {
    $parsed = $this->parse_image_resolution($val);
    
    $this->_prop_cache["image_resolution"] = null;
    $this->_props["image_resolution"] = $parsed;
  }
  
  function set__dompdf_background_image_resolution($val) {
    $this->set_background_image_resolution($val);
  }
  
  function set__dompdf_image_resolution($val) {
    $this->set_image_resolution($val);
  }

  function set_z_index($val) {
    if ( round($val) != $val && $val !== "auto" ) {
      return;
    }

    $this->_prop_cache["z_index"] = null;
    $this->_props["z_index"] = $val;
  }
  
  function set_counter_increment($val) {
    $val = trim($val);
    $value = null;
    
    if ( in_array($val, array("none", "inherit")) ) {
      $value = $val;
    }
    else {
      if ( preg_match_all("/(".self::CSS_IDENTIFIER.")(?:\s+(".self::CSS_INTEGER."))?/", $val, $matches, PREG_SET_ORDER) ){
        $value = array();
        foreach($matches as $match) {
          $value[$match[1]] = isset($match[2]) ? $match[2] : 1;
        }
      }
    }

    $this->_prop_cache["counter_increment"] = null;
    $this->_props["counter_increment"] = $value;
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
  function __toString() {
    return print_r(array_merge(array("parent_font_size" => $this->_parent_font_size),
                               $this->_props), true);
  }

/*DEBUGCSS*/  function debug_print() {
/*DEBUGCSS*/    print "parent_font_size:".$this->_parent_font_size . ";\n";
/*DEBUGCSS*/    foreach($this->_props as $prop => $val ) {
/*DEBUGCSS*/      print $prop.':'.$val;
/*DEBUGCSS*/      if (isset($this->_important_props[$prop])) {
/*DEBUGCSS*/        print '!important';
/*DEBUGCSS*/      }
/*DEBUGCSS*/      print ";\n";
/*DEBUGCSS*/    }
/*DEBUGCSS*/  }
}
