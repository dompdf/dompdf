<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * DOMPDF loads HTML and does its best to render it as a PDF.  It gets its
 * name from the new DomDocument PHP5 extension.  Source HTML is first
 * parsed by a DomDocument object.  DOMPDF takes the resulting DOM tree and
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
 * other inline frames.  The Block_Frame_Decorator therefore adds a $lines
 * property as well as methods to add {@link Frame}s to lines and to add
 * additional lines.  {@link Frame}s also are attached to specific
 * Positioner and {@link Frame_Reflower} objects that contain the
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
 * the {@link CPDF_Adapter} class provides a simple, stateless interface to
 * PDF generation.)  PDFLib support has now also been added, via the {@link
 * PDFLib_Adapter}.
 *
 *
 * @package dompdf
 */
class DOMPDF {

  /**
   * DomDocument representing the HTML document
   *
   * @var DOMDocument
   */
  protected $_xml;

  /**
   * Frame_Tree derived from the DOM tree
   *
   * @var Frame_Tree
   */
  protected $_tree;

  /**
   * Stylesheet for the document
   *
   * @var Stylesheet
   */
  protected $_css;

  /**
   * Actual PDF renderer
   *
   * @var Canvas
   */
  protected $_pdf;

  /**
   * Desired paper size ('letter', 'legal', 'A4', etc.)
   *
   * @var string
   */
  protected $_paper_size;

  /**
   * Paper orientation ('portrait' or 'landscape')
   *
   * @var string
   */
  protected $_paper_orientation;

  /**
   * Callbacks on new page and new element
   *
   * @var array
   */
  protected $_callbacks;

  /**
   * Experimental caching capability
   *
   * @var string
   */
  private $_cache_id;

  /**
   * Base hostname
   *
   * Used for relative paths/urls
   * @var string
   */
  protected $_base_host;

  /**
   * Absolute base path
   *
   * Used for relative paths/urls
   * @var string
   */
  protected $_base_path;

  /**
   * Protcol used to request file (file://, http://, etc)
   *
   * @var string
   */
  protected $_protocol;

  /**
   * HTTP context created with stream_context_create()
   * Will be used for file_get_contents
   *
   * @var resource
   */
  protected $_http_context;

  /**
   * Timestamp of the script start time
   *
   * @var int
   */
  private $_start_time = null;

  /**
   * The system's locale
   *
   * @var string
   */
  private $_system_locale = null;

  /**
   * Tells if the system's locale is the C standard one
   *
   * @var bool
   */
  private $_locale_standard = false;

  /**
   * The default view of the PDF in the viewer
   *
   * @var string
   */
  private $_default_view = "Fit";

  /**
   * The default view options of the PDF in the viewer
   *
   * @var array
   */
  private $_default_view_options = array();

  /**
   * Tells wether the DOM document is in quirksmode (experimental)
   *
   * @var bool
   */
  private $_quirksmode = false;

  /**
   * The list of built-in fonts
   *
   * @var array
   */
  public static $native_fonts = array(
    "courier", "courier-bold", "courier-oblique", "courier-boldoblique",
    "helvetica", "helvetica-bold", "helvetica-oblique", "helvetica-boldoblique",
    "times-roman", "times-bold", "times-italic", "times-bolditalic",
    "symbol", "zapfdinbats"
  );

