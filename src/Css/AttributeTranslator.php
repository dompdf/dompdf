<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Css;

use Dompdf\Frame;
use Dompdf\Helpers;

/**
 * Translates HTML 4.0 attributes into CSS rules
 *
 * @package dompdf
 */
class AttributeTranslator
{
    static $_style_attr = "_html_style_attribute";

    // Munged data originally from
    // http://www.w3.org/TR/REC-html40/index/attributes.html
    // http://www.cs.tut.fi/~jkorpela/html2css.html
    private static $__ATTRIBUTE_LOOKUP = [
        //'caption' => array ( 'align' => '', ),
        'img' => [
            'align' => [
                'bottom' => 'vertical-align: baseline;',
                'middle' => 'vertical-align: middle;',
                'top' => 'vertical-align: top;',
                'left' => 'float: left;',
                'right' => 'float: right;'
            ],
            'border' => 'border: %0.2Fpx solid;',
            'height' => '_set_px_height',
            'hspace' => 'padding-left: %1$0.2Fpx; padding-right: %1$0.2Fpx;',
            'vspace' => 'padding-top: %1$0.2Fpx; padding-bottom: %1$0.2Fpx;',
            'width' => '_set_px_width',
        ],
        'table' => [
            'align' => [
                'left' => 'margin-left: 0; margin-right: auto;',
                'center' => 'margin-left: auto; margin-right: auto;',
                'right' => 'margin-left: auto; margin-right: 0;'
            ],
            'bgcolor' => 'background-color: %s;',
            'border' => '_set_table_border',
            'cellpadding' => '_set_table_cellpadding', //'border-spacing: %0.2F; border-collapse: separate;',
            'cellspacing' => '_set_table_cellspacing',
            'frame' => [
                'void' => 'border-style: none;',
                'above' => 'border-top-style: solid;',
                'below' => 'border-bottom-style: solid;',
                'hsides' => 'border-left-style: solid; border-right-style: solid;',
                'vsides' => 'border-top-style: solid; border-bottom-style: solid;',
                'lhs' => 'border-left-style: solid;',
                'rhs' => 'border-right-style: solid;',
                'box' => 'border-style: solid;',
                'border' => 'border-style: solid;'
            ],
            'rules' => '_set_table_rules',
            'width' => 'width: %s;',
        ],
        'hr' => [
            'align' => '_set_hr_align', // Need to grab width to set 'left' & 'right' correctly
            'noshade' => 'border-style: solid;',
            'size' => '_set_hr_size', //'border-width: %0.2F px;',
            'width' => 'width: %s;',
        ],
        'div' => [
            'align' => 'text-align: %s;',
        ],
        'h1' => [
            'align' => 'text-align: %s;',
        ],
        'h2' => [
            'align' => 'text-align: %s;',
        ],
        'h3' => [
            'align' => 'text-align: %s;',
        ],
        'h4' => [
            'align' => 'text-align: %s;',
        ],
        'h5' => [
            'align' => 'text-align: %s;',
        ],
        'h6' => [
            'align' => 'text-align: %s;',
        ],
        //TODO: translate more form element attributes
        'input' => [
            'size' => '_set_input_width'
        ],
        'p' => [
            'align' => 'text-align: %s;',
        ],
//    'col' => array(
//      'align'  => '',
//      'valign' => '',
//    ),
//    'colgroup' => array(
//      'align'  => '',
//      'valign' => '',
//    ),
        'tbody' => [
            'align' => '_set_table_row_align',
            'valign' => '_set_table_row_valign',
        ],
        'td' => [
            'align' => 'text-align: %s;',
            'bgcolor' => '_set_background_color',
            'height' => 'height: %s;',
            'nowrap' => 'white-space: nowrap;',
            'valign' => 'vertical-align: %s;',
            'width' => 'width: %s;',
        ],
        'tfoot' => [
            'align' => '_set_table_row_align',
            'valign' => '_set_table_row_valign',
        ],
        'th' => [
            'align' => 'text-align: %s;',
            'bgcolor' => '_set_background_color',
            'height' => 'height: %s;',
            'nowrap' => 'white-space: nowrap;',
            'valign' => 'vertical-align: %s;',
            'width' => 'width: %s;',
        ],
        'thead' => [
            'align' => '_set_table_row_align',
            'valign' => '_set_table_row_valign',
        ],
        'tr' => [
            'align' => '_set_table_row_align',
            'bgcolor' => '_set_table_row_bgcolor',
            'valign' => '_set_table_row_valign',
        ],
        'body' => [
            'background' => 'background-image: url(%s);',
            'bgcolor' => '_set_background_color',
            'link' => '_set_body_link',
            'text' => '_set_color',
        ],
        'br' => [
            'clear' => 'clear: %s;',
        ],
        'basefont' => [
            'color' => '_set_color',
            'face' => 'font-family: %s;',
            'size' => '_set_basefont_size',
        ],
        'font' => [
            'color' => '_set_color',
            'face' => 'font-family: %s;',
            'size' => '_set_font_size',
        ],
        'dir' => [
            'compact' => 'margin: 0.5em 0;',
        ],
        'dl' => [
            'compact' => 'margin: 0.5em 0;',
        ],
        'menu' => [
            'compact' => 'margin: 0.5em 0;',
        ],
        'ol' => [
            'compact' => 'margin: 0.5em 0;',
            'start' => 'counter-reset: -dompdf-default-counter %d;',
            'type' => 'list-style-type: %s;',
        ],
        'ul' => [
            'compact' => 'margin: 0.5em 0;',
            'type' => 'list-style-type: %s;',
        ],
        'li' => [
            'type' => 'list-style-type: %s;',
            'value' => 'counter-reset: -dompdf-default-counter %d;',
        ],
        'pre' => [
            'width' => 'width: %s;',
        ],
    ];

