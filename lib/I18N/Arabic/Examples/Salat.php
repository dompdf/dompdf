<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Muslim Prayer Times</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>

<body>

<div class="Paragraph">
<h2 dir="ltr">Example Output:</h2>
<?php
/**
 * Example of Muslim Prayer Times
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

require '../../Arabic.php';
$Arabic = new I18N_Arabic('Salat');

// Latitude, Longitude, Zone, and Elevation
$Arabic->setLocation(33.52, 36.31, 3, 691);

// Month, Day, and Year
$Arabic->setDate(date('n'), date('j'), date('Y'));

echo "<b>Damascus, Syria</b> ".date('l F j, Y')."<br /><br />";

// Salat calculation configuration: Egyptian General Authority of Survey
$Arabic->setConf('Shafi', -0.833333, -17.5, -19.5, 'Sunni');

$times = $Arabic->getPrayTime();

echo "<b>Imsak:</b> {$times[8]}<br />";
echo "<b>Fajr:</b> {$times[0]}<br />";
echo "<b>Sunrise:</b> {$times[1]}<br />";
echo "<b>Dhuhr:</b> {$times[2]}<br />";
echo "<b>Asr:</b> {$times[3]}<br />";
echo "<b>Sunset:</b> {$times[6]}<br />";
echo "<b>Maghrib:</b> {$times[4]}<br />";
echo "<b>Isha:</b> {$times[5]}<br />";
echo "<b>Midnight:</b> {$times[7]}<br /><br />";

echo '<b>Imsak:</b> '   .date('l j F Y g:i a', $times[9][8]).'<br />';
echo '<b>Fajr:</b> '    .date('l j F Y g:i a', $times[9][0]).'<br />';
echo '<b>Sunrise:</b> ' .date('l j F Y g:i a', $times[9][1]).'<br />';
echo '<b>Dhuhr:</b> '   .date('l j F Y g:i a', $times[9][2]).'<br />';
echo '<b>Asr:</b> '     .date('l j F Y g:i a', $times[9][3]).'<br />';
echo '<b>Sunset:</b> '  .date('l j F Y g:i a', $times[9][6]).'<br />';
echo '<b>Maghrib:</b> ' .date('l j F Y g:i a', $times[9][4]).'<br />';
echo '<b>Isha:</b> '    .date('l j F Y g:i a', $times[9][5]).'<br />';
echo '<b>Midnight:</b> '.date('l j F Y g:i a', $times[9][7]).'<br /><br />';

$direction = $Arabic->getQibla();
echo "<b>Qibla Direction (from the north direction):</b> $direction ";
echo "(<a href=\"./Qibla.php?d=$direction\" target=_blank>click here</a>)<br /><br/>";
?>
</div><br />
<div class="Paragraph">
<h2>Example Code:</h2>
<?php
$code = <<< END
<?php
    date_default_timezone_set('UTC');
    
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Salat');

    // Latitude, Longitude, Zone, and Elevation
    \$Arabic->setLocation(33.52, 36.31, 3, 691);
    
    // Month, Day, and Year
    \$Arabic->setDate(date('n'), date('j'), date('Y'));

    echo "<b>Damascus, Syria</b> ".date('l F j, Y')."<br /><br />";

    // Salat calculation configuration: Egyptian General Authority of Survey
    \$Arabic->setConf('Shafi', -0.833333,  -17.5, -19.5, 'Sunni');
    \$times = \$Arabic->getPrayTime();
    
    echo "<b>Imsak:</b> {\$times[8]}<br />";
    echo "<b>Fajr:</b> {\$times[0]}<br />";
    echo "<b>Sunrise:</b> {\$times[1]}<br />";
    echo "<b>Dhuhr:</b> {\$times[2]}<br />";
    echo "<b>Asr:</b> {\$times[3]}<br />";
    echo "<b>Sunset:</b> {\$times[6]}<br />";
    echo "<b>Maghrib:</b> {\$times[4]}<br />";
    echo "<b>Isha:</b> {\$times[5]}<br />";
    echo "<b>Midnight:</b> {\$times[7]}<br /><br />";

    echo '<b>Imsak:</b> '   .date('l j F Y g:i a', \$times[9][8]).'<br />';
    echo '<b>Fajr:</b> '    .date('l j F Y g:i a', \$times[9][0]).'<br />';
    echo '<b>Sunrise:</b> ' .date('l j F Y g:i a', \$times[9][1]).'<br />';
    echo '<b>Dhuhr:</b> '   .date('l j F Y g:i a', \$times[9][2]).'<br />';
    echo '<b>Asr:</b> '     .date('l j F Y g:i a', \$times[9][3]).'<br />';
    echo '<b>Sunset:</b> '  .date('l j F Y g:i a', \$times[9][6]).'<br />';
    echo '<b>Maghrib:</b> ' .date('l j F Y g:i a', \$times[9][4]).'<br />';
    echo '<b>Isha:</b> '    .date('l j F Y g:i a', \$times[9][5]).'<br />';
    echo '<b>Midnight:</b> '.date('l j F Y g:i a', \$times[9][7]).'<br /><br />';
    
    \$direction = \$Arabic->getQibla();
    echo "<b>Qibla Direction (from the north direction):</b> \$direction<br />";
    echo "(<a href=\"./Qibla.php?d=\$direction\" target=_blank>click here</a>)<br /><br/>";
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
<a href="../Docs/I18N_Arabic/_Arabic---Salat.php.html" target="_blank">Related Class Documentation</a>
</div>
</body>
</html>
