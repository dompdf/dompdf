<?php
/**
 * @package dompdf
 * @link    http://dompdf.github.com/
 * @author  Benj Carson <benjcarson@digitaljunkies.ca>
 * @author  Helmut Tischer <htischer@weihenstephan.org>
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */

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

