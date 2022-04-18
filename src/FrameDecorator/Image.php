<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Helpers;
use Dompdf\Image\Cache;

/**
 * Decorates frames for image layout and rendering
 *
 * @package dompdf
 */
class Image extends AbstractFrameDecorator
{

    /**
     * The path to the image file (note that remote images are
     * downloaded locally to Options:tempDir).
     *
     * @var string
     */
    protected $_image_url;

    /**
     * The image's file error message
     *
     * @var string
     */
    protected $_image_msg;

    /**
     * Class constructor
     *
     * @param Frame $frame the frame to decorate
     * @param DOMPDF $dompdf the document's dompdf object (required to resolve relative & remote urls)
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
        $url = $frame->get_node()->getAttribute("src");

        $debug_png = $dompdf->getOptions()->getDebugPng();
        if ($debug_png) {
            print '[__construct ' . $url . ']';
        }

        list($this->_image_url, /*$type*/, $this->_image_msg) = Cache::resolve_url(
            $url,
            $dompdf->getProtocol(),
            $dompdf->getBaseHost(),
            $dompdf->getBasePath(),
            $dompdf->getOptions()
        );

        if (Cache::is_broken($this->_image_url) &&
            $alt = $frame->get_node()->getAttribute("alt")
        ) {
            $fontMetrics = $dompdf->getFontMetrics();
            $style = $frame->get_style();
            $font = $style->font_family;
            $size = $style->font_size;
            $word_spacing = $style->word_spacing;
            $letter_spacing = $style->letter_spacing;

            $style->width = (4 / 3) * $fontMetrics->getTextWidth($alt, $font, $size, $word_spacing, $letter_spacing);
            $style->height = $fontMetrics->getFontHeight($font, $size);
        }
    }

    /**
     * Get the intrinsic pixel dimensions of the image.
     *
     * @return array Width and height as `float|int`.
     */
    public function get_intrinsic_dimensions(): array
    {
        [$width, $height] = Helpers::dompdf_getimagesize($this->_image_url, $this->_dompdf->getHttpContext());

        return [$width, $height];
    }

    /**
     * Resample the given pixel length according to dpi.
     *
     * @param float|int $length
     * @return float
     */
    public function resample($length): float
    {
        $dpi = $this->_dompdf->getOptions()->getDpi();
        return ($length * 72) / $dpi;
    }

    /**
     * Return the image's url
     *
     * @return string The url of this image
     */
    function get_image_url()
    {
        return $this->_image_url;
    }

    /**
     * Return the image's error message
     *
     * @return string The image's error message
     */
    function get_image_msg()
    {
        return $this->_image_msg;
    }

}