  private $_options = array(
    // Directories
    "temp_dir"                 => DOMPDF_TEMP_DIR,
    "font_dir"                 => DOMPDF_FONT_DIR,
    "font_cache"               => DOMPDF_FONT_CACHE,
    "chroot"                   => DOMPDF_CHROOT,
    "log_output_file"          => DOMPDF_LOG_OUTPUT_FILE,

    // Rendering
    "default_media_type"       => DOMPDF_DEFAULT_MEDIA_TYPE,
    "default_paper_size"       => DOMPDF_DEFAULT_PAPER_SIZE,
    "default_font"             => DOMPDF_DEFAULT_FONT,
    "dpi"                      => DOMPDF_DPI,
    "font_height_ratio"        => DOMPDF_FONT_HEIGHT_RATIO,

    // Features
    "enable_unicode"           => DOMPDF_UNICODE_ENABLED,
    "enable_php"               => DOMPDF_ENABLE_PHP,
    "enable_remote"            => DOMPDF_ENABLE_REMOTE,
    "enable_css_float"         => DOMPDF_ENABLE_CSS_FLOAT,
    "enable_javascript"        => DOMPDF_ENABLE_JAVASCRIPT,
    "enable_html5_parser"      => DOMPDF_ENABLE_HTML5PARSER,
    "enable_font_subsetting"   => DOMPDF_ENABLE_FONTSUBSETTING,

    // Debug
    "debug_png"                => DEBUGPNG,
    "debug_keep_temp"          => DEBUGKEEPTEMP,
    "debug_css"                => DEBUGCSS,
    "debug_layout"             => DEBUG_LAYOUT,
    "debug_layout_lines"       => DEBUG_LAYOUT_LINES,
    "debug_layout_blocks"      => DEBUG_LAYOUT_BLOCKS,
    "debug_layout_inline"      => DEBUG_LAYOUT_INLINE,
    "debug_layout_padding_box" => DEBUG_LAYOUT_PADDINGBOX,

    // Admin
    "admin_username"           => DOMPDF_ADMIN_USERNAME,
    "admin_password"           => DOMPDF_ADMIN_PASSWORD,
  );

  /**
   * Class constructor
   */
  function __construct() {
    $this->_locale_standard = sprintf('%.1f', 1.0) == '1.0';

    $this->save_locale();

    $this->_messages = array();
    $this->_css = new Stylesheet($this);
    $this->_pdf = null;
    $this->_paper_size = DOMPDF_DEFAULT_PAPER_SIZE;
    $this->_paper_orientation = "portrait";
    $this->_base_protocol = "";
    $this->_base_host = "";
    $this->_base_path = "";
    $this->_http_context = null;
    $this->_callbacks = array();
    $this->_cache_id = null;

    $this->restore_locale();
  }

  /**
   * Class destructor
   */
  function __destruct() {
    clear_object($this);
  }

  /**
   * Get the dompdf option value
   *
   * @param string $key
   *
   * @return mixed
   * @throws DOMPDF_Exception
   */
  function get_option($key) {
    if ( !array_key_exists($key, $this->_options) ) {
      throw new DOMPDF_Exception("Option '$key' doesn't exist");
    }

    return $this->_options[$key];
  }

  /**
   * @param string $key
   * @param mixed  $value
   *
   * @throws DOMPDF_Exception
   */
  function set_option($key, $value) {
    if ( !array_key_exists($key, $this->_options) ) {
      throw new DOMPDF_Exception("Option '$key' doesn't exist");
    }

    $this->_options[$key] = $value;
  }

  /**
   * @param array $options
   */
  function set_options(array $options) {
    foreach ($options as $key => $value) {
      $this->set_option($key, $value);
    }
  }

  /**
   * Save the system's locale configuration and
   * set the right value for numeric formatting
   */
  private function save_locale() {
    if ( $this->_locale_standard ) {
      return;
    }

    $this->_system_locale = setlocale(LC_NUMERIC, "0");
    setlocale(LC_NUMERIC, "C");
  }

  /**
   * Restore the system's locale configuration
   */
  private function restore_locale() {
    if ( $this->_locale_standard ) {
      return;
    }

    setlocale(LC_NUMERIC, $this->_system_locale);
  }

  /**
   * Returns the underlying {@link Frame_Tree} object
   *
   * @return Frame_Tree
   */
  function get_tree() {
    return $this->_tree;
  }

  /**
   * Sets the protocol to use
   * FIXME validate these
   *
   * @param string $proto
   */
  function set_protocol($proto) {
    $this->_protocol = $proto;
  }

  /**
   * Sets the base hostname
   *
   * @param string $host
   */
  function set_host($host) {
    $this->_base_host = $host;
  }

  /**
   * Sets the base path
   *
   * @param string $path
   */
  function set_base_path($path) {
    $this->_base_path = $path;
  }

  /**
   * Sets the HTTP context
   *
   * @param resource $http_context
   */
  function set_http_context($http_context) {
    $this->_http_context = $http_context;
  }

  /**
   * Sets the default view
   *
   * @param string $default_view The default document view
   * @param array  $options      The view's options
   */
  function set_default_view($default_view, $options) {
    $this->_default_view = $default_view;
    $this->_default_view_options = $options;
  }

  /**
   * Returns the protocol in use
   *
   * @return string
   */
  function get_protocol() {
    return $this->_protocol;
  }

