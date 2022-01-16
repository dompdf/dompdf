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

        // Counters and generated content
        $this->_set_content();

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
        // TODO: While the containing block is not set yet on the frame, it can
        // already be determined in some cases due to fixed dimensions on the
        // ancestor forming the containing block. In such cases, percentage
        // values could be resolved here
        $style = $this->_frame->get_style();

        [$width] = $this->calculate_size(null, null);
        $min_width = $this->resolve_min_width(null);
        $percent_width = Helpers::is_percent($style->width)
            || Helpers::is_percent($style->max_width);

        // Use the specified min width as minimum when width or max width depend
        // on the containing block and cannot be resolved yet. This mimics
        // browser behavior
        $min = $percent_width ? $min_width : $width;
        $max = $width;

        return [$min, $max];
    }

    /**
     * Calculate width and height, accounting for min/max constraints.
     *
     * * https://www.w3.org/TR/CSS21/visudet.html#inline-replaced-width
     * * https://www.w3.org/TR/CSS21/visudet.html#inline-replaced-height
     * * https://www.w3.org/TR/CSS21/visudet.html#min-max-widths
     * * https://www.w3.org/TR/CSS21/visudet.html#min-max-heights
     *
     * @param float|null $cbw Width of the containing block.
     * @param float|null $cbh Height of the containing block.
     *
     * @return float[]
     */
    protected function calculate_size(?float $cbw, ?float $cbh): array
    {
        /** @var ImageFrameDecorator */
        $frame = $this->_frame;
        $style = $frame->get_style();

        $computed_width = $style->width;
        $computed_height = $style->height;

        $width = $cbw === null && Helpers::is_percent($computed_width)
            ? "auto"
            : $style->length_in_pt($computed_width, $cbw ?? 0);
        $height = $cbh === null && Helpers::is_percent($computed_height)
            ? "auto"
            : $style->length_in_pt($computed_height, $cbh ?? 0);
        $min_width = $this->resolve_min_width($cbw);
        $max_width = $this->resolve_max_width($cbw);
        $min_height = $this->resolve_min_height($cbh);
        $max_height = $this->resolve_max_height($cbh);

        if ($width === "auto" && $height === "auto") {
            // Use intrinsic dimensions, resampled to pt
            [$img_width, $img_height] = $frame->get_intrinsic_dimensions();
            $w = $frame->resample($img_width);
            $h = $frame->resample($img_height);

            // Resolve min/max constraints according to the constraint-violation
            // table in https://www.w3.org/TR/CSS21/visudet.html#min-max-widths
            $max_width = max($min_width, $max_width);
            $max_height = max($min_height, $max_height);

            if (($w > $max_width && $h <= $max_height)
                || ($w > $max_width && $h > $max_height && $max_width / $w <= $max_height / $h)
                || ($w < $min_width && $h > $min_height)
                || ($w < $min_width && $h < $min_height && $min_width / $w > $min_height / $h)
            ) {
                $width = Helpers::clamp($w, $min_width, $max_width);
                $height = $width * ($img_height / $img_width);
                $height = Helpers::clamp($height, $min_height, $max_height);
            } else {
                $height = Helpers::clamp($h, $min_height, $max_height);
                $width = $height * ($img_width / $img_height);
                $width = Helpers::clamp($width, $min_width, $max_width);
            }
        } elseif ($height === "auto") {
            // Width is fixed, scale height according to aspect ratio
            [$img_width, $img_height] = $frame->get_intrinsic_dimensions();
            $width = Helpers::clamp((float) $width, $min_width, $max_width);
            $height = $width * ($img_height / $img_width);
            $height = Helpers::clamp($height, $min_height, $max_height);
        } elseif ($width === "auto") {
            // Height is fixed, scale width according to aspect ratio
            [$img_width, $img_height] = $frame->get_intrinsic_dimensions();
            $height = Helpers::clamp((float) $height, $min_height, $max_height);
            $width = $height * ($img_width / $img_height);
            $width = Helpers::clamp($width, $min_width, $max_width);
        } else {
            // Width and height are fixed
            $width = Helpers::clamp((float) $width, $min_width, $max_width);
            $height = Helpers::clamp((float) $height, $min_height, $max_height);
        }

        return [$width, $height];
    }

    protected function resolve_dimensions(): void
    {
        /** @var ImageFrameDecorator */
        $frame = $this->_frame;
        $style = $frame->get_style();

        $debug_png = $this->get_dompdf()->getOptions()->getDebugPng();

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

        [, , $cbw, $cbh] = $frame->get_containing_block();
        [$width, $height] = $this->calculate_size($cbw, $cbh);

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
