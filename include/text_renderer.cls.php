<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: text_renderer.cls.php,v $
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
 * @version dompdf_trunk_with_helmut_mods.20090528
 * - fix text decoration positions according to font metrics
 * @version 20090610
 * - better accuracy on using different renderer as cpdf, added comments
 */

/* $Id: text_renderer.cls.php,v 1.8 2008-03-12 06:35:43 benjcarson Exp $ */
/**
 * Renders text frames
 *
 * @access private
 * @package dompdf
 */
class Text_Renderer extends Abstract_Renderer {
  
  const DECO_THICKNESS = 0.04;     // Thickness of underline. Screen: 0.08, print: better less, e.g. 0.04

  //Tweaking if $base and $descent are not accurate.
  //Check method_exists( $this->_canvas, "get_cpdf" )
  //- For cpdf these can and must stay 0, because font metrics are used directly.
  //- For other renderers, if different values are wanted, separate the parameter sets.
  //  But $size and $size-$height seem to be accurate enough
  const UNDERLINE_OFFSET = 0.0;    // Relative to bottom of text, as fraction of height.
  const OVERLINE_OFFSET = 0.0;    // Relative to top of text
  const LINETHROUGH_OFFSET = 0.0; // Relative to centre of text.
  const DECO_EXTENSION = 0.0;     // How far to extend lines past either end, in pt
    
  //........................................................................

  function render(Frame $frame) {
    
    $style = $frame->get_style();
    list($x, $y) = $frame->get_position();
    $cb = $frame->get_containing_block();

    if ( ($ml = $style->margin_left) == "auto" || $ml == "none" )
      $ml = 0;

    if ( ($pl = $style->padding_left) == "auto" || $pl == "none" )
      $pl = 0;

    if ( ($bl = $style->border_left_width) == "auto" || $bl == "none" )
      $bl = 0;

    $x += $style->length_in_pt( array($ml, $pl, $bl), $cb["w"] );

    $text = $frame->get_text();
    
    $font = $style->font_family;
    $size = $style->font_size;
    $height = $style->height;    
    $spacing = $frame->get_text_spacing() + $style->word_spacing;

//     if ( preg_replace("/[\s]+/", "", $text) == "" )
//       return;
    
    $this->_canvas->text($x, $y, $text,
                         $font, $size,
                         $style->color, $spacing);

    if ( method_exists( $this->_canvas, "get_cpdf" ) ) {
      $base = ($this->_canvas->get_cpdf()->fonts[$this->_canvas->get_cpdf()->currentFont]['FontBBox'][3]*$size)/1000;
      $descent = ($this->_canvas->get_cpdf()->fonts[$this->_canvas->get_cpdf()->currentFont]['FontBBox'][1]*$size)/1000;
      //print '<pre>Text_Renderer cpdf:'.$base.' '.$descent.' '.$size.'</pre>';
    } else {
      //Descent is font part below baseline, typically negative. $height is about full height of font box.
      //$descent = -$size/6; is less accurate, depends on font family.
      $base = $size;
      $descent = $size-$height;
      //print '<pre>Text_Renderer other than cpdf:'.$base.' '.$descent.' '.$size.'</pre>';
    }
    
    // Handle text decoration:
    // http://www.w3.org/TR/CSS21/text.html#propdef-text-decoration
    
    // Draw all applicable text-decorations.  Start with the root and work
    // our way down.
    $p = $frame;
    $stack = array();
    while ( $p = $p->get_parent() )
      $stack[] = $p;
    
    while ( count($stack) > 0 ) {
      $f = array_pop($stack);

      $deco_y = $y;
      if ( ($text_deco = $f->get_style()->text_decoration) === "none" )
        continue;

      $color = $f->get_style()->color;

      switch ($text_deco) {

      default:
        continue;

      case "underline":
        $deco_y += $base - $descent+ $size * (self::UNDERLINE_OFFSET - self::DECO_THICKNESS/2);
        break;

      case "overline":
        $deco_y += $size * (self::OVERLINE_OFFSET + self::DECO_THICKNESS/2);
        break;

      case "line-through":
        $deco_y += $base * 0.7 + $size * self::LINETHROUGH_OFFSET;
        break;

      }

      $dx = 0;
      $x1 = $x - self::DECO_EXTENSION;
      $x2 = $x + $style->width + $dx + self::DECO_EXTENSION;
      $this->_canvas->line($x1, $deco_y, $x2, $deco_y, $color, $size * self::DECO_THICKNESS);

    }
  }

}

?>