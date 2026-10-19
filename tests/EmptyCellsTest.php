<?php
namespace Dompdf\Tests;

use Dompdf\Dompdf;
use Symfony\Component\Process\Process;

class EmptyCellsTest extends TestCase
{
    public static function contentProvider(): array
    {
        $image = '<img src="jamaica.jpg" style="width:10pt;height:10pt%s">';
        return [
            ['', '', 'separate', 'hide', false],
            ['   ', '', 'separate', 'hide', false],
            [sprintf($image, ''), '', 'separate', 'hide', true],
            [sprintf($image, ';visibility:hidden'), '', 'separate', 'hide', true],
            ['<span style="visibility:hidden">' . sprintf($image, ';visibility:visible') . '</span>', '', 'separate', 'hide', true],
            [sprintf($image, ';display:none!important'), '', 'separate', 'hide', false],
            ['   ', 'white-space:pre', 'separate', 'hide', true],
            ['&nbsp;', '', 'separate', 'hide', true],
            ['<span></span>', '', 'separate', 'hide', true],
            ['<div></div>', '', 'separate', 'hide', true],
            [sprintf($image, ';position:absolute'), 'position:relative', 'separate', 'hide', false],
            ['<span style="padding:2pt;background:blue"></span>', '', 'separate', 'hide', true],
            ['<div style="float:left"></div>', '', 'separate', 'hide', true],
            ['', '', 'separate', 'show', true],
            ['', '', 'collapse', 'hide', true],
            ['text', '', 'separate', 'hide', true],
        ];
    }

    /**
     * @dataProvider contentProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('contentProvider')]
    public function testActualCellBackgroundAndBorder(string $content, string $style, string $collapse, string $empty, bool $paint): void
    {
        $dompdf = new Dompdf();
        $dompdf->getOptions()->setChroot(dirname(__DIR__));
        $dompdf->setBasePath(__DIR__ . '/_files/');
        $dompdf->setPaper([0, 0, 300, 300]);
        $box = null;
        $dompdf->setCallbacks([['event' => 'end_frame', 'f' => function ($frame) use (&$box) {
            $node = $frame->get_node();
            if ($node instanceof \DOMElement && $node->getAttribute('id') === 'cell') {
                $box = $frame->get_border_box();
            }
        }]]);
        $dompdf->loadHtml('<style>@page{margin:0}body{margin:10pt}table{background:lime;border-spacing:0;border-collapse:' . $collapse . ';empty-cells:' . $empty . '}' .
            'td{width:50pt;height:30pt;background:red;border:2pt solid black}</style>' .
            '<table><tr><td id="cell" style="' . $style . '">' . $content . '</td><td>guard</td></tr></table>');
        $dompdf->render();
        $this->assertNotNull($box);
        $process = new Process([
            'gs', '-q', '-dBATCH', '-dNOPAUSE', '-sstdout=%stderr',
            '-r72', '-sDEVICE=png16m', '-sOutputFile=-', '-'
        ]);
        $process->setInput($dompdf->output());
        $process->mustRun();
        $this->assertSame('', $process->getErrorOutput());
        $image = imagecreatefromstring($process->getOutput());
        foreach ([
            [(int) ($box['x'] + $box['w'] - 6), (int) ($box['y'] + $box['h'] - 6), $paint ? [255, 0, 0] : [0, 255, 0]],
            [(int) ($box['x'] + 1), (int) ($box['y'] + $box['h'] - 8), $paint ? [0, 0, 0] : [0, 255, 0]],
        ] as [$x, $y, $expected]) {
            $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
            foreach (['red', 'green', 'blue'] as $index => $channel) {
                $this->assertEqualsWithDelta($expected[$index], $color[$channel], 1);
            }
        }
        imagedestroy($image);
    }
}
