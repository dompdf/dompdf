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
     * Name of the font cache file
     *
     * This file must be writable by the webserver process only to update it
     * with save_font_families() after adding the .afm file references of a new font family
     * with FontMetrics::saveFontFamilies().
     * This is typically done only from command line with load_font.php on converting
     * ttf fonts to ufm with php-font-lib.
     */
    const CACHE_FILE = "dompdf_font_family_cache.php";

    /**
     * @var Canvas
     * @deprecated
     */
    protected $pdf;

    /**
     * Underlying {@link Canvas} object to perform text size calculations
     *
     * @var Canvas
     */
    protected $canvas;

    /**
     * Array of font family names to font files
     *
     * Usually cached by the {@link load_font.php} script
     *
     * @var array
     */
    protected $fontLookup = array();

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
     * FontMetrics::CACHE_FILE}. This file should be writable by the
     * webserver process.
     *
     * @see FontMetrics::loadFontFamilies()
     */
    public function saveFontFamilies()
    {
        // replace the path to the DOMPDF font directories with the corresponding constants (allows for more portability)
        $cacheData = sprintf("<?php return array (%s", PHP_EOL);
        foreach ($this->fontLookup as $family => $variants) {
            $cacheData .= sprintf("  '%s' => array(%s", addslashes($family), PHP_EOL);
            foreach ($variants as $variant => $path) {
                $path = sprintf("'%s'", $path);
                $path = str_replace('\'' . $this->getOptions()->getFontDir() , '$fontDir . \'' , $path);
                $path = str_replace('\'' . $this->getOptions()->getRootDir() , '$rootDir . \'' , $path);
                $cacheData .= sprintf("    '%s' => %s,%s", $variant, $path, PHP_EOL);
            }
            $cacheData .= sprintf("  ),%s", PHP_EOL);
        }
        $cacheData .= ") ?>";
        file_put_contents($this->getCacheFile(), $cacheData);
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
        $fontDir = $this->getOptions()->getFontDir();
        $rootDir = $this->getOptions()->getRootDir();

        // FIXME: temporarily define constants for cache files <= v0.6.2
        if (!defined("DOMPDF_DIR")) { define("DOMPDF_DIR", $rootDir); }
        if (!defined("DOMPDF_FONT_DIR")) { define("DOMPDF_FONT_DIR", $fontDir); }

        $file = $rootDir . "/lib/fonts/dompdf_font_family_cache.dist.php";
        $distFonts = require $file;

        if (!is_readable($this->getCacheFile())) {
            $this->fontLookup = $distFonts;
            return;
        }

        $cacheData = require $this->getCacheFile();

        $this->fontLookup = array();
        if (is_array($this->fontLookup)) {
            foreach ($cacheData as $key => $value) {
                $this->fontLookup[stripslashes($key)] = $value;
            }
        }

        // Merge provided fonts
        $this->fontLookup += $distFonts;
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

        $entry = array();
        if (isset($families[$fontname])) {
            $entry = $families[$fontname];
        }

        $styleString = $this->getType("{$style['weight']} {$style['style']}");

        $fontDir = $this->getOptions()->getFontDir();
        $remoteHash = md5($remoteFile);

        $prefix = $fontname . "_" . $styleString;
        $prefix = preg_replace("/[^\\pL\d]+/u", "-", $prefix);
        $prefix = trim($prefix, "-");
        if (function_exists('iconv')) {
            $prefix = iconv('utf-8', 'us-ascii//TRANSLIT', $prefix);
        }
        $prefix = preg_replace("/[^-\w]+/", "", $prefix);
        
        $localFile = $fontDir . "/" . $prefix . "_" . $remoteHash;

        if (isset($entry[$styleString]) && $localFile == $entry[$styleString]) {
            return true;
        }

        $cacheEntry = $localFile;
        $localFile .= ".".strtolower(pathinfo(parse_url($remoteFile, PHP_URL_PATH), PATHINFO_EXTENSION));

        $entry[$styleString] = $cacheEntry;

        // Download the remote file
        list($remoteFileContent, $http_response_header) = @Helpers::getFileContent($remoteFile, $context);
        if (empty($remoteFileContent)) {
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
        $font->saveAdobeFontMetrics("$cacheEntry.ufm");
        $font->close();

        unlink($localTempFile);

        if ( !file_exists("$cacheEntry.ufm") ) {
            return false;
        }

        // Save the changes
        file_put_contents($localFile, $remoteFileContent);

        if ( !file_exists($localFile) ) {
            unlink("$cacheEntry.ufm");
            return false;
        }

        $this->setFontFamily($fontname, $entry);
        $this->saveFontFamilies();

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
     * @param string $text the text to be sized
     * @param string $font the desired font
     * @param float $size  the desired font size
     * @param float $wordSpacing
     * @param float $charSpacing
     *
     * @internal param float $spacing word spacing, if any
     * @return float
     */
    public function getTextWidth($text, $font, $size, $wordSpacing = 0.0, $charSpacing = 0.0)
    {
        // @todo Make sure this cache is efficient before enabling it
        static $cache = array();

        if ($text === "") {
            return 0;
        }

        // Don't cache long strings
        $useCache = !isset($text[50]); // Faster than strlen

        $key = "$font/$size/$wordSpacing/$charSpacing";

        if ($useCache && isset($cache[$key][$text])) {
            return $cache[$key]["$text"];
        }

        $width = $this->getCanvas()->get_text_width($text, $font, $size, $wordSpacing, $charSpacing);

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
     * Calculates font height
     *
     * @param string $font
     * @param float $size
     *
     * @return float
     */
    public function getFontHeight($font, $size)
    {
        return $this->getCanvas()->get_font_height($font, $size);
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
     * @param string $familyRaw
     * @param string $subtypeRaw
     *
     * @return string
     */
    public function getFont($familyRaw, $subtypeRaw = "normal")
    {
        static $cache = array();

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

        if ($familyRaw) {
            $family = str_replace(array("'", '"'), "", strtolower($familyRaw));

            if (isset($this->fontLookup[$family][$subtype])) {
                return $cache[$familyRaw][$subtypeRaw] = $this->fontLookup[$family][$subtype];
            }

            return null;
        }

        $family = "serif";

        if (isset($this->fontLookup[$family][$subtype])) {
            return $cache[$familyRaw][$subtypeRaw] = $this->fontLookup[$family][$subtype];
        }

        if (!isset($this->fontLookup[$family])) {
            return null;
        }

        $family = $this->fontLookup[$family];

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
        $family = str_replace(array("'", '"'), "", mb_strtolower($family));

        if (isset($this->fontLookup[$family])) {
            return $this->fontLookup[$family];
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
        return $this->fontLookup;
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
        $this->fontLookup[mb_strtolower($fontname)] = $entry;
    }

    /**
     * @return string
     */
    public function getCacheFile()
    {
        return $this->getOptions()->getFontDir() . '/' . self::CACHE_FILE;
    }

    /**
     * @param Options $options
     * @return $this
     */
    public function setOptions(Options $options)
    {
        $this->options = $options;
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
        // Still write deprecated pdf for now. It might be used by a parent class.
        $this->pdf = $canvas;
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