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
        $this->assertNull($option->getAllowedRemoteHosts());

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
            'allowedProtocols' => ['http://' => []],
            'logOutputFile' => 'test5',
            'defaultMediaType' => 'test6',
            'defaultPaperSize' => 'test7',
            'defaultPaperOrientation' => 'landscape',
            'defaultFont' => 'test8',
            'dpi' => 300,
            'fontHeightRatio' => 1.2,
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
            'allowedRemoteHosts' => ['w3.org'],
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
            'pdfBackend' => 'CPDF',
            'pdflibLicense' => 'test9',
            'httpContext' => ['ssl' => ['verify_peer' => false]],
        ]);
        $this->assertEquals('test1', $option->getTempDir());
        $this->assertEquals('test2', $option->getFontDir());
        $this->assertEquals('test3', $option->getFontCache());
        $this->assertEquals(['test4','test4a'], $option->getChroot());
        $this->assertSame(['http://'], \array_keys($option->getAllowedProtocols()));
        $this->assertEquals('test5', $option->getLogOutputFile());
        $this->assertEquals('test6', $option->getDefaultMediaType());
        $this->assertEquals('test7', $option->getDefaultPaperSize());
        $this->assertEquals('landscape', $option->getDefaultPaperOrientation());
        $this->assertEquals('test8', $option->getDefaultFont());
        $this->assertEquals(300, $option->getDpi());
        $this->assertEquals(1.2, $option->getFontHeightRatio());
        $this->assertTrue($option->getIsPhpEnabled());
        $this->assertTrue($option->getIsRemoteEnabled());
        $this->assertEquals(['w3.org'], $option->getAllowedRemoteHosts());
        $this->assertFalse($option->getIsJavascriptEnabled());
        $this->assertTrue($option->getIsHtml5ParserEnabled());
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
        $this->assertEquals('CPDF', $option->getPdfBackend());
        $this->assertEquals('test9', $option->getPdflibLicense());
        $this->assertIsArray($option->getAllowedRemoteHosts());

        $option->setChroot(['test11']);
        $this->assertEquals(['test11'], $option->getChroot());
    }

    public function testSettersWithUnderscores()
    {
        $option = new Options();
        $option->set([
            'temp_dir' => 'test1',
            'font_dir' => 'test2',
            'font_cache' => 'test3',
            'chroot' => 'test4,test4a',
            'allowed_protocols' => ['http://' => []],
            'log_output_file' => 'test5',
            'default_media_type' => 'test6',
            'default_paper_size' => 'test7',
            'default_paper_orientation' => 'landscape',
            'default_font' => 'test8',
            'dpi' => 300,
            'font_height_ratio' => 1.2,
            'is_php_enabled' => true,
            'is_remote_enabled' => true,
            'allowed_remote_hosts' => ['w3.org'],
            'is_javascript_enabled' => false,
            'is_html5_parser_enabled' => true,
            'is_font_subsetting_enabled' => false,
            'debug_png' => true,
            'debug_keep_temp' => true,
            'debug_css' => true,
            'debug_layout' => true,
            'debug_layout_lines' => false,
            'debug_layout_blocks' => false,
            'debug_layout_inline' => false,
            'debug_layout_padding_box' => false,
            'pdf_backend' => 'CPDF',
            'pdflib_license' => 'test9',
            'http_context' => ['ssl' => ['verify_peer' => false]],
        ]);
        $this->assertEquals('test1', $option->getTempDir());
        $this->assertEquals('test2', $option->getFontDir());
        $this->assertEquals('test3', $option->getFontCache());
        $this->assertEquals(['test4','test4a'], $option->getChroot());
        $this->assertSame(['http://'], \array_keys($option->getAllowedProtocols()));
        $this->assertEquals('test5', $option->getLogOutputFile());
        $this->assertEquals('test6', $option->getDefaultMediaType());
        $this->assertEquals('test7', $option->getDefaultPaperSize());
        $this->assertEquals('landscape', $option->getDefaultPaperOrientation());
        $this->assertEquals('test8', $option->getDefaultFont());
        $this->assertEquals(300, $option->getDpi());
        $this->assertEquals(1.2, $option->getFontHeightRatio());
        $this->assertTrue($option->getIsPhpEnabled());
        $this->assertTrue($option->getIsRemoteEnabled());
        $this->assertEquals(['w3.org'], $option->getAllowedRemoteHosts());
        $this->assertFalse($option->getIsJavascriptEnabled());
        $this->assertTrue($option->getIsHtml5ParserEnabled());
        $this->assertFalse($option->getIsFontSubsettingEnabled());
        $this->assertTrue($option->getDebugPng());
        $this->assertTrue($option->getDebugKeepTemp());
        $this->assertTrue($option->getDebugCss());
        $this->assertTrue($option->getDebugLayout());
        $this->assertFalse($option->getDebugLayoutLines());
        $this->assertFalse($option->getDebugLayoutBlocks());
        $this->assertFalse($option->getDebugLayoutInline());
        $this->assertFalse($option->getDebugLayoutPaddingBox());
        $this->assertEquals('CPDF', $option->getPdfBackend());
        $this->assertEquals('test9', $option->getPdflibLicense());
        $this->assertIsResource($option->getHttpContext());
    }

    public function testGetters()
    {
        $option = new Options([
            'tempDir' => 'test1',
            'fontDir' => 'test2',
            'fontCache' => 'test3',
            'chroot' => 'test4,test4a',
            'allowedProtocols' => ['http://' => []],
            'logOutputFile' => 'test5',
            'defaultMediaType' => 'test6',
            'defaultPaperSize' => 'test7',
            'defaultPaperOrientation' => 'landscape',
            'defaultFont' => 'test8',
            'dpi' => 300,
            'fontHeightRatio' => 1.2,
            'isPhpEnabled' => true,
            'isRemoteEnabled' => true,
            'allowedRemoteHosts' => ['w3.org'],
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
            'pdfBackend' => 'CPDF',
            'pdflibLicense' => 'test9',
            'httpContext' => ['ssl' => ['verify_peer' => false]],
        ]);

        $this->assertEquals('test1', $option->get('tempDir'));
        $this->assertEquals('test2', $option->get('fontDir'));
        $this->assertEquals('test3', $option->get('fontCache'));
        $this->assertEquals(['test4', 'test4a'], $option->get('chroot'));
        $this->assertSame(['http://'], \array_keys($option->getAllowedProtocols()));
        $this->assertEquals('test5', $option->get('logOutputFile'));
        $this->assertEquals('test6', $option->get('defaultMediaType'));
        $this->assertEquals('test7', $option->get('defaultPaperSize'));
        $this->assertEquals('landscape', $option->get('defaultPaperOrientation'));
        $this->assertEquals('test8', $option->get('defaultFont'));
        $this->assertEquals(300, $option->get('dpi'));
        $this->assertEquals(1.2, $option->get('fontHeightRatio'));
        $this->assertTrue($option->get('isPhpEnabled'));
        $this->assertTrue($option->get('isRemoteEnabled'));
        $this->assertEquals(['w3.org'], $option->get('allowedRemoteHosts'));
        $this->assertFalse($option->get('isJavascriptEnabled'));
        $this->assertTrue($option->get('isHtml5ParserEnabled'));
        $this->assertFalse($option->get('isFontSubsettingEnabled'));
        $this->assertTrue($option->get('debugPng'));
        $this->assertTrue($option->get('debugKeepTemp'));
        $this->assertTrue($option->get('debugCss'));
        $this->assertTrue($option->get('debugLayout'));
        $this->assertFalse($option->get('debugLayoutLines'));
        $this->assertFalse($option->get('debugLayoutBlocks'));
        $this->assertFalse($option->get('debugLayoutInline'));
        $this->assertFalse($option->get('debugLayoutPaddingBox'));
        $this->assertEquals('CPDF', $option->get('pdfBackend'));
        $this->assertEquals('test9', $option->get('pdflibLicense'));
        $this->assertIsResource($option->get('httpContext'));
    }

    public function testGettersWithUnderscores()
    {
        $option = new Options([
            'temp_dir' => 'test1',
            'font_dir' => 'test2',
            'font_cache' => 'test3',
            'chroot' => 'test4,test4a',
            'allowed_protocols' => ['http://' => []],
            'log_output_file' => 'test5',
            'default_media_type' => 'test6',
            'default_paper_size' => 'test7',
            'default_paper_orientation' => 'landscape',
            'default_font' => 'test8',
            'dpi' => 300,
            'font_height_ratio' => 1.2,
            'is_php_enabled' => true,
            'is_remote_enabled' => true,
            'allowed_remote_hosts' => ['w3.org'],
            'is_javascript_enabled' => false,
            'is_html5_parser_enabled' => true,
            'is_font_subsetting_enabled' => false,
            'debug_png' => true,
            'debug_keep_temp' => true,
            'debug_css' => true,
            'debug_layout' => true,
            'debug_layout_lines' => false,
            'debug_layout_blocks' => false,
            'debug_layout_inline' => false,
            'debug_layout_padding_box' => false,
            'pdf_backend' => 'CPDF',
            'pdflib_license' => 'test9',
            'http_context' => ['ssl' => ['verify_peer' => false]],
        ]);

        $this->assertEquals('test1', $option->get('temp_dir'));
        $this->assertEquals('test2', $option->get('font_dir'));
        $this->assertEquals('test3', $option->get('font_cache'));
        $this->assertEquals(['test4', 'test4a'], $option->get('chroot'));
        $this->assertSame(['http://'], \array_keys($option->getAllowedProtocols()));
        $this->assertEquals('test5', $option->get('log_output_file'));
        $this->assertEquals('test6', $option->get('default_media_type'));
        $this->assertEquals('test7', $option->get('default_paper_size'));
        $this->assertEquals('landscape', $option->get('default_paper_orientation'));
        $this->assertEquals('test8', $option->get('default_font'));
        $this->assertEquals(300, $option->get('dpi'));
        $this->assertEquals(1.2, $option->get('font_height_ratio'));
        $this->assertTrue($option->get('is_php_enabled'));
        $this->assertTrue($option->get('is_remote_enabled'));
        $this->assertEquals(['w3.org'], $option->get('allowed_remote_hosts'));
        $this->assertFalse($option->get('is_javascript_enabled'));
        $this->assertTrue($option->get('is_html5_parser_enabled'));
        $this->assertFalse($option->get('is_font_subsetting_enabled'));
        $this->assertTrue($option->get('debug_png'));
        $this->assertTrue($option->get('debug_keep_temp'));
        $this->assertTrue($option->get('debug_css'));
        $this->assertTrue($option->get('debug_layout'));
        $this->assertFalse($option->get('debug_layout_lines'));
        $this->assertFalse($option->get('debug_layout_blocks'));
        $this->assertFalse($option->get('debug_layout_inline'));
        $this->assertFalse($option->get('debug_layout_padding_box'));
        $this->assertEquals('CPDF', $option->get('pdf_backend'));
        $this->assertEquals('test9', $option->get('pdflib_license'));
        $this->assertIsResource($option->get('http_context'));
    }

    public function testSetForEnableMethods()
    {
        $option = new Options();
        $option->set([
            'enable_php' => true,
            'enable_remote' => true,
            'enable_javascript' => false,
            'enable_html5_parser' => true,
            'enable_font_subsetting' => false
        ]);

        $this->assertTrue($option->getIsPhpEnabled());
        $this->assertTrue($option->getIsRemoteEnabled());
        $this->assertFalse($option->getIsJavascriptEnabled());
        $this->assertTrue($option->getIsHtml5ParserEnabled());
        $this->assertFalse($option->getIsFontSubsettingEnabled());
    }

    public function testGetForEnableMethods()
    {
        $option = new Options();
        $option->setIsPhpEnabled(true);
        $option->setIsRemoteEnabled(true);
        $option->setIsJavascriptEnabled(false);
        $option->setIsHtml5ParserEnabled(true);
        $option->setIsFontSubsettingEnabled(false);

        $this->assertTrue($option->get('enable_php'));
        $this->assertTrue($option->get('enable_remote'));
        $this->assertFalse($option->get('enable_javascript'));
        $this->assertTrue($option->get('enable_html5_parser'));
        $this->assertFalse($option->get('enable_font_subsetting'));
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

    public function testAllowedRemoteHosts()
    {
        $options = new Options(['isRemoteEnabled' => true]);
        $options->setAllowedRemoteHosts(['en.wikipedia.org']);
        $options->setAllowedProtocols(["http://"]);
        $allowedRemoteHosts = $options->getAllowedRemoteHosts();
        $this->assertIsArray($allowedRemoteHosts);
        $this->assertEquals(1, count($allowedRemoteHosts));
        $this->assertContains("en.wikipedia.org", $allowedRemoteHosts);

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
        [$validation_result] = $allowedProtocols["http://"]["rules"][0]("http://en.wikipedia.org/");
        $this->assertTrue($validation_result);
    }

    public function testArtifactPathValidation()
    {
        $options = new Options();

        $log_path = $options->getLogOutputFile();
        $options->setLogOutputFile("phar://test.phar/log.html");
        $this->assertEquals($log_path, $options->getLogOutputFile());

        $log_path = sys_get_temp_dir() . "/log.html";
        $options->setLogOutputFile($log_path);
        $this->assertEquals($log_path, $options->getLogOutputFile());

        $log_path = null;
        $options->setLogOutputFile($log_path);
        $this->assertEquals($log_path, $options->getLogOutputFile());
    }
}
