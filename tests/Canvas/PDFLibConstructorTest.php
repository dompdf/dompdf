<?php
namespace Dompdf\Tests\Canvas;

use Dompdf\Adapter\PDFLib;
use Dompdf\Dompdf;
use Dompdf\Tests\TestCase;

class PDFLibConstructorTest extends TestCase
{
    public function testOmittedContextCreatesAUsableNativeDocument(): void
    {
        if (!extension_loaded('PDFlib')) {
            $this->markTestSkipped('The native PDFlib extension is required.');
        }
        $canvas = new PDFLib();
        $this->assertInstanceOf(Dompdf::class, $canvas->get_dompdf());
        $this->assertInstanceOf(\PDFlib::class, $canvas->get_pdflib());
        $this->assertEqualsWithDelta(612, $canvas->get_width(), 1e-7);
        $this->assertEqualsWithDelta(792, $canvas->get_height(), 1e-7);
        $canvas->rectangle(10, 10, 20, 20, [0, 0, 0], 1);
        $this->assertStringStartsWith('%PDF-', $canvas->output());
    }

    public function testExplicitNullAndSuppliedContext(): void
    {
        if (!extension_loaded('PDFlib')) {
            $this->markTestSkipped('The native PDFlib extension is required.');
        }
        $canvas = new PDFLib('a4', 'landscape', null);
        $this->assertInstanceOf(Dompdf::class, $canvas->get_dompdf());
        $this->assertGreaterThan($canvas->get_height(), $canvas->get_width());
        $this->assertStringStartsWith('%PDF-', $canvas->output());
        $dompdf = new Dompdf();
        $supplied = new PDFLib('letter', 'portrait', $dompdf);
        $this->assertSame($dompdf, $supplied->get_dompdf());
        $this->assertStringStartsWith('%PDF-', $supplied->output());
    }
}
