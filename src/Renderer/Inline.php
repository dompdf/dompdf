<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Renderer;

use Dompdf\Frame;
use Dompdf\Helpers;

/**
 * Renders inline frames
 *
 * @access  private
 * @package dompdf
 */
class Inline extends AbstractRenderer
{

    //........................................................................

    function render(Frame $frame)
    {
        $style = $frame->get_style();

        if (!$frame->get_first_child())
            return; // No children, no service

        // Draw the left border if applicable
        $bp = $style->get_border_properties();
        $widths = array($style->length_in_pt($bp["top"]["width"]),
            $style->length_in_pt($bp["right"]["width"]),
            $style->length_in_pt($bp["bottom"]["width"]),
            $style->length_in_pt($bp["left"]["width"]));

        // Draw the background & border behind each child.  To do this we need
        // to figure out just how much space each child takes:
        list($x, $y) = $frame->get_first_child()->get_position();
        $w = null;
        $h = 0;
//     $x += $widths[3];
//     $y += $widths[0];

        $this->_set_opacity($frame->get_opacity($style->opacity));

        $first_row = true;

        foreach ($frame->get_children() as $child) {
            list($child_x, $child_y, $child_w, $child_h) = $child->get_padding_box();

            if (!is_null($w) && $child_x < $x + $w) {
                //This branch seems to be supposed to being called on the first part
                //of an inline html element, and the part after the if clause for the
                //parts after a line break.
                //But because $w initially mostly is 0, and gets updated only on the next
                //round, this seem to be never executed and the common close always.

                // The next child is on another line.  Draw the background &
                // borders on this line.

                // Background:
                if (($bg = $style->background_color) !== "transparent")
                    $this->_canvas->filled_rectangle($x, $y, $w, $h, $bg);

                if (($url = $style->background_image) && $url !== "none") {
                    $this->_background_image($url, $x, $y, $w, $h, $style);
                }

                // If this is the first row, draw the left border
                if ($first_row) {

                    if ($bp["left"]["style"] !== "none" && $bp["left"]["color"] !== "transparent" && $bp["left"]["width"] > 0) {
                        $method = "_border_" . $bp["left"]["style"];
                        $this->$method($x, $y, $h + $widths[0] + $widths[2], $bp["left"]["color"], $widths, "left");
                    }
                    $first_row = false;
                }

                // Draw the top & bottom borders
                if ($bp["top"]["style"] !== "none" && $bp["top"]["color"] !== "transparent" && $bp["top"]["width"] > 0) {
                    $method = "_border_" . $bp["top"]["style"];
                    $this->$method($x, $y, $w + $widths[1] + $widths[3], $bp["top"]["color"], $widths, "top");
                }

                if ($bp["bottom"]["style"] !== "none" && $bp["bottom"]["color"] !== "transparent" && $bp["bottom"]["width"] > 0) {
                    $method = "_border_" . $bp["bottom"]["style"];
                    $this->$method($x, $y + $h + $widths[0] + $widths[2], $w + $widths[1] + $widths[3], $bp["bottom"]["color"], $widths, "bottom");
                }

                // Handle anchors & links
                $link_node = null;
                if ($frame->get_node()->nodeName === "a") {
                    $link_node = $frame->get_node();
                } else if ($frame->get_parent()->get_node()->nodeName === "a") {
                    $link_node = $frame->get_parent()->get_node();
                }

                if ($link_node && $href = $link_node->getAttribute("href")) {
                    $href = Helpers::build_url($this->_dompdf->getProtocol(), $this->_dompdf->getBaseHost(), $this->_dompdf->getBasePath(), $href);
                    $this->_canvas->add_link($href, $x, $y, $w, $h);
                }

                $x = $child_x;
                $y = $child_y;
                $w = $child_w;
                $h = $child_h;
                continue;
            }

            if (is_null($w))
                $w = $child_w;
            else
                $w += $child_w;

            $h = max($h, $child_h);

            if ($this->_dompdf->get_option("debugLayout") && $this->_dompdf->get_option("debugLayoutInline")) {
                $this->_debug_layout($child->get_border_box(), "blue");
                if ($this->_dompdf->get_option("debugLayoutPaddingBox")) {
                    $this->_debug_layout($child->get_padding_box(), "blue", array(0.5, 0.5));
                }
            }
        }


        // Handle the last child
        if (($bg = $style->background_color) !== "transparent")
            $this->_canvas->filled_rectangle($x + $widths[3], $y + $widths[0], $w, $h, $bg);

        //On continuation lines (after line break) of inline elements, the style got copied.
        //But a non repeatable background image should not be repeated on the next line.
        //But removing the background image above has never an effect, and removing it below
        //removes it always, even on the initial line.
        //Need to handle it elsewhere, e.g. on certain ...clone()... usages.
        // Repeat not given: default is Style::__construct
        // ... && (!($repeat = $style->background_repeat) || $repeat === "repeat" ...
        //different position? $this->_background_image($url, $x, $y, $w, $h, $style);
        if (($url = $style->background_image) && $url !== "none")
            $this->_background_image($url, $x + $widths[3], $y + $widths[0], $w, $h, $style);

        // Add the border widths
        $w += $widths[1] + $widths[3];
        $h += $widths[0] + $widths[2];

        // make sure the border and background start inside the left margin
        $left_margin = $style->length_in_pt($style->margin_left);
        $x += $left_margin;

        // If this is the first row, draw the left border too
        if ($first_row && $bp["left"]["style"] !== "none" && $bp["left"]["color"] !== "transparent" && $widths[3] > 0) {
            $method = "_border_" . $bp["left"]["style"];
            $this->$method($x, $y, $h, $bp["left"]["color"], $widths, "left");
        }

        // Draw the top & bottom borders
        if ($bp["top"]["style"] !== "none" && $bp["top"]["color"] !== "transparent" && $widths[0] > 0) {
            $method = "_border_" . $bp["top"]["style"];
            $this->$method($x, $y, $w, $bp["top"]["color"], $widths, "top");
        }

        if ($bp["bottom"]["style"] !== "none" && $bp["bottom"]["color"] !== "transparent" && $widths[2] > 0) {
            $method = "_border_" . $bp["bottom"]["style"];
            $this->$method($x, $y + $h, $w, $bp["bottom"]["color"], $widths, "bottom");
        }

        //    Helpers::var_dump(get_class($frame->get_next_sibling()));
        //    $last_row = get_class($frame->get_next_sibling()) !== 'Inline';
        // Draw the right border if this is the last row
        if ($bp["right"]["style"] !== "none" && $bp["right"]["color"] !== "transparent" && $widths[1] > 0) {
            $method = "_border_" . $bp["right"]["style"];
            $this->$method($x + $w, $y, $h, $bp["right"]["color"], $widths, "right");
        }

        // Only two levels of links frames
        $link_node = null;
        if ($frame->get_node()->nodeName === "a") {
            $link_node = $frame->get_node();

            if (($name = $link_node->getAttribute("name")) || ($name = $link_node->getAttribute("id"))) {
                $this->_canvas->add_named_dest($name);
            }
        }

        if ($frame->get_parent() && $frame->get_parent()->get_node()->nodeName === "a") {
            $link_node = $frame->get_parent()->get_node();
        }

        // Handle anchors & links
        if ($link_node) {
            if ($href = $link_node->getAttribute("href")) {
                $href = Helpers::build_url($this->_dompdf->getProtocol(), $this->_dompdf->getBaseHost(), $this->_dompdf->getBasePath(), $href);
                $this->_canvas->add_link($href, $x, $y, $w, $h);
            }
        }
    }
}
