<?php
namespace Dompdf;

class Helpers
{
    /**
     * print_r wrapper for html/cli output
     *
     * Wraps print_r() output in < pre > tags if the current sapi is not 'cli'.
     * Returns the output string instead of displaying it if $return is true.
     *
     * @param mixed $mixed variable or expression to display
     * @param bool $return
     *
     * @return string
     */
    public static function pre_r($mixed, $return = false)
    {
        if ($return) {
            return "<pre>" . print_r($mixed, true) . "</pre>";
        }

        if (php_sapi_name() !== "cli") {
            echo "<pre>";
        }

        print_r($mixed);

        if (php_sapi_name() !== "cli") {
            echo "</pre>";
        } else {
            echo "\n";
        }

        flush();
    }

    /**
     * var_dump wrapper for html/cli output
     *
     * Wraps var_dump() output in < pre > tags if the current sapi is not 'cli'.
     *
     * @param mixed $mixed variable or expression to display.
     * @deprecated
     */
    public static function pre_var_dump($mixed)
    {
        if (php_sapi_name() !== "cli") {
            echo "<pre>";
        }

        var_dump($mixed);

        if (php_sapi_name() !== "cli") {
            echo "</pre>";
        }
    }

    /**
     * generic debug function
     *
     * Takes everything and does its best to give a good debug output
     *
     * @param mixed $mixed variable or expression to display.
     * @deprecated
     */
    public static function d($mixed)
    {
        if (php_sapi_name() !== "cli") {
            echo "<pre>";
        }

        // line
        if ($mixed instanceof LineBox) {
            echo $mixed;
        } // other
        else {
            var_export($mixed);
        }

        if (php_sapi_name() !== "cli") {
            echo "</pre>";
        }
    }

    /**
     * builds a full url given a protocol, hostname, base path and url
     *
     * @param string $protocol
     * @param string $host
     * @param string $base_path
     * @param string $url
     * @return string
     *
     * Initially the trailing slash of $base_path was optional, and conditionally appended.
     * However on dynamically created sites, where the page is given as url parameter,
     * the base path might not end with an url.
     * Therefore do not append a slash, and **require** the $base_url to ending in a slash
     * when needed.
     * Vice versa, on using the local file system path of a file, make sure that the slash
     * is appended (o.k. also for Windows)
     */
    public static function build_url($protocol, $host, $base_path, $url)
    {
        if (strlen($url) == 0) {
            //return $protocol . $host . rtrim($base_path, "/\\") . "/";
            return $protocol . $host . $base_path;
        }

        // Is the url already fully qualified or a Data URI?
        if (mb_strpos($url, "://") !== false || mb_strpos($url, "data:") === 0) {
            return $url;
        }

        $ret = $protocol;

        if (!in_array(mb_strtolower($protocol), array("http://", "https://", "ftp://", "ftps://"))) {
            //On Windows local file, an abs path can begin also with a '\' or a drive letter and colon
            //drive: followed by a relative path would be a drive specific default folder.
            //not known in php app code, treat as abs path
            //($url[1] !== ':' || ($url[2]!=='\\' && $url[2]!=='/'))
            if ($url[0] !== '/' && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' || ($url[0] !== '\\' && $url[1] !== ':'))) {
                // For rel path and local acess we ignore the host, and run the path through realpath()
                $ret .= realpath($base_path) . '/';
            }
            $ret .= $url;
            $ret = preg_replace('/\?(.*)$/', "", $ret);
            return $ret;
        }

        //remote urls with backslash in html/css are not really correct, but lets be genereous
        if ($url[0] === '/' || $url[0] === '\\') {
            // Absolute path
            $ret .= $host . $url;
        } else {
            // Relative path
            //$base_path = $base_path !== "" ? rtrim($base_path, "/\\") . "/" : "";
            $ret .= $host . $base_path . $url;
        }

        return $ret;
    }