  /**
   * Returns the base hostname
   *
   * @return string
   */
  function get_host() {
    return $this->_base_host;
  }

  /**
   * Returns the base path
   *
   * @return string
   */
  function get_base_path() {
    return $this->_base_path;
  }

  /**
   * Returns the HTTP context
   *
   * @return resource
   */
  function get_http_context() {
    return $this->_http_context;
  }

  /**
   * Return the underlying Canvas instance (e.g. CPDF_Adapter, GD_Adapter)
   *
   * @return Canvas
   */
  function get_canvas() {
    return $this->_pdf;
  }

  /**
   * Returns the callbacks array
   *
   * @return array
   */
  function get_callbacks() {
    return $this->_callbacks;
  }

  /**
   * Returns the stylesheet
   *
   * @return Stylesheet
   */
  function get_css() {
    return $this->_css;
  }

  /**
   * @return DOMDocument
   */
  function get_dom() {
    return $this->_xml;
  }

  /**
   * Loads an HTML file
   * Parse errors are stored in the global array _dompdf_warnings.
   *
   * @param string $file a filename or url to load
   *
   * @throws DOMPDF_Exception
   */
  function load_html_file($file) {
    $this->save_locale();

    // Store parsing warnings as messages (this is to prevent output to the
    // browser if the html is ugly and the dom extension complains,
    // preventing the pdf from being streamed.)
    if ( !$this->_protocol && !$this->_base_host && !$this->_base_path ) {
      list($this->_protocol, $this->_base_host, $this->_base_path) = explode_url($file);
    }

    if ( !$this->get_option("enable_remote") && ($this->_protocol != "" && $this->_protocol !== "file://" ) ) {
      throw new DOMPDF_Exception("Remote file requested, but DOMPDF_ENABLE_REMOTE is false.");
    }

    if ($this->_protocol == "" || $this->_protocol === "file://") {

      // Get the full path to $file, returns false if the file doesn't exist
      $realfile = realpath($file);
      if ( !$realfile ) {
        throw new DOMPDF_Exception("File '$file' not found.");
      }

      $chroot = $this->get_option("chroot");
      if ( strpos($realfile, $chroot) !== 0 ) {
        throw new DOMPDF_Exception("Permission denied on $file. The file could not be found under the directory specified by DOMPDF_CHROOT.");
      }

      // Exclude dot files (e.g. .htaccess)
      if ( substr(basename($realfile), 0, 1) === "." ) {
        throw new DOMPDF_Exception("Permission denied on $file.");
      }

      $file = $realfile;
    }

    $contents = file_get_contents($file, null, $this->_http_context);
    $encoding = null;

    // See http://the-stickman.com/web-development/php/getting-http-response-headers-when-using-file_get_contents/
    if ( isset($http_response_header) ) {
      foreach($http_response_header as $_header) {
        if ( preg_match("@Content-Type:\s*[\w/]+;\s*?charset=([^\s]+)@i", $_header, $matches) ) {
          $encoding = strtoupper($matches[1]);
          break;
        }
      }
    }

    $this->restore_locale();

    $this->load_html($contents, $encoding);
  }

