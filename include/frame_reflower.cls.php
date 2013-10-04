<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Base reflower class
 *
 * Reflower objects are responsible for determining the width and height of
 * individual frames.  They also create line and page breaks as necessary.
 *
 * @access private
 * @package dompdf
 */
abstract class Frame_Reflower {

  /**
   * Frame for this reflower
   *
   * @var Frame
   */
  protected $_frame;

  /**
   * Cached min/max size
   *
   * @var array
   */
  protected $_min_max_cache;
  
  function __construct(Frame $frame) {
    $this->_frame = $frame;
    $this->_min_max_cache = null;
  }

  function dispose() {
    clear_object($this);
  }

  /**
   * @return DOMPDF
   */
  function get_dompdf() {
    return $this->_frame->get_dompdf();
  }

  /**
   * Collapse frames margins
   * http://www.w3.org/TR/CSS2/box.html#collapsing-margins
   */
  protected function _collapse_margins() {
    $frame = $this->_frame;
    $cb = $frame->get_containing_block();
    $style = $frame->get_style();
    
    if ( !$frame->is_in_flow() ) {
      return;
    }

    $t = $style->length_in_pt($style->margin_top, $cb["h"]);
    $b = $style->length_in_pt($style->margin_bottom, $cb["h"]);

    // Handle 'auto' values
    if ( $t === "auto" ) {
      $style->margin_top = "0pt";
      $t = 0;
    }

    if ( $b === "auto" ) {
      $style->margin_bottom = "0pt";
      $b = 0;
    }

    // Collapse vertical margins:
    $n = $frame->get_next_sibling();
    if ( $n && !$n->is_block() ) {
      while ( $n = $n->get_next_sibling() ) {
        if ( $n->is_block() ) {
          break;
        }
        
        if ( !$n->get_first_child() ) {
          $n = null;
          break;
        }
      }
    }
    
    if ( $n ) {
      $n_style = $n->get_style();
      $b = max($b, $n_style->length_in_pt($n_style->margin_top, $cb["h"]));
      $n_style->margin_top = "0pt";
      $style->margin_bottom = $b."pt";
    }

    // Collapse our first child's margin
    /*$f = $this->_frame->get_first_child();
    if ( $f && !$f->is_block() ) {
      while ( $f = $f->get_next_sibling() ) {
        if ( $f->is_block() ) {
          break;
        }
        
        if ( !$f->get_first_child() ) {
          $f = null;
          break;
        }
      }
    }

    // Margin are collapsed only between block elements
    if ( $f ) {
      $f_style = $f->get_style();
      $t = max($t, $f_style->length_in_pt($f_style->margin_top, $cb["h"]));
      $style->margin_top = $t."pt";
      $f_style->margin_bottom = "0pt";
    }*/
  }

  //........................................................................

  abstract function reflow(Block_Frame_Decorator $block = null);

  //........................................................................

  // Required for table layout: Returns an array(0 => min, 1 => max, "min"
  // => min, "max" => max) of the minimum and maximum widths of this frame.
  // This provides a basic implementation.  Child classes should override
  // this if necessary.
  function get_min_max_width() {
    if ( !is_null($this->_min_max_cache) ) {
      return $this->_min_max_cache;
    }
    
    $style = $this->_frame->get_style();

    // Account for margins & padding
    $dims = array($style->padding_left,
                  $style->padding_right,
                  $style->border_left_width,
                  $style->border_right_width,
                  $style->margin_left,
                  $style->margin_right);

    $cb_w = $this->_frame->get_containing_block("w");
    $delta = $style->length_in_pt($dims, $cb_w);

    // Handle degenerate case
    if ( !$this->_frame->get_first_child() ) {
      return $this->_min_max_cache = array(
        $delta, $delta,
        "min" => $delta, 
        "max" => $delta,
      );
    }

    $low = array();
    $high = array();

    for ( $iter = $this->_frame->get_children()->getIterator();
          $iter->valid();
          $iter->next() ) {

      $inline_min = 0;
      $inline_max = 0;

      // Add all adjacent inline widths together to calculate max width
      while ( $iter->valid() && in_array( $iter->current()->get_style()->display, Style::$INLINE_TYPES ) ) {

        $child = $iter->current();

        $minmax = $child->get_min_max_width();

        if ( in_array( $iter->current()->get_style()->white_space, array("pre", "nowrap") ) ) {
          $inline_min += $minmax["min"];
        }
        else {
          $low[] = $minmax["min"];
        }

        $inline_max += $minmax["max"];
        $iter->next();

      }

      if ( $inline_max > 0 ) $high[] = $inline_max;
      if ( $inline_min > 0 ) $low[]  = $inline_min;

      if ( $iter->valid() ) {
        list($low[], $high[]) = $iter->current()->get_min_max_width();
        continue;
      }

    }
    $min = count($low) ? max($low) : 0;
    $max = count($high) ? max($high) : 0;

    // Use specified width if it is greater than the minimum defined by the
    // content.  If the width is a percentage ignore it for now.
    $width = $style->width;
    if ( $width !== "auto" && !is_percent($width) ) {
      $width = $style->length_in_pt($width, $cb_w);
      if ( $min < $width ) $min = $width;
      if ( $max < $width ) $max = $width;
    }

    $min += $delta;
    $max += $delta;
    return $this->_min_max_cache = array($min, $max, "min"=>$min, "max"=>$max);
  }

