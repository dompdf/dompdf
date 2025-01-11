<?php
namespace Dompdf\Tests\Css;

use Dompdf\Css\Content\Attr;
use Dompdf\Css\Content\CloseQuote;
use Dompdf\Css\Content\ContentPart;
use Dompdf\Css\Content\Counter;
use Dompdf\Css\Content\Counters;
use Dompdf\Css\Content\NoCloseQuote;
use Dompdf\Css\Content\NoOpenQuote;
use Dompdf\Css\Content\OpenQuote;
use Dompdf\Css\Content\StringPart;
use Dompdf\Css\Content\Url;
use Dompdf\Dompdf;
use Dompdf\Css\Style;
use Dompdf\Css\Stylesheet;
use Dompdf\Frame;
use Dompdf\Options;
use Dompdf\Tests\TestCase;

class StyleTest extends TestCase
{
    public function testInitial(): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("width", "100pt");
        $this->assertSame(100.0, $style->width);

        $style->set_prop("width", "initial");
        $this->assertSame("auto", $style->width);
    }

    public function testUnsetNonInherited(): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $s1 = new Style($sheet);
        $s2 = new Style($sheet);

        $s1->set_prop("width", "100pt");
        $s2->set_prop("width", "200pt");
        $this->assertSame(100.0, $s1->width);
        $this->assertSame(200.0, $s2->width);

        $s1->set_prop("width", "unset");
        $s1->inherit($s2);
        $this->assertSame("auto", $s1->width);
    }

    public function testUnsetInherited(): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $s1 = new Style($sheet);
        $s2 = new Style($sheet);

        $s1->set_prop("orphans", "4");
        $s2->set_prop("orphans", "6");
        $this->assertSame(4, $s1->orphans);
        $this->assertSame(6, $s2->orphans);

        $s1->set_prop("orphans", "unset");
        $s1->inherit($s2);
        $this->assertSame(6, $s1->orphans);
    }

    public static function lengthInPtProvider(): array
    {
        return [
            ["auto", null, "auto"],
            ["none", null, "none"],
            ["100px", null, 75.0],
            ["100PX", null, 75.0], // Also check caps
            ["100pt", null, 100.0],
            ["1.5e2pt", null, 150.0], // Exponential notation
            ["1.5e+2pt", null, 150.0],
            ["15E-2pT", null, 0.15],
            ["1.5em", 20, 18.0], // Default font size is 12pt
            ["100%", null, 12.0],
            ["50%", 360, 180.0],

            // Basic Arithmetic
            ["calc(100%)", null, 12.0],
            ["calc(50% - 1pt)", 200, 99.0],
            ["calc(100)", null, 100.0],
            ["calc(100% / 3)", 100, 33.3333, 4],
            ["calc(  100pt    +   50pt  )", null, 150.0],  // extra whitespace
            ["calc( (100pt + 50pt) / 3)", null, 50.0],     // parentheses
            ["calc(50pt*2)", null, 100.0],                 // * do not require whitespace
            ["calc(50%/2)", 120, 30.0],                    // / do not require whitespace
            ["calc(10pt + -50%)", 12, 4.0],                // negative value
            ["CalC(10)", null, 10.0],                      // case-insensitive

            ["calc()", null, 0.0],                         // invalid - empty
            ["calc(invalid)", 100, 0.0],                   // invalid
            ["calc(5pt - x)", 100, 0.0],                   // invalid
            ["calc((50% + 10) 1pt)", 100, 0.0],            // invalid - missing op
            ["calc(50% -1pt)", 100, 0.0],                  // invalid - missing op
            ["calc((50% + 10) + 2pt))", 100, 0.0],         // invalid - extra bracket
            ["calc(100pt / 0)", null, 0.0],                // invalid - division by zero

            // Comparison Functions
            ["min(-20, 5 * 2, 8)", null, -20.0],           // min function
            ["max(-20, 5 * 2, 8)", null, 10.0],            // max function
            ["clamp(10, 15 - 7, 20)", null, 10.0],         // clamp min function
            ["clamp(10, 15 + 7, 20)", null, 20.0],         // clamp max function
            ["clamp(10, 7 * 2, 20)", null, 14.0],          // clamp val function
            ["clamp(20, 5, 10)", null, 20.0],              // clamp min > max
            ["clamp(20, 15, 10)", null, 20.0],             // clamp min > max
            ["clamp(20, 25, 10)", null, 20.0],             // clamp min > max

            // Stepped Value Functions
            ["round(up, 100%, 10%)", 100, 0.0],            // Not supported
            ["round(30%, 0%)", 100, 0.0],
            ["round(4%, 9%)", 100, 0.0],
            ["round(6%, 9%)", 100, 9.0],
            ["round(13.5%, 9%)", 100, 18.0],               // Default when exactly between (nearest)
            ["round(15%, 9)", 100, 18.0],
            ["round(5.4, 1)", null, 5.0],
            ["round(5.5, 1)", null, 6.0],                  // Default when exactly between (nearest)
            ["round(5.6, 1)", null, 6.0],
            ["round(-5.4, 1)", null, -5.0],
            ["round(-5.5, 1)", null, -5.0],                // Default when exactly between (nearest)
            ["round(-5.6, 1)", null, -6.0],
            ["round(-5.5, -1)", null, -5.0],               // Default when exactly between (nearest)
            ["round(5.5, -1)", null, 6.0],                 // Default when exactly between (nearest)
            ["round(0.54, 0.1)", null, 0.5, 4],
            ["round(0.56, 0.1)", null, 0.6, 4],
            ["mod(30, 0)", null, 0.0],
            ["mod(18, 5)", null, 3.0],
            ["mod(-18, 5)", null, 2.0],
            ["mod(18, -5)", null, -2.0],
            ["mod(-18, -5)", null, -3.0],
            ["rem(30, 0)", null, 0.0],
            ["rem(18, 5)", null, 3.0],
            ["rem(-18, 5)", null, -3.0],
            ["rem(18, -5)", null, 3.0],
            ["rem(-18, -5)", null, -3.0],

            // Trigonometric Functions
            ["sin(0)", null, 0.0],                         // sin function
            ["sin(1)", null, 0.8415, 4],                   // sin function
            ["cos(0)", null, 1.0],                         // cos function
            ["cos(1)", null, 0.5403, 4],                   // cos function
            ["tan(0)", null, 0.0],                         // tan function
            ["tan(1)", null, 1.5574, 4],                   // tan function
            ["asin(0)", null, 0.0],                        // asin function
            ["asin(-0.2)", null, -0.2014, 4],              // asin function
            ["acos(1)", null, 0.0],                        // acos function
            ["acos(-0.2)", null, 1.7722, 4],               // acos function
            ["atan(0)", null, 0.0],                        // atan function
            ["atan(1)", null, 0.7854, 4],                  // atan function
            ["atan2(0, 0)", null, 0.0],                    // atan2 function
            ["atan2(3, 2)", null, 0.9828, 4],              // atan2 function

            // Exponential Functions
            ["pow(5, 2)", null, 25.0],                     // pow function
            ["sqrt(25)", null, 5.0],                       // sqrt function
            ["hypot(3,4)", null, 5.0],                     // hypot function
            ["log(1)", null, 0.0],                         // log function
            ["log(10)", null, 2.3026, 4],                  // log function
            ["log(8, 2)", null, 3.0],                      // log function
            ["log(625, 5)", null, 4.0],                    // log function
            ["exp(0)", null, 1.0],                         // exp function

            // Sign-Related Functions
            ["abs(-20)", null, 20.0],                      // abs function
            ["sign(-20)", null, -1.0],                     // sign function
            ["sign(5)", null, 1.0],                        // sign function
            ["sign(0)", null, 0.0],
            ["sign(100%)", 100.0, 1.0],
            ["sign(100%)", -100.0, -1.0],
            ["sign(-100%)", -100.0, 1.0],

            // Complex
            ["calc(max(3 + abs(-20), 5 * 2, 8 + 5) + 7)", null, 30.0],
            ["calc(min(5pt, 3rem) + 2pt)", null, 7.0],

            ["unknownFunc()", null, 0.0],                  // Unsupported func
            ["calc(1 + unknownFunc(2, 3))", null, 0.0]     // Unsupported func
        ];
    }

    /**
     * @dataProvider lengthInPtProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('lengthInPtProvider')]
    public function testLengthInPt(string $length, ?float $ref_size, $expected, ?int $precision = null): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $s = new Style($sheet);

        $result = $s->length_in_pt($length, $ref_size);
        if ($precision !== null) {
            $result = round($result, $precision);
        }

        $this->assertSame($expected, $result);
    }

    public static function widthProvider(): array
    {
        return [
            [[ "width" => "100pt" ], 1000.0, 100.0],
            [[ "width" => "calc(100% + 100%)", "font-size: 12pt;" ], 1000.0, 2000.0],
            [[ "width" => "calc(100% + var(--expand-by))", "--expand-by" => "100pt"], 1000.0, 1100.0],
            [[ "width" => "calc(100% + var(--invalid))"], 1000.0, "auto"]
        ];
    }
    
    /**
     * @dataProvider widthProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('widthProvider')]
    public function testSetWidth(array $properties, ?float $ref_size, $expected, ?int $precision = null): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $s = new Style($sheet);

        foreach ($properties as $prop => $value) {
            $s->set_prop($prop, $value);
        }
        $result = $s->length_in_pt($s->width, $ref_size);
        if ($precision !== null) {
            $result = round($result, $precision);
        }

        $this->assertSame($expected, $result);
    }

    public static function cssImageBasicProvider(): array
    {
        return [
            "no value" => ["", "none"],
            "keyword none" => ["none", "none"],
            "bare url" => ["http://example.com/test.png", "none"],
            "http" => ["url(http://example.com/test.png)", "http://example.com/test.png"],
            "case" => ["URL(http://example.com/Test.png)", "http://example.com/Test.png"],
            "quoted parens" => ["url(\"http://example.com/Test(1).png\")", "http://example.com/Test(1).png"],
            "escaped parens" => ["url(http://example.com/Test\(1\).png)", "http://example.com/Test(1).png"],
            "quotes" => ["url(http://example.com/Test\"1\".png)", "http://example.com/Test\"1\".png"],
            "escaped quotes" => ["url(\"http://example.com/Test\\\"1\\\".png\")", "http://example.com/Test\"1\".png"]
        ];
    }

    public static function cssImageNoBaseHrefProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        return [
            "local absolute" => ["url($basePath/_files/jamaica.jpg)", "file://" . $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"],
            "local relative" => ["url(../_files/jamaica.jpg)", "file://" . $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"]
        ];
    }

    public static function cssImageWithBaseHrefProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        return [
            "local absolute" => ["url($basePath/_files/jamaica.jpg)", "file://" . $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"],
            "local relative" => ["url(../_files/jamaica.jpg)", "file://" . $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"]
        ];
    }

    public static function cssImageWithStylesheetBaseHrefProvider(): array
    {
        return [
            "local absolute" => ["url(/_files/jamaica.jpg)", "https://example.com/_files/jamaica.jpg"],
            "local relative" => ["url(../_files/jamaica.jpg)", "https://example.com/../_files/jamaica.jpg"]
        ];
    }

    /**
     * @dataProvider cssImageBasicProvider
     * @dataProvider cssImageNoBaseHrefProvider
     * @group regression
     */
    #[\PHPUnit\Framework\Attributes\Group('regression')]
    #[\PHPUnit\Framework\Attributes\DataProvider('cssImageBasicProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('cssImageNoBaseHrefProvider')]
    public function testCssImageNoBaseHref(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $sheet->set_base_path(__DIR__); // Treat stylesheet as being located in this directory
        $s = new Style($sheet);

        $s->set_prop("background_image", $value);
        $this->assertSame($expected, $s->background_image);
    }

    /**
     * @dataProvider cssImageBasicProvider
     * @dataProvider cssImageWithBaseHrefProvider
     * @group regression
     */
    #[\PHPUnit\Framework\Attributes\Group('regression')]
    #[\PHPUnit\Framework\Attributes\DataProvider('cssImageBasicProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('cssImageWithBaseHrefProvider')]
    public function testCssImageWithBaseHref(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $dompdf->setProtocol("https://");
        $dompdf->setBaseHost("example.com");
        $dompdf->setBasePath("/");
        $sheet = new Stylesheet($dompdf);
        $sheet->set_base_path(__DIR__); // Treat stylesheet as being located in this directory
        $s = new Style($sheet);

        $s->set_prop("background_image", $value);
        $this->assertSame($expected, $s->background_image);
    }

    /**
     * @dataProvider cssImageBasicProvider
     * @dataProvider cssImageWithStylesheetBaseHrefProvider
     * @group regression
     */
    #[\PHPUnit\Framework\Attributes\Group('regression')]
    #[\PHPUnit\Framework\Attributes\DataProvider('cssImageBasicProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('cssImageWithStylesheetBaseHrefProvider')]
    public function testCssImageWithStylesheetBaseHref(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $sheet->set_protocol("https://");
        $sheet->set_host("example.com");
        $sheet->set_base_path("/");
        $s = new Style($sheet);

        $s->set_prop("background_image", $value);
        $this->assertSame($expected, $s->background_image);
    }

    public static function backgroundPositionProvider(): array
    {
        return [
            // One value
            ["left", [0.0, "50%"]],
            ["right", ["100%", "50%"]],
            ["top", ["50%", 0.0]],
            ["bottom", ["50%", "100%"]],
            ["center", ["50%", "50%"]],
            ["20pt", [20.0, "50%"]],
            ["-10pt", [-10.0, "50%"]],
            ["23%", ["23%", "50%"]],
            ["-75%", ["-75%", "50%"]],

            // Two values
            ["left top", [0.0, 0.0]],
            ["top left", [0.0, 0.0]],
            ["left bottom", [0.0, "100%"]],
            ["bottom left", [0.0, "100%"]],
            ["left center", [0.0, "50%"]],
            ["center left", [0.0, "50%"]],
            ["right top", ["100%", 0.0]],
            ["top right", ["100%", 0.0]],
            ["right bottom", ["100%", "100%"]],
            ["bottom right", ["100%", "100%"]],
            ["right center", ["100%", "50%"]],
            ["center right", ["100%", "50%"]],
            ["bottom center", ["50%", "100%"]],
            ["center bottom", ["50%", "100%"]],
            ["top center", ["50%", 0.0]],
            ["center top", ["50%", 0.0]],
            ["center center", ["50%", "50%"]],
            ["left 23%", [0.0, "23%"]],
            ["right 23%", ["100%", "23%"]],
            ["center 23%", ["50%", "23%"]],
            ["23% top", ["23%", 0.0]],
            ["23% bottom", ["23%", "100%"]],
            ["23% center", ["23%", "50%"]],
            ["23% 50pt", ["23%", 50.0]],
            ["50pt 23%", [50.0, "23%"]],

            // Calc values
            ["calc(-75% + 100pt)", ["calc(-75% + 100pt)", "50%"]],
            ["calc(33% * 3 + 1%) calc(20pt + 30pt)", ["calc(33% * 3 + 1%)", 50.0]],

            // Case and whitespace variations
            ["LEFT", [0.0, "50%"]],
            ["TOP    Right", ["100%", 0.0]],
            ["-23PT     BoTTom", [-23.0, "100%"]],

            // Invalid values
            ["", [0.0, 0.0]],
            ["none", [0.0, 0.0]],
            ["auto", [0.0, 0.0]],
            ["left left", [0.0, 0.0]],
            ["left right", [0.0, 0.0]],
            ["bottom top", [0.0, 0.0]],
            ["center center center", [0.0, 0.0]],
            ["1pt 2pt 3pt 4pt", [0.0, 0.0]],
            ["23% left", [0.0, 0.0]],
            ["23% right", [0.0, 0.0]],
            ["top 23%", [0.0, 0.0]],
            ["bottom 23%", [0.0, 0.0]]
        ];
    }

    /**
     * @dataProvider backgroundPositionProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('backgroundPositionProvider')]
    public function testBackgroundPosition(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("background_position", $value);
        $this->assertSame($expected, $style->background_position);
    }

    public static function backgroundSizeProvider(): array
    {
        return [
            // Keywords
            ["cover", "cover"],
            ["contain", "contain"],

            // One value
            ["100%", ["100%", "auto"]],
            ["200pt", [200.0, "auto"]],

            // Two values
            ["100% auto", ["100%", "auto"]],
            ["200pt auto", [200.0, "auto"]],
            ["auto 100%", ["auto", "100%"]],
            ["auto 200pt", ["auto", 200.0]],
            ["10% 200pt", ["10%", 200.0]],

            // Calc values
            ["calc(-75% + 100pt) auto", ["calc(-75% + 100pt)", "auto"]],
            ["calc(33% * 3 + 1%) calc(20pt + 30pt)", ["calc(33% * 3 + 1%)", 50.0]],

            // Case and whitespace variations
            ["CoveR", "cover"],
            ["AUTO    23PT", ["auto", 23.0]],
            ["CALC(20PT*3)23PT", [60.0, 23.0]],

            // Invalid values
            ["", ["auto", "auto"]],
            ["none", ["auto", "auto"]],
            ["auto", ["auto", "auto"]],
            ["cover contain", ["auto", "auto"]]
        ];
    }

    /**
     * @dataProvider backgroundSizeProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('backgroundSizeProvider')]
    public function testBackgroundSize(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("background_size", $value);
        $this->assertSame($expected, $style->background_size);
    }

    public static function fontWeightProvider(): array
    {
        return [
            // Absolute
            ["normal", 400],
            ["bold", 700],
            ["1", 1],
            ["100", 100],
            ["125", 125],
            ["400", 400],
            ["700", 700],
            ["900", 900],
            ["1000", 1000],
            ["+1e3", 1000],

            // Relative
            ["bolder", 400, 1],
            ["bolder", 400, 100],
            ["bolder", 400, 200],
            ["bolder", 400, 300],
            ["bolder", 700, 400],
            ["bolder", 700, 500],
            ["bolder", 900, 600],
            ["bolder", 900, 700],
            ["bolder", 900, 800],
            ["bolder", 900, 900],
            ["bolder", 917, 917],
            ["lighter", 15, 15],
            ["lighter", 100, 100],
            ["lighter", 100, 200],
            ["lighter", 100, 300],
            ["lighter", 100, 400],
            ["lighter", 100, 500],
            ["lighter", 400, 600],
            ["lighter", 400, 700],
            ["lighter", 700, 800],
            ["lighter", 700, 900],
            ["lighter", 700, 1000],

            // Case variations
            ["BOLD", 700],

            // Invalid values
            ["none", 400],
            ["-100", 400],
            ["1001", 400]
        ];
    }

    /**
     * @dataProvider fontWeightProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('fontWeightProvider')]
    public function testFontWeight(string $value, $expected, int $parentWeight = 400): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);
        $parentStyle = new Style($sheet);

        $parentStyle->set_prop("font_weight", $parentWeight);
        $style->inherit($parentStyle);

        $style->set_prop("font_weight", $value);
        $this->assertSame($expected, $style->font_weight);
    }

    private function validateLengthProperty(
        string $prop,
        string $value,
        float $fontSize,
        $expected,
        $initialProps = []
    ): void {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->font_size = $fontSize;

        foreach ($initialProps as $p => $v) {
            $style->$p = $v;
        }

        $style->set_prop($prop, $value);
        $this->assertSame($expected, $style->$prop);
    }

    public static function lengthPercentagePositiveProvider(): array
    {
        return [
            // Lengths
            ["0", 12.0, 0.0],
            ["1em", 20.0, 20.0],
            ["100pt", 12.0, 100.0],
            ["50%", 12.0, "50%"],

            // Calc values
            ["calc(6pt + 2em)", 12.0, 30.0],
            ["calc(50% + 2em)", 12.0, "calc(50% + 2em)"],
            ["calc(100% - 100pt)", 12.0, "calc(100% - 100pt)"],
            ["calc(-100pt)", 12.0, -100.0], // Negative calc values are valid
            ["calc(-50%)", 12.0, "calc(-50%)"],

            // Case variations
            ["1EM", 20.0, 20.0],

            // Invalid values
            ["-100pt", 12.0, 79.0, 79.0],
            ["-50%", 12.0, 79.0, 79.0]
        ];
    }

    public static function lengthPercentageProvider(): array
    {
        return [
            // Lengths
            ["0", 12.0, 0.0],
            ["1em", 20.0, 20.0],
            ["100pt", 12.0, 100.0],
            ["-100pt", 12.0, -100.0],
            ["50%", 12.0, "50%"],
            ["-50%", 12.0, "-50%"],

            // Calc values
            ["calc(6pt - 2em)", 12.0, -18.0],
            ["calc(50% + 2em)", 12.0, "calc(50% + 2em)"],
            ["calc(100% - 100pt)", 12.0, "calc(100% - 100pt)"],
            ["calc(-100pt)", 12.0, -100.0],
            ["calc(-50%)", 12.0, "calc(-50%)"],

            // Case variations
            ["1EM", 20.0, 20.0],

            // Invalid values
            ["invalid", 12.0, 79.0, 79.0],
            ["-50% + 2em", 12.0, 79.0, 79.0]
        ];
    }

    public static function autoKeywordProvider(): array
    {
        return [
            // Keywords
            ["auto", 12.0, "auto", 0.0],

            // Case variations
            ["Auto", 12.0, "auto", 0.0],
            ["AUTO", 12.0, "auto", 0.0],

            // Invalid values
            ["none", 12.0, 79.0, 79.0]
        ];
    }

    /**
     * @dataProvider autoKeywordProvider
     * @dataProvider lengthPercentagePositiveProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('autoKeywordProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('lengthPercentagePositiveProvider')]
    public function testWidth(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->validateLengthProperty("width", $value, $fontSize, $expected, ["width" => $initial]);
    }

    /**
     * @dataProvider autoKeywordProvider
     * @dataProvider lengthPercentagePositiveProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('autoKeywordProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('lengthPercentagePositiveProvider')]
    public function testHeight(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->validateLengthProperty("height", $value, $fontSize, $expected, ["height" => $initial]);
    }

    public static function minWidthHeightProvider(): array
    {
        return [
            // Keywords
            ["auto", 12.0, "auto", 0.0],

            // Legacy keywords
            ["none", 12.0, "auto", 0.0],

            // Case variations
            ["Auto", 12.0, "auto", 0.0],
            ["AUTO", 12.0, "auto", 0.0],

            // Invalid values
            ["other", 12.0, 79.0, 79.0]
        ];
    }

    /**
     * @dataProvider minWidthHeightProvider
     * @dataProvider lengthPercentagePositiveProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('minWidthHeightProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('lengthPercentagePositiveProvider')]
    public function testMinWidth(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->validateLengthProperty("min_width", $value, $fontSize, $expected, ["min_width" => $initial]);
    }

    /**
     * @dataProvider minWidthHeightProvider
     * @dataProvider lengthPercentagePositiveProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('minWidthHeightProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('lengthPercentagePositiveProvider')]
    public function testMinHeight(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->validateLengthProperty("min_height", $value, $fontSize, $expected, ["min_height" => $initial]);
    }

    public static function maxWidthHeightProvider(): array
    {
        return [
            // Keywords
            ["none", 12.0, "none", 0.0],

            // Legacy keywords
            ["auto", 12.0, "none", 0.0],

            // Case variations
            ["None", 12.0, "none", 0.0],
            ["NONE", 12.0, "none", 0.0],

            // Invalid values
            ["other", 12.0, 79.0, 79.0]
        ];
    }

    /**
     * @dataProvider maxWidthHeightProvider
     * @dataProvider lengthPercentagePositiveProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('maxWidthHeightProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('lengthPercentagePositiveProvider')]
    public function testMaxWidth(string $value, float $fontSize, $expected, $initial = "none"): void
    {
        $this->validateLengthProperty("max_width", $value, $fontSize, $expected, ["max_width" => $initial]);
    }

    /**
     * @dataProvider maxWidthHeightProvider
     * @dataProvider lengthPercentagePositiveProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('maxWidthHeightProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('lengthPercentagePositiveProvider')]
    public function testMaxHeight(string $value, float $fontSize, $expected, $initial = "none"): void
    {
        $this->validateLengthProperty("max_height", $value, $fontSize, $expected, ["max_height" => $initial]);
    }

    /**
     * @dataProvider autoKeywordProvider
     * @dataProvider lengthPercentageProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('autoKeywordProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('lengthPercentageProvider')]
    public function testBoxInset(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->validateLengthProperty("top", $value, $fontSize, $expected, ["top" => $initial]);
        $this->validateLengthProperty("right", $value, $fontSize, $expected, ["right" => $initial]);
        $this->validateLengthProperty("bottom", $value, $fontSize, $expected, ["bottom" => $initial]);
        $this->validateLengthProperty("left", $value, $fontSize, $expected, ["left" => $initial]);
    }

    public static function marginProvider(): array
    {
        return [
            // Keywords
            ["auto", 12.0, "auto", 0.0],

            // Legacy keywords
            ["none", 12.0, 0.0, 0.0],

            // Case variations
            ["Auto", 12.0, "auto", 0.0],
            ["AUTO", 12.0, "auto", 0.0],

            // Invalid values
            ["other", 12.0, 79.0, 79.0]
        ];
    }

    /**
     * @dataProvider marginProvider
     * @dataProvider lengthPercentageProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('marginProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('lengthPercentageProvider')]
    public function testMargin(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->validateLengthProperty("margin_top", $value, $fontSize, $expected, ["margin_top" => $initial]);
        $this->validateLengthProperty("margin_right", $value, $fontSize, $expected, ["margin_right" => $initial]);
        $this->validateLengthProperty("margin_bottom", $value, $fontSize, $expected, ["margin_bottom" => $initial]);
        $this->validateLengthProperty("margin_left", $value, $fontSize, $expected, ["margin_left" => $initial]);
    }

    public static function paddingProvider(): array
    {
        return [
            // Legacy keywords
            ["none", 12.0, 0.0, 0.0],

            // Invalid values
            ["auto", 12.0, 79.0, 79.0]
        ];
    }

    /**
     * @dataProvider paddingProvider
     * @dataProvider lengthPercentagePositiveProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('paddingProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('lengthPercentagePositiveProvider')]
    public function testPadding(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->validateLengthProperty("padding_top", $value, $fontSize, $expected, ["padding_top" => $initial]);
        $this->validateLengthProperty("padding_right", $value, $fontSize, $expected, ["padding_right" => $initial]);
        $this->validateLengthProperty("padding_bottom", $value, $fontSize, $expected, ["padding_bottom" => $initial]);
        $this->validateLengthProperty("padding_left", $value, $fontSize, $expected, ["padding_left" => $initial]);
    }

    public static function lineWidthProvider(): array
    {
        return [
            // Keywords
            ["thin", 12.0, 0.5],
            ["medium", 12.0, 1.5],
            ["thick", 12.0, 2.5],

            // Lengths
            ["0", 12.0, 0.0],
            ["1em", 20.0, 20.0],
            ["100pt", 12.0, 100.0],

            // Calc values
            ["calc(6pt + 2em)", 12.0, 30.0],
            ["calc(-100pt)", 12.0, -100.0], // Negative calc values are valid

            // Case variations
            ["THIN", 12.0, 0.5],
            ["Medium", 12.0, 1.5],
            ["thICK", 12.0, 2.5],
            ["1EM", 20.0, 20.0],

            // Invalid values
            ["auto", 12.0, 5.0, 5.0, 5.0],
            ["none", 12.0, 5.0, 5.0, 5.0],
            ["-100pt", 12.0, 5.0, 5.0, 5.0],
            ["50%", 12.0, 5.0, 5.0, 5.0],
            ["-50%", 12.0, 5.0, 5.0, 5.0],
            ["calc(50% + 2em)", 12.0, 5.0, 5.0, 5.0]
        ];
    }

    /**
     * @dataProvider lineWidthProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('lineWidthProvider')]
    public function testBorderOutlineWidth(
        string $value,
        float $fontSize,
        $expectedStyleSolid,
        $expectedStyleNone = 0.0,
        $initial = "50.0"
    ): void {
        $props = ["border_top", "border_right", "border_bottom", "border_left", "outline"];

        foreach ($props as $prop) {
            $initialPropsSolid = [
                "{$prop}_width" => $initial,
                "{$prop}_style" => "solid"
            ];
            $initialPropsNone = [
                "{$prop}_width" => $initial,
                "{$prop}_style" => "none"
            ];

            $this->validateLengthProperty("{$prop}_width", $value, $fontSize, $expectedStyleSolid, $initialPropsSolid);
            $this->validateLengthProperty("{$prop}_width", $value, $fontSize, $expectedStyleNone, $initialPropsNone);
        }
    }

    public static function borderRadiusProvider(): array
    {
        return [
            // Invalid values
            ["auto", 12.0, 79.0, 79.0],
            ["none", 12.0, 79.0, 79.0]
        ];
    }

    /**
     * @dataProvider borderRadiusProvider
     * @dataProvider lengthPercentagePositiveProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('borderRadiusProvider')]
    #[\PHPUnit\Framework\Attributes\DataProvider('lengthPercentagePositiveProvider')]
    public function testBorderRadius(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->validateLengthProperty("border_top_left_radius", $value, $fontSize, $expected, ["border_top_left_radius" => $initial]);
        $this->validateLengthProperty("border_top_right_radius", $value, $fontSize, $expected, ["border_top_right_radius" => $initial]);
        $this->validateLengthProperty("border_bottom_right_radius", $value, $fontSize, $expected, ["border_bottom_right_radius" => $initial]);
        $this->validateLengthProperty("border_bottom_left_radius", $value, $fontSize, $expected, ["border_bottom_left_radius" => $initial]);
    }

    public static function borderSpacingProvider(): array
    {
        return [
            // One value
            ["0", [0.0, 0.0]],
            ["10pt", [10.0, 10.0]],

            // Two values
            ["0 0", [0.0, 0.0]],
            ["20pt 50pt", [20.0, 50.0]],

            // Calc values
            ["20pt calc(20pt + 30pt)", [20.0, 50.0]],

            // Case and whitespace variations
            ["CALC(20PT*3)23PT", [60.0, 23.0]],

            // Invalid values
            ["", [0.0, 0.0]],
            ["none", [0.0, 0.0]],
            ["auto", [0.0, 0.0]],
            ["100% 10pt", [0.0, 0.0]],
            ["30pt -10pt", [0.0, 0.0]]
        ];
    }

    /**
     * @dataProvider borderSpacingProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('borderSpacingProvider')]
    public function testBorderSpacing(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("border_spacing", $value);
        $this->assertSame($expected, $style->border_spacing);
    }

    public static function counterIncrementProvider(): array
    {
        return [
            // Keywords
            ["none", "none"],

            // Valid values
            ["c", ["c" => 1]],
            ["c1 c2 c3", ["c1" => 1, "c2" => 1, "c3" => 1]],
            ["c 0", ["c" => 0]],
            ["c 1", ["c" => 1]],
            ["c -5", ["c" => -5]],
            ["c1 -5 c2 2", ["c1" => -5, "c2" => 2]],
            ["c1 -5 c2", ["c1" => -5, "c2" => 1]],
            ["c1 c2 2", ["c1" => 1, "c2" => 2]],
            ["UPPER lower", ["UPPER" => 1, "lower" => 1]],

            // Duplicate counter
            ["c 2 c 4", ["c" => 6]],

            // Case and whitespace variations
            ["NONE", "none"],
            ["UPPER\tlower       \n   5", ["UPPER" => 1, "lower" => 5]],

            // Invalid values
            ["", "none"],
            ["3", "none"],
            ["c 3 7", "none"],
            ["3 c 7", "none"],

            // Reserved names
            ["inherit 1", "none"],
            ["initial 1", "none"],
            ["unset 1", "none"],
            ["default 1", "none"]
        ];
    }

    /**
     * @dataProvider counterIncrementProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('counterIncrementProvider')]
    public function testCounterIncrement(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("counter_increment", $value);
        $this->assertSame($expected, $style->counter_increment);
    }

    public static function counterResetProvider(): array
    {
        return [
            // Keywords
            ["none", "none"],

            // Valid values
            ["c", ["c" => 0]],
            ["c1 c2 c3", ["c1" => 0, "c2" => 0, "c3" => 0]],
            ["c 0", ["c" => 0]],
            ["c 1", ["c" => 1]],
            ["c -5", ["c" => -5]],
            ["c1 -5 c2 2", ["c1" => -5, "c2" => 2]],
            ["c1 -5 c2", ["c1" => -5, "c2" => 0]],
            ["c1 c2 2", ["c1" => 0, "c2" => 2]],
            ["UPPER lower", ["UPPER" => 0, "lower" => 0]],

            // Duplicate counter
            ["c 2 c 4", ["c" => 4]],

            // Case and whitespace variations
            ["NONE", "none"],
            ["UPPER\tlower       \n   5", ["UPPER" => 0, "lower" => 5]],

            // Invalid values
            ["", "none"],
            ["3", "none"],
            ["c 3 7", "none"],
            ["3 c 7", "none"],

            // Reserved names
            ["inherit 1", "none"],
            ["initial 1", "none"],
            ["unset 1", "none"],
            ["default 1", "none"]
        ];
    }

    /**
     * @dataProvider counterResetProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('counterResetProvider')]
    public function testCounterReset(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("counter_reset", $value);
        $this->assertSame($expected, $style->counter_reset);
    }

    public static function quotesProvider(): array
    {
        $autoResolved = [['"', '"'], ["'", "'"]];

        return [
            // Keywords
            ["none", "none"],
            ["auto", $autoResolved],

            // Valid values
            ["'\"' '\"'", [['"', '"']]],
            [" '\"'   '\"'   \"'\"   \"'\" ", [['"', '"'], ["'", "'"]]],
            ["'“' '”' '‘' '’'", [['“', '”'], ['‘', '’']]],
            ["'open-quote' 'close-quote'", [["open-quote", "close-quote"]]],
            ["'😀️' '😐️' '\"2\"' '\"2\"' '›' '‹'", [['😀️', '😐️'], ['"2"', '"2"'], ['›', '‹']]],

            // Case and whitespace variations
            ["NONE", "none"],
            ["Auto", $autoResolved],
            ["AUTO", $autoResolved],
            ["'\"'    '\"'", [['"', '"']]],

            // Invalid values
            ["'\''", $autoResolved]
        ];
    }

    /**
     * @dataProvider quotesProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('quotesProvider')]
    public function testQuotes(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("quotes", $value);
        $this->assertSame($expected, $style->quotes);
    }

    public static function contentProvider(): array
    {
        return [
            // Keywords
            ["normal", "normal"],
            ["none", "none"],

            // String
            ['"string"', [new StringPart("string")]],
            ["'string'", [new StringPart("string")]],
            ["\"'s't\\\"r'\"", [new StringPart("'s't\"r'")]],
            ['"\(\A\5F\;_\A\)"', [new StringPart("(\n_;_\n)")]],
            ["'attr(title)'", [new StringPart("attr(title)")]],
            ['"url(\'image.png\')"', [new StringPart("url('image.png')")]],

            // Attr
            ["attr(title)", [new Attr("title")]],

            // Url
            ["url(image.png)", [new Url("image.png")]],
            ['url("image.png")', [new Url("image.png")]],
            ["url('image.png')", [new Url("image.png")]],
            ["url(\"'image.PNG'\")", [new Url("'image.PNG'")]],
            ["url(\"image(1).PNG\")", [new Url("image(1).PNG")]],
            ["url(image\(1\).PNG)", [new Url("image(1).PNG")]],
            ["url(\"image\\\"1\\\".PNG\")", [new Url("image\"1\".PNG")]],

            // Counter/Counters
            ["counter(c)", [new Counter("c", "decimal")]],
            ["counter(UPPER, UPPER-roman)", [new Counter("UPPER", "upper-roman")]],
            ["counters(c, '')", [new Counters("c", "", "decimal")]],
            ["counters(c, '', decimal)", [new Counters("c", "", "decimal")]],
            ["counters(c, ')', decimal)", [new Counters("c", ")", "decimal")]],
            ["counters(UPPER, 'UPPER', lower-ROMAN)", [new Counters("UPPER", "UPPER", "lower-roman")]],

            // Quotes
            ["open-quote", [new OpenQuote]],
            ["close-quote", [new CloseQuote]],
            ["no-open-quote", [new NoOpenQuote]],
            ["no-close-quote", [new NoCloseQuote]],

            // Case and whitespace variations
            ["Normal", "normal"],
            ["NONE", "none"],
            ["ATTR(  TITLE )", [new Attr("title")]],
            ["URL( \n\t \"'image.PNG ' \"  )", [new Url("'image.PNG ' ")]],
            ["COUNTER(  UPPER  ,  UPPER-roman  )", [new Counter("UPPER", "upper-roman")]],
            ["COUNTERS(  UPPER  ,  ' \"UPPER\"'  , lower-ROMAN  )", [new Counters("UPPER", " \"UPPER\"", "lower-roman")]],
            ["OPEN-QUOTE", [new OpenQuote]],
            ["No-Close-Quote", [new NoCloseQuote]],

            // Content lists
            [
                "'–' attr( title ) '–'",
                [new StringPart("–"), new Attr("title"), new StringPart("–")]
            ],
            [
                'counter(page)" / {PAGES}"',
                [new Counter("page", "decimal"), new StringPart(" / {PAGES}")]
            ],
            [
                "counter(li1, decimal)\".\"counters(li2, '.', upper-roman)   ')'URL('IMAGE.png')",
                [new Counter("li1", "decimal"), new StringPart("."), new Counters("li2", ".", "upper-roman"), new StringPart(")"), new Url("IMAGE.png")]
            ],
            [
                '"url(\' \')"open-quote url(" ")close-quote',
                [new StringPart("url(' ')"), new OpenQuote, new Url(" "), new CloseQuote]
            ],

            // Invalid values
            ["attr()", "normal"],
            ["count", "normal"],
            ["counter", "normal"],
            ["counter(c", "normal"],
            ["count()", "normal"],
            ["counters(c)", "normal"],
            ["counters(c, decimal, '')", "normal"],
            ["counters(c, decimal)", "normal"],
            ["open-quoteclose-quote", "normal"],
            ["😀️()", "normal"],
            ["attr(title) unknown-keyword", "normal"],

            // Reserved names
            ["counter(none)", "normal"],
            ["counter(inherit)", "normal"],
            ["counter(initial)", "normal"],
            ["counter(unset)", "normal"],
            ["counter(default)", "normal"],
            ["counters(none, '')", "normal"],
            ["counters(inherit, '')", "normal"],
            ["counters(initial, '')", "normal"],
            ["counters(unset, '')", "normal"],
            ["counters(default, '')", "normal"],
            ["counter(c, none)", "normal"],
            ["counter(c, inherit)", "normal"],
            ["counter(c, initial)", "normal"],
            ["counter(c, unset)", "normal"],
            ["counter(c, default)", "normal"],
            ["counters(c, '', none)", "normal"],
            ["counters(c, '', inherit)", "normal"],
            ["counters(c, '', initial)", "normal"],
            ["counters(c, '', unset)", "normal"],
            ["counters(c, '', default)", "normal"]
        ];
    }

    /**
     * @param string               $value
     * @param ContentPart[]|string $expected
     *
     * @dataProvider contentProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('contentProvider')]
    public function testContent(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("content", $value);

        if (is_array($expected)) {
            $content = $style->content;

            $this->assertIsArray($content);
            $this->assertCount(count($expected), $content);

            foreach ($expected as $i => $part) {
                $actualPart = $content[$i];
                $this->assertTrue($part->equals($actualPart), "Failed asserting that $actualPart equals $part.");
            }
        } else {
            $this->assertSame($expected, $style->content);
        }
    }

    public static function sizeProvider(): array
    {
        return [
            // Keywords
            ["auto", "auto"],

            // Default paper sizes
            ["letter", [612.00, 792.00]],
            ["portrait", [419.53, 595.28]],
            ["landscape", [595.28, 419.53]],
            ["A4 portrait", [595.28, 841.89]],
            ["landscape a4", [841.89, 595.28]],

            // Custom paper sizes
            ["200pt", [200.0, 200.0]],
            ["400pt 300pt", [400.0, 300.0]],
            ["400pt 300pt portrait", [300.0, 400.0]],
            ["landscape 300pt 400pt", [400.0, 300.0]],
            ["landscape 400pt 300pt", [400.0, 300.0]],

            // Case and whitespace variations
            ["Auto", "auto"],
            ["AUTO", "auto"],
            ["LETTER", [612.00, 792.00]],
            ["a4    PORTRAIT", [595.28, 841.89]],
            ["LANDSCAPE\n400PT    300PT", [400.0, 300.0]],

            // Invalid values
            ["", "auto"],
            ["letter auto", "auto"],
            ["landscape landscape a4", "auto"],
            ["letter 300mm 300mm", "auto"]
        ];
    }

    /**
     * @dataProvider sizeProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('sizeProvider')]
    public function testSize(string $value, $expected): void
    {
        $options = new Options();
        $options->setDefaultPaperSize("A5");
        $dompdf = new Dompdf($options);
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("size", $value);
        $this->assertSame($expected, $style->size);
    }

    public static function transformProvider(): array
    {
        $initialInvalid = [["translate", 0.0, 0.0]];

        return [
            // Keywords
            ["none", []],

            // Translate
            ["translate(10pt)", [["translate", [10.0, 0.0]]]],
            ["translate(10pt, 5pt)", [["translate", [10.0, 5.0]]]],
            ["translate(100%, -50%)", [["translate", ["100%", "-50%"]]]],
            ["translateX(10pt)", [["translate", [10.0, 0.0]]]],
            ["translateY(10pt)", [["translate", [0.0, 10.0]]]],

            // Scale
            ["scale(2.5)", [["scale", [2.5, 2.5]]]],
            ["scale(5, 1)", [["scale", [5.0, 1.0]]]],
            ["scale(-5, 0)", [["scale", [-5.0, 0.0]]]],
            ["scaleX(5)", [["scale", [5.0, 1.0]]]],
            ["scaleY(5)", [["scale", [1.0, 5.0]]]],

            // Rotate
            ["rotate(0.0)", [["rotate", [0.0]]]],
            ["rotate(0deg)", [["rotate", [0.0]]]],
            ["rotate(360deg)", [["rotate", [360.0]]]],
            ["rotate(-45deg)", [["rotate", [-45.0]]]],
            ["rotate(-200grad)", [["rotate", [-180.0]]]],
            ["rotate(0rad)", [["rotate", [0.0]]]],
            ["rotate(0.25turn)", [["rotate", [90.0]]]],

            // Skew
            ["skew(45deg)", [["skew", [45.0, 0.0]]]],
            ["skew(45deg, 45deg)", [["skew", [45.0, 45.0]]]],
            ["skewX(45deg)", [["skew", [45.0, 0.0]]]],
            ["skewY(45deg)", [["skew", [0.0, 45.0]]]],

            // Transform list and calc values
            ["translateX(10pt) translateX(-10pt)", [["translate", [10.0, 0.0]], ["translate", [-10.0, 0.0]]]],
            ["scale(2.5) translate(calc(100% - 100pt), 100pt) rotate(-90deg)", [["scale", [2.5, 2.5]], ["translate", ["calc(100% - 100pt)", 100.0]], ["rotate", [-90.0]]]],

            // Case and whitespace variations
            ["translatex(10pt)", [["translate", [10.0, 0.0]]]],
            ["SCALE(2.5)TRANSLATEy(CALc(-10pt))", [["scale", [2.5, 2.5]], ["translate", [0.0, -10.0]]]],

            // Invalid values
            ["auto", $initialInvalid, $initialInvalid],
            ["translate( )", $initialInvalid, $initialInvalid],
            ["scale(1, 1, 1)", $initialInvalid, $initialInvalid],
            ["rotate(20deg, 30deg) ", $initialInvalid, $initialInvalid],
            ["rotate(20deg) skewY(45deg, 90deg)", $initialInvalid, $initialInvalid],
        ];
    }

    /**
     * @dataProvider transformProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('transformProvider')]
    public function testTransform(string $value, $expected, array $initial = []): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->transform = $initial;
        $style->set_prop("transform", $value);
        $this->assertSame($expected, $style->transform);
    }

    public static function transformOriginProvider(): array
    {
        return [
            // One value
            ["left", [0.0, "50%", 0.0]],
            ["right", ["100%", "50%", 0.0]],
            ["top", ["50%", 0.0, 0.0]],
            ["bottom", ["50%", "100%", 0.0]],
            ["center", ["50%", "50%", 0.0]],
            ["20pt", [20.0, "50%", 0.0]],
            ["-10pt", [-10.0, "50%", 0.0]],
            ["23%", ["23%", "50%", 0.0]],
            ["-75%", ["-75%", "50%", 0.0]],

            // Two values
            ["left top", [0.0, 0.0, 0.0]],
            ["top left", [0.0, 0.0, 0.0]],
            ["left bottom", [0.0, "100%", 0.0]],
            ["bottom left", [0.0, "100%", 0.0]],
            ["left center", [0.0, "50%", 0.0]],
            ["center left", [0.0, "50%", 0.0]],
            ["right top", ["100%", 0.0, 0.0]],
            ["top right", ["100%", 0.0, 0.0]],
            ["right bottom", ["100%", "100%", 0.0]],
            ["bottom right", ["100%", "100%", 0.0]],
            ["right center", ["100%", "50%", 0.0]],
            ["center right", ["100%", "50%", 0.0]],
            ["bottom center", ["50%", "100%", 0.0]],
            ["center bottom", ["50%", "100%", 0.0]],
            ["top center", ["50%", 0.0, 0.0]],
            ["center top", ["50%", 0.0, 0.0]],
            ["center center", ["50%", "50%", 0.0]],
            ["left 23%", [0.0, "23%", 0.0]],
            ["right 23%", ["100%", "23%", 0.0]],
            ["center 23%", ["50%", "23%", 0.0]],
            ["23% top", ["23%", 0.0, 0.0]],
            ["23% bottom", ["23%", "100%", 0.0]],
            ["23% center", ["23%", "50%", 0.0]],
            ["23% 50pt", ["23%", 50.0, 0.0]],
            ["50pt 23%", [50.0, "23%", 0.0]],

            // Three values
            ["left top 20pt", [0.0, 0.0, 20.0]],
            ["center bottom 0", ["50%", "100%", 0.0]],
            ["center center -50pt", ["50%", "50%", -50.0]],
            ["-50pt -23% -50pt", [-50.0, "-23%", -50.0]],

            // Calc values
            ["calc(-75% + 100pt)", ["calc(-75% + 100pt)", "50%", 0.0]],
            ["calc(33% * 3 + 1%) calc(20pt + 30pt) calc( 99pt/3 )", ["calc(33% * 3 + 1%)", 50.0, 33.0]],

            // Case and whitespace variations
            ["LEFT", [0.0, "50%", 0.0]],
            ["TOP    Right", ["100%", 0.0, 0.0]],
            ["-23PT     BoTTom", [-23.0, "100%", 0.0]],

            // Invalid values
            ["", ["50%", "50%", 0.0]],
            ["none", ["50%", "50%", 0.0]],
            ["auto", ["50%", "50%", 0.0]],
            ["left left", ["50%", "50%", 0.0]],
            ["left right", ["50%", "50%", 0.0]],
            ["bottom top", ["50%", "50%", 0.0]],
            ["center center center", ["50%", "50%", 0.0]],
            ["1pt 2pt 3pt 4pt", ["50%", "50%", 0.0]],
            ["23% left", ["50%", "50%", 0.0]],
            ["23% right", ["50%", "50%", 0.0]],
            ["top 23%", ["50%", "50%", 0.0]],
            ["bottom 23%", ["50%", "50%", 0.0]],
            ["-50pt -23% -23%", ["50%", "50%", 0.0]] // Percentage for z not allowed
        ];
    }

    /**
     * @dataProvider transformOriginProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('transformOriginProvider')]
    public function testTransformOrigin(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("transform_origin", $value);
        $this->assertSame($expected, $style->transform_origin);
    }

    public static function opacityProvider(): array
    {
        return [
            // Valid values
            ["0", 0.0],
            ["1", 1.0],
            ["+1.0", 1.0],
            ["0.5", 0.5],
            [".5", 0.5],
            ["100%", 1.0],
            ["23.78%", 0.2378],
            ["2e-2%", 0.0002],

            // Out-of-range values (clamped instead of invalid)
            ["500.95", 1.0],
            ["300%", 1.0],
            ["-100", 0.0],
            ["-23.3%", 0.0],

            // Invalid values
            ["", 1.0],
            ["auto", 1.0],
            ["invalid", 1.0],
            ["0.5pt", 1.0]
        ];
    }

    /**
     * @dataProvider opacityProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('opacityProvider')]
    public function testOpacity(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("opacity", $value);
        $this->assertSame($expected, $style->opacity);
    }

    public static function zIndexProvider(): array
    {
        return [
            // Valid values
            ["auto", "auto"],
            ["0", 0],
            ["1", 1],
            ["+23", 23],
            ["-100", -100],

            // Case variations
            ["AUTO", "auto"],

            // Invalid values
            ["", "auto"],
            ["5.5", "auto"],
            ["invalid", "auto"]
        ];
    }

    /**
     * @dataProvider zIndexProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('zIndexProvider')]
    public function testZIndex(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("z_index", $value);
        $this->assertSame($expected, $style->z_index);
    }

    public function testWordBreakBreakWord(): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("overflow_wrap", "break-word");
        $style->set_prop("word_break", "break-word");

        $this->assertSame("normal", $style->word_break);
        $this->assertSame("anywhere", $style->overflow_wrap);
    }

    public static function varValueProvider(): array {
        return [
            'simple' => [[
                "font_family" => "var(--font-family)",
                "--font-family" => "Helvetica",
            ], "font_family", "Helvetica"],

            'simple_valid_value' => [[
                "font_family" => "var(--font-family, Courier)",
                "--font-family" => "Helvetica"
            ], "font_family", "Helvetica"],

            'simple_empty_value' => [[
                "font_family" => "var(--font-family, Courier)",
                "--font-family" => ""
            ], "font_family", "Courier"],

            'simple_invalid_value' => [[
                "font_family" => "var(--invalid-prop, Courier)",
                "--font-family" => ""
            ], "font_family", "Courier"],

            'var_value' => [[
                "border" => "2px solid var(--bg)",
                "--bg" => "#ff0000FF",
            ], "border_top_color", "#ff0000FF"],

            'var_value_twice' => [[
                "border" => "2px solid var(--bg)",
                "--bg" => "#ff0000FF",
                "--bg" => "#0000ffFF",
            ], "border_top_color", "#0000ffFF"],

            'multi_var_value_color' => [[
                "border" => "2px var(--style) var(--bg)",
                "--style" => "dotted",
                "--bg" => "#ff0000FF",
            ], "border_top_color", "#ff0000FF"],

            'multi_var_value_style' => [[
                "border" => "2px var(--style) var(--bg)",
                "--style" => "dotted",
                "--bg" => "#ff0000FF",
            ], "border_top_style", "dotted"],

            'shorthand_override' => [[
                "border" => "2px solid var(--bg)",
                "border-color" => "#0000ffff",
                "--bg" => "#ff0000FF",
            ], "border_top_color", "#0000ffFF"],

            'specific_override' => [[
                "border-color" => "#0000ffff",
                "border" => "2px solid var(--bg)",
                "--bg" => "#ff0000FF",
            ], "border_top_color", "#ff0000FF"],

            'referenced_var' => [[
                "border" => "var(--border-specification)",
                "--border-specification" => "2px solid var(--bg)",
                "--bg" => "#ff0000FF",
            ], "border_top_color", "#ff0000FF"],

            'fallback_var_valid_property' => [[
                "background_color" => "var(--bg, var(--fallback))",
                "--bg" => "#ffffffFF",
                "--fallback" => "#000000FF",
            ], "background_color", "#ffffffFF"],

            'fallback_var_undefined_property' => [[
                "background_color" => "var(--undefined, var(--fallback))",
                "--fallback" => "#000000FF",
            ], "background_color", "#000000FF"],

            'fallback_var_double_undefined_property' => [[
                "background_color" => "var(--undefined, var(--undefined, #eeeeeeFF))",
            ], "background_color", "#eeeeeeFF"],

            'recursion' => [[
                "color" => "var(--one)",
                "--one" => "var(--one)"
            ], "color", "#000000FF"],

            'recursion_with_fallback' => [[
                "color" => "var(--one)",
                "--one" => "var(--one, #00ff00ff)"
            ], "color", "#00ff00FF"],

            'recursion_with_recursive fallback' => [[
                "color" => "var(--one)",
                "--one" => "var(--one, var(--one))"
            ], "color", "#000000FF"],
        ];
    }

    /**
     * @dataProvider varValueProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('varValueProvider')]
    public function testVar(array $properties, $lookup_property, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        // Set all properties and values.
        foreach ($properties as $property => $value) {
            $style->set_prop($property, $value);
        }

        // Use __get to get the computed value.
        $resolved_value = $style->$lookup_property;

        // Only compare the hex value from color arrays.
        if (is_array($resolved_value) && array_key_exists("hex", $resolved_value)) {
            $resolved_value = $resolved_value["hex"];
        }

        // Assert the parsed result.
        $this->assertStringContainsString($expected, $resolved_value);
    }

    public static function mergedVarValueProvider(): array {
        return [
            'simple' => [[
                [
                    "color" => "var(--color)",
                    "--color" => "#ff0000FF"
                ],
                [
                    "--color" => "#0000ffFF"
                ],
            ], "color", "#0000ffFF"],

            'important_ref' => [[
                [
                    "color" => "var(--color) !important",
                    "--color" => "#ff0000FF"
                ],
                [
                    "color" => "000000FF",
                    "--color" => "#0000ffFF"
                ],
            ], "color", "#0000ffFF"],

            'important_var' => [[
                [
                    "color" => "var(--color)",
                    "--color" => "#ff0000FF !important"
                ],
                [
                    "--color" => "#0000ffFF"
                ],
            ], "color", "#ff0000FF"],
        ];
    }

    /**
     * @dataProvider mergedVarValueProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('mergedVarValueProvider')]
    public function testMergeVar(array $styleDefs, $lookup_property, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $styles = [
            new Style($sheet),
            new Style($sheet)
        ];

        // Set all properties and values.
        foreach ($styleDefs as $index => $def) {
            foreach ($def as $prop => $value) {
                $important = false;
                if (substr($value, -9) === 'important') {
                    $value_tmp = rtrim(substr($value, 0, -9));

                    if (substr($value_tmp, -1) === '!') {
                        $value = rtrim(substr($value_tmp, 0, -1));
                        $important = true;
                    }
                }
                $styles[$index]->set_prop($prop, $value, $important);
            }
        }

        $resolved_style = new Style($sheet);
        foreach ($styles as $style) {
            $resolved_style->merge($style);
        }

        // Use __get to get the computed value.
        $resolved_value = $resolved_style->$lookup_property;

        // Only compare the hex value from color arrays.
        if (is_array($resolved_value) && array_key_exists("hex", $resolved_value)) {
            $resolved_value = $resolved_value["hex"];
        }

        // Assert the parsed result.
        $this->assertStringContainsString($expected, $resolved_value);
    }

    public static function inheritedVarValueProvider(): array {
        return [
            'outer' => ['outer', '#0000ffFF'],
            'middle1' => ['middle1', '#00ff00FF'],
            'middle2' => ['middle2', '#ffff00FF'],
            'inner' => ['inner', '#ff0000FF'],
            'fallback' => ['fallback', '#ff00ffFF'],
            'undefined' => ['undefined', 'transparent'],
            'undefined-inherit' => ['undefined-inherit', 'transparent'],
            'inherit' => ['inherit', '#ffffffFF'],
            'invalid-inherit' => ['invalid-inherit', 'transparent'],
        ];
    }

    /**
     * @dataProvider inheritedVarValueProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('inheritedVarValueProvider')]
    public function testInheritedVar($id, $hexval): void
    {
        $html = '<!DOCTYPE html>
<html>
    <head>
        <style>
            :root {
                --custom-color: #ffffff;
                --custom-size: 1em;
            }
            div {
                background-color: var(--custom-color);
            }
            #middle1 {
                background-color: var(--custom-color, turquoise);
            }
            #outer {
                --custom-color: #00ff00ff;
                background-color: #0000ffff;
            }
            #middle2 {
                --custom-color: #ff0000ff;
                background-color: #ffff00ff;
            }
            #inherit {
                --custom-color: inherit;
            }
            #invalid-inherit {
                background-color: var(--custom-size);
            }
            #fallback {
                --fallback-property: #ff00ffff;
                background-color: var(--undefined-property, var(--fallback-property));
            }
            #undefined {
                background-color: var(--undefined-property);
            }
            #undefined-inherit {
                --inherited-property: inherit;
                background-color: var(--inherited-property);
            }
        </style>
    </head>
    <body>
        <div id="outer">
            <div id="middle1"></div>
            <div id="middle2">
                <div id="inner"></div>
            </div>
        </div>
        <div id="fallback"></div>
        <div id="inherit"></div>
        <div id="undefined"></div>
        <div id="undefined-inherit"></div>
        <div id="invalid-inherit"></div>
    </body>
</html>';

        $styles = [];

        $dompdf = new Dompdf();

        $dompdf->setCallbacks(['test' => [
            'event' => 'end_frame',
            'f' => function (Frame $frame) use (&$styles) {
                $node = $frame->get_node();
                if ($node->nodeName === 'div') {
                    $htmlid = $node->hasAttributes() && ($id = $node->attributes->getNamedItem("id")) !== null ? $id->nodeValue : $frame->get_id();
                    $background_color = $frame->get_style()->background_color;
                    $styles[$htmlid] = is_array($background_color) ? $background_color['hex'] : $background_color;
                }
            }
        ]]);

        $dompdf->loadHtml($html);
        $dompdf->render();

        // Todo: Ideally have the style associated with the div id or something.
        $this->assertEquals($hexval, $styles[$id]);
    }
}
