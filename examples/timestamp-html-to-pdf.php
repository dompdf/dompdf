<?php
/**
 * Example + integration smoke test for Cpdf::addTimestamp().
 *
 * Renders a tiny HTML document through Dompdf, attaches an RFC 3161
 * document timestamp via Cpdf::addTimestamp(), and verifies the
 * resulting byte stream carries the expected
 * /Type/DocTimeStamp /SubFilter/ETSI.RFC3161 dictionary plus the
 * embedded TimeStampToken.
 *
 * Run with:
 *
 *   php examples/timestamp-html-to-pdf.php
 *
 * Offline by default: the `tsaClient` closure short-circuits the
 * cURL roundtrip and returns a handcrafted DER TimeStampResp with a
 * marker token, so the example doubles as a smoke test that can
 * land in CI without network access.
 *
 * To exercise the real cURL path against a public TSA, drop the
 * tsaClient closure - Cpdf falls back to Helpers::postFileContent()
 * with the URL passed to addTimestamp(). Apple's TSA needs
 * signatureMaxLen >= 8000:
 *
 *   $cpdf->signatureMaxLen = 8000;
 *   $cpdf->addTimestamp('http://timestamp.apple.com/ts01');
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dompdf\Cpdf;
use Dompdf\Dompdf;

$failures = [];
function ok(bool $cond, string $msg): void
{
    global $failures;
    if (! $cond) {
        $failures[] = $msg;
        fwrite(STDERR, "FAIL: $msg\n");
    }
}

/**
 * Build a minimal-but-valid DER TimeStampResp:
 *
 *   SEQUENCE {
 *     PKIStatusInfo SEQUENCE { INTEGER 0 (granted) }
 *     TimeStampToken SEQUENCE { OCTET STRING <marker> }
 *   }
 *
 * Cpdf doesn't validate the token contents - it embeds the
 * SEQUENCE bytes verbatim into /Contents - so a simple OCTET
 * STRING is enough to drive the assertion below.
 */
function fakeTimestampResp(string $marker): string
{
    // 30 03 02 01 00 == SEQUENCE { INTEGER 0 } == "granted".
    $pkiStatusInfo = "\x30\x03\x02\x01\x00";
    // 04 <len> <marker> == OCTET STRING wrapping the marker bytes.
    $octet         = "\x04" . chr(strlen($marker)) . $marker;
    // 30 <len> <octet> == SEQUENCE wrapping the OCTET STRING.
    $tokenSeq      = "\x30" . chr(strlen($octet)) . $octet;
    $body          = $pkiStatusInfo . $tokenSeq;
    // 30 <len> <body> == outer SEQUENCE.
    return "\x30" . chr(strlen($body)) . $body;
}

/* -------------------------------------------------------------------- */
/* Render                                                                */
/* -------------------------------------------------------------------- */

$html = <<<HTML
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>dompdf TSA example</title></head>
<body>
<h1>Hello from dompdf</h1>
<p>This PDF is timestamped via Cpdf::addTimestamp() with an
injected TSA client. No network required.</p>
</body>
</html>
HTML;

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Reach into the CPDF adapter to call addTimestamp() on the inner
// Cpdf instance. The adapter doesn't expose a public hook yet;
// downstream callers currently use this pattern. A small adapter
// wrapper is the natural follow-up.
$canvas      = $dompdf->getCanvas();
$reflection  = new ReflectionObject($canvas);
$cpdfHandle  = $reflection->getProperty('_pdf');
$cpdfHandle->setAccessible(true);
/** @var Cpdf $cpdf */
$cpdf = $cpdfHandle->getValue($canvas);
ok($cpdf instanceof Cpdf, 'Canvas backing instance is Cpdf');

$marker = 'DOMPDF-TSA-EXAMPLE-MARKER';
// 16000 covers every public TSA verified against this
// implementation (4-7 KB raw TST + cert chain -> 8-14 KB hex).
// The example uses a tsaClient closure for offline runs; drop
// the closure to exercise the real cURL roundtrip via
// Helpers::postFileContent.
$cpdf->signatureMaxLen = 16000;
$cpdf->addTimestamp('https://tsa.example.test/ts', [
    'tsaClient' => function ($tsq, $url, $opts) use ($marker) {
        return fakeTimestampResp($marker);
    },
    'userAgent' => 'dompdf-tsa-example',
]);

$pdfBytes = $dompdf->output();

/* -------------------------------------------------------------------- */
/* Smoke-test assertions                                                 */
/* -------------------------------------------------------------------- */

ok(is_string($pdfBytes) && strlen($pdfBytes) > 1024, 'PDF output is a non-trivial byte stream');
ok(strpos($pdfBytes, '/Type/DocTimeStamp/SubFilter/ETSI.RFC3161') !== false, '/Type/DocTimeStamp dictionary emitted');
ok(strpos($pdfBytes, '/Filter/Adobe.PPKLite') !== false, '/Filter/Adobe.PPKLite present on the timestamp dictionary');
ok(preg_match('@/ByteRange\s*\[\s*\d+\s+\*+@', $pdfBytes) === 0, '/ByteRange placeholder resolved (no stars)');
ok(strpos($pdfBytes, bin2hex($marker)) !== false, 'TimeStampToken bytes land in /Contents (hex-encoded)');

if ($failures !== []) {
    fwrite(STDERR, sprintf("\n%d assertion(s) failed\n", count($failures)));
    exit(1);
}

fwrite(STDOUT, sprintf("OK: %d bytes of timestamped PDF produced.\n", strlen($pdfBytes)));
exit(0);
