<?php
namespace Dompdf\Tests\LayoutTest;

use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\Helpers;
use Dompdf\Options;
use Dompdf\Tests\TestCase;

class ImageTest extends TestCase
{
    public function imageDimensionsProvider(): array
    {
        $filepath = "../_files/jamaica.jpg";
        $dpiFactor = 72 / 96;
        $intrinsicWidth = 2048 * $dpiFactor;
        $intrinsicHeight = 1536 * $dpiFactor;

        return [
            // TODO: Heredocs can be nicely indented starting with PHP 7.3
            "zero" => [
                <<<HTML
<img src="$filepath" style="width: 0; height: 0;">
HTML
,
                0.0,
                0.0
            ],
            "auto" => [
                <<<HTML
<img src="$filepath">
HTML
,
                $intrinsicWidth,
                $intrinsicHeight
            ],
            "fixed px" => [
                <<<HTML
<img src="$filepath" width="100" height="200">
HTML
,
                100 * $dpiFactor,
                200 * $dpiFactor
            ],
            "fixed pt" => [
                <<<HTML
<img src="$filepath" style="width: 100pt; height: 200pt;">
HTML
,
                100.0,
                200.0
            ],
            "min-max 1" => [
                <<<HTML
<img src="$filepath" style="
    width: 100px;
    height: 1200px;
    min-width: 400px;
    max-width: 800px;
    min-height: 300px;
    max-height: 500px;
    ">
HTML
,
                400 * $dpiFactor,
                500 * $dpiFactor
            ],
            "min-max 2" => [
                <<<HTML
<img src="$filepath" style="
    width: auto;
    height: 100px;
    max-width: 200px;
    min-height: 200px;
    ">
HTML
,
                200 * $dpiFactor,
                200 * $dpiFactor
            ],
            "min-max 3" => [
                <<<HTML
<img src="$filepath" style="
    width: 100px;
    height: auto;
    min-width: 200px;
    max-height: 200px;
    ">
HTML
,
                200 * $dpiFactor,
                150 * $dpiFactor
            ],
            "min-max 4" => [
                <<<HTML
<img src="$filepath" style="
    width: auto;
    height: auto;
    max-width: 100px;
    max-height: 200px;
    ">
HTML
,
                100 * $dpiFactor,
                75 * $dpiFactor
            ],
            "min-max 5" => [
                <<<HTML
<img src="$filepath" style="
    width: auto;
    height: auto;
    max-width: 100px;
    max-height: 50px;
    ">
HTML
,
                50 * (4 / 3) * $dpiFactor,
                50 * $dpiFactor
            ],
            "page size" => [
                <<<HTML
<img src="$filepath" style="width: 100%; height: 100%;">
HTML
,
                400.0,
                300.0
            ],
            "percentage chain" => [
                <<<HTML
<div style="width: 400px; height: 800px;">
    <div style="width: 50%; height: 75%;">
        <img src="$filepath" style="width: 50%; height: 75%;">
    </div>
</div>
HTML
,
                100 * $dpiFactor,
                450 * $dpiFactor
            ],
            "in table" => [
                <<<HTML
<table style="width: 20%; border-collapse: collapse;">
    <tr>
        <td style="padding: 0;">
            <img src="$filepath">
        </td>
    </tr>
</div>
HTML
,
                $intrinsicWidth,
                $intrinsicHeight
            ],
            "in table max-width" => [
                <<<HTML
<table style="width: 20%; border-collapse: collapse;">
    <tr>
        <td style="padding: 0;">
            <img src="$filepath" style="max-width: 100%; height: auto;">
        </td>
    </tr>
</div>
HTML
,
                80.0,
                60.0
            ]
        ];
    }

    /**
     * @dataProvider imageDimensionsProvider
     */
    public function testImageDimensions(
        string $body,
        float $expectedWidth,
        float $expectedHeight
    ): void {
        $width = null;
        $height = null;

        $options = new Options();

        // Use callback to inspect frame tree
        $dompdf = new Dompdf($options);
        $dompdf->setBasePath(__DIR__);
        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame) use (&$width, &$height) {
                    if ($frame->get_node()->nodeName === "img") {
                        [, , $width, $height] = $frame->get_content_box();
                    }
                }
            ]
        ]);

        $dompdf->loadHtml(<<<HTML
    <!DOCTYPE html>
    <head>
    <meta charset="UTF-8">
    <style>
        @page {
            size: 400pt 300pt;
            margin: 0;
        }
    </style>
    </head>
    <html>
    <body>$body</body>
    </html>
HTML
        );
        $dompdf->render();

        $this->assertTrue(
            Helpers::lengthEqual($expectedWidth, $width),
            "Failed asserting that width $width is equal to $expectedWidth."
        );
        $this->assertTrue(
            Helpers::lengthEqual($expectedHeight, $height),
            "Failed asserting that height $height is equal to $expectedHeight."
        );
    }
}
