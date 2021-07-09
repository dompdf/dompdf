<?php
/**
 * Example of GD implementation for Arabic glyphs Class 
 *
 * @category  I18N
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org
 */

error_reporting(E_STRICT);

// Set the content-type
header("Content-type: image/png");

// Create the image
$im = @imagecreatefromgif('GD/bg.gif');

// Create some colors
$black = imagecolorallocate($im, 0, 0, 0);
$blue  = imagecolorallocate($im, 0, 0, 255);
$white = imagecolorallocate($im, 255, 255, 255);

// Replace by your own font full path and name
$path = substr(
    $_SERVER['SCRIPT_FILENAME'], 0, 
    strrpos($_SERVER['SCRIPT_FILENAME'], '/')
);
$font = $path.'/GD/ae_AlHor.ttf';

// UTF-8 charset
$text = 'بسم الله الرحمن الرحيم';
imagefill($im, 0, 0, $white);
imagettftext($im, 20, 0, 10, 50, $blue, $font, 'UTF-8:');
imagettftext($im, 20, 0, 250, 50, $black, $font, $text);

require '../../Arabic.php';
$Arabic = new I18N_Arabic('Glyphs');

$text = 'بسم الله الرحمن الرحيم';
$text = $Arabic->utf8Glyphs($text);

imagettftext($im, 20, 0, 10, 100, $blue, $font, 'Arabic Glyphs:');
imagettftext($im, 20, 0, 250, 100, $black, $font, $text);

// Using imagepng() results in clearer text compared with imagejpeg()
imagepng($im);
imagedestroy($im);
?>
