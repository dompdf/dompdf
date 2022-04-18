<?php
namespace Dompdf\Tests;

use Dompdf\Helpers;
use Dompdf\Tests\TestCase;

class HelpersTest extends TestCase
{
    public function testParseDataUriBase64Image(): void
    {
        $imageParts = [
            'mime' => 'data:image/png;base64,',
            'data' => 'iVBORw0KGgoAAAANSUhEUgAAAAUA
AAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO
9TXL0Y4OHwAAAABJRU5ErkJggg=='
        ];
        $result = Helpers::parse_data_uri(implode('', $imageParts));
        $this->assertEquals(
            $result['data'],
            base64_decode($imageParts['data'])
        );
    }

    public function dec2RomanProvider(): array
    {
        return [
            [-5, "-5"],
            [0, "0"],
            [1, "i"],
            [5, "v"],
            [3999, "mmmcmxcix"],
            [4000, "4000"],
            [50000, "50000"],
        ];
    }

    /**
     * @dataProvider dec2RomanProvider
     */
    public function testDec2Roman($number, string $expected): void
    {
        $roman = Helpers::dec2roman($number);
        $this->assertSame($expected, $roman);
    }

    public function lengthEqualProvider(): array
    {
        // Adapted from
        // https://floating-point-gui.de/errors/NearlyEqualsTest.java
        return [
            [0.0, 0.3 - 0.2 - 0.1, true],
            [0.3, 0.1 + 0.1 + 0.1, true],

            // Large numbers
            [100000000.0, 100000001.0, true],
            [100000.0001, 100000.0002, true],
            [100000.01, 100000.02, false],
            [1000.0001, 1000.0002, false],

            // Numbers around 1
            [1.000000001, 1.000000002, true],
            [1.0000001, 1.0000002, false],

            // Numbers between 1 and 0
            [0.00000010000001, 0.00000010000002, true],
            [0.00000000001001, 0.00000000001002, true],
            [0.000000100001, 0.000000100002, false],

            // Close to zero
            [0.0, 0.0, true],
            [0.0, -0.0, true],
            [1e-38, 1e-37, true],
            [1e-38, -1e-37, true],
            [1e-38, 0.0, true],
            [1e-13, 1e-38, true],
            [1e-13, 0.0, true],
            [1e-13, -1e-13, true],
            [1e-12, -1e-12, false],
            [1e-12, 0.0, false],

            // Very large numbers
            [1e38, 1e38, true],
            [1e38, 1.000001e38, false],

            // Infinity and NaN
            [INF, INF, true],
            [INF, -INF, false],
            [INF, 1e38, false],
            [NAN, NAN, false],
            [NAN, 0.0, false],
        ];
    }

    /**
     * @dataProvider lengthEqualProvider
     */
    public function testLengthEqual(float $a, float $b, bool $expected): void
    {
        $this->assertSame($expected, Helpers::lengthEqual($a, $b));
        $this->assertSame($expected, Helpers::lengthEqual($b, $a));
        $this->assertSame($expected, Helpers::lengthEqual(-$a, -$b));
        $this->assertSame($expected, Helpers::lengthEqual(-$b, -$a));
    }

    
    public function testCustomProtocolParsing(): void
    {
        $uri = "mock://path/to/resource";
        $this->assertSame($uri, Helpers::build_url("", "", "", $uri));
    }
}
