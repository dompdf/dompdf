<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: attribute_translator.cls.php,v $
 * Created on: 2004-09-13
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
 * @version 0.5.1
 */

/* $Id: attribute_translator.cls.php,v 1.12 2008-02-07 07:31:05 benjcarson Exp $ */

/**
 * Translates HTML 4.0 attributes into CSS rules
 *
 * @access private
 * @package dompdf
 */
class Attribute_Translator {
  
  // Munged data originally from
  // http://www.w3.org/TR/REC-html40/index/attributes.html
  //
  // thank you var_export() :D
  static private $__ATTRIBUTE_LOOKUP =
    array (//'caption' => array ( 'align' => '', ),
           'img_inner' =>  // img tags actually end up wrapping img_inner elements
           array ('align' => array('bottom' => 'vertical-align: baseline;',
                                   'middle' => 'vertical-align: middle;',
                                   'top' => 'vertical-align: top;',
                                   'left' => 'float: left;',
                                   'right' => 'float: right;'),
                  'border' => 'border-width: %0.2f px;',
                  'height' => 'height: %s;',
                  'hspace' => 'padding-left: %1$0.2f px; padding-right: %1$0.2f px;',
                  'vspace' => 'padding-top: %1$0.2f px; padding-bottom: %1$0.2f px',
                  'width' => 'width: %s;',
                  ),
           'table' => 
           array ("align" => array(//'left' => '',
                        'center' => 'margin-left: auto; margin-right: auto;',
                        //'right' => ''
                        ),
                  'bgcolor' => 'background-color: %s;',
                  'border' => '!set_table_border',
                  'cellpadding' => '!set_table_cellpadding',
                  'cellspacing' => 'border-spacing: %0.2f; border-collapse: separate;',
                  'frame' => array('void' => 'border-style: none;',
                                   'above' => 'border-top-style: solid;',
                                   'below' => 'border-bottom-style: solid;',
                                   'hsides' => 'border-left-style: solid; border-right-style: solid;',
                                   'vsides' => 'border-top-style: solid; border-bottom-style: solid;',
                                   'lhs' => 'border-left-style: solid;',
                                   'rhs' => 'border-right-style: solid;',
                                   'box' => 'border-style: solid;',
                                   'border' => 'border-style: solid;'),
                  'rules' => '!set_table_rules',
                  'width' => 'width: %s;',
                  ),
           'hr' => 
           array (
                  'align' => '!set_hr_align', // Need to grab width to set 'left' & 'right' correctly
                  'noshade' => 'border-style: solid;',
                  'size' => 'border-width: %0.2f px;',
                  'width' => 'width: %s;',
                  ),
           'div' => 
           array (
                  'align' => 'text-align: %s;',
                  ),
           'h1' => 
           array (
                  'align' => 'text-align: %s;',
                  ),
           'h2' => 
           array (
                  'align' => 'text-align: %s;',
                  ),
           'h3' => 
           array (
                  'align' => 'text-align: %s;',
                  ),
           'h4' => 
           array (
                  'align' => 'text-align: %s;',
                  ),
           'h5' => 
           array (
                  'align' => 'text-align: %s;',
                  ),
           'h6' => 
           array (
                  'align' => 'text-align: %s;',
                  ),
           'p' => 
           array (
                  'align' => 'text-align: %s;',
                  ),
//            'col' => 
//            array (
//                   'align' => '',
//                   'valign' => '',
//                   ),
//            'colgroup' => 
//            array (
//                   'align' => '',
//                   'valign' => '',
//                   ),
           'tbody' => 
           array (
                  'align' => '!set_table_row_align',
                  'valign' => '!set_table_row_valign',
                  ),
           'td' => 
           array (
                  'align' => 'text-align: %s;',
                  'bgcolor' => 'background-color: %s;',
                  'height' => 'height: %s;',
                  'nowrap' => 'white-space: nowrap;',
                  'valign' => 'vertical-align: %s;',
                  'width' => 'width: %s;',
                  ),
           'tfoot' => 
           array (
                  'align' => '!set_table_row_align',
                  'valign' => '!set_table_row_valign',
                  ),
           'th' => 
           array (
                  'align' => 'text-align: %s;',
                  'bgcolor' => 'background-color: %s;',
                  'height' => 'height: %s;',
                  'nowrap' => 'white-space: nowrap;',
                  'valign' => 'vertical-align: %s;',
                  'width' => 'width: %s;',
                  ),
           'thead' => 
           array (
                  'align' => '!set_table_row_align',
                  'valign' => '!set_table_row_valign',
                  ),
           'tr' => 
           array (
                  'align' => '!set_table_row_align',
                  'bgcolor' => '!set_table_row_bgcolor',
                  'valign' => '!set_table_row_valign',
                  ),
           'body' => 
           array (
                  'background' => 'background-image: url(%s);',
                  'bgcolor' => 'background-color: %s;',
                  'link' => '!set_body_link',
                  'text' => 'color: %s;',
                  ),
           'br' => 
           array (
                  'clear' => 'clear: %s;',
                  ),
           'basefont' => 
           array (
                  'color' => 'color: %s;',
                  'face' => 'font-family: %s;',
                  'size' => '!set_basefont_size',
                  ),
           'font' => 
           array (
                  'color' => 'color: %s;',
                  'face' => 'font-family: %s;',
                  'size' => '!set_font_size',
                  ),
           'dir' => 
           array (
                  'compact' => 'margin: 0.5em 0;',
                  ),
           'dl' => 
           array (
                  'compact' => 'margin: 0.5em 0;',
                  ),
           'menu' => 
           array (
                  'compact' => 'margin: 0.5em 0;',
                  ),
           'ol' => 
           array (
                  'compact' => 'margin: 0.5em 0;',
                  'start' => 'counter-reset: -dompdf-default-counter %d;',
                  'type' => 'list-style-type: %s;',
                  ),
           'ul' => 
           array (
                  'compact' => 'margin: 0.5em 0;',
                  'type' => 'list-style-type: %s;',
                  ),
           'li' => 
           array (
                  'type' => 'list-style-type: %s;',
                  'value' => 'counter-reset: -dompdf-default-counter %d;',
                  ),
           'pre' => 
           array (
                  'width' => 'width: %s;',
                  ),
           );

  
  static protected $_last_basefont_size = 3;
  static protected $_font_size_lookup = array(1=>"xx-small",
                                              2=>"x-small",
                                              3=>"medium",
                                              4=>"large",
                                              5=>"x-large",
                                              6=>"xx-large",
                                              7=>"300%");
  
  
  static function translate_attributes($frame) {
    $node = $frame->get_node();
    $tag = $node->tagName;

    if ( !isset(self::$__ATTRIBUTE_LOOKUP[$tag]) )
      return;

    $valid_attrs = self::$__ATTRIBUTE_LOOKUP[$tag];
    $attrs = $node->attributes;
    $style = rtrim($node->getAttribute("style"), "; ");
    if ( $style != "" )
      $style .= ";";

    foreach ($attrs as $attr => $attr_node ) {
      if ( !isset($valid_attrs[$attr]) )
        continue;

      $value = $attr_node->value;

      $target = $valid_attrs[$attr];
      
      // Look up $value in $target, if $target is an array:
      if ( is_array($target) ) {

        if ( isset($target[$value]) ) 
          $style .= " " . self::_resolve_target($node, $target[$value], $value);

      } else {
        // otherwise use target directly
        $style .= " " . self::_resolve_target($node, $target, $value);
      }
    }
    if ( !is_null($style) ) {
      $style = ltrim($style);
      $node->setAttribute("style", $style);
    }
    
  }

