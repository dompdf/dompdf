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

    public static function marginPaddingShorthandProvider(): array
    {
        return [
            ["5pt", "5pt", "5pt", "5pt", "5pt"],
            ["1rem 2rem", "1rem", "2rem", "1rem", "2rem"],
            ["10% 5pt 25%", "10%", "5pt", "25%", "5pt"],
            ["5mm 4mm 3mm 2mm", "5mm", "4mm", "3mm", "2mm"],
            // Exponential notation
            ["1e2% 50e-1pt 2.5e+1%", "1e2%", "50e-1pt", "2.5e+1%", "50e-1pt"],

            // Calc
            ["calc(50% - 10pt) 1%", "calc(50% - 10pt)", "1%", "calc(50% - 10pt)", "1%"],
            ["calc( (5 * 1pt) + 0pt ) 5pt CALC((0pt + 5pt))5pt", "calc( (5 * 1pt) + 0pt )", "5pt", "CALC((0pt + 5pt))", "5pt"]
        ];
    }

    /**
     * @dataProvider marginPaddingShorthandProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('marginPaddingShorthandProvider')]
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
    #[\PHPUnit\Framework\Attributes\DataProvider('marginPaddingShorthandProvider')]
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
    #[\PHPUnit\Framework\Attributes\DataProvider('marginPaddingShorthandProvider')]
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

    public static function borderWidthShorthandProvider(): array
    {
        return [
            ["thin", "thin", "thin", "thin", "thin"],
            ["medium 1.2rem", "medium", "1.2rem", "medium", "1.2rem"],
            ["thick 5pt 12pc", "thick", "5pt", "12pc", "5pt"],
            ["5mm 4mm 3mm 2mm", "5mm", "4mm", "3mm", "2mm"],

            // Calc
            ["calc(1pc - 12pt)medium", "calc(1pc - 12pt)", "medium", "calc(1pc - 12pt)", "medium"]
        ];
    }

    /**
     * @dataProvider borderWidthShorthandProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('borderWidthShorthandProvider')]
    public function testBorderWidthShorthand(
        string $value,
        string $top,
        string $right,
        string $bottom,
        string $left
    ): void {
        $this->borderTypeShorthandTest("width", $value, $top, $right, $bottom, $left);
    }

    public static function borderStyleShorthandProvider(): array
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
    #[\PHPUnit\Framework\Attributes\DataProvider('borderStyleShorthandProvider')]
    public function testBorderStyleShorthand(
        string $value,
        string $top,
        string $right,
        string $bottom,
        string $left
    ): void {
        $this->borderTypeShorthandTest("style", $value, $top, $right, $bottom, $left);
    }

    public static function borderColorShorthandProvider(): array
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
    #[\PHPUnit\Framework\Attributes\DataProvider('borderColorShorthandProvider')]
    public function testBorderColorShorthand(
        string $value,
        string $top,
        string $right,
        string $bottom,
        string $left
    ): void {
        $this->borderTypeShorthandTest("color", $value, $top, $right, $bottom, $left);
    }

    public static function borderOutlineShorthandProvider(): array
    {
        return [
            ["transparent", "medium", "none", "transparent"],
            ["currentcolor 1pc", "1pc", "none", "currentcolor"],
            ["thick inset", "thick", "inset", "currentcolor"],
            ["solid 5pt", "5pt", "solid", "currentcolor"],
            ["1pt solid red", "1pt", "solid", "red"],
            ["rgb(0, 0, 0) double 1rem", "1rem", "double", "rgb(0, 0, 0)"],
            ["thin rgb(0 255 0 / 0.2) solid", "thin", "solid", "rgb(0 255 0 / 0.2)"],

            // Calc
            ["dotted calc((5pt + 1em)/2) #FF0000", "calc((5pt + 1em)/2)", "dotted", "#ff0000"],
            ["calc( 3pt - 1px ) outset", "calc( 3pt - 1px )", "outset", "currentcolor"],
        ];
    }

    public static function borderShorthandProvider(): array
    {
        return [
            ["blue 1mm hidden", "1mm", "hidden", "blue"]
        ];
    }

    public static function outlineShorthandProvider(): array
    {
        return [
            ["auto 5pt", "5pt", "auto", "currentcolor"],
            ["thin #000000 auto", "thin", "auto", "#000000"]
        ];
    }

    /**
     * @dataProvider borderOutlineShorthandProvider
     * @dataProvider borderShorthandProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('borderOutlineShorthandProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('borderShorthandProvider')]
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

    /**
     * @dataProvider borderOutlineShorthandProvider
     * @dataProvider outlineShorthandProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('borderOutlineShorthandProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('outlineShorthandProvider')]
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

    public static function borderRadiusShorthandProvider(): array
    {
        return [
            ["5pt", "5pt", "5pt", "5pt", "5pt"],
            ["1rem 2rem", "1rem", "2rem", "1rem", "2rem"],
            ["10% 5pt 15%", "10%", "5pt", "15%", "5pt"],
            ["5mm 4mm 3mm 2mm", "5mm", "4mm", "3mm", "2mm"],

            // Calc
            ["calc(50% - 10pt) 1%", "calc(50% - 10pt)", "1%", "calc(50% - 10pt)", "1%"],
        ];
    }

    /**
     * @dataProvider borderRadiusShorthandProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('borderRadiusShorthandProvider')]
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

    public static function backgroundShorthandProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        $imagePath = "$basePath/_files/jamaica.jpg";

        return [
            ["none", "none"],
            ["url($imagePath)", "url($imagePath)"],
            ["url( \"$imagePath\" )", "url( \"$imagePath\" )"],
            ["rgba( 5, 5, 5, 1 )", "none", [0.0, 0.0], ["auto", "auto"], "repeat", "scroll", "rgba( 5, 5, 5, 1 )"],
            ["url(non-existing.png) top center no-repeat red fixed", "url(non-existing.png)", "top center", ["auto", "auto"], "no-repeat", "fixed", "red"],
            ["url($imagePath) LEFT/200PT 30% RGB( 123 16 69/0.8 )no-REPEAT", "url($imagePath)", "left", "200pt 30%", "no-repeat", "scroll", "rgb( 123 16 69/0.8 )"],
            ["url($imagePath) 10pt 10pt/200PT 30%", "url($imagePath)", "10pt 10pt", "200pt 30%"],

            // Calc for position and size
            ["url($imagePath) calc(100% - 20pt)/ calc(10% + 20pt)CALC(100%/3)", "url($imagePath)", "calc(100% - 20pt)", "calc(10% + 20pt) calc(100%/3)"],
        ];
    }

    /**
     * @dataProvider backgroundShorthandProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('backgroundShorthandProvider')]
    public function testBackgroundShorthand(
        string $value,
        string $image,
        $position = [0.0, 0.0],
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

    public static function fontShorthandProvider(): array
    {
        return [
            ["8.5mm Helvetica", "normal", "normal", 400, "8.5mm", "normal", "helvetica"],
            ["bold 16pt/10pt serif", "normal", "normal", "bold", "16pt", "10pt", "serif"],
            ["italic 700\n\t15.5pt / 2.1 'Courier', sans-serif", "italic", "normal", "700", "15.5pt", "2.1", "'courier',sans-serif"],
            ["700   normal  ITALIC    15.5PT /2.1 'Courier',sans-serif", "italic", "normal", "700", "15.5pt", "2.1", "'courier',sans-serif"],
            ["normal normal small-caps 100.01% serif, sans-serif", "normal", "small-caps", 400, "100.01%", "normal", "serif,sans-serif"],
            ["normal normal normal xx-small/normal monospace", "normal", "normal", 400, "xx-small", "normal", "monospace"],
            ["1 0 serif", "normal", "normal", "1", "0", "normal", "serif"],

            // TODO: Calc for font size and line height
            // ["italic 700 calc(1rem + 0.5pt)/calc(10/3) sans-serif", "italic", "normal", "700", "calc(1rem + 0.5pt)", "calc(10/3)", "sans-serif"],
        ];
    }

    /**
     * @dataProvider fontShorthandProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('fontShorthandProvider')]
    public function testFontShorthand(
        string $value,
        string $fontStyle,
        string $fontVariant,
        $fontWeight,
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

    public static function listStyleShorthandProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        $imagePath = "$basePath/_files/jamaica.jpg";

        return [
            ["none", "none", "none"],
            ["NONE    None", "none", "none"],
            ["url($imagePath)", "disc", "url($imagePath)"],
            ["url($imagePath) none", "none", "url($imagePath)"],
            ["url( '$imagePath' ) outside", "disc", "url( '$imagePath' )", "outside"],
            ["inside url($imagePath) square", "square", "url($imagePath)", "inside"],
            ["inside decimal", "decimal", "none", "inside"],
            ["OUTSIDE    LOWER-GREEK", "LOWER-GREEK", "none", "outside"],

            // Invalid values
            ["inside none none none", "disc"]
        ];
    }

    /**
     * @dataProvider listStyleShorthandProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('listStyleShorthandProvider')]
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
