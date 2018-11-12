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

use Dompdf\Dompdf;
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
    protected static $_cache = array();

    /**
     * The url to the "broken image" used when images can't be loaded
     *
     * @var string
     */
    public static $broken_image = "data:image/svg+xml;charset=utf8,%3C?xml version='1.0'?%3E%3Csvg width='64' height='64' xmlns='http://www.w3.org/2000/svg'%3E%3Cg%3E%3Crect stroke='%23666666' id='svg_1' height='60.499994' width='60.166667' y='1.666669' x='1.999998' stroke-width='1.5' fill='none'/%3E%3Cline stroke-linecap='null' stroke-linejoin='null' id='svg_3' y2='59.333253' x2='59.749916' y1='4.333415' x1='4.250079' stroke-width='1.5' stroke='%23999999' fill='none'/%3E%3Cline stroke-linecap='null' stroke-linejoin='null' id='svg_4' y2='59.999665' x2='4.062838' y1='3.750342' x1='60.062164' stroke-width='1.5' stroke='%23999999' fill='none'/%3E%3C/g%3E%3C/svg%3E";

    public static $error_message = "Image not found or type unknown";
    
    /**
     * Current dompdf instance
     *
     * @var Dompdf
     */
    protected static $_dompdf;

    /**
     * Resolve and fetch an image for use.
     *
     * @param string $url       The url of the image
     * @param string $protocol  Default protocol if none specified in $url
     * @param string $host      Default host if none specified in $url
     * @param string $base_path Default path if none specified in $url
     * @param Dompdf $dompdf    The Dompdf instance
     *
     * @throws ImageException
     * @return array             An array with two elements: The local path to the image and the image extension
     */
    static function resolve_url($url, $protocol, $host, $base_path, Dompdf $dompdf)
    {
        self::$_dompdf = $dompdf;
        
        $protocol = mb_strtolower($protocol);
        $parsed_url = Helpers::explode_url($url);
        $message = null;

        $remote = ($protocol && $protocol !== "file://") || ($parsed_url['protocol'] != "");

        $data_uri = strpos($parsed_url['protocol'], "data:") === 0;
        $full_url = null;
        $enable_remote = $dompdf->getOptions()->getIsRemoteEnabled();

        try {

            // Remote not allowed and is not DataURI
            if (!$enable_remote && $remote && !$data_uri) {
                throw new ImageException("Remote file access is disabled.", E_WARNING);
            } // Remote allowed or DataURI
            else {
                if ($enable_remote && $remote || $data_uri) {
                    // Download remote files to a temporary directory
                    $full_url = Helpers::build_url($protocol, $host, $base_path, $url);

                    // From cache
                    if (isset(self::$_cache[$full_url])) {
                        $resolved_url = self::$_cache[$full_url];
                    } // From remote
                    else {
                        $tmp_dir = $dompdf->getOptions()->getTempDir();
                        $resolved_url = @tempnam($tmp_dir, "ca_dompdf_img_");
                        $image = "";

                        if ($data_uri) {
                            if ($parsed_data_uri = Helpers::parse_data_uri($url)) {
                                $image = $parsed_data_uri['data'];
                            }
                        } else {
                            list($image, $http_response_header) = Helpers::getFileContent($full_url, $dompdf->getHttpContext());
                        }

                        // Image not found or invalid
                        if (strlen($image) == 0) {
                            $msg = ($data_uri ? "Data-URI could not be parsed" : "Image not found");
                            throw new ImageException($msg, E_WARNING);
                        } // Image found, put in cache and process
                        else {
                            //e.g. fetch.php?media=url.jpg&cache=1
                            //- Image file name might be one of the dynamic parts of the url, don't strip off!
                            //- a remote url does not need to have a file extension at all
                            //- local cached file does not have a matching file extension
                            //Therefore get image type from the content
                            file_put_contents($resolved_url, $image);
                        }
                    }
                } // Not remote, local image
                else {
                    $resolved_url = Helpers::build_url($protocol, $host, $base_path, $url);
                }
            }

            // Check if the local file is readable
            if (!is_readable($resolved_url) || !filesize($resolved_url)) {
                throw new ImageException("Image not readable or empty", E_WARNING);
            } // Check is the file is an image
            else {
                list($width, $height, $type) = Helpers::dompdf_getimagesize($resolved_url, $dompdf->getHttpContext());

                // Known image type
                if ($width && $height && in_array($type, array("gif", "png", "jpeg", "bmp", "svg"))) {
                    //Don't put replacement image into cache - otherwise it will be deleted on cache cleanup.
                    //Only execute on successful caching of remote image.
                    if ($enable_remote && $remote || $data_uri) {
                        self::$_cache[$full_url] = $resolved_url;
                    }
                } // Unknown image type
                else {
                    throw new ImageException("Image type unknown", E_WARNING);
                }
            }
        } catch (ImageException $e) {
            $resolved_url = self::$broken_image;
            $type = "png";
            $message = self::$error_message;
            Helpers::record_warnings($e->getCode(), $e->getMessage() . " \n $url", $e->getFile(), $e->getLine());
        }

        return array($resolved_url, $type, $message);
    }

    /**
     * Unlink all cached images (i.e. temporary images either downloaded
     * or converted)
     */
    static function clear()
    {
        if (empty(self::$_cache) || self::$_dompdf->getOptions()->getDebugKeepTemp()) {
            return;
        }

        foreach (self::$_cache as $file) {
            if (self::$_dompdf->getOptions()->getDebugPng()) {
                print "[clear unlink $file]";
            }
            unlink($file);
        }

        self::$_cache = array();
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