<?php
namespace Dompdf\Tests;

use Dompdf\Options;
use PHPUnit_Framework_TestCase;

class OptionsTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $root = realpath(__DIR__ . "/../../..");
        $option = new Options();
        $this->assertEquals(sys_get_temp_dir(), $option->getTempDir());
        $this->assertEquals($root . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'fonts', $option->getFontDir());
        $this->assertEquals($root . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'fonts', $option->getFontCache());
        $this->assertEquals($root, $option->getChroot());
        $this->assertEquals(sys_get_temp_dir() . DIRECTORY_SEPARATOR . "log.htm", $option->getLogOutputFile());
        $this->assertEquals('screen', $option->getDefaultMediaType());
        $this->assertEquals('letter', $option->getDefaultPaperSize());
        $this->assertEquals('serif', $option->getDefaultFont());
        $this->assertEquals(96, $option->getDpi());
        $this->assertEquals(1.1, $option->getFontHeightRatio());
        $this->assertFalse($option->getIsPhpEnabled());
        $this->assertFalse($option->getIsRemoteEnabled());
        $this->assertTrue($option->getIsJavascriptEnabled());
        $this->assertFalse($option->getIsHtml5ParserEnabled());
        $this->assertFalse($option->getIsFontSubsettingEnabled());
        $this->assertFalse($option->getDebugPng());
        $this->assertFalse($option->getDebugKeepTemp());
        $this->assertFalse($option->getDebugCss());
        $this->assertFalse($option->getDebugLayout());
        $this->assertTrue($option->getDebugLayoutLines());
        $this->assertTrue($option->getDebugLayoutBlocks());
        $this->assertTrue($option->getDebugLayoutInline());
        $this->assertTrue($option->getDebugLayoutPaddingBox());
        $this->assertEquals('user', $option->getAdminUsername());
        $this->assertEquals('password', $option->getAdminPassword());

        $option = new Options(array('tempDir' => 'test1'));
        $this->assertEquals('test1', $option->getTempDir());
    }

    public function testSetters()
    {
        $option = new Options();
        $option->set(array(
            'tempDir' => 'test1',
            'fontDir' => 'test2',
            'fontCache' => 'test3',
            'chroot' => 'test4',
            'logOutputFile' => 'test5',
            'defaultMediaType' => 'test6',
            'defaultPaperSize' => 'test7',
            'defaultFont' => 'test8',
            'dpi' => 300,
            'fontHeightRatio' => 1.2,
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
            'isJavascriptEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'debugPng' => true,
            'debugKeepTemp' => true,
            'debugCss' => true,
            'debugLayout' => true,
            'debugLayoutLines' => false,
            'debugLayoutBlocks' => false,
            'debugLayoutInline' => false,
            'debugLayoutPaddingBox' => false,
            'adminUsername' => 'test9',
            'adminPassword' => 'test10',
        ));
        $this->assertEquals('test1', $option->getTempDir());
        $this->assertEquals('test2', $option->getFontDir());
        $this->assertEquals('test3', $option->getFontCache());
        $this->assertEquals('test4', $option->getChroot());
        $this->assertEquals('test5', $option->getLogOutputFile());
        $this->assertEquals('test6', $option->getDefaultMediaType());
        $this->assertEquals('test7', $option->getDefaultPaperSize());
        $this->assertEquals('test8', $option->getDefaultFont());
        $this->assertEquals(300, $option->getDpi());
        $this->assertEquals(1.2, $option->getFontHeightRatio());
        $this->assertTrue($option->getIsPhpEnabled());
        $this->assertTrue($option->getIsRemoteEnabled());
        $this->assertFalse($option->getIsJavascriptEnabled());
        $this->assertTrue($option->getIsHtml5ParserEnabled());
        $this->assertTrue($option->getIsFontSubsettingEnabled());
        $this->assertTrue($option->getDebugPng());
        $this->assertTrue($option->getDebugKeepTemp());
        $this->assertTrue($option->getDebugCss());
        $this->assertTrue($option->getDebugLayout());
        $this->assertFalse($option->getDebugLayoutLines());
        $this->assertFalse($option->getDebugLayoutBlocks());
        $this->assertFalse($option->getDebugLayoutInline());
        $this->assertFalse($option->getDebugLayoutPaddingBox());
        $this->assertEquals('test9', $option->getAdminUsername());
        $this->assertEquals('test10', $option->getAdminPassword());
    }
}