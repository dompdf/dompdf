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
use Dompdf\Options;
use Dompdf\Tests\TestCase;

class StyleTest extends TestCase
{
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

            ["calc(100%)", null, 12.0],
            ["calc(50% - 1pt)", 200, 99.0],
            ["calc(100)", null, 100.0],
            ["calc(100% / 3)", 100, 33.333333333333336],
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
            ["calc((50% + 10) + 2pt))", 100, 0.0]          // invalid - extra bracket
        ];
    }

    /**
     * @dataProvider lengthInPtProvider
     */
    public function testLengthInPt(string $length, ?float $ref_size, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $s = new Style($sheet);

        $result = $s->length_in_pt($length, $ref_size);
        $this->assertSame($expected, $result);
    }

    public static function cssImageBasicProvider(): array
    {
        return [
            "no value" => ["", "none"],
            "keyword none" => ["none", "none"],
            "bare url" => ["http://example.com/test.png", "none"],
            "http" => ["url(http://example.com/test.png)", "http://example.com/test.png"],
            "case" => ["URL(http://example.com/Test.png)", "http://example.com/Test.png"]
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

            // Case and whitespace variations
            ["LEFT", [0.0, "50%"]],
            ["TOP    Right", ["100%", 0.0]],
            ["-23PT     BoTTom", [-23.0, "100%"]],

            // Invalid values
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
    public function testBackgroundPosition(string $value, $expected): void
    {
        $dompdf = new Dompdf();
        $sheet = new Stylesheet($dompdf);
        $style = new Style($sheet);

        $style->set_prop("background_position", $value);
        $this->assertSame($expected, $style->background_position);
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

    private function testLengthProperty(
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

    public static function widthHeightProvider(): array
    {
        return [
            // Keywords
            ["auto", 12.0, "auto", 0.0],

            // Lengths
            ["0", 12.0, 0.0],
            ["1em", 20.0, 20.0],
            ["100pt", 12.0, 100.0],
            ["50%", 12.0, "50%"],

            // Case variations
            ["Auto", 12.0, "auto", 0.0],
            ["AUTO", 12.0, "auto", 0.0],
            ["1EM", 20.0, 20.0],

            // Invalid values
            ["none", 12.0, "auto"],
            ["-100pt", 12.0, "auto"],
            ["-50%", 12.0, "auto"]
        ];
    }

    /**
     * @dataProvider widthHeightProvider
     */
    public function testWidth(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->testLengthProperty("width", $value, $fontSize, $expected, ["width" => $initial]);
    }

    /**
     * @dataProvider widthHeightProvider
     */
    public function testHeight(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->testLengthProperty("height", $value, $fontSize, $expected, ["height" => $initial]);
    }

    public static function minWidthHeightProvider(): array
    {
        return [
            // Keywords
            ["auto", 12.0, "auto", 0.0],

            // Legacy keywords
            ["none", 12.0, "auto", 0.0],

            // Lengths
            ["0", 12.0, 0.0],
            ["1em", 20.0, 20.0],
            ["100pt", 12.0, 100.0],
            ["50%", 12.0, "50%"],

            // Case variations
            ["Auto", 12.0, "auto", 0.0],
            ["AUTO", 12.0, "auto", 0.0],
            ["1EM", 20.0, 20.0],

            // Invalid values
            ["-100pt", 12.0, "auto"],
            ["-50%", 12.0, "auto"]
        ];
    }

    /**
     * @dataProvider minWidthHeightProvider
     */
    public function testMinWidth(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->testLengthProperty("min_width", $value, $fontSize, $expected, ["min_width" => $initial]);
    }

    /**
     * @dataProvider minWidthHeightProvider
     */
    public function testMinHeight(string $value, float $fontSize, $expected, $initial = "auto"): void
    {
        $this->testLengthProperty("min_height", $value, $fontSize, $expected, ["min_height" => $initial]);
    }

    public static function maxWidthHeightProvider(): array
    {
        return [
            // Keywords
            ["none", 12.0, "none", 0.0],

            // Legacy keywords
            ["auto", 12.0, "none", 0.0],

            // Lengths
            ["0", 12.0, 0.0],
            ["1em", 20.0, 20.0],
            ["100pt", 12.0, 100.0],
            ["50%", 12.0, "50%"],

            // Case variations
            ["None", 12.0, "none", 0.0],
            ["NONE", 12.0, "none", 0.0],
            ["1EM", 20.0, 20.0],

            // Invalid values
            ["-100pt", 12.0, "none"],
            ["-50%", 12.0, "none"]
        ];
    }

    /**
     * @dataProvider maxWidthHeightProvider
     */
    public function testMaxWidth(string $value, float $fontSize, $expected, $initial = "none"): void
    {
        $this->testLengthProperty("max_width", $value, $fontSize, $expected, ["max_width" => $initial]);
    }

    /**
     * @dataProvider maxWidthHeightProvider
     */
    public function testMaxHeight(string $value, float $fontSize, $expected, $initial = "none"): void
    {
        $this->testLengthProperty("max_height", $value, $fontSize, $expected, ["max_height" => $initial]);
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
            ["-50%", 12.0, 5.0, 5.0, 5.0]
        ];
    }

    /**
     * @dataProvider lineWidthProvider
     */
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

            $this->testLengthProperty("{$prop}_width", $value, $fontSize, $expectedStyleSolid, $initialPropsSolid);
            $this->testLengthProperty("{$prop}_width", $value, $fontSize, $expectedStyleNone, $initialPropsNone);
        }
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
            ["'attr(title)'", [new StringPart("attr(title)")]],
            ['"url(\'image.png\')"', [new StringPart("url('image.png')")]],

            // Attr
            ["attr(title)", [new Attr("title")]],

            // Url
            ["url(image.png)", [new Url("image.png")]],
            ['url("image.png")', [new Url("image.png")]],
            ["url('image.png')", [new Url("image.png")]],
            ["url(\"'image.PNG'\")", [new Url("'image.PNG'")]],

            // Counter/Counters
            ["counter(c)", [new Counter("c", "decimal")]],
            ["counter(UPPER, UPPER-roman)", [new Counter("UPPER", "upper-roman")]],
            ["counters(c, '')", [new Counters("c", "", "decimal")]],
            ["counters(c, '', decimal)", [new Counters("c", "", "decimal")]],
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
}
