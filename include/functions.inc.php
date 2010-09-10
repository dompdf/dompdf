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
 * http://www.dompdf.com/
 *
 * @link http://www.dompdf.com/
 * @copyright 2004 Benj Carson
 * @author Benj Carson <benjcarson@digitaljunkies.ca>
 * @contributor Helmut Tischer <htischer@weihenstephan.org>
 * @package dompdf
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

/* $Id$ */

function def($name, $value = true) {
  if (!defined($name)) {
    define($name, $value);
  }
}

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

  if ( php_sapi_name() !== "cli")
    echo ("<pre>");
  print_r($mixed);

  if ( php_sapi_name() !== "cli")
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
  if ( php_sapi_name() !== "cli")
    echo("<pre>");
  var_dump($mixed);
  if ( php_sapi_name() !== "cli")
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

  // Is the url already fully qualified or a Data URI?
  if ( mb_strpos($url, "://") !== false || mb_strpos($url, "data:") === 0 )
    return $url;

  $ret = $protocol;

  if (!in_array(mb_strtolower($protocol), array("http://", "https://", "ftp://", "ftps://"))) {
    //On Windows local file, an abs path can begin also with a '\' or a drive letter and colon
    //drive: followed by a relative path would be a drive specific default folder.
    //not known in php app code, treat as abs path
    //($url[1] !== ':' || ($url[2]!=='\\' && $url[2]!=='/'))
    if ($url[0] !== '/' && (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN' || ($url[0] !== '\\' && $url[1] !== ':'))) {
      // For rel path and local acess we ignore the host, and run the path through realpath()
      $ret .= realpath($base_path).'/';
    }
    $ret .= $url;
    $ret = preg_replace("/\?(.*)$/", "", $ret);
    return $ret;
  }

  //remote urls with backslash in html/css are not really correct, but lets be genereous
  if ( $url[0] === '/' || $url[0] === '\\' ) {
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
       $arr["scheme"] !== "file" &&
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
      if ( $arr["path"]{ mb_strlen($arr["path"]) - 1 } === "/" ) {
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
      $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';

      $host = isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : php_uname("n");

      if ( substr($arr["path"], 0, 1) === '/' ) {
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
    $ret .= $thou[$num[3]];

  case 3:
    $ret .= $hund[$num[2]];

  case 2:
    $ret .= $tens[$num[1]];

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
 * @return bool
 */
function is_percent($value) { return false !== mb_strpos($value, "%"); }

function parse_data_uri($data_uri) {
  if (!preg_match('/^data:(?P<mime>[a-z0-9\/+-.]+)(;charset=(?P<charset>[a-z0-9-])+)?(?P<base64>;base64)?\,(?P<data>.*)?/i', $data_uri, $match)) {
    return false;
  }
  
  $match['data'] = urldecode($match['data']);
  $result = array(
    'charset' => $match['charset'] ? $match['charset'] : 'US-ASCII',
    'mime'    => $match['mime'] ? $match['mime'] : 'text/plain',
    'data'    => $match['base64'] ? base64_decode($match['data']) : $match['data'],
  );
  
  return $result;
}

/**
 * mb_string compatibility
 */

if ( !function_exists("mb_convert_encoding") ) {
  function mb_convert_encoding($data, $to_encoding, $from_encoding='UTF-8') {
    if (str_replace('-', '', strtolower($to_encoding)) == 'utf8') {
      return utf8_encode($data);
    } else {
      return utf8_decode($data);
    }
  }
}

if ( !function_exists("mb_detect_encoding") ) {
  function mb_detect_encoding($data, $encoding_list=array('iso-8859-1'), $strict=false) {
    return 'iso-8859-1';
  }
}

if ( !function_exists("mb_detect_order") ) {
  function mb_detect_order($encoding_list=array('iso-8859-1')) {
    return 'iso-8859-1';
  }
}

if ( !function_exists("mb_internal_encoding") ) {
  function mb_internal_encoding($encoding=NULL) {
    if (isset($encoding)) {
      return true;
    } else {
      return 'iso-8859-1';
    }
  }
}

if ( !function_exists("mb_strlen") ) {
  function mb_strlen($str, $encoding='iso-8859-1') {
    if (str_replace('-', '', strtolower($encoding)) == 'utf8') {
      return strlen(utf8_encode($str));
    } else {
      return strlen(utf8_decode($str));
    }
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

if ( !function_exists("mb_substr") ) {
  function mb_substr($str, $start, $length=null, $encoding='iso-8859-1') {
    if ( is_null($length) )
      return substr($str, $start);
    else
      return substr($str, $start, $length);
  }
}

if ( !function_exists("mb_substr_count") ) {
  function mb_substr_count($haystack, $needle) {
    return substr_count($haystack, $needle);
  }
}

# Decoder for RLE8 compression in windows bitmaps
# see http://msdn.microsoft.com/library/default.asp?url=/library/en-us/gdi/bitmaps_6x0u.asp
function rle8_decode ($str, $width){
  $lineWidth = $width + (3 - ($width-1) % 4);
  $out = '';
  $cnt = strlen($str);
  
  for ($i = 0; $i <$cnt; $i++) {
    $o = ord($str[$i]);
    switch ($o){
      case 0: # ESCAPE
        $i++;
        switch (ord($str[$i])){
          case 0: # NEW LINE
            $padCnt = $lineWidth - strlen($out)%$lineWidth;
            if ($padCnt<$lineWidth) $out .= str_repeat(chr(0), $padCnt); # pad line
            break;
          case 1: # END OF FILE
            $padCnt = $lineWidth - strlen($out)%$lineWidth;
            if ($padCnt<$lineWidth) $out .= str_repeat(chr(0), $padCnt); # pad line
            break 3;
          case 2: # DELTA
            $i += 2;
            break;
          default: # ABSOLUTE MODE
            $num = ord($str[$i]);
            for ($j = 0; $j < $num; $j++)
              $out .= $str[++$i];
            if ($num % 2) $i++;
        }
      break;
      default:
      $out .= str_repeat($str[++$i], $o);
    }
  }
  return $out;
}

# Decoder for RLE4 compression in windows bitmaps
# see http://msdn.microsoft.com/library/default.asp?url=/library/en-us/gdi/bitmaps_6x0u.asp
function rle4_decode ($str, $width) {
  $w = floor($width/2) + ($width % 2);
  $lineWidth = $w + (3 - ( ($width-1) / 2) % 4);    
  $pixels = array();
  $cnt = strlen($str);
  
  for ($i = 0; $i < $cnt; $i++) {
    $o = ord($str[$i]);
    switch ($o) {
      case 0: # ESCAPE
        $i++;
        switch (ord($str[$i])){
          case 0: # NEW LINE
            while (count($pixels)%$lineWidth!=0)
              $pixels[]=0;
            break;
          case 1: # END OF FILE
            while (count($pixels)%$lineWidth!=0)
              $pixels[]=0;
            break 3;
          case 2: # DELTA
            $i += 2;
            break;
          default: # ABSOLUTE MODE
            $num = ord($str[$i]);
            for ($j = 0; $j < $num; $j++){
              if ($j%2 == 0){
                $c = ord($str[++$i]);
                $pixels[] = ($c & 240)>>4;
              } else
                $pixels[] = $c & 15;
            }
            if ($num % 2) $i++;
       }
       break;
      default:
        $c = ord($str[++$i]);
        for ($j = 0; $j < $o; $j++)
          $pixels[] = ($j%2==0 ? ($c & 240)>>4 : $c & 15);
    }
  }
  
  $out = '';
  if (count($pixels)%2) $pixels[]=0;
  $cnt = count($pixels)/2;
  
  for ($i = 0; $i < $cnt; $i++)
    $out .= chr(16*$pixels[2*$i] + $pixels[2*$i+1]);
    
  return $out;
} 

/**
 * Credit goes to mgutt 
 * http://www.programmierer-forum.de/function-imagecreatefrombmp-welche-variante-laeuft-t143137.htm
 * Modified by Fabien Ménager to support RGB555 BMP format
 */
if ( !function_exists("imagecreatefrombmp") ) {
function imagecreatefrombmp($filename) {
  try {
  // version 1.00
  if (!($fh = fopen($filename, 'rb'))) {
    trigger_error('imagecreatefrombmp: Can not open ' . $filename, E_USER_WARNING);
    return false;
  }
  
  // read file header
  $meta = unpack('vtype/Vfilesize/Vreserved/Voffset', fread($fh, 14));
  
  // check for bitmap
  if ($meta['type'] != 19778) {
    trigger_error('imagecreatefrombmp: ' . $filename . ' is not a bitmap!', E_USER_WARNING);
    return false;
  }
  
  // read image header
  $meta += unpack('Vheadersize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', fread($fh, 40));

  // read additional bitfield header
  if ($meta['compression'] == 3) {
    $meta += unpack('VrMask/VgMask/VbMask', fread($fh, 12));
  }
  
  //pre_r($filename);pre_r($meta);
  
  // set bytes and padding
  $meta['bytes'] = $meta['bits'] / 8;
  $meta['decal'] = 4 - (4 * (($meta['width'] * $meta['bytes'] / 4)- floor($meta['width'] * $meta['bytes'] / 4)));
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
  $palette = array();
  if ($meta['bits'] < 16) {
    $palette = unpack('l' . $meta['colors'], fread($fh, $meta['colors'] * 4));
    // in rare cases the color value is signed
    if ($palette[1] < 0) {
      foreach ($palette as $i => $color) {
        $palette[$i] = $color + 16777216;
      }
    }
  }
  
  // create gd image
  $im = imagecreatetruecolor($meta['width'], $meta['height']);
  $data = fread($fh, $meta['imagesize']);
  
  // uncompress data
  switch ($meta['compression']) {
    case 1: $data = rle8_decode($data, $meta['width']); break;
    case 2: $data = rle4_decode($data, $meta['width']); break;
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

          if (empty($meta['rMask']) || $meta['rMask'] != 0xf800)
            $color[1] = (($color[1] & 0x7c00) >> 7) * 65536 + (($color[1] & 0x03e0) >> 2) * 256 + (($color[1] & 0x001f) << 3); // 555
          else 
            $color[1] = (($color[1] & 0xf800) >> 8) * 65536 + (($color[1] & 0x07e0) >> 3) * 256 + (($color[1] & 0x001f) << 3); // 565
          break;
        case 8:
          $color = unpack('n', $vide . substr($data, $p, 1));
          $color[1] = $palette[ $color[1] + 1 ];
          break;
        case 4:
          $color = unpack('n', $vide . substr($data, floor($p), 1));
          $color[1] = ($p * 2) % 2 == 0 ? $color[1] >> 4 : $color[1] & 0x0F;
          $color[1] = $palette[ $color[1] + 1 ];
          break;
        case 1:
          $color = unpack('n', $vide . substr($data, floor($p), 1));
          switch (($p * 8) % 8) {
            case 0: $color[1] =  $color[1] >> 7; break;
            case 1: $color[1] = ($color[1] & 0x40) >> 6; break;
            case 2: $color[1] = ($color[1] & 0x20) >> 5; break;
            case 3: $color[1] = ($color[1] & 0x10) >> 4; break;
            case 4: $color[1] = ($color[1] & 0x8 ) >> 3; break;
            case 5: $color[1] = ($color[1] & 0x4 ) >> 2; break;
            case 6: $color[1] = ($color[1] & 0x2 ) >> 1; break;
            case 7: $color[1] = ($color[1] & 0x1 );      break;
          }
          $color[1] = $palette[ $color[1] + 1 ];
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
  } catch (Exception $e) {var_dump($e);}
}
}

/**
 * @param int $c
 * @param int $m
 * @param int $y
 * @param int $k
 * @return object
 */
function cmyk_to_rgb($c, $m = null, $y = null, $k = null) {
  if (is_array($c)) {
    list($c, $m, $y, $k) = $c;
  }
  
  $c *= 255;
  $m *= 255;
  $y *= 255;
  $k *= 255;
  
  $r = (1 - round(2.55 * ($c+$k))) ;
  $g = (1 - round(2.55 * ($m+$k))) ;
  $b = (1 - round(2.55 * ($y+$k))) ;
    
  if($r<0) $r = 0;
  if($g<0) $g = 0;
  if($b<0) $b = 0;
    
  return array(
    $r, $g, $b,
    "r" => $r, "g" => $g, "b" => $b
  );
}

function unichr($c) {
  if ($c <= 0x7F) {
    return chr($c);
  } else if ($c <= 0x7FF) {
    return chr(0xC0 | $c >>  6) . chr(0x80 | $c & 0x3F);
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

if ( !function_exists("date_default_timezone_get") ) {
  function date_default_timezone_get() {
    return "";
  }
}

if ( !function_exists("date_default_timezone_set") ) {
  function date_default_timezone_set($timezone_identifier) {
    return true;
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
  global $_DOMPDF_DEBUG_TYPES, $_dompdf_show_warnings, $_dompdf_debug;
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

/**
 * Affect null to the unused objects
 * @param unknown_type $object
 */
function clear_object(&$object) {  
  if ( is_object($object) ) {
    foreach (array_keys((array)$object) as $key) {
      clear_object($property);
    }
    foreach(get_class_vars(get_class($object)) as $property => $value) {
      clear_object($property);
    }
  }
  $object = null;
  unset($object);
}
