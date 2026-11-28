<?php
namespace Dompdf\Tests\LayoutTest;

use Dompdf\Dompdf;
use Dompdf\FrameDecorator\Table;
use Dompdf\Tests\TestCase;

class ZeroRowspanTest extends TestCase
{
    public function testImplicitGroupAndOrdinarySpanDefaults(): void
    {
        $dompdf = new Dompdf();
        $cells = [];
        $dompdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$cells) {
            $node = $frame->get_node();
            if ($node instanceof \DOMElement && $node->tagName === 'td' && $node->hasAttribute('id')) {
                $cells[$node->getAttribute('id')] = Table::find_parent_table($frame)->get_cellmap()->get_spanned_cells($frame);
            }
        }]]);
        $dompdf->loadHtml('<table><tr><td id="zero" rowspan="0">A</td><td id="missing">B</td></tr>' .
            '<tr><td id="invalid" rowspan="invalid">C</td></tr><tr><td id="positive" rowspan="2">D</td></tr>' .
            '<tr></tr><tr><td id="negative" rowspan="-1">E</td></tr></table>');
        $dompdf->render();
        $this->assertSame([0, 1, 2, 3, 4], $cells['zero']['rows']);
        $this->assertSame([0], $cells['missing']['rows']);
        $this->assertSame([1], $cells['invalid']['rows']);
        $this->assertSame([2, 3], $cells['positive']['rows']);
        $this->assertSame([4], $cells['negative']['rows']);
        foreach (['missing', 'invalid', 'positive', 'negative'] as $id) {
            $this->assertSame([1], $cells[$id]['columns']);
        }
    }

    public function testZeroSpanTableStillPaginatesWithoutLosingRows(): void
    {
        $dompdf = new Dompdf();
        $dompdf->setPaper([0, 0, 200, 140]);
        $seen = [];
        $dompdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$seen) {
            $node = $frame->get_node();
            if ($node instanceof \DOMElement && $node->tagName === 'td' && $node->hasAttribute('id')) {
                $seen[] = $node->getAttribute('id');
            }
        }]]);
        $html = '<style>@page{margin:0}td{height:25pt}table{border-spacing:0}</style><table>' .
            '<tbody><tr><td rowspan="0">span</td><td id="r0">row0</td></tr><tr><td id="r1">row1</td></tr></tbody><tbody>';
        for ($i = 2; $i < 12; $i++) {
            $html .= '<tr><td id="r' . $i . '">row' . $i . '</td><td>other</td></tr>';
        }
        $dompdf->loadHtml($html . '</tbody></table>');
        $dompdf->render();
        $this->assertGreaterThan(1, $dompdf->getCanvas()->get_page_count());
        $this->assertSame(array_map(static function ($i) {
            return 'r' . $i;
        }, range(0, 11)), $seen);
    }

    public static function borderProvider(): array
    {
        return [['collapse'], ['separate']];
    }

    /**
     * @dataProvider borderProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('borderProvider')]
    public function testZeroStaysWithinItsNormalizedRowGroup(string $border): void
    {
        $dompdf = new Dompdf();
        $cells = [];
        $dompdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$cells) {
            $node = $frame->get_node();
            if ($node instanceof \DOMElement && $node->tagName === 'td' && $node->hasAttribute('id')) {
                $table = Table::find_parent_table($frame);
                $cells[$node->getAttribute('id')] = $table->get_cellmap()->get_spanned_cells($frame);
            }
        }]]);
        $dompdf->loadHtml('<style>td{height:20pt;border:1pt solid black}table{border-collapse:' . $border . '}</style>' .
            '<table><thead><tr><td id="head" rowspan="0">H</td><td>H1</td></tr><tr><td id="head2">H2</td></tr></thead>' .
            '<tbody><tr><td id="a" rowspan="0">A</td><td>B1</td></tr><tr><td id="b2">B2</td></tr><tr><td>B3</td></tr></tbody>' .
            '<tbody><tr><td id="c">C</td><td>D</td></tr><tr><td id="c2" rowspan=" +00 ">C2</td><td>D2</td></tr><tr><td id="d3">D3</td></tr></tbody>' .
            '<tfoot><tr><td id="foot" rowspan="0">F</td><td>F2</td></tr></tfoot></table>');
        $dompdf->render();
        $this->assertCount(8, $cells);
        $this->assertSame([0, 1], $cells['head']['rows']);
        $this->assertSame([1], $cells['head2']['columns']);
        $this->assertSame([2, 3, 4], $cells['a']['rows']);
        $this->assertSame([1], $cells['b2']['columns']);
        $this->assertSame([0], $cells['c']['columns']);
        $this->assertSame([6, 7], $cells['c2']['rows']);
        $this->assertSame([1], $cells['d3']['columns']);
        $this->assertSame([8], $cells['foot']['rows']);
    }
}
