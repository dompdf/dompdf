<?php
namespace Dompdf\Tests;

use Dompdf\Options;
use Dompdf\Tests\TestCase;

class OptionsTest extends TestCase
{
    public function testConstructor()
    {
        $root = realpath(dirname(__DIR__));
        $option = new Options();
        $this->assertEquals(sys_get_temp_dir(), $option->getTempDir());
        $this->assertEquals($root . '/lib/fonts', $option->getFontDir());
        $this->assertEquals($root . '/lib/fonts', $option->getFontCache());
        $this->assertEquals([$root], $option->getChroot());
        $this->assertEmpty($option->getLogOutputFile());
        $this->assertEquals('screen', $option->getDefaultMediaType());
        $this->assertEquals('letter', $option->getDefaultPaperSize());
        $this->assertEquals('serif', $option->getDefaultFont());
        $this->assertEquals(96, $option->getDpi());
        $this->assertEquals(1.1, $option->getFontHeightRatio());
        $this->assertFalse($option->getIsPhpEnabled());
        $this->assertFalse($option->getIsRemoteEnabled());
        $this->assertTrue($option->getIsJavascriptEnabled());
        $this->assertTrue($option->getIsFontSubsettingEnabled());
        $this->assertFalse($option->getDebugPng());
        $this->assertFalse($option->getDebugKeepTemp());
        $this->assertFalse($option->getDebugCss());
        $this->assertFalse($option->getDebugLayout());
        $this->assertTrue($option->getDebugLayoutLines());
        $this->assertTrue($option->getDebugLayoutBlocks());
        $this->assertTrue($option->getDebugLayoutInline());
        $this->assertTrue($option->getDebugLayoutPaddingBox());

        $option = new Options(['tempDir' => 'test1']);
        $this->assertEquals('test1', $option->getTempDir());
    }

    public function testSetters()
    {
        $option = new Options();
        $option->set([
            'tempDir' => 'test1',
            'fontDir' => 'test2',
            'fontCache' => 'test3',
            'chroot' => 'test4,test4a',
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
            'isFontSubsettingEnabled' => false,
            'debugPng' => true,
            'debugKeepTemp' => true,
            'debugCss' => true,
            'debugLayout' => true,
            'debugLayoutLines' => false,
            'debugLayoutBlocks' => false,
            'debugLayoutInline' => false,
            'debugLayoutPaddingBox' => false,
            'httpContext' => ['ssl' => ['verify_peer' => false]],
        ]);
        $this->assertEquals('test1', $option->getTempDir());
        $this->assertEquals('test2', $option->getFontDir());
        $this->assertEquals('test3', $option->getFontCache());
        $this->assertEquals(['test4','test4a'], $option->getChroot());
        $this->assertEquals('test5', $option->getLogOutputFile());
        $this->assertEquals('test6', $option->getDefaultMediaType());
        $this->assertEquals('test7', $option->getDefaultPaperSize());
        $this->assertEquals('test8', $option->getDefaultFont());
        $this->assertEquals(300, $option->getDpi());
        $this->assertEquals(1.2, $option->getFontHeightRatio());
        $this->assertTrue($option->getIsPhpEnabled());
        $this->assertTrue($option->getIsRemoteEnabled());
        $this->assertFalse($option->getIsJavascriptEnabled());
        $this->assertFalse($option->getIsFontSubsettingEnabled());
        $this->assertTrue($option->getDebugPng());
        $this->assertTrue($option->getDebugKeepTemp());
        $this->assertTrue($option->getDebugCss());
        $this->assertTrue($option->getDebugLayout());
        $this->assertFalse($option->getDebugLayoutLines());
        $this->assertFalse($option->getDebugLayoutBlocks());
        $this->assertFalse($option->getDebugLayoutInline());
        $this->assertFalse($option->getDebugLayoutPaddingBox());
        $this->assertIsResource($option->getHttpContext());

        $option->setChroot(['test11']);
        $this->assertEquals(['test11'], $option->getChroot());
    }

    public function testAllowedProtocols()
    {
        $options = new Options(["isRemoteEnabled" => false]);
        $options->setAllowedProtocols(["http://"]);
        $allowedProtocols = $options->getAllowedProtocols();
        $this->assertIsArray($allowedProtocols);
        $this->assertEquals(1, count($allowedProtocols));
        $this->assertArrayHasKey("http://", $allowedProtocols);
        $this->assertIsArray($allowedProtocols["http://"]);
        $this->assertArrayHasKey("rules", $allowedProtocols["http://"]);
        $this->assertIsArray($allowedProtocols["http://"]["rules"]);
        $this->assertEquals(1, count($allowedProtocols["http://"]["rules"]));
        $this->assertEquals([$options, "validateRemoteUri"], $allowedProtocols["http://"]["rules"][0]);

        [$validation_result] = $allowedProtocols["http://"]["rules"][0]("http://example.com/");
        $this->assertFalse($validation_result);

        
        $mock_protocol = [
            "mock://" => [
                "rules" => [
                    function ($uri) { return [true, null]; }
                ]
            ]
        ];
        $options->setAllowedProtocols($mock_protocol);
        $allowedProtocols = $options->getAllowedProtocols();
        $this->assertIsArray($allowedProtocols);
        $this->assertEquals(1, count($allowedProtocols));
        $this->assertArrayHasKey("mock://", $allowedProtocols);
        $this->assertIsArray($allowedProtocols["mock://"]);
        $this->assertArrayHasKey("rules", $allowedProtocols["mock://"]);
        $this->assertIsArray($allowedProtocols["mock://"]["rules"]);
        $this->assertEquals(1, count($allowedProtocols["mock://"]["rules"]));
        $this->assertEquals($mock_protocol["mock://"]["rules"][0], $allowedProtocols["mock://"]["rules"][0]);

        [$validation_result] = $allowedProtocols["mock://"]["rules"][0]("mock://example.com/");
        $this->assertTrue($validation_result);
    }
}
