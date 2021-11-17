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

/**
 * Decorates frames for list bullet rendering
 *
 * @package dompdf
 */
class ListBullet extends AbstractFrameDecorator
{
    /**
     * Bullet diameter as fraction of font size.
     */
    public const BULLET_SIZE = 0.35;

    /**
     * Bullet offset from font baseline as fraction of font size.
     */
    public const BULLET_OFFSET = 0.1;

    /**
     * Thickness of bullet outline as fraction of font size.
     * See also `DECO_THICKNESS`. Screen: 0.08, print: better less, e.g. 0.04.
     */
    public const BULLET_THICKNESS = 0.04;

    /**
     * Indentation from the start of the line as fraction of font size.
     */
    public const MARKER_INDENT = 0.52;

    /**
     * @deprecated
     */
    const BULLET_PADDING = 1;

    /**
     * @deprecated
     */
    const BULLET_DESCENT = 0.3;

    /**
     * @deprecated
     */
    static $BULLET_TYPES = ["disc", "circle", "square"];

    /**
     * ListBullet constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);
    }

    /**
     * Get the width of the bullet symbol.
     *
     * @return float
     */
    public function get_width(): float
    {
        $style = $this->_frame->get_style();

        if ($style->list_style_type === "none") {
            return 0.0;
        }

        return $style->font_size * self::BULLET_SIZE;
    }

    /**
     * Get the height of the bullet symbol.
     *
     * @return float
     */
    public function get_height(): float
    {
        $style = $this->_frame->get_style();

        if ($style->list_style_type === "none") {
            return 0.0;
        }

        return $style->font_size * self::BULLET_SIZE;
    }

    /**
     * Get the width of the bullet, including indentation.
     */
    public function get_margin_width(): float
    {
        $style = $this->get_style();

        if ($style->list_style_type === "none") {
            return 0.0;
        }

        return $style->font_size * (self::BULLET_SIZE + self::MARKER_INDENT);
    }

    /**
     * Get the line height for the bullet.
     *
     * This increases the height of the corresponding line box when necessary.
     */
    public function get_margin_height(): float
    {
        $style = $this->get_style();

        if ($style->list_style_type === "none") {
            return 0.0;
        }

        // TODO: This is a copy of `FrameDecorator\Text::get_margin_height()`
        // Would be nice to properly refactor that at some point
        $font = $style->font_family;
        $size = $style->font_size;
        $fontHeight = $this->_dompdf->getFontMetrics()->getFontHeight($font, $size);

        return ($style->line_height / ($size > 0 ? $size : 1)) * $fontHeight;
    }
}
