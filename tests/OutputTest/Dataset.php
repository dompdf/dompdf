<?php
namespace Dompdf\Tests\OutputTest;

use Dompdf\Options;
use Dompdf\Dompdf;
use SplFileInfo;

final class Dataset
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var SplFileInfo
     */
    public $file;

    /**
     * @param string      $name The name of the data set.
     * @param SplFileInfo $file The HTML source file.
     */
    public function __construct(string $name, SplFileInfo $file)
    {
        $this->name = $name;
        $this->file = $file;
    }

    public function referenceFile(): SplFileInfo
    {
        $path = $this->file->getPath();
        $name = $this->file->getBasename("." . $this->file->getExtension());
        return new SplFileInfo("$path/$name.pdf");
    }

    public function render(string $backend = "cpdf"): Dompdf
    {
        $options = new Options();
        $options->setPdfBackend($backend);

        $pdf = new Dompdf($options);
        $pdf->loadHtmlFile($this->file->getPathname());
        $pdf->setBasePath($this->file->getPath());
        $pdf->render();

        return $pdf;
    }

    public function updateReferenceFile(): void
    {
        $pdf = $this->render();
        $file = $this->referenceFile();
        file_put_contents($file->getPathname(), $pdf->output());
    }
}
