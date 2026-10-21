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
    public static function pageBreakProvider(): array
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
            "bottom margin does not have to fit" => [
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
        margin: 0;
    }

    .a {
        height: 200pt;
    }

    .b {
        height: 150pt;
        margin-bottom: 100pt;
    }

    .c {
        height: 10pt;
    }
</style>
</head>
<body><div class="a"></div><div class="b"></div><div class="c"></div></body>
</html>
HTML
,
                2,
                ["a" => 1, "b" => 1, "c" => 2]
            ],
            "bottom margin does not hide overflow" => [
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
        margin: 0;
    }

    .a {
        height: 200pt;
    }

    .b {
        height: 250pt;
        margin-bottom: 100pt;
    }

    .c {
        height: 10pt;
    }
</style>
</head>
<body><div class="a"></div><div class="b"></div><div class="c"></div></body>
</html>
HTML
,
                2,
                ["a" => 1, "b" => 2, "c" => 2]
            ],
            "bottom padding of a parent is not reserved before a break" => [
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
        margin: 0;
    }

    .wrap {
        padding-bottom: 100pt;
    }

    .a {
        height: 200pt;
    }

    .b {
        height: 150pt;
    }

    .c {
        height: 100pt;
    }
</style>
</head>
<body>
<div class="wrap">
    <div class="a"></div>
    <div class="b"></div>
    <div class="c"></div>
</div>
</body>
</html>
HTML
,
                2,
                ["a" => 1, "b" => 1, "c" => 2]
            ],
            "bottom padding of a parent has to fit at the end" => [
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
        margin: 0;
    }

    .wrap {
        padding-bottom: 100pt;
    }

    .a {
        height: 200pt;
    }

    .b {
        height: 150pt;
    }

    .c {
        height: 100pt;
    }
</style>
</head>
<body>
<div class="wrap">
    <div class="a"></div>
    <div class="b"></div>
</div>
</body>
</html>
HTML
,
                2,
                ["a" => 1, "b" => 2]
            ],
            "paragraph with a large bottom margin is not moved as a whole" => [
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
        margin: 0;
    }

    .p {
        margin: 0 0 300pt 0;
        font-size: 12pt;
        line-height: 120pt;
    }

    .c {
        height: 10pt;
    }
</style>
</head>
<body>
<p class="p">A<br>B<br>C</p>
<div class="c"></div>
</body>
</html>
HTML
,
                2,
                ["p" => 1, "c" => 2]
            ],
        ];
    }

    /**
     * @dataProvider pageBreakProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pageBreakProvider')]
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
