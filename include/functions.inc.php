<?php
/**
 * DOMPDF - PHP5 HTML to PDF renderer
 *
 * File: $RCSfile: functions.inc.php,v $
 * Created on: 2004-08-04
 *
 * Copyright (c) 2004 - Benj Carson <benjcarson@digitaljunkies.ca>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this library in the file LICENSE.LGPL; if not, write to the
 * Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * Alternatively, you may distribute this software under the terms of the
 * PHP License, version 3.0 or later.  A copy of this license should have
 * been distributed with this file in the file LICENSE.PHP .  If this is not
 * the case, you can obtain a copy at http://www.php.net/license/3_0.txt.
 *
 * The latest version of DOMPDF might be available at:
 * http://www.digitaljunkies.ca/dompdf
 *
 * @link http://www.digitaljunkies.ca/dompdf
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf
 * @version 0.5.1
 *
 * Changes
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @version 0.5.1.htischer.20090507
 * - trailing slash of base_path in build_url is no longer optional when
 *   required. This allows paths not ending in a slash, e.g. on dynamically
 *   created sites with page id in the url parameters.
 * @version 20090601
 * - fix windows paths 
 * @version 20090610
 * - relax windows path syntax, use uniform path delimiter. Used for background images.
 */

/* $Id: functions.inc.php,v 1.17 2007-08-22 23:02:07 benjcarson Exp $ */

/**
 * print_r wrapper for html/cli output
 *
 * Wraps print_r() output in < pre > tags if the current sapi is not
 * 'cli'.  Returns the output string instead of displaying it if $return is
 * true.
 *
 * @param mixed $mixed variable or expression to display
 * @param bool $return
 *
 */
if ( !function_exists("pre_r") ) {
function pre_r($mixed, $return = false) {
  if ($return)
    return "<pre>" . print_r($mixed, true) . "</pre>";

  if ( php_sapi_name() != "cli")
    echo ("<pre>");
  print_r($mixed);

  if ( php_sapi_name() != "cli")
    echo("</pre>");
  else
    echo ("\n");
  flush();

}
}

/**
 * var_dump wrapper for html/cli output
 *
 * Wraps var_dump() output in < pre > tags if the current sapi is not
 * 'cli'.
 *
 * @param mixed $mixed variable or expression to display.
 */
