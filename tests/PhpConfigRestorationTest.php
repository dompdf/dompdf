<?php
namespace Dompdf\Tests;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PhpConfigRestorationTest extends TestCase
{
    public function testSuccessfulFileLoadAndOutputPreserveCallerConfiguration(): void
    {
        $temporary = tempnam(sys_get_temp_dir(), 'dompdf-config-');
        $file = $temporary . '.html';
        rename($temporary, $file);
        file_put_contents($file, '<p>Successful file load</p>');
        $options = new Options();
        $options->setChroot(dirname($file));
        $dompdf = new Dompdf($options);
        $original = [ini_get('pcre.jit'), mb_internal_encoding()];
        ini_set('pcre.jit', '1');
        mb_internal_encoding('ISO-8859-1');
        try {
            $dompdf->loadHtmlFile($file);
            $this->assertSame(['1', 'ISO-8859-1'], [ini_get('pcre.jit'), mb_internal_encoding()]);
            $this->assertStringContainsString('Successful file load', $dompdf->getDom()->textContent);
            $dompdf->render();
            $this->assertSame(['1', 'ISO-8859-1'], [ini_get('pcre.jit'), mb_internal_encoding()]);
            $this->assertStringStartsWith('%PDF-', $dompdf->output());
            $this->assertSame(['1', 'ISO-8859-1'], [ini_get('pcre.jit'), mb_internal_encoding()]);
        } finally {
            ini_set('pcre.jit', $original[0]);
            mb_internal_encoding($original[1]);
            unlink($file);
        }
    }

    public static function operationProvider(): array
    {
        return [['loadHtmlFile'], ['render'], ['output'], ['stream']];
    }

    /**
     * @dataProvider operationProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('operationProvider')]
    public function testRealExceptionRestoresCallerConfiguration(string $operation): void
    {
        $options = new Options();
        $options->setIsPdfAEnabled(in_array($operation, ['output', 'stream'], true));
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml('<p style="font-family:Helvetica">Real standard font</p>');
        $sentinel = new \RuntimeException('application render callback');
        if ($operation === 'render') {
            $dompdf->setCallbacks([['event' => 'begin_page_render', 'f' => static function () use ($sentinel) {
                throw $sentinel;
            }]]);
        } elseif ($operation !== 'loadHtmlFile') {
            $dompdf->render();
        }
        $original = [ini_get('pcre.jit'), mb_internal_encoding(), setlocale(LC_NUMERIC, 0)];
        ini_set('pcre.jit', '1');
        mb_internal_encoding('ISO-8859-1');
        setlocale(LC_NUMERIC, 'de_DE.UTF-8', 'fr_FR.UTF-8', 'C');
        $expected = [ini_get('pcre.jit'), mb_internal_encoding(), setlocale(LC_NUMERIC, 0)];
        try {
            $caught = null;
            try {
                if ($operation === 'loadHtmlFile') {
                    $dompdf->loadHtmlFile('unsupported://document.html');
                } elseif ($operation === 'stream') {
                    $this->assertFalse(headers_sent());
                    $dompdf->stream('document.pdf');
                } else {
                    $dompdf->$operation();
                }
            } catch (\Throwable $exception) {
                $caught = $exception;
            }
            $this->assertInstanceOf(\Exception::class, $caught);
            if ($operation === 'render') {
                $this->assertSame($sentinel, $caught);
            } elseif ($operation === 'loadHtmlFile') {
                $this->assertStringContainsString('protocol is not supported', $caught->getMessage());
            } else {
                $this->assertSame('A fully embeddable font must be used when generating a document in PDF/A mode', $caught->getMessage());
            }
            $this->assertSame($expected, [ini_get('pcre.jit'), mb_internal_encoding(), setlocale(LC_NUMERIC, 0)]);
        } finally {
            ini_set('pcre.jit', $original[0]);
            mb_internal_encoding($original[1]);
            setlocale(LC_NUMERIC, $original[2]);
        }
    }
}
