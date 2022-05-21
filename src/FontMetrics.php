<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

namespace Dompdf;

use FontLib\Font;

/**
 * The font metrics class
 *
 * This class provides information about fonts and text.  It can resolve
 * font names into actual installed font files, as well as determine the
 * size of text in a particular font and size.
 *
 * @static
 * @package dompdf
 */
class FontMetrics
{
    /**
     * Name of the user font families file
     *
     * This file must be writable by the webserver process only to update it
     * with save_font_families() after adding the .afm file references of a new font family
     * with FontMetrics::saveFontFamilies().
     * This is typically done only from command line with load_font.php on converting
     * ttf fonts to ufm with php-font-lib.
     */
    const USER_FONTS_FILE = "installed-fonts.json";


    /**
     * Underlying {@link Canvas} object to perform text size calculations
     *
     * @var Canvas
     */
    protected $canvas;

    /**
     * Array of bundled font family names to variants
     *
     * @var array
     */
    protected $bundledFonts = [];

    /**
     * Array of user defined font family names to variants
     *
     * @var array
     */
    protected $userFonts = [];

    /**
     * combined list of all font families with absolute paths
     *
     * @var array
     */
    protected $fontFamilies;

    /**
     * @var Options
     */
    private $options;

    /**
     * Class initialization
     */
    public function __construct(Canvas $canvas, Options $options)
    {
        $this->setCanvas($canvas);
        $this->setOptions($options);
        $this->loadFontFamilies();
    }

    /**
     * @deprecated
     */
    public function save_font_families()
    {
        $this->saveFontFamilies();
    }

    /**
     * Saves the stored font family cache
     *
     * The name and location of the cache file are determined by {@link
     * FontMetrics::USER_FONTS_FILE}. This file should be writable by the
     * webserver process.
     *
     * @see FontMetrics::loadFontFamilies()
     */
    public function saveFontFamilies()
    {
        file_put_contents($this->getUserFontsFilePath(), json_encode($this->userFonts, JSON_PRETTY_PRINT));
    }

    /**
     * @deprecated
     */
    public function load_font_families()
    {
        $this->loadFontFamilies();
    }

    /**
     * Loads the stored font family cache
     *
     * @see FontMetrics::saveFontFamilies()
     */
    public function loadFontFamilies()
    {
        $file = $this->options->getRootDir() . "/lib/fonts/installed-fonts.dist.json";
        $this->bundledFonts = json_decode(file_get_contents($file), true);

        if (is_readable($this->getUserFontsFilePath())) {
            $this->userFonts = json_decode(file_get_contents($this->getUserFontsFilePath()), true);
        } else {
            $this->loadFontFamiliesLegacy();
        }
    }

    private function loadFontFamiliesLegacy()
    {
        $legacyCacheFile = $this->options->getFontDir() . '/dompdf_font_family_cache.php';
        if (is_readable($legacyCacheFile)) {
            $fontDir = $this->options->getFontDir();
            $rootDir = $this->options->getRootDir();
    
            if (!defined("DOMPDF_DIR")) { define("DOMPDF_DIR", $rootDir); }
            if (!defined("DOMPDF_FONT_DIR")) { define("DOMPDF_FONT_DIR", $fontDir); }
    
            $cacheDataClosure = require $legacyCacheFile;
            $cacheData = is_array($cacheDataClosure) ? $cacheDataClosure : $cacheDataClosure($fontDir, $rootDir);
            if (is_array($cacheData)) {
                foreach ($cacheData as $family => $variants) {
                    if (!isset($this->bundledFonts[$family]) && is_array($variants)) {
                        foreach ($variants as $variant => $variantPath) {
                            $variantName = basename($variantPath);
                            $variantDir = dirname($variantPath);
                            if ($variantDir == $fontDir) {
                                $this->userFonts[$family][$variant] = $variantName;
                            } else {
                                $this->userFonts[$family][$variant] = $variantPath;
                            }
                        }
                    }
                }
                $this->saveFontFamilies();
            }
        }
    }

    /**
     * @param array $style
     * @param string $remote_file
     * @param resource $context
     * @return bool
     * @deprecated
     */
    public function register_font($style, $remote_file, $context = null)
    {
        return $this->registerFont($style, $remote_file);
    }