    /**
     * Converts decimal numbers to roman numerals
     *
     * @param int $num
     *
     * @throws Exception
     * @return string
     */
    public static function dec2roman($num)
    {

        static $ones = array("", "i", "ii", "iii", "iv", "v", "vi", "vii", "viii", "ix");
        static $tens = array("", "x", "xx", "xxx", "xl", "l", "lx", "lxx", "lxxx", "xc");
        static $hund = array("", "c", "cc", "ccc", "cd", "d", "dc", "dcc", "dccc", "cm");
        static $thou = array("", "m", "mm", "mmm");

        if (!is_numeric($num)) {
            throw new Exception("dec2roman() requires a numeric argument.");
        }

        if ($num > 4000 || $num < 0) {
            return "(out of range)";
        }

        $num = strrev((string)$num);

        $ret = "";
        switch (mb_strlen($num)) {
            case 4:
                $ret .= $thou[$num[3]];
            case 3:
                $ret .= $hund[$num[2]];
            case 2:
                $ret .= $tens[$num[1]];
            case 1:
                $ret .= $ones[$num[0]];
            default:
                break;
        }

        return $ret;
    }

    /**
     * Determines whether $value is a percentage or not
     *
     * @param float $value
     *
     * @return bool
     */
    public static function is_percent($value)
    {
        return false !== mb_strpos($value, "%");
    }

    /**
     * Parses a data URI scheme
     * http://en.wikipedia.org/wiki/Data_URI_scheme
     *
     * @param string $data_uri The data URI to parse
     *
     * @return array The result with charset, mime type and decoded data
     */
    public static function parse_data_uri($data_uri)
    {
        if (!preg_match('/^data:(?P<mime>[a-z0-9\/+-.]+)(;charset=(?P<charset>[a-z0-9-])+)?(?P<base64>;base64)?\,(?P<data>.*)?/i', $data_uri, $match)) {
            return false;
        }

        $match['data'] = rawurldecode($match['data']);
        $result = array(
            'charset' => $match['charset'] ? $match['charset'] : 'US-ASCII',
            'mime' => $match['mime'] ? $match['mime'] : 'text/plain',
            'data' => $match['base64'] ? base64_decode($match['data']) : $match['data'],
        );

        return $result;
    }

    /**
     * Decoder for RLE8 compression in windows bitmaps
     * http://msdn.microsoft.com/library/default.asp?url=/library/en-us/gdi/bitmaps_6x0u.asp
     *
     * @param string $str Data to decode
     * @param integer $width Image width
     *
     * @return string
     */
    public static function rle8_decode($str, $width)
    {
        $lineWidth = $width + (3 - ($width - 1) % 4);
        $out = '';
        $cnt = strlen($str);

        for ($i = 0; $i < $cnt; $i++) {
            $o = ord($str[$i]);
            switch ($o) {
                case 0: # ESCAPE
                    $i++;
                    switch (ord($str[$i])) {
                        case 0: # NEW LINE
                            $padCnt = $lineWidth - strlen($out) % $lineWidth;
                            if ($padCnt < $lineWidth) $out .= str_repeat(chr(0), $padCnt); # pad line
                            break;
                        case 1: # END OF FILE
                            $padCnt = $lineWidth - strlen($out) % $lineWidth;
                            if ($padCnt < $lineWidth) $out .= str_repeat(chr(0), $padCnt); # pad line
                            break 3;
                        case 2: # DELTA
                            $i += 2;
                            break;
                        default: # ABSOLUTE MODE
                            $num = ord($str[$i]);
                            for ($j = 0; $j < $num; $j++)
                                $out .= $str[++$i];
                            if ($num % 2) $i++;
                    }
                    break;
                default:
                    $out .= str_repeat($str[++$i], $o);
            }
        }
        return $out;
    }

