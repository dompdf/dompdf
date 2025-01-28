<?php
/**
 * A PHP class to provide the basic functionality to create a pdf document without
 * any requirement for additional modules.
 *
 * @author  Wayne Munro
 * @license http://creativecommons.org/licenses/publicdomain/ Public Domain
 * @package Cpdf
 */
namespace Dompdf;

use FontLib\Exception\FontNotFoundException;
use FontLib\Font;
use FontLib\BinaryStream;

class Cpdf
{
    const PDF_VERSION = '1.7';

    const ACROFORM_SIG_SIGNATURESEXISTS = 0x0001;
    const ACROFORM_SIG_APPENDONLY =       0x0002;

    const ACROFORM_FIELD_BUTTON =   'Btn';
    const ACROFORM_FIELD_TEXT =     'Tx';
    const ACROFORM_FIELD_CHOICE =   'Ch';
    const ACROFORM_FIELD_SIG =      'Sig';

    const ACROFORM_FIELD_READONLY =               0x0001;
    const ACROFORM_FIELD_REQUIRED =               0x0002;

    const ACROFORM_FIELD_TEXT_MULTILINE =         0x1000;
    const ACROFORM_FIELD_TEXT_PASSWORD =          0x2000;
    const ACROFORM_FIELD_TEXT_RICHTEXT =         0x10000;

    const ACROFORM_FIELD_CHOICE_COMBO =          0x20000;
    const ACROFORM_FIELD_CHOICE_EDIT =           0x40000;
    const ACROFORM_FIELD_CHOICE_SORT =           0x80000;
    const ACROFORM_FIELD_CHOICE_MULTISELECT =   0x200000;

    const XOBJECT_SUBTYPE_FORM = 'Form';

    /**
     * @var integer The current number of pdf objects in the document
     */
    public $numObj = 0;

    /**
     * @var array This array contains all of the pdf objects, ready for final assembly
     */
    public $objects = [];

    /**
     * @var integer The objectId (number within the objects array) of the document catalog
     */
    public $catalogId;

    /**
     * @var integer The objectId (number within the objects array) of indirect references (Javascript EmbeddedFiles)
     */
    protected $indirectReferenceId = 0;

    /**
     * @var integer The objectId (number within the objects array)
     */
    protected $embeddedFilesId = 0;

    /**
     * AcroForm objectId
     *
     * @var integer
     */
    public $acroFormId;

    /**
     * @var int
     */
    public $signatureMaxLen = 5000;

    /**
     * @var bool Is PDF/A compliance mode enabled
     */
    public $pdfa = false;

    /**
     * @var array Array carrying information about the fonts that the system currently knows about
     * Used to ensure that a font is not loaded twice, among other things
     */
    public $fonts = [];

    /**
     * @var string The default font metrics file to use if no other font has been loaded.
     * The path to the directory containing the font metrics should be included
     */
    public $defaultFont = __DIR__ . '/fonts/Helvetica.afm';

    /**
     * @string A record of the current font
     */
    public $currentFont = '';

    /**
     * @var string The current base font
     */
    public $currentBaseFont = '';

    /**
     * @var integer The number of the current font within the font array
     */
    public $currentFontNum = 0;

    /**
     * @var integer
     */
    public $currentNode;

    /**
     * @var integer Object number of the current page
     */
    public $currentPage;

    /**
     * @var integer Object number of the currently active contents block
     */
    public $currentContents;

    /**
     * @var integer Number of fonts within the system
     */
    public $numFonts = 0;

    /**
     * @var integer Number of graphic state resources used
     */
    private $numStates = 0;

    /**
     * @var array Number of graphic state resources used
     */
    private $gstates = [];

    /**
     * @var array|null Current color for fill operations, defaults to inactive value,
     * all three components should be between 0 and 1 inclusive when active
     */
    public $currentColor = null;

    /**
     * @var array|null Current color for stroke operations (lines etc.)
     */
    public $currentStrokeColor = null;

    /**
     * @var string Fill rule (nonzero or evenodd)
     */
    public $fillRule = "nonzero";

    /**
     * @var string Current style that lines are drawn in
     */
    public $currentLineStyle = '';

    /**
     * @var array|null Current line transparency (partial graphics state)
     */
    public $currentLineTransparency = ["mode" => "Normal", "opacity" => 1.0];

    /**
     * @var array|null Current fill transparency (partial graphics state)
     */
    public $currentFillTransparency = ["mode" => "Normal", "opacity" => 1.0];

    /**
     * @var array An array which is used to save the state of the document, mainly the colors and styles
     * it is used to temporarily change to another state, then change back to what it was before
     */
    public $stateStack = [];

    /**
     * @var integer Number of elements within the state stack
     */
    public $nStateStack = 0;

    /**
     * @var integer Number of page objects within the document
     */
    public $numPages = 0;

    /**
     * @var array Object Id storage stack
     */
    public $stack = [];

    /**
     * @var integer Number of elements within the object Id storage stack
     */
    public $nStack = 0;

    /**
     * an array which contains information about the objects which are not firmly attached to pages
     * these have been added with the addObject function
     */
    public $looseObjects = [];

    /**
     * array contains information about how the loose objects are to be added to the document
     */
    public $addLooseObjects = [];

    /**
     * @var integer The objectId of the information object for the document
     * this contains authorship, title etc.
     */
    public $infoObject = 0;

    /**
     * @var integer Number of images being tracked within the document
     */
    public $numImages = 0;

    /**
     * @var array An array containing options about the document
     * it defaults to turning on the compression of the objects
     */
    public $options = ['compression' => true];

    /**
     * @var integer The objectId of the first page of the document
     */
    public $firstPageId;

    /**
     * @var integer The object Id of the procset object
     */
    public $procsetObjectId;

    /**
     * @var array Store the information about the relationship between font families
     * this used so that the code knows which font is the bold version of another font, etc.
     * the value of this array is initialised in the constructor function.
     */
    public $fontFamilies = [];

    /**
     * @var string Folder for php serialized formats of font metrics files.
     * If empty string, use same folder as original metrics files.
     * This can be passed in from class creator.
     * If this folder does not exist or is not writable, Cpdf will be **much** slower.
     * Because of potential trouble with php safe mode, folder cannot be created at runtime.
     */
    public $fontcache = '';

    /**
     * @var integer The version of the font metrics cache file.
     * This value must be manually incremented whenever the internal font data structure is modified.
     */
    public $fontcacheVersion = 6;

    /**
     * @var string Temporary folder.
     * If empty string, will attempt system tmp folder.
     * This can be passed in from class creator.
     */
    public $tmp = '';

    /**
     * @var string Track if the current font is bolded or italicised
     */
    public $currentTextState = '';

    /**
     * @var string Messages are stored here during processing, these can be selected afterwards to give some useful debug information
     */
    public $messages = '';

    /**
     * @var string The encryption array for the document encryption is stored here
     */
    public $arc4 = '';

    /**
     * @var integer The object Id of the encryption information
     */
    public $arc4_objnum = 0;

    /**
     * @var string The file identifier, used to uniquely identify a pdf document
     */
    public $fileIdentifier = '';

    /**
     * @var boolean A flag to say if a document is to be encrypted or not
     */
    public $encrypted = false;

    /**
     * @var string The encryption key for the encryption of all the document content (structure is not encrypted)
     */
    public $encryptionKey = '';

    /**
     * @var array Array which forms a stack to keep track of nested callback functions
     */
    public $callback = [];

    /**
     * @var integer The number of callback functions in the callback array
     */
    public $nCallback = 0;

    /**
     * @var array Store label->id pairs for named destinations, these will be used to replace internal links
     * done this way so that destinations can be defined after the location that links to them
     */
    public $destinations = [];

    /**
     * @var array Store the stack for the transaction commands, each item in here is a record of the values of all the
     * publiciables within the class, so that the user can rollback at will (from each 'start' command)
     * note that this includes the objects array, so these can be large.
     */
    public $checkpoint = '';

    /**
     * @var array Table of Image origin filenames and image labels which were already added with o_image().
     * Allows to merge identical images
     */
    public $imagelist = [];

    /**
     * @var array Table of already added alpha and plain image files for transparent PNG images.
     */
    protected $imageAlphaList = [];

    /**
     * @var array List of temporary image files to be deleted after processing.
     */
    protected $imageCache = [];

    /**
     * @var boolean Whether the text passed in should be treated as Unicode or just local character set.
     */
    public $isUnicode = false;

    /**
     * @var string the JavaScript code of the document
     */
    public $javascript = '';

    /**
     * @var boolean whether the compression is possible
     */
    protected $compressionReady = false;

    /**
     * @var array Current page size
     */
    protected $currentPageSize = ["width" => 0, "height" => 0];

    /**
     * @var array All the chars that will be required in the font subsets
     */
    protected $stringSubsets = [];

    /**
     * @var string The target internal encoding
     */
    protected static $targetEncoding = 'Windows-1252';

    /**
     * @var array
     */
    protected $byteRange = array();

    /**
     * @var array The list of the core fonts
     */
    protected static $coreFonts = [
        'courier',
        'courier-bold',
        'courier-oblique',
        'courier-boldoblique',
        'helvetica',
        'helvetica-bold',
        'helvetica-oblique',
        'helvetica-boldoblique',
        'times-roman',
        'times-bold',
        'times-italic',
        'times-bolditalic',
        'symbol',
        'zapfdingbats'
    ];

    /**
     * Class constructor
     * This will start a new document
     *
     * @param array   $pageSize  Array of 4 numbers, defining the bottom left and upper right corner of the page. first two are normally zero.
     * @param boolean $isUnicode Whether text will be treated as Unicode or not.
     * @param string  $fontcache The font cache folder
     * @param string  $tmp       The temporary folder
     */
    function __construct($pageSize = [0, 0, 612, 792], $isUnicode = false, $fontcache = '', $tmp = '')
    {
        $this->isUnicode = $isUnicode;
        $this->fontcache = rtrim($fontcache, DIRECTORY_SEPARATOR."/\\");
        $this->tmp = ($tmp !== '' ? $tmp : sys_get_temp_dir());
        $this->newDocument($pageSize);

        $this->compressionReady = function_exists('gzcompress');

        if (in_array('Windows-1252', mb_list_encodings())) {
            self::$targetEncoding = 'Windows-1252';
        }

        // also initialize the font families that are known about already
        $this->setFontFamily('init');
    }

