<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Translates HTML 4.0 attributes into CSS rules
 *
 * @package dompdf
 */
class Attribute_Translator {
  static $_style_attr = "_html_style_attribute";
  
  // Munged data originally from
  // http://www.w3.org/TR/REC-html40/index/attributes.html
  // http://www.cs.tut.fi/~jkorpela/html2css.html
  static private $__ATTRIBUTE_LOOKUP = array(
    //'caption' => array ( 'align' => '', ),
    'img' => array(
      'align' => array(
        'bottom' => 'vertical-align: baseline;',
        'middle' => 'vertical-align: middle;',
        'top'    => 'vertical-align: top;',
        'left'   => 'float: left;',
        'right'  => 'float: right;'
      ),
      'border' => 'border: %0.2F px solid;',
      'height' => 'height: %s px;',
      'hspace' => 'padding-left: %1$0.2F px; padding-right: %1$0.2F px;',
      'vspace' => 'padding-top: %1$0.2F px; padding-bottom: %1$0.2F px;',
      'width'  => 'width: %s px;',
    ),
    'table' => array(
      'align' => array(
        'left'   => 'margin-left: 0; margin-right: auto;',
        'center' => 'margin-left: auto; margin-right: auto;',
        'right'  => 'margin-left: auto; margin-right: 0;'
      ),
      'bgcolor' => 'background-color: %s;',
      'border' => '!set_table_border',
      'cellpadding' => '!set_table_cellpadding',//'border-spacing: %0.2F; border-collapse: separate;',
      'cellspacing' => '!set_table_cellspacing',
      'frame' => array(
        'void'   => 'border-style: none;',
        'above'  => 'border-top-style: solid;',
        'below'  => 'border-bottom-style: solid;',
        'hsides' => 'border-left-style: solid; border-right-style: solid;',
        'vsides' => 'border-top-style: solid; border-bottom-style: solid;',
        'lhs'    => 'border-left-style: solid;',
        'rhs'    => 'border-right-style: solid;',
        'box'    => 'border-style: solid;',
        'border' => 'border-style: solid;'
      ),
      'rules' => '!set_table_rules',
      'width' => 'width: %s;',
    ),
    'hr' => array(
      'align'   => '!set_hr_align', // Need to grab width to set 'left' & 'right' correctly
      'noshade' => 'border-style: solid;',
      'size'    => '!set_hr_size', //'border-width: %0.2F px;',
      'width'   => 'width: %s;',
    ),
    'div' => array(
      'align' => 'text-align: %s;',
    ),
    'h1' => array(
      'align' => 'text-align: %s;',
    ),
    'h2' => array(
      'align' => 'text-align: %s;',
    ),
    'h3' => array(
      'align' => 'text-align: %s;',
    ),
    'h4' => array(
      'align' => 'text-align: %s;',
    ),
    'h5' => array(
      'align' => 'text-align: %s;',
    ),
    'h6' => array(
      'align' => 'text-align: %s;',
    ),
    'p' => array(
      'align' => 'text-align: %s;',
    ),
//    'col' => array(
//      'align'  => '',
//      'valign' => '',
//    ),
//    'colgroup' => array(
//      'align'  => '',
//      'valign' => '',
//    ),
    'tbody' => array(
      'align'  => '!set_table_row_align',
      'valign' => '!set_table_row_valign',
    ),
    'td' => array(
      'align'   => 'text-align: %s;',
      'bgcolor' => '!set_background_color',
      'height'  => 'height: %s;',
      'nowrap'  => 'white-space: nowrap;',
      'valign'  => 'vertical-align: %s;',
      'width'   => 'width: %s;',
    ),
    'tfoot' => array(
      'align'   => '!set_table_row_align',
      'valign'  => '!set_table_row_valign',
    ),
    'th' => array(
      'align'   => 'text-align: %s;',
      'bgcolor' => '!set_background_color',
      'height'  => 'height: %s;',
      'nowrap'  => 'white-space: nowrap;',
      'valign'  => 'vertical-align: %s;',
      'width'   => 'width: %s;',
    ),
    'thead' => array(
      'align'   => '!set_table_row_align',
      'valign'  => '!set_table_row_valign',
    ),
    'tr' => array(
      'align'   => '!set_table_row_align',
      'bgcolor' => '!set_table_row_bgcolor',
      'valign'  => '!set_table_row_valign',
    ),
    'body' => array(
      'background' => 'background-image: url(%s);',
      'bgcolor'    => '!set_background_color',
      'link'       => '!set_body_link',
      'text'       => '!set_color',
    ),
    'br' => array(
      'clear' => 'clear: %s;',
    ),
    'basefont' => array(
      'color' => '!set_color',
      'face'  => 'font-family: %s;',
      'size'  => '!set_basefont_size',
    ),
    'font' => array(
      'color' => '!set_color',
      'face'  => 'font-family: %s;',
      'size'  => '!set_font_size',
    ),
    'dir' => array(
      'compact' => 'margin: 0.5em 0;',
    ),
    'dl' => array(
      'compact' => 'margin: 0.5em 0;',
    ),
    'menu' => array(
      'compact' => 'margin: 0.5em 0;',
    ),
    'ol' => array(
      'compact' => 'margin: 0.5em 0;',
      'start'   => 'counter-reset: -dompdf-default-counter %d;',
      'type'    => 'list-style-type: %s;',
    ),
    'ul' => array(
      'compact' => 'margin: 0.5em 0;',
      'type'    => 'list-style-type: %s;',
    ),
    'li' => array(
      'type'    => 'list-style-type: %s;',
      'value'   => 'counter-reset: -dompdf-default-counter %d;',
    ),
    'pre' => array(
      'width' => 'width: %s;',
    ),
  );
  
