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
     * The url to the "broken image" used when images can't be loade
     *
     * @var string
     */
    public static $broken_image = "/../../res/broken_image.png";
    
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
        
        $parsed_url = Helpers::explode_url($url);
        $message = null;

        $remote = ($protocol && $protocol !== "file://") || ($parsed_url['protocol'] != "");

        $data_uri = strpos($parsed_url['protocol'], "data:") === 0;
        $full_url = null;
        $enable_remote = $dompdf->get_option("enable_remote");

        try {

            // Remote not allowed and is not DataURI
            if (!$enable_remote && $remote && !$data_uri) {
                throw new ImageException("Remote file access is disabled.");
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
                            $image = file_get_contents($full_url);
                            restore_error_handler();
                        }

                        // Image not found or invalid
                        if (strlen($image) == 0) {
                            $msg = ($data_uri ? "Data-URI could not be parsed" : "Image not found");
                            throw new ImageException($msg);
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
                throw new ImageException("Image not readable or empty");
            } // Check is the file is an image
            else {
                list($width, $height, $type) = Helpers::dompdf_getimagesize($resolved_url);

                // Known image type
                if ($width && $height && in_array(
                        $type,
                        array(IMAGETYPE_GIF, IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_BMP)
                    )
                ) {
                    //Don't put replacement image into cache - otherwise it will be deleted on cache cleanup.
                    //Only execute on successful caching of remote image.
                    if ($enable_remote && $remote || $data_uri) {
                        self::$_cache[$full_url] = $resolved_url;
                    }
                } // Unknown image type
                else {
                    throw new ImageException("Image type unknown");
                }
            }
        } catch (ImageException $e) {
            $resolved_url = self::$broken_image;
            $type = IMAGETYPE_PNG;
            $message = $e->getMessage() . " \n $url";
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

    static function detect_type($file)
    {
        list(, , $type) = Helpers::dompdf_getimagesize($file);

        return $type;
    }

    static function type_to_ext($type)
    {
        $image_types = array(
            IMAGETYPE_GIF  => "gif",
            IMAGETYPE_PNG  => "png",
            IMAGETYPE_JPEG => "jpeg",
            IMAGETYPE_BMP  => "bmp",
        );

        return (isset($image_types[$type]) ? $image_types[$type] : null);
    }

    static function is_broken($url)
    {
        return $url === self::$broken_image;
    }
}