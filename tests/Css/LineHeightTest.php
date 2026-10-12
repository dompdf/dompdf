<?php
namespace Dompdf\Tests\Css;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\FrameDecorator\Text;
use Dompdf\LineBox;
use Dompdf\Options;
use Dompdf\Tests\TestCase;

class LineHeightTest extends TestCase
{
    /**
     * Lays out the HTML and returns the line boxes that contain text, in
     * document order.
     *
     * @return LineBox[]
     */
    private function textLines(string $html, string $mode, ?Dompdf &$dompdf = null, string $backend = "CPDF"): array
    {
        // The GD adapter measures fonts with FreeType, which GD may be built without
        if ($backend === "GD" && !function_exists("imagettfbbox")) {
            $this->markTestSkipped("The GD extension with FreeType support is not available.");
        }

        $options = new Options();
        $options->setLineHeightMode($mode);
        $options->setPdfBackend($backend);
        $dompdf = new Dompdf($options);

        $lines = [];
        $walk = function (Frame $frame) use (&$walk, &$lines) {
            if (method_exists($frame, "get_line_boxes")) {
                foreach ($frame->get_line_boxes() as $line) {
                    foreach ($line->get_frames() as $child) {
                        if ($child instanceof Text) {
                            $lines[spl_object_id($line)] = $line;
                            break;
                        }
                    }
                }
            }

            foreach ($frame->get_children() as $child) {
                $walk($child);
            }
        };

        $dompdf->setCallbacks([[
            "event" => "begin_page_render",
            "f" => function (Frame $page) use ($walk) {
                $walk($page);
            }
        ]]);
        $dompdf->loadHtml($html);
        $dompdf->render();

        return array_values($lines);
    }

    private function page(string $body, string $font = "Helvetica", string $size = "10pt"): string
    {
        return "<html><body style=\"margin: 0; font-family: '$font'; font-size: $size\">$body</body></html>";
    }

    /**
     * @return array{float, float} Ascent and descent of the font
     */
    private function fontMetrics(Dompdf $dompdf, string $font, float $size): array
    {
        $fontMetrics = $dompdf->getFontMetrics();
        $file = $fontMetrics->getFont($font);

        return [$fontMetrics->getFontAscent($file, $size), $fontMetrics->getFontDescent($file, $size)];
    }

    public function testModeDefaultsToLegacy(): void
    {
        $this->assertSame("legacy", (new Options())->getLineHeightMode());
    }

    public function testInvalidModeIsRejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        (new Options())->setLineHeightMode("browser");
    }

    /**
     * Fonts available to every backend: the GD backend needs TrueType fonts.
     */
    public function lineHeightProvider(): array
    {
        $fonts = [
            "CPDF" => ["Helvetica", "Times", "DejaVu Sans", "DejaVu Serif"],
            "GD" => ["DejaVu Sans", "DejaVu Serif"]
        ];

        $data = [];
        foreach ($fonts as $backend => $names) {
            foreach ($names as $font) {
                foreach ([1, 1.5, 2] as $lineHeight) {
                    $data["$backend, $font, $lineHeight"] = [$backend, $font, $lineHeight];
                }
            }
        }

        return $data;
    }

    public function backendProvider(): array
    {
        return ["CPDF" => ["CPDF"], "GD" => ["GD"]];
    }

    /**
     * @dataProvider lineHeightProvider
     */
    public function testCssLineHeightDoesNotDependOnTheFont(string $backend, string $font, float $lineHeight): void
    {
        $html = $this->page("<div style=\"line-height: $lineHeight\">a</div>", $font);
        $lines = $this->textLines($html, "css", $dompdf, $backend);

        $this->assertCount(1, $lines);
        $this->assertEqualsWithDelta($lineHeight * 10, $lines[0]->h, 0.001);
    }

    /**
     * @dataProvider lineHeightProvider
     */
    public function testCssBaselineCentersTheFontInTheLine(string $backend, string $font, float $lineHeight): void
    {
        $html = $this->page("<div style=\"line-height: $lineHeight\">a</div>", $font);
        $lines = $this->textLines($html, "css", $dompdf, $backend);
        [$ascent, $descent] = $this->fontMetrics($dompdf, $font, 10);

        $halfLeading = ($lineHeight * 10 - $ascent - $descent) / 2;
        $this->assertEqualsWithDelta($halfLeading + $ascent, $lines[0]->above, 0.001);
        $this->assertEqualsWithDelta($halfLeading + $descent, $lines[0]->below, 0.001);
    }

    /**
     * @dataProvider backendProvider
     */
    public function testCssNormalLineHeightUsesTheHeightOfTheFont(string $backend): void
    {
        $html = $this->page("<div>a</div>", "DejaVu Sans");
        $lines = $this->textLines($html, "css", $dompdf, $backend);
        [$ascent, $descent] = $this->fontMetrics($dompdf, "DejaVu Sans", 10);

        $this->assertEqualsWithDelta($ascent + $descent, $lines[0]->h, 0.001);
    }

    /**
     * @dataProvider backendProvider
     */
    public function testCssLineBoxContainsAllFontSizes(string $backend): void
    {
        $html = $this->page("<div style=\"line-height: 1.5\">a <span style=\"font-size: 22pt\">B</span> c</div>", "DejaVu Sans");
        $lines = $this->textLines($html, "css", $dompdf, $backend);

        [$ascent, $descent] = $this->fontMetrics($dompdf, "DejaVu Sans", 22);
        $halfLeading = (22 * 1.5 - $ascent - $descent) / 2;

        $this->assertCount(1, $lines);
        $this->assertEqualsWithDelta(22 * 1.5, $lines[0]->h, 0.001);
        $this->assertEqualsWithDelta($halfLeading + $ascent, $lines[0]->above, 0.001);
    }

    /**
     * @dataProvider backendProvider
     */
    public function testCssLineHeightInheritsTheComputedLength(string $backend): void
    {
        // A percentage is resolved for the element it is declared on
        $html = $this->page("<div style=\"line-height: 150%\"><span style=\"font-size: 20pt\">a</span></div>", "DejaVu Sans");
        $lines = $this->textLines($html, "css", $dompdf, $backend);

        $this->assertEqualsWithDelta(15, $lines[0]->h, 0.001);
    }

    /**
     * @dataProvider backendProvider
     */
    public function testCssBoxAlignedToTheTopDoesNotMoveTheBaseline(string $backend): void
    {
        $box = "<span style=\"display: inline-block; width: 20pt; height: 50pt; vertical-align: top\"></span>";
        $html = $this->page("<div style=\"line-height: 1.5\">a$box</div>", "DejaVu Sans");
        $lines = $this->textLines($html, "css", $dompdf, $backend);
        [$ascent, $descent] = $this->fontMetrics($dompdf, "DejaVu Sans", 10);

        $this->assertEqualsWithDelta(50, $lines[0]->h, 0.001);
        $this->assertEqualsWithDelta((15 - $ascent - $descent) / 2 + $ascent, $lines[0]->above, 0.001);
    }

    public function testLegacyModeIsNotAffected(): void
    {
        $html = $this->page("<div style=\"line-height: 1.5\">a</div>", "DejaVu Sans");
        $lines = $this->textLines($html, "legacy", $dompdf);
        $fontMetrics = $dompdf->getFontMetrics();
        $fontHeight = $fontMetrics->getFontHeight($fontMetrics->getFont("DejaVu Sans"), 10);

        // line-height factor * font height (which includes the font height ratio)
        $this->assertEqualsWithDelta(1.5 * $fontHeight, $lines[0]->h, 0.001);
        $this->assertEquals(0.0, $lines[0]->above);
    }
}
