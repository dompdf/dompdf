<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: style.cls.php,v $
 * Created on: 2004-06-01
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.digitaljunkies.ca/dompdf
 *
 * @link http://www.digitaljunkies.ca/dompdf
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf
 * @version 0.5.1
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 0.5.1.htischer.20090507
 * - Fix px to pt conversion according to DOMPDF_DPI
 * - Recognize css styles with !important attribute, and store !important attribute within style
 * - Propagate !important by inherit and sequences of styles with merge.
 * - Add missing style property cache flushes for consistent rendering, e.g. on explicte assignments
 * - Add important set/get for access from outside of class
 * - Fix font_family search path with multiple fonts list in css attribute:
 *   On missing font, do not immediately fall back to default font,
 *   but try subsequent fonts in search chain. Only when none found, explicitely
 *   refer to default font.
 * - Allow read of background individual properties
 * - Add support for individual styles background-position, background-attachment, background-repeat
 * - Complete style components of list-style
 * - Add support for combined styles in addition to individual styles
 *   like {border: red 1px solid;}, { border-width: 1px;}, { border-right-color: red; } ...
 *   for font, background
 * - Propagate attributes including !important from combined style to individual component
 *   for border, background, padding, margin, font, list_style
 * - Refactor common code of border, background, padding, margin, font, list_style
 * - Refactor common code of list-style-image and background-image
 * - special treatment of css images "none" instead of url(...), otherwise would prepend string "none" with path name
 * - Added comments
 * - Added debug output
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version dompdf_trunk_with_helmut_mods.20090524
 * - Allow superflous white space and string delimiter in font search path.
 * - Restore lost change of default font of above
 * @version 20090610
 * - Allow absolute path from web server root as html image reference
 * - More accurate handling of css property cache consistency
 */

/* $Id: style.cls.php,v 1.22 2008-03-12 06:35:43 benjcarson Exp $ */

/**
 * Represents CSS properties.
 *
 * The Style class is responsible for handling and storing CSS properties.
 * It includes methods to resolve colours and lengths, as well as getters &
 * setters for many CSS properites.
 *
 * Actual CSS parsing is performed in the {@link Stylesheet} class.
 *
 * @package dompdf
 */
class Style {

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
  static $BLOCK_TYPES = array("block","inline-block", "table-cell", "list-item");

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
  
  // private members
  /**
   * True once the font size is resolved absolutely
   *
   * @var bool
   */
  private $__font_size_calculated; // Cache flag
  
