<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Parse about any Arabic textual datetime description into a Unix timestamp</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>

<body>

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output:</h2>
<?php
/**
 * Example of parse about any Arabic textual datetime description into a Unix timestamp
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
$time_start = microtime(true);

date_default_timezone_set('UTC');
$time = time();

echo date('l dS F Y', $time);
echo '<br /><br />';

require '../../Arabic.php';
$Arabic = new I18N_Arabic('StrToTime');

$str  = 'الخميس القادم';
$int  = $Arabic->strtotime($str, $time);
$date = date('l dS F Y', $int);
echo "$str - $int - $date<br /><br />";

$str  = 'الأحد الماضي';
$int  = $Arabic->strtotime($str, $time);
$date = date('l dS F Y', $int);
echo "$str - $int - $date<br /><br />";

$str  = 'بعد أسبوع و ثلاثة أيام';
$int  = $Arabic->strtotime($str, $time);
$date = date('l dS F Y', $int);
echo "$str - $int - $date<br /><br />";

$str  = 'منذ تسعة أيام';
$int  = $Arabic->strtotime($str, $time);
$date = date('l dS F Y', $int);
echo "$str - $int - $date<br /><br />";

$str  = 'قبل إسبوعين';
$int  = $Arabic->strtotime($str, $time);
$date = date('l dS F Y', $int);
echo "$str - $int - $date<br /><br />";

$str  = '2 آب 1975';
$int  = $Arabic->strtotime($str, $time);
$date = date('l dS F Y', $int);
echo "$str - $int - $date<br /><br />";

$str  = '1 رمضان 1429';
$int  = $Arabic->strtotime($str, $time);
$date = date('l dS F Y', $int);
echo "$str - $int - $date<br /><br />";
?>
</div><br />
<div class="Paragraph">
<h2>Example Code:</h2>
<?php
$code = <<< END
<?php
    date_default_timezone_set('UTC');
    \$time = time();

    echo date('l dS F Y', \$time);
    echo '<br /><br />';

    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('StrToTime');

    \$str  = 'الخميس القادم';
    \$int  = \$Arabic->strtotime(\$str, \$time);
    \$date = date('l dS F Y', \$int);
    echo "\$str - \$int - \$date<br /><br />";
    
    \$str  = 'الأحد الماضي';
    \$int  = \$Arabic->strtotime(\$str, \$time);
    \$date = date('l dS F Y', \$int);
    echo "\$str - \$int - \$date<br /><br />";
    
    \$str  = 'بعد أسبوع و ثلاثة أيام';
    \$int  = \$Arabic->strtotime(\$str, \$time);
    \$date = date('l dS F Y', \$int);
    echo "\$str - \$int - \$date<br /><br />";
    
    \$str  = 'منذ تسعة أيام';
    \$int  = \$Arabic->strtotime(\$str, \$time);
    \$date = date('l dS F Y', \$int);
    echo "\$str - \$int - \$date<br /><br />";
    
    \$str  = 'قبل إسبوعين';
    \$int  = \$Arabic->strtotime(\$str, \$time);
    \$date = date('l dS F Y', \$int);
    echo "\$str - \$int - \$date<br /><br />";
    
    \$str  = '2 آب 1975';
    \$int  = \$Arabic->strtotime(\$str, \$time);
    \$date = date('l dS F Y', \$int);
    echo "\$str - \$int - \$date<br /><br />";

    \$str  = '1 رمضان 1429';
    \$int  = \$Arabic->strtotime(\$str, \$time);
    \$date = date('l dS F Y', \$int);
    echo "\$str - \$int - \$date<br /><br />";
END;

highlight_string($code);

$time_end = microtime(true);
$time = $time_end - $time_start;

echo "<hr />Total execution time is $time seconds<br />\n";
echo 'Amount of memory allocated to this script is ' . memory_get_usage() . ' bytes';

$included_files = get_included_files();
echo '<h4>Names of included or required files:</h4><ul>';

foreach ($included_files as $filename) {
    echo "<li>$filename</li>";
}

echo '</ul>';
?>
<a href="../Docs/I18N_Arabic/_Arabic---StrToTime.php.html" target="_blank">Related Class Documentation</a>
</div>
</body>
</html>
