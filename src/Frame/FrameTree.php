<?php

namespace Dompdf\Frame;

use DOMDocument;
use DOMNode;
use DOMElement;
use DOMXPath;

use Dompdf\Exception;
use Dompdf\Frame;

/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * Represents an entire document as a tree of frames
 *
 * The FrameTree consists of {@link Frame} objects each tied to specific
 * DOMNode objects in a specific DomDocument.  The FrameTree has the same
 * structure as the DomDocument, but adds additional capabilities for
 * styling and layout.
 *
 * @package dompdf
 */
class FrameTree
{
    /**
     * Tags to ignore while parsing the tree
     *
     * @var array
     */
    protected static $HIDDEN_TAGS = [
        "area",
        "base",
        "basefont",
        "head",
        "style",
        "meta",
        "title",
        "colgroup",
        "noembed",
        "param",
        "#comment"
    ];

    /**
     * The main DomDocument
     *
     * @see http://ca2.php.net/manual/en/ref.dom.php
     * @var DOMDocument
     */
    protected $_dom;

    /**
     * The root node of the FrameTree.
     *
     * @var Frame
     */
    protected $_root;

    /**
     * Subtrees of absolutely positioned elements
     *
     * @var array of Frames
     */
    protected $_absolute_frames;

    /**
     * A mapping of {@link Frame} objects to DOMNode objects
     *
     * @var array
     */
    protected $_registry;

    /**
     * Class constructor
     *
     * @param DOMDocument $dom the main DomDocument object representing the current html document
     */
    public function __construct(DomDocument $dom)
    {
        $this->_dom = $dom;
        $this->_root = null;
        $this->_registry = [];
    }

    /**
     * Returns the DOMDocument object representing the current html document
     *
     * @return DOMDocument
     */
    public function get_dom()
    {
        return $this->_dom;
    }

    /**
     * Returns the root frame of the tree
     *
     * @return Frame
     */
    public function get_root()
    {
        return $this->_root;
    }

    /**
     * Returns a specific frame given its id
     *
     * @param string $id
     *
     * @return Frame|null
     */
    public function get_frame($id)
    {
        return isset($this->_registry[$id]) ? $this->_registry[$id] : null;
    }

    /**
     * Returns a post-order iterator for all frames in the tree
     *
     * @return FrameTreeList|Frame[]
     */
    public function get_frames()
    {
        return new FrameTreeList($this->_root);
    }

    /**
     * Builds the tree
     */
    public function build_tree()
    {
        $html = $this->_dom->getElementsByTagName("html")->item(0);
        if (is_null($html)) {
            $html = $this->_dom->firstChild;
        }

        if (is_null($html)) {
            throw new Exception("Requested HTML document contains no data.");
        }

        $this->fix_tables();

        $this->_root = $this->_build_tree_r($html);
    }

    /**
     * Adds missing TBODYs around TR
     */
    protected function fix_tables()
    {
        $xp = new DOMXPath($this->_dom);

        // Move table caption before the table
        // FIXME find a better way to deal with it...
        $captions = $xp->query('//table/caption');
        foreach ($captions as $caption) {
            $table = $caption->parentNode;
            $table->parentNode->insertBefore($caption, $table);
        }

        $firstRows = $xp->query('//table/tr[1]');
        /** @var DOMElement $tableChild */
        foreach ($firstRows as $tableChild) {
            $tbody = $this->_dom->createElement('tbody');
            $tableNode = $tableChild->parentNode;
            do {
                if ($tableChild->nodeName === 'tr') {
                    $tmpNode = $tableChild;
                    $tableChild = $tableChild->nextSibling;
                    $tableNode->removeChild($tmpNode);
                    $tbody->appendChild($tmpNode);
                } else {
                    if ($tbody->hasChildNodes() === true) {
                        $tableNode->insertBefore($tbody, $tableChild);
                        $tbody = $this->_dom->createElement('tbody');
                    }
                    $tableChild = $tableChild->nextSibling;
                }
            } while ($tableChild);
            if ($tbody->hasChildNodes() === true) {
                $tableNode->appendChild($tbody);
            }
        }
    }

    // FIXME: temporary hack, preferably we will improve rendering of sequential #text nodes
    /**
     * Remove a child from a node
     *
     * Remove a child from a node. If the removed node results in two
     * adjacent #text nodes then combine them.
     *
     * @param DOMNode $node the current DOMNode being considered
     * @param array $children an array of nodes that are the children of $node
     * @param int $index index from the $children array of the node to remove
     */
    protected function _remove_node(DOMNode $node, array &$children, $index)
    {
        $child = $children[$index];
        $previousChild = $child->previousSibling;
        $nextChild = $child->nextSibling;
        $node->removeChild($child);
        if (isset($previousChild, $nextChild)) {
            if ($previousChild->nodeName === "#text" && $nextChild->nodeName === "#text") {
                $previousChild->nodeValue .= $nextChild->nodeValue;
                $this->_remove_node($node, $children, $index+1);
            }
        }
        array_splice($children, $index, 1);
    }

    /**
     * Recursively adds {@link Frame} objects to the tree
     *
     * Recursively build a tree of Frame objects based on a dom tree.
     * No layout information is calculated at this time, although the
     * tree may be adjusted (i.e. nodes and frames for generated content
     * and images may be created).
     *
     * @param DOMNode $node the current DOMNode being considered
     *
     * @return Frame
     */
    protected function _build_tree_r(DOMNode $node)
    {
        $frame = new Frame($node);
        $id = $frame->get_id();
        $this->_registry[$id] = $frame;

        if (!$node->hasChildNodes()) {
            return $frame;
        }

        // Store the children in an array so that the tree can be modified
        $children = [];
        $length = $node->childNodes->length;
        for ($i = 0; $i < $length; $i++) {
            $children[] = $node->childNodes->item($i);
        }
        $index = 0;
        // INFO: We don't advance $index if a node is removed to avoid skipping nodes
        while ($index < count($children)) {
            $child = $children[$index];
            $nodeName = strtolower($child->nodeName);

            // Skip non-displaying nodes
            if (in_array($nodeName, self::$HIDDEN_TAGS)) {
                if ($nodeName !== "head" && $nodeName !== "style") {
                    $this->_remove_node($node, $children, $index);
                } else {
                    $index++;
                }
                continue;
            }
            // Skip empty text nodes
            if ($nodeName === "#text" && $child->nodeValue === "") {
                $this->_remove_node($node, $children, $index);
                continue;
            }
            // Skip empty image nodes
            if ($nodeName === "img" && $child->getAttribute("src") === "") {
                $this->_remove_node($node, $children, $index);
                continue;
            }

            if (is_object($child)) {
                $frame->append_child($this->_build_tree_r($child), false);
            }
            $index++;
        }

        return $frame;
    }

    /**
     * @param DOMElement $node
     * @param DOMElement $new_node
     * @param string $pos
     *
     * @return mixed
     */
    public function insert_node(DOMElement $node, DOMElement $new_node, $pos)
    {
        if ($pos === "after" || !$node->firstChild) {
            $node->appendChild($new_node);
        } else {
            $node->insertBefore($new_node, $node->firstChild);
        }

        $this->_build_tree_r($new_node);

        $frame_id = $new_node->getAttribute("frame_id");
        $frame = $this->get_frame($frame_id);

        $parent_id = $node->getAttribute("frame_id");
        $parent = $this->get_frame($parent_id);

        if ($parent) {
            if ($pos === "before") {
                $parent->prepend_child($frame, false);
            } else {
                $parent->append_child($frame, false);
            }
        }

        return $frame_id;
    }
}
