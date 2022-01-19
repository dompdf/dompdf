<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\Frame;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Page as PageFrameDecorator;

/**
 * Reflows pages
 *
 * @package dompdf
 */
class Page extends AbstractFrameReflower
{

    /**
     * Cache of the callbacks array
     *
     * @var array
     */
    private $_callbacks;

    /**
     * Cache of the canvas
     *
     * @var \Dompdf\Canvas
     */
    private $_canvas;

    /**
     * Page constructor.
     * @param PageFrameDecorator $frame
     */
    function __construct(PageFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    /**
     * @param PageFrameDecorator $frame
     * @param int $page_number
     */
    function apply_page_style(Frame $frame, $page_number)
    {
        $style = $frame->get_style();
        $page_styles = $style->get_stylesheet()->get_page_styles();

        // http://www.w3.org/TR/CSS21/page.html#page-selectors
        if (count($page_styles) > 1) {
            $odd = $page_number % 2 == 1;
            $first = $page_number == 1;

            $style = clone $page_styles["base"];

            // FIXME RTL
            if ($odd && isset($page_styles[":right"])) {
                $style->merge($page_styles[":right"]);
            }

            if ($odd && isset($page_styles[":odd"])) {
                $style->merge($page_styles[":odd"]);
            }

            // FIXME RTL
            if (!$odd && isset($page_styles[":left"])) {
                $style->merge($page_styles[":left"]);
            }

            if (!$odd && isset($page_styles[":even"])) {
                $style->merge($page_styles[":even"]);
            }

            if ($first && isset($page_styles[":first"])) {
                $style->merge($page_styles[":first"]);
            }

            $frame->set_style($style);
        }

        $frame->calculate_bottom_page_edge();
    }

    /**
     * Paged layout:
     * http://www.w3.org/TR/CSS21/page.html
     *
     * @param BlockFrameDecorator|null $block
     */
    function reflow(BlockFrameDecorator $block = null)
    {
        $fixed_children = [];
        $prev_child = null;
        $child = $this->_frame->get_first_child();
        $current_page = 0;

        while ($child) {
            $this->apply_page_style($this->_frame, $current_page + 1);

            $style = $this->_frame->get_style();

            // Pages are only concerned with margins
            $cb = $this->_frame->get_containing_block();
            $left = (float)$style->length_in_pt($style->margin_left, $cb["w"]);
            $right = (float)$style->length_in_pt($style->margin_right, $cb["w"]);
            $top = (float)$style->length_in_pt($style->margin_top, $cb["h"]);
            $bottom = (float)$style->length_in_pt($style->margin_bottom, $cb["h"]);

            $content_x = $cb["x"] + $left;
            $content_y = $cb["y"] + $top;
            $content_width = $cb["w"] - $left - $right;
            $content_height = $cb["h"] - $top - $bottom;

            // Only if it's the first page, we save the nodes with a fixed position
            if ($current_page == 0) {
                foreach ($child->get_children() as $onechild) {
                    if ($onechild->get_style()->position === "fixed") {
                        $fixed_children[] = $onechild->deep_copy();
                    }
                }
                $fixed_children = array_reverse($fixed_children);
            }

            $child->set_containing_block($content_x, $content_y, $content_width, $content_height);

            // Check for begin reflow callback
            $this->_check_callbacks("begin_page_reflow", $child);

            //Insert a copy of each node which have a fixed position
            if ($current_page >= 1) {
                foreach ($fixed_children as $fixed_child) {
                    $child->insert_child_before($fixed_child->deep_copy(), $child->get_first_child());
                }
            }

            $child->reflow();
            $next_child = $child->get_next_sibling();

            // Check for begin render callback
            $this->_check_callbacks("begin_page_render", $child);

            // Render the page
            $this->_frame->get_renderer()->render($child);

            // Check for end render callback
            $this->_check_callbacks("end_page_render", $child);

            if ($next_child) {
                $this->_frame->next_page();
            }

            // Wait to dispose of all frames on the previous page
            // so callback will have access to them
            if ($prev_child) {
                $prev_child->dispose(true);
            }
            $prev_child = $child;
            $child = $next_child;
            $current_page++;
        }

        // Dispose of previous page if it still exists
        if ($prev_child) {
            $prev_child->dispose(true);
        }
    }

    /**
     * Check for callbacks that need to be performed when a given event
     * gets triggered on a page
     *
     * @param string $event The type of event
     * @param Frame  $frame The frame that event is triggered on
     */
    protected function _check_callbacks(string $event, Frame $frame): void
    {
        if (!isset($this->_callbacks)) {
            $dompdf = $this->_frame->get_dompdf();
            $this->_callbacks = $dompdf->getCallbacks();
            $this->_canvas = $dompdf->getCanvas();
        }

        if (isset($this->_callbacks[$event])) {
            $fs = $this->_callbacks[$event];
            $info = [
                0 => $this->_canvas, "canvas" => $this->_canvas,
                1 => $frame,         "frame"  => $frame,
            ];

            foreach ($fs as $f) {
                $f($info);
            }
        }
    }
}
