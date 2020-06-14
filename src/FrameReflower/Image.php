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
        $this->_frame->position();

        //FLOAT
        //$frame = $this->_frame;
        //$page = $frame->get_root();

        //if ($frame->get_style()->float !== "none" ) {
        //  $page->add_floating_frame($this);
        //}

        // Set the frame's width
        $this->get_min_max_width();

        if ($block) {
            $block->add_frame_to_line($this->_frame);
        }
    }

    /**
     * @return array
     */
    function get_min_max_width()
    {
        $frame = $this->_frame;

        if ($this->get_dompdf()->getOptions()->getDebugPng()) {
            // Determine the image's size. Time consuming. Only when really needed?
            list($img_width, $img_height) = Helpers::dompdf_getimagesize($frame->get_image_url(), $this->get_dompdf()->getHttpContext());
            print "get_min_max_width() " .
                $frame->get_style()->width . ' ' .
                $frame->get_style()->height . ';' .
                $frame->get_parent()->get_style()->width . " " .
                $frame->get_parent()->get_style()->height . ";" .
                $frame->get_parent()->get_parent()->get_style()->width . ' ' .
                $frame->get_parent()->get_parent()->get_style()->height . ';' .
                $img_width . ' ' .
                $img_height . '|';
        }

        $style = $frame->get_style();

        $width_forced = true;
        $height_forced = true;

        //own style auto or invalid value: use natural size in px
        //own style value: ignore suffix text including unit, use given number as px
        //own style %: walk up parent chain until found available space in pt; fill available space
        //
        //special ignored unit: e.g. 10ex: e treated as exponent; x ignored; 10e completely invalid ->like auto

        $width = $this->get_size($frame, 'width');
        $height = $this->get_size($frame, 'height');

        if ($width === 'auto' || $height === 'auto') {
            // Determine the image's size. Time consuming. Only when really needed!
            list($img_width, $img_height) = Helpers::dompdf_getimagesize($frame->get_image_url(), $this->get_dompdf()->getHttpContext());

            // don't treat 0 as error. Can be downscaled or can be catched elsewhere if image not readable.
            // Resample according to px per inch
            // See also ListBulletImage::__construct
            if ($width === 'auto' && $height === 'auto') {
                $dpi = $frame->get_dompdf()->getOptions()->getDpi();
                $width = (float)($img_width * 72) / $dpi;
                $height = (float)($img_height * 72) / $dpi;
                $width_forced = false;
                $height_forced = false;
            } elseif ($height === 'auto') {
                $height_forced = false;
                $height = ($width / $img_width) * $img_height; //keep aspect ratio
            } else {
                $width_forced = false;
                $width = ($height / $img_height) * $img_width; //keep aspect ratio
            }
        }

        // Handle min/max width/height
        if ($style->min_width !== "none" ||
            $style->max_width !== "none" ||
            $style->min_height !== "none" ||
            $style->max_height !== "none"
        ) {

            list( /*$x*/, /*$y*/, $w, $h) = $frame->get_containing_block();

            $min_width = $style->length_in_pt($style->min_width, $w);
            $max_width = $style->length_in_pt($style->max_width, $w);
            $min_height = $style->length_in_pt($style->min_height, $h);
            $max_height = $style->length_in_pt($style->max_height, $h);

            if ($max_width !== "none" && $width > $max_width) {
                if (!$height_forced) {
                    $height *= $max_width / $width;
                }

                $width = $max_width;
            }

            if ($min_width !== "none" && $width < $min_width) {
                if (!$height_forced) {
                    $height *= $min_width / $width;
                }

                $width = $min_width;
            }

            if ($max_height !== "none" && $height > $max_height) {
                if (!$width_forced) {
                    $width *= $max_height / $height;
                }

                $height = $max_height;
            }

            if ($min_height !== "none" && $height < $min_height) {
                if (!$width_forced) {
                    $width *= $min_height / $height;
                }

                $height = $min_height;
            }
        }

        if ($this->get_dompdf()->getOptions()->getDebugPng()) {
            print $width . ' ' . $height . ';';
        }

        $style->width = $width . "pt";
        $style->height = $height . "pt";

        $style->min_width = "none";
        $style->max_width = "none";
        $style->min_height = "none";
        $style->max_height = "none";

        return [$width, $width, "min" => $width, "max" => $width];
    }

    private function get_size(Frame $f, string $type)
    {
        $ref_stack = [];
        $result_size = 0.0;
        do {
            $f_style = $f->get_style();
            $current_size = $f_style->$type;
            if (Helpers::is_percent($current_size)) {
                $ref_stack[] = str_replace('%px', '%', $current_size);
            } else {
                // auto is a valid first result. In case of previous percentage values we need a real size
                if ($current_size !== 'auto' || count($ref_stack) === 0) {
                    $result_size = $f_style->length_in_pt($current_size);
                    break;
                }
            }
        } while (($f = $f->get_parent()));

        // if we built a percentage stack walk up to find the real size
        if (count($ref_stack) > 0) {
            while (($ref = array_pop($ref_stack))) {
                $result_size = $f_style->length_in_pt($ref, $result_size);
            }
        }

        return $result_size;
    }
}
