<?php

namespace Dompdf\Tests\Renderer;

use Dompdf\Dompdf;
use Dompdf\Renderer;
use Dompdf\Tests\TestCase;

class RendererTest extends TestCase
{
    /** @var Renderer  */
    private $renderer;

    /** @var \ReflectionMethod */
    private $resizeBackgroundImageMethod;

    public function setUp() : void
    {
        $dompdf = new Dompdf();
        $this->renderer = new Renderer($dompdf);
        $this->resizeBackgroundImageMethod = self::getMethod('_resize_background_image');
    }

    /**
     * @dataProvider providerTestResizeBackgroundImage
     */
    public function testResizeBackgroundImage(
        $img_width,
        $img_height,
        $container_width,
        $container_height,
        $bg_resize,
        $new_img_width,
        $new_img_height
    ) {
        $result = $this->resizeBackgroundImageMethod->invokeArgs(
            $this->renderer,
            [
                $img_width,
                $img_height,
                $container_width,
                $container_height,
                $bg_resize,
                96
            ]
        );

        $this->assertEquals([$new_img_width, $new_img_height], $result);
    }

    public function providerTestResizeBackgroundImage()
    {
        return [
            "cover scale up" => [100.0, 200.0, 400.0, 300.0, "cover", 400.0, 800.0],
            "contain scale up" => [100.0, 200.0, 300.0, 400.0, "contain", 200.0, 400.0],
            "cover scale down" => [500.0, 400.0, 100.0, 300.0, "cover", 375.0, 300.0],
            "contain scale down" => [400.0, 500.0, 300.0, 100.0, "contain", 80.0, 100.0],
            "auto auto image size passthrough" => [156.0, 180.0, 777.0, 777.0, ["auto", "auto"], 156.0, 180.0],
            "percentage resize values" => [200.0, 300.0, 400.0, 500.0, ["80%", "75%"], 320.0, 375.0],
            "px or pt resize values (transformed before)" => [
                100.0,
                100.0,
                100.0,
                100.0,
                [150.0, 350.0],
                round(150.0 / 72 * 96),
                round(350.0 / 72 * 96)
            ],
            "percentage, px mixed" => [
                100.0,
                100.0,
                100.0,
                100.0,
                [150.0, "125%"],
                round(150.0 / 72 * 96),
                125.0
            ],
            "percentage width, auto height" => [100, 200, 300, 400, ["75%", "auto"], 225.0, 450.0],
            "auto width, pixel height" => [
                100,
                200,
                300,
                400,
                ["auto", "250"],
                round(round(250.0 / 72 * 96) / 2),
                round(250.0 / 72 * 96)
            ]
        ];
    }

    protected static function getMethod($name) {
        $class = new \ReflectionClass(Renderer::class);
        $method = $class->getMethod($name);
        $method->setAccessible(true);
        return $method;
    }
}
