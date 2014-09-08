<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

use Dompdf\Exception;
use Dompdf\Helpers;

/**
 * Defined a constant if not already defined
 *
 * @param string $name  The constant name
 * @param mixed  $value The value
 */
function def($name, $value = true) {
  if ( !defined($name) ) {
    define($name, $value);
  }
}

/**
 * mb_string compatibility
 */
if (!extension_loaded('mbstring')) {
    if (!defined('MB_OVERLOAD_MAIL')) {
        define('MB_OVERLOAD_MAIL', 1);
    }
    if (!defined('MB_OVERLOAD_STRING')) {
        define('MB_OVERLOAD_STRING', 2);
    }
    if (!defined('MB_OVERLOAD_REGEX')) {
        define('MB_OVERLOAD_REGEX', 4);
    }
    if (!defined('MB_CASE_UPPER')) {
        define('MB_CASE_UPPER', 0);
    }
    if (!defined('MB_CASE_LOWER')) {
        define('MB_CASE_LOWER', 1);
    }
    if (!defined('MB_CASE_TITLE')) {
        define('MB_CASE_TITLE', 2);
    }

    if (!function_exists('mb_convert_encoding')) {
        function mb_convert_encoding($data, $to_encoding, $from_encoding = 'UTF-8')
        {
            if (str_replace('-', '', strtolower($to_encoding)) === 'utf8') {
                return utf8_encode($data);
            }

            return utf8_decode($data);
        }
    }

    if (!function_exists('mb_detect_encoding')) {
        function mb_detect_encoding($data, $encoding_list = array('iso-8859-1'), $strict = false)
        {
            return 'iso-8859-1';
        }
    }

    if (!function_exists('mb_detect_order')) {
        function mb_detect_order($encoding_list = array('iso-8859-1'))
        {
            return 'iso-8859-1';
        }
    }

    if (!function_exists('mb_internal_encoding')) {
        function mb_internal_encoding($encoding = null)
        {
            if (isset($encoding)) {
                return true;
            }

            return 'iso-8859-1';
        }
    }

    if (!function_exists('mb_strlen')) {
        function mb_strlen($str, $encoding = 'iso-8859-1')
        {
            switch (str_replace('-', '', strtolower($encoding))) {
                case "utf8":
                    return strlen(utf8_encode($str));
                case "8bit":
                    return strlen($str);
                default:
                    return strlen(utf8_decode($str));
            }
        }
    }

    if (!function_exists('mb_strpos')) {
        function mb_strpos($haystack, $needle, $offset = 0)
        {
            return strpos($haystack, $needle, $offset);
        }
    }

    if (!function_exists('mb_strrpos')) {
        function mb_strrpos($haystack, $needle, $offset = 0)
        {
            return strrpos($haystack, $needle, $offset);
        }
    }

    if (!function_exists('mb_strtolower')) {
        function mb_strtolower($str)
        {
            return strtolower($str);
        }
    }

    if (!function_exists('mb_strtoupper')) {
        function mb_strtoupper($str)
        {
            return strtoupper($str);
        }
    }

    if (!function_exists('mb_substr')) {
        function mb_substr($string, $start, $length = null, $encoding = 'iso-8859-1')
        {
            if (is_null($length)) {
                return substr($string, $start);
            }

            return substr($string, $start, $length);
        }
    }

    if (!function_exists('mb_substr_count')) {
        function mb_substr_count($haystack, $needle, $encoding = 'iso-8859-1')
        {
            return substr_count($haystack, $needle);
        }
    }

    if (!function_exists('mb_encode_numericentity')) {
        function mb_encode_numericentity($str, $convmap, $encoding)
        {
            return htmlspecialchars($str);
        }
    }

    if (!function_exists('mb_convert_case')) {
        function mb_convert_case($str, $mode = MB_CASE_UPPER, $encoding = array())
        {
            switch ($mode) {
                case MB_CASE_UPPER:
                    return mb_strtoupper($str);
                case MB_CASE_LOWER:
                    return mb_strtolower($str);
                case MB_CASE_TITLE:
                    return ucwords(mb_strtolower($str));
                default:
                    return $str;
            }
        }
    }

    if (!function_exists('mb_list_encodings')) {
        function mb_list_encodings()
        {
            return array(
                "ISO-8859-1",
                "UTF-8",
                "8bit",
            );
        }
    }
}


