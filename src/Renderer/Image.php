<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;
use Dompdf\FrameDecorator\Image as ImageFrameDecorator;
use Dompdf\Image\Cache;

/**
 * Image renderer
 *
 * @package dompdf
 */
class Image extends Block
{
    /**
     * @param ImageFrameDecorator $frame
     */
    function render(Frame $frame)
    {
        $style = $frame->get_style();
        $node = $frame->get_node();
        $border_box = $frame->get_border_box();

        $this->_set_opacity($frame->get_opacity($style->opacity));

        // Render background & borders
        $this->_render_background($frame, $border_box);
        $this->_render_border($frame, $border_box);
        $this->_render_outline($frame, $border_box);

        $content_box = $frame->get_content_box();
        [$x, $y, $w, $h] = $content_box;

        $src = $frame->get_image_url();

        if (Cache::is_broken($src) && ($alt = $node->getAttribute("alt")) !== "") {
            $font = $style->font_family;
            $size = $style->font_size;
            $word_spacing = $style->word_spacing;
            $letter_spacing = $style->letter_spacing;

            $this->_canvas->text(
                $x,
                $y,
                $alt,
                $font,
                $size,
                $style->color,
                $word_spacing,
                $letter_spacing
            );
        } elseif ($w > 0 && $h > 0) {
            if ($style->has_border_radius()) {
                [$tl, $tr, $br, $bl] = $style->resolve_border_radius($border_box, $content_box);
                $this->_canvas->clipping_roundrectangle($x, $y, $w, $h, $tl, $tr, $br, $bl);
            }

            $this->_canvas->image($src, $x, $y, $w, $h, $style->image_resolution);

            if ($style->has_border_radius()) {
                $this->_canvas->clipping_end();
            }
        }

        $this->addNamedDest($node);
        $this->debugBlockLayout($frame, "blue");
    }
}
