<?php
namespace Dompdf\Tests\FrameReflower;

use Dompdf\FrameReflower\Image;

class ImageTestReflower extends Image
{
    public function resolve_dimensions(): void
    {
        parent::resolve_dimensions();
    }
}
