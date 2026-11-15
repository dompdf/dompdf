<?php
namespace Dompdf\Tests;

use Dompdf\Cpdf;

class CpdfTest extends TestCase
{
    private const RED = [1, 0, 0];
    private const BLUE = [0, 0, 1];

    public static function pdfaProvider(): array
    {
        return ["regular" => [false], "PDF/A" => [true]];
    }

    /**
     * @dataProvider pdfaProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('pdfaProvider')]
    public function testGradientResourcesAndState(bool $pdfa): void
    {
        $pdf = new Cpdf([0, 0, 200, 200]);
        if ($pdfa) {
            $pdf->enablePdfACompliance();
        }
        $stops = [[0, self::RED, 1], [1, self::BLUE, 0.5]];
        $pdf->gradient(2, [0, 0, 200, 0], $stops, [0, 0, 200, 200]);
        $pdf->newPage();
        $pdf->gradient(2, [0, 0, 200, 0], $stops, [0, 0, 200, 200]);
        $output = $pdf->output(true);

        // Every shading used is defined as a resource
        preg_match_all("/\/(Sh\d+) sh/", $output, $used);
        $this->assertCount(4, $used[1]);
        foreach (array_unique($used[1]) as $label) {
            $this->assertSame(1, preg_match("/\/$label \d+ 0 R/", $output));
        }

        // The soft mask only applies to the shading on each page
        $this->assertSame(2, preg_match_all("/\nq\n\/GS\d+ gs\n\/Sh\d+ sh\nQ/", $output));
    }

    public function testGradientNegativeRadius(): void
    {
        $pdf = new Cpdf([0, 0, 200, 200]);
        $pdf->gradient(3, [0, 0, -1, 0, 0, 1], [[0, self::RED, 1], [1, self::BLUE, 1]], [0, 0, 200, 200]);
        $output = $pdf->output(true);

        // Starts at radius 0, with the color halfway between red and blue
        $this->assertStringContainsString("/Coords [0.0000 0.0000 0.0000 0.0000 0.0000 1.0000]", $output);
        $this->assertStringContainsString("/C0 [0.5000 0.0000 0.5000]", $output);
    }

    public function testGradientRepeatLimitUsesAverageColor(): void
    {
        $pdf = new Cpdf([0, 0, 200, 200]);
        $stops = [[0, self::RED, 1], [0.5, self::RED, 1], [0.5, self::BLUE, 1], [1, self::BLUE, 1]];
        $pdf->gradient(2, [0, 0, 0.1, 0], $stops, [0, 0, 200, 200], null, "repeat");
        $output = $pdf->output(true);

        $this->assertSame(1, substr_count($output, "/FunctionType 2"));
        $this->assertStringContainsString("/C0 [0.5000 0.0000 0.5000]", $output);
    }
}
