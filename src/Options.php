<?php
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
     * The directory specified must be writable by the webserver process.
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
     * Note: This directory must exist and be writable by the webserver process.
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
     * Note: This directory must exist and be writable by the webserver process.
     *
     * @var string
     */
    private $fontCache;

    /**
     * dompdf's "chroot"
     *
     * Prevents dompdf from accessing system files or other files on the webserver.
     * All local files opened by dompdf must be in a subdirectory of this directory
     * or array of directories.
     * DO NOT set it to '/' since this could allow an attacker to use dompdf to
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
     * @var string
     */
    private $logOutputFile;

    /**
     * html target media view which should be rendered into pdf.
     * List of types and parsing rules for future extensions:
     * http://www.w3.org/TR/REC-html40/types.html
     *   screen, tty, tv, projection, handheld, print, braille, aural, all
     * Note: aural is deprecated in CSS 2.1 because it is replaced by speech in CSS 3.
     * Note, even though the generated pdf file is intended for print output,
     * the desired content might be different (e.g. screen or projection view of html file).
     * Therefore allow specification of content here.
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
     * @var string
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
     * Enable inline Javascript
     *
     * If this setting is set to true then DOMPDF will automatically insert
     * JavaScript code contained within <script type="text/javascript"> ... </script> tags.
     *
     * @var bool
     */
    private $isJavascriptEnabled = true;

    /**
     * Use the more-than-experimental HTML5 Lib parser
     *
     * @var bool
     */
    private $isHtml5ParserEnabled = false;

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
        $this->setLogOutputFile($this->getTempDir() . "/log.htm");

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
        $this->defaultFont = $defaultFont;
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
     * @param string $defaultPaperSize
     * @return $this
     */
    public function setDefaultPaperSize($defaultPaperSize)
    {
        $this->defaultPaperSize = $defaultPaperSize;
        return $this;
    }

    /**
     * @param string $defaultPaperOrientation
     * @return $this
     */
    public function setDefaultPaperOrientation($defaultPaperOrientation)
    {
        $this->defaultPaperOrientation = $defaultPaperOrientation;
        return $this;
    }

    /**
     * @return string
     */
    public function getDefaultPaperSize()
    {
        return $this->defaultPaperSize;
    }

    /**
     * @return string
     */
    public function getDefaultPaperOrientation()
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
        $this->fontCache = $fontCache;
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
        $this->fontDir = $fontDir;
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
     * @param boolean $isHtml5ParserEnabled
     * @return $this
     */
    public function setIsHtml5ParserEnabled($isHtml5ParserEnabled)
    {
        $this->isHtml5ParserEnabled = $isHtml5ParserEnabled;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsHtml5ParserEnabled()
    {
        return $this->isHtml5ParserEnabled;
    }

    /**
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
     * @param string $logOutputFile
     * @return $this
     */
    public function setLogOutputFile($logOutputFile)
    {
        $this->logOutputFile = $logOutputFile;
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
        $this->tempDir = $tempDir;
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
        $this->rootDir = $rootDir;
        return $this;
    }

    /**
     * @return string
     */
    public function getRootDir()
    {
        return $this->rootDir;
    }
}