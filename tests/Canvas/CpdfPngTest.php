<?php
namespace Dompdf\Tests\Canvas;

use Dompdf\Cpdf;
use Dompdf\Tests\TestCase;

class CpdfPngTest extends TestCase
{
    public function testGdAlphaExtractionPreservesDistinctRgbColours(): void
    {
        if (!extension_loaded("gd") || extension_loaded("imagick") || extension_loaded("gmagick")) {
            $this->markTestSkipped("This regression exercises the GD-only PNG alpha path.");
        }

        // All three colours produce the same key if decimal RGB values are concatenated.
        $colours = [[25, 51, 234], [255, 12, 34], [255, 123, 4]];
        $source = imagecreatetruecolor(count($colours), 1);
        imagealphablending($source, false);
        imagesavealpha($source, true);
        foreach ($colours as $x => [$r, $g, $b]) {
            imagesetpixel($source, $x, 0, imagecolorallocatealpha($source, $r, $g, $b, 0));
        }

        $path = tempnam(sys_get_temp_dir(), "dompdf-rgb-");
        $embedded = null;
        try {
            imagepng($source, $path);
            $pdf = new Cpdf();
            $pdf->addPngFromFile($path, 0, 0, count($colours), 1);
            $images = array_values(array_filter($pdf->objects, static function ($object) {
                return $object["t"] === "image" && $object["info"]["ColorSpace"] === "/DeviceRGB";
            }));
            $this->assertCount(1, $images);

            // Reconstruct a PNG from the actual image bytes embedded in the PDF.
            $chunk = static function ($type, $data) {
                return pack("N", strlen($data)) . $type . $data . pack("N", crc32($type . $data));
            };
            $png = "\x89PNG\r\n\x1a\n"
                . $chunk("IHDR", pack("NNCCCCC", count($colours), 1, 8, 2, 0, 0, 0))
                . $chunk("IDAT", $images[0]["data"])
                . $chunk("IEND", "");
            $embedded = imagecreatefromstring($png);
            foreach ($colours as $x => [$r, $g, $b]) {
                $this->assertSame(($r << 16) | ($g << 8) | $b, imagecolorat($embedded, $x, 0));
            }
        } finally {
            unlink($path);
            imagedestroy($source);
            if ($embedded !== null && $embedded !== false) {
                imagedestroy($embedded);
            }
        }
    }
}
