<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Frame;

use Dompdf\Dompdf;
use Dompdf\Exception;
use Dompdf\Frame;
use Dompdf\FrameDecorator\AbstractFrameDecorator;
use Dompdf\FrameDecorator\Page as PageFrameDecorator;
use Dompdf\FrameReflower\Page as PageFrameReflower;
use Dompdf\Positioner\AbstractPositioner;
use DOMXPath;

/**
 * Contains frame decorating logic
 *
 * This class is responsible for assigning the correct {@link AbstractFrameDecorator},
 * {@link AbstractPositioner}, and {@link AbstractFrameReflower} objects to {@link Frame}
 * objects.  This is determined primarily by the Frame's display type, but
 * also by the Frame's node's type (e.g. DomElement vs. #text)
 *
 * @package dompdf
 */
class Factory
{

    /**
     * Array of positioners for specific frame types
     *
     * @var AbstractPositioner[]
     */
    protected static $_positioners;

    /**
     * Decorate the root Frame
     *
     * @param Frame  $root   The frame to decorate
     * @param Dompdf $dompdf The dompdf instance
     *
     * @return PageFrameDecorator
     */
    public static function decorate_root(Frame $root, Dompdf $dompdf): PageFrameDecorator
    {
        $frame = new PageFrameDecorator($root, $dompdf);
        $frame->set_reflower(new PageFrameReflower($frame));
        $root->set_decorator($frame);

        return $frame;
    }

    /**
     * Decorate a Frame
     *
     * @param Frame      $frame  The frame to decorate
     * @param Dompdf     $dompdf The dompdf instance
     * @param Frame|null $root   The root of the frame
     *
     * @throws Exception
     * @return AbstractFrameDecorator|null
     * FIXME: this is admittedly a little smelly...
     */
    public static function decorate_frame(Frame $frame, Dompdf $dompdf, ?Frame $root = null): ?AbstractFrameDecorator
    {
        $style = $frame->get_style();
        $display = $style->display;

        switch ($display) {

            case "block":
                $positioner = "Block";
                $decorator = "Block";
                $reflower = "Block";
                break;

            case "inline-block":
                $positioner = "Inline";
                $decorator = "Block";
                $reflower = "Block";
                break;

            case "inline":
                $positioner = "Inline";
                if ($frame->is_text_node()) {
                    $decorator = "Text";
                    $reflower = "Text";
                } else {
                    $decorator = "Inline";
                    $reflower = "Inline";
                }
                break;

            case "table":
                $positioner = "Block";
                $decorator = "Table";
                $reflower = "Table";
                break;

            case "inline-table":
                $positioner = "Inline";
                $decorator = "Table";
                $reflower = "Table";
                break;

            case "table-row-group":
            case "table-header-group":
            case "table-footer-group":
                $positioner = "NullPositioner";
                $decorator = "TableRowGroup";
                $reflower = "TableRowGroup";
                break;

            case "table-row":
                $positioner = "NullPositioner";
                $decorator = "TableRow";
                $reflower = "TableRow";
                break;

            case "table-cell":
                $positioner = "TableCell";
                $decorator = "TableCell";
                $reflower = "TableCell";
                break;

            case "list-item":
                $positioner = "Block";
                $decorator = "Block";
                $reflower = "Block";
                break;

            case "-dompdf-list-bullet":
                if ($style->list_style_position === "inside") {
                    $positioner = "Inline";
                } else {
                    $positioner = "ListBullet";
                }

                if ($style->list_style_image !== "none") {
                    $decorator = "ListBulletImage";
                } else {
                    $decorator = "ListBullet";
                }

                $reflower = "ListBullet";
                break;

            case "-dompdf-image":
                $positioner = "Inline";
                $decorator = "Image";
                $reflower = "Image";
                break;

            case "-dompdf-br":
                $positioner = "Inline";
                $decorator = "Inline";
                $reflower = "Inline";
                break;

            default:
            case "none":
                if ($style->_dompdf_keep !== "yes") {
                    // Remove the node and the frame
                    $frame->get_parent()->remove_child($frame);
                    return null;
                }

                $positioner = "NullPositioner";
                $decorator = "NullFrameDecorator";
                $reflower = "NullFrameReflower";
                break;
        }

        // Handle CSS position
        $position = $style->position;

        if ($position === "absolute") {
            $positioner = "Absolute";
        } elseif ($position === "fixed") {
            $positioner = "Fixed";
        }

        $node = $frame->get_node();

        // Handle nodeName
        if ($node->nodeName === "img") {
            $style->set_prop("display", "-dompdf-image");
            $decorator = "Image";
            $reflower = "Image";
        }

        $decorator  = "Dompdf\\FrameDecorator\\$decorator";
        $reflower   = "Dompdf\\FrameReflower\\$reflower";

        /** @var AbstractFrameDecorator $deco */
        $deco = new $decorator($frame, $dompdf);

        $deco->set_positioner(self::getPositionerInstance($positioner));
        $deco->set_reflower(new $reflower($deco, $dompdf->getFontMetrics()));

        if ($root) {
            $deco->set_root($root);
        }

        if ($display === "list-item") {
            // Insert a list-bullet frame
            $xml = $dompdf->getDom();
            $bullet_node = $xml->createElement("bullet"); // arbitrary choice
            $b_f = new Frame($bullet_node);

            $node = $frame->get_node();
            $parent_node = $node->parentNode;
            if ($parent_node && $parent_node instanceof \DOMElement) {
                if (!$parent_node->hasAttribute("dompdf-children-count")) {
                    $xpath = new DOMXPath($xml);
                    $count = $xpath->query("li", $parent_node)->length;
                    $parent_node->setAttribute("dompdf-children-count", $count);
                }

                if (is_numeric($node->getAttribute("value"))) {
                    $index = intval($node->getAttribute("value"));
                } else {
                    if (!$parent_node->hasAttribute("dompdf-counter")) {
                        $index = ($parent_node->hasAttribute("start") ? $parent_node->getAttribute("start") : 1);
                    } else {
                        $index = (int)$parent_node->getAttribute("dompdf-counter") + 1;
                    }
                }

                $parent_node->setAttribute("dompdf-counter", $index);
                $bullet_node->setAttribute("dompdf-counter", $index);
            }

            $new_style = $dompdf->getCss()->create_style();
            $new_style->set_prop("display", "-dompdf-list-bullet");
            $new_style->inherit($style);
            $b_f->set_style($new_style);

            $deco->prepend_child(Factory::decorate_frame($b_f, $dompdf, $root));
        }

        return $deco;
    }

    /**
     * Creates Positioners
     *
     * @param string $type Type of positioner to use
     *
     * @return AbstractPositioner
     */
    protected static function getPositionerInstance(string $type): AbstractPositioner
    {
        if (!isset(self::$_positioners[$type])) {
            $class = '\\Dompdf\\Positioner\\'.$type;
            self::$_positioners[$type] = new $class();
        }
        return self::$_positioners[$type];
    }
}
