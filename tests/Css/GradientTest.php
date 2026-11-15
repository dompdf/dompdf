<?php
namespace Dompdf\Tests\Css;

use Dompdf\Css\Gradient;
use Dompdf\Css\Style;
use Dompdf\Css\Stylesheet;
use Dompdf\Dompdf;
use Dompdf\Tests\TestCase;

class GradientTest extends TestCase
{
    private const RED = [1, 0, 0];
    private const LIME = [0, 1, 0];
    private const BLUE = [0, 0, 1];
    private const PURPLE = [0.5, 0, 0.5];

    public static function validProvider(): array
    {
        return [
            ["linear-gradient(red, blue)"],
            ["linear-gradient(red)"],
            ["linear-gradient(+45deg, red, blue)"],
            ["radial-gradient(at 10px top, red, blue)"],
            ["radial-gradient(ellipse farthest-corner at right 10px bottom 20px, red, blue)"],
            ["LINEAR-GRADIENT(RED 0 50%, BLUE)"],
            ["linear-gradient(45deg, red 10%, blue)"],
            ["linear-gradient(0, red, blue)"],
            ["linear-gradient(to top left, red, 30%, blue)"],
            ["linear-gradient(red calc(10% + 5px), blue)"],
            ["linear-gradient(currentColor, transparent)"],
            ["linear-gradient(red, blue), url(image.png)"],
            ["radial-gradient(circle at 20% 30%, red, blue)"],
            ["repeating-radial-gradient(10px 20px, red, blue 5px)"],
        ];
    }

