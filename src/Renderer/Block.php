<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\Helpers;

/**
 * Renders block frames
 *
 * @package dompdf
 */
class Block extends AbstractRenderer
{

    /**
     * @param Frame $frame
     */
    function render(Frame $frame)
    {
        $style = $frame->get_style();
        $node = $frame->get_node();
        $dompdf = $this->_dompdf;

        $this->_set_opacity($frame->get_opacity($style->opacity));

        [$x, $y, $w, $h] = $frame->get_border_box();

        if ($node->nodeName === "body") {
            // Margins should be fully resolved at this point
            $mt = $style->margin_top;
            $mb = $style->margin_bottom;
            $h = $frame->get_containing_block("h") - $mt - $mb;
        }

        $border_box = [$x, $y, $w, $h];

        // Draw our background, border and content
        $this->_render_background($frame, $border_box);
        $this->_render_border($frame, $border_box);
        $this->_render_outline($frame, $border_box);

        // Handle anchors & links
        if ($node->nodeName === "a" && $href = $node->getAttribute("href")) {
            $href = Helpers::build_url($dompdf->getProtocol(), $dompdf->getBaseHost(), $dompdf->getBasePath(), $href) ?? $href;
            $this->_canvas->add_link($href, $x, $y, $w, $h);
        }

        $id = $frame->get_node()->getAttribute("id");
        if (strlen($id) > 0) {
            $this->_canvas->add_named_dest($id);
        }

        $this->debugBlockLayout($frame, "red", false);
    }

    protected function debugBlockLayout(Frame $frame, ?string $color, bool $lines = false): void
    {
        $options = $this->_dompdf->getOptions();
        $debugLayout = $options->getDebugLayout();

        if (!$debugLayout) {
            return;
        }

        if ($color && $options->getDebugLayoutBlocks()) {
            $this->_debug_layout($frame->get_border_box(), $color);

            if ($options->getDebugLayoutPaddingBox()) {
                $this->_debug_layout($frame->get_padding_box(), $color, [0.5, 0.5]);
            }
        }

        if ($lines && $options->getDebugLayoutLines() && $frame instanceof BlockFrameDecorator) {
            [$cx, , $cw] = $frame->get_content_box();

            foreach ($frame->get_line_boxes() as $line) {
                $lw = $cw - $line->left - $line->right;
                $this->_debug_layout([$cx + $line->left, $line->y, $lw, $line->h], "orange");
            }
        }
    }
}
