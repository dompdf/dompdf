<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Arabic/Islamic Date and Calendar</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>

<body>

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output:</h2>
<?php
/**
 * Example of Arabic/Islamic Date and Calendar
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

date_default_timezone_set('GMT');
$time = time();

echo date('l dS F Y h:i:s A', $time);
echo '<br /><br />';

require '../../Arabic.php';
$Arabic = new I18N_Arabic('Date');

$correction = $Arabic->dateCorrection($time);
echo $Arabic->date('l dS F Y h:i:s A', $time, $correction);

$day = $Arabic->date('j', $time, $correction);
echo ' [<a href="Moon.php?day='.$day.'" target=_blank>القمر الليلة</a>]';
echo '<br /><br />';

$Arabic->setMode(8);
echo $Arabic->date('l dS F Y h:i:s A', $time, $correction);
echo '<br /><br />';

$Arabic->setMode(2);
echo $Arabic->date('l dS F Y h:i:s A', $time);
echo '<br /><br />';

$Arabic->setMode(3);
echo $Arabic->date('l dS F Y h:i:s A', $time);
echo '<br /><br />';

$Arabic->setMode(4);
echo $Arabic->date('l dS F Y h:i:s A', $time);
echo '<br /><br />';

$Arabic->setMode(5);
echo $Arabic->date('l dS F Y h:i:s A', $time);
echo '<br /><br />';

$Arabic->setMode(6);
echo $Arabic->date('l dS F Y h:i:s A', $time);
echo '<br /><br />';

$Arabic->setMode(7);
echo $Arabic->date('l dS F Y h:i:s A', $time);

?>
</div><br />
<div class="Paragraph">
<h2>Example Code:</h2>
<?php
$code = <<< END
<?php
    date_default_timezone_set('GMT');
    \$time = time();

    echo date('l dS F Y h:i:s A', \$time);
    echo '<br /><br />';

    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Date');

    \$correction = \$Arabic->dateCorrection (\$time);
    echo \$Arabic->date('l dS F Y h:i:s A', \$time, \$correction);

    \$day = \$Arabic->date('j', \$time, \$correction);
    echo ' [<a href="Moon.php?day='.\$day.'" target=_blank>القمر الليلة</a>]';
    echo '<br /><br />';

	\$Arabic->setMode(8);
	echo \$Arabic->date('l dS F Y h:i:s A', \$time, \$correction);
	echo '<br /><br />';

    \$Arabic->setMode(2);
    echo \$Arabic->date('l dS F Y h:i:s A', \$time);
    echo '<br /><br />';
    
    \$Arabic->setMode(3);
    echo \$Arabic->date('l dS F Y h:i:s A', \$time);
    echo '<br /><br />';

    \$Arabic->setMode(4);
    echo \$Arabic->date('l dS F Y h:i:s A', \$time);
    echo '<br /><br />';

    \$Arabic->setMode(5);
    echo \$Arabic->date('l dS F Y h:i:s A', \$time);
    echo '<br /><br />';

    \$Arabic->setMode(6);
    echo \$Arabic->date('l dS F Y h:i:s A', \$time);
    echo '<br /><br />';

    \$Arabic->setMode(7);
    echo \$Arabic->date('l dS F Y h:i:s A', \$time);
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
<a href="../Docs/I18N_Arabic/_Arabic---Date.php.html" target="_blank">Related Class Documentation</a>
</div>
</body>
</html>