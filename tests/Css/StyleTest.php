<?php

namespace Dompdf\Tests\Css;

use Dompdf\Dompdf;
use Dompdf\Css\Style;
use Dompdf\Css\Stylesheet;
use Dompdf\Tests\TestCase;

class StyleTest extends TestCase
{

    public function testLengthInPt()
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $s = new Style($sheet);

        // PX
        $length = $s->length_in_pt('100px');
        $this->assertEquals(75, $length);

        // also check caps
        $length = $s->length_in_pt('100PX');
        $this->assertEquals(75, $length);

        // PT
        $length = $s->length_in_pt('100pt');
        $this->assertEquals(100, $length);

        // %
        $length = $s->length_in_pt('100%');
        $this->assertEquals(12, $length);
    }

    public function cssImageBasicProvider(): array
    {
        return [
            "no value" => ["", "none"],
            "keyword none" => ["none", "none"],
            "bare url" => ["http://example.com/test.png", "none"],
            "http" => ["url(http://example.com/test.png)", "http://example.com/test.png"]
        ];
    }

    public function cssImageNoBaseHrefProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        return [
            "local absolute" => ["url($basePath/_files/jamaica.jpg)", "$basePath".DIRECTORY_SEPARATOR."_files".DIRECTORY_SEPARATOR."jamaica.jpg"],
            "local relative" => ["url(../_files/jamaica.jpg)", "$basePath".DIRECTORY_SEPARATOR."_files".DIRECTORY_SEPARATOR."jamaica.jpg"]
        ];
    }

    public function cssImageWithBaseHrefProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        return [
            "local absolute" => ["url($basePath/_files/jamaica.jpg)", "$basePath".DIRECTORY_SEPARATOR."_files".DIRECTORY_SEPARATOR."jamaica.jpg"],
            "local relative" => ["url(../_files/jamaica.jpg)", "$basePath".DIRECTORY_SEPARATOR."_files".DIRECTORY_SEPARATOR."jamaica.jpg"]
        ];
    }

    public function cssImageWithStylesheetBaseHrefProvider(): array
    {
        return [
            "local absolute" => ["url(/_files/jamaica.jpg)", "https://example.com/_files/jamaica.jpg"],
            "local relative" => ["url(../_files/jamaica.jpg)", "https://example.com/../_files/jamaica.jpg"]
        ];
    }

    /**
     * @dataProvider cssImageBasicProvider
     * @dataProvider cssImageNoBaseHrefProvider
     * @group regression
     */
    public function testCssImageNoBaseHref($value, $expected)
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $sheet->set_base_path(__DIR__); // Treat the stylesheet as being located in this directory
        $s = new Style($sheet);

        $s->background_image = $value;
        $this->assertSame($expected, $s->background_image);
    }

    /**
     * @dataProvider cssImageBasicProvider
     * @dataProvider cssImageWithBaseHrefProvider
     * @group regression
     */
    public function testCssImageWithBaseHref($value, $expected)
    {
        $dompdf = new Dompdf();
        $dompdf->setProtocol("https://");
        $dompdf->setBaseHost("example.com");
        $dompdf->setBasePath("/");
        $sheet = new Stylesheet($dompdf);
        $sheet->set_base_path(__DIR__); // Treat the stylesheet as being located in this directory
        $s = new Style($sheet);

        $s->background_image = $value;
        $this->assertSame($expected, $s->background_image);
    }

    /**
     * @dataProvider cssImageBasicProvider
     * @dataProvider cssImageWithStylesheetBaseHrefProvider
     * @group regression
     */
    public function testCssImageWithStylesheetBaseHref($value, $expected)
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $sheet->set_protocol("https://");
        $sheet->set_host("example.com");
        $sheet->set_base_path("/");
        $s = new Style($sheet);

        $s->background_image = $value;
        $this->assertSame($expected, $s->background_image);
    }
}