  /**
   * Parses a CSS string containing quotes and escaped hex characters
   * 
   * @param $string string The CSS string to parse
   * @param $single_trim
   * @return string
   */
  protected function _parse_string($string, $single_trim = false) {
    if ( $single_trim ) {
      $string = preg_replace('/^[\"\']/', "", $string);
      $string = preg_replace('/[\"\']$/', "", $string);
    }
    else {
      $string = trim($string, "'\"");
    }
    
    $string = str_replace(array("\\\n",'\\"',"\\'"),
                          array("",'"',"'"), $string);

    // Convert escaped hex characters into ascii characters (e.g. \A => newline)
    $string = preg_replace_callback("/\\\\([0-9a-fA-F]{0,6})/",
                                    create_function('$matches',
                                                    'return unichr(hexdec($matches[1]));'),
                                    $string);
    return $string;
  }
  
  /**
   * Parses a CSS "quotes" property
   * 
   * @return array|null An array of pairs of quotes
   */
  protected function _parse_quotes() {
    
    // Matches quote types
    $re = '/(\'[^\']*\')|(\"[^\"]*\")/';
    
    $quotes = $this->_frame->get_style()->quotes;
      
    // split on spaces, except within quotes
    if ( !preg_match_all($re, "$quotes", $matches, PREG_SET_ORDER) ) {
      return null;
    }
      
    $quotes_array = array();
    foreach($matches as &$_quote){
      $quotes_array[] = $this->_parse_string($_quote[0], true);
    }
    
    if ( empty($quotes_array) ) {
      $quotes_array = array('"', '"');
    }
    
    return array_chunk($quotes_array, 2);
  }