  /**
   * Loads an HTML string
   * Parse errors are stored in the global array _dompdf_warnings.
   * @todo use the $encoding variable
   *
   * @param string $str      HTML text to load
   * @param string $encoding Not used yet
   */
  function load_html($str, $encoding = null) {
    $this->save_locale();

    // FIXME: Determine character encoding, switch to UTF8, update meta tag. Need better http/file stream encoding detection, currently relies on text or meta tag.
    mb_detect_order('auto');

    if (mb_detect_encoding($str) !== 'UTF-8') {
      $metatags = array(
        '@<meta\s+http-equiv="Content-Type"\s+content="(?:[\w/]+)(?:;\s*?charset=([^\s"]+))?@i',
        '@<meta\s+content="(?:[\w/]+)(?:;\s*?charset=([^\s"]+))"?\s+http-equiv="Content-Type"@i',
        '@<meta [^>]*charset\s*=\s*["\']?\s*([^"\' ]+)@i',
      );

      foreach($metatags as $metatag) {
        if (preg_match($metatag, $str, $matches)) break;
      }

      if (mb_detect_encoding($str) == '') {
        if (isset($matches[1])) {
          $encoding = strtoupper($matches[1]);
        }
        else {
          $encoding = 'UTF-8';
        }
      }
      else {
        if ( isset($matches[1]) ) {
          $encoding = strtoupper($matches[1]);
        }
        else {
          $encoding = 'auto';
        }
      }

      if ( $encoding !== 'UTF-8' ) {
        $str = mb_convert_encoding($str, 'UTF-8', $encoding);
      }

      if ( isset($matches[1]) ) {
        $str = preg_replace('/charset=([^\s"]+)/i', 'charset=UTF-8', $str);
      }
      else {
        $str = str_replace('<head>', '<head><meta http-equiv="Content-Type" content="text/html;charset=UTF-8">', $str);
      }
    }
    else {
      $encoding = 'UTF-8';
    }

    // remove BOM mark from UTF-8, it's treated as document text by DOMDocument
    // FIXME: roll this into the encoding detection using UTF-8/16/32 BOM (http://us2.php.net/manual/en/function.mb-detect-encoding.php#91051)?
    if ( substr($str, 0, 3) == chr(0xEF).chr(0xBB).chr(0xBF) ) {
      $str = substr($str, 3);
    }

    // Parse embedded php, first-pass
    if ( $this->get_option("enable_php") ) {
      ob_start();
      eval("?" . ">$str");
      $str = ob_get_clean();
    }

    // if the document contains non utf-8 with a utf-8 meta tag chars and was 
    // detected as utf-8 by mbstring, problems could happen.
    // http://devzone.zend.com/article/8855
    if ( $encoding !== 'UTF-8' ) {
      $re = '/<meta ([^>]*)((?:charset=[^"\' ]+)([^>]*)|(?:charset=["\'][^"\' ]+["\']))([^>]*)>/i';
      $str = preg_replace($re, '<meta $1$3>', $str);
    }

    // Store parsing warnings as messages
    set_error_handler("record_warnings");

    // @todo Take the quirksmode into account
    // http://hsivonen.iki.fi/doctype/
    // https://developer.mozilla.org/en/mozilla's_quirks_mode
    $quirksmode = false;

    if ( $this->get_option("enable_html5_parser") ) {
      $tokenizer = new HTML5_Tokenizer($str);
      $tokenizer->parse();
      $doc = $tokenizer->save();

      // Remove #text children nodes in nodes that shouldn't have
      $tag_names = array("html", "table", "tbody", "thead", "tfoot", "tr");
      foreach($tag_names as $tag_name) {
        $nodes = $doc->getElementsByTagName($tag_name);

        foreach($nodes as $node) {
          self::remove_text_nodes($node);
        }
      }

      $quirksmode = ($tokenizer->getTree()->getQuirksMode() > HTML5_TreeBuilder::NO_QUIRKS);
    }
    else {
      // loadHTML assumes ISO-8859-1 unless otherwise specified, but there are
      // bugs in how DOMDocument determines the actual encoding. Converting to
      // HTML-ENTITIES prior to import appears to resolve the issue.
      // http://devzone.zend.com/1538/php-dom-xml-extension-encoding-processing/ (see #4)
      // http://stackoverflow.com/a/11310258/264628
      $doc = new DOMDocument();
      $doc->preserveWhiteSpace = true;
      $doc->loadHTML( mb_convert_encoding( $str , 'HTML-ENTITIES' , 'UTF-8' ) );

      // If some text is before the doctype, we are in quirksmode
      if ( preg_match("/^(.+)<!doctype/i", ltrim($str), $matches) ) {
        $quirksmode = true;
      }
      // If no doctype is provided, we are in quirksmode
      elseif ( !preg_match("/^<!doctype/i", ltrim($str), $matches) ) {
        $quirksmode = true;
      }
      else {
        // HTML5 <!DOCTYPE html>
        if ( !$doc->doctype->publicId && !$doc->doctype->systemId ) {
          $quirksmode = false;
        }

        // not XHTML
        if ( !preg_match("/xhtml/i", $doc->doctype->publicId) ) {
          $quirksmode = true;
        }
      }
    }

    $this->_xml = $doc;
    $this->_quirksmode = $quirksmode;

    $this->_tree = new Frame_Tree($this->_xml);

    restore_error_handler();

    $this->restore_locale();
  }

