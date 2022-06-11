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
            // Exponential notation
            ["1e2% 50e-1pt 2.5e+1%", "1e2%", "50e-1pt", "2.5e+1%", "50e-1pt"]
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

        $this->assertSame($top, $style->get_specified("top"));
        $this->assertSame($right, $style->get_specified("right"));
        $this->assertSame($bottom, $style->get_specified("bottom"));
        $this->assertSame($left, $style->get_specified("left"));
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

        $this->assertSame($top, $style->get_specified("margin_top"));
        $this->assertSame($right, $style->get_specified("margin_right"));
        $this->assertSame($bottom, $style->get_specified("margin_bottom"));
        $this->assertSame($left, $style->get_specified("margin_left"));
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

        $this->assertSame($top, $style->get_specified("padding_top"));
        $this->assertSame($right, $style->get_specified("padding_right"));
        $this->assertSame($bottom, $style->get_specified("padding_bottom"));
        $this->assertSame($left, $style->get_specified("padding_left"));
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

        $this->assertSame($top, $style->get_specified("border_top_{$type}"));
        $this->assertSame($right, $style->get_specified("border_right_{$type}"));
        $this->assertSame($bottom, $style->get_specified("border_bottom_{$type}"));
        $this->assertSame($left, $style->get_specified("border_left_{$type}"));
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
            $this->assertSame($expectedWidth, $style->get_specified("border_{$side}_width"));
            $this->assertSame($expectedStyle, $style->get_specified("border_{$side}_style"));
            $this->assertSame($expectedColor, $style->get_specified("border_{$side}_color"));
        }
    }

    public function outlineShorthandProvider(): array
    {
        return [
            ["transparent", "medium", "none", "transparent"],
            ["currentcolor 1pc", "1pc", "none", "currentcolor"],
            ["thick inset", "thick", "inset", "currentcolor"],
            ["auto 5pt", "5pt", "auto", "currentcolor"],
            ["1pt solid red", "1pt", "solid", "red"],
            ["rgb(0, 0, 0) double 1rem", "1rem", "double", "rgb(0, 0, 0)"],
            ["thin rgb(0 255 0 / 0.2) auto", "thin", "auto", "rgb(0 255 0 / 0.2)"]
        ];
    }

    /**
     * @dataProvider outlineShorthandProvider
     */
    public function testOutlineShorthand(
        string $value,
        string $expectedWidth,
        string $expectedStyle,
        string $expectedColor
    ): void {
        $style = $this->style();
        $style->set_prop("outline", $value);

        $this->assertSame($expectedWidth, $style->get_specified("outline_width"));
        $this->assertSame($expectedStyle, $style->get_specified("outline_style"));
        $this->assertSame($expectedColor, $style->get_specified("outline_color"));
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

        $this->assertSame($tl, $style->get_specified("border_top_left_radius"));
        $this->assertSame($tr, $style->get_specified("border_top_right_radius"));
        $this->assertSame($br, $style->get_specified("border_bottom_right_radius"));
        $this->assertSame($bl, $style->get_specified("border_bottom_left_radius"));
    }

    public function backgroundShorthandProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        $imagePath = "$basePath/_files/jamaica.jpg";

        return [
            ["none", "none"],
            ["url($imagePath)", "url($imagePath)"],
            ["url( \"$imagePath\" )", "url( \"$imagePath\" )"],
            ["rgba( 5, 5, 5, 1 )", "none", ["0%", "0%"], ["auto", "auto"], "repeat", "scroll", "rgba( 5, 5, 5, 1 )"],
            ["url(non-existing.png) top center no-repeat red fixed", "url(non-existing.png)", "top center", ["auto", "auto"], "no-repeat", "fixed", "red"],
            ["url($imagePath) left/200pt 30% rgb( 123 16 69/0.8 )no-repeat", "url($imagePath)", "left", "200pt 30%", "no-repeat", "scroll", "rgb( 123 16 69/0.8 )"]
        ];
    }

    /**
     * @dataProvider backgroundShorthandProvider
     */
    public function testBackgroundShorthand(
        string $value,
        string $image,
        $position = ["0%", "0%"],
        $size = ["auto", "auto"],
        string $repeat = "repeat",
        string $attachment = "scroll",
        string $color = "transparent"
    ): void {
        $style = $this->style();
        $style->set_prop("background", $value);

        $this->assertSame($image, $style->get_specified("background_image"));
        $this->assertSame($position, $style->get_specified("background_position"));
        $this->assertSame($size, $style->get_specified("background_size"));
        $this->assertSame($repeat, $style->get_specified("background_repeat"));
        $this->assertSame($attachment, $style->get_specified("background_attachment"));
        $this->assertSame($color, $style->get_specified("background_color"));
    }

    public function fontShorthandProvider(): array
    {
        return [
            ["8.5mm Helvetica", "normal", "normal", "normal", "8.5mm", "normal", "helvetica"],
            ["bold 16pt/10pt serif", "normal", "normal", "bold", "16pt", "10pt", "serif"],
            ["italic 700\n\t15.5pt / 2.1 'Courier', sans-serif", "italic", "normal", "700", "15.5pt", "2.1", "'courier',sans-serif"],
            ["700   normal  ITALIC    15.5PT /2.1 'Courier',sans-serif", "italic", "normal", "700", "15.5pt", "2.1", "'courier',sans-serif"],
            ["normal normal small-caps 100.01% serif, sans-serif", "normal", "small-caps", "normal", "100.01%", "normal", "serif,sans-serif"],
            ["normal normal normal xx-small/normal monospace", "normal", "normal", "normal", "xx-small", "normal", "monospace"]
        ];
    }

    /**
     * @dataProvider fontShorthandProvider
     */
    public function testFontShorthand(
        string $value,
        string $fontStyle,
        string $fontVariant,
        string $fontWeight,
        string $fontSize,
        string $lineHeight,
        string $fontFamily
    ): void {
        $style = $this->style();
        $style->set_prop("font", $value);

        $this->assertSame($fontStyle, $style->get_specified("font_style"));
        $this->assertSame($fontVariant, $style->get_specified("font_variant"));
        $this->assertSame($fontWeight, $style->get_specified("font_weight"));
        $this->assertSame($fontSize, $style->get_specified("font_size"));
        $this->assertSame($lineHeight, $style->get_specified("line_height"));
        $this->assertSame($fontFamily, $style->get_specified("font_family"));
    }

    public function listStyleShorthandProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        $imagePath = "$basePath/_files/jamaica.jpg";

        return [
            ["none", "none", "none"],
            ["url($imagePath)", "disc", "url($imagePath)"],
            ["url( '$imagePath' ) outside", "disc", "url( '$imagePath' )", "outside"],
            ["inside url($imagePath) square", "square", "url($imagePath)", "inside"],
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

        $this->assertSame($type, $style->get_specified("list_style_type"));
        $this->assertSame($image, $style->get_specified("list_style_image"));
        $this->assertSame($position, $style->get_specified("list_style_position"));
    }
}
