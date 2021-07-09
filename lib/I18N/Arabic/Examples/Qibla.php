<?php
/**
 * Example of Qibla direction using compass in SVG format
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

if (isset($_GET['d'])) { 
    $degree = $_GET['d']; 
} else { 
    $degree = 0; 
}

header("Content-type: image/svg+xml");

$str = file_get_contents('./images/Compass.svg');
$arrow = '<polyline points="200,272,216,300,232,272,216,100"  transform="rotate('.$degree.',216,272)" style="fill:red"/>';
$str = str_replace('</svg>', $arrow.'</svg>', $str);

echo $str; 
?>