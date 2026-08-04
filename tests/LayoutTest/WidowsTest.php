<?php
namespace Dompdf\Tests\LayoutTest;

use DOMElement;
use Dompdf\Canvas;
use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameDecorator\Block;
use Dompdf\Options;
use Dompdf\Tests\TestCase;

class WidowsTest extends TestCase
{
    /**
     * Build a fixture with a spacer above a paragraph of `<br>`-separated
     * lines, so the number of line boxes is independent of font metrics.
     *
     * The page is 400pt high without margins. With a line height of 30pt on a
     * 12pt font, each line box is roughly 30.5pt tall.
     */
    private static function fixture(
        string $spacerHeight,
        $lines,
        string $paragraphStyle,
        string $extraContent = "",
        string $extraStyle = ""
    ): string {
        if (is_int($lines)) {
            $lines = implode("<br>", array_map(function ($i) {
                return "Line $i";
            }, range(1, $lines)));
        }

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page {
        size: 400pt 400pt;
        margin: 0;
    }

    body {
        margin: 0;
        font-size: 12pt;
        line-height: 30pt;
    }

    .spacer {
        height: $spacerHeight;
    }

    p {
        margin: 0;
        $paragraphStyle
    }

    $extraStyle
</style>
</head>
<body><div class="spacer"></div><p class="para">$lines</p>$extraContent</body>
</html>
HTML;
    }

    public static function widowsProvider(): array
    {
        return [
            // 4 lines fit below the spacer, so the natural page break leaves
            // a single line on the second page. `widows: 3` has to move the
            // break up by two lines, which `orphans: 2` still allows
            "single widow pulled back" => [
                self::fixture("270pt", 5, "orphans: 2; widows: 3;"),
                2,
                [1 => 2, 2 => 3]
            ],
            // Only 3 lines fit below the spacer. With 4 lines total,
            // `orphans: 3` and `widows: 3` cannot both be satisfied, so the
            // whole paragraph moves to the next page
            "orphans and widows jointly unsatisfiable" => [
                self::fixture("300pt", 4, "orphans: 3; widows: 3;"),
                2,
                [2 => 4]
            ],
            // 4 of 6 lines fit naturally, leaving 2 on the second page.
            // `widows: 3` moves the break up by one line, down to the
            // `orphans: 3` limit
            "widows moved up to orphans limit" => [
                self::fixture("270pt", 6, "orphans: 3; widows: 3;"),
                2,
                [1 => 3, 2 => 3]
            ],
            // Without a `widows` declaration pagination is unchanged: the
            // natural break applies, even though it leaves a single line on
            // the second page
            "default widows keeps pagination" => [
                self::fixture("270pt", 5, ""),
                2,
                [1 => 4, 2 => 1]
            ],
            "widows zero keeps pagination" => [
                self::fixture("270pt", 5, "widows: 0;"),
                2,
                [1 => 4, 2 => 1]
            ],
            // The paragraph fits on the first page, but the following block
            // does not and disallows a page break before itself, so the break
            // has to backtrack into the already laid-out paragraph. Breaking
            // before its last line would violate `widows: 2`
            "widows in previous block" => [
                self::fixture(
                    "270pt",
                    4,
                    "orphans: 2; widows: 2; page-break-before: avoid;",
                    '<div style="height: 60pt; page-break-before: avoid;">X</div>'
                ),
                2,
                [1 => 2, 2 => 2]
            ],
            // With 4 lines total, `orphans: 3` and `widows: 3` cannot both be
            // satisfied when backtracking into the paragraph, and a break
            // before the paragraph is not allowed either, so the following
            // block moves to the next page on its own and the paragraph stays
            // intact. In particular, the break must not land after the first
            // line, which would satisfy widows by violating orphans
            "orphans in previous block" => [
                self::fixture(
                    "270pt",
                    4,
                    "orphans: 3; widows: 3; page-break-before: avoid;",
                    '<div style="height: 60pt; page-break-before: avoid;">X</div>'
                ),
                2,
                [1 => 4]
            ],
            // The line box produced by the consecutive `<br>` contains no
            // frame to break at. Breaking below it would violate `widows: 3`,
            // breaking above it would violate `orphans: 2`, so the whole
            // paragraph moves to the next page
            "empty line box at break target" => [
                self::fixture(
                    "270pt",
                    "Line 1<br>Line 2<br><br>Line 4<br>Line 5",
                    "orphans: 2; widows: 3;"
                ),
                2,
                [2 => 4]
            ],
            // The widows constraint cannot be satisfied on any page: the
            // paragraph is pushed to the second page whole, still overflows
            // there, and must not be pushed again and again. The fixed header
            // keeps a preceding sibling on every page, so pagination must not
            // rely on the body's-first-child safeguard alone
            "unsatisfiable widows terminate" => [
                self::fixture(
                    "270pt",
                    15,
                    "orphans: 2; widows: 15;",
                    '<div class="header">H</div>',
                    ".header { position: fixed; top: 0pt; left: 0pt; }"
                ),
                3,
                [2 => 13, 3 => 2]
            ],
        ];
    }

