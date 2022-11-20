<?php
namespace Dompdf\Tests\Css;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Dompdf\Css\Stylesheet;
use Dompdf\Dompdf;
use Dompdf\Frame\FrameTree;
use Dompdf\Tests\TestCase;

class SelectorTest extends TestCase
{
    private function stylesheet()
    {
        return new class(new Dompdf()) extends Stylesheet {
            public function specificity(string $selector, int $origin = self::ORIG_AUTHOR): int
            {
                return parent::specificity($selector, $origin);
            }

            public function selectorToXpath(string $selector, bool $firstPass = false): ?array
            {
                return parent::selectorToXpath($selector, $firstPass);
            }
        };
    }

    private function preProcess(string $selector): string
    {
        // Pre-process the selector string the same way the `Stylesheet` class
        // does before passing it to `selectorToXpath`.
        // See `Stylesheet::_parse_sections()`
        $patterns = ["/\s+/", "/\s+([>.:+~#])\s+/"];
        $replacements = [" ", "\\1"];

        return preg_replace($patterns, $replacements, $selector);
    }

    public function selectorMatchesProvider(): array
    {
        // Elements expected to matched by each selector are marked with the
        // attribute `data-match`. The optional third parameter defines whether
        // the root element (`html`) is expected to be matched
        return [
            // Next-sibling combinator
            "next sibling 1" => [
                "h1 + p",
                '<body>
                    <h1></h1>
                    <p data-match></p>
                    <p></p>
                    <h1></h1>
                    <div></div>
                    <p></p>
                </body>'
            ],
            "next sibling 2" => [
                "h1 + .child",
                '<body>
                    <h1></h1>
                    <p></p>
                    <p class="child"></p>
                    <h1></h1>
                    text
                    <p class="child" data-match></p>
                    <p></p>
                </body>'
            ],
            "next sibling 3" => [
                "h1 + p.child",
                '<body>
                    <h1></h1>
                    <p></p>
                    <p class="child"></p>
                    <h1></h1>
                    <p class="child" data-match></p>
                    <p></p>
                </body>'
            ],

            // Following-sibling combinator
            "following sibling 1" => [
                "h1 ~ p",
                '<body>
                    <h1></h1>
                    <p data-match></p>
                    <p data-match></p>
                    <div>
                        <p></p>
                    </div>
                    <p data-match></p>
                </body>'
            ],
            "following sibling 2" => [
                "h1 ~ .child",
                '<body>
                    <h1></h1>
                    <p class="child" data-match></p>
                    <p class="child" data-match></p>
                </body>'
            ],
            "following sibling 3" => [
                "h1 ~ p.child",
                '<body>
                    <h1></h1>
                    <p></p>
                    <div class="child"></div>
                </body>'
            ],

            // ID selector
            "id 1" => [
                "#test",
                '<body><p id="test" data-match></p></body>'
            ],
            "id 2" => [
                "p#test",
                '<body><p id="test" data-match></p></body>'
            ],
            "id 3" => [
                "div#test",
                '<body><p id="test"></p></body>'
            ],
            "id 4" => [
                "#test",
                '<body><p id="test test"></p></body>'
            ],

            // Class selector
            "class 1" => [
                ".test",
                '<body><p class="test" data-match></p></body>'
            ],
            "class 2" => [
                "p.test",
                '<body>
                    <p class="test more" data-match></p>
                    <div class="test"></div>
                </body>'
            ],
            "class 3" => [
                ".test.more",
                '<body>
                    <p class="test more" data-match></p>
                    <div class="
                    more
                    test
                    " data-match></div>
                </body>'
            ],

            // root
            "root 1" => [
                ":root",
                '<body><div></div></body>',
                true
            ],
            "root 2" => [
                "body:root",
                '<body><div></div></body>',
                false
            ],

            // first-child
            "first-child 1" => [
                ":first-child",
                '<body>
                    <div data-match>
                        <p data-match></p>
                        <p></p>
                    </div>
                    <div>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>',
                true
            ],
            "first-child 2" => [
                "body :first-child",
                '<body>
                    <div data-match>
                        <p data-match></p>
                        <p></p>
                    </div>
                    <div>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>'
            ],

            // last-child
            "last-child 1" => [
                ":last-child",
                '<body data-match>
                    <div>
                        <p></p>
                        <p data-match></p>
                    </div>
                    <div data-match>
                        <p></p>
                        <p data-match></p>
                    </div>
                </body>',
                true
            ],
            "last-child 2" => [
                "body :last-child",
                '<body>
                    <div>
                        <p></p>
                        <p data-match></p>
                    </div>
                    <div data-match>
                        <p></p>
                        <p data-match></p>
                    </div>
                </body>'
            ],

            // only-child
            "only-child" => [
                ":only-child",
                '<body>
                    <div>
                        <p></p>
                        <p></p>
                    </div>
                    <div>
                        <p data-match></p>
                    </div>
                </body>',
                true
            ],

            // nth-child
            "nth-child odd" => [
                "div > :nth-child(odd)",
                '<body>
                    <div>
                        <p data-match></p>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                        <p data-match></p>
                    </div>
                    <div>
                        <p data-match></p>
                        <div></div>
                        <div data-match></div>
                        <p></p>
                        <div data-match></div>
                        <p></p>
                    </div>
                </body>'
            ],
            "nth-child even" => [
                "div > *:nth-child(even)",
                '<body>
                    <div>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                    </div>
                    <div>
                        <p></p>
                        <p data-match></p>
                        <div></div>
                        <div data-match></div>
                        <p></p>
                        <div data-match></div>
                    </div>
                </body>'
            ],
            "nth-child 1" => [
                ":nth-child(1)",
                '<body>
                    <div data-match>
                        <p data-match></p>
                        <p></p>
                    </div>
                    <div>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>',
                true
            ],
            "nth-child 2" => [
                ":nth-child(2)",
                '<body data-match>
                    <div>
                        <p></p>
                        <p data-match></p>
                    </div>
                    <div data-match>
                        <div></div>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>'
            ],
            "nth-child 3" => [
                "div > p:nth-child(3n + 1)",
                '<body>
                    <div>
                        <p data-match></p>
                        <p></p>
                        <p></p>
                        <div></div>
                        <p></p>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                        <p></p>
                        <p data-match></p>
                    </div>
                    <div>
                        <p data-match></p>
                    </div>
                </body>'
            ],
            "nth-child 4" => [
                ".child:nth-child(2n + 2)",
                '<body>
                    <div>
                        <p class="child"></p>
                        <p class="child" data-match></p>
                        <p class="child"></p>
                        <p></p>
                        <p class="child"></p>
                        <div class="child" data-match></div>
                    </div>
                    <div>
                        <p class="child"></p>
                    </div>
                </body>'
            ],
            "nth-child 5" => [
                ".child:nth-child(n + 4)",
                '<body>
                    <div>
                        <p class="child"></p>
                        <p></p>
                        <p class="child"></p>
                        <p class="child" data-match></p>
                        <p class="child" data-match></p>
                    </div>
                    <div>
                        <p class="child"></p>
                    </div>
                </body>'
            ],
            "nth-child 6" => [
                "div > :nth-child(-n + 3)",
                '<body>
                    <div>
                        <p data-match></p>
                        <p data-match></p>
                        <div data-match></div>
                        <div></div>
                        <p></p>
                    </div>
                    <div>
                        <p data-match></p>
                    </div>
                </body>'
            ],

            // nth-last-child
            "nth-last-child 1" => [
                "body :nth-last-child(2)",
                '<body>
                    <div data-match>
                        <p data-match></p>
                        <p></p>
                    </div>
                    <div>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>'
            ],
            "nth-last-child 2" => [
                "body :nth-last-child(-2n + 4)",
                '<body>
                    <div data-match>
                        <p></p>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                    </div>
                    <div>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>'
            ],

            // first-of-type
            "first-of-type 1" => [
                "p:first-of-type",
                '<body>
                    <div>
                        <p data-match></p>
                        <p></p>
                    </div>
                    <div>
                        <div></div>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>'
            ],
            "first-of-type 2" => [
                "body p:first-of-type",
                '<body>
                    <div>
                        <p data-match></p>
                        <p></p>
                    </div>
                    <div>
                        <div></div>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>'
            ],

            // last-of-type
            "last-of-type 1" => [
                "p:last-of-type",
                '<body>
                    <div>
                        <p></p>
                        <p data-match></p>
                    </div>
                    <div>
                        <p></p>
                        <p data-match></p>
                        <div></div>
                    </div>
                </body>'
            ],
            "last-of-type 2" => [
                "body p:last-of-type",
                '<body>
                    <div>
                        <p></p>
                        <p data-match></p>
                    </div>
                    <div>
                        <p></p>
                        <p data-match></p>
                        <div></div>
                    </div>
                </body>'
            ],

            // only-of-type
            "only-of-type" => [
                "p:only-of-type",
                '<body>
                    <div>
                        <p></p>
                        <p></p>
                    </div>
                    <div>
                        <div></div>
                        <p data-match></p>
                        <div></div>
                    </div>
                </body>'
            ],

            // nth-of-type
            "nth-of-type odd" => [
                "div > p:nth-of-type(odd)",
                '<body>
                    <div>
                        <div></div>
                        <p data-match></p>
                        <div></div>
                        <p></p>
                        <div></div>
                        <p data-match></p>
                    </div>
                    <div>
                        <p data-match></p>
                        <div></div>
                        <p></p>
                        <div></div>
                        <p data-match></p>
                        <div></div>
                    </div>
                </body>'
            ],
            "nth-of-type even" => [
                "div > p:nth-of-type(even)",
                '<body>
                    <div>
                        <div></div>
                        <p></p>
                        <div></div>
                        <p data-match></p>
                        <div></div>
                    </div>
                    <div>
                        <div></div>
                        <p></p>
                        <div></div>
                        <p data-match></p>
                        <div></div>
                        <p></p>
                    </div>
                </body>'
            ],
            "nth-of-type 1" => [
                "p:nth-of-type(1)",
                '<body>
                    <div>
                        <div></div>
                        <p data-match></p>
                        <p></p>
                    </div>
                    <div>
                        <div></div>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>'
            ],
            "nth-of-type 2" => [
                "p:nth-of-type(2)",
                '<body>
                    <div>
                        <p></p>
                        <div></div>
                        <p data-match></p>
                    </div>
                    <div>
                        <div></div>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>'
            ],
            "nth-of-type 3" => [
                "div > p:nth-of-type(3n + 1)",
                '<body>
                    <div>
                        <div></div>
                        <div></div>
                        <p data-match></p>
                        <div></div>
                        <div></div>
                        <p></p>
                        <div></div>
                        <div></div>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                        <p></p>
                        <p data-match></p>
                    </div>
                    <div>
                        <div></div>
                        <p data-match></p>
                    </div>
                </body>'
            ],
            "nth-of-type 4" => [
                "p.child:nth-of-type(2n + 2)",
                '<body>
                    <div>
                        <div class="child"></div>
                        <p></p>
                        <p class="child" data-match></p>
                        <p class="child"></p>
                        <p></p>
                        <div></div>
                        <p class="child"></p>
                        <p class="child" data-match></p>
                        <p class="child"></p>
                    </div>
                    <div>
                        <p class="child"></p>
                        <p class="child" data-match></p>
                    </div>
                </body>'
            ],

            // nth-last-of-type
            "nth-last-of-type 1" => [
                "p:nth-last-of-type(2)",
                '<body>
                    <div>
                        <p data-match></p>
                        <p></p>
                        <div></div>
                    </div>
                    <div>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>'
            ],
            "nth-last-of-type 2" => [
                "p:nth-last-of-type(-2n + 4)",
                '<body>
                    <div>
                        <p></p>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                        <p data-match></p>
                        <p></p>
                        <div></div>
                    </div>
                    <div>
                        <p data-match></p>
                        <p></p>
                    </div>
                </body>'
            ],

            // link
            "link" => [
                ":link",
                '<body><a href="https://example.com" data-match></a></body>'
            ],
            "any-link" => [
                ":any-link",
                '<body><a href="https://example.com" data-match></a></body>'
            ],
            "visited" => [
                ":visited",
                '<body><a href="https://example.com"></a></body>'
            ],

            // attribute ~=
            "attribute ~= 1" => [
                '[title~="multiple"]',
                '<body><div title="multiple tokens" data-match></div></body>'
            ],
            "attribute ~= 2" => [
                '[title~="mult"]',
                '<body><div title="multiple tokens"></div></body>'
            ],
            "attribute ~= 3" => [
                '[title~="multiple tokens"]',
                '<body><div title="multiple tokens"></div></body>'
            ],
            "attribute ~= 4" => [
                '[title~=""]',
                '<body><div title="multiple tokens"></div><div title=""></div></body>'
            ],

            // attribute ^=
            "attribute ^= 1" => [
                '[title^="multiple"]',
                '<body><div title="multiple tokens" data-match></div></body>'
            ],
            "attribute ^= 2" => [
                '[title^="mult"]',
                '<body><div title="multiple tokens" data-match></div></body>'
            ],
            "attribute ^= 3" => [
                '[title^="kens"]',
                '<body><div title="multiple tokens"></div></body>'
            ],
            "attribute ^= 4" => [
                '[title^="le t"]',
                '<body><div title="multiple tokens"></div></body>'
            ],
            "attribute ^= 5" => [
                '[title^="multiple tokens"]',
                '<body><div title="multiple tokens" data-match></div></body>'
            ],
            "attribute ^= 6" => [
                '[title^=""]',
                '<body><div title="multiple tokens"></div><div title=""></div></body>'
            ],

            // attribute $=
            "attribute $= 1" => [
                '[title$="multiple"]',
                '<body><div title="multiple tokens"></div></body>'
            ],
            "attribute $= 2" => [
                '[title$="mult"]',
                '<body><div title="multiple tokens"></div></body>'
            ],
            "attribute $= 3" => [
                '[title$="kens"]',
                '<body><div title="multiple tokens" data-match></div></body>'
            ],
            "attribute $= 4" => [
                '[title$="le t"]',
                '<body><div title="multiple tokens"></div></body>'
            ],
            "attribute $= 5" => [
                '[title$="multiple tokens"]',
                '<body><div title="multiple tokens" data-match></div></body>'
            ],
            "attribute $= 6" => [
                '[title$=""]',
                '<body><div title="multiple tokens"></div><div title=""></div></body>'
            ],

            // attribute *=
            "attribute *= 1" => [
                '[title*="multiple"]',
                '<body><div title="multiple tokens" data-match></div></body>'
            ],
            "attribute *= 2" => [
                '[title*="mult"]',
                '<body><div title="multiple tokens" data-match></div></body>'
            ],
            "attribute *= 3" => [
                '[title*="kens"]',
                '<body><div title="multiple tokens" data-match></div></body>'
            ],
            "attribute *= 4" => [
                '[title*="le t"]',
                '<body><div title="multiple tokens" data-match></div></body>'
            ],
            "attribute *= 5" => [
                '[title*="multiple tokens"]',
                '<body><div title="multiple tokens" data-match></div></body>'
            ],
            "attribute *= 6" => [
                '[title*=""]',
                '<body><div title="multiple tokens"></div><div title=""></div></body>'
            ],
        ];
    }

