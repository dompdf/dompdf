<?php
namespace Dompdf\Tests;

use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\Tests\TestCase;

final class GeneratedContentTest extends TestCase
{
    public static function countersProvider(): array
    {
        return [
            // TODO: Heredocs can be nicely indented starting with PHP 7.3
            "basic counter" => [
                <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
span {
    counter-increment: c;
}

span::before {
    content: counter(c) "-";
}
</style>
</head>
<body>
    <div><span></span><span></span><span></span></div>
</body>
</html>
HTML
,
                [
                    "div" => ["1-2-3-"]
                ]
            ],
            "nested counters" => [
                <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
ul {
    counter-reset: li;
}

li {
    counter-increment: li;
}

span::before {
    content: counters(li, ".") " ";
}
</style>
</head>
<body>
<ul>
    <li><span>Item 1</span></li>
    <li><span>Item 2</span>
        <ul>
            <li><span>Item 3</span></li>
            <li><span>Item 4</span></li>
            <li><span>Item 5</span></li>
        </ul>
    </li>
    <li><span>Item 6</span></li>
</ul>
</body>
</html>
HTML
,
                [
                    "span" => [
                        "1 Item 1",
                        "2 Item 2",
                        "2.1 Item 3",
                        "2.2 Item 4",
                        "2.3 Item 5",
                        "3 Item 6"
                    ]
                ]
            ],
            "auto reset nested" => [
                <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
li {
    counter-increment: c1;
}

li li {
    counter-increment: c2;
}

span::before {
    content: counter(c1) "|" counter(c2) " ";
}
</style>
</head>
<body>
<ul>
    <li><span>Item 1</span></li>
    <li><span>Item 2</span>
        <ul>
            <li><span>Item 3</span></li>
            <li><span>Item 4</span></li>
            <li><span>Item 5</span></li>
        </ul>
    </li>
    <li><span>Item 6</span>
        <ul>
            <li><span>Item 7</span></li>
            <li><span>Item 8</span></li>
        </ul>
    </li>
</ul>
</body>
</html>
HTML
,
                [
                    "span" => [
                        "1|0 Item 1",
                        "2|0 Item 2",
                        "2|1 Item 3",
                        "2|2 Item 4",
                        "2|3 Item 5",
                        "3|0 Item 6",
                        "3|1 Item 7",
                        "3|2 Item 8"
                    ]
                ]
            ],
            // Note: There have been spec changes in regards to how `counter-reset`
            // is supposed to work in cases like the following. Firefox 82+
            // behaves differently here, dompdf is consistent with other browsers
            // and older Firefox versions:
            // * https://github.com/mdn/content/issues/13293
            // * https://github.com/w3c/csswg-drafts/issues/5477
            "sibling reset" => [
                <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
body {
    counter-reset: c;
}

li {
    counter-increment: c;
}

li::before {
    content: counters(c, ".") " ";
}

.reset {
    counter-reset: c;
}
</style>
</head>
<body>
    <ul>
        <li>Item 1</li>
        <li class="reset">Item 2</li>
        <li>Item 3</li>
        <li class="reset">Item 4</li>
        <li>Item 5</li>
    </ul>

    <ul>
        <li>Item 6</li>
        <li class="reset">Item 7</li>
        <li>Item 8</li>
    </ul>
</body>
</html>
HTML
,
                [
                    "li" => [
                        "1 Item 1",
                        "1.1 Item 2",
                        "1.2 Item 3",
                        "1.1 Item 4",
                        "1.2 Item 5",
                        "2 Item 6",
                        "2.1 Item 7",
                        "2.2 Item 8"
                    ]
                ]
            ],

            // Tests from the CSS2.1 Conformance Test Suite
            // http://test.csswg.org/suites/css21_dev/20110323/
            "counters-scope-000" => [
                <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
 <head>
  <title>CSS Test: Counter scope</title>
  <link rel="author" title="L. David Baron" href="http://dbaron.org/">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#scope">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#counters">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#propdef-content">
  <link rel="help" href="http://www.w3.org/TR/CSS21/syndata.html#counter">
  <style type="text/css">

  body { white-space: nowrap; }


  .scope { counter-reset: c 1; }
  .scope:before, .scope:after { content: counter(c); }
  .c:before { content: counter(c); }

  .one:before { counter-reset: c 2; }
  .two { counter-reset: c 3; }

  </style>
 </head>
 <body>

 <p>The next 2 lines should be identical:</p>

 <div>
   <span class="scope"><span class="one c"><span class="c"></span></span><span class="c"></span></span><span class="c"></span>
   <span class="scope"><span class="two c"><span class="c"></span></span><span class="c"></span></span><span class="c"></span>
 </div>

 <div>
   122111
   133331
 </div>

 </body>
</html>
HTML
,
                [
                    "div" => [
                        "122111 133331",
                        "122111 133331"
                    ]
                ]
            ],
            "counters-scope-001" => [
                <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
 <head>
  <title>CSS Test: Counter scope and nesting on elements</title>
  <link rel="author" title="L. David Baron" href="http://dbaron.org/">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#scope">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#counters">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#propdef-content">
  <link rel="help" href="http://www.w3.org/TR/CSS21/syndata.html#counter">
  <style type="text/css">

  body { white-space: nowrap; }


  span:before { counter-increment: c 1; content: "B" counters(c,".") "-" }
  span:after  { counter-increment: c 1; content: "A" counters(c,".") "-" }

  body, span#reset { counter-reset: c 0; }

  </style>
 </head>
 <body>

 <p>The following two lines should be the same:</p>

 <div><span><span><span id="reset"><span></span><span></span></span><span><span></span></span></span></span></div>
 <div>B1-B2-B2.1-B2.2-A2.3-B2.4-A2.5-A2.6-B2.7-B2.8-A2.9-A2.10-A2.11-A3-</div>

 </body>
</html>
HTML
,
                [
                    "div" => [
                        "B1-B2-B2.1-B2.2-A2.3-B2.4-A2.5-A2.6-B2.7-B2.8-A2.9-A2.10-A2.11-A3-",
                        "B1-B2-B2.1-B2.2-A2.3-B2.4-A2.5-A2.6-B2.7-B2.8-A2.9-A2.10-A2.11-A3-"
                    ]
                ]
            ],
            "counters-scope-002" => [
                <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
 <head>
  <title>CSS Test: Counter scope and nesting on :before</title>
  <link rel="author" title="L. David Baron" href="http://dbaron.org/">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#scope">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#counters">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#propdef-content">
  <link rel="help" href="http://www.w3.org/TR/CSS21/syndata.html#counter">
  <style type="text/css">

  body { white-space: nowrap; }


  span:before { counter-increment: c 1; content: "B" counters(c,".") "-" }
  span:after  { counter-increment: c 1; content: "A" counters(c,".") "-" }

  body, span#reset:before { counter-reset: c 0; }

  </style>
 </head>
 <body>

 <p>The following two lines should be the same:</p>

 <div><span><span id="reset"><span></span></span></span></div>
 <div>B1-B1.1-B1.2-A1.3-A1.4-A2-</div>

 </body>
</html>
HTML
,
                [
                    "div" => [
                        "B1-B1.1-B1.2-A1.3-A1.4-A2-",
                        "B1-B1.1-B1.2-A1.3-A1.4-A2-"
                    ]
                ]
            ],
            "counters-scope-003" => [
                <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
 <head>
  <title>CSS Test: Counter scope and nesting on :after</title>
  <link rel="author" title="L. David Baron" href="http://dbaron.org/">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#scope">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#counters">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#propdef-content">
  <link rel="help" href="http://www.w3.org/TR/CSS21/syndata.html#counter">
  <style type="text/css">

  body { white-space: nowrap; }


  span:before { counter-increment: c 1; content: "B" counters(c,".") "-" }
  span:after  { counter-increment: c 1; content: "A" counters(c,".") "-" }

  body, span#reset:after { counter-reset: c 0; }

  </style>
 </head>
 <body>

 <p>The following two lines should be the same:</p>

 <div><span><span id="reset"><span></span></span></span></div>
 <div>B1-B2-B3-A4-A4.1-A5-</div>

 </body>
</html>
HTML
,
                [
                    "div" => [
                        "B1-B2-B3-A4-A4.1-A5-",
                        "B1-B2-B3-A4-A4.1-A5-"
                    ]
                ]
            ],
            "counters-scope-004" => [
                <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
 <head>
  <title>CSS Test: Counter scope and nesting</title>
  <link rel="author" title="L. David Baron" href="http://dbaron.org/">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#scope">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#counters">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#propdef-content">
  <link rel="help" href="http://www.w3.org/TR/CSS21/syndata.html#counter">
  <style type="text/css">

  body { white-space: nowrap; }


  .reset { counter-reset: c; }
  .rb:before { counter-reset: c; content: "R"; }
  .use { counter-increment: c; }
  .use:before { content: counters(c, ".") " "; }

  </style>
 </head>
 <body>

 <p>The next two lines should be the same:</p>

 <div><span class="reset"></span><span class="use"></span><span class="reset"></span><span class="use"></span><span class="rb"><span class="use"></span><span class="reset"></span><span class="use"></span></span></div>
 <div>1 1 R1.1 1.1</div>

 </body>
</html>
HTML
,
                [
                    "div" => [
                        "1 1 R1.1 1.1",
                        "1 1 R1.1 1.1"
                    ]
                ]
            ],
            "counters-scope-implied-000" => [
                <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
 <head>
  <title>CSS Test: Implied counter scopes with no 'counter-increment' or 'counter-reset'</title>
  <link rel="author" title="L. David Baron" href="http://dbaron.org/">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#counters">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#propdef-content">
  <link rel="help" href="http://www.w3.org/TR/CSS21/syndata.html#counter">
  <style type="text/css">

  body { white-space: nowrap; }


  #one:before { content: counter(one) }
  #two:before { content: counter(two) }

  </style>
 </head>
 <body>

 <p>The following should be identical:</p>

 <div><span id="one"></span><span id="two"></span></div>
 <div>00</div>

 </body>
</html>
HTML
,
                [
                    "div" => [
                        "00",
                        "00"
                    ]
                ]
            ],
            "counters-scope-implied-001" => [
                <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
 <head>
  <title>CSS Test: Implied counter scopes by counter use</title>
  <link rel="author" title="L. David Baron" href="http://dbaron.org/">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#counters">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#propdef-content">
  <link rel="help" href="http://www.w3.org/TR/CSS21/syndata.html#counter">
  <style type="text/css">

  body { white-space: nowrap; }


  .i { counter-increment: c 1; }
  .r { counter-reset: c 0; }
  .u:before { content: counters(c, ".") " "; }

  </style>
 </head>
 <body>

 <p>The following two lines should be identical:</p>

 <div><span class="u"></span><span class="r"><span class="i u"></span></span></div>

 <div>0 1</div>

 </body>
</html>
HTML
,
                [
                    "div" => [
                        "0 1",
                        "0 1"
                    ]
                ]
            ],
            "counters-scope-implied-002" => [
                <<<HTML
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
 <head>
  <title>CSS Test: Implied counter scopes by 'counter-increment'</title>
  <link rel="author" title="L. David Baron" href="http://dbaron.org/">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#counters">
  <link rel="help" href="http://www.w3.org/TR/CSS21/generate.html#propdef-content">
  <link rel="help" href="http://www.w3.org/TR/CSS21/syndata.html#counter">
  <style type="text/css">

  body { white-space: nowrap; }


  .i { counter-increment: c 1; }
  .ib:before { counter-increment: c 1; content: "B" }
  .r { counter-reset: c 0; }
  .u:before { content: counters(c, ".") " "; }

  </style>
 </head>
 <body>

 <p>The following two lines should be identical:</p>

 <div><span class="ib"><span class="u"></span><span class="r"><span class="u"></span></span></span><span class="i"><span class="u"></span><span class="r"><span class="u"></span></span></span></div>

 <div>B1 0 1 1.0</div>

 </body>
</html>
HTML
,
                [
                    "div" => [
                        "B1 0 1 1.0",
                        "B1 0 1 1.0"
                    ]
                ]
            ],

            // Involving page breaks
            // Check that generated content is handled correctly after a page
            // break if font mapping forces a text-frame split
            "font mapping with page break" => [
                <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page {
    size: 300pt 200pt;
    margin: 0;
}

html {
    font-family: Helvetica, "DejaVu Sans";
}

div {
    height: 60pt;
    padding: 20pt;
    counter-increment: div;
}

div::before {
    content: "Box ∉ " counter(div);
}
</style>
</head>
<body>
    <div></div>
    <div></div>
    <div></div>
    <div></div>
</body>
</html>
HTML
,
                [
                    "div" => [
                        "Box ∉ 1",
                        "Box ∉ 2",
                        "Box ∉ 3",
                        "Box ∉ 4"
                    ]
                ]
            ],
        ];
    }

    /**
     * The expected content defines the nodes to check by node name. For each
     * name, the corresponding nodes have to match the expected text content in
     * order before render.
     *
     * @dataProvider countersProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('countersProvider')]
    public function testCounters(
        string $html,
        array $expectedContent
    ): void {
        $content = array_fill_keys(array_keys($expectedContent), []);

        // Use callback to inspect frame tree
        $dompdf = new Dompdf();
        $dompdf->setCallbacks([
            [
                "event" => "begin_frame",
                "f" => function (AbstractFrameDecorator $frame) use ($expectedContent, &$content) {
                    $node = $frame->get_node();
                    $name = $node->nodeName;

                    if (isset($expectedContent[$name])) {
                        $content[$name][] = $node->textContent;
                    }
                }
            ]
        ]);

        $dompdf->loadHtml($html);
        $dompdf->render();

        $this->assertSame($expectedContent, $content);
    }
}
