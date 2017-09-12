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

        $this->_line_boxes = array(new LineBox($this));
        $this->_cl = 0;
    }

    /**
     *
     */
    function reset()
    {
        parent::reset();

        $this->_line_boxes = array(new LineBox($this));
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
     * @return integer
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
     * @param integer $line_number
     * @return integer
     */
    function set_current_line_number($line_number)
    {
        $line_boxes_count = count($this->_line_boxes);
        $cl = max(min($line_number, $line_boxes_count), 0);
        return ($this->_cl = $cl);
    }

    /**
     * @param integer $i
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
                $this->maximize_line_height($style->length_in_pt($style->line_height), $frame);
                $this->add_line(true);
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
        if ($w == 0 && $frame->get_node()->nodeName !== "hr") {
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

        $line = $this->_line_boxes[$this->_cl];
        if ($line->left + $line->w + $line->right + $w > $this->get_containing_block("w")) {
            $this->add_line();
        }

        $frame->position();

        $current_line = $this->_line_boxes[$this->_cl];
        $current_line->add_frame($frame);

        if ($frame->is_text_node()) {
            $current_line->wc += count(preg_split("/\s+/", trim($frame->get_text())));
        }

        $this->increase_line_width($w);

        $this->maximize_line_height($frame->get_margin_height(), $frame);
    }

    /**
     * @param Frame $frame
     */
    function remove_frames_from_line(Frame $frame)
    {
        // Search backwards through the lines for $frame
        $i = $this->_cl;
        $j = null;

        while ($i >= 0) {
            if (($j = in_array($frame, $this->_line_boxes[$i]->get_frames(), true)) !== false) {
                break;
            }

            $i--;
        }

        if ($j === false) {
            return;
        }

        // Remove $frame and all frames that follow
        while ($j < count($this->_line_boxes[$i]->get_frames())) {
            $frames = $this->_line_boxes[$i]->get_frames();
            $f = $frames[$j];
            $frames[$j] = null;
            unset($frames[$j]);
            $j++;
            $this->_line_boxes[$i]->w -= $f->get_margin_width();
        }

        // Recalculate the height of the line
        $h = 0;
        foreach ($this->_line_boxes[$i]->get_frames() as $f) {
            $h = max($h, $f->get_margin_height());
        }

        $this->_line_boxes[$i]->h = $h;

        // Remove all lines that follow
        while ($this->_cl > $i) {
            $this->_line_boxes[$this->_cl] = null;
            unset($this->_line_boxes[$this->_cl]);
            $this->_cl--;
        }
    }

    /**
     * @param float $w
     */
    function increase_line_width($w)
    {
        $this->_line_boxes[$this->_cl]->w += $w;
    }

    /**
     * @param $val
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
    function add_line($br = false)
    {

//     if ( $this->_line_boxes[$this->_cl]["h"] == 0 ) //count($this->_line_boxes[$i]["frames"]) == 0 ||
//       return;

        $this->_line_boxes[$this->_cl]->br = $br;
        $y = $this->_line_boxes[$this->_cl]->y + $this->_line_boxes[$this->_cl]->h;

        $new_line = new LineBox($this, $y);

        $this->_line_boxes[++$this->_cl] = $new_line;
    }

    //........................................................................
}
