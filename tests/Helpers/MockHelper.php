<?php
namespace Dompdf\Tests\Helpers;

use Dompdf\Css\Style;
use Dompdf\Css\Stylesheet;
use Dompdf\Dompdf;
use Mockery\MockInterface;

class MockHelper
{
    /**
     * @param $properties
     * @return MockInterface | Style
     */
    public static function getStyleMock($properties)
    {
        // initialize static properties
        // For now we cannot mock methods in a constructor
        // https://github.com/mockery/mockery/issues/534
        // $style = \Mockery::mock(Style::class, [new Stylesheet(new Dompdf())]);

        new Style(new Stylesheet(new Dompdf()));
        $style = \Mockery::mock(Style::class);

        foreach ($properties as $property => $value) {
            $style->$property = $value;
        }

        return $style;
    }
}
