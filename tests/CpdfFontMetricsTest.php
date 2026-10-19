<?php
namespace Dompdf\Tests;

use Dompdf\Cpdf;

class CpdfFontMetricsTest extends TestCase
{
    public function testDefaultHelveticaXHeightUsesItsAfmMetric(): void
    {
        $pdf = new Cpdf([0, 0, 100, 100], false, sys_get_temp_dir(), sys_get_temp_dir());
        $this->assertEqualsWithDelta(5.23, $pdf->getFontXHeight(10), 1e-8);
        $this->assertEqualsWithDelta(10.46, $pdf->getFontXHeight(20), 1e-8);
    }

    public function testSymbolWithoutXHeightUsesHalfTheHeightInPoints(): void
    {
        $pdf = new Cpdf([0, 0, 100, 100], false, sys_get_temp_dir(), sys_get_temp_dir());
        $pdf->selectFont(__DIR__ . '/../lib/fonts/Symbol.afm');
        // The real bundled Symbol AFM has no XHeight and a 1303-unit bounding height.
        $this->assertEqualsWithDelta(13.03, $pdf->getFontHeight(10), 1e-8);
        $this->assertEqualsWithDelta(6.515, $pdf->getFontXHeight(10), 1e-8);
        $this->assertEqualsWithDelta(13.03, $pdf->getFontXHeight(20), 1e-8);
    }
}
