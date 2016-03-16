<?php
namespace Dompdf\Tests;

use Dompdf\Helpers;
use PHPUnit_Framework_TestCase;

class HelpersTest extends PHPUnit_Framework_TestCase
{
    public function testParseDataUriBase64Image()
    {
        $imageParts = array(
            'mime' => 'data:image/png;base64,',
            'data' => 'iVBORw0KGgoAAAANSUhEUgAAAAUA
AAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO
9TXL0Y4OHwAAAABJRU5ErkJggg=='
        );
        $helpers = new Helpers();
        $result = $helpers->parse_data_uri(implode('', $imageParts));
        $this->assertEquals(
            $result['data'],
            base64_decode($imageParts['data'])
        );
    }
}
