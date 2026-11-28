<?php
namespace Dompdf\Tests\Canvas;

use Dompdf\Adapter\GD;
use Dompdf\Dompdf;
use Dompdf\Tests\TestCase;

class GDLineTest extends TestCase
{
    public static function cappedLineProvider(): array
    {
        $cases = [];
        $directions = [
            'horizontal' => [[20, 50, 80, 50], [18, 50], [82, 50]],
            'vertical' => [[50, 20, 50, 80], [50, 18], [50, 82]],
            'diagonal' => [[20, 20, 80, 80], [18, 18], [82, 82]],
        ];
        foreach (['round', 'square'] as $cap) {
            foreach ([1, 4] as $aa) {
                foreach ($directions as $name => [$line, $start, $end]) {
                    $cases["$cap-$aa-$name-forward"] = [$cap, $aa, $line, $start, $end];
                    $reverse = [$line[2], $line[3], $line[0], $line[1]];
                    $cases["$cap-$aa-$name-reverse"] = [$cap, $aa, $reverse, $start, $end];
                }
            }
        }
        return $cases;
    }

    /**
     * @dataProvider cappedLineProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('cappedLineProvider')]
    public function testCapsExtendBothEnds(string $cap, float $aa, array $line, array $start, array $end): void
    {
        $dompdf = new Dompdf(['dpi' => 72]);
        $canvas = new GD([0, 0, 100, 100], 'portrait', $dompdf, $aa);
        $canvas->line($line[0], $line[1], $line[2], $line[3], [0, 0, 0], 10, [], $cap);
        $image = imagecreatefromstring($canvas->output(['type' => 'png']));
        $this->assertNotFalse($image);
        foreach ([$start, $end, [50, 50]] as [$x, $y]) {
            $color = imagecolorsforindex($image, imagecolorat($image, $x, $y));
            $this->assertLessThan(64, $color['red']);
        }
        $outside = imagecolorsforindex($image, imagecolorat($image, 95, 95));
        $this->assertGreaterThan(200, $outside['red']);
        imagedestroy($image);
    }

    public function testButtEndpointsAndDashPatternRemainUnchanged(): void
    {
        $canvas = new GD([0, 0, 100, 100], 'portrait', new Dompdf(['dpi' => 72]));
        $canvas->line(20, 50, 80, 50, [0, 0, 0], 4, [10, 10], 'butt');
        $image = imagecreatefromstring($canvas->output(['type' => 'png']));
        foreach ([25, 45, 65] as $x) {
            $color = imagecolorsforindex($image, imagecolorat($image, $x, 50));
            $this->assertLessThan(64, $color['red']);
        }
        foreach ([18, 35, 55, 75, 82] as $x) {
            $color = imagecolorsforindex($image, imagecolorat($image, $x, 50));
            $this->assertGreaterThan(200, $color['red']);
        }
        imagedestroy($image);
    }
}
