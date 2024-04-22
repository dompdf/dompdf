<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

use DOMDocument;
use DOMNode;
use Dompdf\Adapter\CPDF;
use DOMXPath;
use Dompdf\Frame\Factory;
use Dompdf\Frame\FrameTree;
use Dompdf\Image\Cache;
use Dompdf\Css\Stylesheet;
use Dompdf\Helpers;
use Masterminds\HTML5;

/**
 * Dompdf - PHP5 HTML to PDF renderer
 *
 * Dompdf loads HTML and does its best to render it as a PDF.  It gets its
 * name from the new DomDocument PHP5 extension.  Source HTML is first
 * parsed by a DomDocument object.  Dompdf takes the resulting DOM tree and
 * attaches a {@link Frame} object to each node.  {@link Frame} objects store
 * positioning and layout information and each has a reference to a {@link
 * Style} object.
 *
 * Style information is loaded and parsed (see {@link Stylesheet}) and is
 * applied to the frames in the tree by using XPath.  CSS selectors are
 * converted into XPath queries, and the computed {@link Style} objects are
 * applied to the {@link Frame}s.
 *
 * {@link Frame}s are then decorated (in the design pattern sense of the
 * word) based on their CSS display property ({@link
 * http://www.w3.org/TR/CSS21/visuren.html#propdef-display}).
 * Frame_Decorators augment the basic {@link Frame} class by adding
 * additional properties and methods specific to the particular type of
 * {@link Frame}.  For example, in the CSS layout model, block frames
 * (display: block;) contain line boxes that are usually filled with text or
 * other inline frames.  The Block therefore adds a $lines
 * property as well as methods to add {@link Frame}s to lines and to add
 * additional lines.  {@link Frame}s also are attached to specific
 * AbstractPositioner and {@link AbstractFrameReflower} objects that contain the
 * positioining and layout algorithm for a specific type of frame,
 * respectively.  This is an application of the Strategy pattern.
 *
 * Layout, or reflow, proceeds recursively (post-order) starting at the root
 * of the document.  Space constraints (containing block width & height) are
 * pushed down, and resolved positions and sizes bubble up.  Thus, every
 * {@link Frame} in the document tree is traversed once (except for tables
 * which use a two-pass layout algorithm).  If you are interested in the
 * details, see the reflow() method of the Reflower classes.
 *
 * Rendering is relatively straightforward once layout is complete. {@link
 * Frame}s are rendered using an adapted {@link Cpdf} class, originally
 * written by Wayne Munro, http://www.ros.co.nz/pdf/.  (Some performance
 * related changes have been made to the original {@link Cpdf} class, and
 * the {@link Dompdf\Adapter\CPDF} class provides a simple, stateless interface to
 * PDF generation.)  PDFLib support has now also been added, via the {@link
 * Dompdf\Adapter\PDFLib}.
 *
 *
 * @package dompdf
 */
class Dompdf
{
    /**
     * Version string for dompdf
     *
     * @var string
     */
    private $version = 'dompdf';

    /**
     * DomDocument representing the HTML document
     *
     * @var DOMDocument
     */
    private $dom;

    /**
     * FrameTree derived from the DOM tree
     *
     * @var FrameTree
     */
    private $tree;

    /**
     * Stylesheet for the document
     *
     * @var Stylesheet
     */
    private $css;

    /**
     * Actual PDF renderer
     *
     * @var Canvas
     */
    private $canvas;

    /**
     * Desired paper size ('letter', 'legal', 'A4', etc.)
     *
     * @var string|float[]
     */
    private $paperSize;

    /**
     * Paper orientation ('portrait' or 'landscape')
     *
     * @var string
     */
    private $paperOrientation = "portrait";

    /**
     * Callbacks on new page and new element
     *
     * @var array
     */
    private $callbacks = [];

    /**
     * Experimental caching capability
     *
     * @var string
     */
    private $cacheId;

    /**
     * Base hostname
     *
     * Used for relative paths/urls
     * @var string
     */
    private $baseHost = "";

    /**
     * Absolute base path
     *
     * Used for relative paths/urls
     * @var string
     */
    private $basePath = "";

    /**
     * Protocol used to request file (file://, http://, etc)
     *
     * @var string
     */
    private $protocol = "";

    /**
     * The system's locale
     *
     * @var string
     */
    private $systemLocale = null;

    /**
     * The system's mbstring internal encoding
     *
     * @var string
     */
    private $mbstringEncoding = null;

    /**
     * The system's PCRE JIT configuration
     *
     * @var string
     */
    private $pcreJit = null;

    /**
     * The default view of the PDF in the viewer
     *
     * @var string
     */
    private $defaultView = "Fit";

    /**
     * The default view options of the PDF in the viewer
     *
     * @var array
     */
    private $defaultViewOptions = [];