    /**
     * @param array $style
     * @param string $remoteFile
     * @param resource $context
     * @return bool
     */
    public function registerFont($style, $remoteFile, $context = null)
    {
        $fontname = mb_strtolower($style["family"]);
        $families = $this->getFontFamilies();

        $entry = [];
        if (isset($families[$fontname])) {
            $entry = $families[$fontname];
        }

        $styleString = $this->getType("{$style['weight']} {$style['style']}");

        $remoteHash = md5($remoteFile);

        $prefix = $fontname . "_" . $styleString;
        $prefix = trim($prefix, "-");
        if (function_exists('iconv')) {
            $prefix = @iconv('utf-8', 'us-ascii//TRANSLIT', $prefix);
        }
        $prefix_encoding = mb_detect_encoding($prefix, mb_detect_order(), true);
        $substchar = mb_substitute_character();
        mb_substitute_character(0x005F);
        $prefix = mb_convert_encoding($prefix, "ISO-8859-1", $prefix_encoding);
        mb_substitute_character($substchar);
        $prefix = preg_replace("[\W]", "_", $prefix);
        $prefix = preg_replace("/[^-_\w]+/", "", $prefix);

        $localFile = $prefix . "_" . $remoteHash;
        $localFilePath = $this->getOptions()->getFontDir() . "/" . $localFile;

        if (isset($entry[$styleString]) && $localFilePath == $entry[$styleString]) {
            return true;
        }


        $entry[$styleString] = $localFile;

        // Download the remote file
        [$protocol] = Helpers::explode_url($remoteFile);
        if (!$this->options->isRemoteEnabled() && ($protocol !== "" && $protocol !== "file://")) {
            Helpers::record_warnings(E_USER_WARNING, "Remote font resource $remoteFile referenced, but remote file download is disabled.", __FILE__, __LINE__);
            return false;
        }
        if ($protocol === "" || $protocol === "file://") {
            $realfile = realpath($remoteFile);

            $rootDir = realpath($this->options->getRootDir());
            if (strpos($realfile, $rootDir) !== 0) {
                $chroot = $this->options->getChroot();
                $chrootValid = false;
                foreach ($chroot as $chrootPath) {
                    $chrootPath = realpath($chrootPath);
                    if ($chrootPath !== false && strpos($realfile, $chrootPath) === 0) {
                        $chrootValid = true;
                        break;
                    }
                }
                if ($chrootValid !== true) {
                    Helpers::record_warnings(E_USER_WARNING, "Permission denied on $remoteFile. The file could not be found under the paths specified by Options::chroot.", __FILE__, __LINE__);
                    return false;
                }
            }

            if (!$realfile) {
                Helpers::record_warnings(E_USER_WARNING, "File '$realfile' not found.", __FILE__, __LINE__);
                return false;
            }

            $remoteFile = $realfile;
        }
        list($remoteFileContent, $http_response_header) = @Helpers::getFileContent($remoteFile, $context);
        if ($remoteFileContent === null) {
            return false;
        }

        $localTempFile = @tempnam($this->options->get("tempDir"), "dompdf-font-");
        file_put_contents($localTempFile, $remoteFileContent);

        $font = Font::load($localTempFile);

        if (!$font) {
            unlink($localTempFile);
            return false;
        }

        $font->parse();
        $font->saveAdobeFontMetrics("$localFilePath.ufm");
        $font->close();

        unlink($localTempFile);

        if ( !file_exists("$localFilePath.ufm") ) {
            return false;
        }

        $fontExtension = ".ttf";
        switch ($font->getFontType()) {
            case "TrueType":
            default:
                $fontExtension = ".ttf";
                break;
        }

        // Save the changes
        file_put_contents($localFilePath.$fontExtension, $remoteFileContent);

        if ( !file_exists($localFilePath.$fontExtension) ) {
            unlink("$localFilePath.ufm");
            return false;
        }

        $this->setFontFamily($fontname, $entry);

        return true;
    }

