<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;

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

        $this->addNamedDest($node);
        $this->addHyperlink($node, $border_box);
        $this->debugBlockLayout($frame, "red", false);
    }

    /**
     * @param Frame        $frame
     * @param array|string $color
     * @param bool         $lines
     */
    protected function debugBlockLayout(Frame $frame, $color, bool $lines = false): void
    {
        $options = $this->_dompdf->getOptions();
        $debugLayout = $options->getDebugLayout();

        if (!$debugLayout) {
            return;
        }

        if ($options->getDebugLayoutBlocks()) {
            $this->debugLayout($frame->get_border_box(), $color);

            if ($options->getDebugLayoutPaddingBox()) {
                $this->debugLayout($frame->get_padding_box(), $color, [0.5, 0.5]);
            }
        }

        if ($lines && $options->getDebugLayoutLines() && $frame instanceof BlockFrameDecorator) {
            [$cx, , $cw] = $frame->get_content_box();

            foreach ($frame->get_line_boxes() as $line) {
                $lw = $cw - $line->left - $line->right;
                $this->debugLayout([$cx + $line->left, $line->y, $lw, $line->h], "orange");
            }
        }
    }
}
