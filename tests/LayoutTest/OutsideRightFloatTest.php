<?php
namespace Dompdf\Tests\LayoutTest;

use Dompdf\Dompdf;
use Dompdf\Tests\TestCase;

class OutsideRightFloatTest extends TestCase
{
    public static function marginProvider(): array
    {
        return [[0, 40, 0], [40, 5, 20], [5, 5, 20]];
    }

    /**
     * @dataProvider marginProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('marginProvider')]
    public function testActualOutsideFloatUsesTheRightMargin(int $left, int $right, int $clearance): void
    {
        $dompdf = new Dompdf();
        $dompdf->setPaper([0, 0, 400, 400]);
        $lines = [];
        $dompdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$lines) {
            $node = $frame->get_node();
            if ($node instanceof \DOMElement && $node->getAttribute('id') === 'text') {
                foreach ($frame->get_line_boxes() as $line) {
                    if (!$line->is_empty()) {
                        $lines[] = ['right' => $line->right, 'left' => $line->left, 'floats' => count($line->floating_blocks)];
                    }
                }
            }
        }]]);
        $dompdf->loadHtml('<style>@page{margin:0}html,body{margin:0;padding:0}' .
            '#container{width:200pt}#float{float:right;width:30pt;height:80pt}' .
            '#text{margin-left:' . $left . 'pt;margin-right:' . $right . 'pt;padding-right:5pt}' .
            '</style><div id="container"><div id="float"></div><div id="text">Ordinary text wrapping around a real outside right float.</div></div>');
        $dompdf->render();
        $this->assertNotEmpty($lines);
        foreach ($lines as $line) {
            $this->assertSame(1, $line['floats'], json_encode($lines));
            $this->assertEqualsWithDelta($clearance, $line['right'], 1e-7, json_encode($lines));
            $this->assertEqualsWithDelta(0, $line['left'], 1e-7);
        }
    }
}
