<?php
namespace Dompdf\Tests\Canvas;

use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;
use Dompdf\Tests\TestCase;

class CPDFEmbeddedFileTest extends TestCase
{
    public function testEncryptedEmbeddedFileCanBeRendered(): void
    {
        $basePath = realpath(__DIR__ . "/..");
        $filePath = "$basePath/_files/red-dot.png";

        $dompdf = new Dompdf();
        $canvas = new CPDF([0, 0, 200, 200], "portrait", $dompdf);
        $cpdf = $canvas->get_cpdf();

        $cpdf->setEncryption("user", "owner");
        $cpdf->addEmbeddedFile($filePath, "red-dot.png", "Encrypted attachment", "image/png");

        $output = $canvas->output();

        $this->assertNotSame("", $output);
    }
}
