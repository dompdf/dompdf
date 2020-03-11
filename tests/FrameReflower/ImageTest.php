<?php

namespace Dompdf\Tests\FrameReflower;

use Dompdf\Css\Style;
use Dompdf\Css\Stylesheet;
use Dompdf\Dompdf;
use Dompdf\FrameReflower\Image;
use Dompdf\FrameDecorator\Image as ImageFrameDecorator;
use Dompdf\Tests\TestCase;
use Mockery;

class ImageTest extends TestCase
{
    public function testGetMinMaxWidthBasic(): void
    {
        $frame = $this->getImageMock('100px', '200px');

        $image = new Image($frame);
        $result = $image->get_min_max_width();

        $style = $frame->get_style();

        $this->assertEquals('75' . 'pt', $style->width);
        $this->assertEquals('150' . 'pt', $style->height);

        $this->assertEquals([75.0, 75.0, 'min' => 75.0, 'max' => 75.0], $result);
    }

    public function testGetMinMaxWidthPercentageChain(): void
    {
        $rootFrame = $this->getImageMock('400px', '800px');
        $parentFrame = $this->getImageMock('50%', '75%', $rootFrame);
        $imageFrame = $this->getImageMock('50%', '75%', $parentFrame);

        $image = new Image($imageFrame);
        $result = $image->get_min_max_width();

        $style = $imageFrame->get_style();

        // 400px * 0.75 (dpi) * 0.50 (imageFrame) * 0.50 (rootFrame)
        $this->assertEquals('75' . 'pt', $style->width);
        // 800px * 0.75 (dpi) * 0.75 (imageFrame) * 0.75 (rootFrame)
        $this->assertEquals('337.5' . 'pt', $style->height);

        $this->assertEquals([75.0, 75.0, 'min' => 75.0, 'max' => 75.0], $result);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    private function getImageMock(
        string $width,
        string $height,
        ImageFrameDecorator $parentFrame = null
    ): ImageFrameDecorator {
        $style = new Style(new Stylesheet(new Dompdf()));
        $style->width = $width;
        $style->height = $height;

        $frame = Mockery::mock(
            ImageFrameDecorator::class,
            [
                'get_dompdf->getOptions->getDebugPng' => false,
                'get_style' => $style,
                'get_parent' => $parentFrame
            ]
        );

        $frame->shouldReceive('get_containing_block')->andReturn([400, 400]);

        return $frame;
    }
}
