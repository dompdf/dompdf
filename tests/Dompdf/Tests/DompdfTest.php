<?php
namespace Dompdf\Tests;

use Dompdf\Frame\FrameTree;
use Dompdf\Options;
use PHPUnit\Framework\TestCase;
use Dompdf\Dompdf;
use Dompdf\Css\Stylesheet;
use DOMDocument;

class DompdfTest extends TestCase
{
    public function testConstructor()
    {
        $dompdf = new Dompdf();
        $this->assertInstanceOf('Dompdf\Adapter\Cpdf', $dompdf->getCanvas());
        $this->assertEquals('', $dompdf->getBaseHost());
        $this->assertEquals('', $dompdf->getBasePath());
        $this->assertInternalType('array', $dompdf->getCallbacks());
        $this->assertInstanceOf('Dompdf\Css\Stylesheet', $dompdf->getCss());
        $this->assertNull($dompdf->getDom());
        $this->assertNull($dompdf->getHttpContext());
        $this->assertInstanceOf('Dompdf\Options', $dompdf->getOptions());
        $this->assertNull($dompdf->getProtocol());
        $this->assertFalse($dompdf->getQuirksmode());
        $this->assertNull($dompdf->getTree());
    }

    public function testSetters()
    {
        $dompdf = new Dompdf();
        $dompdf->setBaseHost('test1');
        $dompdf->setBasePath('test2');
        $dompdf->setCallbacks(['test' => ['event' => 'test', 'f' => function() {}]]);
        $dompdf->setCss(new Stylesheet($dompdf));
        $dompdf->setDom(new DOMDocument());
        $dompdf->setHttpContext(fopen(__DIR__ . "/_files/jamaica.jpg", 'r'));
        $dompdf->setOptions(new Options());
        $dompdf->setProtocol('test3');
        $dompdf->setTree(new FrameTree($dompdf->getDom()));

        $this->assertEquals('test1', $dompdf->getBaseHost());
        $this->assertEquals('test2', $dompdf->getBasePath());
        $this->assertCount(1, $dompdf->getCallbacks());
        $this->assertInstanceOf('Dompdf\Css\Stylesheet', $dompdf->getCss());
        $this->assertInstanceOf('DOMDocument', $dompdf->getDom());
        $this->assertInternalType('resource', $dompdf->getHttpContext());
        $this->assertInstanceOf('Dompdf\Options', $dompdf->getOptions());
        $this->assertEquals('test3', $dompdf->getProtocol());
        $this->assertInstanceOf('Dompdf\Frame\FrameTree', $dompdf->getTree());
    }

    public function testLoadHtml()
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml('<html><body><strong>Hello</strong></body></html>');
        $this->assertEquals('Hello', $dompdf->getDom()->textContent);

        //Test when encoding parameter is used
        $dompdf->loadHtml(mb_convert_encoding('<html><body><strong>Hello</strong></body></html>', 'windows-1252'), 'windows-1252');
        $this->assertEquals('Hello', $dompdf->getDom()->textContent);
    }

    public function testRender()
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml('<html><body><strong>Hello</strong></body></html>');
        $dompdf->render();

        $this->assertEquals('', $dompdf->getDom()->textContent);
    }

    public function testSpaceAtStartOfSecondInlineTag()
    {
        $text_frame_contents = [];

        $dompdf = new Dompdf();

        // Use a callback to inspect the frame tree; otherwise FrameReflower\Page::reflow()
        // will dispose of it before dompdf->render finishes
        $dompdf->setCallbacks(['test' => [
            'event' => 'end_page_render',
            'f' => function($params) use (&$text_frame_contents) {
                $frame = $params["frame"];
                foreach ($frame->get_children() as $child) {
                    foreach ($child->get_children() as $grandchild) {
                        $text_frame_contents[] = $grandchild->get_text();
                    }
                }
            }
        ]]);

        $dompdf->loadHtml('<html><body><span>one</span><span> - two</span></body></html>');
        $dompdf->render();

        $this->assertEquals("one", $text_frame_contents[0]);
        $this->assertEquals(" - two", $text_frame_contents[1]);
    }
}
