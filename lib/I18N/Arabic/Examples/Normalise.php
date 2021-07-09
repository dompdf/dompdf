<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Arabic Normalise Examples</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>

<body>
<div class="Paragraph">
<h2 dir="ltr">Arabic Normalise Examples Output:</h2>

<?php
/**
 * Example of Arabic Normalise Examples
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
$Arabic = new I18N_Arabic('Normalise');

echo <<<END
<p>قال الشاعر حافظ إبراهيم على لسان اللغة العربية</p>
<table border="0" cellpadding="5" cellspacing="2" dir="rtl">
END;

$lines[] = 'وَسِعْتُ كِتابَ اللَّهِ لفظـــاً وَحِكمَــــةً **** وَما ضِقْتُ عن آيٍ به وَعِظــــاتِ';
$lines[] = 'فَكيفَ أَضيقُ اليومَ عن وَصْفِ آلــةٍ **** وَتَنسيـــقُ أسمــاءٍ لِمُخْتَرَعــــاتِ';

foreach ($lines as $line) {
    echo '<tr><th style="background-color: #E5E5E5">Function</th>
          <th style="background-color: #E5E5E5">Text</th></tr>';

    echo "<tr bgcolor=#F0F8FF><th>Original</th><td>$line</td></tr>";
    
    $n1 = $Arabic->unshape($line);
    echo "<tr bgcolor=#F0F8FF><th>Unshape</th><td>$n1</td></tr>";
  
    $n2 = $Arabic->utf8Strrev($n1);
    echo "<tr bgcolor=#F0F8FF><th>UTF8 Reverse</th><td>$n2</td></tr>";
  
    $n3 = $Arabic->stripTashkeel($n1);
    echo "<tr bgcolor=#F0F8FF><th>Strip Tashkeel</th><td>$n3</td></tr>";
  
    $n4 = $Arabic->stripTatweel($n3);
    echo "<tr bgcolor=#F0F8FF><th>Strip Tatweel</th><td>$n4</td></tr>";
  
    $n5 = $Arabic->normaliseHamza($n4);
    echo "<tr bgcolor=#F0F8FF><th>Normalise Hamza</th><td>$n5</td></tr>";
  
    $n6 = $Arabic->normaliseLamaleph($n5);
    echo "<tr bgcolor=#F0F8FF><th>Normalise Lam Alef</th><td>$n6</td></tr>";
}

echo '</table>';
?>

</div><br />

<div class="Paragraph">
<h2>Arabic Normalise Examples Code:</h2>
<?php
$code = <<< ENDALL
<?php
require '../../Arabic.php';
\$Arabic = new I18N_Arabic('Normalise');

echo <<<END
<p>قال الشاعر حافظ إبراهيم على لسان اللغة العربية</p>
<table border="0" cellpadding="5" cellspacing="2" dir="rtl">
END;

\$lines[] = 'وَسِعْتُ كِتابَ اللَّهِ لفظـــاً وَحِكمَــــةً **** وَما ضِقْتُ عن آيٍ به وَعِظــــاتِ';
\$lines[] = 'فَكيفَ أَضيقُ اليومَ عن وَصْفِ آلــةٍ **** وَتَنسيـــقُ أسمــاءٍ لِمُخْتَرَعــــاتِ';

foreach (\$lines as \$line) {
    echo '<tr><th style="background-color: #E5E5E5">Function</th>
          <th style="background-color: #E5E5E5">Text</th></tr>';

    echo "<tr bgcolor=#F0F8FF><th>Original</th><td>\$line</td></tr>";
    
    \$n1 = \$Arabic->unshape(\$line);
    echo "<tr bgcolor=#F0F8FF><th>Unshape</th><td>\$n1</td></tr>";
  
    \$n2 = \$Arabic->utf8Strrev(\$n1);
    echo "<tr bgcolor=#F0F8FF><th>UTF8 Reverse</th><td>\$n2</td></tr>";
  
    \$n3 = \$Arabic->stripTashkeel(\$n1);
    echo "<tr bgcolor=#F0F8FF><th>Strip Tashkeel</th><td>\$n3</td></tr>";
  
    \$n4 = \$Arabic->stripTatweel(\$n3);
    echo "<tr bgcolor=#F0F8FF><th>Strip Tatweel</th><td>\$n4</td></tr>";
  
    \$n5 = \$Arabic->normaliseHamza(\$n4);
    echo "<tr bgcolor=#F0F8FF><th>Normalise Hamza</th><td>\$n5</td></tr>";
  
    \$n6 = \$Arabic->normaliseLamaleph(\$n5);
    echo "<tr bgcolor=#F0F8FF><th>Normalise Lam Alef</th><td>\$n6</td></tr>";
}

echo '</table>';
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
<a href="../Docs/I18N_Arabic/_Arabic---Normalise.php.html" target="_blank">Related Class Documentation</a>

</div>
</body>
</html>
