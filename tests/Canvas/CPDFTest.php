<?php
namespace Dompdf\Tests\Canvas;

use Dompdf\Adapter\CPDF;
use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Tests\TestCase;

class CPDFTest extends TestCase
{
    public function testPageScript(): void
    {
        global $called;
        $called = 0;

        $dompdf = new Dompdf();
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);
        $canvas->new_page();

        $canvas->page_script(function (
            int $pageNumber,
            int $pageCount,
            Canvas $canvas,
            FontMetrics $fontMetrics
        ) use (&$called) {
            $called++;
            $font = $fontMetrics->getFont("Helvetica");
            $canvas->text(40, 20, "Page $pageNumber of $pageCount", $font, 12);
            $canvas->line(200, 0, 0, 200, [0, 0, 0], 1);
        });
        $canvas->page_script('
            global $called;
            $called++;
            $font = $fontMetrics->getFont("Helvetica");
            $pdf->text(20, 0, "Page $PAGE_NUM of $PAGE_COUNT", $font, 12);
        ');

        $output = $canvas->output();

        $this->assertNotSame("", $output);
        $this->assertSame(4, $called);
    }

    public function testPageText(): void
    {
        $dompdf = new Dompdf();
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);
        $canvas->new_page();

        $font = $dompdf->getFontMetrics()->getFont("Helvetica");
        $canvas->page_text(60, 40, "Page {PAGE_NUM} of {PAGE_COUNT}", $font, 12);

        $output = $canvas->output();
        $this->assertNotSame("", $output);
    }

    public function testPageLine(): void
    {
        $dompdf = new Dompdf();
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);
        $canvas->new_page();

        $canvas->page_line(0, 0, 200, 200, [0, 0, 0], 1);

        $output = $canvas->output();
        $this->assertNotSame("", $output);
    }

    public static function fontSupportsCharProvider(): array
    {
        return [
            // Core fonts
            // ASCII and ISO-8859-1
            ["Helvetica", "A", true],
            ["Helvetica", "{", true],
            ["Helvetica", "Æ", true],
            ["Helvetica", "÷", true],

            // Part of Windows-1252, but not ISO-8859-1
            ["Helvetica", "€", true],
            ["Helvetica", "‚", true],
            ["Helvetica", "ƒ", true],
            ["Helvetica", "„", true],
            ["Helvetica", "…", true],
            ["Helvetica", "†", true],
            ["Helvetica", "‡", true],
            ["Helvetica", "ˆ", true],
            ["Helvetica", "‰", true],
            ["Helvetica", "Š", true],
            ["Helvetica", "‹", true],
            ["Helvetica", "Œ", true],
            ["Helvetica", "Ž", true],
            ["Helvetica", "‘", true],
            ["Helvetica", "’", true],
            ["Helvetica", "“", true],
            ["Helvetica", "”", true],
            ["Helvetica", "•", true],
            ["Helvetica", "–", true],
            ["Helvetica", "—", true],
            ["Helvetica", "˜", true],
            ["Helvetica", "™", true],
            ["Helvetica", "š", true],
            ["Helvetica", "›", true],
            ["Helvetica", "œ", true],
            ["Helvetica", "ž", true],
            ["Helvetica", "Ÿ", true],
            ["Helvetica", "ÿ", true],

            // Unicode outside Windows-1252
            ["Helvetica", "Ā", false],
            ["Helvetica", "↦", false],
            ["Helvetica", "∉", false],
            ["Helvetica", "能", false],

            // DejaVu
            ["DejaVu Sans", "A", true],
            ["DejaVu Sans", "{", true],
            ["DejaVu Sans", "Æ", true],
            ["DejaVu Sans", "÷", true],
            ["DejaVu Sans", "Œ", true],
            ["DejaVu Sans", "—", true],
            ["DejaVu Sans", "↦", true],
            ["DejaVu Sans", "∉", true],
            ["DejaVu Sans", "能", false],
        ];
    }

    /**
     * @dataProvider fontSupportsCharProvider
     */
    public function testFontSupportsChar(string $font, string $char, bool $expected): void
    {
        $dompdf = new Dompdf();
        $canvas = new CPDF("letter", "portrait", $dompdf);
        $fontFile = $dompdf->getFontMetrics()->getFont($font);

        $this->assertSame($expected, $canvas->font_supports_char($fontFile, $char));
    }
}
