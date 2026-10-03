<?php
/**
 * Smoke-test Cpdf::addTimestamp() against a curated set of
 * public RFC 3161 TSAs. Writes one PDF per TSA to
 *   examples/output/timestamp-<slug>.pdf
 * along with a summary line on stdout giving the TST size and
 * the PDF byte count. Failures (network / non-2xx / TSA reject)
 * are reported without aborting the run, so a single TSA outage
 * doesn't mask the others.
 *
 * Run:
 *   php examples/timestamp-tsa-matrix.php
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Cpdf;
use Dompdf\Dompdf;

$tsas = [
    // Initial set of public RFC 3161 TSAs.
    'apple'         => 'http://timestamp.apple.com/ts01',
    // FreeTSA appears defunct - DNS resolves but TCP times out
    // from multiple egress points; Wayback Machine's most recent
    // snapshot is from 2015. Kept in the matrix to demonstrate
    // graceful FAIL handling on an unreachable TSA.
    'freetsa'       => 'https://freetsa.org/tsr',
    'digicert'      => 'http://timestamp.digicert.com',
    'certum'        => 'http://time.certum.pl',
    'dfn'           => 'http://zeitstempel.dfn.de',
    'entrust'       => 'http://timestamp.entrust.net/TSS/RFC3161sha2TS',
    'cesnet'        => 'http://tsa.cesnet.cz:3161/tsa',
    // Additional public TSAs verified against this implementation.
    // GlobalSign / SwissSign are EUTL-listed qualified Trust
    // Service Providers; IZENPE is the Spanish/Basque
    // government CA. Sectigo + SSL.com are major commercial CAs
    // with a free public TSA endpoint.
    'sectigo'       => 'http://timestamp.sectigo.com',
    'globalsign'    => 'http://rfc3161timestamp.globalsign.com/advanced',
    'swisssign'    => 'http://tsa.swisssign.net',
    'ssl-com'       => 'http://ts.ssl.com',
    'izenpe'        => 'http://tsa.izenpe.com',
];

$outDir = __DIR__ . '/output';
@mkdir($outDir, 0775, true);

$summary = [];

foreach ($tsas as $slug => $url) {
    $html = sprintf(
        '<!doctype html><html lang="en"><body><h1>dompdf TSA matrix</h1><p>TSA: <code>%s</code></p></body></html>',
        htmlspecialchars($url, ENT_QUOTES)
    );
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $canvas      = $dompdf->getCanvas();
    $reflection  = new ReflectionObject($canvas);
    $cpdfHandle  = $reflection->getProperty('_pdf');
    $cpdfHandle->setAccessible(true);
    /** @var Cpdf $cpdf */
    $cpdf = $cpdfHandle->getValue($canvas);
    $cpdf->signatureMaxLen = 16000;

    // Measured TST size sniffer: intercept the response so we can
    // record it without making the round-trip twice. The first
    // call inside the closure runs the real cURL via the default
    // transport; we just keep a copy of the bytes.
    $rawTstSize = null;
    $cpdf->addTimestamp($url, [
        'userAgent' => 'dompdf-tsa-matrix',
        'tsaClient' => static function ($tsq, $tsaUrl, $opts) use (&$rawTstSize) {
            // Default transport delegated to Helpers::postFileContent.
            list($body) = \Dompdf\Helpers::postFileContent(
                $tsaUrl,
                $tsq,
                ['Content-Type: application/timestamp-query'],
                stream_context_create([
                    'http' => [
                        'user_agent' => is_string($opts['UserAgent'] ?? null) ? $opts['UserAgent'] : 'dompdf',
                        'timeout'    => 30,
                    ],
                ])
            );
            $rawTstSize = is_string($body) ? strlen($body) : 0;
            if ($body === null || $body === '') {
                throw new \Exception('TSA returned no body');
            }
            return $body;
        },
    ]);

    try {
        $pdfBytes = $dompdf->output();
        $path = $outDir . '/timestamp-' . $slug . '.pdf';
        file_put_contents($path, $pdfBytes);
        $summary[] = sprintf(
            'OK   %-8s  TSR=%5d B  PDF=%5d B  -> %s',
            $slug,
            (int) $rawTstSize,
            strlen($pdfBytes),
            $path
        );
    } catch (\Throwable $e) {
        $summary[] = sprintf('FAIL %-8s  %s (%s)', $slug, $url, $e->getMessage());
    }
}

fwrite(STDOUT, "\nTSA matrix results:\n");
foreach ($summary as $line) {
    fwrite(STDOUT, "  $line\n");
}
