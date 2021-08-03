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
     * @return string|null
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

        return null;
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
        $protocol = mb_strtolower($protocol);
        if (strlen($url) == 0) {
            //return $protocol . $host . rtrim($base_path, "/\\") . "/";
            return $protocol . $host . $base_path;
        }

        // Is the url already fully qualified, a Data URI, or a reference to a named anchor?
        // File-protocol URLs may require additional processing (e.g. for URLs with a relative path)
        if ((mb_strpos($url, "://") !== false && substr($url, 0, 7) !== "file://") || mb_substr($url, 0, 1) === "#" || mb_strpos($url, "data:") === 0 || mb_strpos($url, "mailto:") === 0 || mb_strpos($url, "tel:") === 0) {
            return $url;
        }

        if (strpos($url, "file://") === 0) {
            $url = substr($url, 7);
            $protocol = "";
        }

        $ret = "";
        if ($protocol != "file://") {
            $ret = $protocol;
        }

        if (!in_array(mb_strtolower($protocol), ["http://", "https://", "ftp://", "ftps://"])) {
            //On Windows local file, an abs path can begin also with a '\' or a drive letter and colon
            //drive: followed by a relative path would be a drive specific default folder.
            //not known in php app code, treat as abs path
            //($url[1] !== ':' || ($url[2]!=='\\' && $url[2]!=='/'))
            if ($url[0] !== '/' && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' || (mb_strlen($url) > 1 && $url[0] !== '\\' && $url[1] !== ':'))) {
                // For rel path and local access we ignore the host, and run the path through realpath()
                $ret .= realpath($base_path) . '/';
            }
            $ret .= $url;
            $ret = preg_replace('/\?(.*)$/', "", $ret);
            return $ret;
        }

        // Protocol relative urls (e.g. "//example.org/style.css")
        if (strpos($url, '//') === 0) {
            $ret .= substr($url, 2);
            //remote urls with backslash in html/css are not really correct, but lets be genereous
        } elseif ($url[0] === '/' || $url[0] === '\\') {
            // Absolute path
            $ret .= $host . $url;
        } else {
            // Relative path
            //$base_path = $base_path !== "" ? rtrim($base_path, "/\\") . "/" : "";
            $ret .= $host . $base_path . $url;
        }

        // URL should now be complete, final cleanup
        $parsed_url = parse_url($ret);

        // reproduced from https://www.php.net/manual/en/function.parse-url.php#106731
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        
        // partially reproduced from https://stackoverflow.com/a/1243431/264628
        /* replace '//' or '/./' or '/foo/../' with '/' */
        $re = array('#(/\.?/)#', '#/(?!\.\.)[^/]+/\.\./#');
        for ($n=1; $n>0; $path=preg_replace($re, '/', $path, -1, $n)) {}

        $ret = "$scheme$user$pass$host$port$path$query$fragment";

        return $ret;
    }

    /**
     * Builds a HTTP Content-Disposition header string using `$dispositionType`
     * and `$filename`.
     *
     * If the filename contains any characters not in the ISO-8859-1 character
     * set, a fallback filename will be included for clients not supporting the
     * `filename*` parameter.
     *
     * @param string $dispositionType
     * @param string $filename
     * @return string
     */
    public static function buildContentDispositionHeader($dispositionType, $filename)
    {
        $encoding = mb_detect_encoding($filename);
        $fallbackfilename = mb_convert_encoding($filename, "ISO-8859-1", $encoding);
        $fallbackfilename = str_replace("\"", "", $fallbackfilename);
        $encodedfilename = rawurlencode($filename);

        $contentDisposition = "Content-Disposition: $dispositionType; filename=\"$fallbackfilename\"";
        if ($fallbackfilename !== $filename) {
            $contentDisposition .= "; filename*=UTF-8''$encodedfilename";
        }

        return $contentDisposition;
    }

    /**
     * Converts decimal numbers to roman numerals.
     *
     * As numbers larger than 3999 (and smaller than 1) cannot be represented in
     * the standard form of roman numerals, those are left in decimal form.
     *
     * See https://en.wikipedia.org/wiki/Roman_numerals#Standard_form
     *
     * @param int|string $num
     *
     * @throws Exception
     * @return string
     */
    public static function dec2roman($num): string
    {

        static $ones = ["", "i", "ii", "iii", "iv", "v", "vi", "vii", "viii", "ix"];
        static $tens = ["", "x", "xx", "xxx", "xl", "l", "lx", "lxx", "lxxx", "xc"];
        static $hund = ["", "c", "cc", "ccc", "cd", "d", "dc", "dcc", "dccc", "cm"];
        static $thou = ["", "m", "mm", "mmm"];

        if (!is_numeric($num)) {
            throw new Exception("dec2roman() requires a numeric argument.");
        }

        if ($num >= 4000 || $num <= 0) {
            return (string) $num;
        }

        $num = strrev((string)$num);

        $ret = "";
        switch (mb_strlen($num)) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 4:
                $ret .= $thou[$num[3]];
            /** @noinspection PhpMissingBreakStatementInspection */
            case 3:
                $ret .= $hund[$num[2]];
            /** @noinspection PhpMissingBreakStatementInspection */
            case 2:
                $ret .= $tens[$num[1]];
            /** @noinspection PhpMissingBreakStatementInspection */
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
     * @return array|bool The result with charset, mime type and decoded data
     */
    public static function parse_data_uri($data_uri)
    {
        if (!preg_match('/^data:(?P<mime>[a-z0-9\/+-.]+)(;charset=(?P<charset>[a-z0-9-])+)?(?P<base64>;base64)?\,(?P<data>.*)?/is', $data_uri, $match)) {
            return false;
        }

        $match['data'] = rawurldecode($match['data']);
        $result = [
            'charset' => $match['charset'] ? $match['charset'] : 'US-ASCII',
            'mime' => $match['mime'] ? $match['mime'] : 'text/plain',
            'data' => $match['base64'] ? base64_decode($match['data']) : $match['data'],
        ];

        return $result;
    }

    /**
     * Encodes a Uniform Resource Identifier (URI) by replacing non-alphanumeric
     * characters with a percent (%) sign followed by two hex digits, excepting
     * characters in the URI reserved character set.
     *
     * Assumes that the URI is a complete URI, so does not encode reserved
     * characters that have special meaning in the URI.
     *
     * Simulates the encodeURI function available in JavaScript
     * https://developer.mozilla.org/en/JavaScript/Reference/Global_Objects/encodeURI
     *
     * Source: http://stackoverflow.com/q/4929584/264628
     *
     * @param string $uri The URI to encode
     * @return string The original URL with special characters encoded
     */
    public static function encodeURI($uri) {
        $unescaped = [
            '%2D'=>'-','%5F'=>'_','%2E'=>'.','%21'=>'!', '%7E'=>'~',
            '%2A'=>'*', '%27'=>"'", '%28'=>'(', '%29'=>')'
        ];
        $reserved = [
            '%3B'=>';','%2C'=>',','%2F'=>'/','%3F'=>'?','%3A'=>':',
            '%40'=>'@','%26'=>'&','%3D'=>'=','%2B'=>'+','%24'=>'$'
        ];
        $score = [
            '%23'=>'#'
        ];
        return strtr(rawurlencode(rawurldecode($uri)), array_merge($reserved, $unescaped, $score));
    }

    /**
     * Decoder for RLE8 compression in windows bitmaps
     * http://msdn.microsoft.com/library/default.asp?url=/library/en-us/gdi/bitmaps_6x0u.asp
     *
     * @param string $str Data to decode
     * @param int $width Image width
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
                            if ($padCnt < $lineWidth) {
                                $out .= str_repeat(chr(0), $padCnt); # pad line
                            }
                            break;
                        case 1: # END OF FILE
                            $padCnt = $lineWidth - strlen($out) % $lineWidth;
                            if ($padCnt < $lineWidth) {
                                $out .= str_repeat(chr(0), $padCnt); # pad line
                            }
                            break 3;
                        case 2: # DELTA
                            $i += 2;
                            break;
                        default: # ABSOLUTE MODE
                            $num = ord($str[$i]);
                            for ($j = 0; $j < $num; $j++) {
                                $out .= $str[++$i];
                            }
                            if ($num % 2) {
                                $i++;
                            }
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
     * @param int $width Image width
     *
     * @return string
     */
    public static function rle4_decode($str, $width)
    {
        $w = floor($width / 2) + ($width % 2);
        $lineWidth = $w + (3 - (($width - 1) / 2) % 4);
        $pixels = [];
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
        if ( isset($arr["scheme"]) ) {
            $arr["scheme"] = mb_strtolower($arr["scheme"]);
        }

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

            $i = mb_stripos($url, "file://");
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

        $ret = [$protocol, $host, $path, $file,
            "protocol" => $protocol,
            "host" => $host,
            "path" => $path,
            "file" => $file];
        return $ret;
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
        if (!($errno & (E_WARNING | E_NOTICE | E_USER_NOTICE | E_USER_WARNING | E_STRICT | E_DEPRECATED | E_USER_DEPRECATED))) {
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
            [$c, $m, $y, $k] = $c;
        }

        $c *= 255;
        $m *= 255;
        $y *= 255;
        $k *= 255;

        $r = (1 - round(2.55 * ($c + $k)));
        $g = (1 - round(2.55 * ($m + $k)));
        $b = (1 - round(2.55 * ($y + $k)));

        if ($r < 0) {
            $r = 0;
        }
        if ($g < 0) {
            $g = 0;
        }
        if ($b < 0) {
            $b = 0;
        }

        return [
            $r, $g, $b,
            "r" => $r, "g" => $g, "b" => $b
        ];
    }

    /**
     * getimagesize doesn't give a good size for 32bit BMP image v5
     *
     * @param string $filename
     * @param resource $context
     * @return array The same format as getimagesize($filename)
     */
    public static function dompdf_getimagesize($filename, $context = null)
    {
        static $cache = [];

        if (isset($cache[$filename])) {
            return $cache[$filename];
        }

        [$width, $height, $type] = getimagesize($filename);

        // Custom types
        $types = [
            IMAGETYPE_JPEG => "jpeg",
            IMAGETYPE_GIF  => "gif",
            IMAGETYPE_BMP  => "bmp",
            IMAGETYPE_PNG  => "png",
            IMAGETYPE_WEBP => "webp",
        ];

        $type = isset($types[$type]) ? $types[$type] : null;

        if ($width == null || $height == null) {
            [$data, $headers] = Helpers::getFileContent($filename, $context);

            if (!empty($data)) {
                if (substr($data, 0, 2) === "BM") {
                    $meta = unpack('vtype/Vfilesize/Vreserved/Voffset/Vheadersize/Vwidth/Vheight', $data);
                    $width = (int)$meta['width'];
                    $height = (int)$meta['height'];
                    $type = "bmp";
                } else {
                    if (strpos($data, "<svg") !== false) {
                        $doc = new \Svg\Document();
                        $doc->loadFile($filename);

                        [$width, $height] = $doc->getDimensions();
                        $type = "svg";
                    }
                }
            }
        }

        return $cache[$filename] = [$width, $height, $type];
    }

    /**
     * Credit goes to mgutt
     * http://www.programmierer-forum.de/function-imagecreatefrombmp-welche-variante-laeuft-t143137.htm
     * Modified by Fabien Menager to support RGB555 BMP format
     */
    public static function imagecreatefrombmp($filename, $context = null)
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
        $palette = [];
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

    /**
     * Gets the content of the file at the specified path using one of
     * the following methods, in preferential order:
     *  - file_get_contents: if allow_url_fopen is true or the file is local
     *  - curl: if allow_url_fopen is false and curl is available
     *
     * @param string $uri
     * @param resource $context (ignored if curl is used)
     * @param int $offset
     * @param int $maxlen (ignored if curl is used)
     * @return string[]
     */
    public static function getFileContent($uri, $context = null, $offset = 0, $maxlen = null)
    {
        $content = null;
        $headers = null;
        [$proto, $host, $path, $file] = Helpers::explode_url($uri);
        $is_local_path = ($proto == '' || $proto === 'file://');

        set_error_handler([self::class, 'record_warnings']);

        try {
            if ($is_local_path || ini_get('allow_url_fopen')) {
                if ($is_local_path === false) {
                    $uri = Helpers::encodeURI($uri);
                }
                if (isset($maxlen)) {
                    $result = file_get_contents($uri, null, $context, $offset, $maxlen);
                } else {
                    $result = file_get_contents($uri, null, $context, $offset);
                }
                if ($result !== false) {
                    $content = $result;
                }
                if (isset($http_response_header)) {
                    $headers = $http_response_header;
                }

            } elseif (function_exists('curl_exec')) {
                $curl = curl_init($uri);

                //TODO: use $context to define additional curl options
                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_HEADER, true);
                if ($offset > 0) {
                    curl_setopt($curl, CURLOPT_RESUME_FROM, $offset);
                }

                $data = curl_exec($curl);

                if ($data !== false && !curl_errno($curl)) {
                    switch ($http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE)) {
                        case 200:
                            $raw_headers = substr($data, 0, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
                            $headers = preg_split("/[\n\r]+/", trim($raw_headers));
                            $content = substr($data, curl_getinfo($curl, CURLINFO_HEADER_SIZE));
                            break;
                    }
                }
                curl_close($curl);
            }
        } finally {
            restore_error_handler();
        }

        return [$content, $headers];
    }

    public static function mb_ucwords($str) {
        $max_len = mb_strlen($str);
        if ($max_len === 1) {
            return mb_strtoupper($str);
        }

        $str = mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);

        foreach ([' ', '.', ',', '!', '?', '-', '+'] as $s) {
            $pos = 0;
            while (($pos = mb_strpos($str, $s, $pos)) !== false) {
                $pos++;
                // Nothing to do if the separator is the last char of the string
                if ($pos !== false && $pos < $max_len) {
                    // If the char we want to upper is the last char there is nothing to append behind
                    if ($pos + 1 < $max_len) {
                        $str = mb_substr($str, 0, $pos) . mb_strtoupper(mb_substr($str, $pos, 1)) . mb_substr($str, $pos + 1);
                    } else {
                        $str = mb_substr($str, 0, $pos) . mb_strtoupper(mb_substr($str, $pos, 1));
                    }
                }
            }
        }

        return $str;
    }
}
