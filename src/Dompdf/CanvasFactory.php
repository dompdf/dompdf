<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

/**
 * Create canvas instances
 *
 * The canvas factory creates canvas instances based on the
 * availability of rendering backends and config options.
 *
 * @package dompdf
 */
class CanvasFactory
{
    /**
     * Constructor is private: this is a static class
     */
    private function __construct()
    {
    }

    /**
     * @param Dompdf $dompdf
     * @param string|array $paper
     * @param string $orientation
     * @param string $class
     *
     * @return Canvas
     */
    static function get_instance(Dompdf $dompdf, $paper = null, $orientation = null, $class = null)
    {
        $DOMPDF_PDF_BACKEND = $dompdf->get_option('pdf_backend');
        $backend = strtolower($DOMPDF_PDF_BACKEND);

        if (isset($class) && class_exists($class, false)) {
            $class .= "_Adapter";
        } else {
            if (($DOMPDF_PDF_BACKEND === "auto" || $backend === "pdflib") &&
                class_exists("PDFLib", false)
            ) {
                $class = "Dompdf\\Adapter\\PDFLib";
            }

            // FIXME The TCPDF adapter is not ready yet
            //else if ( ($DOMPDF_PDF_BACKEND === "auto" || $backend === "cpdf") )
            //  $class = "Dompdf\\Adapter\\CPDF";

            else {
                if ($backend === "tcpdf") {
                    $class = "Dompdf\\Adapter\\TCPDF";
                } else {
                    if ($backend === "gd") {
                        $class = "Dompdf\\Adapter\\GD";
                    } else {
                        $class = "Dompdf\\Adapter\\CPDF";
                    }
                }
            }
        }

        return new $class($paper, $orientation, $dompdf);
    }
}