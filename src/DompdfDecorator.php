<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

abstract class DompdfDecorator implements DompdfInterface
{
    /**
     * @var DompdfInterface
     */
    protected $pdf;

    public function __construct( DompdfInterface $pdf ) {
        $this->pdf = $pdf;
    }

    public function getOptions() {
        return $this->pdf->getOptions();
    }

    public function getDom() {
        return $this->pdf->getDom();
    }

    public function render() {
        $this->pdf->render();
    }

    public function output( $options = null ) {
        return $this->pdf->output($options);
    }

    public function loadHtml( $html, $encoding = 'UTF-8' ) {
        $this->pdf->loadHtml($html, $encoding);
    }

    public function clone() {
        $this->pdf = $this->pdf->clone();
        return $this;
    }

}