    /**
     * @dataProvider widowsProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('widowsProvider')]
    public function testWidows(
        string $html,
        int $pageCount,
        array $expectedParagraphLines
    ): void {
        $paragraphLines = [];

        $options = new Options();

        // Use callback to count the paragraph's line boxes on each page
        $dompdf = new Dompdf($options);
        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame, Canvas $canvas) use (&$paragraphLines) {
                    $node = $frame->get_node();

                    if (!($node instanceof DOMElement)
                        || $node->getAttribute("class") !== "para"
                        || !($frame instanceof Block)
                    ) {
                        return;
                    }

                    $lines = array_filter($frame->get_line_boxes(), function ($line) {
                        return !$line->is_empty();
                    });

                    $paragraphLines[$canvas->get_page_number()] = count($lines);
                }
            ]
        ]);

        $dompdf->loadHtml($html);
        $dompdf->render();

        $this->assertSame($pageCount, $dompdf->getCanvas()->get_page_count());
        $this->assertSame($expectedParagraphLines, $paragraphLines);
    }

    /**
     * A float following the deferred break moves to the next page with it
     * and must not offset the line boxes kept on the current page.
     */
    public function testFloatAfterDeferredBreak(): void
    {
        $html = self::fixture(
            "270pt",
            'Line 1<br>Line 2<br>Line 3<br>Line 4<br>Line 5'
                . '<span style="float: left; width: 100pt; height: 50pt;"></span>',
            "orphans: 2; widows: 3;"
        );

        $paragraphLines = [];
        $lineOffsets = [];

        $dompdf = new Dompdf(new Options());
        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame, Canvas $canvas) use (&$paragraphLines, &$lineOffsets) {
                    $node = $frame->get_node();

                    if (!($node instanceof DOMElement)
                        || $node->getAttribute("class") !== "para"
                        || !($frame instanceof Block)
                    ) {
                        return;
                    }

                    $page = $canvas->get_page_number();
                    $paragraphLines[$page] = 0;

                    foreach ($frame->get_line_boxes() as $line) {
                        if ($line->is_empty()) {
                            continue;
                        }

                        $paragraphLines[$page]++;
                        $lineOffsets[$page][] = $line->left;
                    }
                }
            ]
        ]);

        $dompdf->loadHtml($html);
        $dompdf->render();

        $this->assertSame([1 => 2, 2 => 3], $paragraphLines);
        $this->assertSame([0.0, 0.0], $lineOffsets[1]);
    }

    /**
     * A float that does not fit next to its line during the speculative
     * layout of a deferred break must reflow from clean state on the next
     * page, where it might fit. The first page is narrowed via its page
     * margin, while the second page offers the full width.
     */
    public function testFloatStateResetAfterDeferredBreak(): void
    {
        $html = self::fixture(
            "270pt",
            'Line 1<br>Line 2<br>Line 3<br>Line 4<br>Line 5'
                . '<span class="fl" style="float: left; width: 190pt; height: 30pt;"></span>',
            "orphans: 2; widows: 3;",
            "",
            "@page :right { margin-right: 200pt; }"
        );

        $floatPositions = [];
        $lineTops = [];

        $dompdf = new Dompdf(new Options());
        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame, Canvas $canvas) use (&$floatPositions, &$lineTops) {
                    $node = $frame->get_node();

                    if (!($node instanceof DOMElement)) {
                        return;
                    }

                    $page = $canvas->get_page_number();

                    if ($node->getAttribute("class") === "fl") {
                        $floatPositions[$page] = $frame->get_position("y");
                    }

                    if ($node->getAttribute("class") === "para" && $frame instanceof Block) {
                        foreach ($frame->get_line_boxes() as $line) {
                            if (!$line->is_empty()) {
                                $lineTops[$page][] = $line->y;
                            }
                        }
                    }
                }
            ]
        ]);

        $dompdf->loadHtml($html);
        $dompdf->render();

        // The float moves to the second page with the deferred break and fits
        // next to its line there
        $this->assertSame([2], array_keys($floatPositions));
        $this->assertCount(3, $lineTops[2]);
        $this->assertSame($lineTops[2][2], $floatPositions[2]);
    }
}
