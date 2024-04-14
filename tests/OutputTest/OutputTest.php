<?php
namespace Dompdf\Tests\OutputTest;

use CallbackFilterIterator;
use Dompdf\Tests\TestCase;
use FilesystemIterator;
use Iterator;
use PHPUnit\Framework\AssertionFailedError;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\Process;

final class OutputTest extends TestCase
{
    private const DATASET_DIRECTORY = __DIR__ . "/../_files/OutputTest";
    private const FAILED_OUTPUT_DIRECTORY = __DIR__ . "/../../tmp/failed-output-tests";

    private static function datasetName(SplFileInfo $file): string
    {
        $prefixLength = strlen(self::DATASET_DIRECTORY);
        $path = substr($file->getPath(), $prefixLength + 1);
        $name = $file->getBasename("." . $file->getExtension());
        return "$path/$name";
    }

    /**
     * @return Iterator<Dataset>
     */
    public static function datasets(): Iterator
    {
        $flags = FilesystemIterator::KEY_AS_FILENAME
            | FilesystemIterator::CURRENT_AS_FILEINFO
            | FilesystemIterator::SKIP_DOTS;
        $filter = function (SplFileInfo $file) {
            return $file->getExtension() === "html";
        };
        $dir = new RecursiveDirectoryIterator(self::DATASET_DIRECTORY, $flags);
        $files = new CallbackFilterIterator(new RecursiveIteratorIterator($dir), $filter);

        foreach ($files as $file) {
            $name = self::datasetName($file);
            yield new Dataset($name, $file);
        }
    }

    public static function outputTestProvider(): Iterator
    {
        foreach (self::datasets() as $dataset) {
            yield $dataset->name => [$dataset];
        }
    }

    protected function setUp(): void
    {
        $process = new Process(["gs", "-v"]);
        $exitCode = $process->run();

        if ($exitCode === 127) {
            $this->markTestSkipped(
                "Output tests need Ghostscript to be available. If you are " .
                "on a Debian-based system, you can use `sudo apt install ghostscript`"
            );
        }
    }

    /**
     * @dataProvider outputTestProvider
     */
    public function testOutputMatchesReferenceRendering(Dataset $dataset): void
    {
        $document = $dataset->render();
        $referenceFile = $dataset->referenceFile()->getPathname();
        $actualOutputFile = tempnam(sys_get_temp_dir(), "dompdf_test_");

        file_put_contents($actualOutputFile, $document->output());

        try {
            $this->assertOutputMatches($referenceFile, $actualOutputFile);
        } catch (AssertionFailedError $e) {
            $path = $this->saveFailedOutput($dataset);
            throw new AssertionFailedError(
                $e->getMessage() . "\nOutput written to $path for review."
            );
        } finally {
            unlink($actualOutputFile);
        }
    }

    private function assertOutputMatches(
        string $referenceFile,
        string $actualOutputFile
    ): void {
        $command = function ($file) {
            return [
                "gs",
                "-q", "-dBATCH", "-dNOPAUSE", "-sstdout=%stderr",
                "-sDEVICE=png16m", "-dGraphicsAlphaBits=4",
                "-sOutputFile=-", $file
            ];
        };
        $process1 = new Process($command($referenceFile));
        $process2 = new Process($command($actualOutputFile));

        foreach ([$process1, $process2] as $process) {
            $process->mustRun();
            $error = $process->getErrorOutput();

            // The `-sstdout=%stderr` setting moves all non-device output to
            // STDERR. Since we only expect image data, consider any other
            // output a failure
            if ($error !== "") {
                throw new RuntimeException("Unexpected Ghostscript output: `$error`");
            }
        }

        $referenceImages = $this->outputToImageData($process1->getOutput());
        $actualImages = $this->outputToImageData($process2->getOutput());

        $expectedCount = count($referenceImages);
        $actualCount = count($actualImages);
        $failureMessage = "Output does not match reference rendering. Expected $expectedCount pages, got $actualCount.";
        $this->assertCount($expectedCount, $actualImages, $failureMessage);

        foreach ($referenceImages as $i => $referenceData) {
            $actualData = $actualImages[$i];

            $matches = $this->compareImages($referenceData, $actualData);
            $page = $i + 1;
            $failureMessage = "Output does not match reference rendering. Difference on page $page.";
            $this->assertTrue($matches, $failureMessage);
        }
    }

    /**
     * Parse the Ghostscript command output, consisting of the concatenated PNG
     * image data, one image for each page.
     *
     * @param string $output The Ghostscript command output.
     *
     * @return string[] A list of the PNG images contained in the output.
     */
    private function outputToImageData(string $output): array
    {
        $pngSignature = "\x89\x50\x4E\x47\x0D\x0A\x1A\x0A";
        $elements = explode($pngSignature, $output);

        if (count($elements) <= 1 || $elements[0] !== "") {
            throw new RuntimeException("Unexpected Ghostscript output: `$output`");
        }

        return array_map(function ($data) use ($pngSignature) {
            return $pngSignature . $data;
        }, array_slice($elements, 1));
    }

    private function compareImages(string $referenceData, string $imageData): bool
    {
        if (extension_loaded('imagick')) {
            $image1 = new \Imagick();
            $image1->readImageBlob($referenceData);
            $image2 = new \Imagick();
            $image2->readImageBlob($imageData);
            $width1 = $image1->getImageWidth();
            $height1 = $image1->getImageHeight();
            $width2 = $image2->getImageWidth();
            $height2 = $image2->getImageHeight();
    
            if ($width1 !== $width2 || $height1 !== $height2) {
                return false;
            }

            [, $error] = $image1->compareImages($image2, \Imagick::METRIC_MEANSQUAREERROR);
            return $error === 0.0;
        } else {
            $image1 = imagecreatefromstring($referenceData);
            $image2 = imagecreatefromstring($imageData);
            $width = imagesx($image1);
            $height = imagesy($image1);
            $width2 = imagesx($image2);
            $height2 = imagesy($image2);
    
            if ($width !== $width2 || $height !== $height2) {
                return false;
            }

            for ($x = 0; $x < $width; $x++) {
                for ($y = 0; $y < $height; $y++) {
                    $color1 = imagecolorat($image1, $x, $y);
                    $color2 = imagecolorat($image2, $x, $y);
    
                    if ($color1 !== $color2) {
                        return false;
                    }
                }
            }
    
            return true;
        }
    }

    private function saveFailedOutput(Dataset $dataset): string
    {
        $name = $dataset->name;
        $basePath = self::FAILED_OUTPUT_DIRECTORY . "/$name";
        $directory = dirname($basePath);

        if (!file_exists($directory)) {
            mkdir($directory, 0777, true);
        }

        $pdf = $dataset->render();
        $failPath = "$basePath.fail.pdf";
        file_put_contents($failPath, $pdf->output());

        return realpath($failPath);
    }
}
