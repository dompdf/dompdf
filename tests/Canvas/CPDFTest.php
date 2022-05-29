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
}
