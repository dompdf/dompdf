<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Arabic-English Transliteration</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>

<body>
<div class="Paragraph">
<h2>Example Output:</h2>
<?php
/**
 * Example of Arabic-English Transliteration
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
$Arabic = new I18N_Arabic('Transliteration');

$ar_terms = array('خالِد الشَمعَة', 'جُبران خَليل جُبران', 'كاظِم الساهِر',
    'ماجِدَة الرُومِي، نِزار قَبَّانِي', 'سُوق الحَمِيدِيَّة؟', 'مَغارَة
    جَعِيتَا', 'غُوطَة دِمَشق', 'حَلَب الشَهبَاء', 'جَزيرَة أَرواد', 'بِلاد
    الرافِدَين', 'أهرامات الجِيزَة', 'دِرْع', 'عِيد', 'عُود', 'رِدْء', 
    'إِيدَاء', 'هِبَة الله', 'قاضٍ');

echo <<< END
<center>
  <table border="0" cellspacing="2" cellpadding="5" width="500">
    <tr>
      <td bgcolor="#27509D" align="center" width="150">
        <b>
          <font color="#ffffff" face="Tahoma">
            English<br />(auto generated)
          </font>
        </b>
      </td>
      <td bgcolor="#27509D" align="center" width="150">
        <b>
          <font color="#ffffff">
            Arabic<br />(sample input)
          </font>
        </b>
      </td>
    </tr>
END;

foreach ($ar_terms as $term) {
    echo '<tr><td bgcolor="#f5f5f5" align="left"><font face="Tahoma">';
    echo $Arabic->ar2en($term);
    echo '</font></td>';
    echo '<td bgcolor="#f5f5f5" align="right">'.$term.'</td></tr>';
}

echo '<tr><td bgcolor="#d0d0f5" align="left"><font face="Tahoma">';
echo $Arabic->enNum('0123,456.789');
echo '</font></td>';
echo '<td bgcolor="#d0d0f5" align="right">0123,456.789</td></tr>';

echo '</table></center>';
?>
</div><br />
<div class="Paragraph">
<h2>Example Code:</h2>
<?php
$code = <<< ENDALL
<?php
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Transliteration');

    \$ar_terms = array('خالِد الشَمعَة', 'جُبران خَليل جُبران', 'كاظِم الساهِر',
        'ماجِدَة الرُومِي، نِزار قَبَّانِي', 'سُوق الحَمِيدِيَّة؟', 'مَغارَة
        جَعِيتَا', 'غُوطَة دِمَشق', 'حَلَب الشَهبَاء', 'جَزيرَة أَرواد', 'بِلاد
        الرافِدَين', 'أهرامات الجِيزَة', 'دِرْع', 'عِيد', 'عُود', 'رِدْء', 
        'إِيدَاء', 'هِبَة الله');
    echo <<< END
<center>
  <table border="0" cellspacing="2" cellpadding="5" width="500">
    <tr>
      <td bgcolor="#27509D" align="center" width="150">
        <b>
          <font color="#ffffff" face="Tahoma">
            English<br />(auto generated)
          </font>
        </b>
      </td>
      <td bgcolor="#27509D" align="center" width="150">
        <b>
          <font color="#ffffff">
            Arabic<br />(sample input)
          </font>
        </b>
      </td>
    </tr>
END;

    foreach (\$ar_terms as \$term) {
        echo '<tr><td bgcolor="#f5f5f5" align="left"><font face="Tahoma">';
        echo \$Arabic->ar2en(\$term);
        echo '</font></td>';
        echo '<td bgcolor="#f5f5f5" align="right">'.\$term.'</td></tr>';
    }

    echo '<tr><td bgcolor="#d0d0f5" align="left"><font face="Tahoma">';
    echo \$Arabic->enNum('0123,456.789');
    echo '</font></td>';
    echo '<td bgcolor="#d0d0f5" align="right">0123,456.789</td></tr>';

    echo '</table></center>';
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
<a href="../Docs/I18N_Arabic/_Arabic---Transliteration.php.html" target="_blank">Related Class Documentation</a>
</div>
</body>
</html>