  /**
   * Parses the CSS "content" property
   * 
   * @return string|null The resulting string
   */
  protected function _parse_content() {

    // Matches generated content
    $re = "/\n".
      "\s(counters?\\([^)]*\\))|\n".
      "\A(counters?\\([^)]*\\))|\n".
      "\s([\"']) ( (?:[^\"']|\\\\[\"'])+ )(?<!\\\\)\\3|\n".
      "\A([\"']) ( (?:[^\"']|\\\\[\"'])+ )(?<!\\\\)\\5|\n" .
      "\s([^\s\"']+)|\n" .
      "\A([^\s\"']+)\n".
      "/xi";
    
    $content = $this->_frame->get_style()->content;

    $quotes = $this->_parse_quotes();
    
    // split on spaces, except within quotes
    if ( !preg_match_all($re, $content, $matches, PREG_SET_ORDER) ) {
      return null;
    }
      
    $text = "";

    foreach ($matches as $match) {
      
      if ( isset($match[2]) && $match[2] !== "" ) {
        $match[1] = $match[2];
      }
      
      if ( isset($match[6]) && $match[6] !== "" ) {
        $match[4] = $match[6];
      }

      if ( isset($match[8]) && $match[8] !== "" ) {
        $match[7] = $match[8];
      }

      if ( isset($match[1]) && $match[1] !== "" ) {
        
        // counters?(...)
        $match[1] = mb_strtolower(trim($match[1]));

        // Handle counter() references:
        // http://www.w3.org/TR/CSS21/generate.html#content

        $i = mb_strpos($match[1], ")");
        if ( $i === false ) {
          continue;
        }

        preg_match( '/(counters?)(^\()*?\(\s*([^\s,]+)\s*(,\s*["\']?([^"\'\)]+)["\']?\s*(,\s*([^\s)]+)\s*)?)?\)/i' , $match[1] , $args );
        $counter_id = $args[3];
        if ( strtolower( $args[1] ) == 'counter' ) {
          // counter(name [,style])
          if ( isset( $args[5] ) ) {
            $type = trim( $args[1] );
          }
          else {
            $type = null;
          }

          $p = $this->_frame->lookup_counter_frame( $counter_id );
          
          $text .= $p->counter_value($counter_id, $type);

        }
        else if ( strtolower( $args[1] ) == 'counters' ) {
          // counters(name, string [,style])
          if ( isset($args[5]) ) {
            $string = $this->_parse_string( $args[5] );
          }
          else {
            $string = "";
          }

          if ( isset( $args[7] ) ) {
            $type = trim( $args[7] );
          }
          else {
            $type = null;
          }

          $p = $this->_frame->lookup_counter_frame($counter_id);
          $tmp = array();
          while ($p) {
            // We only want to use the counter values when they actually increment the counter,
            // elements that reset the counter, but do not increment it, are skipped.
            // FIXME: Is this the best method of determining that an element's counter value should be displayed?
            if ( array_key_exists( $counter_id , $p->_counters ) && $p->get_frame()->get_style()->counter_reset == 'none' ) {
              array_unshift( $tmp , $p->counter_value($counter_id, $type) );
            }
            $p = $p->lookup_counter_frame($counter_id);
            
          }
          $text .= implode( $string , $tmp );

        }
        else {
          // countertops?
          continue;
        }

      }
      else if ( isset($match[4]) && $match[4] !== "" ) {
        // String match
        $text .= $this->_parse_string($match[4]);
      }
      else if ( isset($match[7]) && $match[7] !== "" ) {
        // Directive match

        if ( $match[7] === "open-quote" ) {
          // FIXME: do something here
          $text .= $quotes[0][0];
        }
        else if ( $match[7] === "close-quote" ) {
          // FIXME: do something else here
          $text .= $quotes[0][1];
        }
        else if ( $match[7] === "no-open-quote" ) {
          // FIXME:
        }
        else if ( $match[7] === "no-close-quote" ) {
          // FIXME:
        }
        else if ( mb_strpos($match[7],"attr(") === 0 ) {

          $i = mb_strpos($match[7],")");
          if ( $i === false ) {
            continue;
          }

          $attr = mb_substr($match[7], 5, $i - 5);
          if ( $attr == "" ) {
            continue;
          }
            
          $text .= $this->_frame->get_parent()->get_node()->getAttribute($attr);
        }
        else {
          continue;
        }
      }
    }

    return $text;
  }
  
  /**
   * Sets the generated content of a generated frame
   */
  protected function _set_content(){
    $frame = $this->_frame;
    $style = $frame->get_style();
    
    if ( $style->counter_reset && ($reset = $style->counter_reset) !== "none" ) {
      $vars = preg_split('/\s+/', trim($reset), 2);
      $frame->reset_counter($vars[0], isset($vars[1]) ? $vars[1] : 0);
    }
    
    if ( $style->counter_increment && ($increment = $style->counter_increment) !== "none" ) {
      $frame->increment_counters($increment);
    }
  
    if ( $style->content && !$frame->get_first_child() && $frame->get_node()->nodeName === "dompdf_generated" ) {
      $content = $this->_parse_content();
      $node = $frame->get_node()->ownerDocument->createTextNode($content);
      
      $new_style = $style->get_stylesheet()->create_style();
      $new_style->inherit($style);
      
      $new_frame = new Frame($node);
      $new_frame->set_style($new_style);
      
      Frame_Factory::decorate_frame($new_frame, $frame->get_dompdf(), $frame->get_root());
      $frame->append_child($new_frame);
    }
  }
}
