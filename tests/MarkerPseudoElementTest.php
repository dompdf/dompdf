<?php
namespace Dompdf\Tests;

use Dompdf\Css\Content\Counter;
use Dompdf\Css\Content\Counters;
use Dompdf\Css\Content\StringPart;
use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\Renderer\ListBullet as ListBulletRenderer;
use ReflectionMethod;

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

    public function testNestedListItemCountersAreResolved(): void
    {
        $markers = [];

        $dompdf = new Dompdf();
        $renderer = new ListBulletRenderer($dompdf);
        $resolver = new ReflectionMethod(ListBulletRenderer::class, "resolve_marker_content");
        $resolver->setAccessible(true);

        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame) use (&$markers, $renderer, $resolver): void {
                    $node = $frame->get_node();
                    if ($node->nodeName !== "bullet") {
                        return;
                    }

                    $content = $frame->get_style()->content;
                    if (!is_array($content) || !isset($content[0]) || !$content[0] instanceof Counters) {
                        return;
                    }

                    $index = $node->hasAttribute("dompdf-counter")
                        ? (int) $node->getAttribute("dompdf-counter")
                        : 0;

                    $markers[] = $resolver->invoke($renderer, $frame, $index);
                },
            ],
        ]);

        $dompdf->loadHtml(<<<HTML
<!DOCTYPE html>
<html>
<head>
<style>
ol.legal-list {
    list-style: none;
}

ol.legal-list > li::marker {
    content: counters(list-item, ".") " ";
}
</style>
</head>
<body>
<ol class="legal-list">
    <li>Outer
        <ol class="legal-list">
            <li>First</li>
            <li>Second</li>
            <li>Third</li>
        </ol>
    </li>
</ol>
</body>
</html>
HTML
        );
        $dompdf->render();

        $this->assertSame([
            "1 ",
            "1.1 ",
            "1.2 ",
            "1.3 ",
        ], $markers);
    }
}
