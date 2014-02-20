<?php
namespace DomPdf\Tests;

use PHPUnit_Framework_TestCase;
use DomPdf\Autoloader;

class AutoloaderTest extends PHPUnit_Framework_TestCase
{
    public function testAutoload()
    {
        $declared = get_declared_classes();
        $declaredCount = count($declared);
        Autoloader::autoload('Foo');
        $this->assertEquals($declaredCount, count(get_declared_classes()), 'DomPdf\\Autoloader::autoload() is trying to load classes outside of the DomPdf namespace');
        Autoloader::autoload('DomPdf\Frame\FrameList'); // TODO change this class to the main DomPdf class when it is namespaced
        $this->assertTrue(in_array('DomPdf\Frame\FrameList', get_declared_classes()), 'DomPdf\\Autoloader::autoload() failed to autoload the DomPdf\Frame\FrameList class');
    }
}