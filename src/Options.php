<?php
/**
 * @package dompdf
 * @link    https://github.com/dompdf/dompdf
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf;

class Options
{
    /**
     * The root of your DOMPDF installation
     *
     * @var string
     */
    private $rootDir;

    /**
     * The location of a temporary directory.
     *
     * The directory specified must be writable by the executing process.
     * The temporary directory is required to download remote images and when
     * using the PFDLib back end.
     *
     * @var string
     */
    private $tempDir;

    /**
     * The location of the DOMPDF font directory
     *
     * The location of the directory where DOMPDF will store fonts and font metrics
     * Note: This directory must exist and be writable by the executing process.
     *
     * @var string
     */
    private $fontDir;

    /**
     * The location of the DOMPDF font cache directory
     *
     * This directory contains the cached font metrics for the fonts used by DOMPDF.
     * This directory can be the same as $fontDir
     *
     * Note: This directory must exist and be writable by the executing process.
     *
     * @var string
     */
    private $fontCache;

    /**
     * dompdf's "chroot"
     *
     * Utilized by Dompdf's default file:// protocol URI validation rule.
     * All local files opened by dompdf must be in a subdirectory of the directory
     * or directories specified by this option.
     * DO NOT set this value to '/' since this could allow an attacker to use dompdf to
     * read any files on the server.  This should be an absolute path.
     *
     * ==== IMPORTANT ====
     * This setting may increase the risk of system exploit. Do not change
     * this settings without understanding the consequences. Additional
     * documentation is available on the dompdf wiki at:
     * https://github.com/dompdf/dompdf/wiki
     *
     * @var array
     */
    private $chroot;

    /**
    * Protocol whitelist
    *
    * Protocols and PHP wrappers allowed in URIs, and the validation rules
    * that determine if a resouce may be loaded. Full support is not guaranteed
    * for the protocols/wrappers specified
    * by this array.
    *
    * @var array
    */
    private $allowedProtocols = [
        "file://" => ["rules" => []],
        "http://" => ["rules" => []],
        "https://" => ["rules" => []]
    ];

    /**
    * Operational artifact (log files, temporary files) path validation
    *
    * @var callable
    */
    private $artifactPathValidation = null;

    /**
     * @var string
     */
    private $logOutputFile;

    /**
     * Styles targeted to this media type are applied to the document.
     * This is on top of the media types that are always applied:
     *    all, static, visual, bitmap, paged, dompdf
     *
     * @var string
     */
    private $defaultMediaType = "screen";

    /**
     * The default paper size.
     *
     * North America standard is "letter"; other countries generally "a4"
     * @see \Dompdf\Adapter\CPDF::PAPER_SIZES for valid sizes
     *
     * @var string|float[]
     */
    private $defaultPaperSize = "letter";

    /**
     * The default paper orientation.
     *
     * The orientation of the page (portrait or landscape).
     *
     * @var string
     */
    private $defaultPaperOrientation = "portrait";

    /**
     * The default font family
     *
     * Used if no suitable fonts can be found. This must exist in the font folder.
     *
     * @var string
     */
    private $defaultFont = "serif";

    /**
     * Image DPI setting
     *
     * This setting determines the default DPI setting for images and fonts.  The
     * DPI may be overridden for inline images by explicitly setting the
     * image's width & height style attributes (i.e. if the image's native
     * width is 600 pixels and you specify the image's width as 72 points,
     * the image will have a DPI of 600 in the rendered PDF.  The DPI of
     * background images can not be overridden and is controlled entirely
     * via this parameter.
     *
     * For the purposes of DOMPDF, pixels per inch (PPI) = dots per inch (DPI).
     * If a size in html is given as px (or without unit as image size),
     * this tells the corresponding size in pt at 72 DPI.
     * This adjusts the relative sizes to be similar to the rendering of the
     * html page in a reference browser.
     *
     * In pdf, always 1 pt = 1/72 inch
     *
     * @var int
     */
    private $dpi = 96;

    /**
     * A ratio applied to the fonts height to be more like browsers' line height
     *
     * @var float
     */
    private $fontHeightRatio = 1.1;

    /**
     * Enable embedded PHP
     *
     * If this setting is set to true then DOMPDF will automatically evaluate
     * embedded PHP contained within <script type="text/php"> ... </script> tags.
     *
     * ==== IMPORTANT ====
     * Enabling this for documents you do not trust (e.g. arbitrary remote html
     * pages) is a security risk. Embedded scripts are run with the same level of
     * system access available to dompdf. Set this option to false (recommended)
     * if you wish to process untrusted documents.
     *
     * This setting may increase the risk of system exploit. Do not change
     * this settings without understanding the consequences. Additional
     * documentation is available on the dompdf wiki at:
     * https://github.com/dompdf/dompdf/wiki
     *
     * @var bool
     */
    private $isPhpEnabled = false;

    /**
     * Enable remote file access
     *
     * If this setting is set to true, DOMPDF will access remote sites for
     * images and CSS files as required.
     *
     * ==== IMPORTANT ====
     * This can be a security risk, in particular in combination with isPhpEnabled and
     * allowing remote html code to be passed to $dompdf = new DOMPDF(); $dompdf->load_html(...);
     * This allows anonymous users to download legally doubtful internet content which on
     * tracing back appears to being downloaded by your server, or allows malicious php code
     * in remote html pages to be executed by your server with your account privileges.
     *
     * This setting may increase the risk of system exploit. Do not change
     * this settings without understanding the consequences. Additional
     * documentation is available on the dompdf wiki at:
     * https://github.com/dompdf/dompdf/wiki
     *
     * @var bool
     */
    private $isRemoteEnabled = false;

    /**
     * List of allowed remote hosts
     *
     * Each value of the array must be a valid hostname.
     *
     * This will be used to filter which resources can be loaded in combination with
     * isRemoteEnabled. If isRemoteEnabled is FALSE, then this will have no effect.
     *
     * Leave to NULL to allow any remote host.
     *
     * @var array|null
     */
    private $allowedRemoteHosts = null;

    /**
     * Enable inline JavaScript
     *
     * If this setting is set to true then DOMPDF will automatically insert
     * JavaScript code contained within <script type="text/javascript"> ... </script>
     * tags as written into the PDF.
     *
     * NOTE: This is PDF-based JavaScript to be executed by the PDF viewer,
     * not browser-based JavaScript executed by Dompdf.
     *
     * @var bool
     */
    private $isJavascriptEnabled = true;

    /**
     * Use the HTML5 Lib parser
     *
     * @deprecated
     * @var bool
     */
    private $isHtml5ParserEnabled = true;

    /**
     * Whether to enable font subsetting or not.
     *
     * @var bool
     */
    private $isFontSubsettingEnabled = true;

    /**
     * @var bool
     */
    private $debugPng = false;

    /**
     * @var bool
     */
    private $debugKeepTemp = false;

    /**
     * @var bool
     */
    private $debugCss = false;

    /**
     * @var bool
     */
    private $debugLayout = false;

    /**
     * @var bool
     */
    private $debugLayoutLines = true;

    /**
     * @var bool
     */
    private $debugLayoutBlocks = true;

    /**
     * @var bool
     */
    private $debugLayoutInline = true;

    /**
     * @var bool
     */
    private $debugLayoutPaddingBox = true;

    /**
     * The PDF rendering backend to use
     *
     * Valid settings are 'PDFLib', 'CPDF', 'GD', and 'auto'. 'auto' will
     * look for PDFLib and use it if found, or if not it will fall back on
     * CPDF. 'GD' renders PDFs to graphic files. {@link Dompdf\CanvasFactory}
     * ultimately determines which rendering class to instantiate
     * based on this setting.
     *
     * @var string
     */
    private $pdfBackend = "CPDF";

    /**
     * PDFlib license key
     *
     * If you are using a licensed, commercial version of PDFlib, specify
     * your license key here.  If you are using PDFlib-Lite or are evaluating
     * the commercial version of PDFlib, comment out this setting.
     *
     * @link http://www.pdflib.com
     *
     * If pdflib present in web server and auto or selected explicitly above,
     * a real license code must exist!
     *
     * @var string
     */
    private $pdflibLicense = "";

    /**
     * HTTP context created with stream_context_create()
     * Will be used for file_get_contents
     *
     * @link https://www.php.net/manual/context.php
     *
     * @var resource
     */
    private $httpContext;

    /**
     * @param array $attributes
     */
    public function __construct(array $attributes = null)
    {
        $rootDir = realpath(__DIR__ . "/../");
        $this->setChroot(array($rootDir));
        $this->setRootDir($rootDir);
        $this->setTempDir(sys_get_temp_dir());
        $this->setFontDir($rootDir . "/lib/fonts");
        $this->setFontCache($this->getFontDir());

        $ver = "";
        $versionFile = realpath(__DIR__ . '/../VERSION');
        if (($version = file_get_contents($versionFile)) !== false) {
            $version = trim($version);
            if ($version !== '$Format:<%h>$') {
                $ver = "/$version";
            }
        }
        $this->setHttpContext([
            "http" => [
                "follow_location" => false,
                "user_agent" => "Dompdf$ver https://github.com/dompdf/dompdf"
            ]
        ]);

        $this->setAllowedProtocols(["file://", "http://", "https://"]);

        $this->setArtifactPathValidation([$this, "validateArtifactPath"]);

        if (null !== $attributes) {
            $this->set($attributes);
        }
    }

    /**
     * @param array|string $attributes
     * @param null|mixed $value
     * @return $this
     */
    public function set($attributes, $value = null)
    {
        if (!is_array($attributes)) {
            $attributes = [$attributes => $value];
        }
        foreach ($attributes as $key => $value) {
            if ($key === 'tempDir' || $key === 'temp_dir') {
                $this->setTempDir($value);
            } elseif ($key === 'fontDir' || $key === 'font_dir') {
                $this->setFontDir($value);
            } elseif ($key === 'fontCache' || $key === 'font_cache') {
                $this->setFontCache($value);
            } elseif ($key === 'chroot') {
                $this->setChroot($value);
            } elseif ($key === 'allowedProtocols' || $key === 'allowed_protocols') {
                $this->setAllowedProtocols($value);
            } elseif ($key === 'artifactPathValidation') {
                $this->setArtifactPathValidation($value);
            } elseif ($key === 'logOutputFile' || $key === 'log_output_file') {
                $this->setLogOutputFile($value);
            } elseif ($key === 'defaultMediaType' || $key === 'default_media_type') {
                $this->setDefaultMediaType($value);
            } elseif ($key === 'defaultPaperSize' || $key === 'default_paper_size') {
                $this->setDefaultPaperSize($value);
            } elseif ($key === 'defaultPaperOrientation' || $key === 'default_paper_orientation') {
                $this->setDefaultPaperOrientation($value);
            } elseif ($key === 'defaultFont' || $key === 'default_font') {
                $this->setDefaultFont($value);
            } elseif ($key === 'dpi') {
                $this->setDpi($value);
            } elseif ($key === 'fontHeightRatio' || $key === 'font_height_ratio') {
                $this->setFontHeightRatio($value);
            } elseif ($key === 'isPhpEnabled' || $key === 'is_php_enabled' || $key === 'enable_php') {
                $this->setIsPhpEnabled($value);
            } elseif ($key === 'isRemoteEnabled' || $key === 'is_remote_enabled' || $key === 'enable_remote') {
                $this->setIsRemoteEnabled($value);
            } elseif ($key === 'allowedRemoteHosts' || $key === 'allowed_remote_hosts') {
                $this->setAllowedRemoteHosts($value);
            } elseif ($key === 'isJavascriptEnabled' || $key === 'is_javascript_enabled' || $key === 'enable_javascript') {
                $this->setIsJavascriptEnabled($value);
            } elseif ($key === 'isHtml5ParserEnabled' || $key === 'is_html5_parser_enabled' || $key === 'enable_html5_parser') {
                $this->setIsHtml5ParserEnabled($value);
            } elseif ($key === 'isFontSubsettingEnabled' || $key === 'is_font_subsetting_enabled' || $key === 'enable_font_subsetting') {
                $this->setIsFontSubsettingEnabled($value);
            } elseif ($key === 'debugPng' || $key === 'debug_png') {
                $this->setDebugPng($value);
            } elseif ($key === 'debugKeepTemp' || $key === 'debug_keep_temp') {
                $this->setDebugKeepTemp($value);
            } elseif ($key === 'debugCss' || $key === 'debug_css') {
                $this->setDebugCss($value);
            } elseif ($key === 'debugLayout' || $key === 'debug_layout') {
                $this->setDebugLayout($value);
            } elseif ($key === 'debugLayoutLines' || $key === 'debug_layout_lines') {
                $this->setDebugLayoutLines($value);
            } elseif ($key === 'debugLayoutBlocks' || $key === 'debug_layout_blocks') {
                $this->setDebugLayoutBlocks($value);
            } elseif ($key === 'debugLayoutInline' || $key === 'debug_layout_inline') {
                $this->setDebugLayoutInline($value);
            } elseif ($key === 'debugLayoutPaddingBox' || $key === 'debug_layout_padding_box') {
                $this->setDebugLayoutPaddingBox($value);
            } elseif ($key === 'pdfBackend' || $key === 'pdf_backend') {
                $this->setPdfBackend($value);
            } elseif ($key === 'pdflibLicense' || $key === 'pdflib_license') {
                $this->setPdflibLicense($value);
            } elseif ($key === 'httpContext' || $key === 'http_context') {
                $this->setHttpContext($value);
            }
        }
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get($key)
    {
        if ($key === 'tempDir' || $key === 'temp_dir') {
            return $this->getTempDir();
        } elseif ($key === 'fontDir' || $key === 'font_dir') {
            return $this->getFontDir();
        } elseif ($key === 'fontCache' || $key === 'font_cache') {
            return $this->getFontCache();
        } elseif ($key === 'chroot') {
            return $this->getChroot();
        } elseif ($key === 'allowedProtocols' || $key === 'allowed_protocols') {
            return $this->getAllowedProtocols();
        } elseif ($key === 'artifactPathValidation') {
            return $this->getArtifactPathValidation();
        } elseif ($key === 'logOutputFile' || $key === 'log_output_file') {
            return $this->getLogOutputFile();
        } elseif ($key === 'defaultMediaType' || $key === 'default_media_type') {
            return $this->getDefaultMediaType();
        } elseif ($key === 'defaultPaperSize' || $key === 'default_paper_size') {
            return $this->getDefaultPaperSize();
        } elseif ($key === 'defaultPaperOrientation' || $key === 'default_paper_orientation') {
            return $this->getDefaultPaperOrientation();
        } elseif ($key === 'defaultFont' || $key === 'default_font') {
            return $this->getDefaultFont();
        } elseif ($key === 'dpi') {
            return $this->getDpi();
        } elseif ($key === 'fontHeightRatio' || $key === 'font_height_ratio') {
            return $this->getFontHeightRatio();
        } elseif ($key === 'isPhpEnabled' || $key === 'is_php_enabled' || $key === 'enable_php') {
            return $this->getIsPhpEnabled();
        } elseif ($key === 'isRemoteEnabled' || $key === 'is_remote_enabled' || $key === 'enable_remote') {
            return $this->getIsRemoteEnabled();
        } elseif ($key === 'allowedRemoteHosts' || $key === 'allowed_remote_hosts') {
            return $this->getAllowedProtocols();
        } elseif ($key === 'isJavascriptEnabled' || $key === 'is_javascript_enabled' || $key === 'enable_javascript') {
            return $this->getIsJavascriptEnabled();
        } elseif ($key === 'isHtml5ParserEnabled' || $key === 'is_html5_parser_enabled' || $key === 'enable_html5_parser') {
            return $this->getIsHtml5ParserEnabled();
        } elseif ($key === 'isFontSubsettingEnabled' || $key === 'is_font_subsetting_enabled' || $key === 'enable_font_subsetting') {
            return $this->getIsFontSubsettingEnabled();
        } elseif ($key === 'debugPng' || $key === 'debug_png') {
            return $this->getDebugPng();
        } elseif ($key === 'debugKeepTemp' || $key === 'debug_keep_temp') {
            return $this->getDebugKeepTemp();
        } elseif ($key === 'debugCss' || $key === 'debug_css') {
            return $this->getDebugCss();
        } elseif ($key === 'debugLayout' || $key === 'debug_layout') {
            return $this->getDebugLayout();
        } elseif ($key === 'debugLayoutLines' || $key === 'debug_layout_lines') {
            return $this->getDebugLayoutLines();
        } elseif ($key === 'debugLayoutBlocks' || $key === 'debug_layout_blocks') {
            return $this->getDebugLayoutBlocks();
        } elseif ($key === 'debugLayoutInline' || $key === 'debug_layout_inline') {
            return $this->getDebugLayoutInline();
        } elseif ($key === 'debugLayoutPaddingBox' || $key === 'debug_layout_padding_box') {
            return $this->getDebugLayoutPaddingBox();
        } elseif ($key === 'pdfBackend' || $key === 'pdf_backend') {
            return $this->getPdfBackend();
        } elseif ($key === 'pdflibLicense' || $key === 'pdflib_license') {
            return $this->getPdflibLicense();
        } elseif ($key === 'httpContext' || $key === 'http_context') {
            return $this->getHttpContext();
        }
        return null;
    }

    /**
     * @param string $pdfBackend
     * @return $this
     */
    public function setPdfBackend($pdfBackend)
    {
        $this->pdfBackend = $pdfBackend;
        return $this;
    }

    /**
     * @return string
     */
    public function getPdfBackend()
    {
        return $this->pdfBackend;
    }

    /**
     * @param string $pdflibLicense
     * @return $this
     */
    public function setPdflibLicense($pdflibLicense)
    {
        $this->pdflibLicense = $pdflibLicense;
        return $this;
    }

    /**
     * @return string
     */
    public function getPdflibLicense()
    {
        return $this->pdflibLicense;
    }

    /**
     * @param array|string $chroot
     * @return $this
     */
    public function setChroot($chroot, $delimiter = ',')
    {
        if (is_string($chroot)) {
            $this->chroot = explode($delimiter, $chroot);
        } elseif (is_array($chroot)) {
            $this->chroot = $chroot;
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getAllowedProtocols()
    {
        return $this->allowedProtocols;
    }

    /**
     * @param array $allowedProtocols The protocols to allow, as an array
     * formatted as ["protocol://" => ["rules" => [callable]], ...]
     * or ["protocol://", ...]
     *
     * @return $this
     */
    public function setAllowedProtocols(array $allowedProtocols)
    {
        $protocols = [];
        foreach ($allowedProtocols as $protocol => $config) {
            if (is_string($protocol)) {
                $protocols[$protocol] = [];
                if (is_array($config)) {
                    $protocols[$protocol] = $config;
                }
            } elseif (is_string($config)) {
                $protocols[$config] = [];
            }
        }
        $this->allowedProtocols = [];
        foreach ($protocols as $protocol => $config) {
            $this->addAllowedProtocol($protocol, ...($config["rules"] ?? []));
        }
        return $this;
    }

    /**
     * Adds a new protocol to the allowed protocols collection
     *
     * @param string $protocol The scheme to add (e.g. "http://")
     * @param callable $rule A callable that validates the protocol
     * @return $this
     */
    public function addAllowedProtocol(string $protocol, callable ...$rules)
    {
        $protocol = strtolower($protocol);
        if (empty($rules)) {
            $rules = [];
            switch ($protocol) {
                case "file://":
                    $rules[] = [$this, "validateLocalUri"];
                    break;
                case "http://":
                case "https://":
                    $rules[] = [$this, "validateRemoteUri"];
                    break;
                case "phar://":
                    $rules[] = [$this, "validatePharUri"];
                    break;
            }
        }
        $this->allowedProtocols[$protocol] = ["rules" => $rules];
        return $this;
    }

    /**
     * @return array
     */
    public function getArtifactPathValidation()
    {
        return $this->artifactPathValidation;
    }

    /**
     * @param callable $validator
     * @return $this
     */
    public function setArtifactPathValidation($validator)
    {
        $this->artifactPathValidation = $validator;
        return $this;
    }

    /**
     * @return array
     */
    public function getChroot()
    {
        $chroot = [];
        if (is_array($this->chroot)) {
            $chroot = $this->chroot;
        }
        return $chroot;
    }

    /**
     * @param boolean $debugCss
     * @return $this
     */
    public function setDebugCss($debugCss)
    {
        $this->debugCss = $debugCss;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDebugCss()
    {
        return $this->debugCss;
    }

    /**
     * @param boolean $debugKeepTemp
     * @return $this
     */
    public function setDebugKeepTemp($debugKeepTemp)
    {
        $this->debugKeepTemp = $debugKeepTemp;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDebugKeepTemp()
    {
        return $this->debugKeepTemp;
    }

    /**
     * @param boolean $debugLayout
     * @return $this
     */
    public function setDebugLayout($debugLayout)
    {
        $this->debugLayout = $debugLayout;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDebugLayout()
    {
        return $this->debugLayout;
    }

    /**
     * @param boolean $debugLayoutBlocks
     * @return $this
     */
    public function setDebugLayoutBlocks($debugLayoutBlocks)
    {
        $this->debugLayoutBlocks = $debugLayoutBlocks;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDebugLayoutBlocks()
    {
        return $this->debugLayoutBlocks;
    }

    /**
     * @param boolean $debugLayoutInline
     * @return $this
     */
    public function setDebugLayoutInline($debugLayoutInline)
    {
        $this->debugLayoutInline = $debugLayoutInline;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDebugLayoutInline()
    {
        return $this->debugLayoutInline;
    }

    /**
     * @param boolean $debugLayoutLines
     * @return $this
     */
    public function setDebugLayoutLines($debugLayoutLines)
    {
        $this->debugLayoutLines = $debugLayoutLines;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDebugLayoutLines()
    {
        return $this->debugLayoutLines;
    }

    /**
     * @param boolean $debugLayoutPaddingBox
     * @return $this
     */
    public function setDebugLayoutPaddingBox($debugLayoutPaddingBox)
    {
        $this->debugLayoutPaddingBox = $debugLayoutPaddingBox;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDebugLayoutPaddingBox()
    {
        return $this->debugLayoutPaddingBox;
    }

    /**
     * @param boolean $debugPng
     * @return $this
     */
    public function setDebugPng($debugPng)
    {
        $this->debugPng = $debugPng;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getDebugPng()
    {
        return $this->debugPng;
    }

    /**
     * @param string $defaultFont
     * @return $this
     */
    public function setDefaultFont($defaultFont)
    {
        if (!($defaultFont === null || trim($defaultFont) === "")) {
            $this->defaultFont = $defaultFont;
        } else {
            $this->defaultFont = "serif";
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultFont()
    {
        return $this->defaultFont;
    }

    /**
     * @param string $defaultMediaType
     * @return $this
     */
    public function setDefaultMediaType($defaultMediaType)
    {
        $this->defaultMediaType = $defaultMediaType;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultMediaType()
    {
        return $this->defaultMediaType;
    }

    /**
     * @param string|float[] $defaultPaperSize
     * @return $this
     */
    public function setDefaultPaperSize($defaultPaperSize): self
    {
        $this->defaultPaperSize = $defaultPaperSize;
        return $this;
    }

    /**
     * @param string $defaultPaperOrientation
     * @return $this
     */
    public function setDefaultPaperOrientation(string $defaultPaperOrientation): self
    {
        $this->defaultPaperOrientation = $defaultPaperOrientation;
        return $this;
    }

    /**
     * @return string|float[]
     */
    public function getDefaultPaperSize()
    {
        return $this->defaultPaperSize;
    }

    /**
     * @return string
     */
    public function getDefaultPaperOrientation(): string
    {
        return $this->defaultPaperOrientation;
    }

    /**
     * @param int $dpi
     * @return $this
     */
    public function setDpi($dpi)
    {
        $this->dpi = $dpi;
        return $this;
    }

    /**
     * @return int
     */
    public function getDpi()
    {
        return $this->dpi;
    }

    /**
     * @param string $fontCache
     * @return $this
     */
    public function setFontCache($fontCache)
    {
        if (!is_callable($this->artifactPathValidation) || ($this->artifactPathValidation)($fontCache, "fontCache") === true) {
            $this->fontCache = $fontCache;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getFontCache()
    {
        return $this->fontCache;
    }

    /**
     * @param string $fontDir
     * @return $this
     */
    public function setFontDir($fontDir)
    {
        if (!is_callable($this->artifactPathValidation) || ($this->artifactPathValidation)($fontDir, "fontDir") === true) {
            $this->fontDir = $fontDir;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getFontDir()
    {
        return $this->fontDir;
    }

    /**
     * @param float $fontHeightRatio
     * @return $this
     */
    public function setFontHeightRatio($fontHeightRatio)
    {
        $this->fontHeightRatio = $fontHeightRatio;
        return $this;
    }

    /**
     * @return float
     */
    public function getFontHeightRatio()
    {
        return $this->fontHeightRatio;
    }

    /**
     * @param boolean $isFontSubsettingEnabled
     * @return $this
     */
    public function setIsFontSubsettingEnabled($isFontSubsettingEnabled)
    {
        $this->isFontSubsettingEnabled = $isFontSubsettingEnabled;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsFontSubsettingEnabled()
    {
        return $this->isFontSubsettingEnabled;
    }

    /**
     * @return boolean
     */
    public function isFontSubsettingEnabled()
    {
        return $this->getIsFontSubsettingEnabled();
    }

    /**
     * @deprecated
     * @param boolean $isHtml5ParserEnabled
     * @return $this
     */
    public function setIsHtml5ParserEnabled($isHtml5ParserEnabled)
    {
        $this->isHtml5ParserEnabled = $isHtml5ParserEnabled;
        return $this;
    }

    /**
     * @deprecated
     * @return boolean
     */
    public function getIsHtml5ParserEnabled()
    {
        return $this->isHtml5ParserEnabled;
    }

    /**
     * @deprecated
     * @return boolean
     */
    public function isHtml5ParserEnabled()
    {
        return $this->getIsHtml5ParserEnabled();
    }

    /**
     * @param boolean $isJavascriptEnabled
     * @return $this
     */
    public function setIsJavascriptEnabled($isJavascriptEnabled)
    {
        $this->isJavascriptEnabled = $isJavascriptEnabled;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsJavascriptEnabled()
    {
        return $this->isJavascriptEnabled;
    }

    /**
     * @return boolean
     */
    public function isJavascriptEnabled()
    {
        return $this->getIsJavascriptEnabled();
    }

    /**
     * @param boolean $isPhpEnabled
     * @return $this
     */
    public function setIsPhpEnabled($isPhpEnabled)
    {
        $this->isPhpEnabled = $isPhpEnabled;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsPhpEnabled()
    {
        return $this->isPhpEnabled;
    }

    /**
     * @return boolean
     */
    public function isPhpEnabled()
    {
        return $this->getIsPhpEnabled();
    }

    /**
     * @param boolean $isRemoteEnabled
     * @return $this
     */
    public function setIsRemoteEnabled($isRemoteEnabled)
    {
        $this->isRemoteEnabled = $isRemoteEnabled;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsRemoteEnabled()
    {
        return $this->isRemoteEnabled;
    }

    /**
     * @return boolean
     */
    public function isRemoteEnabled()
    {
        return $this->getIsRemoteEnabled();
    }

    /**
     * @param array|null $allowedRemoteHosts
     * @return $this
     */
    public function setAllowedRemoteHosts($allowedRemoteHosts)
    {
        if (is_array($allowedRemoteHosts)) {
            // Set hosts to lowercase
            foreach ($allowedRemoteHosts as &$host) {
                $host = mb_strtolower($host);
            }

            unset($host);
        }

        $this->allowedRemoteHosts = $allowedRemoteHosts;
        return $this;
    }

    /**
     * @return array|null
     */
    public function getAllowedRemoteHosts()
    {
        return $this->allowedRemoteHosts;
    }

    /**
     * @param string $logOutputFile
     * @return $this
     */
    public function setLogOutputFile($logOutputFile)
    {
        if (!is_callable($this->artifactPathValidation) || ($this->artifactPathValidation)($logOutputFile, "logOutputFile") === true) {
            $this->logOutputFile = $logOutputFile;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getLogOutputFile()
    {
        return $this->logOutputFile;
    }

    /**
     * @param string $tempDir
     * @return $this
     */
    public function setTempDir($tempDir)
    {
        if (!is_callable($this->artifactPathValidation) || ($this->artifactPathValidation)($tempDir, "tempDir") === true) {
            $this->tempDir = $tempDir;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getTempDir()
    {
        return $this->tempDir;
    }

    /**
     * @param string $rootDir
     * @return $this
     */
    public function setRootDir($rootDir)
    {
        if (!is_callable($this->artifactPathValidation) || ($this->artifactPathValidation)($rootDir, "rootDir") === true) {
            $this->rootDir = $rootDir;
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return $this->rootDir;
    }

    /**
     * Sets the HTTP context
     *
     * @param resource|array $httpContext
     * @return $this
     */
    public function setHttpContext($httpContext)
    {
        $this->httpContext = is_array($httpContext) ? stream_context_create($httpContext) : $httpContext;
        return $this;
    }

    /**
     * Returns the HTTP context
     *
     * @return resource
     */
    public function getHttpContext()
    {
        return $this->httpContext;
    }


    public function validateArtifactPath(?string $path, string $option)
    {
        if ($path === null) {
            return true;
        }
        $parsed_uri = parse_url($path);
        if ($parsed_uri === false || (array_key_exists("scheme", $parsed_uri) && strtolower($parsed_uri["scheme"]) === "phar")) {
            return false;
        }
        return true;
    }

    public function validateLocalUri(string $uri)
    {
        if ($uri === null || strlen($uri) === 0) {
            return [false, "The URI must not be empty."];
        }

        $realfile = realpath(str_replace("file://", "", $uri));

        $dirs = $this->chroot;
        $dirs[] = $this->rootDir;
        $chrootValid = false;
        foreach ($dirs as $chrootPath) {
            $chrootPath = realpath($chrootPath);
            if ($chrootPath !== false && strpos($realfile, $chrootPath) === 0) {
                $chrootValid = true;
                break;
            }
        }
        if ($chrootValid !== true) {
            return [false, "Permission denied. The file could not be found under the paths specified by Options::chroot."];
        }

        if (!$realfile) {
            return [false, "File not found."];
        }

        return [true, null];
    }

    public function validatePharUri(string $uri)
    {
        if ($uri === null || strlen($uri) === 0) {
            return [false, "The URI must not be empty."];
        }

        $file = substr(substr($uri, 0, strpos($uri, ".phar") + 5), 7);
        return $this->validateLocalUri($file);
    }

    public function validateRemoteUri(string $uri)
    {
        if ($uri === null || strlen($uri) === 0) {
            return [false, "The URI must not be empty."];
        }

        if (!$this->isRemoteEnabled) {
            return [false, "Remote file requested, but remote file download is disabled."];
        }

        if (is_array($this->allowedRemoteHosts) && count($this->allowedRemoteHosts) > 0) {
            $host = parse_url($uri, PHP_URL_HOST);
            $host = mb_strtolower($host);

            if (!in_array($host, $this->allowedRemoteHosts, true)) {
                return [false, "Remote host is not in allowed list: " . $host];
            }
        }

        return [true, null];
    }
}