if (!function_exists("imagecreatefrombmp")) {

    /**
     * Credit goes to mgutt
     * http://www.programmierer-forum.de/function-imagecreatefrombmp-welche-variante-laeuft-t143137.htm
     * Modified by Fabien Menager to support RGB555 BMP format
     */
    function imagecreatefrombmp($filename)
    {
        if (!function_exists("imagecreatetruecolor")) {
            trigger_error("The PHP GD extension is required, but is not installed.", E_ERROR);
            return false;
        }

        // version 1.00
        if (!($fh = fopen($filename, 'rb'))) {
            trigger_error('imagecreatefrombmp: Can not open ' . $filename, E_USER_WARNING);
            return false;
        }

        $bytes_read = 0;

        // read file header
        $meta = unpack('vtype/Vfilesize/Vreserved/Voffset', fread($fh, 14));

        // check for bitmap
        if ($meta['type'] != 19778) {
            trigger_error('imagecreatefrombmp: ' . $filename . ' is not a bitmap!', E_USER_WARNING);
            return false;
        }

        // read image header
        $meta += unpack('Vheadersize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', fread($fh, 40));
        $bytes_read += 40;

        // read additional bitfield header
        if ($meta['compression'] == 3) {
            $meta += unpack('VrMask/VgMask/VbMask', fread($fh, 12));
            $bytes_read += 12;
        }

        // set bytes and padding
        $meta['bytes'] = $meta['bits'] / 8;
        $meta['decal'] = 4 - (4 * (($meta['width'] * $meta['bytes'] / 4) - floor($meta['width'] * $meta['bytes'] / 4)));
        if ($meta['decal'] == 4) {
            $meta['decal'] = 0;
        }

        // obtain imagesize
        if ($meta['imagesize'] < 1) {
            $meta['imagesize'] = $meta['filesize'] - $meta['offset'];
            // in rare cases filesize is equal to offset so we need to read physical size
            if ($meta['imagesize'] < 1) {
                $meta['imagesize'] = @filesize($filename) - $meta['offset'];
                if ($meta['imagesize'] < 1) {
                    trigger_error('imagecreatefrombmp: Can not obtain filesize of ' . $filename . '!', E_USER_WARNING);
                    return false;
                }
            }
        }

        // calculate colors
        $meta['colors'] = !$meta['colors'] ? pow(2, $meta['bits']) : $meta['colors'];

        // read color palette
        $palette = array();
        if ($meta['bits'] < 16) {
            $palette = unpack('l' . $meta['colors'], fread($fh, $meta['colors'] * 4));
            // in rare cases the color value is signed
            if ($palette[1] < 0) {
                foreach ($palette as $i => $color) {
                    $palette[$i] = $color + 16777216;
                }
            }
        }

        // ignore extra bitmap headers
        if ($meta['headersize'] > $bytes_read) {
            fread($fh, $meta['headersize'] - $bytes_read);
        }

        // create gd image
        $im = imagecreatetruecolor($meta['width'], $meta['height']);
        $data = fread($fh, $meta['imagesize']);

        // uncompress data
        switch ($meta['compression']) {
            case 1:
                $data = Helpers::rle8_decode($data, $meta['width']);
                break;
            case 2:
                $data = Helpers::rle4_decode($data, $meta['width']);
                break;
        }

        $p = 0;
        $vide = chr(0);
        $y = $meta['height'] - 1;
        $error = 'imagecreatefrombmp: ' . $filename . ' has not enough data!';

        // loop through the image data beginning with the lower left corner
        while ($y >= 0) {
            $x = 0;
            while ($x < $meta['width']) {
                switch ($meta['bits']) {
                    case 32:
                    case 24:
                        if (!($part = substr($data, $p, 3 /*$meta['bytes']*/))) {
                            trigger_error($error, E_USER_WARNING);
                            return $im;
                        }
                        $color = unpack('V', $part . $vide);
                        break;
                    case 16:
                        if (!($part = substr($data, $p, 2 /*$meta['bytes']*/))) {
                            trigger_error($error, E_USER_WARNING);
                            return $im;
                        }
                        $color = unpack('v', $part);

                        if (empty($meta['rMask']) || $meta['rMask'] != 0xf800) {
                            $color[1] = (($color[1] & 0x7c00) >> 7) * 65536 + (($color[1] & 0x03e0) >> 2) * 256 + (($color[1] & 0x001f) << 3); // 555
                        } else {
                            $color[1] = (($color[1] & 0xf800) >> 8) * 65536 + (($color[1] & 0x07e0) >> 3) * 256 + (($color[1] & 0x001f) << 3); // 565
                        }
                        break;
                    case 8:
                        $color = unpack('n', $vide . substr($data, $p, 1));
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    case 4:
                        $color = unpack('n', $vide . substr($data, floor($p), 1));
                        $color[1] = ($p * 2) % 2 == 0 ? $color[1] >> 4 : $color[1] & 0x0F;
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    case 1:
                        $color = unpack('n', $vide . substr($data, floor($p), 1));
                        switch (($p * 8) % 8) {
                            case 0:
                                $color[1] = $color[1] >> 7;
                                break;
                            case 1:
                                $color[1] = ($color[1] & 0x40) >> 6;
                                break;
                            case 2:
                                $color[1] = ($color[1] & 0x20) >> 5;
                                break;
                            case 3:
                                $color[1] = ($color[1] & 0x10) >> 4;
                                break;
                            case 4:
                                $color[1] = ($color[1] & 0x8) >> 3;
                                break;
                            case 5:
                                $color[1] = ($color[1] & 0x4) >> 2;
                                break;
                            case 6:
                                $color[1] = ($color[1] & 0x2) >> 1;
                                break;
                            case 7:
                                $color[1] = ($color[1] & 0x1);
                                break;
                        }
                        $color[1] = $palette[$color[1] + 1];
                        break;
                    default:
                        trigger_error('imagecreatefrombmp: ' . $filename . ' has ' . $meta['bits'] . ' bits and this is not supported!', E_USER_WARNING);
                        return false;
                }
                imagesetpixel($im, $x, $y, $color[1]);
                $x++;
                $p += $meta['bytes'];
            }
            $y--;
            $p += $meta['decal'];
        }
        fclose($fh);
        return $im;
    }
}

