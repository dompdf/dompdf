<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\FrameDecorator;

use Dompdf\Dompdf;
use Dompdf\Frame;
use Dompdf\LineBox;
use Dompdf\FrameReflower\Text as TextFrameReflower;

/**
 * Decorates frames for block layout
 *
 * @access  private
 * @package dompdf
 */
class Block extends AbstractFrameDecorator
{
    /**
     * Current line index
     *
     * @var int
     */
    protected $_cl;

    /**
     * The block's line boxes
     *
     * @var LineBox[]
     */
    protected $_line_boxes;

    /**
     * Block constructor.
     * @param Frame $frame
     * @param Dompdf $dompdf
     */
    function __construct(Frame $frame, Dompdf $dompdf)
    {
        parent::__construct($frame, $dompdf);

        $this->_line_boxes = [new LineBox($this)];
        $this->_cl = 0;
    }

    /**
     *
     */
    function reset()
    {
        parent::reset();

        $this->_line_boxes = [new LineBox($this)];
        $this->_cl = 0;
    }

    /**
     * @return LineBox
     */
    function get_current_line_box()
    {
        return $this->_line_boxes[$this->_cl];
    }

    /**
     * @return int
     */
    function get_current_line_number()
    {
        return $this->_cl;
    }

    /**
     * @return LineBox[]
     */
    function get_line_boxes()
    {
        return $this->_line_boxes;
    }

    /**
     * @param int $line_number
     * @return int
     */
    function set_current_line_number($line_number)
    {
        $line_boxes_count = count($this->_line_boxes);
        $cl = max(min($line_number, $line_boxes_count), 0);
        return ($this->_cl = $cl);
    }

    /**
     * @param int $i
     */
    function clear_line($i)
    {
        if (isset($this->_line_boxes[$i])) {
            unset($this->_line_boxes[$i]);
        }
    }

    /**
     * @param Frame $frame
     */
    function add_frame_to_line(Frame $frame)
    {
        if (!$frame->is_in_flow()) {
            return;
        }

        $style = $frame->get_style();

        $frame->set_containing_line($this->_line_boxes[$this->_cl]);

        /*
        // Adds a new line after a block, only if certain conditions are met
        if ((($frame instanceof Inline && $frame->get_node()->nodeName !== "br") ||
              $frame instanceof Text && trim($frame->get_text())) &&
            ($frame->get_prev_sibling() && $frame->get_prev_sibling()->get_style()->display === "block" &&
             $this->_line_boxes[$this->_cl]->w > 0 )) {

               $this->maximize_line_height( $style->length_in_pt($style->line_height), $frame );
               $this->add_line();

               // Add each child of the inline frame to the line individually
               foreach ($frame->get_children() as $child)
                 $this->add_frame_to_line( $child );
        }
        else*/

        // Handle inline frames (which are effectively wrappers)
        if ($frame instanceof Inline) {
            // Handle line breaks
            if ($frame->get_node()->nodeName === "br") {
                $this->maximize_line_height($style->line_height, $frame);
                $this->add_line(true);

                $next = $frame->get_next_sibling();
                $p = $frame->get_parent();

                if ($next && $p instanceof Inline) {
                    $p->split($next);
                }
            }

            return;
        }

        // Trim leading text if this is an empty line.  Kinda a hack to put it here,
        // but what can you do...
        if ($this->get_current_line_box()->w == 0 &&
            $frame->is_text_node() &&
            !$frame->is_pre()
        ) {
            $frame->set_text(ltrim($frame->get_text()));
            $frame->recalculate_width();
        }

        $w = $frame->get_margin_width();

        // FIXME: Why? Doesn't quite seem to be the correct thing to do,
        // but does appear to be necessary. Hack to handle wrapped white space?
        if ($w == 0 && $frame->get_node()->nodeName !== "hr" && !$frame->is_pre()) {
            return;
        }

        // Debugging code:
        /*
        Helpers::pre_r("\n<h3>Adding frame to line:</h3>");

        //    Helpers::pre_r("Me: " . $this->get_node()->nodeName . " (" . spl_object_hash($this->get_node()) . ")");
        //    Helpers::pre_r("Node: " . $frame->get_node()->nodeName . " (" . spl_object_hash($frame->get_node()) . ")");
        if ( $frame->is_text_node() )
          Helpers::pre_r('"'.$frame->get_node()->nodeValue.'"');

        Helpers::pre_r("Line width: " . $this->_line_boxes[$this->_cl]->w);
        Helpers::pre_r("Frame: " . get_class($frame));
        Helpers::pre_r("Frame width: "  . $w);
        Helpers::pre_r("Frame height: " . $frame->get_margin_height());
        Helpers::pre_r("Containing block width: " . $this->get_containing_block("w"));
        */
        // End debugging

        $current_line = $this->_line_boxes[$this->_cl];
        $current_line->add_frame($frame);

        if ($frame->is_text_node()) {
            $trimmed = trim($frame->get_text());

            if ($trimmed !== "") {
                // split the text into words (used to determine spacing between words on justified lines)
                // The regex splits on everything that's a separator (^\S double negative), excluding nbsp (\xa0)
                // This currently excludes the "narrow nbsp" character
                $words = preg_split('/[^\S\xA0]+/u', $trimmed);
                $current_line->wc += count($words);
            }
        }

        $this->increase_line_width($w);
        $this->maximize_line_height($frame->get_margin_height(), $frame);
    }