  static protected $_last_basefont_size = 3;
  static protected $_font_size_lookup = array(
    // For basefont support
    -3 => "4pt", 
    -2 => "5pt", 
    -1 => "6pt", 
     0 => "7pt", 
    
     1 => "8pt",
     2 => "10pt",
     3 => "12pt",
     4 => "14pt",
     5 => "18pt",
     6 => "24pt",
     7 => "34pt",
     
    // For basefont support
     8 => "48pt", 
     9 => "44pt", 
    10 => "52pt", 
    11 => "60pt", 
  );

  /**
   * @param Frame $frame
   */
  static function translate_attributes(Frame $frame) {
    $node = $frame->get_node();
    $tag = $node->nodeName;

    if ( !isset(self::$__ATTRIBUTE_LOOKUP[$tag]) ) {
      return;
    }

    $valid_attrs = self::$__ATTRIBUTE_LOOKUP[$tag];
    $attrs = $node->attributes;
    $style = rtrim($node->getAttribute(self::$_style_attr), "; ");
    if ( $style != "" ) {
      $style .= ";";
    }

    foreach ($attrs as $attr => $attr_node ) {
      if ( !isset($valid_attrs[$attr]) ) {
        continue;
      }

      $value = $attr_node->value;

      $target = $valid_attrs[$attr];
      
      // Look up $value in $target, if $target is an array:
      if ( is_array($target) ) {
        if ( isset($target[$value]) ) {
          $style .= " " . self::_resolve_target($node, $target[$value], $value);
        }
      }
      else {
        // otherwise use target directly
        $style .= " " . self::_resolve_target($node, $target, $value);
      }
    }
    
    if ( !is_null($style) ) {
      $style = ltrim($style);
      $node->setAttribute(self::$_style_attr, $style);
    }
    
  }

  /**
   * @param DOMNode $node
   * @param string  $target
   * @param string      $value
   *
   * @return string
   */
  static protected function _resolve_target(DOMNode $node, $target, $value) {
    if ( $target[0] === "!" ) {
      // Function call
      $func = "_" . mb_substr($target, 1);
      return self::$func($node, $value);
    }
    
    return $value ? sprintf($target, $value) : "";
  }

  /**
   * @param DOMElement $node
   * @param string     $new_style
   */
  static function append_style(DOMElement $node, $new_style) {
    $style = rtrim($node->getAttribute(self::$_style_attr), ";");
    $style .= $new_style;
    $style = ltrim($style, ";");
    $node->setAttribute(self::$_style_attr, $style);
  }

  /**
   * @param DOMNode $node
   *
   * @return DOMNodeList|DOMElement[]
   */
  static protected function get_cell_list(DOMNode $node) {
    $xpath = new DOMXpath($node->ownerDocument);
    
    switch($node->nodeName) {
      default:
      case "table":
        $query = "tr/td | thead/tr/td | tbody/tr/td | tfoot/tr/td | tr/th | thead/tr/th | tbody/tr/th | tfoot/tr/th";
        break;
        
      case "tbody":
      case "tfoot":
      case "thead":
        $query = "tr/td | tr/th";
        break;
        
      case "tr":
        $query = "td | th";
        break;
    }
    
    return $xpath->query($query, $node);
  }

