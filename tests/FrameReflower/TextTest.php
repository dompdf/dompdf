<?php
namespace Dompdf\Tests\FrameReflower;

use DOMElement;
use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\Tests\TestCase;

class TextTest extends TestCase
{
    /**
     * Render the HTML and collect the text of every text frame and the used
     * width of every element with a class attribute.
     */
    private function layout(string $body): array
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'
            . '@page { size: 400pt 400pt; margin: 0; }'
            . 'body { margin: 0; font-family: DejaVu Sans; font-size: 10pt; }'
            . '</style></head><body>' . $body . '</body></html>';

        $lines = [];
        $widths = [];

        $dompdf = new Dompdf();
        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame, Canvas $canvas) use (&$lines, &$widths) {
                    $node = $frame->get_node();

                    if ($node instanceof DOMElement && $node->getAttribute("class") !== "") {
                        $widths[$node->getAttribute("class")] = $frame->get_margin_width();
                    } elseif ($node->nodeName === "#text" && $frame->get_text() !== "") {
                        $lines[] = $frame->get_text();
                    }
                }
            ]
        ]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return [$lines, $widths];
    }

    private function stripZwsp(array $lines): array
    {
        return array_map(function ($line) {
            return trim(str_replace("\u{200B}", "", $line));
        }, $lines);
    }

    public function testZeroWidthSpaceIsBreakOpportunity(): void
    {
        [$lines] = $this->layout("<div style=\"width: 60pt\">aaaaaaaa\u{200B}bbbbbbbb</div>");

        $this->assertSame(["aaaaaaaa", "bbbbbbbb"], $this->stripZwsp($lines));
    }

    public function testZeroWidthSpaceDoesNotBreakWhenTextFits(): void
    {
        [$lines] = $this->layout("<div style=\"width: 200pt\">aaaa\u{200B}bbbb</div>");

        $this->assertCount(1, $lines);
        $this->assertSame("aaaabbbb", $this->stripZwsp($lines)[0]);
    }

    public function testZeroWidthSpaceHasNoWidth(): void
    {
        [, $widths] = $this->layout(
            "<div class=\"a\" style=\"float: left\">aaaa\u{200B}bbbb</div>"
            . "<div class=\"b\" style=\"float: left\">aaaabbbb</div>"
        );

        $this->assertEqualsWithDelta($widths["b"], $widths["a"], 0.001);
    }

    /**
     * @dataProvider breakMarkerProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('breakMarkerProvider')]
    public function testBreakOpportunityLowersMinContentWidth(string $marker): void
    {
        // A shrink-to-fit table is limited by the available width unless its
        // min-content width is larger
        [, $widths] = $this->layout(
            "<div style=\"width: 60pt\"><table class=\"t\" style=\"float: left\"><tr>"
            . "<td>aaaaaaaa{$marker}bbbbbbbb</td></tr></table></div>"
        );

        $this->assertLessThanOrEqual(60.5, $widths["t"]);
    }

    public static function breakMarkerProvider(): array
    {
        return [
            "zwsp" => ["\u{200B}"],
            "wbr" => ["<wbr>"],
        ];
    }

    public function testWbrIsBreakOpportunity(): void
    {
        [$lines] = $this->layout("<div style=\"width: 60pt\">aaaaaaaa<wbr>bbbbbbbb</div>");

        $this->assertSame(["aaaaaaaa", "bbbbbbbb"], $this->stripZwsp($lines));
    }

    public function testWbrDoesNotBreakWhenTextFits(): void
    {
        [$lines] = $this->layout("<div style=\"width: 200pt\">aaaa<wbr>bbbb</div>");

        $this->assertSame(["aaaabbbb"], [implode("", $this->stripZwsp($lines))]);
    }

    public function testWbrInsideInlineElement(): void
    {
        [$lines] = $this->layout("<div style=\"width: 60pt\"><b>aaaaaaaa<wbr>bbbbbbbb</b></div>");

        $this->assertSame(["aaaaaaaa", "bbbbbbbb"], $this->stripZwsp($lines));
    }

    public function testLongWordWithoutOpportunityStillOverflows(): void
    {
        [$lines] = $this->layout("<div style=\"width: 60pt\">aaaaaaaabbbbbbbb</div>");

        $this->assertSame(["aaaaaaaabbbbbbbb"], $this->stripZwsp($lines));
    }
}
