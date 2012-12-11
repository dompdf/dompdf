<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Renders block frames
 *
 * @access private
 * @package dompdf
 */
class Block_Renderer extends Abstract_Renderer {

  //........................................................................

  function render(Frame $frame) {
    $style = $frame->get_style(); 
    list($x, $y, $w, $h) = $frame->get_border_box();
    
    $this->_set_opacity( $frame->get_opacity( $style->opacity ) );

    if ( $frame->get_node()->nodeName === "body" ) {
      $h = $frame->get_containing_block("h") - $style->length_in_pt(array(
        $style->margin_top,
        $style->padding_top,
        $style->border_top_width,
        $style->border_bottom_width,
        $style->padding_bottom,
        $style->margin_bottom),
      $style->width);
    }
    
    // Draw our background, border and content
    list($tl, $tr, $br, $bl) = $style->get_computed_border_radius($w, $h);
    
    if ( $tl + $tr + $br + $bl > 0 ) {
      $this->_canvas->clipping_roundrectangle( $x, $y, $w, $h, $tl, $tr, $br, $bl );
    }
      
    if ( ($bg = $style->background_color) !== "transparent" ) {
      $this->_canvas->filled_rectangle( $x, $y, $w, $h, $bg );
    }

    if ( ($url = $style->background_image) && $url !== "none" ) {
      $this->_background_image($url, $x, $y, $w, $h, $style);
    }
    
    if ( $tl + $tr + $br + $bl > 0 ) {
      $this->_canvas->clipping_end();
    }

    $this->_render_border($frame);
    $this->_render_outline($frame);
    
    if (DEBUG_LAYOUT && DEBUG_LAYOUT_BLOCKS) {
      $this->_debug_layout($frame->get_border_box(), "red");
      if (DEBUG_LAYOUT_PADDINGBOX) {
        $this->_debug_layout($frame->get_padding_box(), "red", array(0.5, 0.5));
      }
    }
    
    if (DEBUG_LAYOUT && DEBUG_LAYOUT_LINES && $frame->get_decorator()) {
      foreach ($frame->get_decorator()->get_line_boxes() as $line) {
        $frame->_debug_layout(array($line->x, $line->y, $line->w, $line->h), "orange");
      }
    }
  }

  protected function _render_border(Frame_Decorator $frame, $corner_style = "bevel") {
    $style = $frame->get_style();
    $bbox = $frame->get_border_box();
    $bp = $style->get_border_properties();
    
    // find the radius
    $radius = $style->get_computed_border_radius($bbox["w"], $bbox["h"]);

    // Short-cut: If all the borders are "solid" with the same color and style, and no radius, we'd better draw a rectangle
    if (
        in_array($bp["top"]["style"], array("solid", "dashed", "dotted")) && 
        $bp["top"]    == $bp["right"] &&
        $bp["right"]  == $bp["bottom"] &&
        $bp["bottom"] == $bp["left"] &&
        array_sum($radius) == 0
    ) {
      $props = $bp["top"];
      if ( $props["color"] === "transparent" || $props["width"] <= 0 ) return;
      
      list($x, $y, $w, $h) = $bbox;
      $width = $style->length_in_pt($props["width"]);
      $pattern = $this->_get_dash_pattern($props["style"], $width);
      $this->_canvas->rectangle($x + $width / 2, $y + $width / 2, $w - $width, $h - $width, $props["color"], $width, $pattern);
      return;
    }

    // Do it the long way
    $widths = array($style->length_in_pt($bp["top"]["width"]),
                    $style->length_in_pt($bp["right"]["width"]),
                    $style->length_in_pt($bp["bottom"]["width"]),
                    $style->length_in_pt($bp["left"]["width"]));
    
    foreach ($bp as $side => $props) {
      list($x, $y, $w, $h) = $bbox;
      $length = 0;
      $r1 = 0;
      $r2 = 0;

      if ( !$props["style"] || 
            $props["style"] === "none" || 
            $props["width"] <= 0 || 
            $props["color"] == "transparent" )
        continue;

      switch($side) {
      case "top":
        $length = $w;
        $r1 = $radius["top-left"];
        $r2 = $radius["top-right"];
        break;

      case "bottom":
        $length = $w;
        $y += $h;
        $r1 = $radius["bottom-left"];
        $r2 = $radius["bottom-right"];
        break;

      case "left":
        $length = $h;
        $r1 = $radius["top-left"];
        $r2 = $radius["bottom-left"];
        break;

      case "right":
        $length = $h;
        $x += $w;
        $r1 = $radius["top-right"];
        $r2 = $radius["bottom-right"];
        break;
      default:
        break;
      }
      $method = "_border_" . $props["style"];
    
      // draw rounded corners
      $this->$method($x, $y, $length, $props["color"], $widths, $side, $corner_style, $r1, $r2);
    }
  }

  protected function _render_outline(Frame_Decorator $frame, $corner_style = "bevel") {
    $style = $frame->get_style();
    
    $props = array(
      "width" => $style->outline_width,
      "style" => $style->outline_style,
      "color" => $style->outline_color,
    );
    
    if ( !$props["style"] || $props["style"] === "none" || $props["width"] <= 0 )
      return;
      
    $bbox = $frame->get_border_box();
    $offset = $style->length_in_pt($props["width"]);
    $pattern = $this->_get_dash_pattern($props["style"], $offset);

    // If the outline style is "solid" we'd better draw a rectangle
    if ( in_array($props["style"], array("solid", "dashed", "dotted")) ) {
      $bbox[0] -= $offset / 2;
      $bbox[1] -= $offset / 2;
      $bbox[2] += $offset;
      $bbox[3] += $offset;
    
      list($x, $y, $w, $h) = $bbox;
      $this->_canvas->rectangle($x, $y, $w, $h, $props["color"], $offset, $pattern);
      return;
    }

    $bbox[0] -= $offset;
    $bbox[1] -= $offset;
    $bbox[2] += $offset * 2;
    $bbox[3] += $offset * 2;
    
    $method = "_border_" . $props["style"];
    $widths = array_fill(0, 4, $props["width"]);
    $sides = array("top", "right", "left", "bottom");
    $length = 0;
    
    foreach ($sides as $side) {
      list($x, $y, $w, $h) = $bbox;

      switch($side) {
      case "top":
        $length = $w;
        break;

      case "bottom":
        $length = $w;
        $y += $h;
        break;

      case "left":
        $length = $h;
        break;

      case "right":
        $length = $h;
        $x += $w;
        break;
      default:
        break;
      }

      $this->$method($x, $y, $length, $props["color"], $widths, $side, $corner_style);
    }
  }
}