if ( !function_exists("pre_var_dump") ) {
function pre_var_dump($mixed) {
  if ( php_sapi_name() != "cli")
    echo("<pre>");
  var_dump($mixed);
  if ( php_sapi_name() != "cli")
    echo("</pre>");
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
function build_url($protocol, $host, $base_path, $url) {
  if ( mb_strlen($url) == 0 ) {
    //return $protocol . $host . rtrim($base_path, "/\\") . "/";
    return $protocol . $host . $base_path;
  }

  // Is the url already fully qualified?
  if ( mb_strpos($url, "://") !== false )
    return $url;

  $ret = $protocol;

  if (!in_array(mb_strtolower($protocol), array("http://", "https://", "ftp://", "ftps://"))) {
    //On Windows local file, an abs path can begin also with a '\' or a drive letter and colon
    //drive: followed by a relative path would be a drive specific default folder.
    //not known in php app code, treat as abs path 
    //($url{1} !== ':' || ($url{2}!=='\\' && $url{2}!=='/'))
    if ($url{0} !== '/' && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' || ($url{0} != '\\' && $url{1} !== ':'))) {
      // For rel path and local acess we ignore the host, and run the path through realpath()
      $ret .= dompdf_realpath($base_path).'/';
    }
    $ret .= $url; 
    return $ret;
  }

  //remote urls with backslash in html/css are not really correct, but lets be genereous
  if ( $url{0} === '/' || $url{0} === '\\' ) {
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
 * parse a full url or pathname and return an array(protocol, host, path,
 * file + query + fragment)
 *
 * @param string $url
 * @return array
 */
function explode_url($url) {
  $protocol = "";
  $host = "";
  $path = "";
  $file = "";

  $arr = parse_url($url);

  if ( isset($arr["scheme"]) &&
       $arr["scheme"] != "file" &&
       mb_strlen($arr["scheme"]) > 1 ) // Exclude windows drive letters...
    {
    $protocol = $arr["scheme"] . "://";

    if ( isset($arr["user"]) ) {
      $host .= $arr["user"];

      if ( isset($arr["pass"]) )
        $host .= "@" . $arr["pass"];

      $host .= ":";
    }

    if ( isset($arr["host"]) )
      $host .= $arr["host"];

    if ( isset($arr["port"]) )
      $host .= ":" . $arr["port"];

    if ( isset($arr["path"]) && $arr["path"] !== "" ) {
      // Do we have a trailing slash?
      if ( $arr["path"]{ mb_strlen($arr["path"]) - 1 } == "/" ) {
        $path = $arr["path"];
        $file = "";
      } else {
        $path = dirname($arr["path"]) . "/";
        $file = basename($arr["path"]);
      }
    }

    if ( isset($arr["query"]) )
      $file .= "?" . $arr["query"];

    if ( isset($arr["fragment"]) )
      $file .= "#" . $arr["fragment"];

  } else {

    $i = mb_strpos($url, "file://");
    if ( $i !== false)
      $url = mb_substr($url, $i + 7);

    $protocol = ""; // "file://"; ? why doesn't this work... It's because of
                    // network filenames like //COMPU/SHARENAME

    $host = ""; // localhost, really
    $file = basename($url);

    $path = dirname($url);

    // Check that the path exists
    if ( $path !== false ) {
      $path .= '/';

    } else {
      // generate a url to access the file if no real path found.
      $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? 'https://' : 'http://';

      $host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : php_uname("n");

      if ( substr($arr["path"], 0, 1) == '/' ) {
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
 * converts decimal numbers to roman numerals
 *
 * @param int $num
 * @return string
 */
function dec2roman($num) {

  static $ones = array("", "i", "ii", "iii", "iv", "v",
                       "vi", "vii", "viii", "ix");
  static $tens = array("", "x", "xx", "xxx", "xl", "l",
                       "lx", "lxx", "lxxx", "xc");
  static $hund = array("", "c", "cc", "ccc", "cd", "d",
                       "dc", "dcc", "dccc", "cm");
  static $thou = array("", "m", "mm", "mmm");

  if ( !is_numeric($num) )
    throw new DOMPDF_Exception("dec2roman() requires a numeric argument.");

  if ( $num > 4000 || $num < 0 )
    return "(out of range)";

  $num = strrev((string)$num);

  $ret = "";
  switch (mb_strlen($num)) {

  case 4:
    $ret .= $thou[$num{3}];

  case 3:
    $ret .= $hund[$num{2}];

  case 2:
    $ret .= $tens[$num{1}];

  case 1:
    $ret .= $ones[$num{0}];

  default:
    break;
  }
  return $ret;

}

/**
 * Determines whether $value is a percentage or not
 *
 * @param float $value
 * @return bool
 */
function is_percent($value) { return false !== mb_strpos($value, "%"); }

/**
 * Canonicalize a path without checking if the file exists
 *
 * @param  string $path The path to canonicalize
 * @return string The canonical path, or null if the path is invalid (e.g. /../../foo)
 */
function dompdf_realpath($path) {
  // If the path is relative, prepend the current directory
  if ( strtoupper(substr(PHP_OS, 0, 3)) === 'WIN' ) {
    if ( $path{0} == '/' || $path{0} == '\\' ) {
      $path = substr(getcwd(),0,2) . $path;
    } else if ($path{1} != ':' ) {
      $path = getcwd() . DIRECTORY_SEPARATOR . $path;
    }
  } else if ($path{0} != '/') {
    $path = getcwd() . DIRECTORY_SEPARATOR . $path;
  }
  $path = strtr( $path, DIRECTORY_SEPARATOR == "\\" ? "/" : DIRECTORY_SEPARATOR , DIRECTORY_SEPARATOR);
 
  $parts = explode(DIRECTORY_SEPARATOR, $path);
  $path = array();

  $i = 0;
  foreach ($parts as $dir) {

    if ( $dir == "." )
      continue;

    if ( $dir == ".." ) {
      $i--;
      if ( $i < 0 )
        $i = 0;

      unset($path[$i]);
      continue;
    }

    if ( $dir == "" )
      continue;

    $path[$i] = $dir;
    $i++;
  }

  return (DIRECTORY_SEPARATOR === '/' ? DIRECTORY_SEPARATOR : NULL) . join(DIRECTORY_SEPARATOR, $path);
}

/**
 * mb_string compatibility
 */

if ( !function_exists("mb_strlen") ) {
  function mb_strlen($str) {
    return strlen($str);
  }
}

if ( !function_exists("mb_strpos") ) {
  function mb_strpos($haystack, $needle, $offset = 0) {
    return strpos($haystack, $needle, $offset);
  }
}

if ( !function_exists("mb_strrpos") ) {
  function mb_strrpos($haystack, $needle, $offset = 0) {
    return strrpos($haystack, $needle, $offset);
  }
}

if ( !function_exists("mb_substr") ) {
  function mb_substr($str, $start, $length = null) {
    if ( is_null($length) )
      return substr($str, $start);
    else
      return substr($str, $start, $length);
  }
}

if ( !function_exists("mb_strtolower") ) {
  function mb_strtolower($str) {
    return strtolower($str);
  }
}

if ( !function_exists("mb_strtoupper") ) {
  function mb_strtoupper($str) {
    return strtoupper($str);
  }
}

if ( !function_exists("mb_substr_count") ) {
  function mb_substr_count($haystack, $needle) {
    return substr_count($haystack, $needle);
  }
}


/**
 * Stores warnings in an array for display later
 *
 * This function allows warnings generated by the DomDocument parser
 * and CSS loader ({@link Stylesheet}) to be captured and displayed
 * later.  Without this function, errors are displayed immediately and
 * PDF streaming is impossible.
 *
 * @see http://www.php.net/manual/en/function.set-error_handler.php
 *
 * @param int $errno
 * @param string $errstr
 * @param string $errfile
 * @param string $errline
 */
function record_warnings($errno, $errstr, $errfile, $errline) {

  if ( !($errno & (E_WARNING | E_NOTICE | E_USER_NOTICE | E_USER_WARNING )) ) // Not a warning or notice
    throw new DOMPDF_Exception($errstr . " $errno");

  global $_dompdf_warnings;
  global $_dompdf_show_warnings;

  if ( $_dompdf_show_warnings )
    echo $errstr . "\n";

  $_dompdf_warnings[] = $errstr;
}

/**
 * Print a useful backtrace
 */
function bt() {
  $bt = debug_backtrace();

  array_shift($bt); // remove actual bt() call
  echo "\n";

  $i = 0;
  foreach ($bt as $call) {
    $file = basename($call["file"]) . " (" . $call["line"] . ")";
    if ( isset($call["class"]) ) {
      $func = $call["class"] . "->" . $call["function"] . "()";
    } else {
      $func = $call["function"] . "()";
    }

    echo "#" . str_pad($i, 2, " ", STR_PAD_RIGHT) . ": " . str_pad($file.":", 42) . " $func\n";
    $i++;
  }
  echo "\n";
}

/**
 * Print debug messages
 *
 * @param string $type  The type of debug messages to print
 */
function dompdf_debug($type, $msg) {
  global $_DOMPDF_DEBUG_TYPES;
  global $_dompdf_show_warnings;
  global $_dompdf_debug;
  if ( isset($_DOMPDF_DEBUG_TYPES[$type]) && ($_dompdf_show_warnings || $_dompdf_debug) ) {
    $arr = debug_backtrace();

    echo basename($arr[0]["file"]) . " (" . $arr[0]["line"] ."): " . $arr[1]["function"] . ": ";
    pre_r($msg);
  }
}

/**
 * Dump memory usage
 */
if ( !function_exists("print_memusage") ) {
function print_memusage() {
  global $memusage;
  echo ("Memory Usage\n");
  $prev = 0;
  $initial = reset($memusage);
  echo (str_pad("Initial:", 40) . $initial . "\n\n");

  foreach ($memusage as $key=>$mem) {
    $mem -= $initial;
    echo (str_pad("$key:" , 40));
    echo (str_pad("$mem", 12) . "(diff: " . ($mem - $prev) . ")\n");
    $prev = $mem;
  }

  echo ("\n" . str_pad("Total:", 40) . memory_get_usage()) . "\n";
}
}

/**
 * Initialize memory profiling code
 */
if ( !function_exists("enable_mem_profile") ) {
function enable_mem_profile() {
    global $memusage;
    $memusage = array("Startup" => memory_get_usage());
    register_shutdown_function("print_memusage");
}
}

/**
 * Record the current memory usage
 *
 * @param string $location a meaningful location
 */
if ( !function_exists("mark_memusage") ) {
function mark_memusage($location) {
  global $memusage;
  if ( isset($memusage) )
    $memusage[$location] = memory_get_usage();
}
}

/**
 * Find the current system temporary directory
 *
 * @link http://us.php.net/manual/en/function.sys-get-temp-dir.php#85261
 */
if ( !function_exists('sys_get_temp_dir')) {
  function sys_get_temp_dir() {
    if (!empty($_ENV['TMP'])) { return realpath($_ENV['TMP']); }
    if (!empty($_ENV['TMPDIR'])) { return realpath( $_ENV['TMPDIR']); }
    if (!empty($_ENV['TEMP'])) { return realpath( $_ENV['TEMP']); }
    $tempfile=tempnam(uniqid(rand(),TRUE),'');
    if (file_exists($tempfile)) {
    unlink($tempfile);
    return realpath(dirname($tempfile));
    }
  }
}
?>