    /**
     * Tells whether the DOM document is in quirksmode (experimental)
     *
     * @var bool
     */
    private $quirksmode = false;

    /**
    * Local file extension whitelist
    *
    * File extensions supported by dompdf for local files.
    *
    * @var array
    */
    private $allowedLocalFileExtensions = ["htm", "html"];

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var Options
     */
    private $options;

    /**
     * @var FontMetrics
     */
    private $fontMetrics;

    /**
     * The list of built-in fonts
     *
     * @var array
     * @deprecated
     */
    public static $native_fonts = [
        "courier", "courier-bold", "courier-oblique", "courier-boldoblique",
        "helvetica", "helvetica-bold", "helvetica-oblique", "helvetica-boldoblique",
        "times-roman", "times-bold", "times-italic", "times-bolditalic",
        "symbol", "zapfdinbats"
    ];

    /**
     * The list of built-in fonts
     *
     * @var array
     */
    public static $nativeFonts = [
        "courier", "courier-bold", "courier-oblique", "courier-boldoblique",
        "helvetica", "helvetica-bold", "helvetica-oblique", "helvetica-boldoblique",
        "times-roman", "times-bold", "times-italic", "times-bolditalic",
        "symbol", "zapfdinbats"
    ];

    /**
     * Class constructor
     *
     * @param Options|array|null $options
     */
    public function __construct($options = null)
    {
        if (isset($options) && $options instanceof Options) {
            $this->setOptions($options);
        } elseif (is_array($options)) {
            $this->setOptions(new Options($options));
        } else {
            $this->setOptions(new Options());
        }

        $versionFile = realpath(__DIR__ . '/../VERSION');
        if (($version = file_get_contents($versionFile)) !== false) {
            $version = trim($version);
            if ($version !== '$Format:<%h>$') {
                $this->version = sprintf('dompdf %s', $version);
            }
        }

        $this->setPhpConfig();

        $this->paperSize = $this->options->getDefaultPaperSize();
        $this->paperOrientation = $this->options->getDefaultPaperOrientation();

        $this->canvas = CanvasFactory::get_instance($this, $this->paperSize, $this->paperOrientation);
        $this->fontMetrics = new FontMetrics($this->canvas, $this->options);
        $this->css = new Stylesheet($this);

        $this->restorePhpConfig();
    }

    /**
     * Save the system's existing locale, PCRE JIT, and MBString encoding
     * configuration and configure the system for Dompdf processing
     */
    private function setPhpConfig()
    {
        if (sprintf('%.1f', 1.0) !== '1.0') {
            $this->systemLocale = setlocale(LC_NUMERIC, "0");
            setlocale(LC_NUMERIC, "C");
        }

        $this->pcreJit = @ini_get('pcre.jit');
        @ini_set('pcre.jit', '0');

        $this->mbstringEncoding = mb_internal_encoding();
        mb_internal_encoding('UTF-8');
    }

    /**
     * Restore the system's locale configuration
     */
    private function restorePhpConfig()
    {
        if ($this->systemLocale !== null) {
            setlocale(LC_NUMERIC, $this->systemLocale);
            $this->systemLocale = null;
        }

        if ($this->pcreJit !== null) {
            @ini_set('pcre.jit', $this->pcreJit);
            $this->pcreJit = null;
        }

        if ($this->mbstringEncoding !== null) {
            mb_internal_encoding($this->mbstringEncoding);
            $this->mbstringEncoding = null;
        }
    }

    /**
     * @param $file
     * @deprecated
     */
    public function load_html_file($file)
    {
        $this->loadHtmlFile($file);
    }

    /**
     * Loads an HTML file.
     *
     * If no encoding is given or set via `Content-Type` header, the document
     * encoding specified via `<meta>` tag is used. An existing Unicode BOM
     * always takes precedence.
     *
     * Parse errors are stored in the global array `$_dompdf_warnings`.
     *
     * @param string      $file     A filename or URL to load.
     * @param string|null $encoding Encoding of the file.
     */
    public function loadHtmlFile($file, $encoding = null)
    {
        $this->setPhpConfig();

        if (!$this->protocol && !$this->baseHost && !$this->basePath) {
            [$this->protocol, $this->baseHost, $this->basePath] = Helpers::explode_url($file);
        }
        $protocol = strtolower($this->protocol);
        $uri = Helpers::build_url($this->protocol, $this->baseHost, $this->basePath, $file, $this->options->getChroot());

        $allowed_protocols = $this->options->getAllowedProtocols();
        if (!array_key_exists($protocol, $allowed_protocols)) {
            throw new Exception("Permission denied on $file. The communication protocol is not supported.");
        }

        if ($protocol === "file://") {
            $ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
            if (!in_array($ext, $this->allowedLocalFileExtensions)) {
                throw new Exception("Permission denied on $file: The file extension is forbidden.");
            }
        }

        foreach ($allowed_protocols[$protocol]["rules"] as $rule) {
            [$result, $message] = $rule($uri);
            if (!$result) {
                throw new Exception("Error loading $file: $message");
            }
        }

        [$contents, $http_response_header] = Helpers::getFileContent($uri, $this->options->getHttpContext());
        if ($contents === null) {
            throw new Exception("File '$file' not found.");
        }

        // See http://the-stickman.com/web-development/php/getting-http-response-headers-when-using-file_get_contents/
        if (isset($http_response_header)) {
            foreach ($http_response_header as $_header) {
                if (preg_match("@Content-Type:\s*[\w/]+;\s*?charset=([^\s]+)@i", $_header, $matches)) {
                    $encoding = strtoupper($matches[1]);
                    break;
                }
            }
        }

        $this->restorePhpConfig();

        $this->loadHtml($contents, $encoding);
    }

