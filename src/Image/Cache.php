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
    public static $broken_image = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABABAMAAABYR2ztAAAAA3NCSVQICAjb4U/gAAAAHlBMVEWZmZn////g4OCkpKS1tbXv7++9vb2tra3m5ub5+fkFnN6oAAAACXBIWXMAAAsSAAALEgHS3X78AAAAHHRFWHRTb2Z0d2FyZQBBZG9iZSBGaXJld29ya3MgQ1M0BrLToAAAABZ0RVh0Q3JlYXRpb24gVGltZQAwNC8xMi8xMRPnI58AAAGZSURBVEiJhZbPasMwDMbTw2DHKhDQcbDQPsEge4BAjg0Mxh5gkBcY7Niwkpx32PvOjv9JspX60It/+fxJsqxW1b11gN11rA7N3v6vAd5nfR9fDYCTDiyzAeA6qgKd9QDNoAtsAKyKCxzAAfhdBuyHGwC3oovNvQOaxxJwnSNg3ZQFAlBy4ax7AG6ZBLrgA5Cn038SAPgREiaJHJASwXYEhEQQIACyikTTCWCBJJoANBfpPAKQdBLHFMBYkctcBKIE9lAGggt6gRjgA2GV44CL7m1WgS08fAAdsPHxyyMAIyHujgRwEldHArCKy5cBz90+gNOyf8TTyKOUQN2LPEmgnWWPcKD+sr+rnuqTK1avAcHfRSv3afTgVAbqmCPiggLtGM8aSkBNOidVjADrmIDYebT1PoGsWJEE8Oc0b96aZoe4iMBZPiADB6RAzEUA2vwRmyiAL3Lfv6MBSEmUEg7ALt/3LhxwLgj4QNw4UCbKEsaBNpPsyRbgVRASFig78BIGyJNIJQyQTwIi0RvgT98H+Mi6W67j3X8H/427u5bfpQGVAAAAAElFTkSuQmCC";
    
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
        $enable_remote = $dompdf->get_option("enable_remote");

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
                        $tmp_dir = $dompdf->get_option("temp_dir");
                        $resolved_url = tempnam($tmp_dir, "ca_dompdf_img_");
                        $image = "";

                        if ($data_uri) {
                            if ($parsed_data_uri = Helpers::parse_data_uri($url)) {
                                $image = $parsed_data_uri['data'];
                            }
                        } else {
                            set_error_handler(array("\\Dompdf\\Helpers", "record_warnings"));
                            $image = file_get_contents($full_url, null, $dompdf->getHttpContext());
                            restore_error_handler();
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
            $message = "Image not found or type unknown";
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
        if (empty(self::$_cache) || self::$_dompdf->get_option("debugKeepTemp")) {
            return;
        }

        foreach (self::$_cache as $file) {
            if (self::$_dompdf->get_option("debugPng")) {
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

if (file_exists(realpath(__DIR__ . "/../../lib/res/broken_image.png"))) {
    Cache::$broken_image = realpath(__DIR__ . "/../../lib/res/broken_image.png");
}