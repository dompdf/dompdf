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
 * @package dompdf
 * @version 0.3
 */

/* $Id: style.cls.php,v 1.3 2005-03-02 00:51:24 benjcarson Exp $ */

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
      
      if ( ($i = strpos($l, "pt"))  !== false ) {
        $ret += substr($l, 0, $i);
        continue;
      }

      if ( ($i = strpos($l, "px"))  !== false ) {
        $ret += substr($l, 0, $i);
        continue;
      }

      if ( ($i = strpos($l, "em"))  !== false ) {
        $ret += substr($l, 0, $i) * $this->__get("font_size");
        continue;
      }
      
      // FIXME: em:ex ratio?
      if ( ($i = strpos($l, "ex"))  !== false ) {
        $ret += substr($l, 0, $i) * $this->__get("font_size");
        continue;
      }
      
      if ( ($i = strpos($l, "%"))  !== false ) {
        $ret += substr($l, 0, $i)/100 * $ref_size;
        continue;
      }
      
      if ( ($i = strpos($l, "in")) !== false ) {
        $ret += substr($l, 0, $i) * 72;
        continue;
      }
          
      if ( ($i = strpos($l, "cm")) !== false ) {
        $ret += substr($l, 0, $i) * 72 / 2.54;
        continue;
      }

      if ( ($i = strpos($l, "mm")) !== false ) {
        $ret += substr($l, 0, $i) * 72 / 25.4;
        continue;
      }
          
      if ( ($i = strpos($l, "pc")) !== false ) {
        $ret += substr($l, 0, $i) / 12;
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
      if ( isset($parent->_props[$prop]) ) 
        $this->_props[$prop] = $parent->_props[$prop];
    }
    
    foreach (array_keys($this->_props) as $prop) {
      if ( $this->_props[$prop] == "inherit" && isset($parent->_props[$prop]) ) 
        $this->_props[$prop] = $parent->_props[$prop];
    }
          
    return $this;
  }

  
  /**
   * Override properties in this style with those in $style
   *
   * @param Style $style
   */
  function merge(Style $style) {
    $this->_props = array_merge($this->_props, $style->_props);

    if ( array_key_exists("font_size", $style->_props) )
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

      if ( strlen($colour) == 4 ) {
        // #rgb format
        $r = hexdec($colour{1} . $colour{1});
        $g = hexdec($colour{2} . $colour{2});
        $b = hexdec($colour{3} . $colour{3});

      } else if ( strlen($colour) == 7 ) {
        // #rrggbb format
        $r = hexdec(substr($colour, 1, 2));
        $g = hexdec(substr($colour, 3, 2));
        $b = hexdec(substr($colour, 5, 2));

      } else if ( strpos($colour, "rgb") !== false ) {

        // rgb( r,g,b ) format
        $i = strpos($colour, "(");
        $j = strpos($colour, ")");
        
        // Bad colour value
        if ($i === false || $j === false)
          return null;

        $triplet = explode(",", substr($colour, $i+1, $j-$i-1));

        if (count($triplet) != 3)
          return null;
        
        foreach (array_keys($triplet) as $c) {
          $triplet[$c] = trim($triplet[$c]);
          
          if ( $triplet[$c]{strlen($triplet[$c]) - 1} == "%" ) 
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
   * @param string $prop  the property to set
   * @param mixed  $val   the value of the property
   * 
   */
  function __set($prop, $val) {
    global $_dompdf_warnings;

    $prop = str_replace("-", "_", $prop);
    
    if ( !array_key_exists($prop, self::$_defaults) ) {
      $_dompdf_warnings[] = "'$prop' is not a valid CSS2 property.";
      return;
    }
    
    if ( $prop !== "content" && is_string($val) && strpos($val, "url") === false ) {
      $val = strtolower(trim(str_replace(array("\n", "\t"), array(" "), $val)));
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
   * @param string $prop
   * @return mixed
   */
  function __get($prop) {
    
    if ( !array_key_exists($prop, self::$_defaults) ) 
      throw new DOMPDF_Exception("'$prop' is not a valid CSS2 property.");

    $method = "get_$prop";

    // Fall back on defaults if property is not set
    if ( !isset($this->_props[$prop]) )
      $this->_props[$prop] = self::$_defaults[$prop];

    if ( method_exists($this, $method) ) 
      return $this->$method();

    return $this->_props[$prop];
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
    // Select the appropriate font.  First determine the subtype, then check
    // the specified font-families for a candidate.

    // Resolve font-weight
    $weight = $this->__get("font_weight");
    
    if ( is_numeric($weight) ) {

      if ( $weight < 700 )
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

    if ( $weight == "bold" && $font_style == "italic" )
      $subtype = "bold_italic";
    else if ( $weight == "bold" && $font_style != "italic" )
      $subtype = "bold";
    else if ( $weight != "bold" && $font_style == "italic" )
      $subtype = "italic";
    else
      $subtype = "normal";
    
    // Resolve the font family
    $families = explode(",", $this->_props["font_family"]);
    reset($families);
    $font = null;
    while ( current($families) ) {
      list(,$family) = each($families);
      $font = Font_Metrics::get_font($family, $subtype);

      if ( $font )
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
    if ( ($i = strpos($fs, "em")) !== false ) 
      $fs = substr($fs, 0, $i) * $this->_parent_font_size;

    else if ( ($i = strpos($fs, "ex")) !== false ) 
      $fs = substr($fs, 0, $i) * $this->_parent_font_size;

    else
      $fs = $this->length_in_pt($fs);

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
        $y = "50%";
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
           
        
  /**#@+
   * Returns the border colour as an array
   *
   * See {@link Style::get_color()}
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-color-properties
   * @return array
   */
  function get_border_top_color() {
    if ( $this->_props["border_top_color"] === "" )
      $this->_props["border_top_color"] = $this->__get("color");    
    return $this->munge_color($this->_props["border_top_color"]);
  }

  function get_border_right_color() {
    if ( $this->_props["border_right_color"] === "" )
      $this->_props["border_right_color"] = $this->__get("color");
    return $this->munge_color($this->_props["border_right_color"]);
  }

  function get_border_bottom_color() {
    if ( $this->_props["border_bottom_color"] === "" )
      $this->_props["border_bottom_color"] = $this->__get("color");
    return $this->munge_color($this->_props["border_bottom_color"]);;
  }

  function get_border_left_color() {
    if ( $this->_props["border_left_color"] === "" )
      $this->_props["border_left_color"] = $this->__get("color");
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
    
    $this->_props["background_color"] = is_array($col) ? $col["hex"] : $col;
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
    $this->_props["font_size"] = $size;
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

    $this->_props["page_break_before"] = $break;
  }

  function set_page_break_after($break) {
    if ($break === "left" || $break === "right")
      $break = "always";

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
    $this->_props["margin_top"] = str_replace("none", "0px", $val);
  }

  function set_margin_right($val) {
    $this->_props["margin_right"] = str_replace("none", "0px", $val);
  }

  function set_margin_bottom($val) {
    $this->_props["margin_bottom"] = str_replace("none", "0px", $val);
  }

  function set_margin_left($val) {
    $this->_props["margin_left"] = str_replace("none", "0px", $val);
  }
  
  function set_margin($val) {
    $val = str_replace("none", "0px", $val);
    $margins = explode(" ", $val);

    switch (count($margins)) {

    case 1:
      $this->_props["margin_top"] = $margins[0];
      $this->_props["margin_right"] = $margins[0];
      $this->_props["margin_bottom"] = $margins[0];
      $this->_props["margin_left"] = $margins[0];
      break;

    case 2:
      $this->_props["margin_top"] = $margins[0];
      $this->_props["margin_bottom"] = $margins[0];

      $this->_props["margin_right"] = $margins[1];
      $this->_props["margin_left"] = $margins[1];
      break;
        
    case 3:
      $this->_props["margin_top"] = $margins[0];
      $this->_props["margin_right"] = $margins[1];
      $this->_props["margin_bottom"] = $margins[1];
      $this->_props["margin_left"] = $margins[2];
      break;

    case 4:
      $this->_props["margin_top"] = $margins[0];
      $this->_props["margin_right"] = $margins[1];
      $this->_props["margin_bottom"] = $margins[2];
      $this->_props["margin_left"] = $margins[3];
      break;

    default:
      break;
    }

    $this->_props["margin"] = $val;
    
  }
  /**#@-*/
     

  /**#@+
   * Sets the padding size
   *
   * @link http://www.w3.org/TR/CSS21/box.html#padding-properties
   * @param $val
   */
  function set_padding_top($val) {
    $this->_props["padding_top"] = str_replace("none", "0px", $val);
  }

  function set_padding_right($val) {
    $this->_props["padding_right"] = str_replace("none", "0px", $val);
  }

  function set_padding_bottom($val) {
    $this->_props["padding_bottom"] = str_replace("none", "0px", $val);
  }

  function set_padding_left($val) {
    $this->_props["padding_left"] = str_replace("none", "0px", $val);
  }

  function set_padding($val) {
    $val = str_replace("none", "0px", $val);
    $paddings = explode(" ", $val);

    switch (count($paddings)) {

    case 1:
      $this->_props["padding_top"] = $paddings[0];
      $this->_props["padding_right"] = $paddings[0];
      $this->_props["padding_bottom"] = $paddings[0];
      $this->_props["padding_left"] = $paddings[0];
      break;

    case 2:
      $this->_props["padding_top"] = $paddings[0];
      $this->_props["padding_bottom"] = $paddings[0];

      $this->_props["padding_right"] = $paddings[1];
      $this->_props["padding_left"] = $paddings[1];
      break;
        
    case 3:
      $this->_props["padding_top"] = $paddings[0];
      $this->_props["padding_right"] = $paddings[1];
      $this->_props["padding_bottom"] = $paddings[1];
      $this->_props["padding_left"] = $paddings[2];
      break;

    case 4:
      $this->_props["padding_top"] = $paddings[0];
      $this->_props["padding_right"] = $paddings[1];
      $this->_props["padding_bottom"] = $paddings[2];
      $this->_props["padding_left"] = $paddings[3];
      break;

    default:
      break;
    }

    $this->_props["padding"] = $val;
  }
  /**#@-*/

  /**
   * Sets a single border
   *
   * @param string $side
   * @param string $border_spec  ([width] [style] [color])
   */
  protected function _set_border($side, $border_spec) {
    $border_spec = str_replace(",", " ", $border_spec);
    $arr = explode(" ", $border_spec);

    // FIXME: handle partial values
    
    $p = "border_" . $side;
    $p_width = $p . "_width";
    $p_style = $p . "_style";
    $p_color = $p . "_color";
    
    if ( isset($arr[0]) && isset($arr[1]) ) {
      // Swap mixed up border specs
      if ( in_array($arr[0], self::$BORDER_STYLES) ) {
        $t = $arr[1];
        $arr[1] = $arr[0];
        $arr[0] = $t;
      }
    }
    if ( isset($arr[0]) ) 
      $this->_props[$p_width] = str_replace("none", "0px", $arr[0]);

    if ( isset($arr[1]) )
      $this->_props[$p_style] = $arr[1];

    if ( isset($arr[2]) )
      $this->_props[$p_color] = $arr[2];
    
    $this->_props[$p] = $border_spec;
  }

  /**#@+
   * Sets the border styles
   *
   * @link http://www.w3.org/TR/CSS21/box.html#border-properties
   * @param string $val
   */
  function set_border_top($val) { $this->_set_border("top", $val); }
  function set_border_right($val) { $this->_set_border("right", $val); }
  function set_border_bottom($val) { $this->_set_border("bottom", $val); }
  function set_border_left($val) { $this->_set_border("left", $val); }
  
  function set_border($val) {
    $this->_set_border("top", $val);
    $this->_set_border("right", $val);
    $this->_set_border("bottom", $val);
    $this->_set_border("left", $val);
    $this->_props["border"] = $val;
  }

  function set_border_width($val) {
    $arr = explode(" ", $val);

    switch (count($arr)) {

    case 1:
      $this->_props["border_top_width"] = $arr[0];
      $this->_props["border_right_width"] = $arr[0];
      $this->_props["border_bottom_width"] = $arr[0];
      $this->_props["border_left_width"] = $arr[0];
      break;

    case 2:
      $this->_props["border_top_width"] = $arr[0];
      $this->_props["border_bottom_width"] = $arr[0];

      $this->_props["border_right_width"] = $arr[1];
      $this->_props["border_left_width"] = $arr[1];
      break;
        
    case 3:
      $this->_props["border_top_width"] = $arr[0];
      $this->_props["border_right_width"] = $arr[1];
      $this->_props["border_bottom_width"] = $arr[1];
      $this->_props["border_left_width"] = $arr[2];
      break;

    case 4:
      $this->_props["border_top_width"] = $arr[0];
      $this->_props["border_right_width"] = $arr[1];
      $this->_props["border_bottom_width"] = $arr[2];
      $this->_props["border_left_width"] = $arr[3];
      break;

    default:
      break;
    }

    $this->_props["border_width"] = $val;
  }
  
  function set_border_color($val) {
    
    $arr = explode(" ", $val);
    
    switch (count($arr)) {

    case 1:
      $this->_props["border_top_color"] = $arr[0];
      $this->_props["border_right_color"] = $arr[0];
      $this->_props["border_bottom_color"] = $arr[0];
      $this->_props["border_left_color"] = $arr[0];
      break;

    case 2:
      $this->_props["border_top_color"] = $arr[0];
      $this->_props["border_bottom_color"] = $arr[0];

      $this->_props["border_right_color"] = $arr[1];
      $this->_props["border_left_color"] = $arr[1];
      break;
        
    case 3:
      $this->_props["border_top_color"] = $arr[0];
      $this->_props["border_right_color"] = $arr[1];
      $this->_props["border_bottom_color"] = $arr[1];
      $this->_props["border_left_color"] = $arr[2];
      break;

    case 4:
      $this->_props["border_top_color"] = $arr[0];
      $this->_props["border_right_color"] = $arr[1];
      $this->_props["border_bottom_color"] = $arr[2];
      $this->_props["border_left_color"] = $arr[3];
      break;

    default:
      break;
    }

    $this->_props["border_color"] = $val;

  }

  function set_border_style($val) {
    $arr = explode(" ", $val);

    switch (count($arr)) {

    case 1:
      $this->_props["border_top_style"] = $arr[0];
      $this->_props["border_right_style"] = $arr[0];
      $this->_props["border_bottom_style"] = $arr[0];
      $this->_props["border_left_style"] = $arr[0];
      break;

    case 2:
      $this->_props["border_top_style"] = $arr[0];
      $this->_props["border_bottom_style"] = $arr[0];

      $this->_props["border_right_style"] = $arr[1];
      $this->_props["border_left_style"] = $arr[1];
      break;
        
    case 3:
      $this->_props["border_top_style"] = $arr[0];
      $this->_props["border_right_style"] = $arr[1];
      $this->_props["border_bottom_style"] = $arr[1];
      $this->_props["border_left_style"] = $arr[2];
      break;

    case 4:
      $this->_props["border_top_style"] = $arr[0];
      $this->_props["border_right_style"] = $arr[1];
      $this->_props["border_bottom_style"] = $arr[2];
      $this->_props["border_left_style"] = $arr[3];
      break;

    default:
      break;
    }

    $this->_props["border_style"] = $val;
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

    $this->_props["border_spacing"] = $arr[0] . " " . $arr[1];
  }
      
  /**
   * Sets the list style
   *
   * This is not currently implemented
   *
   * @link http://www.w3.org/TR/CSS21/generate.html#propdef-list-style
   * @param $val
   */
  function set_list_style($val) {
    // FIXME:
    //throw new DOMPDF_Exception("FIXME: need to implement this.");
    global $_dompdf_warnings;
    $_dompdf_warnings[] = "FIXME: list_style not implemented yet.";
  }

  /**
   * Generate a string representation of the Style
   *
   * This dumps the entire property array into a string via print_r.  Useful
   * for debugging.
   *
   * @return string
   */
  function __toString() {
    return print_r(array_merge(array("parent_font_size" => $this->_parent_font_size),
                               $this->_props), true);
  }
}
?>