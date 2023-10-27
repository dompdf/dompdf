<?php
namespace Dompdf\Tests;

use DOMDocument;
use Dompdf\Adapter\CPDF;
use Dompdf\Canvas;
use Dompdf\Css\Stylesheet;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Frame;
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
        $this->assertInstanceOf(Options::class, $dompdf->getOptions());
        $this->assertFalse($dompdf->getQuirksmode());
        $this->assertNull($dompdf->getTree());
    }

    public function testSetters()
    {
        $dompdf = new Dompdf();
        $dompdf->setBaseHost('test1');
        $dompdf->setBasePath('test2');
        $dompdf->setCallbacks(['test' => ['event' => 'test', 'f' => function () {}]]);
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

        $dompdf = new Dompdf();
        $dompdf->setHttpContext(['ssl' => ['verify_peer' => false]]);
        $this->assertIsResource($dompdf->getHttpContext());
    }

    public static function loadHtmlProvider(): array
    {
        $textContent = "Some – Unicode";
        $document = function (string $encoding, string $head = "") use ($textContent) {
            $html = "<html><head>$head</head><body><strong>$textContent</strong></body></html>";
            return $encoding !== "UTF-8"
                ? mb_convert_encoding($html, $encoding, "UTF-8")
                : $html;
        };
        $metaCharset = function (string $charset) {
            return "<meta charset='$charset'>";
        };
        $metaContent1 = function (string $charset) {
            return "<meta http-equiv='Content-Type' content='text/html; charset=$charset'>";
        };
        $metaContent2 = function (string $charset) {
            return "<meta content='text/html; charset=$charset' http-equiv='Content-Type'>";
        };

        return [
            // Without encoding parameter
            "utf-8 no encoding" => [
                $document("UTF-8"),
                null,
                $textContent
            ],
            "utf-8 meta no encoding" => [
                $document("UTF-8", $metaCharset("UTF-8")),
                null,
                $textContent
            ],
            "windows-1252 meta no encoding 1" => [
                $document("Windows-1252", $metaCharset("Windows-1252")),
                null,
                $textContent
            ],
            "windows-1252 meta no encoding 2" => [
                $document("Windows-1252", $metaContent1("Windows-1252")),
                null,
                $textContent
            ],
            "windows-1252 meta no encoding 3" => [
                $document("Windows-1252", $metaContent2("Windows-1252")),
                null,
                $textContent
            ],

            // With encoding parameter
            "utf-8 with encoding" => [
                $document("UTF-8"),
                "UTF-8",
                $textContent
            ],
            "windows-1252 with encoding" => [
                $document("Windows-1252"),
                "Windows-1252",
                $textContent
            ],
            // Verify that passed encoding takes precedence
            "windows-1252 meta mismatch with encoding" => [
                $document("Windows-1252", $metaCharset("UTF-8")),
                "Windows-1252",
                $textContent
            ],
            "utf-16 meta with encoding" => [
                $document("UTF-16", $metaCharset("UTF-16")),
                "UTF-16",
                $textContent
            ],

            // With BOM
            "utf-8 bom" => [
                "\xEF\xBB\xBF" . $document("UTF-8"),
                null,
                $textContent
            ],
            "utf-16be bom" => [
                "\xFE\xFF" . $document("UTF-16BE", $metaCharset("UTF-16")),
                null,
                $textContent
            ],
            "utf-16le bom" => [
                "\xFF\xFE" . $document("UTF-16LE", $metaCharset("UTF-16")),
                null,
                $textContent
            ],
            // Verify that BOM takes precedence
            "utf-8 bom with encoding mismatch" => [
                "\xEF\xBB\xBF" . $document("UTF-8"),
                "Windows-1252",
                $textContent
            ],
            "utf-16le bom with encoding mismatch" => [
                "\xFF\xFE" . $document("UTF-16LE", $metaCharset("UTF-16")),
                "UTF-8",
                $textContent
            ],
        ];
    }

    /**
     * @dataProvider loadHtmlProvider
     */
    public function testLoadHtml(
        string $html,
        ?string $encoding,
        string $expectedText
    ): void {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html, $encoding);

        $this->assertSame($expectedText, $dompdf->getDom()->textContent);
    }

    public function testRender()
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml('<html><body><strong>Hello</strong></body></html>');
        $dompdf->render();

        $this->assertEquals('', $dompdf->getDom()->textContent);
    }

    public static function callbacksProvider(): array
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
                "f" => function ($frame, $canvas, $fontMetrics) use (&$called) {
                    $this->assertInstanceOf(Frame::class, $frame);
                    $this->assertInstanceOf(Canvas::class, $canvas);
                    $this->assertInstanceOf(FontMetrics::class, $fontMetrics);
                    $called++;
                }
            ]
        ]);

        $dompdf->loadHtml("<html><body><p>Some text</p></body></html>");
        $dompdf->render();

        $this->assertSame($numCalls, $called);
    }

    public function testEndDocumentCallback(): void
    {
        $called = 0;

        $dompdf = new Dompdf();
        $dompdf->setCallbacks([
            [
                "event" => "end_document",
                "f" => function ($pageNumber, $pageCount, $canvas, $fontMetrics) use (&$called) {
                    $called++;
                    $this->assertSame($called, $pageNumber);
                    $this->assertSame(2, $pageCount);
                    $this->assertInstanceOf(Canvas::class, $canvas);
                    $this->assertInstanceOf(FontMetrics::class, $fontMetrics);
                }
            ]
        ]);

        $dompdf->loadHtml("<html><body><p>Page 1</p><p style='page-break-before: always;'>Page 2</p></body></html>");
        $dompdf->render();

        $this->assertSame(2, $called);
    }

    public static function customCanvasProvider(): array
    {
        return [
            ["A4", "portrait", true, "auto"],
            ["A5", "landscape", true, "A5 landscape"],
            ["A5", "landscape", false, "A5 landscape"],
            [[0, 0, 300, 400], "portrait", true, "300pt 400pt"]
        ];
    }

    /**
     * Test that a custom canvas is not replaced on render if its size matches
     * the desired paper size.
     *
     * @dataProvider customCanvasProvider
     */
    public function testCustomCanvas(
        $size,
        string $orientation,
        bool $setPaper,
        string $cssSize
    ): void {
        $options = new Options();
        $options->setDefaultPaperSize("Letter");

        $dompdf = new Dompdf($options);

        if ($setPaper) {
            $dompdf->setPaper($size, $orientation);
        }

        $c1 = new CPDF($size, $orientation, $dompdf);
        $dompdf->setCanvas($c1);
        $dompdf->loadHtml("<html><head><style>@page { size: $cssSize; }</style></head><body></body></html>");
        $dompdf->render();
        $c2 = $dompdf->getCanvas();

        $this->assertSame($c1, $c2);
    }

    public function testSpaceAtStartOfSecondInlineTag()
    {
        $text_frame_contents = [];

        $dompdf = new Dompdf();

        // Use a callback to inspect the frame tree; otherwise FrameReflower\Page::reflow()
        // will dispose of it before dompdf->render finishes
        $dompdf->setCallbacks(['test' => [
            'event' => 'end_page_render',
            'f' => function (Frame $frame) use (&$text_frame_contents) {
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
