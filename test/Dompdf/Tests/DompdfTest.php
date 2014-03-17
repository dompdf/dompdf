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
}