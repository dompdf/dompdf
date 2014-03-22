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
     * parse a full url or pathname and return an array(protocol, host, path,
     * file + query + fragment)
     *
     * @param string $url
     * @return array
     */
    public static function explodeUrl($url)
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
}