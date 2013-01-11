<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Reflows text frames.
 *
 * @access private
 * @package dompdf
 */
class Text_Frame_Reflower extends Frame_Reflower {

  /**
   * @var Block_Frame_Decorator
   */
  protected $_block_parent; // Nearest block-level ancestor
  
  /**
   * @var Text_Frame_Decorator
   */
  protected $_frame;
  
  public static $_whitespace_pattern = "/[ \t\r\n\f]+/u";

  function __construct(Text_Frame_Decorator $frame) { parent::__construct($frame); }

  //........................................................................

  protected function _collapse_white_space($text) {
    //$text = $this->_frame->get_text();
//     if ( $this->_block_parent->get_current_line_box->w == 0 )
//       $text = ltrim($text, " \n\r\t");
    return preg_replace(self::$_whitespace_pattern, " ", $text);
  }

  //........................................................................

  protected function _line_break($text) {
    $style = $this->_frame->get_style();
    $size = $style->font_size;
    $font = $style->font_family;
    $current_line = $this->_block_parent->get_current_line_box();
    
    // Determine the available width
    $line_width = $this->_frame->get_containing_block("w");
    $current_line_width = $current_line->left + $current_line->w + $current_line->right;
    
    $available_width = $line_width - $current_line_width;

    // Account for word-spacing
    $word_spacing = $style->length_in_pt($style->word_spacing);
    $char_spacing = $style->length_in_pt($style->letter_spacing);

    // Determine the frame width including margin, padding & border
    $text_width = Font_Metrics::get_text_width($text, $font, $size, $word_spacing, $char_spacing);
    $mbp_width =
      $style->length_in_pt( array( $style->margin_left,
                                   $style->border_left_width,
                                   $style->padding_left,
                                   $style->padding_right,
                                   $style->border_right_width,
                                   $style->margin_right), $line_width );
                                   
    $frame_width = $text_width + $mbp_width;

// Debugging:
//    pre_r("Text: '" . htmlspecialchars($text). "'");
//    pre_r("width: " .$frame_width);
//    pre_r("textwidth + delta: $text_width + $mbp_width");
//    pre_r("font-size: $size");
//    pre_r("cb[w]: " .$line_width);
//    pre_r("available width: " . $available_width);
//    pre_r("current line width: " . $current_line_width);

//     pre_r($words);

    if ( $frame_width <= $available_width )
      return false;

    // split the text into words
    $words = preg_split('/([\s-]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
    $wc = count($words);

    // Determine the split point
    $width = 0;
    $str = "";
    reset($words);

    // @todo support <shy>, <wbr>
    for ($i = 0; $i < $wc; $i += 2) {
      $word = $words[$i] . (isset($words[$i+1]) ? $words[$i+1] : "");
      $word_width = Font_Metrics::get_text_width($word, $font, $size, $word_spacing, $char_spacing);
      if ( $width + $word_width + $mbp_width > $available_width )
        break;

      $width += $word_width;
      $str .= $word;
    }

    $break_word = ($style->word_wrap === "break-word");
    
    // The first word has overflowed.   Force it onto the line
    if ( $current_line_width == 0 && $width == 0 ) {
      
      $s = "";
      $last_width = 0;
        
      if ( $break_word ) {
        for ( $j = 0; $j < strlen($word); $j++ ) {
          $s .= $word[$j];
          $_width = Font_Metrics::get_text_width($s, $font, $size, $word_spacing, $char_spacing);
          if ($_width > $available_width) {
            break;
          }
          
          $last_width = $_width;
        }
      }
      
      if ( $break_word && $last_width > 0 ) {
        $width += $last_width;
        $str .= substr($s, 0, -1);
      }
      else {
        $width += $word_width;
        $str .= $word;
      }
    }

    $offset = mb_strlen($str);

// More debugging:
//     pre_var_dump($str);
//     pre_r("Width: ". $width);
//     pre_r("Offset: " . $offset);

    return $offset;

  }

  //........................................................................

  protected function _newline_break($text) {

    if ( ($i = mb_strpos($text, "\n")) === false)
      return false;

    return $i+1;

  }

  //........................................................................

  protected function _layout_line() {
    $frame = $this->_frame;
    $style = $frame->get_style();
    $text = $frame->get_text();
    $size = $style->font_size;
    $font = $style->font_family;

    // Determine the text height
    $style->height = Font_Metrics::get_font_height( $font, $size );

    $split = false;
    $add_line = false;

    // Handle text transform:
    // http://www.w3.org/TR/CSS21/text.html#propdef-text-transform
    switch (strtolower($style->text_transform)) {
      default: break;
      case "capitalize": $text = mb_convert_case($text, MB_CASE_TITLE); break;
      case "uppercase":  $text = mb_convert_case($text, MB_CASE_UPPER); break;
      case "lowercase":  $text = mb_convert_case($text, MB_CASE_LOWER); break;
    }
    
    // Handle white-space property:
    // http://www.w3.org/TR/CSS21/text.html#propdef-white-space
    switch ($style->white_space) {

    default:
    case "normal":
      $frame->set_text( $text = $this->_collapse_white_space($text) );
      if ( $text == "" )
        break;

      $split = $this->_line_break($text);
      break;

    case "pre":
      $split = $this->_newline_break($text);
      $add_line = $split !== false;
      break;

    case "nowrap":
      $frame->set_text( $text = $this->_collapse_white_space($text) );
      break;

    case "pre-wrap":
      $split = $this->_newline_break($text);

      if ( ($tmp = $this->_line_break($text)) !== false ) {
        $add_line = $split < $tmp;
        $split = min($tmp, $split);
      } else
        $add_line = true;

      break;

    case "pre-line":
      // Collapse white-space except for \n
      $frame->set_text( $text = preg_replace( "/[ \t]+/u", " ", $text ) );

      if ( $text == "" )
        break;

      $split = $this->_newline_break($text);

      if ( ($tmp = $this->_line_break($text)) !== false ) {
        $add_line = $split < $tmp;
        $split = min($tmp, $split);
      } else
        $add_line = true;

      break;

    }

    // Handle degenerate case
    if ( $text === "" )
      return;

    if ( $split !== false) {

      // Handle edge cases
      if ( $split == 0 && $text === " " ) {
        $frame->set_text("");
        return;
      }

      if ( $split == 0 ) {

        // Trim newlines from the beginning of the line
        //$this->_frame->set_text(ltrim($text, "\n\r"));

        $this->_block_parent->add_line();
        $frame->position();

        // Layout the new line
        $this->_layout_line();

      } 
      
      else if ( $split < mb_strlen($frame->get_text()) ) {
        // split the line if required
        $frame->split_text($split);

        $t = $frame->get_text();
        
        // Remove any trailing newlines
        if ( $split > 1 && $t[$split-1] === "\n" && !$frame->is_pre() )
          $frame->set_text( mb_substr($t, 0, -1) );

        // Do we need to trim spaces on wrapped lines? This might be desired, however, we 
        // can't trim the lines here or the layout will be affected if trimming the line 
        // leaves enough space to fit the next word in the text stream (because pdf layout  
        // is performed elsewhere).
        /*if (!$this->_frame->get_prev_sibling() && !$this->_frame->get_next_sibling()) {
          $t = $this->_frame->get_text();
          $this->_frame->set_text( trim($t) );
        }*/
      }

      if ( $add_line ) {
        $this->_block_parent->add_line();
        $frame->position();
      }

    } else {

      // Remove empty space from start and end of line, but only where there isn't an inline sibling
      // and the parent node isn't an inline element with siblings
      // FIXME: Include non-breaking spaces?
      $t = $frame->get_text();
      $parent = $frame->get_parent();
      $is_inline_frame = get_class($parent) === 'Inline_Frame_Decorator';
      
      if ((!$is_inline_frame && !$frame->get_next_sibling())/* ||
          ( $is_inline_frame && !$parent->get_next_sibling())*/) { // fails <b>BOLD <u>UNDERLINED</u></b> becomes <b>BOLD<u>UNDERLINED</u></b>
        $t = rtrim($t);
      }
      
      if ((!$is_inline_frame && !$frame->get_prev_sibling())/* || 
          ( $is_inline_frame && !$parent->get_prev_sibling())*/) { //  <span><span>A<span>B</span> C</span></span> fails (the whitespace is removed)
        $t = ltrim($t);
      }
      
      $frame->set_text( $t );
      
    }

    // Set our new width
    $width = $frame->recalculate_width();
  }

  //........................................................................

  function reflow(Block_Frame_Decorator $block = null) {
    $frame = $this->_frame;
    $page = $frame->get_root();
    $page->check_forced_page_break($this->_frame);
    
    if ( $page->is_full() )
      return;

    $this->_block_parent = /*isset($block) ? $block : */$frame->find_block_parent();

    // Left trim the text if this is the first text on the line and we're
    // collapsing white space
//     if ( $this->_block_parent->get_current_line()->w == 0 &&
//          ($frame->get_style()->white_space !== "pre" ||
//           $frame->get_style()->white_space !== "pre-wrap") ) {
//       $frame->set_text( ltrim( $frame->get_text() ) );
//     }
    
    $frame->position();

    $this->_layout_line();
    
    if ( $block ) {
      $block->add_frame_to_line($frame);
    }
  }

  //........................................................................

  // Returns an array(0 => min, 1 => max, "min" => min, "max" => max) of the
  // minimum and maximum widths of this frame
  function get_min_max_width() {
    /*if ( !is_null($this->_min_max_cache)  )
      return $this->_min_max_cache;*/
    $frame = $this->_frame;
    $style = $frame->get_style();
    $this->_block_parent = $frame->find_block_parent();
    $line_width = $frame->get_containing_block("w");

    $str = $text = $frame->get_text();
    $size = $style->font_size;
    $font = $style->font_family;

    $word_spacing = $style->length_in_pt($style->word_spacing);
    $char_spacing = $style->length_in_pt($style->letter_spacing);

    switch($style->white_space) {

    default:
    case "normal":
      $str = preg_replace(self::$_whitespace_pattern," ", $str);
    case "pre-wrap":
    case "pre-line":

      // Find the longest word (i.e. minimum length)

      // This technique (using arrays & an anonymous function) is actually
      // faster than doing a single-pass character by character scan.  Heh,
      // yes I took the time to bench it ;)
      $words = array_flip(preg_split("/[\s-]+/u",$str, -1, PREG_SPLIT_DELIM_CAPTURE));
      /*foreach($words as &$word) {
        $word = Font_Metrics::get_text_width($word, $font, $size, $word_spacing, $char_spacing);
      }*/
      array_walk($words, create_function('&$val,$str',
                                         '$val = Font_Metrics::get_text_width($str, "'.addslashes($font).'", '.$size.', '.$word_spacing.', '.$char_spacing.');'));
      arsort($words);
      $min = reset($words);
      break;

    case "pre":
      $lines = array_flip(preg_split("/\n/u", $str));
      /*foreach($words as &$word) {
        $word = Font_Metrics::get_text_width($word, $font, $size, $word_spacing, $char_spacing);
      }*/
      array_walk($lines, create_function('&$val,$str',
                                         '$val = Font_Metrics::get_text_width($str, "'.addslashes($font).'", '.$size.', '.$word_spacing.', '.$char_spacing.');'));

      arsort($lines);
      $min = reset($lines);
      break;

    case "nowrap":
      $min = Font_Metrics::get_text_width($this->_collapse_white_space($str), $font, $size, $word_spacing, $char_spacing);
      break;

    }

    switch ($style->white_space) {

    default:
    case "normal":
    case "nowrap":
      $str = preg_replace(self::$_whitespace_pattern," ", $text);
      break;

    case "pre-line":
      //XXX: Is this correct?
      $str = preg_replace( "/[ \t]+/u", " ", $text);

    case "pre-wrap":
      // Find the longest word (i.e. minimum length)
      $lines = array_flip(preg_split("/\n/", $text));
      /*foreach($words as &$word) {
        $word = Font_Metrics::get_text_width($word, $font, $size, $word_spacing, $char_spacing);
      }*/
      array_walk($lines, create_function('&$val,$str',
                                         '$val = Font_Metrics::get_text_width($str, "'.$font.'", '.$size.', '.$word_spacing.', '.$char_spacing.');'));
      arsort($lines);
      reset($lines);
      $str = key($lines);
      break;

    }

    $max = Font_Metrics::get_text_width($str, $font, $size, $word_spacing, $char_spacing);
    
    $delta = $style->length_in_pt(array($style->margin_left,
                                        $style->border_left_width,
                                        $style->padding_left,
                                        $style->padding_right,
                                        $style->border_right_width,
                                        $style->margin_right), $line_width);
    $min += $delta;
    $max += $delta;

    return $this->_min_max_cache = array($min, $max, "min" => $min, "max" => $max);

  }

}