    protected static $_last_basefont_size = 3;
    protected static $_font_size_lookup = [
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
    ];

    /**
     * @param Frame $frame
     */
    static function translate_attributes(Frame $frame)
    {
        $node = $frame->get_node();
        $tag = $node->nodeName;

        if (!isset(self::$__ATTRIBUTE_LOOKUP[$tag])) {
            return;
        }

        $valid_attrs = self::$__ATTRIBUTE_LOOKUP[$tag];
        $attrs = $node->attributes;
        $style = rtrim($node->getAttribute(self::$_style_attr), "; ");
        if ($style != "") {
            $style .= ";";
        }

        foreach ($attrs as $attr => $attr_node) {
            if (!isset($valid_attrs[$attr])) {
                continue;
            }

            $value = $attr_node->value;

            $target = $valid_attrs[$attr];

            // Look up $value in $target, if $target is an array:
            if (is_array($target)) {
                if (isset($target[$value])) {
                    $style .= " " . self::_resolve_target($node, $target[$value], $value);
                }
            } else {
                // otherwise use target directly
                $style .= " " . self::_resolve_target($node, $target, $value);
            }
        }

        if (!is_null($style)) {
            $style = ltrim($style);
            $node->setAttribute(self::$_style_attr, $style);
        }
    }

    /**
     * @param \DOMNode $node
     * @param string $target
     * @param string $value
     *
     * @return string
     */
    protected static function _resolve_target(\DOMNode $node, $target, $value)
    {
        if ($target[0] === "_") {
            return self::$target($node, $value);
        }

        return $value ? sprintf($target, $value) : "";
    }

    /**
     * @param \DOMElement $node
     * @param string $new_style
     */
    static function append_style(\DOMElement $node, $new_style)
    {
        $style = rtrim($node->getAttribute(self::$_style_attr), ";");
        $style .= $new_style;
        $style = ltrim($style, ";");
        $node->setAttribute(self::$_style_attr, $style);
    }

