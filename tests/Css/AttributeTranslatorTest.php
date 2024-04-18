<?php
namespace Dompdf\Tests\Css;

use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\Tests\TestCase;

final class AttributeTranslatorTest extends TestCase
{
    public static function attributeToStyleTranslationProvider(): array
    {
        return [
            // TODO: Heredocs can be nicely indented starting with PHP 7.3
            "list type ol" => [
                <<<HTML
<ol type="1"></ol>
<ol type="a"></ol>
<ol type="A"></ol>
<ol type="i"></ol>
<ol type="I"></ol>
HTML
,
                [
                    "ol" => [
                        ["list-style-type" => "decimal"],
                        ["list-style-type" => "lower-alpha"],
                        ["list-style-type" => "upper-alpha"],
                        ["list-style-type" => "lower-roman"],
                        ["list-style-type" => "upper-roman"]
                    ]
                ]
            ],
            "list type ul" => [
                <<<HTML
<ul type="1"></ul>
<ul type="a"></ul>
<ul type="A"></ul>
<ul type="i"></ul>
<ul type="I"></ul>
HTML
,
                [
                    "ul" => [
                        ["list-style-type" => "decimal"],
                        ["list-style-type" => "lower-alpha"],
                        ["list-style-type" => "upper-alpha"],
                        ["list-style-type" => "lower-roman"],
                        ["list-style-type" => "upper-roman"]
                    ]
                ]
            ],
            "list type li" => [
                <<<HTML
<ol>
    <li type="1"></li>
    <li type="a"></li>
    <li type="A"></li>
    <li type="i"></li>
    <li type="I"></li>
</ol>
HTML
,
                [
                    "li" => [
                        ["list-style-type" => "decimal"],
                        ["list-style-type" => "lower-alpha"],
                        ["list-style-type" => "upper-alpha"],
                        ["list-style-type" => "lower-roman"],
                        ["list-style-type" => "upper-roman"]
                    ]
                ]
            ]
        ];
    }

    /**
     * The expected styles defines the nodes to check by node name. For each
     * name, the corresponding nodes have to match the expected styles in
     * order before render.
     *
     * @dataProvider attributeToStyleTranslationProvider
     */
    public function testAttributeToStyleTranslation(
        string $body,
        array $expectedStyles
    ): void {
        $styles = array_fill_keys(array_keys($expectedStyles), []);

        // Use callback to inspect frame tree
        $dompdf = new Dompdf();
        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame) use ($expectedStyles, &$styles) {
                    $node = $frame->get_node();
                    $name = $node->nodeName;

                    if (isset($expectedStyles[$name])) {
                        $translateProp = function ($prop) {
                            return str_replace("-", "_", $prop);
                        };

                        $style = $frame->get_style();
                        $index = count($styles[$name]);
                        $keys = array_keys($expectedStyles[$name][$index]);
                        $props = array_map($translateProp, $keys);
                        $values = array_map(function ($prop) use ($style) {
                            return $style->$prop;
                        }, $props);

                        $styles[$name][] = array_combine($keys, $values);
                    }
                }
            ]
        ]);

        $dompdf->loadHtml("<html><body>$body</body></html>");
        $dompdf->render();

        $this->assertSame($expectedStyles, $styles);
    }
}