    /**
     * Decoder for RLE4 compression in windows bitmaps
     * see http://msdn.microsoft.com/library/default.asp?url=/library/en-us/gdi/bitmaps_6x0u.asp
     *
     * @param string $str Data to decode
     * @param integer $width Image width
     *
     * @return string
     */
    public static function rle4_decode($str, $width)
    {
        $w = floor($width / 2) + ($width % 2);
        $lineWidth = $w + (3 - (($width - 1) / 2) % 4);
        $pixels = array();
        $cnt = strlen($str);
        $c = 0;

        for ($i = 0; $i < $cnt; $i++) {
            $o = ord($str[$i]);
            switch ($o) {
                case 0: # ESCAPE
                    $i++;
                    switch (ord($str[$i])) {
                        case 0: # NEW LINE
                            while (count($pixels) % $lineWidth != 0) {
                                $pixels[] = 0;
                            }
                            break;
                        case 1: # END OF FILE
                            while (count($pixels) % $lineWidth != 0) {
                                $pixels[] = 0;
                            }
                            break 3;
                        case 2: # DELTA
                            $i += 2;
                            break;
                        default: # ABSOLUTE MODE
                            $num = ord($str[$i]);
                            for ($j = 0; $j < $num; $j++) {
                                if ($j % 2 == 0) {
                                    $c = ord($str[++$i]);
                                    $pixels[] = ($c & 240) >> 4;
                                } else {
                                    $pixels[] = $c & 15;
                                }
                            }

                            if ($num % 2 == 0) {
                                $i++;
                            }
                    }
                    break;
                default:
                    $c = ord($str[++$i]);
                    for ($j = 0; $j < $o; $j++) {
                        $pixels[] = ($j % 2 == 0 ? ($c & 240) >> 4 : $c & 15);
                    }
            }
        }

        $out = '';
        if (count($pixels) % 2) {
            $pixels[] = 0;
        }

        $cnt = count($pixels) / 2;

        for ($i = 0; $i < $cnt; $i++) {
            $out .= chr(16 * $pixels[2 * $i] + $pixels[2 * $i + 1]);
        }

        return $out;
    }

    /**
     * parse a full url or pathname and return an array(protocol, host, path,
     * file + query + fragment)
     *
     * @param string $url
     * @return array
     */
    public static function explode_url($url)
    {
        $protocol = "";
        $host = "";
        $path = "";
        $file = "";

        $arr = parse_url($url);

        // Exclude windows drive letters...
        if (isset($arr["scheme"]) && $arr["scheme"] !== "file" && strlen($arr["scheme"]) > 1) {
            $protocol = $arr["scheme"] . "://";

            if (isset($arr["user"])) {
                $host .= $arr["user"];

                if (isset($arr["pass"])) {
                    $host .= ":" . $arr["pass"];
                }

                $host .= "@";
            }

            if (isset($arr["host"])) {
                $host .= $arr["host"];
            }

            if (isset($arr["port"])) {
                $host .= ":" . $arr["port"];
            }

            if (isset($arr["path"]) && $arr["path"] !== "") {
                // Do we have a trailing slash?
                if ($arr["path"][mb_strlen($arr["path"]) - 1] === "/") {
                    $path = $arr["path"];
                    $file = "";
                } else {
                    $path = rtrim(dirname($arr["path"]), '/\\') . "/";
                    $file = basename($arr["path"]);
                }
            }

            if (isset($arr["query"])) {
                $file .= "?" . $arr["query"];
            }

            if (isset($arr["fragment"])) {
                $file .= "#" . $arr["fragment"];
            }

        } else {

            $i = mb_strpos($url, "file://");
            if ($i !== false) {
                $url = mb_substr($url, $i + 7);
            }

            $protocol = ""; // "file://"; ? why doesn't this work... It's because of
            // network filenames like //COMPU/SHARENAME

            $host = ""; // localhost, really
            $file = basename($url);

            $path = dirname($url);

            // Check that the path exists
            if ($path !== false) {
                $path .= '/';

            } else {
                // generate a url to access the file if no real path found.
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';

                $host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : php_uname("n");

                if (substr($arr["path"], 0, 1) === '/') {
                    $path = dirname($arr["path"]);
                } else {
                    $path = '/' . rtrim(dirname($_SERVER["SCRIPT_NAME"]), '/') . '/' . $arr["path"];
                }
            }
        }

        $ret = array($protocol, $host, $path, $file,
            "protocol" => $protocol,
            "host" => $host,
            "path" => $path,
            "file" => $file);
        return $ret;
    }

    /**
     * @param $url
     * @param null $headers
     * @return mixed|string
     * @deprecated
     */
    public static function DOMPDF_fetch_url($url, &$headers = null)
    {
        if (function_exists("curl_init")) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, true);

            $data = curl_exec($ch);
            $raw_headers = substr($data, 0, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
            $headers = preg_split("/[\n\r]+/", trim($raw_headers));
            $data = substr($data, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
            curl_close($ch);

            return $data;
        } else {
            $data = file_get_contents($url);
            $headers = $http_response_header;

            return $data;
        }
    }

