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
use Dompdf\FontMetrics;
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
            $dompdf
        );

        if (Cache::is_broken($this->_image_url) &&
            $alt = $frame->get_node()->getAttribute("alt")
        ) {
            $style = $frame->get_style();
            $style->width = (4 / 3) * $dompdf->getFontMetrics()->getTextWidth($alt, $style->font_family, $style->font_size, $style->word_spacing);
            $style->height = $dompdf->getFontMetrics()->getFontHeight($style->font_family, $style->font_size);
        }
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
