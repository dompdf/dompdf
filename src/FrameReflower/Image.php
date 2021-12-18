<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameReflower;

use Dompdf\Helpers;
use Dompdf\FrameDecorator\Block as BlockFrameDecorator;
use Dompdf\FrameDecorator\Image as ImageFrameDecorator;

/**
 * Image reflower class
 *
 * @package dompdf
 */
class Image extends AbstractFrameReflower
{

    /**
     * Image constructor.
     * @param ImageFrameDecorator $frame
     */
    function __construct(ImageFrameDecorator $frame)
    {
        parent::__construct($frame);
    }

    /**
     * @param BlockFrameDecorator|null $block
     */
    function reflow(BlockFrameDecorator $block = null)
    {
        $this->determine_absolute_containing_block();

        //FLOAT
        //$frame = $this->_frame;
        //$page = $frame->get_root();

        //if ($frame->get_style()->float !== "none" ) {
        //  $page->add_floating_frame($this);
        //}

        $this->resolve_dimensions();
        $this->resolve_margins();

        $frame = $this->_frame;
        $frame->position();

        if ($block && $frame->is_in_flow()) {
            $block->add_frame_to_line($frame);
        }
    }

    public function get_min_max_content_width(): array
    {
        /** @var ImageFrameDecorator */
        $frame = $this->_frame;
        $style = $frame->get_style();
        $width = $style->width;
        $percent_width = Helpers::is_percent($width);

        // The containing block is not defined yet
        if ($width !== "auto" && !$percent_width) {
            $min = (float) $style->length_in_pt($width, 0);
            $max = $min;
        } else {
            [$img_width, $img_height] = $frame->get_intrinsic_dimensions();

            $height = $style->height;
            $fixed_height = $height !== "auto" && !Helpers::is_percent($height);

            if ($percent_width) {
                // Don't enforce any minimum width when it depends on the
                // containing block. Use intrinsic width resampled to pt for max
                // width
                $min = 0.0;
                $max = $frame->resample($img_width);
            } elseif ($fixed_height) {
                // Keep aspect ratio: Scale intrinsic width
                $height = (float) $style->length_in_pt($height, 0);
                $min = $height * ($img_width / $img_height);
                $max = $min;
            } else {
                // Width is `auto`: Use intrinsic width resampled to pt
                $min = $frame->resample($img_width);
                $max = $min;
            }
        }

        // Handle min/max width style properties
        return $this->restrict_min_max_width($min, $max);
    }

    protected function resolve_dimensions(): void
    {
        $debug_png = $this->get_dompdf()->getOptions()->getDebugPng();

        /** @var ImageFrameDecorator */
        $frame = $this->_frame;
        $style = $frame->get_style();

        if ($debug_png) {
            [$img_width, $img_height] = $frame->get_intrinsic_dimensions();
            print "resolve_dimensions() " .
                $frame->get_style()->width . " " .
                $frame->get_style()->height . ";" .
                $frame->get_parent()->get_style()->width . " " .
                $frame->get_parent()->get_style()->height . ";" .
                $frame->get_parent()->get_parent()->get_style()->width . " " .
                $frame->get_parent()->get_parent()->get_style()->height . ";" .
                $img_width . " " .
                $img_height . "|";
        }

        // https://www.w3.org/TR/CSS21/visudet.html#inline-replaced-width
        // https://www.w3.org/TR/CSS21/visudet.html#inline-replaced-height
        $style = $frame->get_style();
        [, , $cbw, $cbh] = $frame->get_containing_block();

        $width = $style->length_in_pt($style->width, $cbw);
        $height = $style->length_in_pt($style->height, $cbh);
        $width_forced = true;
        $height_forced = true;

        if ($width === "auto" || $height === "auto") {
            // Determine the image's size. Time consuming. Only when really needed!
            [$img_width, $img_height] = $frame->get_intrinsic_dimensions();

            // Don't treat 0 as error. Can be downscaled or can be caught elsewhere if image not readable.
            // Resample according to px per inch
            // See also ListBulletImage::__construct
            if ($width === "auto" && $height === "auto") {
                $width = $frame->resample($img_width);
                $height = $frame->resample($img_height);
                $width_forced = false;
                $height_forced = false;
            } elseif ($height === "auto") {
                $height = $width * ($img_height / $img_width); // Keep aspect ratio
                $height_forced = false;
            } else {
                $width = $height * ($img_width / $img_height); // Keep aspect ratio
                $width_forced = false;
            }
        }

        // Handle min/max width/height
        // https://www.w3.org/TR/CSS21/visudet.html#min-max-widths
        // https://www.w3.org/TR/CSS21/visudet.html#min-max-heights
        $min_width = $style->length_in_pt($style->min_width, $cbw);
        $max_width = $style->length_in_pt($style->max_width, $cbw);
        $min_height = $style->length_in_pt($style->min_height, $cbh);
        $max_height = $style->length_in_pt($style->max_height, $cbh);

        if ($max_width !== "none" && $max_width !== "auto" && $width > $max_width) {
            if (!$height_forced) {
                $height *= $max_width / $width;
            }

            $width = $max_width;
        }

        if ($min_width !== "auto" && $min_width !== "none" && $width < $min_width) {
            if (!$height_forced) {
                $height *= $min_width / $width;
            }

            $width = $min_width;
        }

        if ($max_height !== "none" && $max_height !== "auto" && $height > $max_height) {
            if (!$width_forced) {
                $width *= $max_height / $height;
            }

            $height = $max_height;
        }

        if ($min_height !== "auto" && $min_height !== "none" && $height < $min_height) {
            if (!$width_forced) {
                $width *= $min_height / $height;
            }

            $height = $min_height;
        }

        if ($debug_png) {
            print $width . " " . $height . ";";
        }

        $style->width = $width;
        $style->height = $height;
    }

    protected function resolve_margins(): void
    {
        // Only handle the inline case for now
        // https://www.w3.org/TR/CSS21/visudet.html#inline-replaced-width
        // https://www.w3.org/TR/CSS21/visudet.html#inline-replaced-height
        $style = $this->_frame->get_style();

        if ($style->margin_left === "auto") {
            $style->margin_left = 0;
        }
        if ($style->margin_right === "auto") {
            $style->margin_right = 0;
        }
        if ($style->margin_top === "auto") {
            $style->margin_top = 0;
        }
        if ($style->margin_bottom === "auto") {
            $style->margin_bottom = 0;
        }
    }
}
