<?php
namespace Dompdf\Tests\LayoutTest;

use DOMElement;
use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\Options;
use Dompdf\Tests\TestCase;

class PageTest extends TestCase
{
    public function pageBreakProvider(): array
    {
        return [
            // TODO: Heredocs can be nicely indented starting with PHP 7.3
            "one page" => [
                <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page {
        size: 400pt 400pt;
        margin: 0;
    }

    body {
        background-color: rgb(0, 0, 0, 0.05);
    }

    .box {
        height: 400pt;
        background-color: lightblue;
    }
</style>
</head>
<body><div class="box"></div></body>
</html>
HTML
,
                1,
                ["box" => 1]
            ],
            "two pages" => [
                <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page {
        size: 400pt 400pt;
        margin: 0;
    }

    @page :first {
        margin-bottom: 100pt;
    }

    body {
        background-color: rgb(0, 0, 0, 0.05);
    }

    .box {
        height: 400pt;
        background-color: lightblue;
    }
</style>
</head>
<body><div class="box"></div></body>
</html>
HTML
,
                2,
                ["box" => 2]
            ],
        ];
    }

    /**
     * @dataProvider pageBreakProvider
     */
    public function testPageBreak(
        string $html,
        int $pageCount,
        array $expectedPages
    ): void {
        $elementPages = [];

        $options = new Options();

        // Use callback to inspect frame tree
        $dompdf = new Dompdf($options);
        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame, Canvas $canvas) use ($expectedPages, &$elementPages) {
                    $node = $frame->get_node();

                    if (!($node instanceof DOMElement)) {
                        return;
                    }

                    $class = $node->getAttribute("class");

                    if (isset($expectedPages[$class])) {
                        $elementPages[$class] = $canvas->get_page_number();
                    }
                }
            ]
        ]);

        $dompdf->loadHtml($html);
        $dompdf->render();

        $this->assertSame($pageCount, $dompdf->getCanvas()->get_page_count());

        foreach ($expectedPages as $class => $pageNumber) {
            $this->assertSame($pageNumber, $elementPages[$class] ?? 0);
        }
    }
}
