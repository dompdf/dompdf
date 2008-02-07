<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: canvas_factory.cls.php,v $
 * Created on: 2004-06-02
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

/* $Id: canvas_factory.cls.php,v 1.5 2008-02-07 07:31:05 benjcarson Exp $ */

/**
 * Create canvas instances
 *
 * The canvas factory creates canvas instances based on the
 * availability of rendering backends and config options.
 *
 * @package dompdf
 */
class Canvas_Factory {

  /**
   * Constructor is private: this is a static class
   */
  private function __construct() { }

  static function get_instance($paper = null, $orientation = null,  $class = null) {

    $backend = strtolower(DOMPDF_PDF_BACKEND);
    
    if ( isset($class) && class_exists($class, false) )
      $class .= "_Adapter";
    
    else if ( (DOMPDF_PDF_BACKEND == "auto" || $backend == "pdflib" ) &&
              class_exists("PDFLib", false) )
      $class = "PDFLib_Adapter";

    else if ( (DOMPDF_PDF_BACKEND == "auto" || $backend == "cpdf") )
      $class = "CPDF_Adapter";

    else if ( ( $backend == "tcpdf") )
      $class = "TCPDF_Adapter";
      
    else if ( $backend == "gd" )
      $class = "GD_Adapter";
    
    else
      $class = "CPDF_Adapter";

    return new $class($paper, $orientation);
        
  }
}
?>