    /**
     * @param string $str
     * @param string $encoding
     * @deprecated
     */
    public function load_html($str, $encoding = null)
    {
        $this->loadHtml($str, $encoding);
    }

    /**
     * @param DOMDocument $doc
     * @param bool        $quirksmode
     */
    public function loadDOM($doc, $quirksmode = false)
    {
        // Remove #text children nodes in nodes that shouldn't have
        $tag_names = ["html", "head", "table", "tbody", "thead", "tfoot", "tr"];
        foreach ($tag_names as $tag_name) {
            $nodes = $doc->getElementsByTagName($tag_name);

            foreach ($nodes as $node) {
                self::removeTextNodes($node);
            }
        }

        $this->dom = $doc;
        $this->quirksmode = $quirksmode;
        $this->tree = new FrameTree($this->dom);
    }

    /**
     * Loads an HTML document from a string.
     *
     * If no encoding is given, the document encoding specified via `<meta>`
     * tag is used. An existing Unicode BOM always takes precedence.
     *
     * Parse errors are stored in the global array `$_dompdf_warnings`.
     *
     * @param string      $str      The HTML to load.
     * @param string|null $encoding Encoding of the string.
     */
    public function loadHtml($str, $encoding = null)
    {
        $this->setPhpConfig();

        // Detect Unicode via BOM, taking precedence over the given encoding.
        // Remove the mark, as it is treated as document text by DOMDocument.
        // http://us2.php.net/manual/en/function.mb-detect-encoding.php#91051
        if (strncmp($str, "\xFE\xFF", 2) === 0) {
            $str = substr($str, 2);
            $encoding = "UTF-16BE";
        } elseif (strncmp($str, "\xFF\xFE", 2) === 0) {
            $str = substr($str, 2);
            $encoding = "UTF-16LE";
        } elseif (strncmp($str, "\xEF\xBB\xBF", 3) === 0) {
            $str = substr($str, 3);
            $encoding = "UTF-8";
        }

        // Convert document using the given encoding
        $encodingGiven = $encoding !== null && $encoding !== "";

        if ($encodingGiven && !in_array(strtoupper($encoding), ["UTF-8", "UTF8"], true)) {
            $converted = mb_convert_encoding($str, "UTF-8", $encoding);

            if ($converted !== false) {
                $str = $converted;
            }
        }

        // Parse document encoding from `<meta>` tag ...
        $charset = "(?<charset>[a-z0-9\-]+)";
        $contentType = "http-equiv\s*=\s* ([\"']?)\s* Content-Type";
        $contentStart = "content\s*=\s* ([\"']?)\s* [\w\/]+ \s*;\s* charset\s*=\s*";
        $metaTags = [
            "/<meta \s[^>]* $contentType \s*\g1\s* $contentStart $charset \s*\g2 [^>]*>/isx", // <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
            "/<meta \s[^>]* $contentStart $charset \s*\g1\s* $contentType \s*\g3 [^>]*>/isx", // <meta content="text/html; charset=UTF-8" http-equiv="Content-Type">
            "/<meta \s[^>]* charset\s*=\s* ([\"']?)\s* $charset \s*\g1 [^>]*>/isx",           // <meta charset="UTF-8">
        ];

        foreach ($metaTags as $pattern) {
            if (preg_match($pattern, $str, $matches, PREG_OFFSET_CAPTURE)) {
                [$documentEncoding, $offset] = $matches["charset"];
                break;
            }
        }

        // ... and replace it with UTF-8; add a corresponding `<meta>` tag if
        // missing. This is to ensure that `DOMDocument` handles the document
        // encoding properly, as it will mess up the encoding if the charset
        // declaration is missing or different from the actual encoding
        if (isset($documentEncoding) && isset($offset)) {
            if (!in_array(strtoupper($documentEncoding), ["UTF-8", "UTF8"], true)) {
                $str = substr($str, 0, $offset) . "UTF-8" . substr($str, $offset + strlen($documentEncoding));
            }
        } elseif (($headPos = stripos($str, "<head>")) !== false) {
            $str = substr($str, 0, $headPos + 6) . '<meta charset="UTF-8">' . substr($str, $headPos + 6);
        } else {
            $str = '<meta charset="UTF-8">' . $str;
        }

        // If no encoding was passed, use the document encoding, falling back to
        // auto-detection
        $fallbackEncoding = $documentEncoding ?? "auto";

        if (!$encodingGiven && !in_array(strtoupper($fallbackEncoding), ["UTF-8", "UTF8"], true)) {
            $converted = mb_convert_encoding($str, "UTF-8", $fallbackEncoding);

            if ($converted !== false) {
                $str = $converted;
            }
        }

        // Store parsing warnings as messages
        set_error_handler([Helpers::class, "record_warnings"]);

        try {
            // @todo Take the quirksmode into account
            // https://quirks.spec.whatwg.org/
            // http://hsivonen.iki.fi/doctype/
            $quirksmode = false;

            $html5 = new HTML5(["encoding" => "UTF-8", "disable_html_ns" => true]);
            $dom = $html5->loadHTML($str);

            // extra step to normalize the HTML document structure
            // see Masterminds/html5-php#166
            $doc = new DOMDocument("1.0", "UTF-8");
            $doc->preserveWhiteSpace = true;
            $doc->loadHTML($html5->saveHTML($dom), LIBXML_NOWARNING | LIBXML_NOERROR);

            $this->loadDOM($doc, $quirksmode);
        } finally {
            restore_error_handler();
            $this->restorePhpConfig();
        }
    }

