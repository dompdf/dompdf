<?php

declare(strict_types=1);

namespace Dompdf\Tests\StructTree;

use Dompdf\StructTree\CPDFStructTree;
use Dompdf\Tests\TestCase;
use Dompdf\Adapter\CPDF;
use DOMNode;
use DOMDocument;

class CPDFStructTreeTest extends TestCase
{
    public function testConstruct(): void
    {
        $this->assertInstanceOf(CPDFStructTree::class, new CPDFStructTree(
            $this->getMockBuilder(CPDF::class)->disableOriginalConstructor()->getMock()
        ));
    }

    public function testRenderWithPath(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<html><body><div><p>hej</p><div></body></html>');
        $p = $doc->getElementsByTagName('p')[0];

        $canvas = $this->getMockBuilder(CPDF::class)->disableOriginalConstructor()->getMock();

        $canvas->expects(self::once())->method('addOutlineRoot');
        $canvas->expects(self::once())->method('addStructTreeRoot')->willReturn(123);
        $canvas->expects(self::exactly(3))->method('addStructElement')->willReturnCallback(function($tag, $parent){
            $map = ['Document' => [123, 456], 'Div' => [456, 789], 'P' => [789, 111]];
            $this->assertSame($map[$tag][0], $parent);
            return $map[$tag][1];
        });
        $canvas->expects(self::once())->method('inMarkedStructureContent')->with('P', 111, []);

        $instance = new CPDFStructTree($canvas);
        $instance->render($p);
    }

    public function testRenderOutline(): void
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML('<html><body><h1>first</h1><h3>sec</h3><div><h1>third</h1></div></body></html>');
        $p = $doc->getElementsByTagName('p')[0];
        $outline = [
            ['first', 987, 765],
            ['sec', 765, 543],
            ['third', 987, 321],
        ];

        $canvas = $this->getMockBuilder(CPDF::class)->disableOriginalConstructor()->getMock();

        $canvas->expects(self::once())->method('addOutlineRoot')->willReturn(987);
        $canvas->expects(self::exactly(3))->method('addOutline')->willReturnCallback(function($title, $parent) use (&$outline){
            $expected = array_shift($outline);
            $this->assertSame($title, $expected[0]);
            $this->assertSame($parent, $expected[1]);
            return $expected[2];
        });

        $instance = new CPDFStructTree($canvas);
        $instance->render($doc->getElementsByTagName('h1')[0]->firstChild);
        $instance->render($doc->getElementsByTagName('h3')[0]->firstChild);
        $instance->render($doc->getElementsByTagName('h1')[1]->firstChild);
    }
}
