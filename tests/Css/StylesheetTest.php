<?php
namespace Dompdf\Tests\Css;

use Dompdf\Css\Style;
use Dompdf\Dompdf;
use Dompdf\Css\Stylesheet;
use Dompdf\Tests\TestCase;

class StylesheetTest extends TestCase
{
    public static function parseCssProvider(): array
    {
        return [
            // TODO: Heredocs can be nicely indented starting with PHP 7.3
            "closing parenthesis in string" => [
                <<<CSS
li::before {
    counter-increment: c;
    content: ")";
}
CSS
,
                [
                    "li::before" => [[
                        "counter_increment" => "c",
                        "content" => '")"'
                    ]]
                ]
            ],
            "semicolon in url" => [
                <<<CSS
div {
    background-image: url(image;\(12\).png);
}
CSS
,
                [
                    "div" => [[
                        "background_image" => "url(image;\(12\).png)"
                    ]]
                ]
            ]
        ];
    }

    /**
     * The expected styles define the selectors to check. For each selector, the
     * styles have to match the defined properties in their specified values.
     *
     * @dataProvider parseCssProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('parseCssProvider')]
    public function testParseCss(string $css, array $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $sheet->load_css($css);

        $styles = $sheet->get_styles();
        $actual = [];

        foreach ($expected as $selector => $expectedStyles) {
            $this->assertArrayHasKey($selector, $styles);
            $this->assertSameSize($expectedStyles, $styles[$selector]);

            $actual[$selector] = array_map(function (array $props, Style $style) {
                $propNames = array_keys($props);
                $values = array_map(function (string $prop) use ($style) {
                    return $style->get_specified($prop);
                }, $propNames);

                return array_combine($propNames, $values);
            }, $expectedStyles, $styles[$selector]);
        }

        $this->assertSame($expected, $actual);
    }

    /**
     * Confirms that data URIs are parsed internally as blob URIs
     * and that the output value is the original data URI.
     */
    public function testDataUriHandling(): void
    {
        $basePath = realpath(__DIR__ . "/..");
        $imagePath = "$basePath/_files/jamaica.jpg";
        $imageEncoded = base64_encode(file_get_contents($imagePath));
        $dataUri = "data:image/jpeg;base64," . $imageEncoded;
        $css = "div { background-color: #000; background-image: url(\"$dataUri\"); }";

        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $sheet->load_css($css);

        $styles = $sheet->get_styles();

        $this->assertArrayHasKey("div", $styles);
        $this->assertSame("#000", $styles["div"][0]->get_specified("background_color"));
        $this->assertSame("blob://", substr($sheet->resolve_url($styles["div"][0]->get_specified("background_image")), 0, 7));
        $this->assertSame($dataUri, $styles["div"][0]->background_image);
    }
}
