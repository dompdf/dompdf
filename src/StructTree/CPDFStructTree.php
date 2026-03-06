<?php

declare(strict_types=1);

namespace Dompdf\StructTree;

use Dompdf\StructTree;
use Dompdf\Adapter\CPDF;
use SplObjectStorage;
use DOMNode;
use DOMDocument;

class CPDFStructTree implements StructTree
{
    /** @var CPDF */
    private $canvas;

    /**
     * Keys are the headline level.
     * Values are the outline id.
     * Key 0 must be the outline root id of the PDF.
     *
     * @var array<int, int>
     */
    private $headlineParents;

    /**
     * The values are the struct tree ID's.
     *
     * @var SplObjectStorage<DOMNode, int>
     */
    private $structTree;

    /**
     * Last marked struct tree ID (the direct parent of the marked content block).
     *
     * @var ?int
     */
    private $activeStructElement = null;

    private const HTML2PDF_TAGS = [
        'Strong' => 'Span',
        'Article' => 'Art',
        'Img' => 'Span',
        'Tbody' => 'TBody',
        'Thead' => 'THead',
        'Td' => 'TD',
        'Tr' => 'TR',
        'Th' => 'TH',
        'Em' => 'Span',
        'I' => 'Span',
    ];

    private const HTML2PDF_TH_SCOPES = [
        'col' => 'Column',
        'row' => 'Row',
    ];

    /**
     * Placeholder for the struct tree root.
     * The value is just used as a unique identifier, the object itself is never used.
     * To always have the same root in a document this must only be assigned once.
     *
     * @var ?DOMNode
     *
     */
    private static $structDummyRoot = null;

    public function __construct(CPDF $canvas)
    {
        $this->canvas = $canvas;
        $this->structTree = new SplObjectStorage();
        self::$structDummyRoot = self::$structDummyRoot ?? new DOMDocument();
        $this->headlineParents = [0 => $this->canvas->addOutlineRoot()];
    }

    public function inArtifact(): void
    {
        $this->canvas->inArtifact();
    }

    /**
     * Add struct tree, outline and (structure) content marks based on $node's path.
     */
    public function render(DOMNode $node): void
    {
        [$path, $headTagNr] = $this->path($node);
        $this->renderStructTree($path);
        if ($headTagNr !== null && isset($node->data) && $node->data) {
            $this->renderOutline($headTagNr, $node->data);
        }
    }

    /**
     * Add the struct element tree to the PDF according to $path.
     * The values of $path (used as IDs) are used to keep track if a node has been closed.
     * Additionally special attributes are like <th scope="col"></th> are kept to the corresponding PDF attributes.
     *
     * The leaf of $path is used as a struct element and as a marked structured content.
     *
     * @param array<string, DOMNode> $path
     */
    private function renderStructTree(array $path): void
    {
        $parent = $this->canvas->addStructTreeRoot();
        $parentTag = null;
        foreach ($path as $tag => $node) {
            if (!isset($this->structTree[$node])) {
                $this->structTree[$node] = $this->canvas->addStructElement($tag, $parent);
                if ($tag === 'TH' && isset(self::HTML2PDF_TH_SCOPES[$node->getAttribute('scope')])) {
                    $this->canvas->addAttribute($this->structTree[$node], ['O' => 'Table', 'Scope' => self::HTML2PDF_TH_SCOPES[$node->getAttribute('scope')]]);
                }
            }

            $parent = $this->structTree[$node];
            $parentTag = $tag;
        }

        if (!in_array($parent, [null, $this->activeStructElement], true)) {
            $this->canvas->inMarkedStructureContent($parentTag, $parent, $this->markedContentPropsOfNode($path[$parentTag]));
            $this->activeStructElement = $parent;
        }
    }

    /**
     * Keeps track of $node's headlines.
     * Based on the headline level a corresponding outline is added to the PDF.
     *
     * @Example
     * $this->renderOutline(1, 'hej');
     * $this->renderOutline(2, 'ho');
     * $this->renderOutline(3, 'hu');
     * $this->renderOutline(2, 'ha');
     * $this->renderOutline(1, 'hi');
     *
     * Will result in an outline like:
     * - hej
     *  - ho
     *    - hu
     *  - ha
     * - hi
     */
    private function renderOutline(int $headTagNr, string $title): void
    {
        // Remove all tags greater as $headTagNr (as they are no parent).
        if ($headTagNr <= max(array_keys($this->headlineParents))) {
            $this->headlineParents = array_filter($this->headlineParents, function($nr)use($headTagNr){
                return $headTagNr > $nr;
            }, ARRAY_FILTER_USE_KEY);
        }

        // Find next parent if nrs are skipped (like: <html><h1></h1><h5></h5></html>)
        for ($i = $headTagNr - 1; !array_key_exists($i, $this->headlineParents); $i--) {
        }

        $parent = $this->headlineParents[$i];
        $outlineId = $this->canvas->addOutline($title, $parent);
        $this->headlineParents[$headTagNr] = $outlineId;
    }

    /**
     * @return array<string, string>
     */
    private function markedContentPropsOfNode(DOMNode $node): array
    {
        $props = [];

        if (!strcasecmp($node->nodeName ?? '', 'img') && $node->getAttribute('alt')) {
            $props['Alt'] = $node->getAttribute('alt');
        }

        return $props;
    }

    /**
     * Returns the document path from the HTML body to $node in PDF tag names in $this->path($node)[0].
     * Tag replacements from HTML to PDF are done as well.
     * Returns the closest (to $node->get_node()) headline tag in $this->path($node)[1].
     *
     * A HTML path of the node like:
     * body > div > article > p > strong
     * is returned as:
     * Document > Div > Art > P > Span
     * In the form of:
     * [['Document' => self::$structDummyRoot, 'Div' => $divNode, 'Art' => $articeNode, 'P' => $pNode, 'Span' => $strongNode], null]
     * And for headlines:
     * Given HTML Path:
     * div > article > h2
     * [['Document' => self::$structDummyRoot, 'Div' => $divNode, 'Art' => $articeNode, 'H2' => $h2Node], 'H2']
     *
     * Document must always be the root of the struct element tree, so it is always added.
     *
     * @return array{0: array<string, DOMNode>, 1: ?int}
     */
    private function path(DOMNode $node): array
    {
        $path = [];
        $headTagNr = null;
        do {
            if (isset($node->tagName)) {
                $tagName = $this->niceTagName($node->tagName);
                $path[$tagName] = $node;
                if (preg_match('/H([1-9])/', $tagName, $matches)) {
                    $headTagNr = (int) $matches[1];
                }
            }
            $node = $node->parentNode;
        }while ($node && strcasecmp($node->tagName, 'body'));

        $path['Document'] = self::$structDummyRoot;

        return [array_reverse($path), $headTagNr];
    }

    /**
     * Rename HTML tag names to PDF tag names.
     */
    private function niceTagName(string $tagName): string
    {
        $tagName = ucfirst(strtolower($tagName));
        return self::HTML2PDF_TAGS[$tagName] ?? $tagName;
    }
}