    /**
     * @param DOMNode $node
     * @deprecated
     */
    public static function remove_text_nodes(DOMNode $node)
    {
        self::removeTextNodes($node);
    }

    /**
     * @param DOMNode $node
     */
    public static function removeTextNodes(DOMNode $node)
    {
        $children = [];
        for ($i = 0; $i < $node->childNodes->length; $i++) {
            $child = $node->childNodes->item($i);
            if ($child->nodeName === "#text") {
                $children[] = $child;
            }
        }

        foreach ($children as $child) {
            $node->removeChild($child);
        }
    }

    /**
     * Builds the {@link FrameTree}, loads any CSS and applies the styles to
     * the {@link FrameTree}
     */
    private function processHtml()
    {
        $this->tree->build_tree();

        $this->css->load_css_file($this->css->getDefaultStylesheet(), Stylesheet::ORIG_UA);

        $acceptedmedia = Stylesheet::$ACCEPTED_GENERIC_MEDIA_TYPES;
        $acceptedmedia[] = $this->options->getDefaultMediaType();

        // <base href="" />
        /** @var \DOMElement|null */
        $baseNode = $this->dom->getElementsByTagName("base")->item(0);
        $baseHref = $baseNode ? $baseNode->getAttribute("href") : "";
        if ($baseHref !== "") {
            [$this->protocol, $this->baseHost, $this->basePath] = Helpers::explode_url($baseHref);
        }

        // Set the base path of the Stylesheet to that of the file being processed
        $this->css->set_protocol($this->protocol);
        $this->css->set_host($this->baseHost);
        $this->css->set_base_path($this->basePath);

        // Get all the stylesheets so that they are processed in document order
        $xpath = new DOMXPath($this->dom);
        $stylesheets = $xpath->query("//*[name() = 'link' or name() = 'style']");

        /** @var \DOMElement $tag */
        foreach ($stylesheets as $tag) {
            switch (strtolower($tag->nodeName)) {
                // load <link rel="STYLESHEET" ... /> tags
                case "link":
                    if (
                        (stripos($tag->getAttribute("rel"), "stylesheet") !== false // may be "appendix stylesheet"
                        || mb_strtolower($tag->getAttribute("type")) === "text/css")
                        && stripos($tag->getAttribute("rel"), "alternate") === false // don't load "alternate stylesheet"
                    ) {
                        //Check if the css file is for an accepted media type
                        //media not given then always valid
                        $formedialist = preg_split("/[\s\n,]/", $tag->getAttribute("media"), -1, PREG_SPLIT_NO_EMPTY);
                        if (count($formedialist) > 0) {
                            $accept = false;
                            foreach ($formedialist as $type) {
                                if (in_array(mb_strtolower(trim($type)), $acceptedmedia)) {
                                    $accept = true;
                                    break;
                                }
                            }

                            if (!$accept) {
                                //found at least one mediatype, but none of the accepted ones
                                //Skip this css file.
                                break;
                            }
                        }

                        $url = $tag->getAttribute("href");
                        $url = Helpers::build_url($this->protocol, $this->baseHost, $this->basePath, $url, $this->options->getChroot());

                        if ($url !== null) {
                            $this->css->load_css_file($url, Stylesheet::ORIG_AUTHOR);
                        }
                    }
                    break;

                // load <style> tags
                case "style":
                    // Accept all <style> tags by default (note this is contrary to W3C
                    // HTML 4.0 spec:
                    // http://www.w3.org/TR/REC-html40/present/styles.html#adef-media
                    // which states that the default media type is 'screen'
                    if ($tag->hasAttributes() &&
                        ($media = $tag->getAttribute("media")) &&
                        !in_array($media, $acceptedmedia)
                    ) {
                        break;
                    }

                    $css = "";
                    if ($tag->hasChildNodes()) {
                        $child = $tag->firstChild;
                        while ($child) {
                            $css .= $child->nodeValue; // Handle <style><!-- blah --></style>
                            $child = $child->nextSibling;
                        }
                    } else {
                        $css = $tag->nodeValue;
                    }

                    // Set the base path of the Stylesheet to that of the file being processed
                    $this->css->set_protocol($this->protocol);
                    $this->css->set_host($this->baseHost);
                    $this->css->set_base_path($this->basePath);

                    $this->css->load_css($css, Stylesheet::ORIG_AUTHOR);
                    break;
            }

            // Set the base path of the Stylesheet to that of the file being processed
            $this->css->set_protocol($this->protocol);
            $this->css->set_host($this->baseHost);
            $this->css->set_base_path($this->basePath);
        }
    }

