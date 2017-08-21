<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

use DOMDocument;

interface DompdfInterface
{
    /**
     * @return Options
     */
    public function getOptions();

    /**
     * @return DOMDocument
     */
    public function getDom();

    /**
     * @return void
     */
    public function render();

    /**
     * @return string
     */
    public function output( $options = null );

    /**
     * @return void
     */
    public function loadHtml( $str, $encoding = 'UTF-8' );

    /**
     * @return DompdfInterface
     */
    public function clone();

}
