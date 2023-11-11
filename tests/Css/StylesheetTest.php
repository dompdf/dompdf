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
}
