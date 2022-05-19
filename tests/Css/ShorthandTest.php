<?php
namespace Dompdf\Tests\Css;

use Dompdf\Dompdf;
use Dompdf\Css\Style;
use Dompdf\Css\Stylesheet;
use Dompdf\Tests\TestCase;

class ShorthandTest extends TestCase
{
    protected function style(): Style
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $sheet->set_base_path(__DIR__); // Treat stylesheet as being located in this directory

        return new Style($sheet);
    }

    public function marginPaddingShorthandProvider(): array
    {
        return [
            ["5pt", "5pt", "5pt", "5pt", "5pt"],
            ["1rem 2rem", "1rem", "2rem", "1rem", "2rem"],
            ["10% 5pt 25%", "10%", "5pt", "25%", "5pt"],
            ["5mm 4mm 3mm 2mm", "5mm", "4mm", "3mm", "2mm"],
        ];
    }

    /**
     * @dataProvider marginPaddingShorthandProvider
     */
    public function testInsetShorthand(
        string $value,
        string $top,
        string $right,
        string $bottom,
        string $left
    ): void {
        $style = $this->style();
        $style->set_prop("inset", $value);

        $this->assertSame($top, $style->get_prop("top"));
        $this->assertSame($right, $style->get_prop("right"));
        $this->assertSame($bottom, $style->get_prop("bottom"));
        $this->assertSame($left, $style->get_prop("left"));
    }

    /**
     * @dataProvider marginPaddingShorthandProvider
     */
    public function testMarginShorthand(
        string $value,
        string $top,
        string $right,
        string $bottom,
        string $left
    ): void {
        $style = $this->style();
        $style->set_prop("margin", $value);

        $this->assertSame($top, $style->get_prop("margin_top"));
        $this->assertSame($right, $style->get_prop("margin_right"));
        $this->assertSame($bottom, $style->get_prop("margin_bottom"));
        $this->assertSame($left, $style->get_prop("margin_left"));
    }

    /**
     * @dataProvider marginPaddingShorthandProvider
     */
    public function testPaddingShorthand(
        string $value,
        string $top,
        string $right,
        string $bottom,
        string $left
    ): void {
        $style = $this->style();
        $style->set_prop("padding", $value);

        $this->assertSame($top, $style->get_prop("padding_top"));
        $this->assertSame($right, $style->get_prop("padding_right"));
        $this->assertSame($bottom, $style->get_prop("padding_bottom"));
        $this->assertSame($left, $style->get_prop("padding_left"));
    }

    protected function borderTypeShorthandTest(
        string $type,
        string $value,
        string $top,
        string $right,
        string $bottom,
        string $left
    ): void {
        $style = $this->style();
        $style->set_prop("border_{$type}", $value);

        $this->assertSame($top, $style->get_prop("border_top_{$type}"));
        $this->assertSame($right, $style->get_prop("border_right_{$type}"));
        $this->assertSame($bottom, $style->get_prop("border_bottom_{$type}"));
        $this->assertSame($left, $style->get_prop("border_left_{$type}"));
    }

    public function borderWidthShorthandProvider(): array
    {
        return [
            ["thin", "thin", "thin", "thin", "thin"],
            ["medium 1.2rem", "medium", "1.2rem", "medium", "1.2rem"],
            ["thick 5pt 12pc", "thick", "5pt", "12pc", "5pt"],
            ["5mm 4mm 3mm 2mm", "5mm", "4mm", "3mm", "2mm"],
        ];
    }

    /**
     * @dataProvider borderWidthShorthandProvider
     */
    public function testBorderWidthShorthand(
        string $value,
        string $top,
        string $right,
        string $bottom,
        string $left
    ): void {
        $this->borderTypeShorthandTest("width", $value, $top, $right, $bottom, $left);
    }

    public function borderStyleShorthandProvider(): array
    {
        return [
            ["solid", "solid", "solid", "solid", "solid"],
            ["none double", "none", "double", "none", "double"],
            ["inset outset groove", "inset", "outset", "groove", "outset"],
            ["solid double none hidden", "solid", "double", "none", "hidden"],
        ];
    }

    /**
     * @dataProvider borderStyleShorthandProvider
     */
    public function testBorderStyleShorthand(
        string $value,
        string $top,
        string $right,
        string $bottom,
        string $left
    ): void {
        $this->borderTypeShorthandTest("style", $value, $top, $right, $bottom, $left);
    }

    public function borderColorShorthandProvider(): array
    {
        return [
            ["transparent", "transparent", "transparent", "transparent", "transparent"],
            ["#000 #fff", "#000", "#fff", "#000", "#fff"],
            ["red blue green", "red", "blue", "green", "blue"],
            ["rgb(0 0 0) rgb(50%, 50%, 50%) currentcolor rgb(255 0 0 / 0.5)", "rgb(0 0 0)", "rgb(50%, 50%, 50%)", "currentcolor", "rgb(255 0 0 / 0.5)"],
        ];
    }

    /**
     * @dataProvider borderColorShorthandProvider
     */
    public function testBorderColorShorthand(
        string $value,
        string $top,
        string $right,
        string $bottom,
        string $left
    ): void {
        $this->borderTypeShorthandTest("color", $value, $top, $right, $bottom, $left);
    }

    public function borderShorthandProvider(): array
    {
        return [
            ["transparent", "medium", "none", "transparent"],
            ["currentcolor 1pc", "1pc", "none", "currentcolor"],
            ["thick inset", "thick", "inset", "currentcolor"],
            ["solid 5pt", "5pt", "solid", "currentcolor"],
            ["1pt solid red", "1pt", "solid", "red"],
            ["rgb(0, 0, 0) double 1rem", "1rem", "double", "rgb(0, 0, 0)"],
            ["thin rgb(0 255 0 / 0.2) solid", "thin", "solid", "rgb(0 255 0 / 0.2)"]
        ];
    }

    /**
     * @dataProvider borderShorthandProvider
     */
    public function testBorderShorthand(
        string $value,
        string $expectedWidth,
        string $expectedStyle,
        string $expectedColor
    ): void {
        $style = $this->style();
        $style->set_prop("border", $value);

        $sides = ["top", "right", "bottom", "left"];

        foreach ($sides as $side) {
            $this->assertSame($expectedWidth, $style->get_prop("border_{$side}_width"));
            $this->assertSame($expectedStyle, $style->get_prop("border_{$side}_style"));
            $this->assertSame($expectedColor, $style->get_prop("border_{$side}_color"));
        }
    }

    /**
     * @dataProvider borderShorthandProvider
     */
    public function testOutlineShorthand(
        string $value,
        string $expectedWidth,
        string $expectedStyle,
        string $expectedColor
    ): void {
        $style = $this->style();
        $style->set_prop("outline", $value);

        $this->assertSame($expectedWidth, $style->get_prop("outline_width"));
        $this->assertSame($expectedStyle, $style->get_prop("outline_style"));
        $this->assertSame($expectedColor, $style->get_prop("outline_color"));
    }

    public function borderRadiusShorthandProvider(): array
    {
        return [
            ["5pt", "5pt", "5pt", "5pt", "5pt"],
            ["1rem 2rem", "1rem", "2rem", "1rem", "2rem"],
            ["10% 5pt 15%", "10%", "5pt", "15%", "5pt"],
            ["5mm 4mm 3mm 2mm", "5mm", "4mm", "3mm", "2mm"],
        ];
    }

    /**
     * @dataProvider borderRadiusShorthandProvider
     */
    public function testBorderRadiusShorthand(
        string $value,
        string $tl,
        string $tr,
        string $br,
        string $bl
    ): void {
        $style = $this->style();
        $style->set_prop("border_radius", $value);

        $this->assertSame($tl, $style->get_prop("border_top_left_radius"));
        $this->assertSame($tr, $style->get_prop("border_top_right_radius"));
        $this->assertSame($br, $style->get_prop("border_bottom_right_radius"));
        $this->assertSame($bl, $style->get_prop("border_bottom_left_radius"));
    }

    public function backgroundShorthandProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        $imagePath = "$basePath/_files/jamaica.jpg";
        $expectedPath = $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg";

        return [
            ["none", "none"],
            ["url($imagePath)", $expectedPath],
            ["url( \"$imagePath\" )", $expectedPath],
            ["rgba( 5, 5, 5, 1 )", "none", "0% 0%", "auto auto", "repeat", "scroll", "rgba( 5, 5, 5, 1 )"],
            ["url(non-existing.png) top center no-repeat red fixed", "none", "top center", "auto auto", "no-repeat", "fixed", "red"],
            ["url($imagePath) left/200pt 30% rgb( 123 16 69/0.8 )no-repeat", $expectedPath, "left", "200pt 30%", "no-repeat", "scroll", "rgb( 123 16 69/0.8 )"]
        ];
    }

    /**
     * @dataProvider backgroundShorthandProvider
     */
    public function testBackgroundShorthand(
        string $value,
        string $image,
        string $position = "0% 0%",
        string $size = "auto auto",
        string $repeat = "repeat",
        string $attachment = "scroll",
        string $color = "transparent"
    ): void {
        $style = $this->style();
        $style->set_prop("background", $value);

        $this->assertSame($image, $style->get_prop("background_image"));
        $this->assertSame($position, $style->get_prop("background_position"));
        $this->assertSame($size, $style->get_prop("background_size"));
        $this->assertSame($repeat, $style->get_prop("background_repeat"));
        $this->assertSame($attachment, $style->get_prop("background_attachment"));
        $this->assertSame($color, $style->get_prop("background_color"));
    }

    public function listStyleShorthandProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        $imagePath = "$basePath/_files/jamaica.jpg";
        $expectedPath = $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg";

        return [
            ["none", "none", "none"],
            ["url($imagePath)", "disc", $expectedPath],
            ["url( '$imagePath' ) outside", "disc", $expectedPath, "outside"],
            ["inside url($imagePath) square", "square", $expectedPath, "inside"],
            ["inside decimal", "decimal", "none", "inside"]
        ];
    }

    /**
     * @dataProvider listStyleShorthandProvider
     */
    public function testListStyleShorthand(
        string $value,
        string $type,
        string $image = "none",
        string $position = "outside"
    ): void {
        $style = $this->style();
        $style->set_prop("list_style", $value);

        $this->assertSame($type, $style->get_prop("list_style_type"));
        $this->assertSame($image, $style->get_prop("list_style_image"));
        $this->assertSame($position, $style->get_prop("list_style_position"));
    }
}