  static function remove_text_nodes(DOMNode $node) {
    $children = array();
    for ($i = 0; $i < $node->childNodes->length; $i++) {
      $child = $node->childNodes->item($i);
      if ( $child->nodeName === "#text" ) {
        $children[] = $child;
      }
    }

    foreach($children as $child) {
      $node->removeChild($child);
    }
  }

  /**
   * Builds the {@link Frame_Tree}, loads any CSS and applies the styles to
   * the {@link Frame_Tree}
   */
  protected function _process_html() {
    $this->_tree->build_tree();

    $this->_css->load_css_file(Stylesheet::DEFAULT_STYLESHEET, Stylesheet::ORIG_UA);

    $acceptedmedia = Stylesheet::$ACCEPTED_GENERIC_MEDIA_TYPES;
    $acceptedmedia[] = $this->get_option("default_media_type");

    // <base href="" />
    $base_nodes = $this->_xml->getElementsByTagName("base");
    if ( $base_nodes->length && ($href = $base_nodes->item(0)->getAttribute("href")) ) {
      list($this->_protocol, $this->_base_host, $this->_base_path) = explode_url($href);
    }

    // Set the base path of the Stylesheet to that of the file being processed
    $this->_css->set_protocol($this->_protocol);
    $this->_css->set_host($this->_base_host);
    $this->_css->set_base_path($this->_base_path);

    // Get all the stylesheets so that they are processed in document order
    $xpath = new DOMXPath($this->_xml);
    $stylesheets = $xpath->query("//*[name() = 'link' or name() = 'style']");

    foreach($stylesheets as $tag) {
      switch (strtolower($tag->nodeName)) {
        // load <link rel="STYLESHEET" ... /> tags
        case "link":
          if ( mb_strtolower(stripos($tag->getAttribute("rel"), "stylesheet") !== false) || // may be "appendix stylesheet"
            mb_strtolower($tag->getAttribute("type")) === "text/css" ) {
            //Check if the css file is for an accepted media type
            //media not given then always valid
            $formedialist = preg_split("/[\s\n,]/", $tag->getAttribute("media"),-1, PREG_SPLIT_NO_EMPTY);
            if ( count($formedialist) > 0 ) {
              $accept = false;
              foreach ( $formedialist as $type ) {
                if ( in_array(mb_strtolower(trim($type)), $acceptedmedia) ) {
                  $accept = true;
                  break;
                }
              }

              if (!$accept) {
                //found at least one mediatype, but none of the accepted ones
                //Skip this css file.
                continue;
              }
            }

            $url = $tag->getAttribute("href");
            $url = build_url($this->_protocol, $this->_base_host, $this->_base_path, $url);

            $this->_css->load_css_file($url, Stylesheet::ORIG_AUTHOR);
          }
          break;

        // load <style> tags
        case "style":
          // Accept all <style> tags by default (note this is contrary to W3C
          // HTML 4.0 spec:
          // http://www.w3.org/TR/REC-html40/present/styles.html#adef-media
          // which states that the default media type is 'screen'
          if ( $tag->hasAttributes() &&
            ($media = $tag->getAttribute("media")) &&
            !in_array($media, $acceptedmedia) ) {
            continue;
          }

          $css = "";
          if ( $tag->hasChildNodes() ) {
            $child = $tag->firstChild;
            while ( $child ) {
              $css .= $child->nodeValue; // Handle <style><!-- blah --></style>
              $child = $child->nextSibling;
            }
          }
          else {
            $css = $tag->nodeValue;
          }

          $this->_css->load_css($css);
          break;
      }
    }
  }

  /**
   * Sets the paper size & orientation
   *
   * @param string $size 'letter', 'legal', 'A4', etc. {@link CPDF_Adapter::$PAPER_SIZES}
   * @param string $orientation 'portrait' or 'landscape'
   */
  function set_paper($size, $orientation = "portrait") {
    $this->_paper_size = $size;
    $this->_paper_orientation = $orientation;
  }

  /**
   * Enable experimental caching capability
   * @access private
   */
  function enable_caching($cache_id) {
    $this->_cache_id = $cache_id;
  }

