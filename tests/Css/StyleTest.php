<?php
namespace Dompdf\Tests\Css;

use Dompdf\Dompdf;
use Dompdf\Css\Style;
use Dompdf\Css\Stylesheet;
use Dompdf\Tests\TestCase;

class StyleTest extends TestCase
{
    public function lengthInPtProvider(): array
    {
        return [
            ["auto", null, "auto"],
            ["none", null, "none"],
            ["100px", null, 75.0],
            ["100PX", null, 75.0], // Also check caps
            ["100pt", null, 100.0],
            ["1.5em", 20, 18.0], // Default font size is 12pt
            ["100%", null, 12.0],
            ["50%", 360, 180.0]
        ];
    }

    /**
     * @dataProvider lengthInPtProvider
     */
    public function testLengthInPt(string $length, ?float $ref_size, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $s = new Style($sheet);

        $result = $s->length_in_pt($length, $ref_size);
        $this->assertSame($expected, $result);
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
            "local absolute" => ["url($basePath/_files/jamaica.jpg)", $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"],
            "local relative" => ["url(../_files/jamaica.jpg)", $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"]
        ];
    }

    public function cssImageWithBaseHrefProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        return [
            "local absolute" => ["url($basePath/_files/jamaica.jpg)", $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"],
            "local relative" => ["url(../_files/jamaica.jpg)", $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"]
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
    public function testCssImageNoBaseHref(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $sheet->set_base_path(__DIR__); // Treat stylesheet as being located in this directory
        $s = new Style($sheet);

        $s->background_image = $value;
        $this->assertSame($expected, $s->background_image);
    }

    /**
     * @dataProvider cssImageBasicProvider
     * @dataProvider cssImageWithBaseHrefProvider
     * @group regression
     */
    public function testCssImageWithBaseHref(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $dompdf->setProtocol("https://");
        $dompdf->setBaseHost("example.com");
        $dompdf->setBasePath("/");
        $sheet = new Stylesheet($dompdf);
        $sheet->set_base_path(__DIR__); // Treat stylesheet as being located in this directory
        $s = new Style($sheet);

        $s->background_image = $value;
        $this->assertSame($expected, $s->background_image);
    }

    /**
     * @dataProvider cssImageBasicProvider
     * @dataProvider cssImageWithStylesheetBaseHrefProvider
     * @group regression
     */
    public function testCssImageWithStylesheetBaseHref(string $value, $expected): void
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

    public function contentProvider(): array
    {
        return [
            ["normal", "normal"],
            ["none", "none"],
            [
                "'–' attr(title) '–'",
                ["'–'", "attr(title)", "'–'"]
            ],
            [
                'counter(page)" / {PAGES}"',
                ["counter(page)", '" / {PAGES}"']
            ],
            [
                "counter(li1, decimal)\".\"counter(li2, upper-roman)  ')'url('image.png')",
                ["counter(li1, decimal)", '"."', "counter(li2, upper-roman)", "')'", "url('image.png')"]
            ],
            [
                '"url(\' \')"open-quote url(" ")close-quote',
                ['"url(\' \')"', "open-quote", 'url(" ")', "close-quote"]
            ]
        ];
    }

    /**
     * @dataProvider contentProvider
     */
    public function testContent(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("content", $value);
        $this->assertSame($expected, $style->content);
    }

    public function zIndexProvider(): array
    {
        return [
            // Valid values
            ["auto", "auto"],
            ["0", 0],
            ["1", 1],
            ["+23", 23],
            ["-100", -100],

            // Invalid values
            ["", "auto"],
            ["5.5", "auto"],
            ["invalid", "auto"]
        ];
    }

    /**
     * @dataProvider zIndexProvider
     */
    public function testZIndex(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("z_index", $value);
        $this->assertSame($expected, $style->z_index);
    }

    public function valueCaseProvider(): array
    {
        return [
            ["width", "Auto",           "width", "auto"],
            ["list-style-type", "A",    "list_style_type", "A"],
        ];
    }

    /**
     * @dataProvider valueCaseProvider
     */
    public function testValueCase(string $cssProp, string $inputValue, string $phpProp, string $expectValue): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop($cssProp, $inputValue);
        $this->assertSame($expectValue, $style->$phpProp);
    }

    public function testWordBreakBreakWord(): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("overflow_wrap", "break-word");
        $style->set_prop("word_break", "break-word");
        
        $this->assertSame("normal", $style->word_break);
        $this->assertSame("anywhere", $style->overflow_wrap);
    }
}
