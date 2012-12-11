<?php
/**
 * @package dompdf
 * @link    http://www.dompdf.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Brian Sweeney <eclecticgeek@gmail.com>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Decorates Frame objects for text layout
 *
 * @access private
 * @package dompdf
 */
class Text_Frame_Decorator extends Frame_Decorator {
  
  // protected members
  protected $_text_spacing;
  
  // buggy DOMText::splitText (PHP < 5.2.7)
  public static $_buggy_splittext;
  
  function __construct(Frame $frame, DOMPDF $dompdf) {
    if ( !$frame->is_text_node() )
      throw new DOMPDF_Exception("Text_Decorator can only be applied to #text nodes.");
    
    parent::__construct($frame, $dompdf);
    $this->_text_spacing = null;
  }

  //........................................................................

  function reset() {
    parent::reset();
    $this->_text_spacing = null;
  }
  
  //........................................................................

  // Accessor methods
  function get_text_spacing() { return $this->_text_spacing; }
      
  function get_text() {
    // FIXME: this should be in a child class (and is incorrect)
//    if ( $this->_frame->get_style()->content !== "normal" ) {
//      $this->_frame->get_node()->data = $this->_frame->get_style()->content;
//      $this->_frame->get_style()->content = "normal";
//    }

//      pre_r("---");
//      $style = $this->_frame->get_style();
//      var_dump($text = $this->_frame->get_node()->data);
//      var_dump($asc = utf8_decode($text));
//      for ($i = 0; $i < strlen($asc); $i++)
//        pre_r("$i: " . $asc[$i] . " - " . ord($asc[$i]));
//      pre_r("width: " . Font_Metrics::get_text_width($text, $style->font_family, $style->font_size));

    return $this->_frame->get_node()->data;
  }

  //........................................................................

  // Vertical margins & padding do not apply to text frames

  // http://www.w3.org/TR/CSS21/visudet.html#inline-non-replaced:
  //
  // The vertical padding, border and margin of an inline, non-replaced box
  // start at the top and bottom of the content area, not the
  // 'line-height'. But only the 'line-height' is used to calculate the
  // height of the line box.
  function get_margin_height() {
    // This function is called in add_frame_to_line() and is used to
    // determine the line height, so we actually want to return the
    // 'line-height' property, not the actual margin box
    $style = $this->get_parent()->get_style();
    $font = $style->font_family;
    $size = $style->font_size;

    /*
    pre_r('-----');
    pre_r($style->line_height);
    pre_r($style->font_size);
    pre_r(Font_Metrics::get_font_height($font, $size));
    pre_r(($style->line_height / $size) * Font_Metrics::get_font_height($font, $size));
    */

    return ($style->line_height / $size) * Font_Metrics::get_font_height($font, $size);
    
  }

  function get_padding_box() {
    $pb = $this->_frame->get_padding_box();
    $pb[3] = $pb["h"] = $this->_frame->get_style()->height;
    return $pb;
  }
  //........................................................................

  // Set method
  function set_text_spacing($spacing) {
    $style = $this->_frame->get_style();
    
    $this->_text_spacing = $spacing;
    $char_spacing = $style->length_in_pt($style->letter_spacing);
    
    // Re-adjust our width to account for the change in spacing
    $style->width = Font_Metrics::get_text_width($this->get_text(), $style->font_family, $style->font_size, $spacing, $char_spacing);
  }

  //........................................................................

  // Recalculate the text width
  function recalculate_width() {
    $style = $this->get_style();
    $text = $this->get_text();
    $size = $style->font_size;
    $font = $style->font_family;
    $word_spacing = $style->length_in_pt($style->word_spacing);
    $char_spacing = $style->length_in_pt($style->letter_spacing);

    return $style->width = Font_Metrics::get_text_width($text, $font, $size, $word_spacing, $char_spacing);
  }
  
  //........................................................................

  // Text manipulation methods
  
  // split the text in this frame at the offset specified.  The remaining
  // text is added a sibling frame following this one and is returned.
  function split_text($offset) {
    if ( $offset == 0 )
      return null;

    if ( self::$_buggy_splittext ) {
      // workaround to solve DOMText::spliText() bug parsing multibyte strings
      $node = $this->_frame->get_node();
      $txt0 = $node->substringData(0, $offset);
      $txt1 = $node->substringData($offset, mb_strlen($node->textContent)-1);

      $node->replaceData(0, mb_strlen($node->textContent), $txt0);
      $split = $node->parentNode->appendChild(new DOMText($txt1));
    }
    else {
      $split = $this->_frame->get_node()->splitText($offset);
    }
    
    $deco = $this->copy($split);

    $p = $this->get_parent();
    $p->insert_child_after($deco, $this, false);

    if ( $p instanceof Inline_Frame_Decorator )
      $p->split($deco);

    return $deco;
  }

  //........................................................................

  function delete_text($offset, $count) {
    $this->_frame->get_node()->deleteData($offset, $count);
  }

  //........................................................................

  function set_text($text) {
    $this->_frame->get_node()->data = $text;
  }

}

Text_Frame_Decorator::$_buggy_splittext = PHP_VERSION_ID < 50207;