  /**
   * @param string $value
   *
   * @return string
   */
  static protected function _get_valid_color($value) {
    if ( preg_match('/^#?([0-9A-F]{6})$/i', $value, $matches) ) {
      $value = "#$matches[1]";
    }
    
    return $value;
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return string
   */
  static protected function _set_color(DOMElement $node, $value) {
    $value = self::_get_valid_color($value);
    return "color: $value;";
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return string
   */
  static protected function _set_background_color(DOMElement $node, $value) {
    $value = self::_get_valid_color($value);
    return "background-color: $value;";
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return null
   */
  static protected function _set_table_cellpadding(DOMElement $node, $value) {
    $cell_list = self::get_cell_list($node);
    
    foreach ($cell_list as $cell) {
      self::append_style($cell, "; padding: {$value}px;");
    }
    
    return null;
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return string
   */
  static protected function _set_table_border(DOMElement $node, $value) {
    $cell_list = self::get_cell_list($node);

    foreach ($cell_list as $cell) {
      $style = rtrim($cell->getAttribute(self::$_style_attr));
      $style .= "; border-width: " . ($value > 0 ? 1 : 0) . "pt; border-style: inset;";
      $style = ltrim($style, ";");
      $cell->setAttribute(self::$_style_attr, $style);
    }
    
    $style = rtrim($node->getAttribute(self::$_style_attr), ";");
    $style .= "; border-width: $value" . "px; ";
    return ltrim($style, "; ");
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return string
   */
  static protected function _set_table_cellspacing(DOMElement $node, $value) {
    $style = rtrim($node->getAttribute(self::$_style_attr), ";");

    if ( $value == 0 ) {
      $style .= "; border-collapse: collapse;";
    }
    else {
      $style .= "; border-spacing: {$value}px; border-collapse: separate;";
    }
    
    return ltrim($style, ";");
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return null|string
   */
  static protected function _set_table_rules(DOMElement $node, $value) {
    $new_style = "; border-collapse: collapse;";
    
    switch ($value) {
    case "none":
      $new_style .= "border-style: none;";
      break;

    case "groups":
      // FIXME: unsupported
      return null;

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

    $cell_list = self::get_cell_list($node);
    
    foreach ($cell_list as $cell) {
      $style = $cell->getAttribute(self::$_style_attr);
      $style .= $new_style;
      $cell->setAttribute(self::$_style_attr, $style);
    }
    
    $style = rtrim($node->getAttribute(self::$_style_attr), ";");
    $style .= "; border-collapse: collapse; ";
    
    return ltrim($style, "; ");
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return string
   */
  static protected function _set_hr_size(DOMElement $node, $value) {
    $style = rtrim($node->getAttribute(self::$_style_attr), ";");
    $style .= "; border-width: ".max(0, $value-2)."; ";
    return ltrim($style, "; ");
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return null|string
   */
  static protected function _set_hr_align(DOMElement $node, $value) {
    $style = rtrim($node->getAttribute(self::$_style_attr),";");
    $width = $node->getAttribute("width");
    
    if ( $width == "" ) {
      $width = "100%";
    }

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

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return null
   */
  static protected function _set_table_row_align(DOMElement $node, $value) {
    $cell_list = self::get_cell_list($node);

    foreach ($cell_list as $cell) {
      self::append_style($cell, "; text-align: $value;");
    }

    return null;
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return null
   */
  static protected function _set_table_row_valign(DOMElement $node, $value) {
    $cell_list = self::get_cell_list($node);

    foreach ($cell_list as $cell) {
      self::append_style($cell, "; vertical-align: $value;");
    }

    return null;
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return null
   */
  static protected function _set_table_row_bgcolor(DOMElement $node, $value) {
    $cell_list = self::get_cell_list($node);
    $value = self::_get_valid_color($value);
    
    foreach ($cell_list as $cell) {
      self::append_style($cell, "; background-color: $value;");
    }

    return null;
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return null
   */
  static protected function _set_body_link(DOMElement $node, $value) {
    $a_list = $node->getElementsByTagName("a");
    $value = self::_get_valid_color($value);
    
    foreach ($a_list as $a) {
      self::append_style($a, "; color: $value;");
    }

    return null;
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return null
   */
  static protected function _set_basefont_size(DOMElement $node, $value) {
    // FIXME: ? we don't actually set the font size of anything here, just
    // the base size for later modification by <font> tags.
    self::$_last_basefont_size = $value;
    return null;
  }

  /**
   * @param DOMElement $node
   * @param string     $value
   *
   * @return string
   */
  static protected function _set_font_size(DOMElement $node, $value) {
    $style = $node->getAttribute(self::$_style_attr);

    if ( $value[0] === "-" || $value[0] === "+" ) {
      $value = self::$_last_basefont_size + (int)$value;
    }
    
    if ( isset(self::$_font_size_lookup[$value]) ) {
      $style .= "; font-size: " . self::$_font_size_lookup[$value] . ";";
    }
    else {
      $style .= "; font-size: $value;";
    }
    
    return ltrim($style, "; ");
  }
}
