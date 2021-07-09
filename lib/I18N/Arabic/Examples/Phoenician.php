<?php
/**
 * Example of render Phoenician language transliteration
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

(!empty($_GET['w'])) ? $word = $_GET['w'] : $word='خالد الشمعة';

require '../../Arabic.php';
$x = new I18N_Arabic('Hiero');

$x->setLanguage('Phoenician');
$im = $x->str2graph($word, 'rtl', 'ar');

$w = imagesx($im);
$h = imagesy($im);

$bg  = imagecreatefromjpeg('images/bg.jpg');
$bgw = imagesx($bg);
$bgh = imagesy($bg);

// Set the content-type
header("Content-type: image/png");

imagecopyresized($bg, $im, ($bgw-$w)/2, ($bgh-$h)/2, 0, 0, $w, $h, $w, $h);

imagepng($bg);
imagedestroy($im);
imagedestroy($bg);
?>