    /**
     * @dataProvider validProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('validProvider')]
    public function testParseValid(string $value): void
    {
        $this->assertNotNull(Gradient::parse($value));
    }

    public static function invalidProvider(): array
    {
        return [
            ["linear-gradient(red, blorg)"],
            ["linear-gradient(to left right, red, blue)"],
            ["linear-gradient(red, 50%)"],
            ["linear-gradient(50%, red)"],
            ["linear-gradient(red, 10%, 20%, blue)"],
            ["linear-gradient(red 50, blue)"],
            ["linear-gradient(red 1px 2px 3px, blue)"],
            ["linear-gradient(red,,blue)"],
            ["linear-gradient(red 10furlong, blue)"],
            ["radial-gradient(circle nonsense, red, blue)"],
            ["radial-gradient(circle -10px, red, blue)"],
            ["radial-gradient(circle at potato, red, blue)"],
            ["radial-gradient(circle 10px 20px, red, blue)"],
            ["radial-gradient(circle 10%, red, blue)"],
            ["radial-gradient(ellipse 10px, red, blue)"],
            ["radial-gradient(circle at left left, red, blue)"],
            ["radial-gradient(at top 10px, red, blue)"],
            ["radial-gradient(at right 10px bottom, red, blue)"],
            ["linear-gradient(red, blue"],
            ["linear-gradient(red, blue) red"],
            ["linear-gradient()"],
            ["conic-gradient(red, blue)"],
        ];
    }

    /**
     * @dataProvider invalidProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidProvider')]
    public function testParseInvalid(string $value): void
    {
        $this->assertNull(Gradient::parse($value));
    }

    public function testInvalidDeclarationKeepsValidOne(): void
    {
        $style = new Style(new Stylesheet(new Dompdf()));
        $style->set_prop("background_image", "linear-gradient(red, blue)");
        $style->set_prop("background_image", "linear-gradient(red, blorg)");

        $this->assertSame("linear-gradient(red, blue)", $style->background_image);
    }

    public static function resolveProvider(): array
    {
        $r = sqrt(50 ** 2 + 25 ** 2);

        return [
            "linear" => [
                "linear-gradient(to right, red, blue)",
                [2, [0, 25, 100, 25], [1, 0, 0, 1, 0, 0], [[0, self::RED, 1], [1, self::BLUE, 1]], false]
            ],
            "hint after decreasing position" => [
                // Fixup: blue moves to the hint at 80%, so red is kept up to there
                "linear-gradient(to right, red, 80%, blue 20%, lime)",
                [2, [0, 25, 100, 25], [1, 0, 0, 1, 0, 0], [
                    [0, self::RED, 1], [0.8, self::RED, 1], [0.8, self::BLUE, 1], [1, self::LIME, 1]
                ], false]
            ],
            "calc position" => [
                "linear-gradient(to right, red calc(10% + 4pt), blue)",
                [2, [0, 25, 100, 25], [1, 0, 0, 1, 0, 0], [[0.14, self::RED, 1], [1, self::BLUE, 1]], false]
            ],
            "transparent takes neighbor color" => [
                "linear-gradient(to right, red, transparent)",
                [2, [0, 25, 100, 25], [1, 0, 0, 1, 0, 0], [
                    [0, self::RED, 1], [1, self::RED, 0], [1, self::RED, 0]
                ], false]
            ],
            "negative radial stop" => [
                // Cpdf starts the gradient at radius 0, see CpdfTest::testGradientNegativeRadius()
                "radial-gradient(circle, red -100%, blue 100%)",
                [3, [0, 0, -1, 0, 0, 1], [$r, 0, 0, $r, 50, 25], [[0, self::RED, 1], [1, self::BLUE, 1]], false]
            ],
            "position with offsets" => [
                // Center 30pt from the right and 10pt from the bottom, radius to the farthest corner
                "radial-gradient(circle at right 30pt bottom 10pt, red, blue)",
                [3, [0, 0, 0, 0, 0, 1], [hypot(70, 40), 0, 0, hypot(70, 40), 70, 40], [[0, self::RED, 1], [1, self::BLUE, 1]], false]
            ],
            "position keywords in any order" => [
                "radial-gradient(10pt 20pt at bottom left, red, blue)",
                [3, [0, 0, 0, 0, 0, 1], [10, 0, 0, 20, 0, 50], [[0, self::RED, 1], [1, self::BLUE, 1]], false]
            ],
            "repeating" => [
                "repeating-linear-gradient(to right, red 10pt, blue 30pt)",
                [2, [10, 25, 30, 25], [1, 0, 0, 1, 0, 0], [[0, self::RED, 1], [1, self::BLUE, 1]], true]
            ],
            "repeating without length" => [
                // Average of the stops at an arbitrary distance
                "repeating-linear-gradient(to right, red 5pt, blue 5pt)",
                [2, [0, 25, 100, 25], [1, 0, 0, 1, 0, 0], [[0, self::PURPLE, 1]], false]
            ],
            "repeating too small to render" => [
                "repeating-linear-gradient(to right, red 0 .1pt, blue .1pt .2pt)",
                [2, [0, 25, 100, 25], [1, 0, 0, 1, 0, 0], [[0, self::PURPLE, 1]], false]
            ],
        ];
    }

    /**
     * @dataProvider resolveProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('resolveProvider')]
    public function testResolve(string $value, array $expected): void
    {
        $style = new Style(new Stylesheet(new Dompdf()));
        $actual = Gradient::parse($value)->resolve(0, 0, 100, 50, $style);

        $this->assertEqualsWithDelta($expected, $actual, 0.0001);
    }

    public function testResolveCurrentColor(): void
    {
        $style = new Style(new Stylesheet(new Dompdf()));
        $style->set_prop("color", "#00ff00");
        [, , , $stops] = Gradient::parse("linear-gradient(currentcolor, blue)")->resolve(0, 0, 100, 50, $style);

        $this->assertEqualsWithDelta([0, self::LIME, 1], $stops[0], 0.0001);
    }

    public function testResolvePremultipliedAlpha(): void
    {
        $style = new Style(new Stylesheet(new Dompdf()));
        [, , , $stops] = Gradient::parse("linear-gradient(red, rgb(0 0 255 / 0.2))")->resolve(0, 0, 100, 50, $style);

        // Halfway, the alpha is 0.6 and red dominates: (1 * 1 * 0.5) / 0.6
        $this->assertEqualsWithDelta([0.5, [5 / 6, 0, 1 / 6], 0.6], $stops[4], 0.0001);
    }
}
