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
     * FontMetrics::CACHE_FILE}.  This file should be writable by the
     * webserver process.
     *
     * @see Font_Metrics::load_font_families()
     */
    public function saveFontFamilies()
    {
        // replace the path to the Dompdf font directories with the corresponding constants (allows for more portability)
        $cache_data = var_export($this->fontLookup, true);
        $fontDir = $this->getOptions()->getFontDir();
        $rootDir = $this->getOptions()->getRootDir();
        $cache_data = str_replace('\'' . $fontDir, '$fontDir . \'', $cache_data);
        $cache_data = str_replace('\'' . $rootDir, '$rootDir . \'', $cache_data);
        $cache_data = "<" . "?php return $cache_data ?" . ">";
        file_put_contents($this->getCacheFile(), $cache_data);
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
     * @see save_font_families()
     */
    public function loadFontFamilies()
    {
        $fontDir = $this->getOptions()->getFontDir();
        $rootDir = $this->getOptions()->getRootDir();
        
        // FIXME: tempoarary define constants for cache files <= v0.6.1
        if (!defined("DOMPDF_DIR")) { define("DOMPDF_DIR", $rootDir); }
        if (!defined("DOMPDF_FONT_DIR")) { define("DOMPDF_FONT_DIR", $fontDir); }
        
        $file = $rootDir . "/lib/fonts/dompdf_font_family_cache.dist.php";
        $distFonts = require $file;
        
        // FIXME: temporary step for font cache created before the font cache fix
        if (is_readable($fontDir . DIRECTORY_SEPARATOR . "dompdf_font_family_cache")) {
            $old_fonts = require $fontDir . DIRECTORY_SEPARATOR . "dompdf_font_family_cache";
            // If the font family cache is still in the old format
            if ($old_fonts === 1) {
                $cache_data = file_get_contents($fontDir . DIRECTORY_SEPARATOR . "dompdf_font_family_cache");
                file_put_contents($fontDir . DIRECTORY_SEPARATOR . "dompdf_font_family_cache", "<" . "?php return $cache_data ?" . ">");
                $old_fonts = require $fontDir . DIRECTORY_SEPARATOR . "dompdf_font_family_cache";
            }
            $distFonts += $old_fonts;
        }
        
        if (!is_readable($this->getCacheFile())) {
            $this->fontLookup = $distFonts;
            return;
        } else {
            $this->fontLookup = require $this->getCacheFile();
        }
        
        // If the font family cache is still in the old format
        if ($this->fontLookup === 1) {
            $cache_data = file_get_contents($this->getCacheFile());
            file_put_contents($this->getCacheFile(), "<" . "?php return $cache_data ?" . ">");
            $this->fontLookup = require $this->getCacheFile();
        }
        
        // Merge provided fonts
        $this->fontLookup += $distFonts;
    }

    /**
     * @param array $files
     * @return array
     * @deprecated
     */
    public function install_fonts($files)
    {
        return $this->installFonts($files);
    }

    /**
     * @param array $files
     * @return array
     */
    public function installFonts(array $files)
    {
        $names = array();

        foreach ($files as $file) {
            $font = Font::load($file);
            $records = $font->getData("name", "records");
            $type = $this->getType($records[2]);
            $names[mb_strtolower($records[1])][$type] = $file;
        }

        return $names;
    }

    /**
     * @param array $style
     * @param string $remote_file
     * @return bool
     * @deprecated
     */
    public function register_font($style, $remote_file)
    {
        return $this->registerFont($style, $remote_file);
    }

    /**
     * @param array $style
     * @param string $remoteFile
     * @return bool
     */
    public function registerFont($style, $remoteFile)
    {
        $fontDir = $this->getOptions()->getFontDir();
        $fontname = mb_strtolower($style["family"]);
        $families = $this->getFontFamilies();

        $entry = array();
        if (isset($families[$fontname])) {
            $entry = $families[$fontname];
        }

        $local_file = $fontDir . DIRECTORY_SEPARATOR . md5($remoteFile);
        $cache_entry = $local_file;
        $local_file .= ".ttf";

        $style_string = $this->getType("{$style['weight']} {$style['style']}");

        if (!isset($entry[$style_string])) {
            $entry[$style_string] = $cache_entry;

            $this->setFontFamily($fontname, $entry);

            // Download the remote file
            if (!is_file($local_file)) {
                file_put_contents($local_file, file_get_contents($remoteFile));
            }

            $font = Font::load($local_file);

            if (!$font) {
                return false;
            }

            $font->parse();
            $font->saveAdobeFontMetrics("$cache_entry.ufm");

            // Save the changes
            $this->saveFontFamilies();
        }

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
        if (preg_match("/bold/i", $type)) {
            if (preg_match("/italic|oblique/i", $type)) {
                $type = "bold_italic";
            } else {
                $type = "bold";
            }
        } elseif (preg_match("/italic|oblique/i", $type)) {
            $type = "italic";
        } else {
            $type = "normal";
        }

        return $type;
    }

    /**
     * @return array
     * @deprecated
     */
    public function get_system_fonts()
    {
        return $this->getSystemFonts();
    }

    /**
     * @return array
     */
    public function getSystemFonts()
    {
        $files = glob("/usr/share/fonts/truetype/*.ttf") +
            glob("/usr/share/fonts/truetype/*/*.ttf") +
            glob("/usr/share/fonts/truetype/*/*/*.ttf") +
            glob("C:\\Windows\\fonts\\*.ttf") +
            glob("C:\\WinNT\\fonts\\*.ttf") +
            glob("/mnt/c_drive/WINDOWS/Fonts/");

        return $this->installFonts($files);
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
        return $this->getOptions()->getFontDir() . DIRECTORY_SEPARATOR . self::CACHE_FILE;
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
        $this->pdf = $canvas;
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