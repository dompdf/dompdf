<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Arabic Text ArStandard</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>

<body>

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output:</h2>
<?php
/**
 * Example of Arabic Text ArStandard
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

require '../../Arabic.php';
$Arabic = new I18N_Arabic('Standard');

$content = <<<END
هذا نص عربي ، و فيه علامات ترقيم بحاجة إلى ضبط و معايرة !و كذلك نصوص( بين 
أقواس )أو حتى مؤطرة"بإشارات إقتباس "أو- علامات إعتراض -الخ......
<br>
لذا ستكون هذه المكتبة أداة و وسيلة لمعالجة مثل هكذا حالات، بما فيها الواحدات 1 
Kg أو مثلا MB 16 وسواها حتى النسب المؤية مثل 20% أو %50 وهكذا ...
END;

    $str = $Arabic->standard($content);

    echo '<b>Origenal:</b>';
    echo '<p dir="rtl" align="justify">';
    echo $content . '</p>';
    
    echo '<b>Standard:</b>';
    echo '<p dir="rtl" align="justify">';
    echo $str . '</p>';
?>

</div><br />
<div class="Paragraph">
<h2>Example Code:</h2>
<?php
$code = <<< ENDALL
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Standard');
    
    \$content = <<<END
هذا نص عربي ، و فيه علامات ترقيم بحاجة إلى ضبط و معايرة !و كذلك نصوص( بين 
أقواس )أو حتى مؤطرة"بإشارات إقتباس "أو- علامات إعتراض -الخ......
<br>
لذا ستكون هذه المكتبة أداة و وسيلة لمعالجة مثل هكذا حالات، بما فيها الواحدات 1 
Kg أو مثلا MB 16 وسواها حتى النسب المؤية مثل 20% أو %50 وهكذا ...
END;

    \$str = \$Arabic->standard(\$content);
    
    echo '<b>Origenal:</b>';
    echo '<p dir="rtl" align="justify">';
    echo \$content . '</p>';
    
    echo '<b>Standard:</b>';
    echo '<p dir="rtl" align="justify">';
    echo \$str . '</p>';
ENDALL;

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
<a href="../Docs/I18N_Arabic/_Arabic---Standard.php.html" target="_blank">Related Class Documentation</a>
</div>
</body>
</html>