    /**
     * @return int|string
     */
    public static function DOMPDF_memory_usage() {
        if (function_exists("memory_get_peak_usage")) {
            return memory_get_peak_usage(true);
        } else if (function_exists("memory_get_usage")) {
            return memory_get_usage(true);
        } else {
            return "N/A";
        }
    }

    /**
     * Print debug messages
     *
     * @param string $type The type of debug messages to print
     * @param string $msg The message to show
     */
    public static function dompdf_debug($type, $msg)
    {
        global $_DOMPDF_DEBUG_TYPES, $_dompdf_show_warnings, $_dompdf_debug;
        if (isset($_DOMPDF_DEBUG_TYPES[$type]) && ($_dompdf_show_warnings || $_dompdf_debug)) {
            $arr = debug_backtrace();

            echo basename($arr[0]["file"]) . " (" . $arr[0]["line"] . "): " . $arr[1]["function"] . ": ";
            Helpers::pre_r($msg);
        }
    }

    /**
     * Stores warnings in an array for display later
     * This function allows warnings generated by the DomDocument parser
     * and CSS loader ({@link Stylesheet}) to be captured and displayed
     * later.  Without this function, errors are displayed immediately and
     * PDF streaming is impossible.
     * @see http://www.php.net/manual/en/function.set-error_handler.php
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param string $errline
     *
     * @throws Exception
     */
    public static function record_warnings($errno, $errstr, $errfile, $errline)
    {
        // Not a warning or notice
        if (!($errno & (E_WARNING | E_NOTICE | E_USER_NOTICE | E_USER_WARNING))) {
            throw new Exception($errstr . " $errno");
        }

        global $_dompdf_warnings;
        global $_dompdf_show_warnings;

        if ($_dompdf_show_warnings) {
            echo $errstr . "\n";
        }

        $_dompdf_warnings[] = $errstr;
    }

    /**
     * @param $c
     * @return bool|string
     */
    public static function unichr($c)
    {
        if ($c <= 0x7F) {
            return chr($c);
        } else if ($c <= 0x7FF) {
            return chr(0xC0 | $c >> 6) . chr(0x80 | $c & 0x3F);
        } else if ($c <= 0xFFFF) {
            return chr(0xE0 | $c >> 12) . chr(0x80 | $c >> 6 & 0x3F)
            . chr(0x80 | $c & 0x3F);
        } else if ($c <= 0x10FFFF) {
            return chr(0xF0 | $c >> 18) . chr(0x80 | $c >> 12 & 0x3F)
            . chr(0x80 | $c >> 6 & 0x3F)
            . chr(0x80 | $c & 0x3F);
        }
        return false;
    }

    /**
     * Converts a CMYK color to RGB
     *
     * @param float|float[] $c
     * @param float $m
     * @param float $y
     * @param float $k
     *
     * @return float[]
     */
    public static function cmyk_to_rgb($c, $m = null, $y = null, $k = null)
    {
        if (is_array($c)) {
            list($c, $m, $y, $k) = $c;
        }

        $c *= 255;
        $m *= 255;
        $y *= 255;
        $k *= 255;

        $r = (1 - round(2.55 * ($c + $k)));
        $g = (1 - round(2.55 * ($m + $k)));
        $b = (1 - round(2.55 * ($y + $k)));

        if ($r < 0) $r = 0;
        if ($g < 0) $g = 0;
        if ($b < 0) $b = 0;

        return array(
            $r, $g, $b,
            "r" => $r, "g" => $g, "b" => $b
        );
    }

    /**
     * getimagesize doesn't give a good size for 32bit BMP image v5
     *
     * @param string $filename
     * @return array The same format as getimagesize($filename)
     */
    public static function dompdf_getimagesize($filename)
    {
        static $cache = array();

        if (isset($cache[$filename])) {
            return $cache[$filename];
        }

        list($width, $height, $type) = getimagesize($filename);

        if ($width == null || $height == null) {
            $data = file_get_contents($filename, null, null, 0, 26);

            if (substr($data, 0, 2) === "BM") {
                $meta = unpack('vtype/Vfilesize/Vreserved/Voffset/Vheadersize/Vwidth/Vheight', $data);
                $width = (int)$meta['width'];
                $height = (int)$meta['height'];
                $type = IMAGETYPE_BMP;
            }
        }

        return $cache[$filename] = array($width, $height, $type);
    }
}