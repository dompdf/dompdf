<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
namespace Dompdf\Image;

use Dompdf\Options;
use Dompdf\Helpers;
use Dompdf\Exception\ImageException;

/**
 * Static class that resolves image urls and downloads and caches
 * remote images if required.
 *
 * @package dompdf
 */
class Cache
{
    /**
     * Array of downloaded images.  Cached so that identical images are
     * not needlessly downloaded.
     *
     * @var array
     */
    protected static $_cache = [];

    /**
     * @var array
     */
    protected static $tempImages = [];

    /**
     * The url to the "broken image" used when images can't be loaded
     *
     * @var string
     */
    public static $broken_image = "data:image/svg+xml;charset=utf8,%3C?xml version='1.0'?%3E%3Csvg width='64' height='64' xmlns='http://www.w3.org/2000/svg'%3E%3Cg%3E%3Crect stroke='%23666666' id='svg_1' height='60.499994' width='60.166667' y='1.666669' x='1.999998' stroke-width='1.5' fill='none'/%3E%3Cline stroke-linecap='null' stroke-linejoin='null' id='svg_3' y2='59.333253' x2='59.749916' y1='4.333415' x1='4.250079' stroke-width='1.5' stroke='%23999999' fill='none'/%3E%3Cline stroke-linecap='null' stroke-linejoin='null' id='svg_4' y2='59.999665' x2='4.062838' y1='3.750342' x1='60.062164' stroke-width='1.5' stroke='%23999999' fill='none'/%3E%3C/g%3E%3C/svg%3E";

    public static $error_message = "Image not found or type unknown";
    
    /**
     * Resolve and fetch an image for use.
     *
     * @param string $url       The url of the image
     * @param string $protocol  Default protocol if none specified in $url
     * @param string $host      Default host if none specified in $url
     * @param string $base_path Default path if none specified in $url
     * @param Options $options  An instance of Dompdf\Options
     *
     * @return array            An array with three elements: The local path to the image, the image
     *                          extension, and an error message if the image could not be cached
     */
    static function resolve_url($url, $protocol, $host, $base_path, Options $options)
    {
        $tempfile = null;
        $resolved_url = null;
        $type = null;
        $message = null;
        
        try {
            $full_url = Helpers::build_url($protocol, $host, $base_path, $url);

            if ($full_url === null) {
                throw new ImageException("Unable to parse image URL $url.", E_WARNING);
            }

            $parsed_url = Helpers::explode_url($full_url);
            $protocol = strtolower($parsed_url["protocol"]);
            $is_data_uri = strpos($protocol, "data:") === 0;
            
            if (!$is_data_uri) {
                $allowed_protocols = $options->getAllowedProtocols();
                if (!array_key_exists($protocol, $allowed_protocols)) {
                    throw new ImageException("Permission denied on $url. The communication protocol is not supported.", E_WARNING);
                }
                foreach ($allowed_protocols[$protocol]["rules"] as $rule) {
                    [$result, $message] = $rule($full_url);
                    if (!$result) {
                        throw new ImageException("Error loading $url: $message", E_WARNING);
                    }
                }
            }

            if ($protocol === "file://") {
                $resolved_url = $full_url;
            } elseif (isset(self::$_cache[$full_url])) {
                $resolved_url = self::$_cache[$full_url];
            } else {
                $tmp_dir = $options->getTempDir();
                if (($resolved_url = @tempnam($tmp_dir, "ca_dompdf_img_")) === false) {
                    throw new ImageException("Unable to create temporary image in " . $tmp_dir, E_WARNING);
                }
                $tempfile = $resolved_url;

                $image = null;
                if ($is_data_uri) {
                    if (($parsed_data_uri = Helpers::parse_data_uri($url)) !== false) {
                        $image = $parsed_data_uri["data"];
                    }
                } else {
                    list($image, $http_response_header) = Helpers::getFileContent($full_url, $options->getHttpContext());
                }

                // Image not found or invalid
                if ($image === null) {
                    $msg = ($is_data_uri ? "Data-URI could not be parsed" : "Image not found");
                    throw new ImageException($msg, E_WARNING);
                }

                if (@file_put_contents($resolved_url, $image) === false) {
                    throw new ImageException("Unable to create temporary image in " . $tmp_dir, E_WARNING);
                }

                self::$_cache[$full_url] = $resolved_url;
            }

            // Check if the local file is readable
            if (!is_readable($resolved_url) || !filesize($resolved_url)) {
                throw new ImageException("Image not readable or empty", E_WARNING);
            }

            list($width, $height, $type) = Helpers::dompdf_getimagesize($resolved_url, $options->getHttpContext());

            if (($width && $height && in_array($type, ["gif", "png", "jpeg", "bmp", "svg","webp"], true)) === false) {
                throw new ImageException("Image type unknown", E_WARNING);
            }

            if ($type === "svg") {
                $parser = xml_parser_create("utf-8");
                xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);
                xml_set_element_handler(
                    $parser,
                    function ($parser, $name, $attributes) use ($options, $parsed_url, $full_url) {
                        if ($name === "image") {
                            $attributes = array_change_key_case($attributes, CASE_LOWER);
                            $url = $attributes["xlink:href"] ?? $attributes["href"];
                            if (!empty($url)) {
                                $inner_full_url = Helpers::build_url($parsed_url["protocol"], $parsed_url["host"], $parsed_url["path"], $url);
                                if ($inner_full_url === $full_url) {
                                    throw new ImageException("SVG self-reference is not allowed", E_WARNING);
                                }
                                [$resolved_url, $type, $message] = self::resolve_url($url, $parsed_url["protocol"], $parsed_url["host"], $parsed_url["path"], $options);
                                if (!empty($message)) {
                                    throw new ImageException("This SVG document references a restricted resource. $message", E_WARNING);
                                }
                            }
                        }
                    },
                    false
                );
        
                if (($fp = fopen($resolved_url, "r")) !== false) {
                    while ($line = fread($fp, 8192)) {
                        xml_parse($parser, $line, false);
                    }
                    fclose($fp);
                }
                xml_parser_free($parser);
            }
        } catch (ImageException $e) {
            if ($tempfile) {
                unlink($tempfile);
            }
            $resolved_url = self::$broken_image;
            list($width, $height, $type) = Helpers::dompdf_getimagesize($resolved_url, $options->getHttpContext());
            $message = self::$error_message;
            Helpers::record_warnings($e->getCode(), $e->getMessage() . " \n $url", $e->getFile(), $e->getLine());
            self::$_cache[$full_url] = $resolved_url;
        }