    public function __destruct()
    {
        foreach ($this->imageCache as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Document object methods (internal use only)
     *
     * There is about one object method for each type of object in the pdf document
     * Each function has the same call list ($id,$action,$options).
     * $id = the object ID of the object, or what it is to be if it is being created
     * $action = a string specifying the action to be performed, though ALL must support:
     *           'new' - create the object with the id $id
     *           'out' - produce the output for the pdf object
     * $options = optional, a string or array containing the various parameters for the object
     *
     * These, in conjunction with the output function are the ONLY way for output to be produced
     * within the pdf 'file'.
     */

    /**
     * Destination object, used to specify the location for the user to jump to, presently on opening
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return string|null
     */
    protected function o_destination($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'destination', 'info' => []];
                $tmp = '';
                switch ($options['type']) {
                    case 'XYZ':
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case 'FitR':
                        $tmp = ' ' . $options['p3'] . $tmp;
                    case 'FitH':
                    case 'FitV':
                    case 'FitBH':
                    /** @noinspection PhpMissingBreakStatementInspection */
                    case 'FitBV':
                        $tmp = ' ' . $options['p1'] . ' ' . $options['p2'] . $tmp;
                    case 'Fit':
                    case 'FitB':
                        $tmp = $options['type'] . $tmp;
                        $this->objects[$id]['info']['string'] = $tmp;
                        $this->objects[$id]['info']['page'] = $options['page'];
                }
                break;

            case 'out':
                $o = &$this->objects[$id];

                $tmp = $o['info'];
                $res = "\n$id 0 obj\n" . '[' . $tmp['page'] . ' 0 R /' . $tmp['string'] . "]\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * set the viewer preferences
     *
     * @param $id
     * @param $action
     * @param string|array $options
     * @return string|null
     */
    protected function o_viewerPreferences($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'viewerPreferences', 'info' => []];
                break;

            case 'add':
                $o = &$this->objects[$id];

                foreach ($options as $k => $v) {
                    switch ($k) {
                        // Boolean keys
                        case 'HideToolbar':
                        case 'HideMenubar':
                        case 'HideWindowUI':
                        case 'FitWindow':
                        case 'CenterWindow':
                        case 'DisplayDocTitle':
                        case 'PickTrayByPDFSize':
                            $o['info'][$k] = (bool)$v;
                            break;

                        // Integer keys
                        case 'NumCopies':
                            $o['info'][$k] = (int)$v;
                            break;

                        // Name keys
                        case 'ViewArea':
                        case 'ViewClip':
                        case 'PrintClip':
                        case 'PrintArea':
                            $o['info'][$k] = (string)$v;
                            break;

                        // Named with limited valid values
                        case 'NonFullScreenPageMode':
                            if (!in_array($v, ['UseNone', 'UseOutlines', 'UseThumbs', 'UseOC'])) {
                                break;
                            }
                            $o['info'][$k] = $v;
                            break;

                        case 'Direction':
                            if (!in_array($v, ['L2R', 'R2L'])) {
                                break;
                            }
                            $o['info'][$k] = $v;
                            break;

                        case 'PrintScaling':
                            if (!in_array($v, ['None', 'AppDefault'])) {
                                break;
                            }
                            $o['info'][$k] = $v;
                            break;

                        case 'Duplex':
                            if (!in_array($v, ['None', 'Simplex', 'DuplexFlipShortEdge', 'DuplexFlipLongEdge'])) {
                                break;
                            }
                            $o['info'][$k] = $v;
                            break;

                        // Integer array
                        case 'PrintPageRange':
                            // Cast to integer array
                            foreach ($v as $vK => $vV) {
                                $v[$vK] = (int)$vV;
                            }
                            $o['info'][$k] = array_values($v);
                            break;
                    }
                }
                break;

            case 'out':
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< ";

                foreach ($o['info'] as $k => $v) {
                    if (is_string($v)) {
                        $v = '/' . $v;
                    } elseif (is_int($v)) {
                        $v = (string) $v;
                    } elseif (is_bool($v)) {
                        $v = ($v ? 'true' : 'false');
                    } elseif (is_array($v)) {
                        $v = '[' . implode(' ', $v) . ']';
                    }
                    $res .= "\n/$k $v";
                }
                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * define the document catalog, the overall controller for the document
     *
     * @param $id
     * @param $action
     * @param string|array $options
     * @return string|null
     */
    protected function o_catalog($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'catalog', 'info' => []];
                $this->catalogId = $id;
                break;

            case 'acroform':
            case 'outlines':
            case 'pages':
            case 'openHere':
            case 'names':
                $o['info'][$action] = $options;
                break;

            case 'viewerPreferences':
                if (!isset($o['info']['viewerPreferences'])) {
                    $this->numObj++;
                    $this->o_viewerPreferences($this->numObj, 'new');
                    $o['info']['viewerPreferences'] = $this->numObj;
                }

                $vp = $o['info']['viewerPreferences'];
                $this->o_viewerPreferences($vp, 'add', $options);

                break;

            case 'outputIntents':
                if (!isset($o['info']['outputIntents'])) {
                    $o['info']['outputIntents'] = [];
                }

                $this->numObj++;
                $this->o_contents($this->numObj, 'new');
                $this->objects[$this->numObj]['c'] = $options['iccProfileData'];
                $this->o_contents($this->numObj, 'add', [
                    'N' => $options['colorComponentsCount'],
                ]);

                $o['info']['outputIntents'][] = [
                    'iccProfileName' => $options['iccProfileName'],
                    'destOutputProfile' => $this->numObj,
                ];

                break;

            case 'metadata':
                $this->numObj++;

                $o['info']['metadata'] = $this->numObj;

                $this->o_contents($this->numObj, 'new');
                $this->objects[$this->numObj]['c'] = $options;
                $this->o_contents($this->numObj, 'add', [
                    'Type' => '/Metadata',
                    'Subtype' => '/XML',
                ]);

                break;

            case 'out':
                $res = "\n$id 0 obj\n<< /Type /Catalog";

                foreach ($o['info'] as $k => $v) {
                    switch ($k) {
                        case 'outlines':
                            $res .= "\n/Outlines $v 0 R";
                            break;

                        case 'pages':
                            $res .= "\n/Pages $v 0 R";
                            break;

                        case 'viewerPreferences':
                            $res .= "\n/ViewerPreferences $v 0 R";
                            break;

                        case 'openHere':
                            $res .= "\n/OpenAction $v 0 R";
                            break;

                        case 'names':
                            $res .= "\n/Names $v 0 R";
                            break;

                        case 'acroform':
                            $res .= "\n/AcroForm $v 0 R";
                            break;

                        case 'metadata':
                            $res .= "\n/Metadata $v 0 R";
                            break;

                        case 'outputIntents':
                            $res .= "\n/OutputIntents [";
                            foreach ($v as $intent) {
                                $res .= "\n<< /Type /OutputIntent /S /GTS_PDFA1 ";
                                $res .= "/OutputConditionIdentifier (" . $intent['iccProfileName'] . ") /Info (" . $intent['iccProfileName'] . ") ";
                                $res .= "/DestOutputProfile " . $intent['destOutputProfile'] . " 0 R >>";
                            }
                            $res .= "]";
                            break;
                    }
                }

                $res .= " >>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * object which is a parent to the pages in the document
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return string|null
     */
    protected function o_pages($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'pages', 'info' => []];
                $this->o_catalog($this->catalogId, 'pages', $id);
                break;

            case 'page':
                if (!is_array($options)) {
                    // then it will just be the id of the new page
                    $o['info']['pages'][] = $options;
                } else {
                    // then it should be an array having 'id','rid','pos', where rid=the page to which this one will be placed relative
                    // and pos is either 'before' or 'after', saying where this page will fit.
                    if (isset($options['id']) && isset($options['rid']) && isset($options['pos'])) {
                        $i = array_search($options['rid'], $o['info']['pages']);
                        if (isset($o['info']['pages'][$i]) && $o['info']['pages'][$i] == $options['rid']) {

                            // then there is a match
                            // make a space
                            switch ($options['pos']) {
                                case 'before':
                                    $k = $i;
                                    break;

                                case 'after':
                                    $k = $i + 1;
                                    break;

                                default:
                                    $k = -1;
                                    break;
                            }

                            if ($k >= 0) {
                                for ($j = count($o['info']['pages']) - 1; $j >= $k; $j--) {
                                    $o['info']['pages'][$j + 1] = $o['info']['pages'][$j];
                                }

                                $o['info']['pages'][$k] = $options['id'];
                            }
                        }
                    }
                }
                break;

            case 'procset':
                $o['info']['procset'] = $options;
                break;

            case 'mediaBox':
                $o['info']['mediaBox'] = $options;
                // which should be an array of 4 numbers
                $this->currentPageSize = ['width' => $options[2], 'height' => $options[3]];
                break;

            case 'font':
                $o['info']['fonts'][] = ['objNum' => $options['objNum'], 'fontNum' => $options['fontNum']];
                break;

            case 'extGState':
                $o['info']['extGStates'][] = ['objNum' => $options['objNum'], 'stateNum' => $options['stateNum']];
                break;

            case 'xObject':
                $o['info']['xObjects'][] = ['objNum' => $options['objNum'], 'label' => $options['label']];
                break;

            case 'out':
                if (count($o['info']['pages'])) {
                    $res = "\n$id 0 obj\n<< /Type /Pages\n/Kids [";
                    foreach ($o['info']['pages'] as $v) {
                        $res .= "$v 0 R\n";
                    }

                    $res .= "]\n/Count " . count($this->objects[$id]['info']['pages']);

                    if ((isset($o['info']['fonts']) && count($o['info']['fonts'])) ||
                        isset($o['info']['procset']) ||
                        (isset($o['info']['extGStates']) && count($o['info']['extGStates']))
                    ) {
                        $res .= "\n/Resources <<";

                        if (isset($o['info']['procset'])) {
                            $res .= "\n/ProcSet " . $o['info']['procset'] . " 0 R";
                        }

                        if (isset($o['info']['fonts']) && count($o['info']['fonts'])) {
                            $res .= "\n/Font << ";
                            foreach ($o['info']['fonts'] as $finfo) {
                                $res .= "\n/F" . $finfo['fontNum'] . " " . $finfo['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }

                        if (isset($o['info']['xObjects']) && count($o['info']['xObjects'])) {
                            $res .= "\n/XObject << ";
                            foreach ($o['info']['xObjects'] as $finfo) {
                                $res .= "\n/" . $finfo['label'] . " " . $finfo['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }

                        if (isset($o['info']['extGStates']) && count($o['info']['extGStates'])) {
                            $res .= "\n/ExtGState << ";
                            foreach ($o['info']['extGStates'] as $gstate) {
                                $res .= "\n/GS" . $gstate['stateNum'] . " " . $gstate['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }

                        $res .= "\n>>";
                        if (isset($o['info']['mediaBox'])) {
                            $tmp = $o['info']['mediaBox'];
                            $res .= "\n/MediaBox [" . sprintf(
                                    '%.3F %.3F %.3F %.3F',
                                    $tmp[0],
                                    $tmp[1],
                                    $tmp[2],
                                    $tmp[3]
                                ) . ']';
                        }
                    }

                    $res .= "\n >>\nendobj";
                } else {
                    $res = "\n$id 0 obj\n<< /Type /Pages\n/Count 0\n>>\nendobj";
                }

                return $res;
        }

        return null;
    }

    /**
     * define the outlines in the doc, empty for now
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return string|null
     */
    protected function o_outlines($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'outlines', 'info' => ['outlines' => []]];
                $this->o_catalog($this->catalogId, 'outlines', $id);
                break;

            case 'outline':
                $o['info']['outlines'][] = $options;
                break;

            case 'out':
                if (count($o['info']['outlines'])) {
                    $res = "\n$id 0 obj\n<< /Type /Outlines /Kids [";
                    foreach ($o['info']['outlines'] as $v) {
                        $res .= "$v 0 R ";
                    }

                    $res .= "] /Count " . count($o['info']['outlines']) . " >>\nendobj";
                } else {
                    $res = "\n$id 0 obj\n<< /Type /Outlines /Count 0 >>\nendobj";
                }

                return $res;
        }

        return null;
    }

    /**
     * an object to hold the font description
     *
     * @param $id
     * @param $action
     * @param string|array $options
     * @return string|null
     * @throws FontNotFoundException
     */
    protected function o_font($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = [
                    't'    => 'font',
                    'info' => [
                        'name'         => $options['name'],
                        'fontFileName' => $options['fontFileName'],
                        'SubType'      => 'Type1',
                        'isSubsetting'   => $options['isSubsetting']
                    ]
                ];
                $fontNum = $this->numFonts;
                $this->objects[$id]['info']['fontNum'] = $fontNum;

                // deal with the encoding and the differences
                if (isset($options['differences'])) {
                    // then we'll need an encoding dictionary
                    $this->numObj++;
                    $this->o_fontEncoding($this->numObj, 'new', $options);
                    $this->objects[$id]['info']['encodingDictionary'] = $this->numObj;
                } else {
                    if (isset($options['encoding'])) {
                        // we can specify encoding here
                        switch ($options['encoding']) {
                            case 'WinAnsiEncoding':
                            case 'MacRomanEncoding':
                            case 'MacExpertEncoding':
                                $this->objects[$id]['info']['encoding'] = $options['encoding'];
                                break;

                            case 'none':
                                break;

                            default:
                                $this->objects[$id]['info']['encoding'] = 'WinAnsiEncoding';
                                break;
                        }
                    } else {
                        $this->objects[$id]['info']['encoding'] = 'WinAnsiEncoding';
                    }
                }

                if ($this->fonts[$options['fontFileName']]['isUnicode']) {
                    // For Unicode fonts, we need to incorporate font data into
                    // sub-sections that are linked from the primary font section.
                    // Look at o_fontGIDtoCID and o_fontDescendentCID functions
                    // for more information.
                    //
                    // All of this code is adapted from the excellent changes made to
                    // transform FPDF to TCPDF (http://tcpdf.sourceforge.net/)

                    $toUnicodeId = ++$this->numObj;
                    $this->o_toUnicode($toUnicodeId, 'new');
                    $this->objects[$id]['info']['toUnicode'] = $toUnicodeId;

                    $cidFontId = ++$this->numObj;
                    $this->o_fontDescendentCID($cidFontId, 'new', $options);
                    $this->objects[$id]['info']['cidFont'] = $cidFontId;
                }

                // also tell the pages node about the new font
                $this->o_pages($this->currentNode, 'font', ['fontNum' => $fontNum, 'objNum' => $id]);
                break;

            case 'add':
                $font_options = $this->processFont($id, $o['info']);

                if ($font_options !== false) {
                    foreach ($font_options as $k => $v) {
                        switch ($k) {
                            case 'BaseFont':
                                $o['info']['name'] = $v;
                                break;
                            case 'FirstChar':
                            case 'LastChar':
                            case 'Widths':
                            case 'FontDescriptor':
                            case 'SubType':
                                $this->addMessage('o_font ' . $k . " : " . $v);
                                $o['info'][$k] = $v;
                                break;
                        }
                    }

                    // pass values down to descendent font
                    if (isset($o['info']['cidFont'])) {
                        $this->o_fontDescendentCID($o['info']['cidFont'], 'add', $font_options);
                    }
                }
                break;

            case 'out':
                if ($this->fonts[$this->objects[$id]['info']['fontFileName']]['isUnicode']) {
                    // For Unicode fonts, we need to incorporate font data into
                    // sub-sections that are linked from the primary font section.
                    // Look at o_fontGIDtoCID and o_fontDescendentCID functions
                    // for more information.
                    //
                    // All of this code is adapted from the excellent changes made to
                    // transform FPDF to TCPDF (http://tcpdf.sourceforge.net/)

                    $res = "\n$id 0 obj\n<</Type /Font\n/Subtype /Type0\n";
                    $res .= "/BaseFont /" . $o['info']['name'] . "\n";

                    // The horizontal identity mapping for 2-byte CIDs; may be used
                    // with CIDFonts using any Registry, Ordering, and Supplement values.
                    $res .= "/Encoding /Identity-H\n";
                    $res .= "/DescendantFonts [" . $o['info']['cidFont'] . " 0 R]\n";
                    $res .= "/ToUnicode " . $o['info']['toUnicode'] . " 0 R\n";
                    $res .= ">>\n";
                    $res .= "endobj";
                } else {
                    $res = "\n$id 0 obj\n<< /Type /Font\n/Subtype /" . $o['info']['SubType'] . "\n";
                    $res .= "/Name /F" . $o['info']['fontNum'] . "\n";
                    $res .= "/BaseFont /" . $o['info']['name'] . "\n";

                    if (isset($o['info']['encodingDictionary'])) {
                        // then place a reference to the dictionary
                        $res .= "/Encoding " . $o['info']['encodingDictionary'] . " 0 R\n";
                    } else {
                        if (isset($o['info']['encoding'])) {
                            // use the specified encoding
                            $res .= "/Encoding /" . $o['info']['encoding'] . "\n";
                        }
                    }

                    if (isset($o['info']['FirstChar'])) {
                        $res .= "/FirstChar " . $o['info']['FirstChar'] . "\n";
                    }

                    if (isset($o['info']['LastChar'])) {
                        $res .= "/LastChar " . $o['info']['LastChar'] . "\n";
                    }

                    if (isset($o['info']['Widths'])) {
                        $res .= "/Widths " . $o['info']['Widths'] . " 0 R\n";
                    }

                    if (isset($o['info']['FontDescriptor'])) {
                        $res .= "/FontDescriptor " . $o['info']['FontDescriptor'] . " 0 R\n";
                    }

                    $res .= ">>\n";
                    $res .= "endobj";
                }

                return $res;
        }

        return null;
    }

    protected function getFontSubsettingTag(array $font): string
    {
        // convert font num to hexavigesimal numeral system letters A - Z only
        $base_26 = strtoupper(base_convert($font['fontNum'], 10, 26));
        for ($i = 0; $i < strlen($base_26); $i++) {
            $char = $base_26[$i];
            if ($char <= "9") {
                $base_26[$i] = chr(65 + intval($char));
            } else {
                $base_26[$i] = chr(ord($char) + 10);
            }
        }

        return 'SUB' . str_pad($base_26, 3, 'A', STR_PAD_LEFT);
    }

    /**
     * @param int $fontObjId
     * @param array $object_info
     * @return array|false
     * @throws FontNotFoundException
     * @throws Exception
     */
    private function processFont(int $fontObjId, array $object_info)
    {
        $fontFileName = $object_info['fontFileName'];
        if (!isset($this->fonts[$fontFileName])) {
            return false;
        }

        $font = &$this->fonts[$fontFileName];

        $fileSuffix = $font['fileSuffix'];
        $fileSuffixLower = strtolower($font['fileSuffix']);
        $fbfile = "$fontFileName.$fileSuffix";
        $isTtfFont = $fileSuffixLower === 'ttf';
        $isPfbFont = $fileSuffixLower === 'pfb';

        $this->addMessage('selectFont: checking for - ' . $fbfile);

        if ($this->pdfa && !file_exists($fbfile)) {
            throw new \Exception("A fully embeddable font must be used when generating a document in PDF/A mode");
        } elseif (!$fileSuffix) {
            $this->addMessage(
                'selectFont: pfb or ttf file not found, ok if this is one of the 14 standard fonts'
            );

            return false;
        } else {
            $adobeFontName = isset($font['PostScriptName']) ? $font['PostScriptName'] : $font['FontName'];
            //        $fontObj = $this->numObj;
            $this->addMessage("selectFont: adding font file - $fbfile - $adobeFontName");

            // find the array of font widths, and put that into an object.
            $firstChar = -1;
            $lastChar = 0;
            $widths = [];
            $cid_widths = [];

            foreach ($font['C'] as $num => $d) {
                if (intval($num) > 0 || $num == '0') {
                    if (!$font['isUnicode']) {
                        // With Unicode, widths array isn't used
                        if ($lastChar > 0 && $num > $lastChar + 1) {
                            for ($i = $lastChar + 1; $i < $num; $i++) {
                                $widths[] = 0;
                            }
                        }
                    }

                    $widths[] = $d;

                    if ($font['isUnicode']) {
                        $cid_widths[$num] = $d;
                    }

                    if ($firstChar == -1) {
                        $firstChar = $num;
                    }

                    $lastChar = $num;
                }
            }

            // also need to adjust the widths for the differences array
            if (isset($object['differences'])) {
                foreach ($object['differences'] as $charNum => $charName) {
                    if ($charNum > $lastChar) {
                        if (!$object['isUnicode']) {
                            // With Unicode, widths array isn't used
                            for ($i = $lastChar + 1; $i <= $charNum; $i++) {
                                $widths[] = 0;
                            }
                        }

                        $lastChar = $charNum;
                    }

                    if (isset($font['C'][$charName])) {
                        $widths[$charNum - $firstChar] = $font['C'][$charName];
                        if ($font['isUnicode']) {
                            $cid_widths[$charName] = $font['C'][$charName];
                        }
                    }
                }
            }

            if ($font['isUnicode']) {
                $font['CIDWidths'] = $cid_widths;
            }

            $this->addMessage('selectFont: FirstChar = ' . $firstChar);
            $this->addMessage('selectFont: LastChar = ' . $lastChar);

            $widthid = -1;

            if (!$font['isUnicode']) {
                // With Unicode, widths array isn't used

                $this->numObj++;
                $this->o_contents($this->numObj, 'new', 'raw');
                $this->objects[$this->numObj]['c'] .= '[' . implode(' ', $widths) . ']';
                $widthid = $this->numObj;
            }

            $missing_width = 500;
            $stemV = 70;

            if (isset($font['MissingWidth'])) {
                $missing_width = $font['MissingWidth'];
            } elseif (isset($font['IsFixedPitch']) && strtolower($font['IsFixedPitch']) === "true" && isset($font['C'][32])) {
                $missing_width = $font['C'][32];
            }

            if (isset($font['StdVW'])) {
                $stemV = $font['StdVW'];
            } else {
                if (isset($font['Weight']) && preg_match('!(bold|black)!i', $font['Weight'])) {
                    $stemV = 120;
                }
            }

            // load the pfb file, and put that into an object too.
            // note that pdf supports only binary format type 1 font files, though there is a
            // simple utility to convert them from pfa to pfb.
            if (!$font['isSubsetting']) {
                $data = file_get_contents($fbfile);
            } else {
                $adobeFontName = $this->getFontSubsettingTag($font) . '+' . $adobeFontName;
                $this->stringSubsets[$fontFileName][] = 32; // Force space if not in yet

                $subset = $this->stringSubsets[$fontFileName];
                sort($subset);

                // Load font
                $font_obj = Font::load($fbfile);
                $font_obj->parse();

                // Define subset
                $font_obj->setSubset($subset);
                $font_obj->reduce();

                // Write new font
                $tmp_name = @tempnam($this->tmp, "cpdf_subset_");
                $font_obj->open($tmp_name, BinaryStream::modeReadWrite);
                $font_obj->encode(["OS/2"]);
                $font_obj->close();

                // Parse the new font to get cid2gid and widths
                $font_obj = Font::load($tmp_name);

                // Find Unicode char map table
                $subtable = null;
                foreach ($font_obj->getData("cmap", "subtables") as $_subtable) {
                    if ($_subtable["platformID"] == 0 || $_subtable["platformID"] == 3 && $_subtable["platformSpecificID"] == 1) {
                        $subtable = $_subtable;
                        break;
                    }
                }

                if ($subtable) {
                    $glyphIndexArray = $subtable["glyphIndexArray"];
                    $hmtx = $font_obj->getData("hmtx");

                    unset($glyphIndexArray[0xFFFF]);

                    $cidtogid = str_pad('', max(array_keys($glyphIndexArray)) * 2 + 1, "\x00");
                    $font['CIDWidths'] = [];
                    foreach ($glyphIndexArray as $cid => $gid) {
                        if ($cid >= 0 && $cid < 0xFFFF && $gid) {
                            $cidtogid[$cid * 2] = chr($gid >> 8);
                            $cidtogid[$cid * 2 + 1] = chr($gid & 0xFF);
                        }

                        $width = $font_obj->normalizeFUnit(isset($hmtx[$gid]) ? $hmtx[$gid][0] : $hmtx[0][0]);
                        $font['CIDWidths'][$cid] = $width;
                    }

                    $font['CIDtoGID'] = base64_encode(gzcompress($cidtogid));
                    $font['CIDtoGID_Compressed'] = true;

                    $data = file_get_contents($tmp_name);
                } else {
                    $data = file_get_contents($fbfile);
                }

                $font_obj->close();
                unlink($tmp_name);
            }

            // create the font descriptor
            $this->numObj++;
            $fontDescriptorId = $this->numObj;

            $this->numObj++;
            $pfbid = $this->numObj;

            // determine flags (more than a little flakey, hopefully will not matter much)
            $flags = 0;

            if ($font['ItalicAngle'] != 0) {
                $flags += pow(2, 6);
            }

            if ($font['IsFixedPitch'] === 'true') {
                $flags += 1;
            }

            $flags += pow(2, 5); // assume non-sybolic
            $list = [
                'Ascent'       => 'Ascender',
                'CapHeight'    => 'CapHeight',
                'MissingWidth' => 'MissingWidth',
                'Descent'      => 'Descender',
                'FontBBox'     => 'FontBBox',
                'ItalicAngle'  => 'ItalicAngle'
            ];
            $fdopt = [
                'Flags'    => $flags,
                'FontName' => $adobeFontName,
                'StemV'    => $stemV
            ];

            foreach ($list as $k => $v) {
                if (isset($font[$v])) {
                    $fdopt[$k] = $font[$v];
                }
            }
            if (!isset($fdopt['CapHeight']) && isset($fdopt['Ascender'])) {
                $fdopt['CapHeight'] = $fdopt['Ascender'];
            }

            if ($isPfbFont) {
                $fdopt['FontFile'] = $pfbid;
            } elseif ($isTtfFont) {
                $fdopt['FontFile2'] = $pfbid;
            }

            $this->o_fontDescriptor($fontDescriptorId, 'new', $fdopt);

            // embed the font program
            $this->o_contents($this->numObj, 'new');
            $this->objects[$pfbid]['c'] .= $data;

            // determine the cruicial lengths within this file
            if ($isPfbFont) {
                $l1 = strpos($data, 'eexec') + 6;
                $l2 = strpos($data, '00000000') - $l1;
                $l3 = mb_strlen($data, '8bit') - $l2 - $l1;
                $this->o_contents(
                    $this->numObj,
                    'add',
                    ['Length1' => $l1, 'Length2' => $l2, 'Length3' => $l3]
                );
            } elseif ($isTtfFont) {
                $l1 = mb_strlen($data, '8bit');
                $this->o_contents($this->numObj, 'add', ['Length1' => $l1]);
            }

            // tell the font object about all this new stuff
            $options = [
                'BaseFont'       => $adobeFontName,
                'MissingWidth'   => $missing_width,
                'Widths'         => $widthid,
                'FirstChar'      => $firstChar,
                'LastChar'       => $lastChar,
                'FontDescriptor' => $fontDescriptorId
            ];

            if ($isTtfFont) {
                $options['SubType'] = 'TrueType';
            }

            $this->addMessage("adding extra info to font.($fontObjId)");

            foreach ($options as $fk => $fv) {
                $this->addMessage("$fk : $fv");
            }
        }

        return $options;
    }

    /**
     * A toUnicode section, needed for unicode fonts
     *
     * @param $id
     * @param $action
     * @return null|string
     */
    protected function o_toUnicode($id, $action)
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = [
                    't'    => 'toUnicode'
                ];
                break;
            case 'add':
                break;
            case 'out':
                $ordering = 'UCS';
                $registry = 'Adobe';

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $ordering = $this->filterText($this->ARC4($ordering), false, false);
                    $registry = $this->filterText($this->ARC4($registry), false, false);
                }

                $stream = <<<EOT
/CIDInit /ProcSet findresource begin
12 dict begin
begincmap
/CIDSystemInfo
<</Registry ($registry)
/Ordering ($ordering)
/Supplement 0
>> def
/CMapName /Adobe-Identity-UCS def
/CMapType 2 def
1 begincodespacerange
<0000> <FFFF>
endcodespacerange
1 beginbfrange
<0000> <FFFF> <0000>
endbfrange
endcmap
CMapName currentdict /CMap defineresource pop
end
end
EOT;

                $res = "\n$id 0 obj\n";
                $res .= "<</Length " . mb_strlen($stream, '8bit') . " >>\n";
                $res .= "stream\n" . $stream . "\nendstream" . "\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * a font descriptor, needed for including additional fonts
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     */
    protected function o_fontDescriptor($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'fontDescriptor', 'info' => $options];
                break;

            case 'out':
                $res = "\n$id 0 obj\n<< /Type /FontDescriptor\n";
                foreach ($o['info'] as $label => $value) {
                    switch ($label) {
                        case 'Ascent':
                        case 'CapHeight':
                        case 'Descent':
                        case 'Flags':
                        case 'ItalicAngle':
                        case 'StemV':
                        case 'AvgWidth':
                        case 'Leading':
                        case 'MaxWidth':
                        case 'MissingWidth':
                        case 'StemH':
                        case 'XHeight':
                        case 'CharSet':
                            if (mb_strlen($value, '8bit')) {
                                $res .= "/$label $value\n";
                            }

                            break;
                        case 'FontFile':
                        case 'FontFile2':
                        case 'FontFile3':
                            $res .= "/$label $value 0 R\n";
                            break;

                        case 'FontBBox':
                            $res .= "/$label [$value[0] $value[1] $value[2] $value[3]]\n";
                            break;

                        case 'FontName':
                            $res .= "/$label /$value\n";
                            break;
                    }
                }

                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * the font encoding
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     */
    protected function o_fontEncoding($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                // the options array should contain 'differences' and maybe 'encoding'
                $this->objects[$id] = ['t' => 'fontEncoding', 'info' => $options];
                break;

            case 'out':
                $res = "\n$id 0 obj\n<< /Type /Encoding\n";
                if (!isset($o['info']['encoding'])) {
                    $o['info']['encoding'] = 'WinAnsiEncoding';
                }

                if ($o['info']['encoding'] !== 'none') {
                    $res .= "/BaseEncoding /" . $o['info']['encoding'] . "\n";
                }

                $res .= "/Differences \n[";

                $onum = -100;

                foreach ($o['info']['differences'] as $num => $label) {
                    if ($num != $onum + 1) {
                        // we cannot make use of consecutive numbering
                        $res .= "\n$num /$label";
                    } else {
                        $res .= " /$label";
                    }

                    $onum = $num;
                }

                $res .= "\n]\n>>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * a descendent cid font, needed for unicode fonts
     *
     * @param $id
     * @param $action
     * @param string|array $options
     * @return null|string
     */
    protected function o_fontDescendentCID($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'fontDescendentCID', 'info' => $options];

                // we need a CID system info section
                $cidSystemInfoId = ++$this->numObj;
                $this->o_cidSystemInfo($cidSystemInfoId, 'new');
                $this->objects[$id]['info']['cidSystemInfo'] = $cidSystemInfoId;

                // and a CID to GID map
                $cidToGidMapId = ++$this->numObj;
                $this->o_fontGIDtoCIDMap($cidToGidMapId, 'new', $options);
                $this->objects[$id]['info']['cidToGidMap'] = $cidToGidMapId;
                break;

            case 'add':
                foreach ($options as $k => $v) {
                    switch ($k) {
                        case 'BaseFont':
                            $o['info']['name'] = $v;
                            break;

                        case 'FirstChar':
                        case 'LastChar':
                        case 'MissingWidth':
                        case 'FontDescriptor':
                        case 'SubType':
                            $this->addMessage("o_fontDescendentCID $k : $v");
                            $o['info'][$k] = $v;
                            break;
                    }
                }

                // pass values down to cid to gid map
                $this->o_fontGIDtoCIDMap($o['info']['cidToGidMap'], 'add', $options);
                break;

            case 'out':
                $res = "\n$id 0 obj\n";
                $res .= "<</Type /Font\n";
                $res .= "/Subtype /CIDFontType2\n";
                $res .= "/BaseFont /" . $o['info']['name'] . "\n";
                $res .= "/CIDSystemInfo " . $o['info']['cidSystemInfo'] . " 0 R\n";
                //      if (isset($o['info']['FirstChar'])) {
                //        $res.= "/FirstChar ".$o['info']['FirstChar']."\n";
                //      }

                //      if (isset($o['info']['LastChar'])) {
                //        $res.= "/LastChar ".$o['info']['LastChar']."\n";
                //      }
                if (isset($o['info']['FontDescriptor'])) {
                    $res .= "/FontDescriptor " . $o['info']['FontDescriptor'] . " 0 R\n";
                }

                if (isset($o['info']['MissingWidth'])) {
                    $res .= "/DW " . $o['info']['MissingWidth'] . "\n";
                }

                if (isset($o['info']['fontFileName']) && isset($this->fonts[$o['info']['fontFileName']]['CIDWidths'])) {
                    $cid_widths = &$this->fonts[$o['info']['fontFileName']]['CIDWidths'];
                    $w = '';
                    foreach ($cid_widths as $cid => $width) {
                        $w .= "$cid [$width] ";
                    }
                    $res .= "/W [$w]\n";
                }

                $res .= "/CIDToGIDMap " . $o['info']['cidToGidMap'] . " 0 R\n";
                $res .= ">>\n";
                $res .= "endobj";

                return $res;
        }

        return null;
    }

    /**
     * CID system info section, needed for unicode fonts
     *
     * @param $id
     * @param $action
     * @return null|string
     */
    protected function o_cidSystemInfo($id, $action)
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = [
                    't' => 'cidSystemInfo'
                ];
                break;
            case 'add':
                break;
            case 'out':
                $ordering = 'UCS';
                $registry = 'Adobe';

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $ordering = $this->filterText($this->ARC4($ordering), false, false);
                    $registry = $this->filterText($this->ARC4($registry), false, false);
                }


                $res = "\n$id 0 obj\n";

                $res .= '<</Registry (' . $registry . ")\n"; // A string identifying an issuer of character collections
                $res .= '/Ordering (' . $ordering . ")\n"; // A string that uniquely names a character collection issued by a specific registry
                $res .= "/Supplement 0\n"; // The supplement number of the character collection.
                $res .= ">>";

                $res .= "\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * a font glyph to character map, needed for unicode fonts
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     */
    protected function o_fontGIDtoCIDMap($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'fontGIDtoCIDMap', 'info' => $options];
                break;

            case 'out':
                $res = "\n$id 0 obj\n";
                $fontFileName = $o['info']['fontFileName'];
                $tmp = $this->fonts[$fontFileName]['CIDtoGID'] = base64_decode($this->fonts[$fontFileName]['CIDtoGID']);

                $compressed = isset($this->fonts[$fontFileName]['CIDtoGID_Compressed']) &&
                    $this->fonts[$fontFileName]['CIDtoGID_Compressed'];

                if (!$compressed && isset($o['raw'])) {
                    $res .= $tmp;
                } else {
                    $res .= "<<";

                    if (!$compressed && $this->compressionReady && $this->options['compression']) {
                        // then implement ZLIB based compression on this content stream
                        $compressed = true;
                        $tmp = gzcompress($tmp, 6);
                    }
                    if ($compressed) {
                        $res .= "\n/Filter /FlateDecode";
                    }

                    if ($this->encrypted) {
                        $this->encryptInit($id);
                        $tmp = $this->ARC4($tmp);
                    }

                    $res .= "\n/Length " . mb_strlen($tmp, '8bit') . ">>\nstream\n$tmp\nendstream";
                }

                $res .= "\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * the document procset, solves some problems with printing to old PS printers
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     */
    protected function o_procset($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'procset', 'info' => ['PDF' => 1, 'Text' => 1]];
                $this->o_pages($this->currentNode, 'procset', $id);
                $this->procsetObjectId = $id;
                break;

            case 'add':
                // this is to add new items to the procset list, despite the fact that this is considered
                // obsolete, the items are required for printing to some postscript printers
                switch ($options) {
                    case 'ImageB':
                    case 'ImageC':
                    case 'ImageI':
                        $o['info'][$options] = 1;
                        break;
                }
                break;

            case 'out':
                $res = "\n$id 0 obj\n[";
                foreach ($o['info'] as $label => $val) {
                    $res .= "/$label ";
                }
                $res .= "]\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * define the document information
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     */
    protected function o_info($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                $this->infoObject = $id;
                $date = 'D:' . @date('Ymd');
                $this->objects[$id] = [
                    't'    => 'info',
                    'info' => [
                        'Producer'     => 'CPDF (dompdf)',
                        'CreationDate' => $date
                    ]
                ];
                break;
            case 'Title':
            case 'Author':
            case 'Subject':
            case 'Keywords':
            case 'Creator':
            case 'Producer':
            case 'CreationDate':
            case 'ModDate':
            case 'Trapped':
                $this->objects[$id]['info'][$action] = $options;
                break;

            case 'out':
                $encrypted = $this->encrypted;
                if ($encrypted) {
                    $this->encryptInit($id);
                }

                $res = "\n$id 0 obj\n<<\n";
                $o = &$this->objects[$id];
                foreach ($o['info'] as $k => $v) {
                    $res .= "/$k (";

                    // dates must be outputted as-is, without Unicode transformations
                    if ($k !== 'CreationDate' && $k !== 'ModDate') {
                        $v = $this->utf8toUtf16BE($v);
                    }

                    if ($encrypted) {
                        $v = $this->ARC4($v);
                    }

                    $res .= $this->filterText($v, false, false);
                    $res .= ")\n";
                }

                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * an action object, used to link to URLS initially
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     */
    protected function o_action($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                if (is_array($options)) {
                    $this->objects[$id] = ['t' => 'action', 'info' => $options, 'type' => $options['type']];
                } else {
                    // then assume a URI action
                    $this->objects[$id] = ['t' => 'action', 'info' => $options, 'type' => 'URI'];
                }
                break;

            case 'out':
                if ($this->encrypted) {
                    $this->encryptInit($id);
                }

                $res = "\n$id 0 obj\n<< /Type /Action";
                switch ($o['type']) {
                    case 'ilink':
                        if (!isset($this->destinations[(string)$o['info']['label']])) {
                            break;
                        }

                        // there will be an 'label' setting, this is the name of the destination
                        $res .= "\n/S /GoTo\n/D " . $this->destinations[(string)$o['info']['label']] . " 0 R";
                        break;

                    case 'URI':
                        $res .= "\n/S /URI\n/URI (";
                        if ($this->encrypted) {
                            $res .= $this->filterText($this->ARC4($o['info']), false, false);
                        } else {
                            $res .= $this->filterText($o['info'], false, false);
                        }

                        $res .= ")";
                        break;
                }

                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * an annotation object, this will add an annotation to the current page.
     * initially will support just link annotations
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     */
    protected function o_annotation($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                // add the annotation to the current page
                $pageId = $this->currentPage;
                $this->o_page($pageId, 'annot', $id);

                // and add the action object which is going to be required
                switch ($options['type']) {
                    case 'link':
                        $this->objects[$id] = ['t' => 'annotation', 'info' => $options];
                        $this->numObj++;
                        $this->o_action($this->numObj, 'new', $options['url']);
                        $this->objects[$id]['info']['actionId'] = $this->numObj;
                        break;

                    case 'ilink':
                        // this is to a named internal link
                        $label = $options['label'];
                        $this->objects[$id] = ['t' => 'annotation', 'info' => $options];
                        $this->numObj++;
                        $this->o_action($this->numObj, 'new', ['type' => 'ilink', 'label' => $label]);
                        $this->objects[$id]['info']['actionId'] = $this->numObj;
                        break;
                }
                break;

            case 'out':
                $res = "\n$id 0 obj\n<< /Type /Annot";
                switch ($o['info']['type']) {
                    case 'link':
                    case 'ilink':
                        $res .= "\n/Subtype /Link";
                        break;
                }
                $res .= "\n/F 28";
                $res .= "\n/A " . $o['info']['actionId'] . " 0 R";
                $res .= "\n/Border [0 0 0]";
                $res .= "\n/H /I";
                $res .= "\n/Rect [ ";

                foreach ($o['info']['rect'] as $v) {
                    $res .= sprintf("%.4F ", $v);
                }

                $res .= "]";
                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * a page object, it also creates a contents object to hold its contents
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     */
    protected function o_page($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->numPages++;
                $this->objects[$id] = [
                    't'    => 'page',
                    'info' => [
                        'parent'  => $this->currentNode,
                        'pageNum' => $this->numPages,
                        'mediaBox' => $this->objects[$this->currentNode]['info']['mediaBox']
                    ]
                ];

                if (is_array($options)) {
                    // then this must be a page insertion, array should contain 'rid','pos'=[before|after]
                    $options['id'] = $id;
                    $this->o_pages($this->currentNode, 'page', $options);
                } else {
                    $this->o_pages($this->currentNode, 'page', $id);
                }

                $this->currentPage = $id;
                //make a contents object to go with this page
                $this->numObj++;
                $this->o_contents($this->numObj, 'new', $id);
                $this->currentContents = $this->numObj;
                $this->objects[$id]['info']['contents'] = [];
                $this->objects[$id]['info']['contents'][] = $this->numObj;

                $match = ($this->numPages % 2 ? 'odd' : 'even');
                foreach ($this->addLooseObjects as $oId => $target) {
                    if ($target === 'all' || $match === $target) {
                        $this->objects[$id]['info']['contents'][] = $oId;
                    }
                }
                break;

            case 'content':
                $o['info']['contents'][] = $options;
                break;

            case 'annot':
                // add an annotation to this page
                if (!isset($o['info']['annot'])) {
                    $o['info']['annot'] = [];
                }

                // $options should contain the id of the annotation dictionary
                $o['info']['annot'][] = $options;
                break;

            case 'out':
                $res = "\n$id 0 obj\n<< /Type /Page";
                if (isset($o['info']['mediaBox'])) {
                    $tmp = $o['info']['mediaBox'];
                    $res .= "\n/MediaBox [" . sprintf(
                            '%.3F %.3F %.3F %.3F',
                            $tmp[0],
                            $tmp[1],
                            $tmp[2],
                            $tmp[3]
                        ) . ']';
                }
                $res .= "\n/Parent " . $o['info']['parent'] . " 0 R";

                if (isset($o['info']['annot'])) {
                    $res .= "\n/Annots [";
                    foreach ($o['info']['annot'] as $aId) {
                        $res .= " $aId 0 R";
                    }
                    $res .= " ]";
                }

                $count = count($o['info']['contents']);
                if ($count == 1) {
                    $res .= "\n/Contents " . $o['info']['contents'][0] . " 0 R";
                } else {
                    if ($count > 1) {
                        $res .= "\n/Contents [\n";

                        // reverse the page contents so added objects are below normal content
                        //foreach (array_reverse($o['info']['contents']) as $cId) {
                        // Back to normal now that I've got transparency working --Benj
                        foreach ($o['info']['contents'] as $cId) {
                            $res .= "$cId 0 R\n";
                        }
                        $res .= "]";
                    }
                }

                // PDF/A does not allow inheriting Resources, we must explicitly define them on each page
                if ($this->pdfa) {
                    $pagesInfo = $this->objects[$this->currentNode]['info'];

                    if ((isset($pagesInfo['fonts']) && count($pagesInfo['fonts'])) ||
                        isset($pagesInfo['procset']) ||
                        (isset($pagesInfo['extGStates']) && count($pagesInfo['extGStates']))
                    ) {
                        $res .= "\n/Resources <<";
    
                        if (isset($pagesInfo['procset'])) {
                            $res .= "\n/ProcSet " . $pagesInfo['procset'] . " 0 R";
                        }
    
                        if (isset($pagesInfo['fonts']) && count($pagesInfo['fonts'])) {
                            $res .= "\n/Font << ";
                            foreach ($pagesInfo['fonts'] as $finfo) {
                                $res .= "\n/F" . $finfo['fontNum'] . " " . $finfo['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }
    
                        if (isset($pagesInfo['xObjects']) && count($pagesInfo['xObjects'])) {
                            $res .= "\n/XObject << ";
                            foreach ($pagesInfo['xObjects'] as $finfo) {
                                $res .= "\n/" . $finfo['label'] . " " . $finfo['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }
    
                        if (isset($pagesInfo['extGStates']) && count($pagesInfo['extGStates'])) {
                            $res .= "\n/ExtGState << ";
                            foreach ($pagesInfo['extGStates'] as $gstate) {
                                $res .= "\n/GS" . $gstate['stateNum'] . " " . $gstate['objNum'] . " 0 R";
                            }
                            $res .= "\n>>";
                        }
    
                        $res .= "\n>>";
                    }
                }

                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * the contents objects hold all of the content which appears on pages
     *
     * @param $id
     * @param $action
     * @param string|array $options
     * @return null|string
     */
    protected function o_contents($id, $action, $options = '')
    {
        if ($action !== 'new') {
            $o = &$this->objects[$id];
        }

        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'contents', 'c' => '', 'info' => []];
                if (mb_strlen($options, '8bit') && intval($options)) {
                    // then this contents is the primary for a page
                    $this->objects[$id]['onPage'] = $options;
                } else {
                    if ($options === 'raw') {
                        // then this page contains some other type of system object
                        $this->objects[$id]['raw'] = 1;
                    }
                }
                break;

            case 'add':
                // add more options to the declaration
                foreach ($options as $k => $v) {
                    $o['info'][$k] = $v;
                }

            case 'out':
                $tmp = $o['c'];
                $res = "\n$id 0 obj\n";

                if (isset($this->objects[$id]['raw'])) {
                    $res .= $tmp;
                } else {
                    $res .= "<<";
                    if ($this->compressionReady && $this->options['compression']) {
                        // then implement ZLIB based compression on this content stream
                        $res .= " /Filter /FlateDecode";
                        $tmp = gzcompress($tmp, 6);
                    }

                    if ($this->encrypted) {
                        $this->encryptInit($id);
                        $tmp = $this->ARC4($tmp);
                    }

                    foreach ($o['info'] as $k => $v) {
                        $res .= "\n/$k $v";
                    }

                    $res .= "\n/Length " . mb_strlen($tmp, '8bit') . " >>\nstream\n$tmp\nendstream";
                }

                $res .= "\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * @param $id
     * @param $action
     * @return string|null
     */
    protected function o_embedjs($id, $action)
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = [
                    't'    => 'embedjs',
                    'info' => [
                        'Names' => '[(EmbeddedJS) ' . ($id + 1) . ' 0 R]'
                    ]
                ];
                break;

            case 'out':
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< ";
                foreach ($o['info'] as $k => $v) {
                    $res .= "\n/$k $v";
                }
                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * @param $id
     * @param $action
     * @param string $code
     * @return null|string
     */
    protected function o_javascript($id, $action, $code = '')
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = [
                    't'    => 'javascript',
                    'info' => [
                        'S'  => '/JavaScript',
                        'JS' => '(' . $this->filterText($code, true, false) . ')',
                    ]
                ];
                break;

            case 'out':
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< ";

                foreach ($o['info'] as $k => $v) {
                    $res .= "\n/$k $v";
                }
                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * an image object, will be an XObject in the document, includes description and data
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     * @throws Exception
     */
    protected function o_image($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                // make the new object
                $this->objects[$id] = ['t' => 'image', 'data' => &$options['data'], 'info' => []];

                $info =& $this->objects[$id]['info'];

                $info['Type'] = '/XObject';
                $info['Subtype'] = '/Image';
                $info['Width'] = $options['iw'];
                $info['Height'] = $options['ih'];

                if (isset($options['masked']) && $options['masked']) {
                    $info['SMask'] = ($this->numObj - 1) . ' 0 R';
                }

                if (!isset($options['type']) || $options['type'] === 'jpg') {
                    if (!isset($options['channels'])) {
                        $options['channels'] = 3;
                    }

                    switch ($options['channels']) {
                        case 1:
                            $info['ColorSpace'] = '/DeviceGray';
                            break;
                        case 4:
                            $info['ColorSpace'] = '/DeviceCMYK';
                            break;
                        default:
                            $info['ColorSpace'] = '/DeviceRGB';
                            break;
                    }

                    if ($info['ColorSpace'] === '/DeviceCMYK') {
                        if ($this->pdfa) {
                            throw new \Exception("CMYK images are not supported when generating a document in PDF/A mode");
                        }
                        $info['Decode'] = '[1 0 1 0 1 0 1 0]';
                    }

                    $info['Filter'] = '/DCTDecode';
                    $info['BitsPerComponent'] = 8;
                } else {
                    if ($options['type'] === 'png') {
                        $info['Filter'] = '/FlateDecode';
                        $info['DecodeParms'] = '<< /Predictor 15 /Colors ' . $options['ncolor'] . ' /Columns ' . $options['iw'] . ' /BitsPerComponent ' . $options['bitsPerComponent'] . '>>';

                        if ($options['isMask']) {
                            $info['ColorSpace'] = '/DeviceGray';
                        } else {
                            if (mb_strlen($options['pdata'], '8bit')) {
                                $tmp = ' [ /Indexed /DeviceRGB ' . (mb_strlen($options['pdata'], '8bit') / 3 - 1) . ' ';
                                $this->numObj++;
                                $this->o_contents($this->numObj, 'new');
                                $this->objects[$this->numObj]['c'] = $options['pdata'];
                                $tmp .= $this->numObj . ' 0 R';
                                $tmp .= ' ]';
                                $info['ColorSpace'] = $tmp;

                                if (isset($options['transparency'])) {
                                    $transparency = $options['transparency'];
                                    switch ($transparency['type']) {
                                        case 'indexed':
                                            $tmp = ' [ ' . $transparency['data'] . ' ' . $transparency['data'] . '] ';
                                            $info['Mask'] = $tmp;
                                            break;

                                        case 'color-key':
                                            $tmp = ' [ ' .
                                                $transparency['r'] . ' ' . $transparency['r'] .
                                                $transparency['g'] . ' ' . $transparency['g'] .
                                                $transparency['b'] . ' ' . $transparency['b'] .
                                                ' ] ';
                                            $info['Mask'] = $tmp;
                                            break;
                                    }
                                }
                            } else {
                                if (isset($options['transparency'])) {
                                    $transparency = $options['transparency'];

                                    switch ($transparency['type']) {
                                        case 'indexed':
                                            $tmp = ' [ ' . $transparency['data'] . ' ' . $transparency['data'] . '] ';
                                            $info['Mask'] = $tmp;
                                            break;

                                        case 'color-key':
                                            $tmp = ' [ ' .
                                                $transparency['r'] . ' ' . $transparency['r'] . ' ' .
                                                $transparency['g'] . ' ' . $transparency['g'] . ' ' .
                                                $transparency['b'] . ' ' . $transparency['b'] .
                                                ' ] ';
                                            $info['Mask'] = $tmp;
                                            break;
                                    }
                                }
                                $info['ColorSpace'] = '/' . $options['color'];
                            }
                        }

                        $info['BitsPerComponent'] = $options['bitsPerComponent'];
                    }
                }

                // assign it a place in the named resource dictionary as an external object, according to
                // the label passed in with it.
                $this->o_pages($this->currentNode, 'xObject', ['label' => $options['label'], 'objNum' => $id]);

                // also make sure that we have the right procset object for it.
                $this->o_procset($this->procsetObjectId, 'add', 'ImageC');
                break;

            case 'out':
                $o = &$this->objects[$id];
                $tmp = &$o['data'];
                $res = "\n$id 0 obj\n<<";

                foreach ($o['info'] as $k => $v) {
                    $res .= "\n/$k $v";
                }

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $tmp = $this->ARC4($tmp);
                }

                $res .= "\n/Length " . mb_strlen($tmp, '8bit') . ">>\nstream\n$tmp\nendstream\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * graphics state object
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     */
    protected function o_extGState($id, $action, $options = "")
    {
        static $valid_params = [
            "LW",
            "LC",
            "LC",
            "LJ",
            "ML",
            "D",
            "RI",
            "OP",
            "op",
            "OPM",
            "Font",
            "BG",
            "BG2",
            "UCR",
            "TR",
            "TR2",
            "HT",
            "FL",
            "SM",
            "SA",
            "BM",
            "SMask",
            "CA",
            "ca",
            "AIS",
            "TK"
        ];

        switch ($action) {
            case "new":
                $this->objects[$id] = ['t' => 'extGState', 'info' => $options];

                // Tell the pages about the new resource
                $this->numStates++;
                $this->o_pages($this->currentNode, 'extGState', ["objNum" => $id, "stateNum" => $this->numStates]);
                break;

            case "out":
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< /Type /ExtGState\n";

                foreach ($o["info"] as $k => $v) {
                    if (!in_array($k, $valid_params)) {
                        continue;
                    }
                    $res .= "/$k $v\n";
                }

                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * @param integer $id
     * @param string $action
     * @param mixed $options
     * @return string
     */
    protected function o_xobject($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'xobject', 'info' => $options, 'c' => ''];
                break;

            case 'procset':
                $this->objects[$id]['procset'] = $options;
                break;

            case 'font':
                $this->objects[$id]['fonts'][$options['fontNum']] = [
                  'objNum' => $options['objNum'],
                  'fontNum' => $options['fontNum']
                ];
                break;

            case 'xObject':
                $this->objects[$id]['xObjects'][] = ['objNum' => $options['objNum'], 'label' => $options['label']];
                break;

            case 'out':
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< /Type /XObject\n";

                foreach ($o["info"] as $k => $v) {
                    switch ($k) {
                        case 'Subtype':
                            $res .= "/Subtype /$v\n";
                            break;
                        case 'bbox':
                            $res .= "/BBox [";
                            foreach ($v as $value) {
                                $res .= sprintf("%.4F ", $value);
                            }
                            $res .= "]\n";
                            break;
                        default:
                            $res .= "/$k $v\n";
                            break;
                    }
                }
                $res .= "/Matrix[1.0 0.0 0.0 1.0 0.0 0.0]\n";

                $res .= "/Resources <<";
                if (isset($o['procset'])) {
                    $res .= "\n/ProcSet " . $o['procset'] . " 0 R";
                } else {
                    $res .= "\n/ProcSet [/PDF /Text /ImageB /ImageC /ImageI]";
                }
                if (isset($o['fonts']) && count($o['fonts'])) {
                    $res .= "\n/Font << ";
                    foreach ($o['fonts'] as $finfo) {
                        $res .= "\n/F" . $finfo['fontNum'] . " " . $finfo['objNum'] . " 0 R";
                    }
                    $res .= "\n>>";
                }
                if (isset($o['xObjects']) && count($o['xObjects'])) {
                    $res .= "\n/XObject << ";
                    foreach ($o['xObjects'] as $finfo) {
                        $res .= "\n/" . $finfo['label'] . " " . $finfo['objNum'] . " 0 R";
                    }
                    $res .= "\n>>";
                }
                $res .= "\n>>\n";

                $tmp = $o["c"];
                if ($this->compressionReady && $this->options['compression']) {
                    // then implement ZLIB based compression on this content stream
                    $res .= " /Filter /FlateDecode\n";
                    $tmp = gzcompress($tmp, 6);
                }

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $tmp = $this->ARC4($tmp);
                }

                $res .= "/Length " . mb_strlen($tmp, '8bit') . " >>\n";
                $res .= "stream\n" . $tmp . "\nendstream" . "\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     */
    protected function o_acroform($id, $action, $options = '')
    {
        switch ($action) {
            case "new":
                $this->o_catalog($this->catalogId, 'acroform', $id);
                $this->objects[$id] = array('t' => 'acroform', 'info' => $options);
                break;

            case 'addfield':
                $this->objects[$id]['info']['Fields'][] = $options;
                break;

            case 'font':
                $this->objects[$id]['fonts'][$options['fontNum']] = [
                  'objNum' => $options['objNum'],
                  'fontNum' => $options['fontNum']
                ];
                break;

            case "out":
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<<";

                foreach ($o["info"] as $k => $v) {
                    switch ($k) {
                        case 'Fields':
                            $res .= " /Fields [";
                            foreach ($v as $i) {
                                $res .= "$i 0 R ";
                            }
                            $res .= "]\n";
                            break;
                        default:
                            $res .= "/$k $v\n";
                    }
                }

                $res .= "/DR <<\n";
                if (isset($o['fonts']) && count($o['fonts'])) {
                    $res .= "/Font << \n";
                    foreach ($o['fonts'] as $finfo) {
                        $res .= "/F" . $finfo['fontNum'] . " " . $finfo['objNum'] . " 0 R\n";
                    }
                    $res .= ">>\n";
                }
                $res .= ">>\n";

                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * @param $id
     * @param $action
     * @param mixed $options
     * @return null|string
     */
    protected function o_field($id, $action, $options = '')
    {
        switch ($action) {
            case "new":
                $this->o_page($options['pageid'], 'annot', $id);
                $this->o_acroform($this->acroFormId, 'addfield', $id);
                $this->objects[$id] = ['t' => 'field', 'info' => $options];
                break;

            case 'set':
                $this->objects[$id]['info'] = array_merge($this->objects[$id]['info'], $options);
                break;

            case "out":
                $o = &$this->objects[$id];
                $res = "\n$id 0 obj\n<< /Type /Annot /Subtype /Widget \n";

                $encrypted = $this->encrypted;
                if ($encrypted) {
                    $this->encryptInit($id);
                }

                foreach ($o["info"] as $k => $v) {
                    switch ($k) {
                        case 'pageid':
                            $res .= "/P $v 0 R\n";
                            break;
                        case 'value':
                            if ($encrypted) {
                                $v = $this->filterText($this->ARC4($v), false, false);
                            }
                            $res .= "/V ($v)\n";
                            break;
                        case 'refvalue':
                            $res .= "/V $v 0 R\n";
                            break;
                        case 'da':
                            if ($encrypted) {
                                $v = $this->filterText($this->ARC4($v), false, false);
                            }
                            $res .= "/DA ($v)\n";
                            break;
                        case 'options':
                            $res .= "/Opt [\n";
                            foreach ($v as $opt) {
                                if ($encrypted) {
                                    $opt = $this->filterText($this->ARC4($opt), false, false);
                                }
                                $res .= "($opt)\n";
                            }
                            $res .= "]\n";
                            break;
                        case 'rect':
                            $res .= "/Rect [";
                            foreach ($v as $value) {
                                $res .= sprintf("%.4F ", $value);
                            }
                            $res .= "]\n";
                            break;
                        case 'appearance':
                            $res .= "/AP << ";
                            foreach ($v as $a => $ref) {
                                $res .= "/$a $ref 0 R ";
                            }
                            $res .= ">>\n";
                            break;
                        case 'T':
                            if ($encrypted) {
                                $v = $this->filterText($this->ARC4($v), false, false);
                            }
                            $res .= "/T ($v)\n";
                            break;
                        default:
                            $res .= "/$k $v\n";
                    }

                }

                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return null|string
     */
    protected function o_sig($id, $action, $options = '')
    {
        $sign_maxlen = $this->signatureMaxLen;

        switch ($action) {
            case "new":
                $this->objects[$id] = array('t' => 'sig', 'info' => $options);
                $this->byteRange[$id] = ['t' => 'sig'];
                break;

            case 'byterange':
                $o = &$this->objects[$id];
                $content =& $options['content'];
                $content_len = strlen($content);
                $pos = strpos($content, sprintf("/ByteRange [ %'.010d", $id));
                $len = strlen('/ByteRange [ ********** ********** ********** ********** ]');
                $rangeStartPos = $pos + $len + 1 + 10; // before '<'
                $content = substr_replace($content, str_pad(sprintf('/ByteRange [ 0 %u %u %u ]', $rangeStartPos, $rangeStartPos + $sign_maxlen + 2, $content_len - 2 - $sign_maxlen - $rangeStartPos), $len, ' ', STR_PAD_RIGHT), $pos, $len);

                $fuid = uniqid();
                $tmpInput = $this->tmp . "/pkcs7.tmp." . $fuid . '.in';
                $tmpOutput = $this->tmp . "/pkcs7.tmp." . $fuid . '.out';

                if (file_put_contents($tmpInput, substr($content, 0, $rangeStartPos)) === false) {
                    throw new \Exception("Unable to write temporary file for signing.");
                }
                if (file_put_contents($tmpInput, substr($content, $rangeStartPos + 2 + $sign_maxlen),
                    FILE_APPEND) === false) {
                    throw new \Exception("Unable to write temporary file for signing.");
                }

                if (openssl_pkcs7_sign($tmpInput, $tmpOutput,
                    $o['info']['SignCert'],
                    array($o['info']['PrivKey'], $o['info']['Password']),
                    array(), PKCS7_BINARY | PKCS7_DETACHED) === false) {
                    throw new \Exception("Failed to prepare signature.");
                }

                $signature = file_get_contents($tmpOutput);

                unlink($tmpInput);
                unlink($tmpOutput);

                $sign = substr($signature, (strpos($signature, "%%EOF\n\n------") + 13));
                list($head, $signature) = explode("\n\n", $sign);

                $signature = base64_decode(trim($signature));

                $signature = current(unpack('H*', $signature));
                $signature = str_pad($signature, $sign_maxlen, '0');
                $siglen = strlen($signature);
                if (strlen($signature) > $sign_maxlen) {
                    throw new \Exception("Signature length ($siglen) exceeds the $sign_maxlen limit.");
                }

                $content = substr_replace($content, $signature, $rangeStartPos + 1, $sign_maxlen);
                break;

            case "out":
                $res = "\n$id 0 obj\n<<\n";

                $encrypted = $this->encrypted;
                if ($encrypted) {
                    $this->encryptInit($id);
                }

                $res .= "/ByteRange " .sprintf("[ %'.010d ********** ********** ********** ]\n", $id);
                $res .= "/Contents <" . str_pad('', $sign_maxlen, '0') . ">\n";
                $res .= "/Filter/Adobe.PPKLite\n"; //PPKMS \n";
                $res .= "/Type/Sig/SubFilter/adbe.pkcs7.detached \n";

                $date = "D:" . substr_replace(date('YmdHisO'), '\'', -2, 0) . '\'';
                if ($encrypted) {
                    $date = $this->filterText($this->ARC4($date), false, false);
                }

                $res .= "/M ($date)\n";
                $res .= "/Prop_Build << /App << /Name /DomPDF >> /Filter << /Name /Adobe.PPKLite >> >>\n";

                $o = &$this->objects[$id];
                foreach ($o['info'] as $k => $v) {
                    switch ($k) {
                        case 'Name':
                        case 'Location':
                        case 'Reason':
                        case 'ContactInfo':
                            if ($v !== null && $v !== '') {
                                $res .= "/$k (" .
                                  ($encrypted ? $this->filterText($this->ARC4($v), false, false) : $v) . ") \n";
                            }
                            break;
                    }
                }
                $res .= ">>\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * encryption object.
     *
     * @param $id
     * @param $action
     * @param string $options
     * @return string|null
     */
    protected function o_encryption($id, $action, $options = '')
    {
        switch ($action) {
            case 'new':
                // make the new object
                $this->objects[$id] = ['t' => 'encryption', 'info' => $options];
                $this->arc4_objnum = $id;
                break;

            case 'keys':
                // figure out the additional parameters required
                $pad = chr(0x28) . chr(0xBF) . chr(0x4E) . chr(0x5E) . chr(0x4E) . chr(0x75) . chr(0x8A) . chr(0x41)
                    . chr(0x64) . chr(0x00) . chr(0x4E) . chr(0x56) . chr(0xFF) . chr(0xFA) . chr(0x01) . chr(0x08)
                    . chr(0x2E) . chr(0x2E) . chr(0x00) . chr(0xB6) . chr(0xD0) . chr(0x68) . chr(0x3E) . chr(0x80)
                    . chr(0x2F) . chr(0x0C) . chr(0xA9) . chr(0xFE) . chr(0x64) . chr(0x53) . chr(0x69) . chr(0x7A);

                $info = $this->objects[$id]['info'];

                $len = mb_strlen($info['owner'], '8bit');

                if ($len > 32) {
                    $owner = substr($info['owner'], 0, 32);
                } else {
                    if ($len < 32) {
                        $owner = $info['owner'] . substr($pad, 0, 32 - $len);
                    } else {
                        $owner = $info['owner'];
                    }
                }

                $len = mb_strlen($info['user'], '8bit');
                if ($len > 32) {
                    $user = substr($info['user'], 0, 32);
                } else {
                    if ($len < 32) {
                        $user = $info['user'] . substr($pad, 0, 32 - $len);
                    } else {
                        $user = $info['user'];
                    }
                }

                $tmp = $this->md5_16($owner);
                $okey = substr($tmp, 0, 5);
                $this->ARC4_init($okey);
                $ovalue = $this->ARC4($user);
                $this->objects[$id]['info']['O'] = $ovalue;

                // now make the u value, phew.
                $tmp = $this->md5_16(
                    $user . $ovalue . chr($info['p']) . chr(255) . chr(255) . chr(255) . hex2bin($this->fileIdentifier)
                );

                $ukey = substr($tmp, 0, 5);
                $this->ARC4_init($ukey);
                $this->encryptionKey = $ukey;
                $this->encrypted = true;
                $uvalue = $this->ARC4($pad);
                $this->objects[$id]['info']['U'] = $uvalue;
                // initialize the arc4 array
                break;

            case 'out':
                $o = &$this->objects[$id];

                $res = "\n$id 0 obj\n<<";
                $res .= "\n/Filter /Standard";
                $res .= "\n/V 1";
                $res .= "\n/R 2";
                $res .= "\n/O (" . $this->filterText($o['info']['O'], false, false) . ')';
                $res .= "\n/U (" . $this->filterText($o['info']['U'], false, false) . ')';
                // and the p-value needs to be converted to account for the twos-complement approach
                $o['info']['p'] = (($o['info']['p'] ^ 255) + 1) * -1;
                $res .= "\n/P " . ($o['info']['p']);
                $res .= "\n>>\nendobj";

                return $res;
        }

        return null;
    }

    protected function o_indirect_references($id, $action, $options = null)
    {
        switch ($action) {
            case 'new':
            case 'add':
                if ($id === 0) {
                    $id = ++$this->numObj;
                    $this->o_catalog($this->catalogId, 'names', $id);
                    $this->objects[$id] = ['t' => 'indirect_references', 'info' => $options];
                    $this->indirectReferenceId = $id;
                } else {
                    $this->objects[$id]['info'] = array_merge($this->objects[$id]['info'], $options);
                }
                break;
            case 'out':
                $res = "\n$id 0 obj\n<< ";

                foreach ($this->objects[$id]['info'] as $referenceObjName => $referenceObjId) {
                    $res .= "/$referenceObjName $referenceObjId 0 R ";
                }

                $res .= ">>\nendobj";
                return $res;
        }

        return null;
    }

    protected function o_names($id, $action, $options = null)
    {
        switch ($action) {
            case 'new':
            case 'add':
                if ($id === 0) {
                    $id = ++$this->numObj;
                    $this->objects[$id] = ['t' => 'names', 'info' => [$options]];
                    $this->o_indirect_references($this->indirectReferenceId, 'add', ['EmbeddedFiles' => $id]);
                    $this->embeddedFilesId = $id;
                } else {
                    $this->objects[$id]['info'][] = $options;
                }
                break;
            case 'out':
                $info = &$this->objects[$id]['info'];
                $res = '';
                if (count($info) > 0) {
                    $res = "\n$id 0 obj\n<< /Names [ ";

                    if ($this->encrypted) {
                        $this->encryptInit($id);
                    }

                    foreach ($info as $entry) {
                        if ($this->encrypted) {
                            $filename = $this->ARC4($entry['filename']);
                        } else {
                            $filename = $entry['filename'];
                        }

                        $filename = $this->filterText($filename, false, false);

                        $res .= "($filename) " . $entry['dict_reference'] . " 0 R ";
                    }

                    $res .= "] >>\nendobj";
                }
                return $res;
        }

        return null;
    }

    protected function o_embedded_file_dictionary($id, $action, $options = null)
    {
        switch ($action) {
            case 'new':
                $embeddedFileId = ++$this->numObj;
                $options['embedded_reference'] = $embeddedFileId;
                $this->objects[$id] = ['t' => 'embedded_file_dictionary', 'info' => $options];
                $this->o_embedded_file($embeddedFileId, 'new', $options);
                $options['dict_reference'] = $id;
                $this->o_names($this->embeddedFilesId, 'add', $options);
                break;
            case 'out':
                $info = &$this->objects[$id]['info'];
                $filename = $this->utf8toUtf16BE($info['filename']);
                $description = $this->utf8toUtf16BE($info['description']);

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $filename = $this->ARC4($filename);
                    $description = $this->ARC4($description);
                }

                $filename = $this->filterText($filename, false, false);
                $description = $this->filterText($description, false, false);

                $res = "\n$id 0 obj\n<</Type /Filespec /EF";
                $res .= " <</F " . $info['embedded_reference'] . " 0 R >>";
                $res .= " /F ($filename) /UF ($filename) /Desc ($description)";
                $res .= " >>\nendobj";
                return $res;
        }

        return null;
    }

    protected function o_embedded_file($id, $action, $options = null): ?string
    {
        switch ($action) {
            case 'new':
                $this->objects[$id] = ['t' => 'embedded_file', 'info' => $options];
                break;
            case 'out':
                $info = &$this->objects[$id]['info'];

                if ($this->compressionReady) {
                    $filepath = $info['filepath'];
                    $checksum = md5_file($filepath);
                    $f = fopen($filepath, "rb");

                    $file_content_compressed = '';
                    $deflateContext = deflate_init(ZLIB_ENCODING_DEFLATE, ['level' => 6]);
                    while (($block = fread($f, 8192))) {
                        $file_content_compressed .= deflate_add($deflateContext, $block, ZLIB_NO_FLUSH);
                    }
                    $file_content_compressed .= deflate_add($deflateContext, '', ZLIB_FINISH);
                    $file_size_uncompressed = ftell($f);
                    fclose($f);
                } else {
                    $file_content = file_get_contents($info['filepath']);
                    $file_size_uncompressed = mb_strlen($file_content, '8bit');
                    $checksum = md5($file_content);
                }

                if ($this->encrypted) {
                    $this->encryptInit($id);
                    $checksum = $this->filterText($this->ARC4($checksum), false, false);
                    $file_content_compressed = $this->ARC4($file_content_compressed);
                }
                $file_size_compressed = mb_strlen($file_content_compressed, '8bit');

                $res = "\n$id 0 obj\n<</Params <</Size $file_size_uncompressed /CheckSum ($checksum) >>" .
                    " /Type/EmbeddedFile /Filter/FlateDecode" .
                    " /Length $file_size_compressed >> stream\n$file_content_compressed\nendstream\nendobj";

                return $res;
        }

        return null;
    }

    /**
     * Enable PDF/A compliance mode
     */
    public function enablePdfACompliance()
    {
        $this->pdfa = true;

        $iccProfilePath = __DIR__ . '/res/sRGB2014.icc';
        $this->o_catalog($this->catalogId, 'outputIntents', [
            'iccProfileData' => file_get_contents($iccProfilePath),
            'iccProfileName' => basename($iccProfilePath),
            'colorComponentsCount' => '3',
        ]);
    }

    /**
     * Generate the Metadata XMP XML for PDF/A
     *
     * @return string
     */
    function getXmpMetadata()
    {
        $md = <<<EOT
<?xpacket begin="\xEF\xBB\xBF" id="W5M0MpCehiHzreSzNTczkc9d"?>
<x:xmpmeta xmlns:x="adobe:ns:meta/">
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">

<rdf:Description xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/" rdf:about="">
<pdfaid:part>3</pdfaid:part>
<pdfaid:conformance>B</pdfaid:conformance>
</rdf:Description>

<rdf:Description xmlns:dc="http://purl.org/dc/elements/1.1/" rdf:about="">
EOT;

        $info = $this->objects[$this->infoObject]["info"];

        if (isset($info['Title'])) {
            $md .= "\n<dc:title><rdf:Alt><rdf:li xml:lang=\"x-default\">";
            $md .= htmlspecialchars($info['Title'], ENT_XML1, 'UTF-8');
            $md .= "</rdf:li></rdf:Alt></dc:title>";
        }

        if (isset($info['Author'])) {
            $md .= "\n<dc:creator><rdf:Seq><rdf:li>";
            $md .= htmlspecialchars($info['Author'], ENT_XML1, 'UTF-8');
            $md .= "</rdf:li></rdf:Seq></dc:creator>";
        }

        if (isset($info['Subject'])) {
            $md .= "\n<dc:description><rdf:Alt><rdf:li xml:lang=\"x-default\">";
            $md .= htmlspecialchars($info['Subject'], ENT_XML1, 'UTF-8');
            $md .= "</rdf:li></rdf:Alt></dc:description>";
        }

        $md .= "\n</rdf:Description>";
        $md .= "\n<rdf:Description xmlns:pdf=\"http://ns.adobe.com/pdf/1.3/\" rdf:about=\"\">";

        if (isset($info['Producer'])) {
            $md .= "\n<pdf:Producer>";
            $md .= htmlspecialchars($info['Producer'], ENT_XML1, 'UTF-8');
            $md .= "</pdf:Producer>";
        }

        if (isset($info['Keywords'])) {
            $md .= "\n<pdf:Keywords>";
            $md .= htmlspecialchars($info['Keywords'], ENT_XML1, 'UTF-8');
            $md .= "</pdf:Keywords>";
        }

        $md .= "\n</rdf:Description>";
        $md .= "\n<rdf:Description xmlns:xmp=\"http://ns.adobe.com/xap/1.0/\" rdf:about=\"\">";

        if (isset($info['Creator'])) {
            $md .= "\n<xmp:CreatorTool>";
            $md .= htmlspecialchars($info['Creator'], ENT_XML1, 'UTF-8');
            $md .= "</xmp:CreatorTool>";
        }

        if (isset($info['CreationDate']) && $date = $this->parsePdfDate($info['CreationDate'])) {
            $md .= "\n<xmp:CreateDate>";
            $md .= $date->format("Y-m-d\TH:i:sP");
            $md .= "</xmp:CreateDate>";
        }

        if (isset($info['ModDate']) && $date = $this->parsePdfDate($info['ModDate'])) {
            $md .= "\n<xmp:ModifyDate>";
            $md .= $date->format("Y-m-d\TH:i:sP");
            $md .= "</xmp:ModifyDate>";
        }

        $md .= "\n</rdf:Description>\n</rdf:RDF>\n</x:xmpmeta>\n<?xpacket end=\"w\"?>";

        return $md;
    }

    /**
     * Parse a PDF formatted date
     *
     * @param $string
     * @return \DateTime|false
     */
    function parsePdfDate($date)
    {
        $formats = [
            "Y",
            "Ym",
            "Ymd",
            "YmdH",
            "YmdHi",
            "YmdHis",
            "YmdHisO",
        ];

        $date = substr($date, 2);
        $date = str_replace("'", "", $date);

        if ($i = strpos($date, "Z")) {
            $date = substr($date, 0, $i + 1);
        }

        foreach ($formats as $format) {
            $parsedDate = \DateTime::createFromFormat($format, $date, new \DateTimeZone("UTC"));

            if ($parsedDate) return $parsedDate;
        }

        return false;
    }

    /**
     * ARC4 functions
     * A series of function to implement ARC4 encoding in PHP
     */

    /**
     * calculate the 16 byte version of the 128 bit md5 digest of the string
     *
     * @param $string
     * @return string
     */
    function md5_16($string)
    {
        $tmp = md5($string);
        $out = '';
        for ($i = 0; $i <= 30; $i = $i + 2) {
            $out .= chr(hexdec(substr($tmp, $i, 2)));
        }

        return $out;
    }

    /**
     * initialize the encryption for processing a particular object
     *
     * @param $id
     */
    function encryptInit($id)
    {
        $tmp = $this->encryptionKey;
        $hex = dechex($id);
        if (mb_strlen($hex, '8bit') < 6) {
            $hex = substr('000000', 0, 6 - mb_strlen($hex, '8bit')) . $hex;
        }
        $tmp .= chr(hexdec(substr($hex, 4, 2)))
            . chr(hexdec(substr($hex, 2, 2)))
            . chr(hexdec(substr($hex, 0, 2)))
            . chr(0)
            . chr(0)
        ;
        $key = $this->md5_16($tmp);
        $this->ARC4_init(substr($key, 0, 10));
    }

    /**
     * initialize the ARC4 encryption
     *
     * @param string $key
     */
    function ARC4_init($key = '')
    {
        $this->arc4 = '';

        // setup the control array
        if (mb_strlen($key, '8bit') == 0) {
            return;
        }

        $k = '';
        while (mb_strlen($k, '8bit') < 256) {
            $k .= $key;
        }

        $k = substr($k, 0, 256);
        for ($i = 0; $i < 256; $i++) {
            $this->arc4 .= chr($i);
        }

        $j = 0;

        for ($i = 0; $i < 256; $i++) {
            $t = $this->arc4[$i];
            $j = ($j + ord($t) + ord($k[$i])) % 256;
            $this->arc4[$i] = $this->arc4[$j];
            $this->arc4[$j] = $t;
        }
    }

    /**
     * ARC4 encrypt a text string
     *
     * @param $text
     * @return string
     */
    function ARC4($text)
    {
        $len = mb_strlen($text, '8bit');
        $a = 0;
        $b = 0;
        $c = $this->arc4;
        $out = '';
        for ($i = 0; $i < $len; $i++) {
            $a = ($a + 1) % 256;
            $t = $c[$a];
            $b = ($b + ord($t)) % 256;
            $c[$a] = $c[$b];
            $c[$b] = $t;
            $k = ord($c[(ord($c[$a]) + ord($c[$b])) % 256]);
            $out .= chr(ord($text[$i]) ^ $k);
        }

        return $out;
    }

    /**
     * functions which can be called to adjust or add to the document
     */

    /**
     * add a link in the document to an external URL
     *
     * @param $url
     * @param $x0
     * @param $y0
     * @param $x1
     * @param $y1
     */
    function addLink($url, $x0, $y0, $x1, $y1)
    {
        $this->numObj++;
        $info = ['type' => 'link', 'url' => $url, 'rect' => [$x0, $y0, $x1, $y1]];
        $this->o_annotation($this->numObj, 'new', $info);
    }

    /**
     * add a link in the document to an internal destination (ie. within the document)
     *
     * @param $label
     * @param $x0
     * @param $y0
     * @param $x1
     * @param $y1
     */
    function addInternalLink($label, $x0, $y0, $x1, $y1)
    {
        $this->numObj++;
        $info = ['type' => 'ilink', 'label' => $label, 'rect' => [$x0, $y0, $x1, $y1]];
        $this->o_annotation($this->numObj, 'new', $info);
    }

    /**
     * set the encryption of the document
     * can be used to turn it on and/or set the passwords which it will have.
     * also the functions that the user will have are set here, such as print, modify, add
     *
     * @param string $userPass
     * @param string $ownerPass
     * @param array $pc
     */
    function setEncryption($userPass = '', $ownerPass = '', $pc = [])
    {
        $p = bindec("11000000");

        $options = ['print' => 4, 'modify' => 8, 'copy' => 16, 'add' => 32];

        foreach ($pc as $k => $v) {
            if ($v && isset($options[$k])) {
                $p += $options[$k];
            } else {
                if (isset($options[$v])) {
                    $p += $options[$v];
                }
            }
        }

        // implement encryption on the document
        if ($this->arc4_objnum == 0) {
            // then the block does not exist already, add it.
            $this->numObj++;
            if (mb_strlen($ownerPass) == 0) {
                $ownerPass = $userPass;
            }

            $this->o_encryption($this->numObj, 'new', ['user' => $userPass, 'owner' => $ownerPass, 'p' => $p]);
        }
    }

    /**
     * should be used for internal checks, not implemented as yet
     */
    function checkAllHere()
    {
    }

    /**
     * return the pdf stream as a string returned from the function
     *
     * @param bool $debug
     * @return string
     */
    function output($debug = false)
    {
        if ($debug) {
            // turn compression off
            $this->options['compression'] = false;
        }

        if ($this->javascript) {
            $this->numObj++;

            $js_id = $this->numObj;
            $this->o_embedjs($js_id, 'new');
            $this->o_javascript(++$this->numObj, 'new', $this->javascript);

            $id = $this->catalogId;

            $this->o_indirect_references($this->indirectReferenceId, 'add', ['JavaScript' => $js_id]);
        }

        if ($this->pdfa) {
            $this->o_catalog($this->catalogId, 'metadata', $this->getXmpMetadata());
        }

        if ($this->fileIdentifier === '') {
            $tmp = implode('', $this->objects[$this->infoObject]['info']);
            $this->fileIdentifier = md5('DOMPDF' . __FILE__ . $tmp . microtime() . mt_rand());
        }

        if ($this->arc4_objnum) {
            $this->o_encryption($this->arc4_objnum, 'keys');
            $this->ARC4_init($this->encryptionKey);
        }

        $this->checkAllHere();

        $xref = [];
        $content = '%PDF-' . self::PDF_VERSION;

        if ($this->pdfa) {
            // Force binary mode with 4 random bytes above 127
            $content .= "\n%" . chr(rand(128, 255)) . chr(rand(128, 255)) . chr(rand(128, 255)) . chr(rand(128, 255));
        }

        $pos = mb_strlen($content, '8bit');

        // pre-process o_font objects before output of all objects
        foreach ($this->objects as $k => $v) {
            if ($v['t'] === 'font') {
                $this->o_font($k, 'add');
            }
        }

        foreach ($this->objects as $k => $v) {
            $tmp = 'o_' . $v['t'];
            $cont = $this->$tmp($k, 'out');
            $content .= $cont;
            $xref[] = $pos + 1; //+1 to account for \n at the start of each object
            $pos += mb_strlen($cont, '8bit');
        }

        $content .= "\nxref\n0 " . (count($xref) + 1) . "\n0000000000 65535 f \n";

        foreach ($xref as $p) {
            $content .= str_pad($p, 10, "0", STR_PAD_LEFT) . " 00000 n \n";
        }

        $content .= "trailer\n<<\n" .
            '/Size ' . (count($xref) + 1) . "\n" .
            '/Root 1 0 R' . "\n" .
            '/Info ' . $this->infoObject . " 0 R\n"
        ;

        // if encryption has been applied to this document then add the marker for this dictionary
        if ($this->arc4_objnum > 0) {
            $content .= '/Encrypt ' . $this->arc4_objnum . " 0 R\n";
        }

        $content .= '/ID[<' . $this->fileIdentifier . '><' . $this->fileIdentifier . ">]\n";

        // account for \n added at start of xref table
        $pos++;

        $content .= ">>\nstartxref\n$pos\n%%EOF\n";

        if (count($this->byteRange) > 0) {
            foreach ($this->byteRange as $k => $v) {
                $tmp = 'o_' . $v['t'];
                $this->$tmp($k, 'byterange', ['content' => &$content]);
            }
        }

        return $content;
    }

    /**
     * initialize a new document
     * if this is called on an existing document results may be unpredictable, but the existing document would be lost at minimum
     * this function is called automatically by the constructor function
     *
     * @param array $pageSize
     */
    private function newDocument($pageSize = [0, 0, 612, 792])
    {
        $this->numObj = 0;
        $this->objects = [];

        $this->numObj++;
        $this->o_catalog($this->numObj, 'new');

        $this->numObj++;
        $this->o_outlines($this->numObj, 'new');

        $this->numObj++;
        $this->o_pages($this->numObj, 'new');

        $this->o_pages($this->numObj, 'mediaBox', $pageSize);
        $this->currentNode = 3;

        $this->numObj++;
        $this->o_procset($this->numObj, 'new');

        $this->numObj++;
        $this->o_info($this->numObj, 'new');

        $this->numObj++;
        $this->o_page($this->numObj, 'new');

        // need to store the first page id as there is no way to get it to the user during
        // startup
        $this->firstPageId = $this->currentContents;
    }

    /**
     * open the font file and return a php structure containing it.
     * first check if this one has been done before and saved in a form more suited to php
     * note that if a php serialized version does not exist it will try and make one, but will
     * require write access to the directory to do it... it is MUCH faster to have these serialized
     * files.
     *
     * @param $font
     */
    private function openFont($font)
    {
        // assume that $font contains the path and file but not the extension
        $name = basename($font);
        $dir = dirname($font);

        $fontcache = $this->fontcache;
        if ($fontcache == '') {
            $fontcache = $dir;
        }

        //$name       filename without folder and extension of font metrics
        //$dir        folder of font metrics
        //$fontcache  folder of runtime created php serialized version of font metrics.
        //            If this is not given, the same folder as the font metrics will be used.
        //            Storing and reusing serialized versions improves speed much

        $this->addMessage("openFont: $font - $name");

        if (!$this->isUnicode || in_array(mb_strtolower(basename($name)), self::$coreFonts)) {
            $metrics_name = "$name.afm";
        } else {
            $metrics_name = "$name.ufm";
        }

        $cache_name = "$metrics_name.json";
        $this->addMessage("metrics: $metrics_name, cache: $cache_name");

        if (file_exists($fontcache . '/' . $cache_name)) {
            $this->addMessage("openFont: json metrics file exists $fontcache/$cache_name");
            $cached_font_info = json_decode(file_get_contents($fontcache . '/' . $cache_name), true);
            if (!isset($cached_font_info['_version_']) || $cached_font_info['_version_'] != $this->fontcacheVersion) {
                $this->addMessage('openFont: font cache is out of date, regenerating');
            } else {
                $this->fonts[$font] = $cached_font_info;
            }
        }

        if (!isset($this->fonts[$font]) && file_exists("$dir/$metrics_name")) {
            // then rebuild the php_<font>.afm file from the <font>.afm file
            $this->addMessage("openFont: build php file from $dir/$metrics_name");
            $data = [];

            // 20 => 'space'
            $data['codeToName'] = [];

            // Since we're not going to enable Unicode for the core fonts we need to use a font-based
            // setting for Unicode support rather than a global setting.
            $data['isUnicode'] = (strtolower(substr($metrics_name, -3)) !== 'afm');

            $cidtogid = '';
            if ($data['isUnicode']) {
                $cidtogid = str_pad('', 256 * 256 * 2, "\x00");
            }

            $file = file("$dir/$metrics_name");

            foreach ($file as $rowA) {
                $row = trim($rowA);
                $pos = strpos($row, ' ');

                if ($pos) {
                    // then there must be some keyword
                    $key = substr($row, 0, $pos);
                    switch ($key) {
                        case 'FontName':
                        case 'FullName':
                        case 'FamilyName':
                        case 'PostScriptName':
                        case 'Weight':
                        case 'ItalicAngle':
                        case 'IsFixedPitch':
                        case 'CharacterSet':
                        case 'UnderlinePosition':
                        case 'UnderlineThickness':
                        case 'Version':
                        case 'EncodingScheme':
                        case 'CapHeight':
                        case 'XHeight':
                        case 'Ascender':
                        case 'Descender':
                        case 'StdHW':
                        case 'StdVW':
                        case 'StartCharMetrics':
                        case 'FontHeightOffset': // OAR - Added so we can offset the height calculation of a Windows font.  Otherwise it's too big.
                            $data[$key] = trim(substr($row, $pos));
                            break;

                        case 'FontBBox':
                            $data[$key] = explode(' ', trim(substr($row, $pos)));
                            break;

                        //C 39 ; WX 222 ; N quoteright ; B 53 463 157 718 ;
                        case 'C': // Found in AFM files
                            $bits = explode(';', trim($row));
                            $dtmp = ['C' => null, 'N' => null, 'WX' => null, 'B' => []];

                            foreach ($bits as $bit) {
                                $bits2 = explode(' ', trim($bit));
                                if (mb_strlen($bits2[0], '8bit') == 0) {
                                    continue;
                                }

                                if (count($bits2) > 2) {
                                    $dtmp[$bits2[0]] = [];
                                    for ($i = 1; $i < count($bits2); $i++) {
                                        $dtmp[$bits2[0]][] = $bits2[$i];
                                    }
                                } else {
                                    if (count($bits2) == 2) {
                                        $dtmp[$bits2[0]] = $bits2[1];
                                    }
                                }
                            }

                            $c = (int)$dtmp['C'];
                            $n = $dtmp['N'];
                            $width = floatval($dtmp['WX']);

                            if ($c >= 0) {
                                if (!ctype_xdigit($n) || $c != hexdec($n)) {
                                    $data['codeToName'][$c] = $n;
                                }
                                $data['C'][$c] = $width;
                            } elseif (isset($n)) {
                                $data['C'][$n] = $width;
                            }

                            if (!isset($data['MissingWidth']) && $c === -1 && $n === '.notdef') {
                                $data['MissingWidth'] = $width;
                            }

                            break;

                        // U 827 ; WX 0 ; N squaresubnosp ; G 675 ;
                        case 'U': // Found in UFM files
                            if (!$data['isUnicode']) {
                                break;
                            }

                            $bits = explode(';', trim($row));
                            $dtmp = ['G' => null, 'N' => null, 'U' => null, 'WX' => null];

                            foreach ($bits as $bit) {
                                $bits2 = explode(' ', trim($bit));
                                if (mb_strlen($bits2[0], '8bit') === 0) {
                                    continue;
                                }

                                if (count($bits2) > 2) {
                                    $dtmp[$bits2[0]] = [];
                                    for ($i = 1; $i < count($bits2); $i++) {
                                        $dtmp[$bits2[0]][] = $bits2[$i];
                                    }
                                } else {
                                    if (count($bits2) == 2) {
                                        $dtmp[$bits2[0]] = $bits2[1];
                                    }
                                }
                            }

                            $c = (int)$dtmp['U'];
                            $n = $dtmp['N'];
                            $glyph = $dtmp['G'];
                            $width = floatval($dtmp['WX']);

                            if ($c >= 0) {
                                // Set values in CID to GID map
                                if ($c >= 0 && $c < 0xFFFF && $glyph) {
                                    $cidtogid[$c * 2] = chr($glyph >> 8);
                                    $cidtogid[$c * 2 + 1] = chr($glyph & 0xFF);
                                }

                                if (!ctype_xdigit($n) || $c != hexdec($n)) {
                                    $data['codeToName'][$c] = $n;
                                }
                                $data['C'][$c] = $width;
                            } elseif (isset($n)) {
                                $data['C'][$n] = $width;
                            }

                            if (!isset($data['MissingWidth']) && $c === -1 && $n === '.notdef') {
                                $data['MissingWidth'] = $width;
                            }

                            break;

                        case 'KPX':
                            break; // don't include them as they are not used yet
                            //KPX Adieresis yacute -40
                            /*$bits = explode(' ', trim($row));
                            $data['KPX'][$bits[1]][$bits[2]] = $bits[3];
                            break;*/
                    }
                }
            }

            if ($this->compressionReady && $this->options['compression']) {
                // then implement ZLIB based compression on CIDtoGID string
                $data['CIDtoGID_Compressed'] = true;
                $cidtogid = gzcompress($cidtogid, 6);
            }
            $data['CIDtoGID'] = base64_encode($cidtogid);
            $data['_version_'] = $this->fontcacheVersion;
            $this->fonts[$font] = $data;

            //Because of potential trouble with php safe mode, expect that the folder already exists.
            //If not existing, this will hit performance because of missing cached results.
            if (is_dir($fontcache) && is_writable($fontcache)) {
                file_put_contents("$fontcache/$cache_name", json_encode($data, JSON_PRETTY_PRINT));
            }
            $data = null;
        }

        if (!isset($this->fonts[$font])) {
            $this->addMessage("openFont: no font file found for $font. Do you need to run load_font.php?");
        }
    }

    /**
     * if the font is not loaded then load it and make the required object
     * else just make it the current font
     * the encoding array can contain 'encoding'=> 'none','WinAnsiEncoding','MacRomanEncoding' or 'MacExpertEncoding'
     * note that encoding='none' will need to be used for symbolic fonts
     * and 'differences' => an array of mappings between numbers 0->255 and character names.
     *
     * @param string $fontName
     * @param string $encoding
     * @param bool $set
     * @param bool $isSubsetting
     * @return int
     * @throws FontNotFoundException
     */
    function selectFont($fontName, $encoding = '', $set = true, $isSubsetting = true)
    {
        $fontName = (string) $fontName;
        $ext = substr($fontName, -4);
        if ($ext === '.afm' || $ext === '.ufm') {
            $fontName = substr($fontName, 0, mb_strlen($fontName) - 4);
        }
        if ($fontName === '') {
            return $this->currentFontNum;
        }

        if (!isset($this->fonts[$fontName])) {
            $this->addMessage("selectFont: selecting - $fontName - $encoding, $set");

            // load the file
            $this->openFont($fontName);

            if (isset($this->fonts[$fontName])) {
                $this->numObj++;
                $this->numFonts++;

                $font = &$this->fonts[$fontName];

                $name = basename($fontName);
                $options = ['name' => $name, 'fontFileName' => $fontName, 'isSubsetting' => $isSubsetting];

                if (is_array($encoding)) {
                    // then encoding and differences might be set
                    if (isset($encoding['encoding'])) {
                        $options['encoding'] = $encoding['encoding'];
                    }

                    if (isset($encoding['differences'])) {
                        $options['differences'] = $encoding['differences'];
                    }
                } else {
                    if (mb_strlen($encoding, '8bit')) {
                        // then perhaps only the encoding has been set
                        $options['encoding'] = $encoding;
                    }
                }

                $this->o_font($this->numObj, 'new', $options);

                if (file_exists("$fontName.ttf")) {
                    $fileSuffix = 'ttf';
                } elseif (file_exists("$fontName.TTF")) {
                    $fileSuffix = 'TTF';
                } elseif (file_exists("$fontName.pfb")) {
                    $fileSuffix = 'pfb';
                } elseif (file_exists("$fontName.PFB")) {
                    $fileSuffix = 'PFB';
                } else {
                    $fileSuffix = '';
                }

                $font['fileSuffix'] = $fileSuffix;

                $font['fontNum'] = $this->numFonts;
                $font['isSubsetting'] = $isSubsetting && $font['isUnicode'] && strtolower($fileSuffix) === 'ttf';

                // also set the differences here, note that this means that these will take effect only the
                //first time that a font is selected, else they are ignored
                if (isset($options['differences'])) {
                    $font['differences'] = $options['differences'];
                }
            }
        }

        if ($set && isset($this->fonts[$fontName])) {
            // so if for some reason the font was not set in the last one then it will not be selected
            $this->currentBaseFont = $fontName;

            // the next lines mean that if a new font is selected, then the current text state will be
            // applied to it as well.
            $this->currentFont = $this->currentBaseFont;
            $this->currentFontNum = $this->fonts[$this->currentFont]['fontNum'];
        }

        return $this->currentFontNum;
    }

    /**
     * sets up the current font, based on the font families, and the current text state
     * note that this system is quite flexible, a bold-italic font can be completely different to a
     * italic-bold font, and even bold-bold will have to be defined within the family to have meaning
     * This function is to be called whenever the currentTextState is changed, it will update
     * the currentFont setting to whatever the appropriate family one is.
     * If the user calls selectFont themselves then that will reset the currentBaseFont, and the currentFont
     * This function will change the currentFont to whatever it should be, but will not change the
     * currentBaseFont.
     */
    private function setCurrentFont()
    {
        //   if (strlen($this->currentBaseFont) == 0){
        //     // then assume an initial font
        //     $this->selectFont($this->defaultFont);
        //   }
        //   $cf = substr($this->currentBaseFont,strrpos($this->currentBaseFont,'/')+1);
        //   if (strlen($this->currentTextState)
        //     && isset($this->fontFamilies[$cf])
        //       && isset($this->fontFamilies[$cf][$this->currentTextState])){
        //     // then we are in some state or another
        //     // and this font has a family, and the current setting exists within it
        //     // select the font, then return it
        //     $nf = substr($this->currentBaseFont,0,strrpos($this->currentBaseFont,'/')+1).$this->fontFamilies[$cf][$this->currentTextState];
        //     $this->selectFont($nf,'',0);
        //     $this->currentFont = $nf;
        //     $this->currentFontNum = $this->fonts[$nf]['fontNum'];
        //   } else {
        //     // the this font must not have the right family member for the current state
        //     // simply assume the base font
        $this->currentFont = $this->currentBaseFont;
        $this->currentFontNum = $this->fonts[$this->currentFont]['fontNum'];
        //  }
    }

    /**
     * function for the user to find out what the ID is of the first page that was created during
     * startup - useful if they wish to add something to it later.
     *
     * @return int
     */
    function getFirstPageId()
    {
        return $this->firstPageId;
    }

    /**
     * add content to the currently active object
     *
     * @param $content
     */
    private function addContent($content)
    {
        $this->objects[$this->currentContents]['c'] .= $content;
    }

    /**
     * sets the color for fill operations
     *
     * @param array $color
     * @param bool  $force
     * @throws Exception
     */
    function setColor($color, $force = false)
    {
        $new_color = [$color[0], $color[1], $color[2], isset($color[3]) ? $color[3] : null];

        if (!$force && $this->currentColor == $new_color) {
            return;
        }

        if (isset($new_color[3])) {
            if ($this->pdfa) {
                throw new \Exception("CMYK colors are not supported when generating a document in PDF/A mode");
            }
            $this->currentColor = $new_color;
            $this->addContent(vsprintf("\n%.3F %.3F %.3F %.3F k", $this->currentColor));
        } else {
            if (isset($new_color[2])) {
                $this->currentColor = $new_color;
                $this->addContent(vsprintf("\n%.3F %.3F %.3F rg", $this->currentColor));
            }
        }
    }

    /**
     * sets the color for fill operations
     *
     * @param string $fillRule
     */
    function setFillRule($fillRule)
    {
        if (!in_array($fillRule, ["nonzero", "evenodd"])) {
            return;
        }

        $this->fillRule = $fillRule;
    }

    /**
     * sets the color for stroke operations
     *
     * @param array $color
     * @param bool  $force
     * @throws Exception
     */
    function setStrokeColor($color, $force = false)
    {
        $new_color = [$color[0], $color[1], $color[2], isset($color[3]) ? $color[3] : null];

        if (!$force && $this->currentStrokeColor == $new_color) {
            return;
        }

        if (isset($new_color[3])) {
            if ($this->pdfa) {
                throw new \Exception("CMYK colors are not supported when generating a document in PDF/A mode");
            }
            $this->currentStrokeColor = $new_color;
            $this->addContent(vsprintf("\n%.3F %.3F %.3F %.3F K", $this->currentStrokeColor));
        } else {
            if (isset($new_color[2])) {
                $this->currentStrokeColor = $new_color;
                $this->addContent(vsprintf("\n%.3F %.3F %.3F RG", $this->currentStrokeColor));
            }
        }
    }

    /**
     * Set the graphics state for compositions
     *
     * @param $parameters
     */
    function setGraphicsState($parameters)
    {
        // Create a new graphics state object if necessary
        if (($gstate = array_search($parameters, $this->gstates)) === false) {
            $this->numObj++;
            $this->o_extGState($this->numObj, 'new', $parameters);
            $gstate = $this->numStates;
            $this->gstates[$gstate] = $parameters;
        }
        $this->addContent("\n/GS$gstate gs");
    }

    /**
     * Set current blend mode & opacity for lines.
     *
     * Valid blend modes are:
     *
     * Normal, Multiply, Screen, Overlay, Darken, Lighten,
     * ColorDogde, ColorBurn, HardLight, SoftLight, Difference,
     * Exclusion
     *
     * @param string $mode    The blend mode to use
     * @param float  $opacity 0.0 fully transparent, 1.0 fully opaque
     */
    public function setLineTransparency(string $mode, float $opacity): void
    {
        static $blendModes = [
            "Normal",
            "Multiply",
            "Screen",
            "Overlay",
            "Darken",
            "Lighten",
            "ColorDogde",
            "ColorBurn",
            "HardLight",
            "SoftLight",
            "Difference",
            "Exclusion"
        ];

        if (!in_array($mode, $blendModes, true)) {
            $mode = "Normal";
        }

        $newState = [
            "mode"    => $mode,
            "opacity" => $opacity
        ];

        if ($newState === $this->currentLineTransparency) {
            return;
        }

        $this->currentLineTransparency = $newState;

        $options = [
            "BM" => "/$mode",
            "CA" => $opacity
        ];

        $this->setGraphicsState($options);
    }

    /**
     * Set current blend mode & opacity for filled objects.
     *
     * Valid blend modes are:
     *
     * Normal, Multiply, Screen, Overlay, Darken, Lighten,
     * ColorDogde, ColorBurn, HardLight, SoftLight, Difference,
     * Exclusion
     *
     * @param string $mode    The blend mode to use
     * @param float  $opacity 0.0 fully transparent, 1.0 fully opaque
     */
    public function setFillTransparency(string $mode, float $opacity): void
    {
        static $blendModes = [
            "Normal",
            "Multiply",
            "Screen",
            "Overlay",
            "Darken",
            "Lighten",
            "ColorDogde",
            "ColorBurn",
            "HardLight",
            "SoftLight",
            "Difference",
            "Exclusion"
        ];

        if (!in_array($mode, $blendModes, true)) {
            $mode = "Normal";
        }

        $newState = [
            "mode"    => $mode,
            "opacity" => $opacity
        ];

        if ($newState === $this->currentFillTransparency) {
            return;
        }

        $this->currentFillTransparency = $newState;

        $options = [
            "BM" => "/$mode",
            "ca" => $opacity,
        ];

        $this->setGraphicsState($options);
    }

    /**
     * draw a line from one set of coordinates to another
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param bool  $stroke
     */
    function line($x1, $y1, $x2, $y2, $stroke = true)
    {
        $this->addContent(sprintf("\n%.3F %.3F m %.3F %.3F l", $x1, $y1, $x2, $y2));

        if ($stroke) {
            $this->addContent(' S');
        }
    }

    /**
     * draw a bezier curve based on 4 control points
     *
     * @param float $x0
     * @param float $y0
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param float $x3
     * @param float $y3
     */
    function curve($x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3)
    {
        // in the current line style, draw a bezier curve from (x0,y0) to (x3,y3) using the other two points
        // as the control points for the curve.
        $this->addContent(
            sprintf("\n%.3F %.3F m %.3F %.3F %.3F %.3F %.3F %.3F c S", $x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3)
        );
    }

    /**
     * draw a part of an ellipse
     *
     * @param float $x0
     * @param float $y0
     * @param float $astart
     * @param float $afinish
     * @param float $r1
     * @param float $r2
     * @param float $angle
     * @param int $nSeg
     */
    function partEllipse($x0, $y0, $astart, $afinish, $r1, $r2 = 0, $angle = 0, $nSeg = 8)
    {
        $this->ellipse($x0, $y0, $r1, $r2, $angle, $nSeg, $astart, $afinish, false);
    }

    /**
     * draw a filled ellipse
     *
     * @param float $x0
     * @param float $y0
     * @param float $r1
     * @param float $r2
     * @param float $angle
     * @param int $nSeg
     * @param float $astart
     * @param float $afinish
     */
    function filledEllipse($x0, $y0, $r1, $r2 = 0, $angle = 0, $nSeg = 8, $astart = 0, $afinish = 360)
    {
        $this->ellipse($x0, $y0, $r1, $r2, $angle, $nSeg, $astart, $afinish, true, true);
    }

    /**
     * @param float $x
     * @param float $y
     */
    function lineTo($x, $y)
    {
        $this->addContent(sprintf("\n%.3F %.3F l", $x, $y));
    }

    /**
     * @param float $x
     * @param float $y
     */
    function moveTo($x, $y)
    {
        $this->addContent(sprintf("\n%.3F %.3F m", $x, $y));
    }

    /**
     * draw a bezier curve based on 4 control points
     *
     * @param float $x1
     * @param float $y1
     * @param float $x2
     * @param float $y2
     * @param float $x3
     * @param float $y3
     */
    function curveTo($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F %.3F %.3F c", $x1, $y1, $x2, $y2, $x3, $y3));
    }

    /**
     * draw a bezier curve based on 4 control points
     *
     * @param float $cpx
     * @param float $cpy
     * @param float $x
     * @param float $y
     */
    function quadTo($cpx, $cpy, $x, $y)
    {
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F v", $cpx, $cpy, $x, $y));
    }

    function closePath()
    {
        $this->addContent(' h');
    }

    function endPath()
    {
        $this->addContent(' n');
    }

    /**
     * draw an ellipse
     * note that the part and filled ellipse are just special cases of this function
     *
     * draws an ellipse in the current line style
     * centered at $x0,$y0, radii $r1,$r2
     * if $r2 is not set, then a circle is drawn
     * from $astart to $afinish, measured in degrees, running anti-clockwise from the right hand side of the ellipse.
     * nSeg is not allowed to be less than 2, as this will simply draw a line (and will even draw a
     * pretty crappy shape at 2, as we are approximating with bezier curves.
     *
     * @param float $x0
     * @param float $y0
     * @param float $r1
     * @param float $r2
     * @param float $angle
     * @param int   $nSeg
     * @param float $astart
     * @param float $afinish
     * @param bool  $close
     * @param bool  $fill
     * @param bool  $stroke
     * @param bool  $incomplete
     */
    function ellipse(
        $x0,
        $y0,
        $r1,
        $r2 = 0,
        $angle = 0,
        $nSeg = 8,
        $astart = 0,
        $afinish = 360,
        $close = true,
        $fill = false,
        $stroke = true,
        $incomplete = false
    ) {
        if ($r1 == 0) {
            return;
        }

        if ($r2 == 0) {
            $r2 = $r1;
        }

        if ($nSeg < 2) {
            $nSeg = 2;
        }

        $astart = deg2rad((float)$astart);
        $afinish = deg2rad((float)$afinish);
        $totalAngle = $afinish - $astart;

        $dt = $totalAngle / $nSeg;
        $dtm = $dt / 3;

        if ($angle != 0) {
            $a = -1 * deg2rad((float)$angle);

            $this->addContent(
                sprintf("\n q %.3F %.3F %.3F %.3F %.3F %.3F cm", cos($a), -sin($a), sin($a), cos($a), $x0, $y0)
            );

            $x0 = 0;
            $y0 = 0;
        }

        $t1 = $astart;
        $a0 = $x0 + $r1 * cos($t1);
        $b0 = $y0 + $r2 * sin($t1);
        $c0 = -$r1 * sin($t1);
        $d0 = $r2 * cos($t1);

        if (!$incomplete) {
            $this->addContent(sprintf("\n%.3F %.3F m ", $a0, $b0));
        }

        for ($i = 1; $i <= $nSeg; $i++) {
            // draw this bit of the total curve
            $t1 = $i * $dt + $astart;
            $a1 = $x0 + $r1 * cos($t1);
            $b1 = $y0 + $r2 * sin($t1);
            $c1 = -$r1 * sin($t1);
            $d1 = $r2 * cos($t1);

            $this->addContent(
                sprintf(
                    "\n%.3F %.3F %.3F %.3F %.3F %.3F c",
                    ($a0 + $c0 * $dtm),
                    ($b0 + $d0 * $dtm),
                    ($a1 - $c1 * $dtm),
                    ($b1 - $d1 * $dtm),
                    $a1,
                    $b1
                )
            );

            $a0 = $a1;
            $b0 = $b1;
            $c0 = $c1;
            $d0 = $d1;
        }

        if (!$incomplete) {
            if ($fill) {
                $this->addContent(' f');
            }

            if ($stroke) {
                if ($close) {
                    $this->addContent(' s'); // small 's' signifies closing the path as well
                } else {
                    $this->addContent(' S');
                }
            }
        }

        if ($angle != 0) {
            $this->addContent(' Q');
        }
    }

    /**
     * this sets the line drawing style.
     * width, is the thickness of the line in user units
     * cap is the type of cap to put on the line, values can be 'butt','round','square'
     *    where the diffference between 'square' and 'butt' is that 'square' projects a flat end past the
     *    end of the line.
     * join can be 'miter', 'round', 'bevel'
     * dash is an array which sets the dash pattern, is a series of length values, which are the lengths of the
     *   on and off dashes.
     *   (2) represents 2 on, 2 off, 2 on , 2 off ...
     *   (2,1) is 2 on, 1 off, 2 on, 1 off.. etc
     * phase is a modifier on the dash pattern which is used to shift the point at which the pattern starts.
     *
     * @param float  $width
     * @param string $cap
     * @param string $join
     * @param array  $dash
     * @param int    $phase
     */
    function setLineStyle($width = 1, $cap = '', $join = '', $dash = '', $phase = 0)
    {
        // this is quite inefficient in that it sets all the parameters whenever 1 is changed, but will fix another day
        $string = '';

        if ($width > 0) {
            $string .= "$width w";
        }

        $ca = ['butt' => 0, 'round' => 1, 'square' => 2];

        if (isset($ca[$cap])) {
            $string .= " $ca[$cap] J";
        }

        $ja = ['miter' => 0, 'round' => 1, 'bevel' => 2];

        if (isset($ja[$join])) {
            $string .= " $ja[$join] j";
        }

        if (is_array($dash)) {
            $string .= ' [ ' . implode(' ', $dash) . " ] $phase d";
        }

        if ($string === $this->currentLineStyle) {
            return;
        }

        $this->currentLineStyle = $string;
        $this->addContent("\n$string");
    }

    /**
     * draw a polygon, the syntax for this is similar to the GD polygon command
     *
     * @param float[] $p
     * @param bool    $fill
     */
    public function polygon(array $p, bool $fill = false): void
    {
        $this->addContent(sprintf("\n%.3F %.3F m ", $p[0], $p[1]));

        $n = count($p);
        for ($i = 2; $i < $n; $i = $i + 2) {
            $this->addContent(sprintf("%.3F %.3F l ", $p[$i], $p[$i + 1]));
        }

        if ($fill) {
            $this->addContent(' f');
        } else {
            $this->addContent(' S');
        }
    }

    /**
     * a filled rectangle, note that it is the width and height of the rectangle which are the secondary parameters, not
     * the coordinates of the upper-right corner
     *
     * @param float $x1
     * @param float $y1
     * @param float $width
     * @param float $height
     */
    function filledRectangle($x1, $y1, $width, $height)
    {
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F re f", $x1, $y1, $width, $height));
    }

    /**
     * draw a rectangle, note that it is the width and height of the rectangle which are the secondary parameters, not
     * the coordinates of the upper-right corner
     *
     * @param float $x1
     * @param float $y1
     * @param float $width
     * @param float $height
     */
    function rectangle($x1, $y1, $width, $height)
    {
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F re S", $x1, $y1, $width, $height));
    }

    /**
     * draw a rectangle, note that it is the width and height of the rectangle which are the secondary parameters, not
     * the coordinates of the upper-right corner
     *
     * @param float $x1
     * @param float $y1
     * @param float $width
     * @param float $height
     */
    function rect($x1, $y1, $width, $height)
    {
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F re", $x1, $y1, $width, $height));
    }

    function stroke(bool $close = false)
    {
        $this->addContent("\n" . ($close ? "s" : "S"));
    }

    function fill()
    {
        $this->addContent("\nf" . ($this->fillRule === "evenodd" ? "*" : ""));
    }

    function fillStroke(bool $close = false)
    {
        $this->addContent("\n" . ($close ? "b" : "B") . ($this->fillRule === "evenodd" ? "*" : ""));
    }

    /**
     * @param string $subtype
     * @param integer $x
     * @param integer $y
     * @param integer $w
     * @param integer $h
     * @return int
     */
    function addXObject($subtype, $x, $y, $w, $h)
    {
        $id = ++$this->numObj;
        $this->o_xobject($id, 'new', ['Subtype' => $subtype, 'bbox' => [$x, $y, $w, $h]]);
        return $id;
    }

    /**
     * @param integer $numXObject
     * @param string $type
     * @param array $options
     */
    function setXObjectResource($numXObject, $type, $options)
    {
        if (in_array($type, ['procset', 'font', 'xObject'])) {
            $this->o_xobject($numXObject, $type, $options);
        }
    }

    /**
     * add signature
     *
     * $fieldSigId = $cpdf->addFormField(Cpdf::ACROFORM_FIELD_SIG, 'Signature1', 0, 0, 0, 0, 0);
     *
     * $signatureId = $cpdf->addSignature([
     *   'signcert' => file_get_contents('dompdf.crt'),
     *   'privkey' => file_get_contents('dompdf.key'),
     *   'password' => 'password',
     *   'name' => 'DomPDF DEMO',
     *   'location' => 'Home',
     *   'reason' => 'First Form',
     *   'contactinfo' => 'info'
     * ]);
     * $cpdf->setFormFieldValue($fieldSigId, "$signatureId 0 R");
     *
     * @param string $signcert
     * @param string $privkey
     * @param string $password
     * @param string|null $name
     * @param string|null $location
     * @param string|null $reason
     * @param string|null $contactinfo
     * @return int
     */
    function addSignature($signcert, $privkey, $password = '', $name = null, $location = null, $reason = null, $contactinfo = null) {
        $sigId = ++$this->numObj;
        $this->o_sig($sigId, 'new', [
          'SignCert' => $signcert,
          'PrivKey' => $privkey,
          'Password' => $password,
          'Name' => $name,
          'Location' => $location,
          'Reason' => $reason,
          'ContactInfo' => $contactinfo
        ]);

        return $sigId;
    }

    /**
     * add field to form
     *
     * @param string $type ACROFORM_FIELD_*
     * @param string $name
     * @param $x0
     * @param $y0
     * @param $x1
     * @param $y1
     * @param integer $ff Field Flag ACROFORM_FIELD_*_*
     * @param float $size
     * @param array $color
     * @return int
     */
    public function addFormField($type, $name, $x0, $y0, $x1, $y1, $ff = 0, $size = 10.0, $color = [0, 0, 0])
    {
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        $color = implode(' ', $color) . ' rg';

        $currentFontNum = $this->currentFontNum;
        $font = array_filter(
            $this->objects[$this->currentNode]['info']['fonts'],
            function ($item) use ($currentFontNum) { return $item['fontNum'] == $currentFontNum; }
        );

        $this->o_acroform($this->acroFormId, 'font',
          ['objNum' => $font[0]['objNum'], 'fontNum' => $font[0]['fontNum']]);

        $fieldId = ++$this->numObj;
        $this->o_field($fieldId, 'new', [
          'rect' => [$x0, $y0, $x1, $y1],
          'F' => 4,
          'FT' => "/$type",
          'T' => $name,
          'Ff' => $ff,
          'pageid' => $this->currentPage,
          'da' => "$color /F$this->currentFontNum " . sprintf('%.1F Tf ', $size)
        ]);

        return $fieldId;
    }

    /**
     * set Field value
     *
     * @param integer $numFieldObj
     * @param string $value
     */
    public function setFormFieldValue($numFieldObj, $value)
    {
        $this->o_field($numFieldObj, 'set', ['value' => $value]);
    }

    /**
     * set Field value (reference)
     *
     * @param integer $numFieldObj
     * @param integer $numObj Object number
     */
    public function setFormFieldRefValue($numFieldObj, $numObj)
    {
        $this->o_field($numFieldObj, 'set', ['refvalue' => $numObj]);
    }

    /**
     * set Field Appearanc (reference)
     *
     * @param integer $numFieldObj
     * @param integer $normalNumObj
     * @param integer|null $rolloverNumObj
     * @param integer|null $downNumObj
     */
    public function setFormFieldAppearance($numFieldObj, $normalNumObj, $rolloverNumObj = null, $downNumObj = null)
    {
        $appearance['N'] = $normalNumObj;

        if ($rolloverNumObj !== null) {
            $appearance['R'] = $rolloverNumObj;
        }

        if ($downNumObj !== null) {
            $appearance['D'] = $downNumObj;
        }

        $this->o_field($numFieldObj, 'set', ['appearance' => $appearance]);
    }

    /**
     * set Choice Field option values
     *
     * @param integer $numFieldObj
     * @param array $value
     */
    public function setFormFieldOpt($numFieldObj, $value)
    {
        $this->o_field($numFieldObj, 'set', ['options' => $value]);
    }

    /**
     * add form to document
     *
     * @param integer $sigFlags
     * @param boolean $needAppearances
     */
    public function addForm($sigFlags = 0, $needAppearances = false)
    {
        $this->acroFormId = ++$this->numObj;
        $this->o_acroform($this->acroFormId, 'new', [
          'NeedAppearances' => $needAppearances ? 'true' : 'false',
          'SigFlags' => $sigFlags
        ]);
    }

    /**
     * save the current graphic state
     */
    function save()
    {
        $this->addContent("\nq");
    }

    /**
     * restore the last graphic state
     */
    function restore()
    {
        // Reset color and transparency caches, as any changes to the graphics
        // state since saving will be discarded
        $this->currentColor = null;
        $this->currentStrokeColor = null;
        $this->currentLineStyle = '';
        $this->currentLineTransparency = null;
        $this->currentFillTransparency = null;
        $this->addContent("\nQ");
    }

    /**
     * draw a clipping rectangle, all the elements added after this will be clipped
     *
     * @param float $x1
     * @param float $y1
     * @param float $width
     * @param float $height
     */
    function clippingRectangle($x1, $y1, $width, $height)
    {
        $this->save();
        $this->addContent(sprintf("\n%.3F %.3F %.3F %.3F re W n", $x1, $y1, $width, $height));
    }

    /**
     * draw a clipping rounded rectangle, all the elements added after this will be clipped
     *
     * @param float $x1
     * @param float $y1
     * @param float $w
     * @param float $h
     * @param float $rTL
     * @param float $rTR
     * @param float $rBR
     * @param float $rBL
     */
    function clippingRectangleRounded($x1, $y1, $w, $h, $rTL, $rTR, $rBR, $rBL)
    {
        $this->save();

        // start: top edge, left end
        $this->addContent(sprintf("\n%.3F %.3F m ", $x1, $y1 - $rTL + $h));

        // line: bottom edge, left end
        $this->addContent(sprintf("\n%.3F %.3F l ", $x1, $y1 + $rBL));

        // curve: bottom-left corner
        $this->ellipse($x1 + $rBL, $y1 + $rBL, $rBL, 0, 0, 8, 180, 270, false, false, false, true);

        // line: right edge, bottom end
        $this->addContent(sprintf("\n%.3F %.3F l ", $x1 + $w - $rBR, $y1));

        // curve: bottom-right corner
        $this->ellipse($x1 + $w - $rBR, $y1 + $rBR, $rBR, 0, 0, 8, 270, 360, false, false, false, true);

        // line: right edge, top end
        $this->addContent(sprintf("\n%.3F %.3F l ", $x1 + $w, $y1 + $h - $rTR));

        // curve: bottom-right corner
        $this->ellipse($x1 + $w - $rTR, $y1 + $h - $rTR, $rTR, 0, 0, 8, 0, 90, false, false, false, true);

        // line: bottom edge, right end
        $this->addContent(sprintf("\n%.3F %.3F l ", $x1 + $rTL, $y1 + $h));

        // curve: top-right corner
        $this->ellipse($x1 + $rTL, $y1 + $h - $rTL, $rTL, 0, 0, 8, 90, 180, false, false, false, true);

        // line: top edge, left end
        $this->addContent(sprintf("\n%.3F %.3F l ", $x1 + $rBL, $y1));

        // Close & clip
        $this->addContent(" W n");
    }

    /**
     * draw a clipping polygon, the syntax for this is similar to the GD polygon command
     *
     * @param float[] $p
     */
    public function clippingPolygon(array $p): void
    {
        $this->save();

        $this->addContent(sprintf("\n%.3F %.3F m ", $p[0], $p[1]));

        $n = count($p);
        for ($i = 2; $i < $n; $i = $i + 2) {
            $this->addContent(sprintf("%.3F %.3F l ", $p[$i], $p[$i + 1]));
        }

        $this->addContent("W n");
    }

    /**
     * ends the last clipping shape
     */
    function clippingEnd()
    {
        $this->restore();
    }

    /**
     * scale
     *
     * @param float $s_x scaling factor for width as percent
     * @param float $s_y scaling factor for height as percent
     * @param float $x   Origin abscissa
     * @param float $y   Origin ordinate
     */
    function scale($s_x, $s_y, $x, $y)
    {
        $y = $this->currentPageSize["height"] - $y;

        $tm = [
            $s_x,
            0,
            0,
            $s_y,
            $x * (1 - $s_x),
            $y * (1 - $s_y)
        ];

        $this->transform($tm);
    }

    /**
     * translate
     *
     * @param float $t_x movement to the right
     * @param float $t_y movement to the bottom
     */
    function translate($t_x, $t_y)
    {
        $tm = [
            1,
            0,
            0,
            1,
            $t_x,
            -$t_y
        ];

        $this->transform($tm);
    }

    /**
     * rotate
     *
     * @param float $angle angle in degrees for counter-clockwise rotation
     * @param float $x     Origin abscissa
     * @param float $y     Origin ordinate
     */
    function rotate($angle, $x, $y)
    {
        $y = $this->currentPageSize["height"] - $y;

        $a = deg2rad($angle);
        $cos_a = cos($a);
        $sin_a = sin($a);

        $tm = [
            $cos_a,
            -$sin_a,
            $sin_a,
            $cos_a,
            $x - $sin_a * $y - $cos_a * $x,
            $y - $cos_a * $y + $sin_a * $x,
        ];

        $this->transform($tm);
    }

    /**
     * skew
     *
     * @param float $angle_x
     * @param float $angle_y
     * @param float $x Origin abscissa
     * @param float $y Origin ordinate
     */
    function skew($angle_x, $angle_y, $x, $y)
    {
        $y = $this->currentPageSize["height"] - $y;

        $tan_x = tan(deg2rad($angle_x));
        $tan_y = tan(deg2rad($angle_y));

        $tm = [
            1,
            -$tan_y,
            -$tan_x,
            1,
            $tan_x * $y,
            $tan_y * $x,
        ];

        $this->transform($tm);
    }

    /**
     * apply graphic transformations
     *
     * @param array $tm transformation matrix
     */
    function transform($tm)
    {
        $this->addContent(vsprintf("\n %.3F %.3F %.3F %.3F %.3F %.3F cm", $tm));
    }

    /**
     * add a new page to the document
     * this also makes the new page the current active object
     *
     * @param int $insert
     * @param int $id
     * @param string $pos
     * @return int
     */
    function newPage($insert = 0, $id = 0, $pos = 'after')
    {
        // if there is a state saved, then go up the stack closing them
        // then on the new page, re-open them with the right setings

        if ($this->nStateStack) {
            for ($i = $this->nStateStack; $i >= 1; $i--) {
                $this->restoreState($i);
            }
        }

        $this->numObj++;

        if ($insert) {
            // the id from the ezPdf class is the id of the contents of the page, not the page object itself
            // query that object to find the parent
            $rid = $this->objects[$id]['onPage'];
            $opt = ['rid' => $rid, 'pos' => $pos];
            $this->o_page($this->numObj, 'new', $opt);
        } else {
            $this->o_page($this->numObj, 'new');
        }

        // if there is a stack saved, then put that onto the page
        if ($this->nStateStack) {
            for ($i = 1; $i <= $this->nStateStack; $i++) {
                $this->saveState($i);
            }
        }

        // and if there has been a stroke or fill color set, then transfer them
        if (isset($this->currentColor)) {
            $this->setColor($this->currentColor, true);
        }

        if (isset($this->currentStrokeColor)) {
            $this->setStrokeColor($this->currentStrokeColor, true);
        }

        // if there is a line style set, then put this in too
        if ($this->currentLineStyle !== '') {
            $this->addContent("\n$this->currentLineStyle");
        }

        // the call to the o_page object set currentContents to the present page, so this can be returned as the page id
        return $this->currentContents;
    }

    /**
     * Streams the PDF to the client.
     *
     * @param string $filename The filename to present to the client.
     * @param array $options Associative array: 'compress' => 1 or 0 (default 1); 'Attachment' => 1 or 0 (default 1).
     */
    function stream($filename = "document.pdf", $options = [])
    {
        if (headers_sent()) {
            die("Unable to stream pdf: headers already sent");
        }

        if (!isset($options["compress"])) $options["compress"] = true;
        if (!isset($options["Attachment"])) $options["Attachment"] = true;

        $debug = !$options['compress'];
        $tmp = ltrim($this->output($debug));

        header("Cache-Control: private");
        header("Content-Type: application/pdf");
        header("Content-Length: " . mb_strlen($tmp, "8bit"));

        $filename = str_replace(["\n", "'"], "", basename($filename, ".pdf")) . ".pdf";
        $attachment = $options["Attachment"] ? "attachment" : "inline";

        $encoding = mb_detect_encoding($filename);
        $fallbackfilename = mb_convert_encoding($filename, "ISO-8859-1", $encoding);
        $fallbackfilename = str_replace("\"", "", $fallbackfilename);
        $encodedfilename = rawurlencode($filename);

        $contentDisposition = "Content-Disposition: $attachment; filename=\"$fallbackfilename\"";
        if ($fallbackfilename !== $filename) {
            $contentDisposition .= "; filename*=UTF-8''$encodedfilename";
        }
        header($contentDisposition);

        echo $tmp;
        flush();
    }

    /**
     * return the height in units of the current font in the given size
     *
     * @param float $size
     *
     * @return float
     */
    public function getFontHeight(float $size): float
    {
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        $font = $this->fonts[$this->currentFont];

        // for the current font, and the given size, what is the height of the font in user units
        if (isset($font['Ascender']) && isset($font['Descender'])) {
            $h = $font['Ascender'] - $font['Descender'];
        } else {
            $h = $font['FontBBox'][3] - $font['FontBBox'][1];
        }

        // have to adjust by a font offset for Windows fonts.  unfortunately it looks like
        // the bounding box calculations are wrong and I don't know why.
        if (isset($font['FontHeightOffset'])) {
            // For CourierNew from Windows this needs to be -646 to match the
            // Adobe native Courier font.
            //
            // For FreeMono from GNU this needs to be -337 to match the
            // Courier font.
            //
            // Both have been added manually to the .afm and .ufm files.
            $h += (int)$font['FontHeightOffset'];
        }

        return $size * $h / 1000;
    }

    /**
     * @param float $size
     *
     * @return float
     */
    public function getFontXHeight(float $size): float
    {
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        $font = $this->fonts[$this->currentFont];

        // for the current font, and the given size, what is the height of the font in user units
        if (isset($font['XHeight'])) {
            $xh = $font['Ascender'] - $font['Descender'];
        } else {
            $xh = $this->getFontHeight($size) / 2;
        }

        return $size * $xh / 1000;
    }

    /**
     * return the font descender, this will normally return a negative number
     * if you add this number to the baseline, you get the level of the bottom of the font
     * it is in the pdf user units
     *
     * @param float $size
     *
     * @return float
     */
    public function getFontDescender(float $size): float
    {
        // note that this will most likely return a negative value
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        //$h = $this->fonts[$this->currentFont]['FontBBox'][1];
        $h = $this->fonts[$this->currentFont]['Descender'];

        return $size * $h / 1000;
    }

    /**
     * filter the text, this is applied to all text just before being inserted into the pdf document
     * it escapes the various things that need to be escaped, and so on
     *
     * @param $text
     * @param bool $bom
     * @param bool $convert_encoding
     * @return string
     */
    function filterText($text, $bom = true, $convert_encoding = true)
    {
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        if ($convert_encoding) {
            $cf = $this->currentFont;
            if (isset($this->fonts[$cf]) && $this->fonts[$cf]['isUnicode']) {
                $text = $this->utf8toUtf16BE($text, $bom);
            } else {
                //$text = html_entity_decode($text, ENT_QUOTES);
                $text = mb_convert_encoding($text, self::$targetEncoding, 'UTF-8');
            }
        } elseif ($bom) {
            $text = $this->utf8toUtf16BE($text, $bom);
        }

        // the chr(13) substitution fixes a bug seen in TCPDF (bug #1421290)
        return strtr($text, [')' => '\\)', '(' => '\\(', '\\' => '\\\\', chr(13) => '\r']);
    }

    /**
     * return array containing codepoints (UTF-8 character values) for the
     * string passed in.
     *
     * based on the excellent TCPDF code by Nicola Asuni and the
     * RFC for UTF-8 at http://www.faqs.org/rfcs/rfc3629.html
     *
     * @param string $text UTF-8 string to process
     * @return array UTF-8 codepoints array for the string
     */
    function utf8toCodePointsArray(&$text)
    {
        $length = mb_strlen($text, '8bit'); // http://www.php.net/manual/en/function.mb-strlen.php#77040
        $unicode = []; // array containing unicode values
        $bytes = []; // array containing single character byte sequences
        $numbytes = 1; // number of octets needed to represent the UTF-8 character

        for ($i = 0; $i < $length; $i++) {
            $c = ord($text[$i]); // get one string character at time
            if (count($bytes) === 0) { // get starting octect
                if ($c <= 0x7F) {
                    $unicode[] = $c; // use the character "as is" because is ASCII
                    $numbytes = 1;
                } elseif (($c >> 0x05) === 0x06) { // 2 bytes character (0x06 = 110 BIN)
                    $bytes[] = ($c - 0xC0) << 0x06;
                    $numbytes = 2;
                } elseif (($c >> 0x04) === 0x0E) { // 3 bytes character (0x0E = 1110 BIN)
                    $bytes[] = ($c - 0xE0) << 0x0C;
                    $numbytes = 3;
                } elseif (($c >> 0x03) === 0x1E) { // 4 bytes character (0x1E = 11110 BIN)
                    $bytes[] = ($c - 0xF0) << 0x12;
                    $numbytes = 4;
                } else {
                    // use replacement character for other invalid sequences
                    $unicode[] = 0xFFFD;
                    $bytes = [];
                    $numbytes = 1;
                }
            } elseif (($c >> 0x06) === 0x02) { // bytes 2, 3 and 4 must start with 0x02 = 10 BIN
                $bytes[] = $c - 0x80;
                if (count($bytes) === $numbytes) {
                    // compose UTF-8 bytes to a single unicode value
                    $c = $bytes[0];
                    for ($j = 1; $j < $numbytes; $j++) {
                        $c += ($bytes[$j] << (($numbytes - $j - 1) * 0x06));
                    }
                    if ((($c >= 0xD800) and ($c <= 0xDFFF)) or ($c >= 0x10FFFF)) {
                        // The definition of UTF-8 prohibits encoding character numbers between
                        // U+D800 and U+DFFF, which are reserved for use with the UTF-16
                        // encoding form (as surrogate pairs) and do not directly represent
                        // characters.
                        $unicode[] = 0xFFFD; // use replacement character
                    } else {
                        $unicode[] = $c; // add char to array
                    }
                    // reset data for next char
                    $bytes = [];
                    $numbytes = 1;
                }
            } else {
                // use replacement character for other invalid sequences
                $unicode[] = 0xFFFD;
                $bytes = [];
                $numbytes = 1;
            }
        }

        return $unicode;
    }

    /**
     * convert UTF-8 to UTF-16 with an additional byte order marker
     * at the front if required.
     *
     * based on the excellent TCPDF code by Nicola Asuni and the
     * RFC for UTF-8 at http://www.faqs.org/rfcs/rfc3629.html
     *
     * @param string  $text UTF-8 string to process
     * @param boolean $bom  whether to add the byte order marker
     * @return string UTF-16 result string
     */
    function utf8toUtf16BE(&$text, $bom = true)
    {
        $out = $bom ? "\xFE\xFF" : '';

        $unicode = $this->utf8toCodePointsArray($text);
        foreach ($unicode as $c) {
            if ($c === 0xFFFD) {
                $out .= "\xFF\xFD"; // replacement character
            } elseif ($c < 0x10000) {
                $out .= chr($c >> 0x08) . chr($c & 0xFF);
            } else {
                $c -= 0x10000;
                $w1 = 0xD800 | ($c >> 0x10);
                $w2 = 0xDC00 | ($c & 0x3FF);
                $out .= chr($w1 >> 0x08) . chr($w1 & 0xFF) . chr($w2 >> 0x08) . chr($w2 & 0xFF);
            }
        }

        return $out;
    }

    /**
     * given a start position and information about how text is to be laid out, calculate where
     * on the page the text will end
     *
     * @param $x
     * @param $y
     * @param $angle
     * @param $size
     * @param $wa
     * @param $text
     * @return array
     */
    private function getTextPosition($x, $y, $angle, $size, $wa, $text)
    {
        // given this information return an array containing x and y for the end position as elements 0 and 1
        $w = $this->getTextWidth($size, $text);

        // need to adjust for the number of spaces in this text
        $words = explode(' ', $text);
        $nspaces = count($words) - 1;
        $w += $wa * $nspaces;
        $a = deg2rad((float)$angle);

        return [cos($a) * $w + $x, -sin($a) * $w + $y];
    }

    /**
     * Callback method used by smallCaps
     *
     * @param array $matches
     *
     * @return string
     */
    function toUpper($matches)
    {
        return mb_strtoupper($matches[0]);
    }

    function concatMatches($matches)
    {
        $str = "";
        foreach ($matches as $match) {
            $str .= $match[0];
        }

        return $str;
    }

    /**
     * register text for font subsetting
     *
     * @param string $font
     * @param string $text
     */
    function registerText($font, $text)
    {
        if (!$this->isUnicode || in_array(mb_strtolower(basename($font)), self::$coreFonts)) {
            return;
        }

        if (!isset($this->stringSubsets[$font])) {
            $base_subset = "\u{fffd}\u{fffe}\u{ffff}"; // fffd => replacement character, fffe/ffff => not a character
            $this->stringSubsets[$font] = $this->utf8toCodePointsArray($base_subset);
        }

        $this->stringSubsets[$font] = array_unique(
            array_merge($this->stringSubsets[$font], $this->utf8toCodePointsArray($text))
        );
    }

    /**
     * add text to the document, at a specified location, size and angle on the page
     *
     * @param float  $x
     * @param float  $y
     * @param float  $size
     * @param string $text
     * @param float  $angle
     * @param float  $wordSpaceAdjust
     * @param float  $charSpaceAdjust
     * @param bool   $smallCaps
     */
    function addText($x, $y, $size, $text, $angle = 0, $wordSpaceAdjust = 0, $charSpaceAdjust = 0, $smallCaps = false)
    {
        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        $text = str_replace(["\r", "\n"], "", $text);

        // if ($smallCaps) {
        //     preg_match_all("/(\P{Ll}+)/u", $text, $matches, PREG_SET_ORDER);
        //     $lower = $this->concatMatches($matches);
        //     d($lower);

        //     preg_match_all("/(\p{Ll}+)/u", $text, $matches, PREG_SET_ORDER);
        //     $other = $this->concatMatches($matches);
        //     d($other);

        //     $text = preg_replace_callback("/\p{Ll}/u", array($this, "toUpper"), $text);
        // }

        // if there are any open callbacks, then they should be called, to show the start of the line
        if ($this->nCallback > 0) {
            for ($i = $this->nCallback; $i > 0; $i--) {
                // call each function
                $info = [
                    'x'         => $x,
                    'y'         => $y,
                    'angle'     => $angle,
                    'status'    => 'sol',
                    'p'         => $this->callback[$i]['p'],
                    'nCallback' => $this->callback[$i]['nCallback'],
                    'height'    => $this->callback[$i]['height'],
                    'descender' => $this->callback[$i]['descender']
                ];

                $func = $this->callback[$i]['f'];
                $this->$func($info);
            }
        }

        if ($angle == 0) {
            $this->addContent(sprintf("\nBT %.3F %.3F Td", $x, $y));
        } else {
            $a = deg2rad((float)$angle);
            $this->addContent(
                sprintf("\nBT %.3F %.3F %.3F %.3F %.3F %.3F Tm", cos($a), -sin($a), sin($a), cos($a), $x, $y)
            );
        }

        if ($wordSpaceAdjust != 0) {
            $this->addContent(sprintf(" %.3F Tw", $wordSpaceAdjust));
        }

        if ($charSpaceAdjust != 0) {
            $this->addContent(sprintf(" %.3F Tc", $charSpaceAdjust));
        }

        $len = mb_strlen($text);
        $start = 0;

        if ($start < $len) {
            $part = $text; // OAR - Don't need this anymore, given that $start always equals zero.  substr($text, $start);
            $place_text = $this->filterText($part, false);
            // modify unicode text so that extra word spacing is manually implemented (bug #)
            if ($this->fonts[$this->currentFont]['isUnicode'] && $wordSpaceAdjust != 0) {
                $space_scale = 1000 / $size;
                $place_text = str_replace("\x00\x20", "\x00\x20)\x00\x20" . (-round($space_scale * $wordSpaceAdjust)) . "\x00\x20(", $place_text);
            }
            $this->addContent(" /F$this->currentFontNum " . sprintf('%.1F Tf ', $size));
            $this->addContent(" [($place_text)] TJ");
        }

        if ($wordSpaceAdjust != 0) {
            $this->addContent(sprintf(" %.3F Tw", 0));
        }

        if ($charSpaceAdjust != 0) {
            $this->addContent(sprintf(" %.3F Tc", 0));
        }

        $this->addContent(' ET');

        // if there are any open callbacks, then they should be called, to show the end of the line
        if ($this->nCallback > 0) {
            for ($i = $this->nCallback; $i > 0; $i--) {
                // call each function
                $tmp = $this->getTextPosition($x, $y, $angle, $size, $wordSpaceAdjust, $text);
                $info = [
                    'x'         => $tmp[0],
                    'y'         => $tmp[1],
                    'angle'     => $angle,
                    'status'    => 'eol',
                    'p'         => $this->callback[$i]['p'],
                    'nCallback' => $this->callback[$i]['nCallback'],
                    'height'    => $this->callback[$i]['height'],
                    'descender' => $this->callback[$i]['descender']
                ];
                $func = $this->callback[$i]['f'];
                $this->$func($info);
            }
        }

        if ($this->fonts[$this->currentFont]['isSubsetting']) {
            $this->registerText($this->currentFont, $text);
        }
    }

    /**
     * calculate how wide a given text string will be on a page, at a given size.
     * this can be called externally, but is also used by the other class functions
     *
     * @param float  $size
     * @param string $text
     * @param float  $wordSpacing
     * @param float  $charSpacing
     *
     * @return float
     */
    public function getTextWidth(float $size, string $text, float $wordSpacing = 0.0, float $charSpacing = 0.0): float
    {
        static $ord_cache = [];

        // this function should not change any of the settings, though it will need to
        // track any directives which change during calculation, so copy them at the start
        // and put them back at the end.
        $store_currentTextState = $this->currentTextState;

        if (!$this->numFonts) {
            $this->selectFont($this->defaultFont);
        }

        // remove non-printable characters since they have no width
        $text = preg_replace('/[\x00-\x1F\x7F]/u', '', $text);

        // hmm, this is where it all starts to get tricky - use the font information to
        // calculate the width of each character, add them up and convert to user units
        $w = 0;
        $cf = $this->currentFont;
        $current_font = $this->fonts[$cf];
        $space_scale = 1000 / ($size > 0 ? $size : 1);

        if ($current_font['isUnicode']) {
            // for Unicode, use the code points array to calculate width rather
            // than just the string itself
            $unicode = $this->utf8toCodePointsArray($text);

            foreach ($unicode as $char) {
                // check if we have to replace character
                if (isset($current_font['differences'][$char])) {
                    $char = $current_font['differences'][$char];
                }

                if (isset($current_font['C'][$char])) {
                    $char_width = $current_font['C'][$char];
                } elseif (isset($current_font['C'][0xFFFD])) {
                    // fffd => replacement character
                    $char_width = $current_font['C'][0xFFFD];
                } else {
                    $char_width = $current_font['C'][0x0020];
                }

                // add the character width
                $w += $char_width;

                // add additional padding for space
                if (isset($current_font['codeToName'][$char]) && $current_font['codeToName'][$char] === 'space') {  // Space
                    $w += $wordSpacing * $space_scale;
                }
            }

            // add additional char spacing
            if ($charSpacing != 0) {
                $w += $charSpacing * $space_scale * count($unicode);
            }

        } else {
            // If CPDF is in Unicode mode but the current font does not support Unicode we need to convert the character set to Windows-1252
            if ($this->isUnicode) {
                $text = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');
            }

            $len = mb_strlen($text, 'Windows-1252');

            for ($i = 0; $i < $len; $i++) {
                $c = $text[$i];
                $char = isset($ord_cache[$c]) ? $ord_cache[$c] : ($ord_cache[$c] = ord($c));

                // check if we have to replace character
                if (isset($current_font['differences'][$char])) {
                    $char = $current_font['differences'][$char];
                }

                if (isset($current_font['C'][$char])) {
                    $char_width = $current_font['C'][$char];
                } elseif (isset($current_font['C'][0xFFFD])) {
                    // fffd => replacement character
                    $char_width = $current_font['C'][0xFFFD];
                } else {
                    $char_width = $current_font['C'][0x0020];
                }

                // add the character width
                $w += $char_width;

                // add additional padding for space
                if (isset($current_font['codeToName'][$char]) && $current_font['codeToName'][$char] === 'space') {  // Space
                    $w += $wordSpacing * $space_scale;
                }
            }

            // add additional char spacing
            if ($charSpacing != 0) {
                $w += $charSpacing * $space_scale * $len;
            }
        }

        $this->currentTextState = $store_currentTextState;
        $this->setCurrentFont();

        return $w * $size / 1000;
    }

    /**
     * this will be called at a new page to return the state to what it was on the
     * end of the previous page, before the stack was closed down
     * This is to get around not being able to have open 'q' across pages
     *
     * @param int $pageEnd
     */
    function saveState($pageEnd = 0)
    {
        if ($pageEnd) {
            // this will be called at a new page to return the state to what it was on the
            // end of the previous page, before the stack was closed down
            // This is to get around not being able to have open 'q' across pages
            $opt = $this->stateStack[$pageEnd];
            // ok to use this as stack starts numbering at 1
            $this->setColor($opt['col'], true);
            $this->setStrokeColor($opt['str'], true);
            $this->addContent("\n" . $opt['lin']);
            //    $this->currentLineStyle = $opt['lin'];
        } else {
            $this->nStateStack++;
            $this->stateStack[$this->nStateStack] = [
                'col' => $this->currentColor,
                'str' => $this->currentStrokeColor,
                'lin' => $this->currentLineStyle
            ];
        }

        $this->save();
    }

    /**
     * restore a previously saved state
     *
     * @param int $pageEnd
     */
    function restoreState($pageEnd = 0)
    {
        if (!$pageEnd) {
            $n = $this->nStateStack;
            $this->currentColor = $this->stateStack[$n]['col'];
            $this->currentStrokeColor = $this->stateStack[$n]['str'];
            $this->addContent("\n" . $this->stateStack[$n]['lin']);
            $this->currentLineStyle = $this->stateStack[$n]['lin'];
            $this->stateStack[$n] = null;
            unset($this->stateStack[$n]);
            $this->nStateStack--;
        }

        $this->restore();
    }

    /**
     * make a loose object, the output will go into this object, until it is closed, then will revert to
     * the current one.
     * this object will not appear until it is included within a page.
     * the function will return the object number
     *
     * @return int
     */
    function openObject()
    {
        $this->nStack++;
        $this->stack[$this->nStack] = ['c' => $this->currentContents, 'p' => $this->currentPage];
        // add a new object of the content type, to hold the data flow
        $this->numObj++;
        $this->o_contents($this->numObj, 'new');
        $this->currentContents = $this->numObj;
        $this->looseObjects[$this->numObj] = 1;

        return $this->numObj;
    }

    /**
     * open an existing object for editing
     *
     * @param $id
     */
    function reopenObject($id)
    {
        $this->nStack++;
        $this->stack[$this->nStack] = ['c' => $this->currentContents, 'p' => $this->currentPage];
        $this->currentContents = $id;

        // also if this object is the primary contents for a page, then set the current page to its parent
        if (isset($this->objects[$id]['onPage'])) {
            $this->currentPage = $this->objects[$id]['onPage'];
        }
    }

    /**
     * close an object
     */
    function closeObject()
    {
        // close the object, as long as there was one open in the first place, which will be indicated by
        // an objectId on the stack.
        if ($this->nStack > 0) {
            $this->currentContents = $this->stack[$this->nStack]['c'];
            $this->currentPage = $this->stack[$this->nStack]['p'];
            $this->nStack--;
            // easier to probably not worry about removing the old entries, they will be overwritten
            // if there are new ones.
        }
    }

    /**
     * stop an object from appearing on pages from this point on
     *
     * @param $id
     */
    function stopObject($id)
    {
        // if an object has been appearing on pages up to now, then stop it, this page will
        // be the last one that could contain it.
        if (isset($this->addLooseObjects[$id])) {
            $this->addLooseObjects[$id] = '';
        }
    }

    /**
     * after an object has been created, it wil only show if it has been added, using this function.
     *
     * @param $id
     * @param string $options
     */
    function addObject($id, $options = 'add')
    {
        // add the specified object to the page
        if (isset($this->looseObjects[$id]) && $this->currentContents != $id) {
            // then it is a valid object, and it is not being added to itself
            switch ($options) {
                case 'all':
                    // then this object is to be added to this page (done in the next block) and
                    // all future new pages.
                    $this->addLooseObjects[$id] = 'all';

                case 'add':
                    if (isset($this->objects[$this->currentContents]['onPage'])) {
                        // then the destination contents is the primary for the page
                        // (though this object is actually added to that page)
                        $this->o_page($this->objects[$this->currentContents]['onPage'], 'content', $id);
                    }
                    break;

                case 'even':
                    $this->addLooseObjects[$id] = 'even';
                    $pageObjectId = $this->objects[$this->currentContents]['onPage'];
                    if ($this->objects[$pageObjectId]['info']['pageNum'] % 2 == 0) {
                        $this->addObject($id);
                        // hacky huh :)
                    }
                    break;

                case 'odd':
                    $this->addLooseObjects[$id] = 'odd';
                    $pageObjectId = $this->objects[$this->currentContents]['onPage'];
                    if ($this->objects[$pageObjectId]['info']['pageNum'] % 2 == 1) {
                        $this->addObject($id);
                        // hacky huh :)
                    }
                    break;

                case 'next':
                    $this->addLooseObjects[$id] = 'all';
                    break;

                case 'nexteven':
                    $this->addLooseObjects[$id] = 'even';
                    break;

                case 'nextodd':
                    $this->addLooseObjects[$id] = 'odd';
                    break;
            }
        }
    }

    /**
     * return a storable representation of a specific object
     *
     * @param $id
     * @return string|null
     */
    function serializeObject($id)
    {
        if (array_key_exists($id, $this->objects)) {
            return serialize($this->objects[$id]);
        }

        return null;
    }

    /**
     * restore an object from its stored representation. Returns its new object id.
     *
     * @param $obj
     * @return int
     */
    function restoreSerializedObject($obj)
    {
        $obj_id = $this->openObject();
        $this->objects[$obj_id] = unserialize($obj);
        $this->closeObject();

        return $obj_id;
    }

    /**
     * Embeds a file inside the PDF
     *
     * @param string $filepath path to the file to store inside the PDF
     * @param string $embeddedFilename the filename displayed in the list of embedded files
     * @param string $description a description in the list of embedded files
     */
    public function addEmbeddedFile(string $filepath, string $embeddedFilename, string $description): void
    {
        $this->numObj++;
        $this->o_embedded_file_dictionary(
            $this->numObj,
            'new',
            [
                'filepath' => $filepath,
                'filename' => $embeddedFilename,
                'description' => $description
            ]
        );
    }

    /**
     * Add content to the documents info object
     *
     * @param string|array $label
     * @param string       $value
     */
    public function addInfo($label, string $value = ""): void
    {
        // this will only work if the label is one of the valid ones.
        // modify this so that arrays can be passed as well.
        // if $label is an array then assume that it is key => value pairs
        // else assume that they are both scalar, anything else will probably error
        if (is_array($label)) {
            foreach ($label as $l => $v) {
                $this->o_info($this->infoObject, $l, (string) $v);
            }
        } else {
            $this->o_info($this->infoObject, $label, $value);
        }
    }

    /**
     * set the viewer preferences of the document, it is up to the browser to obey these.
     *
     * @param $label
     * @param int $value
     */
    function setPreferences($label, $value = 0)
    {
        // this will only work if the label is one of the valid ones.
        if (is_array($label)) {
            foreach ($label as $l => $v) {
                $this->o_catalog($this->catalogId, 'viewerPreferences', [$l => $v]);
            }
        } else {
            $this->o_catalog($this->catalogId, 'viewerPreferences', [$label => $value]);
        }
    }

    /**
     * extract an integer from a position in a byte stream
     *
     * @param $data
     * @param $pos
     * @param $num
     * @return int
     */
    private function getBytes(&$data, $pos, $num)
    {
        // return the integer represented by $num bytes from $pos within $data
        $ret = 0;
        for ($i = 0; $i < $num; $i++) {
            $ret *= 256;
            $ret += ord($data[$pos + $i]);
        }

        return $ret;
    }

    /**
     * Check if image already added to pdf image directory.
     * If yes, need not to create again (pass empty data)
     *
     * @param string $imgname
     * @return bool
     */
    function image_iscached($imgname)
    {
        return isset($this->imagelist[$imgname]);
    }

    /**
     * add a PNG image into the document, from a GD object
     * this should work with remote files
     *
     * @param \GdImage|resource $img A GD resource
     * @param string $file The PNG file
     * @param float $x X position
     * @param float $y Y position
     * @param float $w Width
     * @param float $h Height
     * @param bool $is_mask true if the image is a mask
     * @param bool $mask true if the image is masked
     * @throws Exception
     */
    function addImagePng(&$img, $file, $x, $y, $w = 0.0, $h = 0.0, $is_mask = false, $mask = null)
    {
        if (!function_exists("imagepng")) {
            throw new \Exception("The PHP GD extension is required, but is not installed.");
        }

        //if already cached, need not to read again
        if (isset($this->imagelist[$file])) {
            $data = null;
        } else {
            // Example for transparency handling on new image. Retain for current image
            // $tIndex = imagecolortransparent($img);
            // if ($tIndex > 0) {
            //   $tColor    = imagecolorsforindex($img, $tIndex);
            //   $new_tIndex    = imagecolorallocate($new_img, $tColor['red'], $tColor['green'], $tColor['blue']);
            //   imagefill($new_img, 0, 0, $new_tIndex);
            //   imagecolortransparent($new_img, $new_tIndex);
            // }
            // blending mode (literal/blending) on drawing into current image. not relevant when not saved or not drawn
            //imagealphablending($img, true);

            //default, but explicitely set to ensure pdf compatibility
            imagesavealpha($img, false/*!$is_mask && !$mask*/);

            $error = 0;
            //DEBUG_IMG_TEMP
            //debugpng
            if (defined("DEBUGPNG") && DEBUGPNG) {
                print '[addImagePng ' . $file . ']';
            }

            ob_start();
            @imagepng($img);
            $data = ob_get_clean();

            if ($data == '') {
                $error = 1;
                $errormsg = 'trouble writing file from GD';
                //DEBUG_IMG_TEMP
                //debugpng
                if (defined("DEBUGPNG") && DEBUGPNG) {
                    print 'trouble writing file from GD';
                }
            }

            if ($error) {
                $this->addMessage('PNG error - (' . $file . ') ' . $errormsg);

                return;
            }
        }  //End isset($this->imagelist[$file]) (png Duplicate removal)

        $this->addPngFromBuf($data, $file, $x, $y, $w, $h, $is_mask, $mask);
    }

    /**
     * @param $file
     * @param $x
     * @param $y
     * @param $w
     * @param $h
     * @param $byte
     */
    protected function addImagePngAlpha($file, $x, $y, $w, $h, $byte)
    {
        // generate images
        $img = @imagecreatefrompng($file);

        if ($img === false) {
            return;
        }

        // FIXME The pixel transformation doesn't work well with 8bit PNGs
        $eight_bit = ($byte & 4) !== 4;

        $wpx = imagesx($img);
        $hpx = imagesy($img);

        imagesavealpha($img, false);

        // create temp alpha file
        $tempfile_alpha = @tempnam($this->tmp, "cpdf_img_");
        @unlink($tempfile_alpha);
        $tempfile_alpha = "$tempfile_alpha.png";

        // create temp plain file
        $tempfile_plain = @tempnam($this->tmp, "cpdf_img_");
        @unlink($tempfile_plain);
        $tempfile_plain = "$tempfile_plain.png";

        $imgalpha = imagecreate($wpx, $hpx);
        imagesavealpha($imgalpha, false);

        // generate gray scale palette (0 -> 255)
        for ($c = 0; $c < 256; ++$c) {
            imagecolorallocate($imgalpha, $c, $c, $c);
        }

        // Use PECL gmagick + Graphics Magic to process transparent PNG images
        if (extension_loaded("gmagick")) {
            $gmagick = new \Gmagick($file);
            $gmagick->setimageformat('png');

            // Get opacity channel (negative of alpha channel)
            $alpha_channel_neg = clone $gmagick;
            $alpha_channel_neg->separateimagechannel(\Gmagick::CHANNEL_OPACITY);

            // Negate opacity channel
            $alpha_channel = new \Gmagick();
            $alpha_channel->newimage($wpx, $hpx, "#FFFFFF", "png");
            $alpha_channel->compositeimage($alpha_channel_neg, \Gmagick::COMPOSITE_DIFFERENCE, 0, 0);
            $alpha_channel->separateimagechannel(\Gmagick::CHANNEL_RED);
            $alpha_channel->writeimage($tempfile_alpha);

            // Cast to 8bit+palette
            $imgalpha_ = @imagecreatefrompng($tempfile_alpha);
            imagecopy($imgalpha, $imgalpha_, 0, 0, 0, 0, $wpx, $hpx);
            imagedestroy($imgalpha_);
            imagepng($imgalpha, $tempfile_alpha);

            // Make opaque image
            $color_channels = new \Gmagick();
            $color_channels->newimage($wpx, $hpx, "#FFFFFF", "png");
            $color_channels->compositeimage($gmagick, \Gmagick::COMPOSITE_COPYRED, 0, 0);
            $color_channels->compositeimage($gmagick, \Gmagick::COMPOSITE_COPYGREEN, 0, 0);
            $color_channels->compositeimage($gmagick, \Gmagick::COMPOSITE_COPYBLUE, 0, 0);
            $color_channels->writeimage($tempfile_plain);

            $imgplain = @imagecreatefrompng($tempfile_plain);
        }
        // Use PECL imagick + ImageMagic to process transparent PNG images
        elseif (extension_loaded("imagick")) {
            // Native cloning was added to pecl-imagick in svn commit 263814
            // the first version containing it was 3.0.1RC1
            static $imagickClonable = null;
            if ($imagickClonable === null) {
                $imagickClonable = true;
                if (defined('Imagick::IMAGICK_EXTVER')) {
                    $imagickVersion = \Imagick::IMAGICK_EXTVER;
                } else {
                    $imagickVersion = '0';
                }
                if (version_compare($imagickVersion, '0.0.1', '>=')) {
                    $imagickClonable = version_compare($imagickVersion, '3.0.1rc1', '>=');
                }
            }

            $imagick = new \Imagick();
            $imagick->setRegistry('temporary-path', $this->tmp);
            $imagick->setFormat('PNG');
            $imagick->readImage($file);

            // Get opacity channel (negative of alpha channel)
            if ($imagick->getImageAlphaChannel()) {
                $alpha_channel = $imagickClonable ? clone $imagick : $imagick->clone();
                $alpha_channel->separateImageChannel(\Imagick::CHANNEL_ALPHA);
                // Since ImageMagick7 negate invert transparency as default
                if (\Imagick::getVersion()['versionNumber'] < 1800) {
                    $alpha_channel->negateImage(true);
                }

                try {
                    $alpha_channel->writeImage($tempfile_alpha);
                } catch (\ImagickException $th) {
                    // Backwards compatible retry attempt in case the IMagick policy is still configured in lowercase
                    $alpha_channel->setFormat('png');
                    $alpha_channel->writeImage($tempfile_alpha);
                }

                // Cast to 8bit+palette
                $imgalpha_ = @imagecreatefrompng($tempfile_alpha);
                imagecopy($imgalpha, $imgalpha_, 0, 0, 0, 0, $wpx, $hpx);
                imagedestroy($imgalpha_);
                imagepng($imgalpha, $tempfile_alpha);
            } else {
                $tempfile_alpha = null;
            }

            // Make opaque image
            $color_channels = new \Imagick();
            $color_channels->setRegistry('temporary-path', $this->tmp);
            $color_channels->newImage($wpx, $hpx, "#FFFFFF", "png");
            $color_channels->compositeImage($imagick, \Imagick::COMPOSITE_COPYRED, 0, 0);
            $color_channels->compositeImage($imagick, \Imagick::COMPOSITE_COPYGREEN, 0, 0);
            $color_channels->compositeImage($imagick, \Imagick::COMPOSITE_COPYBLUE, 0, 0);
            $color_channels->writeImage($tempfile_plain);

            $imgplain = @imagecreatefrompng($tempfile_plain);
        } else {
            // allocated colors cache
            $allocated_colors = [];

            // extract alpha channel
            for ($xpx = 0; $xpx < $wpx; ++$xpx) {
                for ($ypx = 0; $ypx < $hpx; ++$ypx) {
                    $color = imagecolorat($img, $xpx, $ypx);
                    $col = imagecolorsforindex($img, $color);
                    $alpha = $col['alpha'];

                    if ($eight_bit) {
                        // with gamma correction
                        $gammacorr = 2.2;
                        $pixel = round(pow((((127 - $alpha) * 255 / 127) / 255), $gammacorr) * 255);
                    } else {
                        // without gamma correction
                        $pixel = (127 - $alpha) * 2;

                        $key = $col['red'] . $col['green'] . $col['blue'];

                        if (!isset($allocated_colors[$key])) {
                            $pixel_img = imagecolorallocate($img, $col['red'], $col['green'], $col['blue']);
                            $allocated_colors[$key] = $pixel_img;
                        } else {
                            $pixel_img = $allocated_colors[$key];
                        }

                        imagesetpixel($img, $xpx, $ypx, $pixel_img);
                    }

                    imagesetpixel($imgalpha, $xpx, $ypx, $pixel);
                }
            }

            // extract image without alpha channel
            $imgplain = imagecreatetruecolor($wpx, $hpx);
            imagecopy($imgplain, $img, 0, 0, 0, 0, $wpx, $hpx);
            imagedestroy($img);

            imagepng($imgalpha, $tempfile_alpha);
            imagepng($imgplain, $tempfile_plain);
        }

        $this->imageAlphaList[$file] = [$tempfile_alpha, $tempfile_plain];

        // embed mask image
        if ($tempfile_alpha) {
            $this->addImagePng($imgalpha, $tempfile_alpha, $x, $y, $w, $h, true);
            imagedestroy($imgalpha);
            $this->imageCache[] = $tempfile_alpha;
        }

        // embed image, masked with previously embedded mask
        $this->addImagePng($imgplain, $tempfile_plain, $x, $y, $w, $h, false, ($tempfile_alpha !== null));
        imagedestroy($imgplain);
        $this->imageCache[] = $tempfile_plain;
    }

    /**
     * add a PNG image into the document, from a file
     * this should work with remote files
     *
     * @param $file
     * @param $x
     * @param $y
     * @param int $w
     * @param int $h
     * @throws Exception
     */
    function addPngFromFile($file, $x, $y, $w = 0, $h = 0)
    {
        if (!function_exists("imagecreatefrompng")) {
            throw new \Exception("The PHP GD extension is required, but is not installed.");
        }

        if (isset($this->imageAlphaList[$file])) {
            [$alphaFile, $plainFile] = $this->imageAlphaList[$file];

            if ($alphaFile) {
                $img = null;
                $this->addImagePng($img, $alphaFile, $x, $y, $w, $h, true);
            }

            $img = null;
            $this->addImagePng($img, $plainFile, $x, $y, $w, $h, false, ($plainFile !== null));
            return;
        }

        //if already cached, need not to read again
        if (isset($this->imagelist[$file])) {
            $img = null;
        } else {
            $info = file_get_contents($file, false, null, 24, 5);
            $meta = unpack("CbitDepth/CcolorType/CcompressionMethod/CfilterMethod/CinterlaceMethod", $info);
            $bit_depth = $meta["bitDepth"];
            $color_type = $meta["colorType"];

            // http://www.w3.org/TR/PNG/#11IHDR
            // 3 => indexed
            // 4 => greyscale with alpha
            // 6 => fullcolor with alpha
            $is_alpha = in_array($color_type, [4, 6]) || ($color_type == 3 && $bit_depth != 4);

            if ($is_alpha) { // exclude grayscale alpha
                $this->addImagePngAlpha($file, $x, $y, $w, $h, $color_type);
                return;
            }

            //png files typically contain an alpha channel.
            //pdf file format or class.pdf does not support alpha blending.
            //on alpha blended images, more transparent areas have a color near black.
            //This appears in the result on not storing the alpha channel.
            //Correct would be the box background image or its parent when transparent.
            //But this would make the image dependent on the background.
            //Therefore create an image with white background and copy in
            //A more natural background than black is white.
            //Therefore create an empty image with white background and merge the
            //image in with alpha blending.
            $imgtmp = @imagecreatefrompng($file);
            if (!$imgtmp) {
                return;
            }
            $sx = imagesx($imgtmp);
            $sy = imagesy($imgtmp);
            $img = imagecreatetruecolor($sx, $sy);
            imagealphablending($img, true);

            // @todo is it still needed ??
            $ti = imagecolortransparent($imgtmp);
            if ($ti >= 0) {
                $tc = imagecolorsforindex($imgtmp, $ti);
                $ti = imagecolorallocate($img, $tc['red'], $tc['green'], $tc['blue']);
                imagefill($img, 0, 0, $ti);
                imagecolortransparent($img, $ti);
            } else {
                imagefill($img, 1, 1, imagecolorallocate($img, 255, 255, 255));
            }

            imagecopy($img, $imgtmp, 0, 0, 0, 0, $sx, $sy);
            imagedestroy($imgtmp);
        }
        $this->addImagePng($img, $file, $x, $y, $w, $h);

        if ($img) {
            imagedestroy($img);
        }
    }

    /**
     * add an SVG image into the document from a file
     *
     * @param $file
     * @param $x
     * @param $y
     * @param int $w
     * @param int $h
     */
    function addSvgFromFile($file, $x, $y, $w = 0, $h = 0)
    {
        $doc = new \Svg\Document();
        $doc->loadFile($file);
        $dimensions = $doc->getDimensions();

        $this->save();

        $this->transform([$w / $dimensions["width"], 0, 0, $h / $dimensions["height"], $x, $y]);

        $surface = new \Svg\Surface\SurfaceCpdf($doc, $this);
        $doc->render($surface);

        $this->restore();
    }

    /**
     * add a PNG image into the document, from a memory buffer of the file
     *
     * @param $data
     * @param $file
     * @param $x
     * @param $y
     * @param float $w
     * @param float $h
     * @param bool $is_mask
     * @param null $mask
     */
    function addPngFromBuf(&$data, $file, $x, $y, $w = 0.0, $h = 0.0, $is_mask = false, $mask = null)
    {
        if (isset($this->imagelist[$file])) {
            $data = null;
            $info['width'] = $this->imagelist[$file]['w'];
            $info['height'] = $this->imagelist[$file]['h'];
            $label = $this->imagelist[$file]['label'];
        } else {
            if ($data == null) {
                $this->addMessage('addPngFromBuf error - data not present!');

                return;
            }

            $error = 0;

            if (!$error) {
                $header = chr(137) . chr(80) . chr(78) . chr(71) . chr(13) . chr(10) . chr(26) . chr(10);

                if (mb_substr($data, 0, 8, '8bit') != $header) {
                    $error = 1;

                    if (defined("DEBUGPNG") && DEBUGPNG) {
                        print '[addPngFromFile this file does not have a valid header ' . $file . ']';
                    }

                    $errormsg = 'this file does not have a valid header';
                }
            }

            if (!$error) {
                // set pointer
                $p = 8;
                $len = mb_strlen($data, '8bit');

                // cycle through the file, identifying chunks
                $haveHeader = 0;
                $info = [];
                $idata = '';
                $pdata = '';

                while ($p < $len) {
                    $chunkLen = $this->getBytes($data, $p, 4);
                    $chunkType = mb_substr($data, $p + 4, 4, '8bit');

                    switch ($chunkType) {
                        case 'IHDR':
                            // this is where all the file information comes from
                            $info['width'] = $this->getBytes($data, $p + 8, 4);
                            $info['height'] = $this->getBytes($data, $p + 12, 4);
                            $info['bitDepth'] = ord($data[$p + 16]);
                            $info['colorType'] = ord($data[$p + 17]);
                            $info['compressionMethod'] = ord($data[$p + 18]);
                            $info['filterMethod'] = ord($data[$p + 19]);
                            $info['interlaceMethod'] = ord($data[$p + 20]);

                            //print_r($info);
                            $haveHeader = 1;
                            if ($info['compressionMethod'] != 0) {
                                $error = 1;

                                //debugpng
                                if (defined("DEBUGPNG") && DEBUGPNG) {
                                    print '[addPngFromFile unsupported compression method ' . $file . ']';
                                }

                                $errormsg = 'unsupported compression method';
                            }

                            if ($info['filterMethod'] != 0) {
                                $error = 1;

                                //debugpng
                                if (defined("DEBUGPNG") && DEBUGPNG) {
                                    print '[addPngFromFile unsupported filter method ' . $file . ']';
                                }

                                $errormsg = 'unsupported filter method';
                            }
                            break;

                        case 'PLTE':
                            $pdata .= mb_substr($data, $p + 8, $chunkLen, '8bit');
                            break;

                        case 'IDAT':
                            $idata .= mb_substr($data, $p + 8, $chunkLen, '8bit');
                            break;

                        case 'tRNS':
                            //this chunk can only occur once and it must occur after the PLTE chunk and before IDAT chunk
                            //print "tRNS found, color type = ".$info['colorType']."\n";
                            $transparency = [];

                            switch ($info['colorType']) {
                                // indexed color, rbg
                                case 3:
                                    /* corresponding to entries in the plte chunk
                                     Alpha for palette index 0: 1 byte
                                     Alpha for palette index 1: 1 byte
                                     ...etc...
                                    */
                                    // there will be one entry for each palette entry. up until the last non-opaque entry.
                                    // set up an array, stretching over all palette entries which will be o (opaque) or 1 (transparent)
                                    $transparency['type'] = 'indexed';
                                    $trans = 0;

                                    for ($i = $chunkLen; $i >= 0; $i--) {
                                        if (ord($data[$p + 8 + $i]) == 0) {
                                            $trans = $i;
                                        }
                                    }

                                    $transparency['data'] = $trans;
                                    break;

                                // grayscale
                                case 0:
                                    /* corresponding to entries in the plte chunk
                                     Gray: 2 bytes, range 0 .. (2^bitdepth)-1
                                    */
                                    //            $transparency['grayscale'] = $this->PRVT_getBytes($data,$p+8,2); // g = grayscale
                                    $transparency['type'] = 'indexed';
                                    $transparency['data'] = ord($data[$p + 8 + 1]);
                                    break;

                                // truecolor
                                case 2:
                                    /* corresponding to entries in the plte chunk
                                     Red: 2 bytes, range 0 .. (2^bitdepth)-1
                                     Green: 2 bytes, range 0 .. (2^bitdepth)-1
                                     Blue: 2 bytes, range 0 .. (2^bitdepth)-1
                                    */
                                    $transparency['r'] = $this->getBytes($data, $p + 8, 2);
                                    // r from truecolor
                                    $transparency['g'] = $this->getBytes($data, $p + 10, 2);
                                    // g from truecolor
                                    $transparency['b'] = $this->getBytes($data, $p + 12, 2);
                                    // b from truecolor

                                    $transparency['type'] = 'color-key';
                                    break;

                                //unsupported transparency type
                                default:
                                    if (defined("DEBUGPNG") && DEBUGPNG) {
                                        print '[addPngFromFile unsupported transparency type ' . $file . ']';
                                    }
                                    break;
                            }

                            // KS End new code
                            break;

                        default:
                            break;
                    }

                    $p += $chunkLen + 12;
                }

                if (!$haveHeader) {
                    $error = 1;

                    //debugpng
                    if (defined("DEBUGPNG") && DEBUGPNG) {
                        print '[addPngFromFile information header is missing ' . $file . ']';
                    }

                    $errormsg = 'information header is missing';
                }

                if (isset($info['interlaceMethod']) && $info['interlaceMethod']) {
                    $error = 1;

                    //debugpng
                    if (defined("DEBUGPNG") && DEBUGPNG) {
                        print '[addPngFromFile no support for interlaced images in pdf ' . $file . ']';
                    }

                    $errormsg = 'There appears to be no support for interlaced images in pdf.';
                }
            }

            if (!$error && $info['bitDepth'] > 8) {
                $error = 1;

                //debugpng
                if (defined("DEBUGPNG") && DEBUGPNG) {
                    print '[addPngFromFile bit depth of 8 or less is supported ' . $file . ']';
                }

                $errormsg = 'only bit depth of 8 or less is supported';
            }

            if (!$error) {
                switch ($info['colorType']) {
                    case 3:
                        $color = 'DeviceRGB';
                        $ncolor = 1;
                        break;

                    case 2:
                        $color = 'DeviceRGB';
                        $ncolor = 3;
                        break;

                    case 0:
                        $color = 'DeviceGray';
                        $ncolor = 1;
                        break;

                    default:
                        $error = 1;

                        //debugpng
                        if (defined("DEBUGPNG") && DEBUGPNG) {
                            print '[addPngFromFile alpha channel not supported: ' . $info['colorType'] . ' ' . $file . ']';
                        }

                        $errormsg = 'transparency alpha channel not supported, transparency only supported for palette images.';
                }
            }

            if ($error) {
                $this->addMessage('PNG error - (' . $file . ') ' . $errormsg);

                return;
            }

            //print_r($info);
            // so this image is ok... add it in.
            $this->numImages++;
            $im = $this->numImages;
            $label = "I$im";
            $this->numObj++;

            //  $this->o_image($this->numObj,'new',array('label' => $label,'data' => $idata,'iw' => $w,'ih' => $h,'type' => 'png','ic' => $info['width']));
            $options = [
                'label'            => $label,
                'data'             => $idata,
                'bitsPerComponent' => $info['bitDepth'],
                'pdata'            => $pdata,
                'iw'               => $info['width'],
                'ih'               => $info['height'],
                'type'             => 'png',
                'color'            => $color,
                'ncolor'           => $ncolor,
                'masked'           => $mask,
                'isMask'           => $is_mask
            ];

            if (isset($transparency)) {
                $options['transparency'] = $transparency;
            }

            $this->o_image($this->numObj, 'new', $options);
            $this->imagelist[$file] = ['label' => $label, 'w' => $info['width'], 'h' => $info['height']];
        }

        if ($is_mask) {
            return;
        }

        if ($w <= 0 && $h <= 0) {
            $w = $info['width'];
            $h = $info['height'];
        }

        if ($w <= 0) {
            $w = $h / $info['height'] * $info['width'];
        }

        if ($h <= 0) {
            $h = $w * $info['height'] / $info['width'];
        }

        $this->addContent(sprintf("\nq\n%.3F 0 0 %.3F %.3F %.3F cm /%s Do\nQ", $w, $h, $x, $y, $label));
    }

    /**
     * add a JPEG image into the document, from a file
     *
     * @param $img
     * @param $x
     * @param $y
     * @param int $w
     * @param int $h
     */
    function addJpegFromFile($img, $x, $y, $w = 0, $h = 0)
    {
        // attempt to add a jpeg image straight from a file, using no GD commands
        // note that this function is unable to operate on a remote file.

        if (substr($img, 0, 5) == 'data:') {
            $filename = 'data-' . hash('md4', $img);
        } else {
            if (!file_exists($img)) {
                return;
            }
            $filename = $img;
        }

        if ($this->image_iscached($filename)) {
            $data = null;
            $imageWidth = $this->imagelist[$filename]['w'];
            $imageHeight = $this->imagelist[$filename]['h'];
            $channels = $this->imagelist[$filename]['c'];
        } else {
            $tmp = getimagesize($img);
            $imageWidth = $tmp[0];
            $imageHeight = $tmp[1];

            if (isset($tmp['channels'])) {
                $channels = $tmp['channels'];
            } else {
                $channels = 3;
            }

            $data = file_get_contents($img);
        }

        if ($w <= 0 && $h <= 0) {
            $w = $imageWidth;
        }

        if ($w == 0) {
            $w = $h / $imageHeight * $imageWidth;
        }

        if ($h == 0) {
            $h = $w * $imageHeight / $imageWidth;
        }

        $this->addJpegImage_common($data, $filename, $imageWidth, $imageHeight, $x, $y, $w, $h, $channels);
    }

    /**
     * common code used by the two JPEG adding functions
     * @param $data
     * @param $imgname
     * @param $imageWidth
     * @param $imageHeight
     * @param $x
     * @param $y
     * @param int $w
     * @param int $h
     * @param int $channels
     */
    private function addJpegImage_common(
        &$data,
        $imgname,
        $imageWidth,
        $imageHeight,
        $x,
        $y,
        $w = 0,
        $h = 0,
        $channels = 3
    ) {
        if ($this->image_iscached($imgname)) {
            $label = $this->imagelist[$imgname]['label'];
            //debugpng
            //if (DEBUGPNG) print '[addJpegImage_common Duplicate '.$imgname.']';

        } else {
            if ($data == null) {
                $this->addMessage('addJpegImage_common error - (' . $imgname . ') data not present!');

                return;
            }

            // note that this function is not to be called externally
            // it is just the common code between the GD and the file options
            $this->numImages++;
            $im = $this->numImages;
            $label = "I$im";
            $this->numObj++;

            $this->o_image(
                $this->numObj,
                'new',
                [
                    'label'    => $label,
                    'data'     => &$data,
                    'iw'       => $imageWidth,
                    'ih'       => $imageHeight,
                    'channels' => $channels
                ]
            );

            $this->imagelist[$imgname] = [
                'label' => $label,
                'w'     => $imageWidth,
                'h'     => $imageHeight,
                'c'     => $channels
            ];
        }

        $this->addContent(sprintf("\nq\n%.3F 0 0 %.3F %.3F %.3F cm /%s Do\nQ ", $w, $h, $x, $y, $label));
    }

    /**
     * specify where the document should open when it first starts
     *
     * @param $style
     * @param int $a
     * @param int $b
     * @param int $c
     */
    function openHere($style, $a = 0, $b = 0, $c = 0)
    {
        // this function will open the document at a specified page, in a specified style
        // the values for style, and the required parameters are:
        // 'XYZ'  left, top, zoom
        // 'Fit'
        // 'FitH' top
        // 'FitV' left
        // 'FitR' left,bottom,right
        // 'FitB'
        // 'FitBH' top
        // 'FitBV' left
        $this->numObj++;
        $this->o_destination(
            $this->numObj,
            'new',
            ['page' => $this->currentPage, 'type' => $style, 'p1' => $a, 'p2' => $b, 'p3' => $c]
        );
        $id = $this->catalogId;
        $this->o_catalog($id, 'openHere', $this->numObj);
    }

    /**
     * Add JavaScript code to the PDF document
     *
     * @param string $code
     */
    function addJavascript($code)
    {
        $this->javascript .= $code;
    }

    /**
     * create a labelled destination within the document
     *
     * @param $label
     * @param $style
     * @param int $a
     * @param int $b
     * @param int $c
     */
    function addDestination($label, $style, $a = 0, $b = 0, $c = 0)
    {
        // associates the given label with the destination, it is done this way so that a destination can be specified after
        // it has been linked to
        // styles are the same as the 'openHere' function
        $this->numObj++;
        $this->o_destination(
            $this->numObj,
            'new',
            ['page' => $this->currentPage, 'type' => $style, 'p1' => $a, 'p2' => $b, 'p3' => $c]
        );
        $id = $this->numObj;

        // store the label->idf relationship, note that this means that labels can be used only once
        $this->destinations["$label"] = $id;
    }

    /**
     * define font families, this is used to initialize the font families for the default fonts
     * and for the user to add new ones for their fonts. The default bahavious can be overridden should
     * that be desired.
     *
     * @param $family
     * @param string $options
     */
    function setFontFamily($family, $options = '')
    {
        if (!is_array($options)) {
            if ($family === 'init') {
                // set the known family groups
                // these font families will be used to enable bold and italic markers to be included
                // within text streams. html forms will be used... <b></b> <i></i>
                $this->fontFamilies['Helvetica.afm'] =
                    [
                        'b'  => 'Helvetica-Bold.afm',
                        'i'  => 'Helvetica-Oblique.afm',
                        'bi' => 'Helvetica-BoldOblique.afm',
                        'ib' => 'Helvetica-BoldOblique.afm'
                    ];

                $this->fontFamilies['Courier.afm'] =
                    [
                        'b'  => 'Courier-Bold.afm',
                        'i'  => 'Courier-Oblique.afm',
                        'bi' => 'Courier-BoldOblique.afm',
                        'ib' => 'Courier-BoldOblique.afm'
                    ];

                $this->fontFamilies['Times-Roman.afm'] =
                    [
                        'b'  => 'Times-Bold.afm',
                        'i'  => 'Times-Italic.afm',
                        'bi' => 'Times-BoldItalic.afm',
                        'ib' => 'Times-BoldItalic.afm'
                    ];
            }
        } else {

            // the user is trying to set a font family
            // note that this can also be used to set the base ones to something else
            if (mb_strlen($family)) {
                $this->fontFamilies[$family] = $options;
            }
        }
    }

    /**
     * used to add messages for use in debugging
     *
     * @param $message
     */
    function addMessage($message)
    {
        $this->messages .= $message . "\n";
    }

    /**
     * a few functions which should allow the document to be treated transactionally.
     *
     * @param $action
     */
    function transaction($action)
    {
        switch ($action) {
            case 'start':
                // store all the data away into the checkpoint variable
                $data = get_object_vars($this);
                $this->checkpoint = $data;
                unset($data);
                break;

            case 'commit':
                if (is_array($this->checkpoint) && isset($this->checkpoint['checkpoint'])) {
                    $tmp = $this->checkpoint['checkpoint'];
                    $this->checkpoint = $tmp;
                    unset($tmp);
                } else {
                    $this->checkpoint = '';
                }
                break;

            case 'rewind':
                // do not destroy the current checkpoint, but move us back to the state then, so that we can try again
                if (is_array($this->checkpoint)) {
                    // can only abort if were inside a checkpoint
                    $tmp = $this->checkpoint;

                    foreach ($tmp as $k => $v) {
                        if ($k !== 'checkpoint') {
                            $this->$k = $v;
                        }
                    }
                    unset($tmp);
                }
                break;

            case 'abort':
                if (is_array($this->checkpoint)) {
                    // can only abort if were inside a checkpoint
                    $tmp = $this->checkpoint;
                    foreach ($tmp as $k => $v) {
                        $this->$k = $v;
                    }
                    unset($tmp);
                }
                break;
        }
    }
}