    /**
     * @param \DOMNode $node
     *
     * @return \DOMNodeList|\DOMElement[]
     */
    protected static function get_cell_list(\DOMNode $node)
    {
        $xpath = new \DOMXpath($node->ownerDocument);

        switch ($node->nodeName) {
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
    protected static function _get_valid_color($value)
    {
        if (preg_match('/^#?([0-9A-F]{6})$/i', $value, $matches)) {
            $value = "#$matches[1]";
        }

        return $value;
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return string
     */
    protected static function _set_color(\DOMElement $node, $value)
    {
        $value = self::_get_valid_color($value);

        return "color: $value;";
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return string
     */
    protected static function _set_background_color(\DOMElement $node, $value)
    {
        $value = self::_get_valid_color($value);

        return "background-color: $value;";
    }

    protected static function _set_px_width(\DOMElement $node, string $value): string
    {
        $v = trim($value);

        if (Helpers::is_percent($v)) {
            return sprintf("width: %s;", $v);
        }

        if (is_numeric(mb_substr($v, 0, 1))) {
            return sprintf("width: %spx;", (float) $v);
        }

        return "";
    }

    protected static function _set_px_height(\DOMElement $node, string $value): string
    {
        $v = trim($value);

        if (Helpers::is_percent($v)) {
            return sprintf("height: %s;", $v);
        }

        if (is_numeric(mb_substr($v, 0, 1))) {
            return sprintf("height: %spx;", (float) $v);
        }

        return "";
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return null
     */
    protected static function _set_table_cellpadding(\DOMElement $node, $value)
    {
        $cell_list = self::get_cell_list($node);

        foreach ($cell_list as $cell) {
            self::append_style($cell, "; padding: {$value}px;");
        }

        return null;
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return string
     */
    protected static function _set_table_border(\DOMElement $node, $value)
    {
        return "border-width: $value" . "px;";
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return string
     */
    protected static function _set_table_cellspacing(\DOMElement $node, $value)
    {
        $style = rtrim($node->getAttribute(self::$_style_attr), ";");

        if ($value == 0) {
            $style .= "; border-collapse: collapse;";
        } else {
            $style .= "; border-spacing: {$value}px; border-collapse: separate;";
        }

        return ltrim($style, ";");
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return null|string
     */
    protected static function _set_table_rules(\DOMElement $node, $value)
    {
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
     * @param \DOMElement $node
     * @param string $value
     *
     * @return string
     */
    protected static function _set_hr_size(\DOMElement $node, $value)
    {
        $style = rtrim($node->getAttribute(self::$_style_attr), ";");
        $style .= "; border-width: " . max(0, $value - 2) . "; ";

        return ltrim($style, "; ");
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return null|string
     */
    protected static function _set_hr_align(\DOMElement $node, $value)
    {
        $style = rtrim($node->getAttribute(self::$_style_attr), ";");
        $width = $node->getAttribute("width");

        if ($width == "") {
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
     * @param \DOMElement $node
     * @param string $value
     *
     * @return null|string
     */
    protected static function _set_input_width(\DOMElement $node, $value)
    {
        if (empty($value)) { return null; }

        if ($node->hasAttribute("type") && in_array(strtolower($node->getAttribute("type")), ["text","password"])) {
            return sprintf("width: %Fem", (((int)$value * .65)+2));
        } else {
            return sprintf("width: %upx;", (int)$value);
        }
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return null
     */
    protected static function _set_table_row_align(\DOMElement $node, $value)
    {
        $cell_list = self::get_cell_list($node);

        foreach ($cell_list as $cell) {
            self::append_style($cell, "; text-align: $value;");
        }

        return null;
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return null
     */
    protected static function _set_table_row_valign(\DOMElement $node, $value)
    {
        $cell_list = self::get_cell_list($node);

        foreach ($cell_list as $cell) {
            self::append_style($cell, "; vertical-align: $value;");
        }

        return null;
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return null
     */
    protected static function _set_table_row_bgcolor(\DOMElement $node, $value)
    {
        $cell_list = self::get_cell_list($node);
        $value = self::_get_valid_color($value);

        foreach ($cell_list as $cell) {
            self::append_style($cell, "; background-color: $value;");
        }

        return null;
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return null
     */
    protected static function _set_body_link(\DOMElement $node, $value)
    {
        $a_list = $node->getElementsByTagName("a");
        $value = self::_get_valid_color($value);

        foreach ($a_list as $a) {
            self::append_style($a, "; color: $value;");
        }

        return null;
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return null
     */
    protected static function _set_basefont_size(\DOMElement $node, $value)
    {
        // FIXME: ? we don't actually set the font size of anything here, just
        // the base size for later modification by <font> tags.
        self::$_last_basefont_size = $value;

        return null;
    }

    /**
     * @param \DOMElement $node
     * @param string $value
     *
     * @return string
     */
    protected static function _set_font_size(\DOMElement $node, $value)
    {
        $style = $node->getAttribute(self::$_style_attr);

        if ($value[0] === "-" || $value[0] === "+") {
            $value = self::$_last_basefont_size + (int)$value;
        }

        if (isset(self::$_font_size_lookup[$value])) {
            $style .= "; font-size: " . self::$_font_size_lookup[$value] . ";";
        } else {
            $style .= "; font-size: $value;";
        }

        return ltrim($style, "; ");
    }
}
