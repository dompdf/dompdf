<?php
namespace Dompdf\Tests\Canvas;

use Dompdf\Tests\TestCase;

/**
 * A PNG with transparency is split into a color and an alpha image. With Imagick
 * the temporary files are made by ImageMagick and read back with GD; when
 * ImageMagick cannot do that (a security policy, a resource limit, a full disk)
 * the image must still be made by the GD code instead of failing the render.
 */
class CPDFTransparentImageTest extends TestCase
{
    /** @var string */
    private $directory;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded("imagick") || extension_loaded("gmagick")) {
            $this->markTestSkipped("Only the Imagick path of transparent PNG images is tested here.");
        }

        $this->directory = sys_get_temp_dir() . "/dompdf-alpha-" . bin2hex(random_bytes(4));
        mkdir($this->directory);
        // ImageMagick may read PNG files but not write them
        file_put_contents(
            $this->directory . "/policy.xml",
            '<policymap><policy domain="coder" rights="write" pattern="PNG" /></policymap>'
        );
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . "/*") ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->directory);

        parent::tearDown();
    }

    /**
     * Renders in a process of its own: ImageMagick reads its policy only once.
     */
    private function render(array $environment): array
    {
        $script = '
            require $argv[1] . "/vendor/autoload.php";
            $dompdf = new Dompdf\Dompdf();
            $dompdf->loadHtml("<img src=\"" . $argv[2] . "\">");
            $dompdf->render();
            echo substr($dompdf->output(), 0, 5);
        ';
        $command = [
            PHP_BINARY, "-r", $script, "--",
            realpath(__DIR__ . "/../.."),
            "data:image/png;base64," . base64_encode((string) file_get_contents(__DIR__ . "/../_files/red-dot.png")),
        ];
        $process = proc_open(
            $command,
            [1 => ["pipe", "w"], 2 => ["pipe", "w"]],
            $pipes,
            null,
            array_merge(getenv(), $environment)
        );
        $output = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        return [proc_close($process), $output, $errors];
    }

    public function testTransparentImageIsRenderedWhenImagickCannotWritePng(): void
    {
        [$status, $output, $errors] = $this->render(["MAGICK_CONFIGURE_PATH" => $this->directory]);

        $this->assertSame(0, $status, $errors . $output);
        $this->assertSame("%PDF-", $output);
    }
}