    /**
     * @param $text
     * @param $font
     * @param $size
     * @param float $word_spacing
     * @param float $char_spacing
     * @return float
     * @deprecated
     */
    public function get_text_width($text, $font, $size, $word_spacing = 0.0, $char_spacing = 0.0)
    {
        //return self::$_pdf->get_text_width($text, $font, $size, $word_spacing, $char_spacing);
        return $this->getTextWidth($text, $font, $size, $word_spacing, $char_spacing);
    }

    /**
     * Calculates text size, in points
     *
     * @param string $text        the text to be sized
     * @param string $font        the desired font
     * @param float  $size        the desired font size
     * @param float  $wordSpacing word spacing, if any
     * @param float  $charSpacing char spacing, if any
     *
     * @return float
     */
    public function getTextWidth(string $text, $font, float $size, float $wordSpacing = 0.0, float $charSpacing = 0.0): float
    {
        // @todo Make sure this cache is efficient before enabling it
        static $cache = [];

        if ($text === "") {
            return 0;
        }

        // Don't cache long strings
        $useCache = !isset($text[50]); // Faster than strlen

        // Text-size calculations depend on the canvas used. Make sure to not
        // return wrong values when switching canvas backends
        $canvasClass = get_class($this->canvas);
        $key = "$canvasClass/$font/$size/$wordSpacing/$charSpacing";

        if ($useCache && isset($cache[$key][$text])) {
            return $cache[$key][$text];
        }

        $width = $this->canvas->get_text_width($text, $font, $size, $wordSpacing, $charSpacing);

        if ($useCache) {
            $cache[$key][$text] = $width;
        }

        return $width;
    }

    /**
     * @param $font
     * @param $size
     * @return float
     * @deprecated
     */
    public function get_font_height($font, $size)
    {
        return $this->getFontHeight($font, $size);
    }

    /**
     * Calculates font height, in points
     *
     * @param string $font
     * @param float  $size
     *
     * @return float
     */
    public function getFontHeight($font, float $size): float
    {
        return $this->canvas->get_font_height($font, $size);
    }

    /**
     * Calculates font baseline, in points
     *
     * @param string $font
     * @param float  $size
     *
     * @return float
     */
    public function getFontBaseline($font, float $size): float
    {
        return $this->canvas->get_font_baseline($font, $size);
    }

    /**
     * @param $family_raw
     * @param string $subtype_raw
     * @return string
     * @deprecated
     */
    public function get_font($family_raw, $subtype_raw = "normal")
    {
        return $this->getFont($family_raw, $subtype_raw);
    }

    /**
     * Resolves a font family & subtype into an actual font file
     * Subtype can be one of 'normal', 'bold', 'italic' or 'bold_italic'.  If
     * the particular font family has no suitable font file, the default font
     * ({@link Options::defaultFont}) is used.  The font file returned
     * is the absolute pathname to the font file on the system.
     *
     * @param string|null $familyRaw
     * @param string      $subtypeRaw
     *
     * @return string|null
     */
    public function getFont($familyRaw, $subtypeRaw = "normal")
    {
        static $cache = [];

        if (isset($cache[$familyRaw][$subtypeRaw])) {
            return $cache[$familyRaw][$subtypeRaw];
        }

        /* Allow calling for various fonts in search path. Therefore not immediately
         * return replacement on non match.
         * Only when called with NULL try replacement.
         * When this is also missing there is really trouble.
         * If only the subtype fails, nevertheless return failure.
         * Only on checking the fallback font, check various subtypes on same font.
         */

        $subtype = strtolower($subtypeRaw);

        $families = $this->getFontFamilies();
        if ($familyRaw) {
            $family = str_replace(["'", '"'], "", strtolower($familyRaw));

            if (isset($families[$family][$subtype])) {
                return $cache[$familyRaw][$subtypeRaw] = $families[$family][$subtype];
            }

            return null;
        }

        $fallback_families = [strtolower($this->options->getDefaultFont()), "serif"];
        foreach ($fallback_families as $family) {
            if (isset($families[$family][$subtype])) {
                return $cache[$familyRaw][$subtypeRaw] = $families[$family][$subtype];
            }
    
            if (!isset($families[$family])) {
                continue;
            }
    
            $family = $families[$family];
    
            foreach ($family as $sub => $font) {
                if (strpos($subtype, $sub) !== false) {
                    return $cache[$familyRaw][$subtypeRaw] = $font;
                }
            }
    
            if ($subtype !== "normal") {
                foreach ($family as $sub => $font) {
                    if ($sub !== "normal") {
                        return $cache[$familyRaw][$subtypeRaw] = $font;
                    }
                }
            }
    
            $subtype = "normal";
    
            if (isset($family[$subtype])) {
                return $cache[$familyRaw][$subtypeRaw] = $family[$subtype];
            }
        }
        
        return null;
    }

