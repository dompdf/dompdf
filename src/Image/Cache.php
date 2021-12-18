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

        $remote = ($protocol && $protocol !== "file://") || ($parsed_url['protocol'] !== "");

        $data_uri = strpos($parsed_url['protocol'], "data:") === 0;
        $full_url = null;
        $enable_remote = $dompdf->getOptions()->getIsRemoteEnabled();
        $tempfile = false;

        try {

            // Remote not allowed and is not DataURI
            if (!$enable_remote && $remote && !$data_uri) {
                throw new ImageException("Remote file access is disabled.", E_WARNING);
            }
            
            // remote allowed or DataURI
            if (($enable_remote && $remote) || $data_uri) {
                // Download remote files to a temporary directory
                $full_url = Helpers::build_url($protocol, $host, $base_path, $url);

                // From cache
                if (isset(self::$_cache[$full_url])) {
                    $resolved_url = self::$_cache[$full_url];
                } // From remote
                else {
                    $tmp_dir = $dompdf->getOptions()->getTempDir();
                    if (($resolved_url = @tempnam($tmp_dir, "ca_dompdf_img_")) === false) {
                        throw new ImageException("Unable to create temporary image in " . $tmp_dir, E_WARNING);
                    }
                    $tempfile = $resolved_url;
                    $image = null;

                    if ($data_uri) {
                        if ($parsed_data_uri = Helpers::parse_data_uri($url)) {
                            $image = $parsed_data_uri['data'];
                        }
                    } else {
                        list($image, $http_response_header) = Helpers::getFileContent($full_url, $dompdf->getHttpContext());
                    }

                    // Image not found or invalid
                    if ($image === null) {
                        $msg = ($data_uri ? "Data-URI could not be parsed" : "Image not found");
                        throw new ImageException($msg, E_WARNING);
                    } // Image found, put in cache and process
                    else {
                        //e.g. fetch.php?media=url.jpg&cache=1
                        //- Image file name might be one of the dynamic parts of the url, don't strip off!
                        //- a remote url does not need to have a file extension at all
                        //- local cached file does not have a matching file extension
                        //Therefore get image type from the content
                        if (@file_put_contents($resolved_url, $image) === false) {
                            throw new ImageException("Unable to create temporary image in " . $tmp_dir, E_WARNING);
                        }
                    }
                }
            } // Not remote, local image
            else {
                $resolved_url = Helpers::build_url($protocol, $host, $base_path, $url);

                if ($protocol === "" || $protocol === "file://") {
                    $realfile = realpath($resolved_url);
        
                    $rootDir = realpath($dompdf->getOptions()->getRootDir());
                    if (strpos($realfile, $rootDir) !== 0) {
                        $chroot = $dompdf->getOptions()->getChroot();
                        $chrootValid = false;
                        foreach ($chroot as $chrootPath) {
                            $chrootPath = realpath($chrootPath);
                            if ($chrootPath !== false && strpos($realfile, $chrootPath) === 0) {
                                $chrootValid = true;
                                break;
                            }
                        }
                        if ($chrootValid !== true) {
                            throw new ImageException("Permission denied on $resolved_url. The file could not be found under the paths specified by Options::chroot.", E_WARNING);
                        }
                    }
        
                    if (!$realfile) {
                        throw new ImageException("File '$realfile' not found.", E_WARNING);
                    }
        
                    $resolved_url = $realfile;
                }
            }

            // Check if the local file is readable
            if (!is_readable($resolved_url) || !filesize($resolved_url)) {
                throw new ImageException("Image not readable or empty", E_WARNING);
            } // Check is the file is an image
            else {
                list($width, $height, $type) = Helpers::dompdf_getimagesize($resolved_url, $dompdf->getHttpContext());

                // Known image type
                if ($width && $height && in_array($type, ["gif", "png", "jpeg", "bmp", "svg","webp"], true)) {
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
            if ($tempfile) {
                unlink($tempfile);
            }
            $resolved_url = self::$broken_image;
            $type = "png";
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
            unlink($file);
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
