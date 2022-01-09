<?php
namespace Dompdf\Tests\Css;

use Dompdf\Css\Color;
use Dompdf\Tests\TestCase;

class ColorTest extends TestCase
{
    public function validColorProvider(): array
    {
        return [
            // Color names
            ["red", [1, 0, 0, 1.0]],
            ["lime", [0, 1, 0, 1.0]],
            ["blue", [0, 0, 1, 1.0]],

            // Hex notation
            ["#f00", [1, 0, 0, 1.0]],
            ["#f003", [1, 0, 0, 0.2]],
            ["#ff0000", [1, 0, 0, 1.0]],
            ["#ff000033", [1, 0, 0, 0.2]],
            ["#FFFFFF00", [1, 1, 1, 0.0]],

            // Functional rgb syntax (space-separated)
            ["rgb(255 0 0)", [1, 0, 0, 1.0]],
            ["rgb(255 0 0/0.2)", [1, 0, 0, 0.2]],
            ["rgb( 255 0 0 / 0.2 )", [1, 0, 0, 0.2]],
            ["rgb(100% 0% 0% / 20%)", [1, 0, 0, 0.2]],
            ["rgba(255 0 0)", [1, 0, 0, 1.0]],
            ["rgba(255 0 0/0.2)", [1, 0, 0, 0.2]],

            // Functional rgb syntax (comma-separated)
            ["rgb(255, 0, 0)", [1, 0, 0, 1.0]],
            ["rgb(255, 0, 0, 0.2)", [1, 0, 0, 0.2]],
            ["rgb( 255,0,0,0.2 )", [1, 0, 0, 0.2]],
            ["rgb(100%, 0%, 0%, 20%)", [1, 0, 0, 0.2]],
            ["rgba(255, 0, 0)", [1, 0, 0, 1.0]],
            ["rgba(255, 0, 0, 0.2)", [1, 0, 0, 0.2]],
        ];
    }

    /**
     * @dataProvider validColorProvider
     */
    public function testParseColor(string $value, array $expected): void
    {
        $color = Color::parse($value);

        if (!is_array($color)) {
            $this->fail("Failed to parse valid color declaration");
        }

        [$r, $g, $b] = $color;
        $alpha = $color["alpha"];

        $this->assertEquals($expected, [$r, $g, $b, $alpha]);
    }
}