    /**
     * @param $family
     * @return null|string
     * @deprecated
     */
    public function get_family($family)
    {
        return $this->getFamily($family);
    }

    /**
     * @param string $family
     * @return null|string
     */
    public function getFamily($family)
    {
        $family = str_replace(["'", '"'], "", mb_strtolower($family));
        $families = $this->getFontFamilies();

        if (isset($families[$family])) {
            return $families[$family];
        }

        return null;
    }

    /**
     * @param $type
     * @return string
     * @deprecated
     */
    public function get_type($type)
    {
        return $this->getType($type);
    }

    /**
     * @param string $type
     * @return string
     */
    public function getType($type)
    {
        if (preg_match('/bold/i', $type)) {
            $weight = 700;
        } elseif (preg_match('/([1-9]00)/', $type, $match)) {
            $weight = (int)$match[0];
        } else {
            $weight = 400;
        }
        $weight = $weight === 400 ? 'normal' : $weight;
        $weight = $weight === 700 ? 'bold' : $weight;

        $style = preg_match('/italic|oblique/i', $type) ? 'italic' : null;

        if ($weight === 'normal' && $style !== null) {
            return $style;
        }

        return $style === null
            ? $weight
            : $weight.'_'.$style;
    }

    /**
     * @return array
     * @deprecated
     */
    public function get_font_families()
    {
        return $this->getFontFamilies();
    }

    /**
     * Returns the current font lookup table
     *
     * @return array
     */
    public function getFontFamilies()
    {
        if (!isset($this->fontFamilies)) {
            $this->setFontFamilies();
        }
        return $this->fontFamilies;
    }

    /**
     * Convert loaded fonts to font lookup table
     *
     * @return array
     */
    public function setFontFamilies()
    {
        $fontFamilies = [];
        if (isset($this->bundledFonts) && is_array($this->bundledFonts)) {
            foreach ($this->bundledFonts as $family => $variants) {
                if (!isset($fontFamilies[$family])) {
                    $fontFamilies[$family] = array_map(function($variant) {
                        return $this->getOptions()->getRootDir() . '/lib/fonts/' . $variant;
                    }, $variants);
                }
            }
        }
        if (isset($this->userFonts) && is_array($this->userFonts)) {
            foreach ($this->userFonts as $family => $variants) {
                $fontFamilies[$family] = array_map(function($variant) {
                    $variantName = basename($variant);
                    if ($variantName === $variant) {
                        return $this->getOptions()->getFontDir() . '/' . $variant;
                    }
                    return $variant;
                }, $variants);
            }
        }
        $this->fontFamilies = $fontFamilies;
    }

    /**
     * @param string $fontname
     * @param mixed $entry
     * @deprecated
     */
    public function set_font_family($fontname, $entry)
    {
        $this->setFontFamily($fontname, $entry);
    }

    /**
     * @param string $fontname
     * @param mixed $entry
     */
    public function setFontFamily($fontname, $entry)
    {
        $this->userFonts[mb_strtolower($fontname)] = $entry;
        $this->saveFontFamilies();
        unset($this->fontFamilies);
    }

    /**
     * @return string
     */
    public function getUserFontsFilePath()
    {
        return $this->options->getFontDir() . '/' . self::USER_FONTS_FILE;
    }

    /**
     * @param Options $options
     * @return $this
     */
    public function setOptions(Options $options)
    {
        $this->options = $options;
        unset($this->fontFamilies);
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
     */
    public function getCanvas()
    {
        return $this->canvas;
    }
}
