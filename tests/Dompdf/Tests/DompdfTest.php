<?php
namespace Dompdf\Tests;

use Dompdf\Frame\FrameTree;
use Dompdf\Options;
use PHPUnit_Framework_TestCase;
use Dompdf\Dompdf;
use Dompdf\Css\Stylesheet;
use DOMDocument;

class DompdfTest extends PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $dompdf = new Dompdf();
        $this->assertInstanceOf('Dompdf\Adapter\Cpdf', $dompdf->getCanvas());
        $this->assertEquals('', $dompdf->getBaseHost());
        $this->assertEquals('', $dompdf->getBasePath());
        $this->assertInternalType('array', $dompdf->getCallbacks());
        $this->assertInstanceOf('Dompdf\Css\Stylesheet', $dompdf->getCss());
        $this->assertNull($dompdf->getDom());
        $this->assertNull($dompdf->getHttpContext());
        $this->assertInstanceOf('Dompdf\Options', $dompdf->getOptions());
        $this->assertNull($dompdf->getProtocol());
        $this->assertFalse($dompdf->getQuirksmode());
        $this->assertNull($dompdf->getTree());
    }

    public function testSetters()
    {
        $dompdf = new Dompdf();
        $dompdf->setBaseHost('test1');
        $dompdf->setBasePath('test2');
        $dompdf->setCallbacks(array('test' => array('event' => 'test', 'f' => function() {})));
        $dompdf->setCss(new Stylesheet($dompdf));
        $dompdf->setDom(new DOMDocument());
        $dompdf->setHttpContext(fopen(__DIR__ . "/_files/jamaica.jpg", 'r'));
        $dompdf->setOptions(new Options());
        $dompdf->setProtocol('test3');
        $dompdf->setTree(new FrameTree($dompdf->getDom()));

        $this->assertEquals('test1', $dompdf->getBaseHost());
        $this->assertEquals('test2', $dompdf->getBasePath());
        $this->assertCount(1, $dompdf->getCallbacks());
        $this->assertInstanceOf('Dompdf\Css\Stylesheet', $dompdf->getCss());
        $this->assertInstanceOf('DOMDocument', $dompdf->getDom());
        $this->assertInternalType('resource', $dompdf->getHttpContext());
        $this->assertInstanceOf('Dompdf\Options', $dompdf->getOptions());
        $this->assertEquals('test3', $dompdf->getProtocol());
        $this->assertInstanceOf('Dompdf\Frame\FrameTree', $dompdf->getTree());
    }

    public function testLoadHtml()
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml('<strong>Hello</strong>');
        $dom = $dompdf->getDom();
        $this->assertEquals('Hello', $dom->textContent);
    }

    public function testRender()
    {
        $dompdf = new Dompdf();
        $dompdf->loadHtml('<strong>Hello</strong>');
        $dompdf->render();

        $dom = $dompdf->getDom();
        $this->assertEquals('', $dom->textContent);

        /* This closes the OB opened by render() */
        $dompdf->output();

    }

    public function testSpaceAtStartOfSecondInlineTag()
    {
        $text_frame_contents = array();

        $dompdf = new Dompdf();

        // Use a callback to inspect the frame tree; otherwise FrameReflower\Page::reflow()
        // will dispose of it before dompdf->render finishes
        $dompdf->setCallbacks(array('test' => array(
            'event' => 'end_page_render',
            'f' => function($params) use (&$text_frame_contents) {
                $frame = $params["frame"];
                foreach ($frame->get_children() as $child) {
                    foreach ($child->get_children() as $grandchild) {
                        $text_frame_contents[] = $grandchild->get_text();
                    }
                }
            }
        )));

        $dompdf->loadHtml('<span>one</span><span> - two</span>');
        $dompdf->render();

        $this->assertEquals("one", $text_frame_contents[0]);
        $this->assertEquals(" - two", $text_frame_contents[1]);
    }

    private function _pageBreak($orphans, $widows, $lines_in_paragraph, $lines_that_could_fit_on_first_page)
    {
        $dompdf = new Dompdf();

        $callback_count = 0;
        $number_of_lines_on_second_page = 0;

        // Use a callback to inspect the frame tree; otherwise FrameReflower\Page::reflow()
        // will dispose of it before dompdf->render finishes
        $dompdf->setCallbacks(array('test' => array(
            'event' => 'end_page_render',
            'f' => function($params) use (&$number_of_lines_on_second_page, &$callback_count) {
                if ($callback_count == 1) {
                    $frame = $params["frame"];
                    foreach ($frame->get_children() as $child) {
                        foreach ($child->get_children() as $grandchild) {
                            if ($grandchild->get_node()->nodeName == "#text")
                                $number_of_lines_on_second_page++;
                        }
                    }
                }
                $callback_count++;
            }
        )));

        $html = "<p>";
        $filler = array();
        $MAX_FILLER = 47; // Is there a better way to know how many lines fit on a page?
        for ($i = 1; $i <= $MAX_FILLER - $lines_that_could_fit_on_first_page; $i++) {
            $filler[] = "Filler $i";
        }
        $html .= join("<br>", $filler);
        $html .= "</p>";

        $html .= "<p style=\"widows: $widows; orphans: $orphans;\">";
        $lines = array();
        for ($i = 1; $i <= $lines_in_paragraph; $i++) {
            $lines[] = "Line $i";
        }
        $html .= join("<br>", $lines);
        $html .= "</p>";

        $dompdf->loadHtml($html);
        $dompdf->render();

        return $number_of_lines_on_second_page;
    }

    public function testPageBreak1orphan1widow()
    {
        $orphans = 1;        // Minimum lines on first page
        $widows = 1;        // Minimum lines on second page
        $lines_in_paragraph = 4; // Expected: 2 on first, 2 on second
        $lines_that_could_fit_on_first_page = 2;
        $number_of_lines_on_second_page = $this->_pageBreak($orphans, $widows, $lines_in_paragraph, $lines_that_could_fit_on_first_page);

        $this->assertEquals(2, $number_of_lines_on_second_page, "Unexpected number of lines on the second page");
    }

    public function testPageBreak1orphan2widows()
    {
        $orphans = 1;
        $widows = 2;        // Minimum lines on second page
        $lines_in_paragraph = 4;
        $lines_that_could_fit_on_first_page = 2; // Expected: 2 on first, 2 on second
        $number_of_lines_on_second_page = $this->_pageBreak($orphans, $widows, $lines_in_paragraph, $lines_that_could_fit_on_first_page);

        $this->assertEquals(2, $number_of_lines_on_second_page, "Unexpected number of lines on the second page");
    }

    public function testPageBreak1orphan3widows()
    {
        $orphans = 1;
        $widows = 3;        // Minimum lines on second page
        $lines_in_paragraph = 4;
        $lines_that_could_fit_on_first_page = 2; // Expected: 1 on first, 3 on second
        $number_of_lines_on_second_page = $this->_pageBreak($orphans, $widows, $lines_in_paragraph, $lines_that_could_fit_on_first_page);
        $this->assertEquals(3, $number_of_lines_on_second_page, "Unexpected number of lines on the second page");
    }

    public function testPageBreak1orphan4widows()
    {
        $orphans = 1;
        $widows = 4;        // Minimum lines on second page
        $lines_in_paragraph = 4;
        $lines_that_could_fit_on_first_page = 2; // Expected: 0 on first, 4 on second
        $number_of_lines_on_second_page = $this->_pageBreak($orphans, $widows, $lines_in_paragraph, $lines_that_could_fit_on_first_page);
        $this->assertEquals(4, $number_of_lines_on_second_page, "Unexpected number of lines on the second page");
    }

    public function testPageBreak2orphans1widow()
    {
        $orphans = 2;
        $widows = 1;        // Minimum lines on second page
        $lines_in_paragraph = 4;
        $lines_that_could_fit_on_first_page = 2; // Expected: 2 on first, 2 on second
        $number_of_lines_on_second_page = $this->_pageBreak($orphans, $widows, $lines_in_paragraph, $lines_that_could_fit_on_first_page);
        $this->assertEquals(2, $number_of_lines_on_second_page, "Unexpected number of lines on the second page");
    }

    public function testPageBreak3orphans1widow()
    {
        $orphans = 3;
        $widows = 1;        // Minimum lines on second page
        $lines_in_paragraph = 4;
        $lines_that_could_fit_on_first_page = 2; // Expected: 0 on first, 4 on second
        $number_of_lines_on_second_page = $this->_pageBreak($orphans, $widows, $lines_in_paragraph, $lines_that_could_fit_on_first_page);
        $this->assertEquals(4, $number_of_lines_on_second_page, "Unexpected number of lines on the second page");
    }

    public function testPageBreak4orphans1widow()
    {
        $orphans = 4;
        $widows = 1;        // Minimum lines on second page
        $lines_in_paragraph = 4;
        $lines_that_could_fit_on_first_page = 2; // Expected: 0 on first, 4 on second
        $number_of_lines_on_second_page = $this->_pageBreak($orphans, $widows, $lines_in_paragraph, $lines_that_could_fit_on_first_page);
        $this->assertEquals(4, $number_of_lines_on_second_page, "Unexpected number of lines on the second page");
    }

}
