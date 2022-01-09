<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Brian Sweeney <eclecticgeek@gmail.com>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\Exception;

/**
 * Decorates Frame objects for text layout
 *
 * @access  private
 * @package dompdf
 */
class Text extends AbstractFrameDecorator
{
    /**
     * @var float
     */
    protected $text_spacing;

    /**
     * Text constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     * @throws Exception
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        if (!$frame->is_text_node()) {
            throw new Exception("Text_Decorator can only be applied to #text nodes.");
        }

        parent::__construct($frame, $dompdf);
        $this->text_spacing = 0.0;
    }

    function reset()
    {
        parent::reset();
        $this->text_spacing = 0.0;
    }

    // Accessor methods

    /**
     * @return float
     */
    public function get_text_spacing(): float
    {
        return $this->text_spacing;
    }

    /**
     * @return string
     */
    function get_text()
    {
        // FIXME: this should be in a child class (and is incorrect)
//    if ( $this->_frame->get_style()->content !== "normal" ) {
//      $this->_frame->get_node()->data = $this->_frame->get_style()->content;
//      $this->_frame->get_style()->content = "normal";
//    }

//      Helpers::pre_r("---");
//      $style = $this->_frame->get_style();
//      var_dump($text = $this->_frame->get_node()->data);
//      var_dump($asc = utf8_decode($text));
//      for ($i = 0; $i < strlen($asc); $i++)
//        Helpers::pre_r("$i: " . $asc[$i] . " - " . ord($asc[$i]));
//      Helpers::pre_r("width: " . $this->_dompdf->getFontMetrics()->getTextWidth($text, $style->font_family, $style->font_size));

        return $this->_frame->get_node()->data;
    }

    //........................................................................

    /**
     * Vertical padding, border, and margin do not apply when determining the
     * height for inline frames.
     *
     * http://www.w3.org/TR/CSS21/visudet.html#inline-non-replaced
     *
     * The vertical padding, border and margin of an inline, non-replaced box
     * start at the top and bottom of the content area, not the
     * 'line-height'. But only the 'line-height' is used to calculate the
     * height of the line box.
     *
     * @return float
     */
    public function get_margin_height(): float
    {
        // This function is also called in add_frame_to_line() and is used to
        // determine the line height
        $style = $this->get_style();
        $font = $style->font_family;
        $size = $style->font_size;
        $fontHeight = $this->_dompdf->getFontMetrics()->getFontHeight($font, $size);

        return ($style->line_height / ($size > 0 ? $size : 1)) * $fontHeight;
    }

    public function get_padding_box(): array
    {
        $style = $this->_frame->get_style();
        $pb = $this->_frame->get_padding_box();
        $pb[3] = $pb["h"] = (float) $style->length_in_pt($style->height);
        return $pb;
    }

    /**
     * @param float $spacing
     */
    public function set_text_spacing(float $spacing): void
    {
        $this->text_spacing = $spacing;
        $this->recalculate_width();
    }

    /**
     * Recalculate the text width
     *
     * @return float
     */
    public function recalculate_width(): float
    {
        $fontMetrics = $this->_dompdf->getFontMetrics();
        $style = $this->get_style();
        $text = $this->get_text();
        $font = $style->font_family;
        $size = $style->font_size;
        $word_spacing = $this->text_spacing + $style->word_spacing;
        $letter_spacing = $style->letter_spacing;
        $text_width = $fontMetrics->getTextWidth($text, $font, $size, $word_spacing, $letter_spacing);

        $style->set_used("width", $text_width);
        return $text_width;
    }

    // Text manipulation methods

    /**
     * Split the text in this frame at the offset specified.  The remaining
     * text is added as a sibling frame following this one and is returned.
     *
     * @param int $offset
     * @return Frame|null
     */
    function split_text($offset)
    {
        if ($offset == 0) {
            return null;
        }

        $split = $this->_frame->get_node()->splitText($offset);
        if ($split === false) {
            return null;
        }
        
        $deco = $this->copy($split);

        $p = $this->get_parent();
        $p->insert_child_after($deco, $this, false);

        if ($p instanceof Inline) {
            $p->split($deco);
        }

        return $deco;
    }

    /**
     * @param int $offset
     * @param int $count
     */
    function delete_text($offset, $count)
    {
        $this->_frame->get_node()->deleteData($offset, $count);
    }

    /**
     * @param string $text
     */
    function set_text($text)
    {
        $this->_frame->get_node()->data = $text;
    }
}
