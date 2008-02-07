<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: cached_pdf_decorator.cls.php,v $
 * Created on: 2004-07-23
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

/* $Id: cached_pdf_decorator.cls.php,v 1.4 2008-02-07 07:31:05 benjcarson Exp $ */

/**
 * Caching canvas implementation
 *
 * Each rendered page is serialized and stored in the {@link Page_Cache}.
 * This is useful for static forms/pages that do not need to be re-rendered
 * all the time.
 *
 * This class decorates normal CPDF_Adapters.  It is currently completely
 * experimental.
 *
 * @access private
 * @package dompdf
 */
class Cached_PDF_Decorator extends CPDF_Adapter implements Canvas {
  protected $_pdf;
  protected $_cache_id;
  protected $_current_page_id;
  protected $_fonts;  // fonts used in this document
  
  function __construct($cache_id, CPDF_Adapter $pdf) {
    $this->_pdf = $pdf;
    $this->_cache_id = $cache_id;
    $this->_fonts = array();
    
    $this->_current_page_id = $this->_pdf->open_object();
  }

  //........................................................................

  function get_cpdf() { return $this->_pdf->get_cpdf(); }

  function open_object() { $this->_pdf->open_object(); }
  function reopen_object() { return $this->_pdf->reopen_object(); }
  
  function close_object() { $this->_pdf->close_object(); }

  function add_object($object, $where = 'all') { $this->_pdf->add_object($object, $where); }

  function serialize_object($id) { $this->_pdf->serialize_object($id); }

  function reopen_serialized_object($obj) { $this->_pdf->reopen_serialized_object($obj); }
    
  //........................................................................

  function get_width() { return $this->_pdf->get_width(); }
  function get_height() {  return $this->_pdf->get_height(); }
  function get_page_number() { return $this->_pdf->get_page_number(); }
  function get_page_count() { return $this->_pdf->get_page_count(); }

  function set_page_number($num) { $this->_pdf->set_page_number($num); }
  function set_page_count($count) { $this->_pdf->set_page_count($count); }

  function line($x1, $y1, $x2, $y2, $color, $width, $style = array()) {
    $this->_pdf->line($x1, $y1, $x2, $y2, $color, $width, $style);
  }
                              
  function rectangle($x1, $y1, $w, $h, $color, $width, $style = array()) {
    $this->_pdf->rectangle($x1, $y1, $w, $h, $color, $width, $style);
  }
 
  function filled_rectangle($x1, $y1, $w, $h, $color) {
    $this->_pdf->filled_rectangle($x1, $y1, $w, $h, $color);
  }
    
  function polygon($points, $color, $width = null, $style = array(), $fill = false) {
    $this->_pdf->polygon($points, $color, $width, $style, $fill);
  }

  function circle($x, $y, $r1, $color, $width = null, $style = null, $fill = false) {
    $this->_pdf->circle($x, $y, $r1, $color, $width, $style, $fill);
  }

  function image($img_url, $x, $y, $w = null, $h = null) {
    $this->_pdf->image($img_url, $x, $y, $w, $h);
  }
  
  function text($x, $y, $text, $font, $size, $color = array(0,0,0), $adjust = 0, $angle = 0) {
    $this->_fonts[$font] = true;
    $this->_pdf->text($x, $y, $text, $font, $size, $color, $adjust, $angle);
  }

  function page_text($x, $y, $text, $font, $size, $color = array(0,0,0), $adjust = 0, $angle = 0) {
    
    // We want to remove this from cached pages since it may not be correct
    $this->_pdf->close_object();
    $this->_pdf->page_text($x, $y, $text, $font, $size, $color, $adjust, $angle);
    $this->_pdf->reopen_object($this->_current_page_id);
  }
  
  function page_script($script, $type = 'text/php') {
    
    // We want to remove this from cached pages since it may not be correct
    $this->_pdf->close_object();
    $this->_pdf->page_script($script, $type);
    $this->_pdf->reopen_object($this->_current_page_id);
  }
  
  function new_page() {
    $this->_pdf->close_object();

    // Add the object to the current page
    $this->_pdf->add_object($this->_current_page_id, "add");
    $this->_pdf->new_page();    

    Page_Cache::store_page($this->_cache_id,
                           $this->_pdf->get_page_number() - 1,
                           $this->_pdf->serialize_object($this->_current_page_id));

    $this->_current_page_id = $this->_pdf->open_object();
    return $this->_current_page_id;
  }
  
  function stream($filename) {
    // Store the last page in the page cache
    if ( !is_null($this->_current_page_id) ) {
      $this->_pdf->close_object();
      $this->_pdf->add_object($this->_current_page_id, "add");
      Page_Cache::store_page($this->_cache_id,
                             $this->_pdf->get_page_number(),
                             $this->_pdf->serialize_object($this->_current_page_id));
      Page_Cache::store_fonts($this->_cache_id, $this->_fonts);
      $this->_current_page_id = null;
    }
    
    $this->_pdf->stream($filename);
    
  }
  
  function &output() {
    // Store the last page in the page cache
    if ( !is_null($this->_current_page_id) ) {
      $this->_pdf->close_object();
      $this->_pdf->add_object($this->_current_page_id, "add");
      Page_Cache::store_page($this->_cache_id,
                             $this->_pdf->get_page_number(),
                             $this->_pdf->serialize_object($this->_current_page_id));
      $this->_current_page_id = null;
    }
    
    return $this->_pdf->output();
  }
  
  function get_messages() { return $this->_pdf->get_messages(); }
  
}

?>