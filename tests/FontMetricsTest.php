<?php
namespace Dompdf\Tests;

use Dompdf\Dompdf;

class FontMetricsTest extends TestCase
{
    public function testTerminalRunsPreserveLengthsAndSubstrings(): void
    {
        $dompdf = new Dompdf([
            'fontCache' => sys_get_temp_dir(),
            'tempDir' => sys_get_temp_dir(),
            'isRemoteEnabled' => false,
        ]);
        $metrics = $dompdf->getFontMetrics();
        $font = $metrics->getFont('Helvetica');
        $this->assertNotNull($font);
        $this->assertSame(
            [0 => ['font' => $font, 'length' => 3, 'text' => 'ABC']],
            $metrics->mapTextToFonts('ABC', ['Helvetica'], 'normal', -1, true)
        );
        $this->assertSame([], $metrics->mapTextToFonts('', ['Helvetica'], 'normal', -1, true));
        $this->assertSame([], $metrics->mapTextToFonts("\n\t", ['Helvetica'], 'normal', -1, true));
        $this->assertSame(
            [0 => ['font' => $font, 'length' => 3, 'text' => "A\n\t"]],
            $metrics->mapTextToFonts("A\n\t", ['Helvetica'], 'normal', -1, true)
        );
        // Helvetica has no glyph for this character; use the real CPDF font map.
        $this->assertSame(
            [0 => ['font' => $font, 'length' => 1, 'text' => 'A']],
            $metrics->mapTextToFonts("A\u{1F600}", ['Helvetica'], 'normal', 1, true)
        );
        $this->assertSame(
            [
                0 => ['font' => $font, 'length' => 1, 'text' => 'A'],
                1 => ['font' => null, 'length' => 1, 'text' => "\u{1F600}"]
            ],
            $metrics->mapTextToFonts("A\u{1F600}", ['Helvetica'], 'normal', -1, true)
        );
    }
}
