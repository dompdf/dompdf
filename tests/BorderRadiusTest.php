<?php
namespace Dompdf\Tests;

use Dompdf\Dompdf;
use Symfony\Component\Process\Process;

class BorderRadiusTest extends TestCase
{
    public function testOverlappingRadiiKeepTheirProportions(): void
    {
        $dompdf = new Dompdf();
        $style = $dompdf->getCss()->create_style();
        $style->set_prop('border_radius', '80pt 80pt 10pt 10pt');
        $this->assertSame([50.0, 50.0, 6.25, 6.25], $style->resolve_border_radius([0, 0, 100, 100]));
        $control = $dompdf->getCss()->create_style();
        $control->set_prop('border_radius', '20pt 10pt 5pt 15pt');
        $this->assertSame([20.0, 10.0, 5.0, 15.0], $control->resolve_border_radius([0, 0, 100, 100]));
    }

    public function testBackgroundUsesTheProportionallyReducedBottomCorners(): void
    {
        $dompdf = new Dompdf();
        $dompdf->setPaper([0, 0, 400, 400]);
        $dompdf->loadHtml('<style>@page{margin:0}html,body{margin:0;padding:0}div{width:100pt;height:100pt;background:black;border-radius:80pt 80pt 10pt 10pt}</style><div></div>');
        $dompdf->render();
        $process = new Process([
            'gs', '-q', '-dBATCH', '-dNOPAUSE', '-sstdout=%stderr',
            '-r72', '-sDEVICE=png16m', '-sOutputFile=-', '-'
        ]);
        $process->setInput($dompdf->output());
        $process->mustRun();
        $this->assertSame('', $process->getErrorOutput());
        $image = imagecreatefromstring($process->getOutput());
        $this->assertSame(400, imagesx($image));
        $this->assertSame(400, imagesy($image));
        foreach ([[2, 97, 0], [97, 97, 0], [50, 50, 0], [1, 1, 255], [200, 200, 255]] as [$x, $y, $expected]) {
            $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
            foreach (['red', 'green', 'blue'] as $channel) {
                $this->assertEqualsWithDelta($expected, $color[$channel], 1);
            }
        }
        imagedestroy($image);
    }
}
