<?php
namespace Dompdf\Tests;

use Dompdf\Dompdf;
use Symfony\Component\Process\Process;

class HiddenDescendantsTest extends TestCase
{
    public function testVisibleDescendantsRetainAncestorGraphicsState(): void
    {
        $dompdf = new Dompdf();
        $dompdf->setPaper([0, 0, 400, 400]);
        $dompdf->loadHtml('<style>@page{margin:0}html,body{margin:0;padding:0}' .
            'div{position:absolute}#hidden{left:20pt;top:20pt;width:80pt;height:40pt;visibility:hidden;opacity:.5;transform:translate(12pt,8pt);overflow:hidden;background:red}' .
            '#blue{left:0;top:0;width:140pt;height:20pt;visibility:visible;background:blue}' .
            '#inherited{left:0;top:20pt;width:80pt;height:20pt;background:red}' .
            '#sibling{left:10pt;top:100pt;width:20pt;height:20pt;background:lime}' .
            '#nested{left:20pt;top:150pt;visibility:hidden}#nested div{width:20pt;height:20pt;background:lime;visibility:visible}' .
            '#stack{left:180pt;top:20pt;width:40pt;height:40pt;visibility:hidden}#stack div{left:0;top:0;width:40pt;height:40pt;visibility:visible}' .
            '#low{z-index:1;background:red}#high{z-index:2;background:blue}' .
            '#none{left:250pt;top:20pt;display:none}#none div{visibility:visible;background:red;width:20pt;height:20pt}' .
            '</style><div id="hidden"><div id="blue"></div><div id="inherited"></div></div>' .
            '<div id="sibling"></div><div id="nested"><div></div></div>' .
            '<div id="stack"><div id="high"></div><div id="low"></div></div><div id="none"><div></div></div>');
        $dompdf->render();
        $process = new Process([
            'gs', '-q', '-dBATCH', '-dNOPAUSE', '-sstdout=%stderr',
            '-r72', '-sDEVICE=png16m', '-sOutputFile=-', '-'
        ]);
        $process->setInput($dompdf->output());
        $process->mustRun();
        $this->assertSame('', $process->getErrorOutput());
        $image = imagecreatefromstring($process->getOutput());
        foreach ([
            [40, 35, [127, 127, 255]], // Visible child, transformed and half opaque.
            [25, 35, [255, 255, 255]], // Original untransformed location stays blank.
            [120, 35, [255, 255, 255]], // Child is clipped by the hidden parent.
            [40, 60, [255, 255, 255]], // Parent and inherited-hidden child do not paint.
            [15, 105, [0, 255, 0]], // Following sibling has restored graphics state.
            [25, 155, [0, 255, 0]], // Nested visible override is traversed.
            [190, 30, [0, 0, 255]], // Descendant z-index still determines paint order.
            [255, 25, [255, 255, 255]], // display:none remains a subtree boundary.
        ] as [$x, $y, $expected]) {
            $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
            foreach (['red', 'green', 'blue'] as $index => $channel) {
                $this->assertEqualsWithDelta($expected[$index], $color[$channel], 1, 'Pixel ' . $x . ',' . $y);
            }
        }
        imagedestroy($image);
    }
}