    /**
     * @param string $cacheId
     * @deprecated
     */
    public function enable_caching($cacheId)
    {
        $this->enableCaching($cacheId);
    }

    /**
     * Enable experimental caching capability
     *
     * @param string $cacheId
     */
    public function enableCaching($cacheId)
    {
        $this->cacheId = $cacheId;
    }

    /**
     * @param string $value
     * @return bool
     * @deprecated
     */
    public function parse_default_view($value)
    {
        return $this->parseDefaultView($value);
    }

    /**
     * @param string $value
     * @return bool
     */
    public function parseDefaultView($value)
    {
        $valid = ["XYZ", "Fit", "FitH", "FitV", "FitR", "FitB", "FitBH", "FitBV"];

        $options = preg_split("/\s*,\s*/", trim($value));
        $defaultView = array_shift($options);

        if (!in_array($defaultView, $valid)) {
            return false;
        }

        $this->setDefaultView($defaultView, $options);
        return true;
    }

    /**
     * Renders the HTML to PDF
     */
    public function render()
    {
        $this->setPhpConfig();

        $logOutputFile = $this->options->getLogOutputFile();
        if ($logOutputFile) {
            if (!file_exists($logOutputFile) && is_writable(dirname($logOutputFile))) {
                touch($logOutputFile);
            }

            $startTime = microtime(true);
            if (is_writable($logOutputFile)) {
                ob_start();
            }
        }

        $this->processHtml();

        $this->css->apply_styles($this->tree);

        // @page style rules : size, margins
        $pageStyles = $this->css->get_page_styles();
        $basePageStyle = $pageStyles["base"];
        unset($pageStyles["base"]);

        foreach ($pageStyles as $pageStyle) {
            $pageStyle->inherit($basePageStyle);
        }

        // Set paper size if defined via CSS
        if (is_array($basePageStyle->size)) {
            // Orientation is already applied when reading the computed CSS
            // `size` value. The `Canvas` back ends, however, unconditionally
            // swap with an orientation of `landscape` and leave the defined
            // size as-is with `portrait`; so passing `portrait` as orientation
            // here (via the default value) is correct
            [$width, $height] = $basePageStyle->size;
            $this->setPaper([0, 0, $width, $height]);
        }

        // Create a new canvas instance if the current one does not match the
        // desired paper size
        $canvasWidth = $this->canvas->get_width();
        $canvasHeight = $this->canvas->get_height();
        $size = $this->getPaperSize();

        if ($canvasWidth !== $size[2] || $canvasHeight !== $size[3]) {
            $this->canvas = CanvasFactory::get_instance($this, $this->paperSize, $this->paperOrientation);
            $this->fontMetrics->setCanvas($this->canvas);
        }

        $canvas = $this->canvas;

        $root_frame = $this->tree->get_root();
        $root = Factory::decorate_root($root_frame, $this);
        foreach ($this->tree as $frame) {
            if ($frame === $root_frame) {
                continue;
            }
            Factory::decorate_frame($frame, $this, $root);
        }

        // Add meta information
        $title = $this->dom->getElementsByTagName("title");
        if ($title->length) {
            $canvas->add_info("Title", trim($title->item(0)->nodeValue));
        }

        $metas = $this->dom->getElementsByTagName("meta");
        $labels = [
            "author" => "Author",
            "keywords" => "Keywords",
            "description" => "Subject",
        ];
        /** @var \DOMElement $meta */
        foreach ($metas as $meta) {
            $name = mb_strtolower($meta->getAttribute("name"));
            $value = trim($meta->getAttribute("content"));

            if (isset($labels[$name])) {
                $canvas->add_info($labels[$name], $value);
                continue;
            }

            if ($name === "dompdf.view" && $this->parseDefaultView($value)) {
                $canvas->set_default_view($this->defaultView, $this->defaultViewOptions);
            }
        }

        $root->set_containing_block(0, 0, $canvas->get_width(), $canvas->get_height());
        $root->set_renderer(new Renderer($this));

        // This is where the magic happens:
        $root->reflow();

        if (isset($this->callbacks["end_document"])) {
            $fs = $this->callbacks["end_document"];

            foreach ($fs as $f) {
                $canvas->page_script($f);
            }
        }

        // Clean up cached images
        if (!$this->options->getDebugKeepTemp()) {
            Cache::clear($this->options->getDebugPng());
        }

        global $_dompdf_warnings, $_dompdf_show_warnings;
        if ($_dompdf_show_warnings && isset($_dompdf_warnings)) {
            echo '<b>Dompdf Warnings</b><br><pre>';
            foreach ($_dompdf_warnings as $msg) {
                echo $msg . "\n";
            }

            if ($canvas instanceof CPDF) {
                echo $canvas->get_cpdf()->messages;
            }
            echo '</pre>';
            flush();
        }

        if ($logOutputFile && is_writable($logOutputFile)) {
            $this->writeLog($logOutputFile, $startTime);
            ob_end_clean();
        }

        $this->restorePhpConfig();
    }