if (!function_exists("date_default_timezone_get")) {
    function date_default_timezone_get()
    {
        return "";
    }

    function date_default_timezone_set($timezone_identifier)
    {
        return true;
    }
}

/**
 * Print a useful backtrace
 */
function bt()
{
    if (php_sapi_name() !== "cli") {
        echo "<pre>";
    }

    $bt = debug_backtrace();

    array_shift($bt); // remove actual bt() call
    echo "\n";

    $i = 0;
    foreach ($bt as $call) {
        $file = basename($call["file"]) . " (" . $call["line"] . ")";
        if (isset($call["class"])) {
            $func = $call["class"] . "->" . $call["function"] . "()";
        } else {
            $func = $call["function"] . "()";
        }

        echo "#" . str_pad($i, 2, " ", STR_PAD_RIGHT) . ": " . str_pad($file . ":", 42) . " $func\n";
        $i++;
    }
    echo "\n";

    if (php_sapi_name() !== "cli") {
        echo "</pre>";
    }
}

if (!function_exists("print_memusage")) {
    /**
     * Dump memory usage
     */
    function print_memusage()
    {
        global $memusage;
        echo "Memory Usage\n";
        $prev = 0;
        $initial = reset($memusage);
        echo str_pad("Initial:", 40) . $initial . "\n\n";

        foreach ($memusage as $key => $mem) {
            $mem -= $initial;
            echo str_pad("$key:", 40);
            echo str_pad("$mem", 12) . "(diff: " . ($mem - $prev) . ")\n";
            $prev = $mem;
        }

        echo "\n" . str_pad("Total:", 40) . memory_get_usage() . "\n";
    }
}

if (!function_exists("enable_mem_profile")) {
    /**
     * Initialize memory profiling code
     */
    function enable_mem_profile()
    {
        global $memusage;
        $memusage = array("Startup" => memory_get_usage());
        register_shutdown_function("print_memusage");
    }
}

if (!function_exists("mark_memusage")) {
    /**
     * Record the current memory usage
     *
     * @param string $location a meaningful location
     */
    function mark_memusage($location)
    {
        global $memusage;
        if (isset($memusage)) {
            $memusage[$location] = memory_get_usage();
        }
    }
}

if (!function_exists('sys_get_temp_dir')) {
    /**
     * Find the current system temporary directory
     *
     * @link http://us.php.net/manual/en/function.sys-get-temp-dir.php#85261
     */
    function sys_get_temp_dir()
    {
        if (!empty($_ENV['TMP'])) {
            return realpath($_ENV['TMP']);
        }

        if (!empty($_ENV['TMPDIR'])) {
            return realpath($_ENV['TMPDIR']);
        }

        if (!empty($_ENV['TEMP'])) {
            return realpath($_ENV['TEMP']);
        }

        $tempfile = tempnam(uniqid(rand(), true), '');
        if (file_exists($tempfile)) {
            unlink($tempfile);
            return realpath(dirname($tempfile));
        }
    }
}