  /**
   * Sets callbacks for events like rendering of pages and elements.
   * The callbacks array contains arrays with 'event' set to 'begin_page',
   * 'end_page', 'begin_frame', or 'end_frame' and 'f' set to a function or
   * object plus method to be called.
   *
   * The function 'f' must take an array as argument, which contains info
   * about the event.
   *
   * @param array $callbacks the set of callbacks to set
   */
  function set_callbacks($callbacks) {
    if (is_array($callbacks)) {
      $this->_callbacks = array();
      foreach ($callbacks as $c) {
        if (is_array($c) && isset($c['event']) && isset($c['f'])) {
          $event = $c['event'];
          $f = $c['f'];
          if (is_callable($f) && is_string($event)) {
            $this->_callbacks[$event][] = $f;
          }
        }
      }
    }
  }

  /**
   * Get the quirks mode
   *
   * @return boolean true if quirks mode is active
   */
  function get_quirksmode(){
    return $this->_quirksmode;
  }

  function parse_default_view($value) {
    $valid = array("XYZ", "Fit", "FitH", "FitV", "FitR", "FitB", "FitBH", "FitBV");

    $options = preg_split("/\s*,\s*/", trim($value));
    $default_view = array_shift($options);

    if ( !in_array($default_view, $valid) ) {
      return false;
    }

    $this->set_default_view($default_view, $options);
    return true;
  }

  /**
   * Renders the HTML to PDF
   */
  function render() {
    $this->save_locale();

    $log_output_file = $this->get_option("log_output_file");
    if ( $log_output_file ) {
      if ( !file_exists($log_output_file) && is_writable(dirname($log_output_file)) ) {
        touch($log_output_file);
      }

      $this->_start_time = microtime(true);
      ob_start();
    }

    //enable_mem_profile();

    $this->_process_html();

    $this->_css->apply_styles($this->_tree);

    // @page style rules : size, margins
    $page_styles = $this->_css->get_page_styles();

    $base_page_style = $page_styles["base"];
    unset($page_styles["base"]);

    foreach($page_styles as $_page_style) {
      $_page_style->inherit($base_page_style);
    }

    if ( is_array($base_page_style->size) ) {
      $this->set_paper(array(0, 0, $base_page_style->size[0], $base_page_style->size[1]));
    }

    $this->_pdf = Canvas_Factory::get_instance($this, $this->_paper_size, $this->_paper_orientation);
    Font_Metrics::init($this->_pdf);

    if ( $this->get_option("enable_font_subsetting") && $this->_pdf instanceof CPDF_Adapter ) {
      foreach ($this->_tree->get_frames() as $frame) {
        $style = $frame->get_style();
        $node  = $frame->get_node();

        // Handle text nodes
        if ( $node->nodeName === "#text" ) {
          $this->_pdf->register_string_subset($style->font_family, $node->nodeValue);
          continue;
        }

        // Handle generated content (list items)
        if ( $style->display === "list-item" ) {
          $chars = List_Bullet_Renderer::get_counter_chars($style->list_style_type);
          $this->_pdf->register_string_subset($style->font_family, $chars);
          continue;
        }
        
        // Handle other generated content (pseudo elements)
        // FIXME: This only captures the text of the stylesheet declaration,
        //        not the actual generated content, and forces all possible counter
        //        values. See notes in issue #750.
        if ( $frame->get_node()->nodeName == "dompdf_generated" ) {
          // all possible counter values
          $chars = List_Bullet_Renderer::get_counter_chars('decimal');
          $this->_pdf->register_string_subset($style->font_family, $chars);
          $chars = List_Bullet_Renderer::get_counter_chars('upper-alpha');
          $this->_pdf->register_string_subset($style->font_family, $chars);
          $chars = List_Bullet_Renderer::get_counter_chars('lower-alpha');
          $this->_pdf->register_string_subset($style->font_family, $chars);
          $chars = List_Bullet_Renderer::get_counter_chars('lower-greek');
          $this->_pdf->register_string_subset($style->font_family, $chars);
          // the text of the stylesheet declaration
          $this->_pdf->register_string_subset($style->font_family, $style->content);
          continue;
        }
      }
    }

    $root = null;

    foreach ($this->_tree->get_frames() as $frame) {
      // Set up the root frame
      if ( is_null($root) ) {
        $root = Frame_Factory::decorate_root( $this->_tree->get_root(), $this );
        continue;
      }

      // Create the appropriate decorators, reflowers & positioners.
      Frame_Factory::decorate_frame($frame, $this, $root);
    }

    // Add meta information
    $title = $this->_xml->getElementsByTagName("title");
    if ( $title->length ) {
      $this->_pdf->add_info("Title", trim($title->item(0)->nodeValue));
    }

    $metas = $this->_xml->getElementsByTagName("meta");
    $labels = array(
      "author" => "Author",
      "keywords" => "Keywords",
      "description" => "Subject",
    );
    foreach($metas as $meta) {
      $name = mb_strtolower($meta->getAttribute("name"));
      $value = trim($meta->getAttribute("content"));

      if ( isset($labels[$name]) ) {
        $this->_pdf->add_info($labels[$name], $value);
        continue;
      }

      if ( $name === "dompdf.view" && $this->parse_default_view($value) ) {
        $this->_pdf->set_default_view($this->_default_view, $this->_default_view_options);
      }
    }

    $root->set_containing_block(0, 0, $this->_pdf->get_width(), $this->_pdf->get_height());
    $root->set_renderer(new Renderer($this));

    // This is where the magic happens:
    $root->reflow();

    // Clean up cached images
    Image_Cache::clear();

    global $_dompdf_warnings, $_dompdf_show_warnings;
    if ( $_dompdf_show_warnings ) {
      echo '<b>DOMPDF Warnings</b><br><pre>';
      foreach ($_dompdf_warnings as $msg) {
        echo $msg . "\n";
      }
      echo $this->get_canvas()->get_cpdf()->messages;
      echo '</pre>';
      flush();
    }

    $this->restore_locale();
  }

