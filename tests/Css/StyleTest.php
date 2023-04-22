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
    public function lengthInPtProvider(): array
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
            ["50%", 360, 180.0]
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

    public function cssImageBasicProvider(): array
    {
        return [
            "no value" => ["", "none"],
            "keyword none" => ["none", "none"],
            "bare url" => ["http://example.com/test.png", "none"],
            "http" => ["url(http://example.com/test.png)", "http://example.com/test.png"],
            "case" => ["URL(http://example.com/Test.png)", "http://example.com/Test.png"]
        ];
    }

    public function cssImageNoBaseHrefProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        return [
            "local absolute" => ["url($basePath/_files/jamaica.jpg)", "file://" . $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"],
            "local relative" => ["url(../_files/jamaica.jpg)", "file://" . $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"]
        ];
    }

    public function cssImageWithBaseHrefProvider(): array
    {
        $basePath = realpath(__DIR__ . "/..");
        return [
            "local absolute" => ["url($basePath/_files/jamaica.jpg)", "file://" . $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"],
            "local relative" => ["url(../_files/jamaica.jpg)", "file://" . $basePath . DIRECTORY_SEPARATOR . "_files" . DIRECTORY_SEPARATOR . "jamaica.jpg"]
        ];
    }

    public function cssImageWithStylesheetBaseHrefProvider(): array
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

    public function widthHeightProvider(): array
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

    public function minWidthHeightProvider(): array
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

    public function maxWidthHeightProvider(): array
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

    public function lineWidthProvider(): array
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

    public function counterIncrementProvider(): array
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

    public function counterResetProvider(): array
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

    public function quotesProvider(): array
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

    public function contentProvider(): array
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

    public function sizeProvider(): array
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

    public function opacityProvider(): array
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

    public function zIndexProvider(): array
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
