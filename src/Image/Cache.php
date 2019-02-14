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
    public static $broken_image = "data:image/jpeg;base64,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";

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

if (file_exists(realpath(__DIR__ . "/../../lib/res/broken_image.jpg"))) {
    Cache::$broken_image = realpath(__DIR__ . "/../../lib/res/broken_image.jpg");
}