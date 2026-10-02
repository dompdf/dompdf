<?php
namespace Dompdf\Tests\FrameReflower;

use DOMElement;
use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\Tests\TestCase;

class TextOverflowWrapTest extends TestCase
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

    public static function graphemeProvider(): array
    {
        return [
            "thai tone mark and sara am" => ["น้ำกี่", ["น้ำ", "กี่"]],
            "thai mai taikhu" => ["ก็บ", ["ก็", "บ"]],
            "combining acute" => ["e\u{301}e\u{301}", ["e\u{301}", "e\u{301}"]],
            "regional indicator flag" => ["\u{1F1F9}\u{1F1ED}a", ["\u{1F1F9}\u{1F1ED}", "a"]],
            "latin" => ["abc", ["a", "b", "c"]],
        ];
    }

    /**
     * In a container too narrow for any character, each line holds exactly
     * one forced unit, which must be a whole grapheme cluster.
     *
     * @dataProvider graphemeProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('graphemeProvider')]
    public function testOverflowWrapAnywhereKeepsGraphemesTogether(string $text, array $expected): void
    {
        [$lines] = $this->layout("<div style=\"width: 1pt; overflow-wrap: anywhere\">$text</div>");

        $this->assertSame($expected, $lines);
    }

    /**
     * @dataProvider graphemeProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('graphemeProvider')]
    public function testOverflowWrapBreakWordKeepsGraphemesTogether(string $text, array $expected): void
    {
        [$lines] = $this->layout("<div style=\"width: 1pt; overflow-wrap: break-word\">$text</div>");

        $this->assertSame($expected, $lines);
    }

    public function testNoLineStartsWithThaiCombiningMark(): void
    {
        [$lines] = $this->layout(
            "<div style=\"width: 30pt; overflow-wrap: anywhere\">น้ำแข็งกี่ที่ปรึกษาผู้ปกครองน้ำใจ</div>"
        );

        $this->assertGreaterThan(1, count($lines));
        foreach ($lines as $line) {
            $this->assertSame(0, preg_match('/^[\x{0E31}\x{0E33}-\x{0E3A}\x{0E47}-\x{0E4E}]/u', $line), $line);
        }
    }

    public function testMinContentWidthWithAnywhereUsesFirstGrapheme(): void
    {
        [, $widths] = $this->layout(
            "<div style=\"width: 1pt\">"
            . "<table class=\"a\" style=\"float: left\"><tr><td style=\"overflow-wrap: anywhere\">น้ำกี่</td></tr></table>"
            . "<table class=\"b\" style=\"float: left\"><tr><td style=\"white-space: nowrap\">น้ำ</td></tr></table>"
            . "</div>"
        );

        $this->assertEqualsWithDelta($widths["b"], $widths["a"], 0.001);
    }
}
