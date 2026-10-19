<?php
namespace Dompdf\Tests;

use Dompdf\Cpdf;
use Symfony\Component\Process\Process;

class CpdfTransparencyTest extends TestCase
{
    public function testColorDodgeIsEmittedForLineAndFill(): void
    {
        $pdf = new Cpdf([0, 0, 100, 100], false, sys_get_temp_dir(), sys_get_temp_dir());
        $pdf->setLineTransparency('ColorDodge', 0.5);
        $pdf->line(10, 10, 50, 10);
        $pdf->setFillTransparency('ColorDodge', 0.6);
        $pdf->filledRectangle(10, 20, 40, 20);
        $this->assertSame(2, substr_count($pdf->output(true), '/BM /ColorDodge'));
    }

    public function testUnsupportedModesStillFallBackToNormal(): void
    {
        foreach (['NotAMode', 'ColorDogde'] as $mode) {
            $pdf = new Cpdf([0, 0, 100, 100], false, sys_get_temp_dir(), sys_get_temp_dir());
            $pdf->setLineTransparency($mode, 0.5);
            $pdf->setFillTransparency($mode, 0.6);
            $output = $pdf->output(true);
            $this->assertStringContainsString('/BM /Normal', $output);
            $this->assertStringNotContainsString('/BM /' . $mode, $output);
        }
    }

    public function testRealRasterUsesColorDodgeForBothPaintOperations(): void
    {
        $pdf = new Cpdf([0, 0, 100, 100], false, sys_get_temp_dir(), sys_get_temp_dir());
        $pdf->setColor([0.4, 0.4, 0.4]);
        $pdf->filledRectangle(0, 0, 100, 100);
        $pdf->setColor([0.5, 0.5, 0.5]);
        $pdf->setFillTransparency('ColorDodge', 1);
        $pdf->filledRectangle(10, 10, 30, 30);
        $pdf->setStrokeColor([0.5, 0.5, 0.5]);
        $pdf->setLineTransparency('ColorDodge', 1);
        $pdf->setLineStyle(10, 'butt');
        $pdf->line(10, 70, 40, 70);

        $process = new Process([
            'gs', '-q', '-dBATCH', '-dNOPAUSE', '-sstdout=%stderr',
            '-r72', '-sDEVICE=png16m', '-sOutputFile=-', '-'
        ]);
        $process->setInput($pdf->output());
        $process->mustRun();
        $this->assertSame('', $process->getErrorOutput());
        $image = imagecreatefromstring($process->getOutput());
        $this->assertSame(100, imagesx($image));
        $this->assertSame(100, imagesy($image));
        $background = imagecolorsforindex($image, imagecolorat($image, 75, 75));
        foreach (['red', 'green', 'blue'] as $channel) {
            $this->assertEqualsWithDelta(102, $background[$channel], 1);
        }
        // 0.4 backdrop / (1 - 0.5 source) = 0.8, or 204 in 8-bit RGB.
        foreach ([[25, 75], [25, 30]] as [$x, $y]) {
            $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
            foreach (['red', 'green', 'blue'] as $channel) {
                $this->assertEqualsWithDelta(204, $color[$channel], 1);
            }
        }
        imagedestroy($image);
    }
}
