<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Css;

use Dompdf\Dompdf;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameDecorator\Inline as InlineFrameDecorator;
use Dompdf\FrameDecorator\ListBullet as ListBulletFrameDecorator;
use Dompdf\FrameDecorator\ListBulletImage as ListBulletImageFrameDecorator;
use Dompdf\FrameDecorator\Text as TextFrameDecorator;

/**
 * Line height calculation of the "css" line height mode (see
 * {@link \Dompdf\Options::setLineHeightMode()}).
 *
 * @link https://www.w3.org/TR/CSS21/visudet.html#line-height
 */
class LineHeight
{
    /**
     * Whether the "css" line height mode is in use. The canvas has to be able
     * to provide the metrics of the font: the CPDF, PDFLib and GD adapters do.
     */
    public static function enabled(Dompdf $dompdf): bool
    {
        if ($dompdf->getOptions()->getLineHeightMode() !== "css") {
            return false;
        }

        $canvas = $dompdf->getCanvas();

        return method_exists($canvas, "get_font_ascent")
            && method_exists($canvas, "get_font_descent")
            && method_exists($canvas, "get_font_text_offset");
    }

    /**
     * Metrics needed to place the text of a frame in a line box, in points:
     *
     * - ascent: distance from the baseline to the top of the font
     * - descent: distance from the baseline to the bottom of the font
     * - offset: distance from the top of the frame to the baseline of its text,
     *   as drawn by the canvas
     * - line_height: the used value of `line-height`; for `normal` the height
     *   of the font (ascent + descent)
     *
     * @return array|null Null if the metrics are not available
     */
    public static function metrics(AbstractFrameDecorator $frame): ?array
    {
        $style = $frame->get_style();
        $font = $style->font_family;
        $size = (float) $style->font_size;
        $fontMetrics = $frame->get_dompdf()->getFontMetrics();

        $ascent = $fontMetrics->getFontAscent($font, $size);
        $descent = $fontMetrics->getFontDescent($font, $size);
        $offset = $fontMetrics->getFontTextOffset($font, $size);
        if ($ascent === null || $descent === null || $offset === null) {
            return null;
        }

        $normal = strtolower((string) $style->get_specified("line_height")) === "normal";
        $lineHeight = $normal ? $ascent + $descent : (float) $style->line_height;

        return [
            "ascent" => $ascent,
            "descent" => $descent,
            "offset" => $offset,
            "line_height" => $lineHeight
        ];
    }

    /**
     * Whether the frame is laid out as text, i.e. it has a baseline and its
     * line box contribution is given by its line height.
     */
    protected static function is_text(AbstractFrameDecorator $frame): bool
    {
        if ($frame instanceof ListBulletImageFrameDecorator) {
            return false;
        }

        return $frame instanceof TextFrameDecorator
            || $frame instanceof InlineFrameDecorator
            || $frame instanceof ListBulletFrameDecorator;
    }

    /**
     * Whether the frame is aligned to the top or the bottom of the line box
     * instead of the baseline. Such frames do not extend the line box above or
     * below the baseline, they only require the line box to be tall enough.
     */
    public static function is_line_aligned(AbstractFrameDecorator $frame): bool
    {
        return !self::is_text($frame)
            && in_array($frame->get_style()->vertical_align, ["top", "bottom"], true);
    }

    /**
     * The part of the line box above and below the baseline needed by a frame.
     *
     * The content area of the font is centered in the line height: half of the
     * leading goes above it, half below.
     *
     * @param float $height The margin height of the frame
     *
     * @return float[] [above the baseline, below the baseline]
     */
    public static function extents(AbstractFrameDecorator $frame, float $height): array
    {
        $metrics = self::is_text($frame) ? self::metrics($frame) : null;

        if ($metrics === null) {
            // Atomic inline boxes and replaced elements sit on the baseline
            return [$height, 0.0];
        }

        $halfLeading = ($metrics["line_height"] - $metrics["ascent"] - $metrics["descent"]) / 2;
        $above = max($halfLeading + $metrics["ascent"], 0.0);
        $below = max($halfLeading + $metrics["descent"], 0.0);

        // Callers may ask for a minimum height
        if ($above + $below < $height) {
            $below = $height - $above;
        }

        return [$above, $below];
    }
}
