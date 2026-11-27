<?php
namespace Dompdf\Tests\Canvas;

use Dompdf\Tests\TestCase;

/**
 * A local PNG with transparency reaches Cpdf as a file:// URL. Imagick and
 * Gmagick have to get the plain path: with the format set before readImage(),
 * ImageMagick treats "file://..." as a literal file name, and a security policy
 * that disables the URL module (which handles file://) rejects it as well.
 *
 * The test is not skipped without Imagick: the policy then has no effect and the
 * image is made by Gmagick or the GD code, which has to work as well.
 */
class CPDFLocalPngImageTest extends TestCase
{
    /** @var string */
    private $directory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir() . "/dompdf-local-png-" . bin2hex(random_bytes(4));
        mkdir($this->directory);
        // ImageMagick may not load the module of the URL formats (HTTP, HTTPS, FTP, FILE)
        file_put_contents(
            $this->directory . "/policy.xml",
            '<policymap><policy domain="module" rights="none" pattern="URL" /></policymap>'
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
            realpath(__DIR__ . "/../_files/red-dot.png"),
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

    public function testLocalTransparentPngIsRendered(): void
    {
        [$status, $output, $errors] = $this->render([]);

        $this->assertSame(0, $status, $errors . $output);
        $this->assertSame("%PDF-", $output);
    }

    public function testLocalTransparentPngIsRenderedWhenTheUrlModuleIsDisabled(): void
    {
        [$status, $output, $errors] = $this->render(["MAGICK_CONFIGURE_PATH" => $this->directory]);

        $this->assertSame(0, $status, $errors . $output);
        $this->assertSame("%PDF-", $output);
    }
}
