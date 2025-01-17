<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
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
     * @param Dompdf         $dompdf
     * @param string|float[] $paper
     * @param string         $orientation
     * @param string|null    $class
     *
     * @return Canvas
     */
    static function get_instance(Dompdf $dompdf, $paper, string $orientation, ?string $class = null)
    {
        $backend = strtolower($dompdf->getOptions()->getPdfBackend());

        if (isset($class) && class_exists($class, false)) {
            $class .= "_Adapter";
        } else {
            if (($backend === "auto" || $backend === "pdflib") &&
                class_exists("PDFLib", false)
            ) {
                $class = "Dompdf\\Adapter\\PDFLib";
            }

            else {
                if (class_exists($backend, false)) {
                    $class = $backend;
                } elseif ($backend === "gd" && extension_loaded('gd')) {
                    $class = "Dompdf\\Adapter\\GD";
                } else {
                    $class = "Dompdf\\Adapter\\CPDF";
                }
            }
        }

        $instance = new $class($paper, $orientation, $dompdf);

        $class_interfaces = class_implements($class, false);
        if (!$class_interfaces || !in_array("Dompdf\\Canvas", $class_interfaces)) {
            $class = "Dompdf\\Adapter\\CPDF";
            $instance = new $class($paper, $orientation, $dompdf);
        }

        return $instance;
    }
}
