<?php
namespace Dompdf\Tests\Css;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Dompdf\Css\Stylesheet;
use Dompdf\Dompdf;
use Dompdf\Frame\FrameTree;
use Dompdf\Tests\TestCase;

final class HasSelectorTest extends TestCase
{
    private function stylesheet()
    {
        return new class(new Dompdf()) extends Stylesheet {
            public function selectorToXpath(string $selector, bool $firstPass = false): ?array
            {
                return parent::selectorToXpath($selector, $firstPass);
            }
        };
    }

    private function preProcess(string $selector): string
    {
        $patterns = ["/\s+/", "/\s+([>.:+~#])\s+/"];
        $replacements = [" ", "\\1"];

        return preg_replace($patterns, $replacements, $selector);
    }

    public static function selectorMatchesProvider(): array
    {
        return [
            "direct child first-child" => [
                "li:has(> strong:first-child)",
                '<body><ol>
                    <li data-match><strong>Title</strong><span>Text</span></li>
                    <li><span>Text</span><strong>Title</strong></li>
                    <li><em>Text</em></li>
                </ol></body>'
            ],
            "descendant" => [
                "div.wrapper:has(.target)",
                '<body>
                    <div class="wrapper" data-match><div><span class="target"></span></div></div>
                    <div class="wrapper"><div><span></span></div></div>
                </body>'
            ],
            "adjacent sibling" => [
                "h2:has(+ p.note)",
                '<body>
                    <h2 data-match></h2><p class="note"></p>
                    <h2></h2><div></div><p class="note"></p>
                </body>'
            ],
            "subsequent sibling" => [
                "h2:has(~ p.note)",
                '<body>
                    <div><h2 data-match></h2><div></div><p class="note"></p></div>
                    <div><h2></h2><div></div></div>
                </body>'
            ],
            "selector list" => [
                "div.wrapper:has(> img.hero, > p.featured)",
                '<body>
                    <div class="wrapper" data-match><img class="hero"></div>
                    <div class="wrapper" data-match><p class="featured"></p></div>
                    <div class="wrapper"><img></div>
                </body>'
            ],
            "nested functional pseudo-class" => [
                "div:has(> span:nth-child(2))",
                '<body>
                    <div data-match><em></em><span></span></div>
                    <div><span></span><em></em></div>
                </body>'
            ],
            "compound relative selector" => [
                "li:has(> strong em.highlight)",
                '<body><ul>
                    <li data-match><strong><span><em class="highlight"></em></span></strong></li>
                    <li><strong><em></em></strong></li>
                </ul></body>'
            ],
            "attribute selector" => [
                "form:has(> input[checked])",
                '<body>
                    <form data-match><input checked></form>
                    <form><input></form>
                </body>'
            ]
        ];
    }

    /**
     * @dataProvider selectorMatchesProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('selectorMatchesProvider')]
    public function testSelectorMatches(string $selector, string $body): void
    {
        $sheet = $this->stylesheet();
        $dom = new DOMDocument();
        $dom->loadHTML("<html><head></head>$body</html>");
        $tree = new FrameTree($dom);
        $tree->build_tree();
        $xpath = new DOMXPath($dom);

        $query = $sheet->selectorToXpath($this->preProcess($selector));
        $this->assertNotNull($query);
        $nodeList = $xpath->query($query["query"]);
        $this->assertNotFalse($nodeList);
        $nodes = iterator_to_array($nodeList);

        foreach ($tree as $frame) {
            $node = $frame->get_node();
            if (!($node instanceof DOMElement) || $node->nodeName === "head") {
                continue;
            }

            $this->assertSame(
                $node->hasAttribute("data-match"),
                in_array($node, $nodes, true),
                "Unexpected match state for {$node->nodeName}."
            );
        }
    }

    public static function invalidSelectorProvider(): array
    {
        return [
            ["div:has()"],
            ["div:has(:has(.target))"],
            ["div:has(> p::before)"]
        ];
    }

    /**
     * @dataProvider invalidSelectorProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('invalidSelectorProvider')]
    public function testInvalidHasSelector(string $selector): void
    {
        $sheet = $this->stylesheet();
        $this->assertNull($sheet->selectorToXpath($this->preProcess($selector)));
    }
}
