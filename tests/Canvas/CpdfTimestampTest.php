<?php
namespace Dompdf\Tests\Canvas;

use Dompdf\Cpdf;
use Dompdf\Tests\TestCase;

/**
 * Exercises Cpdf::addTimestamp() / o_tsa() without hitting the
 * network. A closure passed via `tsaClient` returns a handcrafted
 * DER TimeStampResp; the test then asserts that the produced PDF
 * carries the expected document-timestamp dictionary and embeds the
 * TimeStampToken bytes verbatim in /Contents.
 */
class CpdfTimestampTest extends TestCase
{
    /**
     * Build a minimal-but-valid DER TimeStampResp ::= SEQUENCE {
     *   PKIStatusInfo ::= SEQUENCE { status INTEGER (0=granted) },
     *   TimeStampToken ::= SEQUENCE { ...opaque ContentInfo... }
     * }
     *
     * dompdf does not validate the token contents; it embeds the
     * SEQUENCE verbatim. So a placeholder OCTET STRING inside the
     * token SEQUENCE is enough to exercise the
     * tsaExtractToken / embedSignature path.
     */
    private function fakeTimestampResp(): string
    {
        // PKIStatusInfo: SEQUENCE { INTEGER 0 } byte-by-byte:
        //   \x30 = 0x30  SEQUENCE tag (constructed, universal)
        //   \x03         length: 3 bytes follow
        //   \x02         INTEGER tag
        //   \x01         length: 1 byte
        //   \x00         value 0 = "granted" per RFC 3161 sec. 2.4.2
        $pkiStatusInfo = "\x30\x03\x02\x01\x00";
        // TimeStampToken: SEQUENCE { OCTET STRING "fake-token" }
        //   \x04 = 0x04  OCTET STRING tag
        //   \x0a         length: 10 bytes (= strlen('fake-token'))
        $tokenPayload  = "\x04\x0a" . 'fake-token';
        // SEQUENCE wrapper: \x30 tag, then length byte (short form),
        // then the OCTET STRING above as the body.
        $tokenSequence = "\x30" . chr(strlen($tokenPayload)) . $tokenPayload;
        // Outer SEQUENCE wrapping both fields. Same shape: \x30
        // tag + 1-byte length + concatenated children.
        $body = $pkiStatusInfo . $tokenSequence;
        return "\x30" . chr(strlen($body)) . $body;
    }

    public function testAddTimestampEmbedsTokenAndDocTimeStampDictionary(): void
    {
        $cpdf = new Cpdf([0, 0, 200, 200]);
        $cpdf->newPage();
        $cpdf->addText(20, 100, 12, 'TSA test');

        $captured = [];
        $tsaResp  = $this->fakeTimestampResp();
        $cpdf->addTimestamp('https://tsa.example.test/ts', [
            'tsaClient' => function ($tsq, $url, $opts) use (&$captured, $tsaResp) {
                $captured = ['tsq' => $tsq, 'url' => $url, 'opts' => $opts];
                return $tsaResp;
            },
            'userAgent' => 'unit-test-ua',
        ]);

        $pdf = $cpdf->output();

        // Document-timestamp dictionary is wired correctly.
        $this->assertStringContainsString('/Type/DocTimeStamp/SubFilter/ETSI.RFC3161', $pdf);
        $this->assertStringContainsString('/Filter/Adobe.PPKLite', $pdf);
        $this->assertStringContainsString('/ByteRange', $pdf);

        // /ByteRange placeholder got rewritten to a real
        // "[ 0 X Y Z ]" form (no '**********' left in the dict).
        // assertNotRegExp / assertDoesNotMatchRegularExpression
        // drifted between PHPUnit majors; preg_match + assertSame
        // works on both.
        $this->assertSame(
            0,
            preg_match('@/ByteRange\s*\[\s*\d+\s*\*+@', $pdf),
            'ByteRange should be resolved, no stars remaining'
        );

        // Token bytes land verbatim in /Contents (hex-encoded).
        $this->assertStringContainsString(bin2hex($tsaResp[strlen($tsaResp) - 1]), $pdf);

        // Closure received: a non-empty DER TimeStampReq, the URL,
        // and the options array including the custom UserAgent.
        $this->assertNotEmpty($captured['tsq']);
        $this->assertSame("\x30", $captured['tsq'][0], 'TimeStampReq must start with SEQUENCE tag (0x30)');
        $this->assertSame('https://tsa.example.test/ts', $captured['url']);
        $this->assertSame('unit-test-ua', $captured['opts']['UserAgent']);
    }