    /**
     * @dataProvider selectorMatchesProvider
     */
    public function testSelectorMatches(
        string $selector,
        string $body,
        bool $matchRoot = false
    ): void {
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
            $name = $node->nodeName;

            // Skip text nodes and head
            if (!($node instanceof DOMElement) || $name === "head") {
                continue;
            }

            $shouldMatch = $name === "html"
                ? $matchRoot
                : $node->hasAttribute("data-match");
            $matches = in_array($node, $nodes, true);

            $failureMessage = $shouldMatch
                ? "Node $name should be matched by selector."
                : "Node $name should not be matched by selector.";

            $this->assertSame($shouldMatch, $matches, $failureMessage);
        }
    }

    public function selectorInvalidProvider(): array
    {
        return [
            // Valid but unsupported selector syntax
            [".w-\[var\(--sidebar-width\)\]"],

            // Invalid selectors
            [":unknown"],
            ["p:unknown"],
            ["[d~]"],
            ["[href=x"],
            ["[href"]
        ];
    }

    /**
     * @dataProvider selectorInvalidProvider
     */
    public function testSelectorInvalid(string $selector): void
    {
        $sheet = $this->stylesheet();
        $query = $sheet->selectorToXpath($this->preProcess($selector));

        $this->assertNull($query);
    }
}
