<?php
/**
 * Example of render Moon phase as an image
 *
 * @category  I18N
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org
 */

header("Content-type: image/jpeg");

if (isset($_GET['day'])) {
    $day  = (int) $_GET['day'];
} else {
    $day = 24;
}

$all  = ImageCreateFromJPEG('../images/moon.jpg');
$moon = ImageCreateTrueColor(50, 50);

if ($day > 29 || $day < 1) {
    $day = 1;
}
$day--;
ImageCopyResampled($moon, $all, 0, 0, $day*50, 0, 50, 50, 50, 50);

ImageJPEG($moon, null, 80);

ImageDestroy($all);
ImageDestroy($moon);
?>