    /**
     * Regression: PAdES validators (Adobe Reader, EU DSS, etc.)
     * locate document timestamps through /Catalog/AcroForm/Fields,
     * not by scanning the byte stream. addTimestamp() must wire the
     * /Sig object into that array; without it the timestamp lands
     * in the PDF but every validator reports "no signature found"
     * (DSS regression observed before the wiring landed).
     *
     * Pin the four structural markers that have to be present:
     *
     *   /Type/Catalog ... /AcroForm <ref> 0 R
     *   /SigFlags 3                  (bit 0 SignaturesExist + bit 1 AppendOnly)
     *   /FT /Sig                     (the sig field's field type)
     *   /V <sigId> 0 R               (the sig field points at the TST object)
     */
    public function testAcroFormWiringIsPresentSoPAdesValidatorsFindTheTimestamp(): void
    {
        $cpdf = new Cpdf([0, 0, 200, 200]);
        $cpdf->newPage();
        $cpdf->addText(20, 100, 12, 'AcroForm wiring');

        $tsaResp = $this->fakeTimestampResp();
        $sigId = $cpdf->addTimestamp('https://tsa.example.test/ts', [
            'tsaClient' => function () use ($tsaResp) {
                return $tsaResp;
            },
        ]);
        $pdf = $cpdf->output();

        // AcroForm is referenced from the catalog (any object id).
        $this->assertMatchesPdfPattern('@/AcroForm\s+\d+\s+0\s+R@', $pdf, 'Catalog must reference /AcroForm');

        // SigFlags = 3: signatures-exist + append-only.
        $this->assertStringContainsString('/SigFlags 3', $pdf);

        // A Sig form field exists.
        $this->assertStringContainsString('/FT /Sig', $pdf);

        // The Sig field's /V points at the sig object id we just
        // got back from addTimestamp() (object-reference form, not
        // string form - a "/V (1234 0 R)" string would not be a
        // real reference and DSS would refuse it).
        $this->assertMatchesPdfPattern(
            '@/V\s+' . $sigId . '\s+0\s+R@',
            $pdf,
            'Sig field /V must be a real object reference to the TST'
        );
    }

    /**
     * assertNotRegExp / assertDoesNotMatchRegularExpression /
     * assertMatchesRegularExpression all drifted between PHPUnit
     * majors. preg_match + assertGreaterThanOrEqual is portable
     * across PHPUnit 7.5+ (which is the lowest version dompdf's
     * composer.json allows).
     */
    private function assertMatchesPdfPattern(string $regex, string $pdf, string $message = ''): void
    {
        $this->assertSame(1, preg_match($regex, $pdf), $message);
    }

    public function testTsaRefusalThrows(): void
    {
        $cpdf = new Cpdf([0, 0, 200, 200]);
        $cpdf->newPage();
        $cpdf->addText(20, 100, 12, 'TSA refusal');

        // PKIStatus = 2 (rejection) per RFC 3161. Byte-by-byte:
        //   \x30 \x05         outer SEQUENCE, 5 bytes follow
        //     \x30 \x03         inner SEQUENCE (PKIStatusInfo), 3 bytes
        //       \x02 \x01 \x02    INTEGER, length 1, value 2 = "rejection"
        // No TimeStampToken attached (legal per ASN.1 OPTIONAL).
        $rejection = "\x30\x05\x30\x03\x02\x01\x02";

        $cpdf->addTimestamp('https://tsa.example.test/ts', [
            'tsaClient' => function () use ($rejection) {
                return $rejection;
            },
        ]);

        // expectExceptionMessageMatches() is PHPUnit 8.4+; the
        // dompdf matrix runs as low as 7.5. Manual try/catch +
        // assertions on $e->getMessage() are version-portable.
        try {
            $cpdf->output();
            $this->fail('Expected an exception on PKIStatus rejection');
        } catch (\Exception $e) {
            $this->assertStringContainsString('PKIStatus = 2', $e->getMessage());
        }
    }

    public function testTokenLargerThanSignatureMaxLenThrows(): void
    {
        $cpdf = new Cpdf([0, 0, 200, 200]);
        $cpdf->newPage();
        $cpdf->addText(20, 100, 12, 'oversize');

        // Force a budget the test fixture is guaranteed to exceed.
        // Token hex-encodes to 2x bytes, so a 200-byte payload =
        // 400 hex chars and signatureMaxLen = 100 must overflow.
        $cpdf->signatureMaxLen = 100;

        // Build a 200-byte-payload TimeStampToken. After hex-encode
        // (factor x2) it exceeds the 100-byte signatureMaxLen budget,
        // so embedSignature() must throw.
        //
        //   PKIStatusInfo: granted (same shape as fakeTimestampResp)
        $pkiStatus = "\x30\x03\x02\x01\x00";
        //   OCTET STRING long-form length: \x04 tag, \x82 = "two
        //   length bytes follow", \x00 \xC8 = 0x00C8 = 200, then
        //   200 bytes of 'A' filler.
        $bigPayload = "\x04\x82\x00\xC8" . str_repeat('A', 200);
        //   Wrap that OCTET STRING in a SEQUENCE with long-form
        //   length: \x30 \x82 then the 2-byte body length.
        $tokenSeq  = "\x30\x82\x00" . chr(strlen($bigPayload)) . $bigPayload;
        //   Outer SEQUENCE wrapping PKIStatusInfo + TimeStampToken.
        //   Same long-form trick: \x30 \x82 + 2 big-endian length
        //   bytes (high byte then low byte).
        $body      = $pkiStatus . $tokenSeq;
        $tsr       = "\x30\x82" . chr(strlen($body) >> 8) . chr(strlen($body) & 0xff) . $body;

        $cpdf->addTimestamp('https://tsa.example.test/ts', [
            'tsaClient' => function () use ($tsr) {
                return $tsr;
            },
        ]);

        try {
            $cpdf->output();
            $this->fail('Expected an exception on oversize token');
        } catch (\Exception $e) {
            $this->assertStringContainsString('exceeds the', $e->getMessage());
        }
    }
}
