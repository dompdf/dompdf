<?php

class Release
{
    /**
     * Execute a shell command and throw exception if fails.
     */
    private function exec(string $command): void
    {
        $return_var = null;
        $fullCommand = "$command 2>&1";
        passthru($fullCommand, $return_var);
        if ($return_var) {
            throw new Exception('FAILED executing: ' . $command);
        }
    }

    private function checkPhpVersion(): void
    {
        $data = json_decode(file_get_contents('composer.json'), true);
        preg_match_all('~\d\.\d~', $data['require']['php'] ?? $data['require']['php-64bit'], $m);

        $expectedVersion = min($m[0]);
        $actualVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        if ($actualVersion > $expectedVersion) {
            throw new Exception("Release must be created with PHP $expectedVersion (the oldest supported version), but you are running the newer PHP $actualVersion.");
        }
    }

    private function getComposerCommand(): string
    {
        $php = PHP_BINARY;
        $composerPath = trim(`which composer`);

        return "$php $composerPath --ansi --no-interaction";
    }

    public function create(): void
    {
        $this->checkPhpVersion();
        $composer = $this->getComposerCommand();
        $tempDir = sys_get_temp_dir() . '/dompdf-release';

        $buildDirectory = dirname(__DIR__) . "/build";
        if (!is_dir($buildDirectory)) {
            mkdir($buildDirectory);
        }

        $this->exec("rm -rf $tempDir");
        mkdir($tempDir);
        chdir($tempDir);
        $this->exec("$composer init --type project");
        $this->exec("$composer require --fixed dompdf/dompdf");
        $this->exec("rm composer.json composer.lock");
        $this->exec("cp vendor/dompdf/dompdf/*.md vendor/dompdf/dompdf/LICENSE.LGPL vendor/dompdf/dompdf/VERSION .");

        file_put_contents($tempDir . '/autoload.inc.php', "<?php require (__DIR__ . '/vendor/autoload.php');");

        // Create zip
        $version = trim(file_get_contents($tempDir . '/VERSION'));
        $destination = $buildDirectory . "/dompdf-$version.zip";
        $this->zip($tempDir, $destination, 'dompdf');

        $this->exec("rm -rf $tempDir");

        echo "created $destination" . PHP_EOL;
    }

    private function zip(string $source, string $destination, string $mainDir): void
    {
        $zip = new ZipArchive();
        $opened = $zip->open($destination, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if (!$opened) {
            throw new Exception('Could not create zip file: ' . $opened);
        }

        $prefix = '';
        if ($mainDir) {
            $zip->addEmptyDir($mainDir);
            $prefix = $mainDir . '/';
        }

        $source = str_replace('\\', '/', realpath($source));
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST) as $file) {
            $file = str_replace('\\', '/', $file);

            // Ignore "." and ".." folders
            $basename = basename($file);
            if (in_array($basename, ['.', '..'])) {
                continue;
            }

            $targetFile = str_replace($source . '/', $prefix, $file);
            if (is_dir($file)) {
                $zip->addEmptyDir($targetFile);
            } else {
                $zip->addFile($file, $targetFile);
            }
        }

        $ok = $zip->close();
        if (!$ok) {
            throw new Exception('Failed to close zip file');
        }
    }

}

$release = new Release();
$release->create();

