<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

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

  /**
   * @param DOMPDF       $dompdf
   * @param string|array $paper
   * @param string       $orientation
   * @param string       $class
   *
   * @return Canvas
   */
  static function get_instance(DOMPDF $dompdf, $paper = null, $orientation = null, $class = null) {

    $backend = strtolower(DOMPDF_PDF_BACKEND);
    
    if ( isset($class) && class_exists($class, false) ) {
      $class .= "_Adapter";
    }
    
    else if ( (DOMPDF_PDF_BACKEND === "auto" || $backend === "pdflib" ) &&
              class_exists("PDFLib", false) ) {
      $class = "PDFLib_Adapter";
    }

    // FIXME The TCPDF adapter is not ready yet
    //else if ( (DOMPDF_PDF_BACKEND === "auto" || $backend === "cpdf") )
    //  $class = "CPDF_Adapter";

    else if ( $backend === "tcpdf" ) {
      $class = "TCPDF_Adapter";
    }
      
    else if ( $backend === "gd" ) {
      $class = "GD_Adapter";
    }
    
    else {
      $class = "CPDF_Adapter";
    }

    return new $class($paper, $orientation, $dompdf);
  }
}
