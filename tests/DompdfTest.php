<?php
namespace Dompdf\Tests;

use DOMDocument;
use Dompdf\Adapter\CPDF;
use Dompdf\Css\Stylesheet;
use Dompdf\Dompdf;
use Dompdf\Frame\FrameTree;
use Dompdf\Options;
use Dompdf\Tests\TestCase;

class DompdfTest extends TestCase
{
    public function testConstructor()
    {
        $dompdf = new Dompdf();
        $this->assertInstanceOf(CPDF::class, $dompdf->getCanvas());
        $this->assertSame("", $dompdf->getProtocol());
        $this->assertSame("", $dompdf->getBaseHost());
        $this->assertSame("", $dompdf->getBasePath());
        $this->assertIsArray($dompdf->getCallbacks());
        $this->assertInstanceOf(Stylesheet::class, $dompdf->getCss());
        $this->assertNull($dompdf->getDom());
        $this->assertNull($dompdf->getHttpContext());
        $this->assertInstanceOf(Options::class, $dompdf->getOptions());
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
        $this->assertInstanceOf(Stylesheet::class, $dompdf->getCss());
        $this->assertInstanceOf(DOMDocument::class, $dompdf->getDom());
        $this->assertIsResource($dompdf->getHttpContext());
        $this->assertInstanceOf(Options::class, $dompdf->getOptions());
        $this->assertEquals('test3', $dompdf->getProtocol());
        $this->assertInstanceOf(FrameTree::class, $dompdf->getTree());
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

    public function callbacksProvider(): array
    {
        return [
            ["begin_page_reflow", 1],
            ["begin_frame", 3],
            ["end_frame", 3],
            ["begin_page_render", 1],
            ["end_page_render", 1]
        ];
    }

    /**
     * @dataProvider callbacksProvider
     */
    public function testCallbacks(string $event, int $numCalls): void
    {
        $called = 0;

        $dompdf = new Dompdf();
        $dompdf->setCallbacks([
            [
                "event" => $event,
                "f" => function ($infos) use (&$called) {
                    $this->assertIsArray($infos);
                    $this->assertCount(4, $infos);
                    $called++;
                }
            ]
        ]);

        $dompdf->loadHtml("<html><body><p>Some text</p></body></html>");
        $dompdf->render();

        $this->assertSame($numCalls, $called);
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