  /**
   * Add meta information to the PDF after rendering
   */
  function add_info($label, $value) {
    if ( !is_null($this->_pdf) ) {
      $this->_pdf->add_info($label, $value);
    }
  }

  /**
   * Writes the output buffer in the log file
   *
   * @return void
   */
  private function write_log() {
    $log_output_file = $this->get_option("log_output_file");
    if ( !$log_output_file || !is_writable($log_output_file) ) {
      return;
    }

    $frames = Frame::$ID_COUNTER;
    $memory = DOMPDF_memory_usage() / 1024;
    $time = (microtime(true) - $this->_start_time) * 1000;

    $out = sprintf(
      "<span style='color: #000' title='Frames'>%6d</span>".
        "<span style='color: #009' title='Memory'>%10.2f KB</span>".
        "<span style='color: #900' title='Time'>%10.2f ms</span>".
        "<span  title='Quirksmode'>  ".
        ($this->_quirksmode ? "<span style='color: #d00'> ON</span>" : "<span style='color: #0d0'>OFF</span>").
        "</span><br />", $frames, $memory, $time);

    $out .= ob_get_clean();

    $log_output_file = $this->get_option("log_output_file");
    file_put_contents($log_output_file, $out);
  }

  /**
   * Streams the PDF to the client
   *
   * The file will open a download dialog by default.  The options
   * parameter controls the output.  Accepted options are:
   *
   * 'Accept-Ranges' => 1 or 0 - if this is not set to 1, then this
   *    header is not included, off by default this header seems to
   *    have caused some problems despite the fact that it is supposed
   *    to solve them, so I am leaving it off by default.
   *
   * 'compress' = > 1 or 0 - apply content stream compression, this is
   *    on (1) by default
   *
   * 'Attachment' => 1 or 0 - if 1, force the browser to open a
   *    download dialog, on (1) by default
   *
   * @param string $filename the name of the streamed file
   * @param array  $options header options (see above)
   */
  function stream($filename, $options = null) {
    $this->save_locale();

    $this->write_log();

    if ( !is_null($this->_pdf) ) {
      $this->_pdf->stream($filename, $options);
    }

    $this->restore_locale();
  }

  /**
   * Returns the PDF as a string
   *
   * The file will open a download dialog by default.  The options
   * parameter controls the output.  Accepted options are:
   *
   *
   * 'compress' = > 1 or 0 - apply content stream compression, this is
   *    on (1) by default
   *
   *
   * @param array  $options options (see above)
   *
   * @return string
   */
  function output($options = null) {
    $this->save_locale();

    $this->write_log();

    if ( is_null($this->_pdf) ) {
      return null;
    }

    $output = $this->_pdf->output( $options );

    $this->restore_locale();

    return $output;
  }

  /**
   * Returns the underlying HTML document as a string
   *
   * @return string
   */
  function output_html() {
    return $this->_xml->saveHTML();
  }
}