  /**
   * Class constructor
   *
   * @param Stylesheet $stylesheet the stylesheet this Style is associated with.
   */
  function __construct(Stylesheet $stylesheet) {
    $this->_props = array();
    $this->_important_props = array();
    $this->_stylesheet = $stylesheet;
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
      $d["font_family"] = "serif";
      $d["font_size"] = "medium";
      $d["font_style"] = "normal";
      $d["font_variant"] = "normal";
      $d["font_weight"] = "normal";
      $d["font"] = "";
      $d["height"] = "auto";
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
      $d["outline_color"] = "invert";
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

      // Properties that inherit by default
      self::$_inherited = array("azimuth",
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
                                 "widows",
                                 "word_spacing");
    }

  }

  /**
   * "Destructor": forcibly free all references held by this object
   */
  function dispose() {
    unset($this->_stylesheet);
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

    if ( !is_array($length) )
      $length = array($length);

    if ( !isset($ref_size) )
      $ref_size = self::$default_font_size;

    $ret = 0;
    foreach ($length as $l) {

      if ( $l === "auto" ) 
        return "auto";
      
      if ( $l === "none" )
        return "none";

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
      
      if ( ($i = mb_strpos($l, "pt"))  !== false ) {
        $ret += mb_substr($l, 0, $i);
        continue;
      }

      if ( ($i = mb_strpos($l, "px"))  !== false ) {
        $ret += ( mb_substr($l, 0, $i)  * 72 ) / DOMPDF_DPI;
        continue;
      }

      if ( ($i = mb_strpos($l, "em"))  !== false ) {
        $ret += mb_substr($l, 0, $i) * $this->__get("font_size");
        continue;
      }
      
      // FIXME: em:ex ratio?
      if ( ($i = mb_strpos($l, "ex"))  !== false ) {
        $ret += mb_substr($l, 0, $i) * $this->__get("font_size");
        continue;
      }
      
      if ( ($i = mb_strpos($l, "%"))  !== false ) {
        $ret += mb_substr($l, 0, $i)/100 * $ref_size;
        continue;
      }
      
      if ( ($i = mb_strpos($l, "in")) !== false ) {
        $ret += mb_substr($l, 0, $i) * 72;
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
          
      if ( ($i = mb_strpos($l, "pc")) !== false ) {
        $ret += mb_substr($l, 0, $i) / 12;
        continue;
      }
          
      // Bogus value
      $ret += $ref_size;
    }

    return $ret;
  }

  
  /**
   * Set inherited properties in this style using values in $parent
   *
   * @param Style $parent
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
      
    foreach (array_keys($this->_props) as $prop) {
      if ( $this->_props[$prop] == "inherit" ) {
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
        $this->$prop = $parent->$prop;
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
 	  } else if ( !isset($this->_important_props[$prop]) ) {
        //see __set and __get, on all assignments clear cache!
		$this->_prop_cache[$prop] = null;
 	    $this->_props[$prop] = $val;
 	  }
 	}

    if ( isset($style->_props["font_size"]) )
      $this->__font_size_calculated = false;    
  }

  
  /**
   * Returns an array(r, g, b, "r"=> r, "g"=>g, "b"=>b, "hex"=>"#rrggbb")
   * based on the provided CSS colour value.
   *
   * @param string $colour
   * @return array
   */
  function munge_colour($colour) {
    if ( is_array($colour) )
      // Assume the array has the right format...
      // FIXME: should/could verify this.
      return $colour;
    
    $r = 0;
    $g = 0;
    $b = 0;

    // Handle colour names
    switch ($colour) {

    case "maroon":
      $r = 0x80;
      break;

    case "red":
      $r = 0xff;
      break;

    case "orange":
      $r = 0xff;
      $g = 0xa5;
      break;

    case "yellow":
      $r = 0xff;
      $g = 0xff;
      break;

    case "olive":
      $r = 0x80;
      $g = 0x80;
      break;

    case "purple":
      $r = 0x80;
      $b = 0x80;
      break;

    case "fuchsia":
      $r = 0xff;
      $b = 0xff;
      break;

    case "white":
      $r = $g = $b = 0xff;
      break;

    case "lime":
      $g = 0xff;
      break;

    case "green":
      $g = 0x80;
      break;

    case "navy":
      $b = 0x80;
      break;

    case "blue":
      $b = 0xff;
      break;

    case "aqua":
      $g = 0xff;
      $b = 0xff;
      break;

    case "teal":
      $g = 0x80;
      $b = 0x80;
      break;

    case "black":
      break;

    case "sliver":
      $r = $g = $b = 0xc0;
      break;

    case "gray":
    case "grey":
      $r = $g = $b = 0x80;
      break;

    case "transparent":
      return "transparent";
      
    default:
      if ( mb_strlen($colour) == 4 && $colour{0} == "#" ) {
        // #rgb format
        $r = hexdec($colour{1} . $colour{1});
        $g = hexdec($colour{2} . $colour{2});
        $b = hexdec($colour{3} . $colour{3});

      } else if ( mb_strlen($colour) == 7 && $colour{0} == "#" ) {
        // #rrggbb format
        $r = hexdec(mb_substr($colour, 1, 2));
        $g = hexdec(mb_substr($colour, 3, 2));
        $b = hexdec(mb_substr($colour, 5, 2));

      } else if ( mb_strpos($colour, "rgb") !== false ) {
        // rgb( r,g,b ) format
        $i = mb_strpos($colour, "(");
        $j = mb_strpos($colour, ")");
        
        // Bad colour value
        if ($i === false || $j === false)
          return null;

        $triplet = explode(",", mb_substr($colour, $i+1, $j-$i-1));

        if (count($triplet) != 3)
          return null;
        
        foreach (array_keys($triplet) as $c) {
          $triplet[$c] = trim($triplet[$c]);
          
          if ( $triplet[$c]{mb_strlen($triplet[$c]) - 1} == "%" ) 
            $triplet[$c] = round($triplet[$c] * 0.255);
        }

        list($r, $g, $b) = $triplet;

      } else {
        // Who knows?
        return null;
      }
      
      // Clip to 0 - 1
      $r = $r < 0 ? 0 : ($r > 255 ? 255 : $r);
      $g = $g < 0 ? 0 : ($g > 255 ? 255 : $g);
      $b = $b < 0 ? 0 : ($b > 255 ? 255 : $b);
      break;
      
    }
    
    // Form array
    $arr = array(0 => $r / 0xff, 1 => $g / 0xff, 2 => $b / 0xff,
                 "r"=>$r / 0xff, "g"=>$g / 0xff, "b"=>$b / 0xff,
                 "hex" => sprintf("#%02X%02X%02X", $r, $g, $b));
    return $arr;
      
  }

  
  /**
   * Alias for {@link Style::munge_colour()}
   *
   * @param string $color
   * @return array
   */
  function munge_color($color) { return $this->munge_colour($color); }

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
      isset($this->_important_props[$prop]);
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
    global $_dompdf_warnings;

    $prop = str_replace("-", "_", $prop);
    $this->_prop_cache[$prop] = null;
    
    if ( !isset(self::$_defaults[$prop]) ) {
      $_dompdf_warnings[] = "'$prop' is not a valid CSS2 property.";
      return;
    }
    
    if ( $prop !== "content" && is_string($val) && mb_strpos($val, "url") === false ) {
      $val = mb_strtolower(trim(str_replace(array("\n", "\t"), array(" "), $val)));
      $val = preg_replace("/([0-9]+) (pt|px|pc|em|ex|in|cm|mm|%)/S", "\\1\\2", $val);
    }
    
    $method = "set_$prop";

    if ( method_exists($this, $method) )
      $this->$method($val);
    else 
      $this->_props[$prop] = $val;
    
  }

  /**
   * PHP5 overloaded getter
   *
   * Along with {@link Style::__set()} __get() provides access to all CSS
   * properties directly.  Typically __get() is not called directly outside
   * of this class.
   *
   * On each modification clear cache to return accurate setting.
   * Also affects direct settings not using __set
   *
   * @param string $prop
   * @return mixed
   */
  function __get($prop) {
    
    if ( !isset(self::$_defaults[$prop]) ) 
      throw new DOMPDF_Exception("'$prop' is not a valid CSS2 property.");

    if ( isset($this->_prop_cache[$prop]) && $this->_prop_cache[$prop] != null)
      return $this->_prop_cache[$prop];
    
    $method = "get_$prop";

    // Fall back on defaults if property is not set
    if ( !isset($this->_props[$prop]) )
      $this->_props[$prop] = self::$_defaults[$prop];

    if ( method_exists($this, $method) )
      return $this->_prop_cache[$prop] = $this->$method();


    return $this->_prop_cache[$prop] = $this->_props[$prop];
  }


  /**
   * Getter for the 'font-family' CSS property.
   *
   * Uses the {@link Font_Metrics} class to resolve the font family into an
   * actual font file.
   *
   * @link http://www.w3.org/TR/CSS21/fonts.html#propdef-font-family
   * @return string
   */
  function get_font_family() {
  
    $DEBUGCSS=DEBUGCSS; //=DEBUGCSS; Allow override of global setting for ad hoc debug
	
    // Select the appropriate font.  First determine the subtype, then check
    // the specified font-families for a candidate.

    // Resolve font-weight
    $weight = $this->__get("font_weight");
    
    if ( is_numeric($weight) ) {

      if ( $weight < 600 )
        $weight = "normal";
      else
        $weight = "bold";

    } else if ( $weight == "bold" || $weight == "bolder" ) {
      $weight = "bold";

    } else {
      $weight = "normal";

    }

    // Resolve font-style
    $font_style = $this->__get("font_style");

    if ( $weight == "bold" && ($font_style == "italic" || $font_style == "oblique") )
      $subtype = "bold_italic";
    else if ( $weight == "bold" && $font_style != "italic" && $font_style != "oblique" )
      $subtype = "bold";
    else if ( $weight != "bold" && ($font_style == "italic" || $font_style == "oblique") )
      $subtype = "italic";
    else
      $subtype = "normal";
    
    // Resolve the font family
    if ($DEBUGCSS) {
      print "<pre>[get_font_family:";
      print '('.$this->_props["font_family"].'.'.$font_style.'.'.$this->__get("font_weight").'.'.$weight.'.'.$subtype.')';
    }
    $families = explode(",", $this->_props["font_family"]);
    $families = array_map('trim',$families);
    reset($families);

    $font = null;
    while ( current($families) ) {
      list(,$family) = each($families);
      //remove leading and trailing string delimiters, e.g. on font names with spaces;
      //remove leading and trailing whitespace
      $family=trim($family," \t\n\r\x0B\"'");
      if ($DEBUGCSS) print '('.$family.')';
      $font = Font_Metrics::get_font($family, $subtype);

      if ( $font ) {
        if ($DEBUGCSS)  print '('.$font.")get_font_family]\n</pre>";
        return $font;
      }
    }

    $family = null;
    if ($DEBUGCSS)  print '(default)';
    $font = Font_Metrics::get_font($family, $subtype);

    if ( $font ) {
      if ($DEBUGCSS) print '('.$font.")get_font_family]\n</pre>";
      return $font;
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

    if ( $this->__font_size_calculated )
      return $this->_props["font_size"];
    
    if ( !isset($this->_props["font_size"]) )
      $fs = self::$_defaults["font_size"];
    else 
      $fs = $this->_props["font_size"];
    
    if ( !isset($this->_parent_font_size) )
      $this->_parent_font_size = self::$default_font_size;
    
    switch ($fs) {
      
    case "xx-small":
      $fs = 3/5 * $this->_parent_font_size;
      break;

    case "x-small":
      $fs = 3/4 * $this->_parent_font_size;
      break;

    case "smaller":
    case "small":
      $fs = 8/9 * $this->_parent_font_size;
      break;

    case "medium":
      $fs = $this->_parent_font_size;
      break;

    case "larger":
    case "large":
      $fs = 6/5 * $this->_parent_font_size;
      break;

    case "x-large":
      $fs = 3/2 * $this->_parent_font_size;
      break;

    case "xx-large":
      $fs = 2/1 * $this->_parent_font_size;
      break;

    default:
      break;
    }

    // Ensure relative sizes resolve to something
    if ( ($i = mb_strpos($fs, "em")) !== false ) 
      $fs = mb_substr($fs, 0, $i) * $this->_parent_font_size;

    else if ( ($i = mb_strpos($fs, "ex")) !== false ) 
      $fs = mb_substr($fs, 0, $i) * $this->_parent_font_size;

    else
      $fs = $this->length_in_pt($fs);

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
    if ( $this->_props["word_spacing"] === "normal" )
      return 0;

    return $this->_props["word_spacing"];
  }

  /**
   * @link http://www.w3.org/TR/CSS21/visudet.html#propdef-line-height
   * @return float
   */
  function get_line_height() {
    if ( $this->_props["line_height"] === "normal" )
      return self::$default_line_height * $this->get_font_size();

    if ( is_numeric($this->_props["line_height"]) ) 
      return $this->length_in_pt( $this->_props["line_height"] . "%", $this->get_font_size());
    
    return $this->length_in_pt( $this->_props["line_height"], $this->get_font_size() );
  }

  /**
   * Returns the colour as an array
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
   * Returns the background colour as an array
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
        if ( $tmp[0] == "left" || $tmp[0] == "right" || $tmp[0] == "center" )
          $y = "50%";
        else
          $x = "50%";
        break;
        
      default:
        $y = $tmp[1];
        break;
      }

    } else {
      $y = "50%";
    }

    if ( !isset($x) )
      $x = "0%";

    if ( !isset($y) )
      $y = "0%";

    return array( 0 => $x, "x" => $x,
                  1 => $y, "y" => $y );
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
   * Returns the border colour as an array
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
    return $this->munge_color($this->_props["border_bottom_color"]);;
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
    return array("top" => array("width" => $this->__get("border_top_width"),
                                "style" => $this->__get("border_top_style"),
                                "color" => $this->__get("border_top_color")),
                 "bottom" => array("width" => $this->__get("border_bottom_width"),
                                   "style" => $this->__get("border_bottom_style"),
                                   "color" => $this->__get("border_bottom_color")),
                 "right" => array("width" => $this->__get("border_right_width"),
                                  "style" => $this->__get("border_right_style"),
                                  "color" => $this->__get("border_right_color")),
                 "left" => array("width" => $this->__get("border_left_width"),
                                 "style" => $this->__get("border_left_style"),
                                 "color" => $this->__get("border_left_color")));
  }

  /**
   * Return a single border property
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
  function get_border_top() { return $this->_get_border("top"); }
  function get_border_right() { return $this->_get_border("right"); }
  function get_border_bottom() { return $this->_get_border("bottom"); }
  function get_border_left() { return $this->_get_border("left"); }
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
   Since not known who is initiating the override, need to get passed !importan as parameter.
   !important Paramter taken as in the original style in the css file.
   When poperty border !important given, do not mark subsets like border_style as important. Only
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
  protected function _set_style_side_type($style,$side,$type,$val,$important) {
    if ( !isset($this->_important_props[$style.'_'.$side.$type]) || $important) {
      //see __set and __get, on all assignments clear cache!
      $this->_prop_cache[$style.'_'.$side.$type] = null;
      if ($important) {
        $this->_important_props[$style.'_'.$side.$type] = true;
      }
      $this->_props[$style.'_'.$side.$type] = $val;
    }
  }

  protected function _set_style_sides_type($style,$top,$right,$bottom,$left,$type,$important) {
      $this->_set_style_side_type($style,'top',$type,$top,$important);
      $this->_set_style_side_type($style,'right',$type,$right,$important);
      $this->_set_style_side_type($style,'bottom',$type,$bottom,$important);
      $this->_set_style_side_type($style,'left',$type,$left,$important);
  }

  protected function _set_style_type($style,$type,$val,$important) {
    $arr = explode(" ", $val);
    switch (count($arr)) {
    case 1:
      $this->_set_style_sides_type($style,$arr[0],$arr[0],$arr[0],$arr[0],$type,$important);
      break;
    case 2:
      $this->_set_style_sides_type($style,$arr[0],$arr[1],$arr[0],$arr[1],$type,$important);
      break;
    case 3:
      $this->_set_style_sides_type($style,$arr[0],$arr[1],$arr[1],$arr[2],$type,$important);
      break;
    case 4:
      $this->_set_style_sides_type($style,$arr[0],$arr[1],$arr[2],$arr[3],$type,$important);
      break;
    default:
      break;
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
      if ($important) {
        $this->_important_props[$style] = true;
      }
      //see __set and __get, on all assignments clear cache!
	  $this->_prop_cache[$style] = null;
      $this->_props[$style] = $val;
    }
  }

  protected function _image($val) {
    $DEBUGCSS=DEBUGCSS;
    
    if ( mb_strpos($val, "url") === false ) {
      $path = "none"; //Don't resolve no image -> otherwise would prefix path and no longer recognize as none
    }
    else {
      $val = preg_replace("/url\(['\"]?([^'\")]+)['\"]?\)/","\\1", trim($val));

      // Resolve the url now in the context of the current stylesheet
      $parsed_url = explode_url($val);
      if ( $parsed_url["protocol"] == "" && $this->_stylesheet->get_protocol() == "" ) {
        if ($parsed_url["path"]{0} == '/' || $parsed_url["path"]{0} == '\\' ) {
          $path = $_SERVER["DOCUMENT_ROOT"].'/';
        } else {
          $path = $this->_stylesheet->get_base_path();
        }
        $path .= $parsed_url["path"] . $parsed_url["file"];
        $path = dompdf_realpath($path);
      } else {
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
   * Sets colour
   *
   * The colour parameter can be any valid CSS colour value
   *   
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-color
   * @param string $colour
   */
  function set_color($colour) {
    $col = $this->munge_colour($colour);

    if ( is_null($col) )
      $col = self::$_defaults["color"];

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
	$this->_prop_cache["color"] = null;
    $this->_props["color"] = $col["hex"];
  }

  /**
   * Sets the background colour
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#propdef-background-color
   * @param string $colour
   */
  function set_background_color($colour) {
    $col = $this->munge_colour($colour);
    if ( is_null($col) )
      $col = self::$_defaults["background_color"];

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
	$this->_prop_cache["background_color"] = null;
    $this->_props["background_color"] = is_array($col) ? $col["hex"] : $col;
  }

  /**
   * Set the background image url
   *
   * @link http://www.w3.org/TR/CSS21/colors.html#background-properties
   * @param string $url
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
    if ( is_null($val) )
      $val = self::$_defaults["background_repeat"];
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
    if ( is_null($val) )
      $val = self::$_defaults["background_attachment"];

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
    if ( is_null($val) )
      $val = self::$_defaults["background_position"];

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
    $col = null;
    $pos = array();
    $tmp = explode(" ", $val);
    $important = isset($this->_important_props["background"]);
    foreach($tmp as $attr) {
	  if (mb_substr($attr, 0, 3) == "url" || $attr == "none") {
   	    $this->_set_style("background_image", $this->_image($attr), $important);
  	  } else if ($attr == "fixed" || $attr == "scroll") {
   	    $this->_set_style("background_attachment", $attr, $important);
  	  } else if ($attr == "repeat" || $attr == "repeat-x" || $attr == "repeat-y" || $attr == "no-repeat") {
   	    $this->_set_style("background_repeat", $attr, $important);
      } else if (($col = $this->munge_color($attr)) != null ) {
   	    $this->_set_style("background_color", is_array($col) ? $col["hex"] : $col, $important);
      } else {
 		$pos[] = $attr;
 	  }
 	}
 	if (count($pos)) {
 	  $this->_set_style("background_position",implode(' ',$pos), $important);
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
	} else {
		$this->_set_style("font_style", self::$_defaults["font_style"], $important);
	}

	if ( preg_match("/^(small-caps|normal)\s*(.*)$/i",$val,$match) ) {
		$this->_set_style("font_variant", $match[1], $important);
		$val = $match[2];
	} else {
		$this->_set_style("font_variant", self::$_defaults["font_variant"], $important);
	}

    //matching numeric value followed by unit -> this is indeed a subsequent font size. Skip!
	if ( preg_match("/^(bold|bolder|lighter|100|200|300|400|500|600|700|800|900|normal)\s*(.*)$/i",$val,$match) &&
         !preg_match("/^(?:pt|px|pc|em|ex|in|cm|mm|%)/",$match[2])
	   ) {
		$this->_set_style("font_weight", $match[1], $important);
		$val = $match[2];
	} else {
		$this->_set_style("font_weight", self::$_defaults["font_weight"], $important);
	}

	if ( preg_match("/^(xx-small|x-small|small|medium|large|x-large|xx-large|smaller|larger|\d+\s*(?:pt|px|pc|em|ex|in|cm|mm|%))\s*(.*)$/i",$val,$match) ) {
		$this->_set_style("font_size", $match[1], $important);
		$val = $match[2];
        if (preg_match("/^\/\s*(\d+\s*(?:pt|px|pc|em|ex|in|cm|mm|%))\s*(.*)$/i",$val,$match) ) {
			$this->_set_style("line_height", $match[1], $important);
			$val = $match[2];
        } else {
			$this->_set_style("line_height", self::$_defaults["line_height"], $important);
        }
	} else {
 		$this->_set_style("font_size", self::$_defaults["font_size"], $important);
		$this->_set_style("line_height", self::$_defaults["line_height"], $important);
	}

	if(strlen($val) != 0) {
	  $this->_set_style("font_family", $val, $important);
	} else {
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
    if ($break === "left" || $break === "right")
      $break = "always";

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
	$this->_prop_cache["page_break_before"] = null;
    $this->_props["page_break_before"] = $break;
  }

  function set_page_break_after($break) {
    if ($break === "left" || $break === "right")
      $break = "always";

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
   * @param string $side
   * @param string $border_spec  ([width] [style] [color])
   */
  protected function _set_border($side, $border_spec, $important) {
    $border_spec = str_replace(",", " ", $border_spec);
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

      } else if ( preg_match("/[.0-9]+(?:px|pt|pc|em|ex|%|in|mm|cm)|(?:thin|medium|thick)/", $value ) ) {
        $this->_set_style_side_type('border',$side,'_width',$value,$important);

      } else {
        // must be colour
        $this->_set_style_side_type('border',$side,'_color',$value,$important);
      }
    }

    //see __set and __get, on all assignments clear cache!
	$this->_prop_cache['border_'.$side] = null;
    $this->_props['border_'.$side] = $border_spec;
  }

  /**#@+
   * Sets the border styles
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-properties
   * @param string $val
   */
  function set_border_top($val) { $this->_set_border("top", $val, isset($this->_important_props['border_top'])); }
  function set_border_right($val) { $this->_set_border("right", $val, isset($this->_important_props['border_right'])); }
  function set_border_bottom($val) { $this->_set_border("bottom", $val, isset($this->_important_props['border_bottom'])); }
  function set_border_left($val) { $this->_set_border("left", $val, isset($this->_important_props['border_left'])); }

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
  /**#@-*/


  /**
   * Sets the border spacing
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-properties
   * @param float $val
   */
  function set_border_spacing($val) {

    $arr = explode(" ", $val);

    if ( count($arr) == 1 )
      $arr[1] = $arr[0];

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
	$this->_prop_cache["border_spacing"] = null;
    $this->_props["border_spacing"] = $arr[0] . " " . $arr[1];
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

    static $types = array("disc", "circle", "square", "decimal",
                   "decimal-leading-zero", "lower-roman",
                   "upper-roman", "lower-greek", "lower-latin",
                   "upper-latin", "armenian", "georgian",
                   "lower-alpha", "upper-alpha", "hebrew",
                   "cjk-ideographic", "hiragana", "katakana",
                   "hiragana-iroha", "katakana-iroha", "none");

    static $positions = array("inside", "outside");

    foreach ($arr as $value) {
      /* http://www.w3.org/TR/CSS21/generate.html#list-style
       * A value of 'none' for the 'list-style' property sets both 'list-style-type' and 'list-style-image' to 'none'
       */
      if ($value == "none") {
   	    $this->_set_style("list_style_type", $value, $important);
   	    $this->_set_style("list_style_image", $value, $important);
        continue;
      }

      //On setting or merging or inheriting list_style_image as well as list_style_type,
      //and url exists, then url has precedence, otherwise fall back to list_style_type
      //Firefox is wrong here (list_style_image gets overwritten on explicite list_style_type)
      //Internet Explorer 7/8 and dompdf is right.
       
	  if (mb_substr($value, 0, 3) == "url") {
   	    $this->_set_style("list_style_image", $this->_image($value), $important);
        continue;
      }

      if ( in_array($value, $types) ) {
   	    $this->_set_style("list_style_type", $value, $important);
      } else if ( in_array($value, $positions) ) {
   	    $this->_set_style("list_style_position", $value, $important);
      }
    }

    //see __set and __get, on all assignments clear cache, not needed on direct set through __set
	$this->_prop_cache["list_style"] = null;
 	$this->_props["list_style"] = $val;
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

/*DEBUGCSS*/  function debug_print()
/*DEBUGCSS*/  {
/*DEBUGCSS*/    print "parent_font_size:".$this->_parent_font_size . ";\n";
/*DEBUGCSS*/    foreach($this->_props as $prop => $val ) {
/*DEBUGCSS*/      print $prop.':'.$val;
/*DEBUGCSS*/      if (isset($this->_important_props[$prop])) {
/*DEBUGCSS*/      	print '!important';
/*DEBUGCSS*/      }
/*DEBUGCSS*/      print ";\n";
/*DEBUGCSS*/    }
/*DEBUGCSS*/  }
}
?>