        return [$resolved_url, $type, $message];
    }

    /**
     * Register a temp file for the given original image file.
     *
     * @param string $filePath The path of the original image.
     * @param string $tempPath The path of the temp file to register.
     * @param string $key      An optional key to register the temp file at.
     */
    static function addTempImage(string $filePath, string $tempPath, string $key = "default"): void
    {
        if (!isset(self::$tempImages[$filePath])) {
            self::$tempImages[$filePath] = [];
        }

        self::$tempImages[$filePath][$key] = $tempPath;
    }

    /**
     * Get the path of a temp file registered for the given original image file.
     *
     * @param string $filePath The path of the original image.
     * @param string $key      The key the temp file is registered at.
     */
    static function getTempImage(string $filePath, string $key = "default"): ?string
    {
        return self::$tempImages[$filePath][$key] ?? null;
    }

    /**
     * Unlink all cached images (i.e. temporary images either downloaded
     * or converted) except for the bundled "broken image"
     */
    static function clear(bool $debugPng = false)
    {
        foreach (self::$_cache as $file) {
            if ($file === self::$broken_image) {
                continue;
            }
            if ($debugPng) {
                print "[clear unlink $file]";
            }
            if (file_exists($file)) {
                unlink($file);
            }
        }

        foreach (self::$tempImages as $versions) {
            foreach ($versions as $file) {
                if ($file === self::$broken_image) {
                    continue;
                }
                if ($debugPng) {
                    print "[unlink temp image $file]";
                }
                if (file_exists($file)) {
                    unlink($file);
                }
            }
        }

        self::$_cache = [];
        self::$tempImages = [];
    }

    static function detect_type($file, $context = null)
    {
        list(, , $type) = Helpers::dompdf_getimagesize($file, $context);

        return $type;
    }

    static function is_broken($url)
    {
        return $url === self::$broken_image;
    }
}

if (file_exists(realpath(__DIR__ . "/../../lib/res/broken_image.svg"))) {
    Cache::$broken_image = realpath(__DIR__ . "/../../lib/res/broken_image.svg");
}
