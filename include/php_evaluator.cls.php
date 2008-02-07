<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: php_evaluator.cls.php,v $
 * Created on: 2004-07-12
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

/* $Id: php_evaluator.cls.php,v 1.5 2008-02-07 07:31:05 benjcarson Exp $ */

/**
 * Executes inline PHP code during the rendering process
 *
 * @access private
 * @package dompdf
 */
class PHP_Evaluator {
  
  protected $_canvas;

  function __construct(Canvas $canvas) {
    $this->_canvas = $canvas;
  }

  function evaluate($code, $vars = array()) {
    if ( !DOMPDF_ENABLE_PHP )
      return;
    
    // Set up some variables for the inline code
    $pdf = $this->_canvas;
    $PAGE_NUM = $pdf->get_page_number();
    $PAGE_COUNT = $pdf->get_page_count();
    
    // Override those variables if passed in
    foreach ($vars as $k => $v) {
        $$k = $v;
    }

    eval(utf8_decode($code)); 
  }

  function render($frame) {
    $this->evaluate($frame->get_node()->nodeValue);
  }
}
?>