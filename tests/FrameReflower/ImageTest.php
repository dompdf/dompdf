<?php
namespace Dompdf\Tests\FrameReflower;

use Dompdf\Css\Style;
use Dompdf\Css\Stylesheet;
use Dompdf\Dompdf;
use Dompdf\FrameDecorator\Image as ImageFrameDecorator;
use Dompdf\Tests\TestCase;
use Mockery;

class ImageTest extends TestCase
{
    public function testGetMinMaxContainerWidthAuto(): void
    {
        $frame = $this->getImageMock(['width' => 'auto', 'height' => 'auto']);

        $image = new ImageTestReflower($frame);
        $result = $image->get_min_max_width();
        $image->resolve_dimensions();

        $style = $frame->get_style();

        $expectedWidth = 1966.08;
        $expectedHeight = 1474.56;

        $this->assertEquals($expectedWidth, $style->width);
        $this->assertEquals($expectedHeight, $style->height);

        $this->assertEquals([$expectedWidth, $expectedWidth, 'min' => $expectedWidth, 'max' => $expectedWidth], $result);
    }

    public function testGetMinMaxContainerWidthBasic(): void
    {
        $frame = $this->getImageMock(['width' => '100px', 'height' => '200px']);

        $image = new ImageTestReflower($frame);
        $result = $image->get_min_max_width();
        $image->resolve_dimensions();

        $style = $frame->get_style();

        $expectedWidth = 75;
        $expectedHeight = 150;

        $this->assertEquals($expectedWidth, $style->width);
        $this->assertEquals($expectedHeight, $style->height);

        $this->assertEquals([$expectedWidth, $expectedWidth, 'min' => $expectedWidth, 'max' => $expectedWidth], $result);
    }

    public function testGetMinMaxWidthPercentageChain(): void
    {
        $rootFrame = $this->getImageMock(['width' => '400px', 'height' => '800px'], null, [0, 0, 300, 600]);
        $parentFrame = $this->getImageMock(['width' => '50%', 'height' => '75%'], $rootFrame, [0, 0, 300, 600]);
        $imageFrame = $this->getImageMock(['width' => '50%', 'height' => '75%'], $parentFrame, [0, 0, 150, 450]);

        $image = new ImageTestReflower($imageFrame);
        $result = $image->get_min_max_width();
        $image->resolve_dimensions();

        $style = $imageFrame->get_style();

        // 400px * 0.75 (dpi) * 0.50 (imageFrame) * 0.50 (rootFrame)
        $expectedWidth = 75;
        // 800px * 0.75 (dpi) * 0.75 (imageFrame) * 0.75 (rootFrame)
        $expectedHeight = 337.5;

        $this->assertEquals($expectedWidth, $style->width);
        $this->assertEquals($expectedHeight, $style->height);

        $this->assertEquals([0.0, 1966.08, 'min' => 0.0, 'max' => 1966.08], $result);
    }

    public function testGetMinMaxWidthZeroWidthZeroHeight(): void
    {
        $frame = $this->getImageMock(['width' => '0', 'height' => '0']);

        $image = new ImageTestReflower($frame);
        $result = $image->get_min_max_width();
        $image->resolve_dimensions();

        $style = $frame->get_style();

        $expectedWidth = 0;
        $expectedHeight = 0;

        $this->assertEquals($expectedWidth, $style->width);
        $this->assertEquals($expectedHeight, $style->height);

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

        $image = new ImageTestReflower($frame);
        $result = $image->get_min_max_width();
        $image->resolve_dimensions();

        $style = $frame->get_style();

        $expectedWidth = 300;
        $expectedHeight = 375;

        $this->assertEquals($expectedWidth, $style->width);
        $this->assertEquals($expectedHeight, $style->height);

        $this->assertEquals([$expectedWidth, $expectedWidth, 'min' => $expectedWidth, 'max' => $expectedWidth], $result);
    }

    public function tearDown(): void
    {
        Mockery::close();
    }

    private function getImageMock(
        array $styleProperties,
        ImageFrameDecorator $parentFrame = null,
        array $containingBlock = [0, 0, 400, 400]
    ): ImageFrameDecorator {
        $style = new Style(new Stylesheet(new Dompdf()));

        foreach ($styleProperties as $prop => $val) {
            $style->set_prop($prop, $val);
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

        $imgWidth = 2048;
        $imgHeight = 1536;

        $frame->shouldReceive('resample')->with($imgWidth)->andReturn(($imgWidth * 72) / 75);
        $frame->shouldReceive('resample')->with($imgHeight)->andReturn(($imgHeight * 72) / 75);
        $frame->shouldReceive('get_intrinsic_dimensions')->andReturn([$imgWidth, $imgHeight]);
        $frame->shouldReceive('get_containing_block')->andReturn($containingBlock);

        return $frame;
    }
}
