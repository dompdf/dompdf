<?php
namespace Dompdf\Tests;

use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;

final class HasMarkerPseudoElementTest extends TestCase
{
    public function testHasSelectorStylesMarker(): void
    {
        $boldMarkers = [];

        $dompdf = new Dompdf();
        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame) use (&$boldMarkers): void {
                    if ($frame->get_node()->nodeName !== "bullet") {
                        return;
                    }

                    $boldMarkers[] = $frame->get_style()->get_specified("font_weight") === "bold";
                },
            ],
        ]);

        $dompdf->loadHtml(<<<HTML
<!DOCTYPE html>
<html>
<head>
<style>
ol > li:has(> strong:first-child)::marker {
    font-weight: bold;
}
</style>
</head>
<body>
<ol>
    <li><strong>First</strong> text</li>
    <li><span>First</span><strong>Second</strong></li>
    <li>Text node <strong>First element child</strong></li>
</ol>
</body>
</html>
HTML
        );
        $dompdf->render();

        $this->assertSame([true, false, true], $boldMarkers);
    }
}