  static protected function _resolve_target($node, $target, $value) {
    if ( $target{0} == "!" ) {
      // Function call
      $func = "_" . mb_substr($target, 1);
      return self::$func($node, $value);
    }
    
    return $value ? sprintf($target, $value) : "";
  }

  //.....................................................................

  static protected function _set_table_cellpadding($node, $value) {

    $td_list = $node->getElementsByTagName("td");
    foreach ($td_list as $td) {
      $style = rtrim($td->getAttribute("style"), ";");
      $style .= "; padding: $value" . "px;";
      $style = ltrim($style, ";");
      $td->setAttribute("style", $style);
    }
    return null;
  }

  static protected function _set_table_border($node, $value) {
    $td_list = $node->getElementsByTagName("td");
    foreach ($td_list as $td) {
      $style = $td->getAttribute("style");
      if ( strpos($style, "border") !== false )
        continue;
      $style = rtrim($style, ";");
      $style .= "; border-width: $value" . "px; border-style: ridge;";
      $style = ltrim($style, ";");
      $td->setAttribute("style", $style);
    }
    $th_list = $node->getElementsByTagName("th");
    foreach ($th_list as $th) {
      $style = $th->getAttribute("style");
      if ( strpos($style, "border") !== false )
        continue;
      $style = rtrim($style, ";");
      $style .= "; border-width: $value" . "px; border-style: ridge;";
      $style = ltrim($style, ";");
      $th->setAttribute("style", $style);
    }
    
    return null;
  }

