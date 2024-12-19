<?php
namespace Dompdf\Tests\Image;

use Dompdf\Helpers;
use Dompdf\Image;
use Dompdf\Options;
use Dompdf\Tests\TestCase;

class CacheTest extends TestCase
{
    public static function imageUrlProvider(): array
    {
        return [
            ["../_files/jamaica.jpg", "file://" . realpath(__DIR__ . "/../_files/jamaica.jpg")],
            ["data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20100%20100%22%3E%3Cimage%20x%3D%2250%22%20y%3D%22150%22%20width%3D%22100%22%20height%3D%22100%22%20xlink%3Ahref%3D%22https%3A%2F%2Fexample.com%2Fimage.gif%22%2F%3E%3C%2Fsvg%3E", Image\Cache::$broken_image],
        ];
    }

    /**
     * @dataProvider imageUrlProvider
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('imageUrlProvider')]
    public function testUrlResolution(string $url, string $expected): void
    {
        $protocol = "";
        $host = "";
        $base_path = __DIR__;
        $options = new Options([
            "chroot" => [
                __DIR__ . "/../_files"
            ]
        ]);

        $cache = Image\Cache::resolve_url($url, $protocol, $host, $base_path, $options);
        $this->assertEquals($expected, $cache[0]);
    }
}