    /**
     * Writes the output buffer in the log file
     *
     * @param string $logOutputFile
     * @param float $startTime
     */
    private function writeLog(string $logOutputFile, float $startTime): void
    {
        $frames = Frame::$ID_COUNTER;
        $memory = memory_get_peak_usage(true) / 1024;
        $time = (microtime(true) - $startTime) * 1000;

        $out = sprintf(
            "<span style='color: #000' title='Frames'>%6d</span>" .
            "<span style='color: #009' title='Memory'>%10.2f KB</span>" .
            "<span style='color: #900' title='Time'>%10.2f ms</span>" .
            "<span  title='Quirksmode'>  " .
            ($this->quirksmode ? "<span style='color: #d00'> ON</span>" : "<span style='color: #0d0'>OFF</span>") .
            "</span><br />", $frames, $memory, $time);

        $out .= ob_get_contents();
        ob_clean();

        file_put_contents($logOutputFile, $out);
    }

    /**
     * Add meta information to the PDF after rendering.
     *
     * @deprecated
     */
    public function add_info($label, $value)
    {
        $this->addInfo($label, $value);
    }

    /**
     * Add meta information to the PDF after rendering.
     *
     * @param string $label Label of the value (Creator, Producer, etc.)
     * @param string $value The text to set
     */
    public function addInfo(string $label, string $value): void
    {
        $this->canvas->add_info($label, $value);
    }

    /**
     * Streams the PDF to the client.
     *
     * The file will open a download dialog by default. The options
     * parameter controls the output. Accepted options (array keys) are:
     *
     * 'compress' = > 1 (=default) or 0:
     *   Apply content stream compression
     *
     * 'Attachment' => 1 (=default) or 0:
     *   Set the 'Content-Disposition:' HTTP header to 'attachment'
     *   (thereby causing the browser to open a download dialog)
     *
     * @param string $filename the name of the streamed file
     * @param array $options header options (see above)
     */
    public function stream($filename = "document.pdf", $options = [])
    {
        $this->setPhpConfig();

        $this->canvas->stream($filename, $options);

        $this->restorePhpConfig();
    }

    /**
     * Returns the PDF as a string.
     *
     * The options parameter controls the output. Accepted options are:
     *
     * 'compress' = > 1 or 0 - apply content stream compression, this is
     *    on (1) by default
     *
     * @param array $options options (see above)
     *
     * @return string|null
     */
    public function output($options = [])
    {
        $this->setPhpConfig();

        $output = $this->canvas->output($options);

        $this->restorePhpConfig();

        return $output;
    }

    /**
     * @return string
     * @deprecated
     */
    public function output_html()
    {
        return $this->outputHtml();
    }

    /**
     * Returns the underlying HTML document as a string
     *
     * @return string
     */
    public function outputHtml()
    {
        return $this->dom->saveHTML();
    }

