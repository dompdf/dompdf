<?php
namespace Dompdf\Tests\Css;

use Dompdf\Dompdf;
use Dompdf\Tests\TestCase;

class SelectorTest extends TestCase
{
    public function selectorParsedWithoutErrorsProvider(): array
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
     * @dataProvider selectorParsedWithoutErrorsProvider
     */
    public function testSelectorParsedWithoutErrors(string $selector): void
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml(<<<HTML
    <!DOCTYPE html>
    <head>
    <meta charset="UTF-8">
    <style>$selector {}</style>
    </head>
    <html>
    <body></body>
    </html>
HTML
        );
        $dompdf->render();

        $this->addToAssertionCount(1);
    }
}
