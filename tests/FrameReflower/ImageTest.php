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
    public function testGetMinMaxContainerWidthAuto(): void
    {
        $frame = $this->getImageMock(['width' => 'auto', 'height' => 'auto']);

        $image = new Image($frame);
        $result = $image->get_min_max_width();

        $style = $frame->get_style();

        $expectedWidth = 1966.08;

        $this->assertEquals($expectedWidth . 'pt', $style->width);
        $this->assertEquals('1474.56' . 'pt', $style->height);

        $this->assertEquals([$expectedWidth, $expectedWidth, 'min' => $expectedWidth, 'max' => $expectedWidth], $result);
    }

    public function testGetMinMaxContainerWidthBasic(): void
    {
        $frame = $this->getImageMock(['width' => '100px', 'height' => '200px']);

        $image = new Image($frame);
        $result = $image->get_min_max_width();

        $style = $frame->get_style();

        $expectedWidth = 75;

        $this->assertEquals($expectedWidth . 'pt', $style->width);
        $this->assertEquals('150' . 'pt', $style->height);

        $this->assertEquals([$expectedWidth, $expectedWidth, 'min' => $expectedWidth, 'max' => $expectedWidth], $result);
    }

    public function testGetMinMaxWidthPercentageChain(): void
    {
        $rootFrame = $this->getImageMock(['width' => '400px', 'height' => '800px']);
        $parentFrame = $this->getImageMock(['width' => '50%', 'height' => '75%'], $rootFrame);
        $imageFrame = $this->getImageMock(['width' => '50%', 'height' => '75%'], $parentFrame);

        $image = new Image($imageFrame);
        $result = $image->get_min_max_width();

        $style = $imageFrame->get_style();

        $expectedWidth = 75;

        // 400px * 0.75 (dpi) * 0.50 (imageFrame) * 0.50 (rootFrame)
        $this->assertEquals($expectedWidth . 'pt', $style->width);
        // 800px * 0.75 (dpi) * 0.75 (imageFrame) * 0.75 (rootFrame)
        $this->assertEquals('337.5' . 'pt', $style->height);

        $this->assertEquals([$expectedWidth, $expectedWidth, 'min' => $expectedWidth, 'max' => $expectedWidth], $result);
    }

    public function testGetMinMaxWidthZeroWidthZeroHeight(): void
    {
        $frame = $this->getImageMock(['width' => '0', 'height' => '0']);

        $image = new Image($frame);
        $result = $image->get_min_max_width();

        $style = $frame->get_style();

        $expectedWidth = 0;

        $this->assertEquals($expectedWidth . 'pt', $style->width);
        $this->assertEquals(0 . 'pt', $style->height);

        $this->assertEquals([$expectedWidth, $expectedWidth, 'min' => $expectedWidth, 'max' => $expectedWidth], $result);
    }

    public function testGetMinMaxWidthMinMaxCaps(): void
    {
        $frame = $this->getImageMock(
            [
                'width' => '100px',
                'height' => '1200px',
                'min_width' => '400px',
                'max_width' => '800px',
                'min_height' => '300px',
                'max_height' => '500px',
            ]
        );

        $image = new Image($frame);
        $result = $image->get_min_max_width();

        $style = $frame->get_style();


        $this->assertEquals('300pt', $style->width);
        $this->assertEquals('375pt', $style->height);

        $this->assertEquals(['300', '300', 'min' => '300', 'max' => '300'], $result);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    private function getImageMock(
        array $styleProperties,
        ImageFrameDecorator $parentFrame = null
    ): ImageFrameDecorator {
        $style = new Style(new Stylesheet(new Dompdf()));

        foreach ($styleProperties as $key => $prop) {
            $style->$key = $prop;
        }

        $frame = Mockery::mock(
            ImageFrameDecorator::class,
            [
                'get_dompdf->getOptions->getDebugPng' => false,
                'get_style' => $style,
                'get_parent' => $parentFrame,
                'get_dompdf->getOptions->getDpi' => 75,
                'get_image_url' => dirname(__DIR__) . '/_files/jamaica.jpg',
                'get_dompdf->getHttpContext' => null
            ]
        );

        $frame->shouldReceive('get_containing_block')->andReturn([0, 0, 400, 400]);

        return $frame;
    }
}