    /**
     * Remove the given frame and all following frames and lines from the block.
     *
     * @param Frame $frame
     */
    public function remove_frames_from_line(Frame $frame): void
    {
        // Inline frames are not added to line boxes themselves, only their
        // text frame children
        $actualFrame = $frame;
        while ($actualFrame !== null && $actualFrame instanceof Inline) {
            $actualFrame = $actualFrame->get_first_child();
        }

        if ($actualFrame === null) {
            return;
        }

        // Search backwards through the lines for $frame
        $frame = $actualFrame;
        $i = $this->_cl;
        $j = null;

        while ($i > 0) {
            $line = $this->_line_boxes[$i];
            foreach ($line->get_frames() as $index => $f) {
                if ($frame === $f) {
                    $j = $index;
                    break 2;
                }
            }
            $i--;
        }

        if ($j === null) {
            return;
        }

        // Remove all lines that follow
        for ($k = $this->_cl; $k > $i; $k--) {
            unset($this->_line_boxes[$k]);
        }

        // Remove the line, if it is empty
        if ($j > 0) {
            $line->remove_frames($j);
        } else {
            unset($this->_line_boxes[$i]);
        }

        // Reset array indices
        $this->_line_boxes = array_values($this->_line_boxes);
        $this->_cl = count($this->_line_boxes) - 1;
    }

    /**
     * @param float $w
     */
    function increase_line_width($w)
    {
        $this->_line_boxes[$this->_cl]->w += $w;
    }

    /**
     * @param float $val
     * @param Frame $frame
     */
    function maximize_line_height($val, Frame $frame)
    {
        if ($val > $this->_line_boxes[$this->_cl]->h) {
            $this->_line_boxes[$this->_cl]->tallest_frame = $frame;
            $this->_line_boxes[$this->_cl]->h = $val;
        }
    }

    /**
     * @param bool $br
     */
    function add_line(bool $br = false)
    {
        $line = $this->_line_boxes[$this->_cl];
        $frames = $line->get_frames();

        if (count($frames) > 0) {
            $last_frame = $frames[count($frames) - 1];
            $reflower = $last_frame->get_reflower();

            if ($reflower instanceof TextFrameReflower
                && !$last_frame->is_pre()
            ) {
                $reflower->trim_trailing_ws();
                $line->recalculate_width();
            }
        }

        $line->br = $br;
        $y = $line->y + $line->h;

        $new_line = new LineBox($this, $y);

        $this->_line_boxes[++$this->_cl] = $new_line;
    }

    //........................................................................
}