  static protected function _set_table_cellspacing($node, $value) {
    $style = rtrim($td->getAttribute($style), ";");

    if ( $value == 0 ) 
      $style .= "; border-collapse: collapse;";
      
    else 
      $style = "; border-collapse: separate;";
      
    return ltrim($style, ";");
  }
  
  static protected function _set_table_rules($node, $value) {
    $new_style = "; border-collapse: collapse;";
    switch ($value) {
    case "none":
      $new_style .= "border-style: none;";
      break;

    case "groups":
      // FIXME: unsupported
      return;

    case "rows":
      $new_style .= "border-style: solid none solid none; border-width: 1px; ";
      break;

    case "cols":
      $new_style .= "border-style: none solid none solid; border-width: 1px; ";
      break;

    case "all":
      $new_style .= "border-style: solid; border-width: 1px; ";
      break;
      
    default:
      // Invalid value
      return null;
    }

    $td_list = $node->getElementsByTagName("td");
    
    foreach ($td_list as $td) {
      $style = $td->getAttribute("style");
      $style .= $new_style;
      $td->setAttribute("style", $style);
    }
    return null;
  }

  static protected function _set_hr_align($node, $value) {

    $style = rtrim($node->getAttribute("style"),";");
    $width = $node->getAttribute("width");
    if ( $width == "" )
      $width = "100%";

    $remainder = 100 - (double)rtrim($width, "% ");
    
    switch ($value) {
    case "left":
      $style .= "; margin-right: $remainder %;";
      break;

    case "right":
      $style .= "; margin-left: $remainder %;";
      break;

    case "center":
      $style .= "; margin-left: auto; margin-right: auto;";
      break;

    default:
      return null;
    }
    return ltrim($style, "; ");
    
  }

  static protected function _set_table_row_align($node, $value) {

    $td_list = $node->getElementsByTagName("td");

    foreach ($td_list as $td) {
      $style = rtrim($td->getAttribute("style"), ";");
      $style .= "; text-align: $value;";
      $style = ltrim($style, "; ");
      $td->setAttribute("style", $style);
    }

    return null;
  }

  static protected function _set_table_row_valign($node, $value) {

    $td_list = $node->getElementsByTagName("td");

    foreach ($td_list as $td) {
      $style = rtrim($td->getAttribute("style"), ";");
      $style .= "; vertical-align: $value;";
      $style = ltrim($style, "; ");
      $td->setAttribute("style", $style);
    }

    return null;
  }

  static protected function _set_table_row_bgcolor($node, $value) {

    $td_list = $node->getElementsByTagName("td");

    foreach ($td_list as $td) {
      $style = rtrim($td->getAttribute("style"), ";");
      $style .= "; background-color: $value;";
      $style = ltrim($style, "; ");
      $td->setAttribute("style", $style);
    }

    return null;
  }

  static protected function _set_body_link($node, $value) {

    $a_list = $node->getElementsByTagName("a");

    foreach ($a_list as $a) {
      $style = rtrim($a->getAttribute("style"), ";");
      $style .= "; color: $value;";
      $style = ltrim($style, "; ");
      $a->setAttribute("style", $style);
    }

    return null;
  }

  static protected function _set_basefont_size($node, $value) {
    // FIXME: ? we don't actually set the font size of anything here, just
    // the base size for later modification by <font> tags.
    self::$_last_basefont_size = $value;
    return null;
  }
  
  static protected function _set_font_size($node, $value) {
    $style = $node->getAttribute("style");

    if ( $value{0} == "-" || $value{0} == "+" )
      $value = self::$_last_basefont_size + (int)$value;

    if ( isset(self::$_font_size_lookup[$value]) )
      $style .= "; font-size: " . self::$_font_size_lookup[$value] . ";";
    else
      $style .= "; font-size: $value;";

    return ltrim($style, "; ");
    
  }

}

?>