    /**
     * Get the dompdf option value
     *
     * @param string $key
     * @return mixed
     * @deprecated
     */
    public function get_option($key)
    {
        return $this->options->get($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return $this
     * @deprecated
     */
    public function set_option($key, $value)
    {
        $this->options->set($key, $value);
        return $this;
    }

    /**
     * @param array $options
     * @return $this
     * @deprecated
     */
    public function set_options(array $options)
    {
        $this->options->set($options);
        return $this;
    }

    /**
     * @param string $size
     * @param string $orientation
     * @deprecated
     */
    public function set_paper($size, $orientation = "portrait")
    {
        $this->setPaper($size, $orientation);
    }

    /**
     * Sets the paper size & orientation
     *
     * @param string|float[] $size 'letter', 'legal', 'A4', etc. {@link Dompdf\Adapter\CPDF::$PAPER_SIZES}
     * @param string $orientation 'portrait' or 'landscape'
     * @return $this
     */
    public function setPaper($size, string $orientation = "portrait"): self
    {
        $this->paperSize = $size;
        $this->paperOrientation = $orientation;
        return $this;
    }

    /**
     * Gets the paper size
     *
     * @return float[] A four-element float array
     */
    public function getPaperSize(): array
    {
        $paper = $this->paperSize;
        $orientation = $this->paperOrientation;

        if (is_array($paper)) {
            $size = array_map("floatval", $paper);
        } else {
            $paper = strtolower($paper);
            $size = CPDF::$PAPER_SIZES[$paper] ?? CPDF::$PAPER_SIZES["letter"];
        }

        if (strtolower($orientation) === "landscape") {
            [$size[2], $size[3]] = [$size[3], $size[2]];
        }

        return $size;
    }

    /**
     * Gets the paper orientation
     *
     * @return string Either "portrait" or "landscape"
     */
    public function getPaperOrientation(): string
    {
        return $this->paperOrientation;
    }

    /**
     * @param FrameTree $tree
     * @return $this
     */
    public function setTree(FrameTree $tree)
    {
        $this->tree = $tree;
        return $this;
    }

    /**
     * @return FrameTree
     * @deprecated
     */
    public function get_tree()
    {
        return $this->getTree();
    }

    /**
     * Returns the underlying {@link FrameTree} object
     *
     * @return FrameTree
     */
    public function getTree()
    {
        return $this->tree;
    }

    /**
     * @param string $protocol
     * @return $this
     * @deprecated
     */
    public function set_protocol($protocol)
    {
        return $this->setProtocol($protocol);
    }

    /**
     * Sets the protocol to use
     * FIXME validate these
     *
     * @param string $protocol
     * @return $this
     */
    public function setProtocol(string $protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * @return string
     * @deprecated
     */
    public function get_protocol()
    {
        return $this->getProtocol();
    }

    /**
     * Returns the protocol in use
     *
     * @return string
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @param string $host
     * @deprecated
     */
    public function set_host($host)
    {
        $this->setBaseHost($host);
    }

    /**
     * Sets the base hostname
     *
     * @param string $baseHost
     * @return $this
     */
    public function setBaseHost(string $baseHost)
    {
        $this->baseHost = $baseHost;
        return $this;
    }

    /**
     * @return string
     * @deprecated
     */
    public function get_host()
    {
        return $this->getBaseHost();
    }

    /**
     * Returns the base hostname
     *
     * @return string
     */
    public function getBaseHost()
    {
        return $this->baseHost;
    }

    /**
     * Sets the base path
     *
     * @param string $path
     * @deprecated
     */
    public function set_base_path($path)
    {
        $this->setBasePath($path);
    }

    /**
     * Sets the base path
     *
     * @param string $basePath
     * @return $this
     */
    public function setBasePath(string $basePath)
    {
        $this->basePath = $basePath;
        return $this;
    }

    /**
     * @return string
     * @deprecated
     */
    public function get_base_path()
    {
        return $this->getBasePath();
    }

    /**
     * Returns the base path
     *
     * @return string
     */
    public function getBasePath()
    {
        return $this->basePath;
    }

    /**
     * @param string $default_view The default document view
     * @param array $options The view's options
     * @return $this
     * @deprecated
     */
    public function set_default_view($default_view, $options)
    {
        return $this->setDefaultView($default_view, $options);
    }

    /**
     * Sets the default view
     *
     * @param string $defaultView The default document view
     * @param array $options The view's options
     * @return $this
     */
    public function setDefaultView($defaultView, $options)
    {
        $this->defaultView = $defaultView;
        $this->defaultViewOptions = $options;
        return $this;
    }

    /**
     * @param resource $http_context
     * @return $this
     * @deprecated
     */
    public function set_http_context($http_context)
    {
        return $this->setHttpContext($http_context);
    }

    /**
     * Sets the HTTP context
     *
     * @param resource|array $httpContext
     * @return $this
     */
    public function setHttpContext($httpContext)
    {
        $this->options->setHttpContext($httpContext);
        return $this;
    }

    /**
     * @return resource
     * @deprecated
     */
    public function get_http_context()
    {
        return $this->getHttpContext();
    }

    /**
     * Returns the HTTP context
     *
     * @return resource
     */
    public function getHttpContext()
    {
        return $this->options->getHttpContext();
    }

    /**
     * Set a custom `Canvas` instance to render the document to.
     *
     * Be aware that the instance will be replaced on render if the document
     * defines a paper size different from the canvas.
     *
     * @param Canvas $canvas
     * @return $this
     */
    public function setCanvas(Canvas $canvas)
    {
        $this->canvas = $canvas;
        return $this;
    }

    /**
     * @return Canvas
     * @deprecated
     */
    public function get_canvas()
    {
        return $this->getCanvas();
    }

    /**
     * Return the underlying Canvas instance (e.g. Dompdf\Adapter\CPDF, Dompdf\Adapter\GD)
     *
     * @return Canvas
     */
    public function getCanvas()
    {
        return $this->canvas;
    }

    /**
     * @param Stylesheet $css
     * @return $this
     */
    public function setCss(Stylesheet $css)
    {
        $this->css = $css;
        return $this;
    }

    /**
     * @return Stylesheet
     * @deprecated
     */
    public function get_css()
    {
        return $this->getCss();
    }

    /**
     * Returns the stylesheet
     *
     * @return Stylesheet
     */
    public function getCss()
    {
        return $this->css;
    }

    /**
     * @param DOMDocument $dom
     * @return $this
     */
    public function setDom(DOMDocument $dom)
    {
        $this->dom = $dom;
        return $this;
    }

    /**
     * @return DOMDocument
     * @deprecated
     */
    public function get_dom()
    {
        return $this->getDom();
    }

    /**
     * @return DOMDocument
     */
    public function getDom()
    {
        return $this->dom;
    }

    /**
     * @param Options $options
     * @return $this
     */
    public function setOptions(Options $options)
    {
        // For backwards compatibility
        if ($this->options && $this->options->getHttpContext() && !$options->getHttpContext()) {
            $options->setHttpContext($this->options->getHttpContext());
        }

        $this->options = $options;
        $fontMetrics = $this->fontMetrics;
        if (isset($fontMetrics)) {
            $fontMetrics->setOptions($options);
        }
        return $this;
    }

    /**
     * @return Options
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return array
     * @deprecated
     */
    public function get_callbacks()
    {
        return $this->getCallbacks();
    }

    /**
     * Returns the callbacks array
     *
     * @return array
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    /**
     * @param array $callbacks the set of callbacks to set
     * @return $this
     * @deprecated
     */
    public function set_callbacks($callbacks)
    {
        return $this->setCallbacks($callbacks);
    }

    /**
     * Define callbacks that allow modifying the document during render.
     *
     * The callbacks array should contain arrays with `event` set to a callback
     * event name and `f` set to a function or any other callable.
     *
     * The available callback events are:
     * * `begin_page_reflow`: called before page reflow
     * * `begin_frame`: called before a frame is rendered
     * * `end_frame`: called after frame rendering is complete
     * * `begin_page_render`: called before a page is rendered
     * * `end_page_render`: called after page rendering is complete
     * * `end_document`: called for every page after rendering is complete
     *
     * The function `f` receives three arguments `Frame $frame`, `Canvas $canvas`,
     * and `FontMetrics $fontMetrics` for all events but `end_document`. For
     * `end_document`, the function receives four arguments `int $pageNumber`,
     * `int $pageCount`, `Canvas $canvas`, and `FontMetrics $fontMetrics` instead.
     *
     * @param array $callbacks The set of callbacks to set.
     * @return $this
     */
    public function setCallbacks(array $callbacks): self
    {
        $this->callbacks = [];

        foreach ($callbacks as $c) {
            if (is_array($c) && isset($c["event"]) && isset($c["f"])) {
                $event = $c["event"];
                $f = $c["f"];
                if (is_string($event) && is_callable($f)) {
                    $this->callbacks[$event][] = $f;
                }
            }
        }

        return $this;
    }

    /**
     * @return boolean
     * @deprecated
     */
    public function get_quirksmode()
    {
        return $this->getQuirksmode();
    }

    /**
     * Get the quirks mode
     *
     * @return boolean true if quirks mode is active
     */
    public function getQuirksmode()
    {
        return $this->quirksmode;
    }

    /**
     * @param FontMetrics $fontMetrics
     * @return $this
     */
    public function setFontMetrics(FontMetrics $fontMetrics)
    {
        $this->fontMetrics = $fontMetrics;
        return $this;
    }

    /**
     * @return FontMetrics
     */
    public function getFontMetrics()
    {
        return $this->fontMetrics;
    }

    /**
     * PHP5 overloaded getter
     * Along with {@link Dompdf::__set()} __get() provides access to all
     * properties directly.  Typically __get() is not called directly outside
     * of this class.
     *
     * @param string $prop
     *
     * @throws Exception
     * @return mixed
     */
    function __get($prop)
    {
        switch ($prop) {
            case 'version':
                return $this->version;
            default:
                throw new Exception('Invalid property: ' . $prop);
        }
    }
}
