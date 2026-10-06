<?php
namespace Dompdf\Tests\FrameReflower;

use DOMElement;
use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\Tests\TestCase;

class ZeroWidthSpaceTest extends TestCase
{
    /**
     * Render the HTML and collect the used width of every element with a
     * class attribute.
     */
    private function layout(string $body): array
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>'
            . '@page { size: 400pt 400pt; margin: 0; }'
            . 'body { margin: 0; font-family: DejaVu Sans; font-size: 10pt; }'
            . '</style></head><body>' . $body . '</body></html>';

        $widths = [];

        $dompdf = new Dompdf();
        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame, Canvas $canvas) use (&$widths) {
                    $node = $frame->get_node();

                    if ($node instanceof DOMElement && $node->getAttribute("class") !== "") {
                        $widths[$node->getAttribute("class")] = $frame->get_margin_width();
                    }
                }
            ]
        ]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return $widths;
    }

    /**
     * @dataProvider zeroWidthSpaceFontProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('zeroWidthSpaceFontProvider')]
    public function testZeroWidthSpaceHasNoWidthInAnyFont(string $font): void
    {
        $widths = $this->layout(
            "<div style=\"font-family: $font\">"
            . "<div class=\"plain\" style=\"float: left\">abab</div>"
            . "<div class=\"zwsp\" style=\"float: left\">ab\u{200B}ab</div>"
            . "<div class=\"spaced\" style=\"float: left; letter-spacing: 3pt\">ab\u{200B}ab</div>"
            . "</div>"
        );

        $this->assertEqualsWithDelta($widths["plain"], $widths["zwsp"], 0.001);
        // Letter spacing applies to the four visible characters only
        $this->assertEqualsWithDelta($widths["plain"] + 4 * 3, $widths["spaced"], 0.001);
    }

    public static function zeroWidthSpaceFontProvider(): array
    {
        // Helvetica is a core font without a glyph for U+200B
        return [
            "font with glyph" => ["DejaVu Sans"],
            "font without glyph" => ["Helvetica"],
        ];
    }

    public function testZeroWidthSpaceIsNotPaintedAsText(): void
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml("<div style=\"font-family: Helvetica\">ab\u{200B}ab</div>");
        $dompdf->render();

        $output = $dompdf->output(["compress" => 0]);

        preg_match_all('/\[\((.*?)\)\] TJ/', $output, $matches);
        $this->assertSame("abab", implode("", $matches[1]));
    }
}
