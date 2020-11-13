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

    /**
     * @group regression
     */
    public function testCssImageNoneParsingNoBaseHref()
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $s = new Style($sheet);

        // keyword none
        $s->background_image = "none";
        $this->assertEquals("none", $s->background_image);

        // no value
        $s->background_image = "";
        $this->assertEquals("none", $s->background_image);

        // bare url
        $s->background_image = "http://example.com/test.png";
        $this->assertEquals("none", $s->background_image);
    }

    /**
     * @group regression
     */
    public function testCssImageNoneParsingWithBaseHref()
    {
        $dompdf = new Dompdf();
        $dompdf->setProtocol("https://");
        $dompdf->setBaseHost("example.com");
        $dompdf->setBasePath("/");
        $sheet = new Stylesheet($dompdf);
        $s = new Style($sheet);

        // keyword none
        $s->background_image = "none";
        $this->assertEquals("none", $s->background_image);

        // no value
        $s->background_image = "";
        $this->assertEquals("none", $s->background_image);

        // bare url
        $s->background_image = "http://example.com/test.png";
        $this->assertEquals("none", $s->background_image);
    }

    /**
     * @group regression
     */
    public function testCssImageNoneParsingWithStylesheetBaseHref()
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $sheet->set_protocol("https://");
        $sheet->set_host("example.com");
        $sheet->set_base_path("/");
        $s = new Style($sheet);

        // keyword none
        $s->background_image = "none";
        $this->assertEquals("none", $s->background_image);

        // no value
        $s->background_image = "";
        $this->assertEquals("none", $s->background_image);

        // bare url
        $s->background_image = "http://example.com/test.png";
        $this->assertEquals("none", $s->background_image);
    }
}
