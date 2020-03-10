<?php
namespace Dompdf\Tests;

use PHPUnit\Framework\TestCase;
use Dompdf\Autoloader;

class AutoloaderTest extends TestCase
{
    public function testAutoload()
    {
        Autoloader::register();

        $declared = get_declared_classes();
        $declaredCount = count($declared);
        Autoloader::autoload('Foo');
        $this->assertEquals($declaredCount, count(get_declared_classes()), 'Dompdf\\Autoloader::autoload() is trying to load classes outside of the Dompdf namespace');
        Autoloader::autoload('Dompdf\Dompdf');
        $this->assertTrue(in_array('Dompdf\Dompdf', get_declared_classes()), 'Dompdf\\Autoloader::autoload() failed to autoload the Dompdf\Dompdf class');
    }
}