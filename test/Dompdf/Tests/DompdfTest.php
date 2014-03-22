<?php
namespace Dompdf\Tests;

use Dompdf\Dompdf;
use PHPUnit_Framework_TestCase;

class DompdfTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        new Dompdf();
    }

    public function testLoadHtml()
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml('<strong>Hello</strong>');
    }
}