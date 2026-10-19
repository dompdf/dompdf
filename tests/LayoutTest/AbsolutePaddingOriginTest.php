<?php
namespace Dompdf\Tests\LayoutTest;

use Dompdf\Dompdf;
use Dompdf\Tests\TestCase;

class AbsolutePaddingOriginTest extends TestCase
{
    public static function containingBlockProvider(): array
    {
        return [[200, 100, 22], [100, 200, 12]];
    }

    /**
     * @dataProvider containingBlockProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('containingBlockProvider')]
    public function testPercentageMarginUsesWidthForAbsoluteChild(int $width, int $height, int $offset): void
    {
        $dompdf = new Dompdf();
        $dompdf->setPaper([0, 0, 400, 400]);
        $frames = [];
        $dompdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$frames) {
            $node = $frame->get_node();
            if ($node instanceof \DOMElement && in_array($node->getAttribute('id'), ['parent', 'absolute'], true)) {
                $frames[$node->getAttribute('id')] = [
                    'position' => $frame->get_position(),
                    'padding' => $frame->get_padding_box(),
                    'border' => $frame->get_border_box(),
                    'containing' => $frame->get_containing_block(),
                ];
            }
        }]]);
        $dompdf->loadHtml('<!doctype html><style>@page{margin:0}html,body{margin:0;padding:0}' .
            '#container{width:' . $width . 'pt;height:' . $height . 'pt;padding-top:1pt}' .
            '#parent{position:relative;margin-top:10%;width:80pt;height:40pt;border:2pt solid black;padding:3pt}' .
            '#absolute{position:absolute;top:0;left:0;width:8pt;height:8pt;background:blue}' .
            '</style><div id="container"><div id="parent"><div id="absolute"></div></div></div>');
        $dompdf->render();
        $this->assertArrayHasKey('parent', $frames);
        $this->assertArrayHasKey('absolute', $frames);
        $parent = $frames['parent'];
        $child = $frames['absolute'];
        $this->assertEqualsWithDelta($offset, $parent['padding']['y'] - $parent['position']['y'], 1e-7, json_encode($frames));
        $this->assertEqualsWithDelta($parent['border']['y'] + 2, $parent['padding']['y'], 1e-7);
        $this->assertEqualsWithDelta($parent['padding']['y'], $child['containing']['y'], 1e-7);
        $this->assertEqualsWithDelta($parent['padding']['y'], $child['position']['y'], 1e-7);
        $this->assertEqualsWithDelta(86, $parent['padding']['w'], 1e-7);
        $this->assertEqualsWithDelta(46, $parent['padding']['h'], 1e-7);
    }
}
