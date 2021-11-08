<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Helpers;
use Dompdf\Image\Cache;

/**
 * Decorates frames for list bullets with custom images
 *
 * @package dompdf
 */
class ListBulletImage extends AbstractFrameDecorator
{

    /**
     * The underlying image frame
     *
     * @var Image
     */
    protected $_img;

    /**
     * The image's width in pixels
     *
     * @var int
     */
    protected $_width;

    /**
     * The image's height in pixels
     *
     * @var int
     */
    protected $_height;

    /**
     * Class constructor
     *
     * @param Frame $frame   the bullet frame to decorate
     * @param Dompdf $dompdf the document's dompdf object
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        $style = $frame->get_style();
        $url = $style->list_style_image;
        $frame->get_node()->setAttribute("src", $url);
        $this->_img = new Image($frame, $dompdf);
        parent::__construct($this->_img, $dompdf);

        if (Cache::is_broken($this->_img->get_image_url())) {
            $width = 0;
            $height = 0;
        } else {
            list($width, $height) = Helpers::dompdf_getimagesize($this->_img->get_image_url(), $dompdf->getHttpContext());
        }

        // Resample the bullet image to be consistent with 'auto' sized images
        // See also Image::get_min_max_width
        // Tested php ver: value measured in px, suffix "px" not in value: rtrim unnecessary.
        $dpi = $this->_dompdf->getOptions()->getDpi();
        $this->_width = ((float)rtrim($width, "px") * 72) / $dpi;
        $this->_height = ((float)rtrim($height, "px") * 72) / $dpi;

        //If an image is taller as the containing block/box, the box should be extended.
        //Neighbour elements are overwriting the overlapping image areas.
        //Todo: Where can the box size be extended?
        //Code below has no effect.
        //See block_frame_reflower _calculate_restricted_height
        //See generated_frame_reflower, Dompdf:render() "list-item", "-dompdf-list-bullet"S.
        //Leave for now
        //if ($style->min_height < $this->_height ) {
        //  $style->min_height = $this->_height;
        //}
        //$style->height = "auto";
    }

    /**
     * Return the bullet's width
     *
     * @return int
     */
    function get_width()
    {
        //ignore image width, use same width as on predefined bullet ListBullet
        //for proper alignment of bullet image and text. Allow image to not fitting on left border.
        //This controls the distance between bullet image and text
        //return $this->_width;
        return $this->_frame->get_style()->font_size * ListBullet::BULLET_SIZE +
        2 * ListBullet::BULLET_PADDING;
    }

    /**
     * Return the bullet's height
     *
     * @return int
     */
    function get_height()
    {
        //based on image height
        if ($this->_height == 0) {
            $style = $this->_frame->get_style();

            if ($style->list_style_type === "none") {
                return 0;
            }
    
            return $style->font_size * ListBullet::BULLET_SIZE + 2 * ListBullet::BULLET_PADDING;
        } else {
            return $this->_height;
        }
    }

    /**
     * Override get_margin_width
     *
     * @return int
     */
    public function get_margin_width(): float
    {
        //ignore image width, use same width as on predefined bullet ListBullet
        //for proper alignment of bullet image and text. Allow image to not fitting on left border.
        //This controls the extra indentation of text to make room for the bullet image.
        //Here use actual image size, not predefined bullet size
        //return $this->_frame->get_style()->font_size*ListBullet::BULLET_SIZE +
        //  2 * ListBullet::BULLET_PADDING;

        // Small hack to prevent indenting of list text
        // Image might not exist, then position like on list_bullet_frame_decorator fallback to none.
        if ($this->_frame->get_style()->list_style_position === "outside" || $this->_width == 0) {
            return 0.0;
        }
        //This aligns the "inside" image position with the text.
        //The text starts to the right of the image.
        //Between the image and the text there is an added margin of image width.
        //Where this comes from is unknown.
        //The corresponding ListBullet sets a smaller margin. bullet size?
        return $this->_width + 2 * ListBullet::BULLET_PADDING;
    }

    /**
     * Override get_margin_height()
     *
     * @return float
     */
    public function get_margin_height(): float
    {
        //Hits only on "inset" lists items, to increase height of box
        //based on image height
        return $this->_height + 2 * ListBullet::BULLET_PADDING;
    }

    /**
     * Return image url
     *
     * @return string
     */
    function get_image_url()
    {
        return $this->_img->get_image_url();
    }

}
