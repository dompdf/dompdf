<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: generated_frame_reflower.cls.php,v $
 * Created on: 2004-06-23
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

/* $Id: generated_frame_reflower.cls.php,v 1.6 2006-10-12 22:02:15 benjcarson Exp $ */

/**
 * Reflows generated content frames (decorates reflower)
 *
 * @access private
 * @package dompdf
 */
class Generated_Frame_Reflower extends Frame_Reflower {

  protected $_reflower; // Decoration target

  function __construct(Frame $frame) {
    parent::__construct($frame);
  }

  function set_reflower(Frame_Reflower $reflow) {
    $this->_reflower = $reflow;
  }

  //........................................................................

  protected function _parse_string($string) {
    $string = trim($string, "'\"");
    $string = str_replace(array("\\\n",'\\"',"\\'"),
                          array("",'"',"'"), $string);

    // Convert escaped hex characters into ascii characters (e.g. \A => newline)
    $string = preg_replace_callback("/\\\\([0-9a-fA-F]{0,6})(\s)?(?(2)|(?=[^0-9a-fA-F]))/",
                                    create_function('$matches',
                                                    'return chr(hexdec($matches[1]));'),
                                    $string);
    return $string;
  }

  protected function _parse_content() {
    $style = $this->_frame->get_style();

    // Matches generated content
    $re = "/\n".
      "\s(counters?\\([^)]*\\))|\n".
      "\A(counters?\\([^)]*\\))|\n".
      "\s([\"']) ( (?:[^\"']|\\\\[\"'])+ )(?<!\\\\)\\3|\n".
      "\A([\"']) ( (?:[^\"']|\\\\[\"'])+ )(?<!\\\\)\\5|\n" .
      "\s([^\s\"']+)|\n" .
      "\A([^\s\"']+)\n".
      "/xi";

    $content = $style->content;

    // split on spaces, except within quotes
    if (!preg_match_all($re, $content, $matches, PREG_SET_ORDER))
      return;

    $text = "";

    foreach ($matches as $match) {
      if ( isset($match[2]) && $match[2] !== "" )
        $match[1] = $match[1];

      if ( isset($match[6]) && $match[6] !== "" )
        $match[4] = $match[6];

      if ( isset($match[8]) && $match[8] !== "" )
        $match[7] = $match[8];

      if ( isset($match[1]) && $match[1] !== "" ) {
        // counters?(...)
        $match[1] = mb_strtolower(trim($match[1]));

        // Handle counter() references:
        // http://www.w3.org/TR/CSS21/generate.html#content

        $i = mb_strpos($match[1], ")");
        if ( $i === false )
          continue;

        $args = explode(",", mb_substr($match[1], 7, $i - 7));
        $counter_id = $args[0];

        if ( $match[1]{7} == "(" ) {
          // counter(name [,style])

          if ( isset($args[1]) )
            $type = $args[1];
          else
            $type = null;


          $p = $this->_frame->find_block_parent();

          $text .= $p->counter_value($counter_id, $type);

        } else if ( $match[1]{7} == "s" ) {
          // counters(name, string [,style])
          if ( isset($args[1]) )
            $string = $this->_parse_string(trim($args[1]));
          else
            $string = "";

          if ( isset($args[2]) )
            $type = $args[2];
          else
            $type = null;

          $p = $this->_frame->find_block_parent();
          $tmp = "";
          while ($p) {
            $tmp = $p->counter_value($counter_id, $type) . $string . $tmp;
            $p = $p->find_block_parent();
          }
          $text .= $tmp;

        } else
          // countertops?
          continue;

      } else if ( isset($match[4]) && $match[4] !== "" ) {
        // String match
        $text .= $this->_parse_string($match[4]);

      } else if ( isset($match[7]) && $match[7] !== "" ) {
        // Directive match

        if ( $match[7] === "open-quote" ) {
          // FIXME: do something here
        } else if ( $match[7] === "close-quote" ) {
          // FIXME: do something else here
        } else if ( $match[7] === "no-open-quote" ) {
          // FIXME:
        } else if ( $match[7] === "no-close-quote" ) {
          // FIXME:
        } else if ( mb_strpos($match[7],"attr(") === 0 ) {

          $i = mb_strpos($match[7],")");
          if ( $i === false )
            continue;

          $attr = mb_substr($match[7], 6, $i - 6);
          if ( $attr == "" )
            continue;

          $text .= $this->_frame->get_node()->getAttribute($attr);
        } else
          continue;

      }
    }

    return $text;

  }

  function reflow() {
    $style = $this->_frame->get_style();

    $text = $this->_parse_content();
    $t_node = $this->_frame->get_node()->ownerDocument->createTextNode($text);
    $t_frame = new Frame($t_node);
    $t_style = $style->get_stylesheet()->create_style();
    $t_style->inherit($style);
    $t_frame->set_style($t_style);

    $this->_frame->prepend_child(Frame_Factory::decorate_frame($t_frame));
    $this->_reflower->reflow();
  }
}

?>