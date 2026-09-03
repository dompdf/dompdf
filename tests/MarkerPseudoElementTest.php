<?php
namespace Dompdf\Tests;

use Dompdf\Css\Content\Counter;
use Dompdf\Css\Content\StringPart;
use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;

final class MarkerPseudoElementTest extends TestCase
{
    public function testMarkerStylesAreAppliedToListBullet(): void
    {
        $markers = [];

        $dompdf = new Dompdf();
        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame) use (&$markers): void {
                    if ($frame->get_node()->nodeName !== "bullet") {
                        return;
                    }

                    $style = $frame->get_style();
                    $markers[] = [
                        "font_weight" => $style->get_specified("font_weight"),
                        "content" => $style->content,
                    ];
                },
            ],
        ]);

        $dompdf->loadHtml(<<<HTML
<!DOCTYPE html>
<html>
<head>
<style>
ol.parenthesized-list {
    list-style: none;
}

ol.parenthesized-list > li::marker {
    content: "(" counter(list-item) ") ";
    font-weight: bold;
}
</style>
</head>
<body>
<ol class="parenthesized-list">
    <li>First</li>
    <li>Second</li>
</ol>
</body>
</html>
HTML
        );
        $dompdf->render();

        $this->assertCount(2, $markers);

        foreach ($markers as $marker) {
            $this->assertSame("bold", $marker["font_weight"]);
            $this->assertIsArray($marker["content"]);
            $this->assertCount(3, $marker["content"]);
            $this->assertInstanceOf(StringPart::class, $marker["content"][0]);
            $this->assertSame("(", $marker["content"][0]->string);
            $this->assertInstanceOf(Counter::class, $marker["content"][1]);
            $this->assertSame("list-item", $marker["content"][1]->name);
            $this->assertSame("decimal", $marker["content"][1]->style);
            $this->assertInstanceOf(StringPart::class, $marker["content"][2]);
            $this->assertSame(") ", $marker["content"][2]->string);
        }
    }
}
