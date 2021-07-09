<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Arabic Soundex Class</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>

<body>

<div class="Paragraph">
<h2>Example Output:</h2>
<?php
/**
 * Example of Arabic Soundex Class
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
$Arabic = new I18N_Arabic('Soundex');

$Clinton = array('كلينتون', 'كلينتن', 'كلينطون', 'كلنتن', 'كلنتون', 
                 'كلاينتون');

echo <<<END
<table border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
    <td colspan="3">Listed below are 6 different spelling for the name
    <i><a href="http://en.wikipedia.org/wiki/Bill_Clinton" target=_blank>Clinton</a></i>
      found in collection of news articles in addition to original English spelling.</td>
</tr>
<tr>
    <td bgcolor=#000000 width=33%><b><font color=#ffffff>Function</font></b></td>
    <td bgcolor=#000000 width=33%><b><font color=#ffffff>Input</font></b></td>
    <td bgcolor=#000000 width=33%><b><font color=#ffffff>Output</font></b></td>
</tr>
END;
echo '<tr>
        <td bgcolor=#f5f5f5>PHP soundex function</td>
        <td bgcolor=#f5f5f5>Clinton</td>
        <td bgcolor=#f5f5f5>' . soundex('Clinton') . '</td>
      </tr>';

foreach ($Clinton as $name) {
    echo '<tr>
            <td bgcolor=#f5f5f5>ArSoundex Method</td>
            <td bgcolor=#f5f5f5>' . $name . '</td>
            <td bgcolor=#f5f5f5>' . $Arabic->soundex($name) . '</td>
          </tr>';
}

echo '<tr>
        <td bgcolor=#f5f5c5>ArSoundex Method</td>
        <td bgcolor=#f5f5c5>كلينزمان</td>
        <td bgcolor=#f5f5c5>' . $Arabic->soundex('كلينزمان') . '</td>
      </tr>';

echo <<<END
<tr>
    <td colspan=3>&nbsp;</td>
</tr>
<tr>
    <td colspan=3>Listed below are 6 different spelling for the name
    <i><a href="http://en.wikipedia.org/wiki/Milosevic" target=_blank>Milosevic</a></i>
     found in collection of news articles in addition to original English spelling.</td>
</tr>
<tr>
    <td bgcolor=#000000><b><font color=#ffffff>Function</font></b></td>
    <td bgcolor=#000000><b><font color=#ffffff>Input</font></b></td>
    <td bgcolor=#000000><b><font color=#ffffff>Output</font></b></td>
</tr>
<tr>
END;
    
$Milosevic = array('ميلوسيفيتش', 'ميلوسفيتش', 'ميلوزفيتش', 'ميلوزيفيتش',
                   'ميلسيفيتش', 'ميلوسيفتش');

echo '<tr>
        <td bgcolor=#f5f5f5>PHP soundex function</td>
        <td bgcolor=#f5f5f5>Milosevic</td>
        <td bgcolor=#f5f5f5>' . soundex('Milosevic') . '</td>
      </tr>';
                   
foreach ($Milosevic as $name) {
    echo '<tr>
            <td bgcolor=#f5f5f5>ArSoundex Method</td>
            <td bgcolor=#f5f5f5>' . $name . '</td>
            <td bgcolor=#f5f5f5>' . $Arabic->soundex($name) . '</td>
          </tr>';
}

echo '<tr>
        <td bgcolor=#f5f5c5>ArSoundex Method</td>
        <td bgcolor=#f5f5c5>ميلينيوم</td>
        <td bgcolor=#f5f5c5>' . $Arabic->soundex('ميلينيوم') . '</td>
      </tr></table>';
?>
</div><br />
<div class="Paragraph">
<h2>Example Code:</h2>
<?php
$code = <<< ENDALL
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Soundex');
    
    \$Clinton = array('كلينتون', 'كلينتن', 'كلينطون', 'كلنتن', 'كلنتون', 'كلاينتون');

    echo <<<END
<table border="0" cellpadding="5" cellspacing="2" align="center">
<tr>
    <td colspan="3">Listed below are 6 different spelling for the name
    <i><a href="http://en.wikipedia.org/wiki/Bill_Clinton" target=_blank>Clinton</a></i>
      found in collection of news articles in addition to original English spelling.</td>
</tr>
<tr>
    <td bgcolor=#000000 width=33%><b><font color=#ffffff>Function</font></b></td>
    <td bgcolor=#000000 width=33%><b><font color=#ffffff>Input</font></b></td>
    <td bgcolor=#000000 width=33%><b><font color=#ffffff>Output</font></b></td>
</tr>
END;
echo '<tr>
        <td bgcolor=#f5f5f5>PHP soundex function</td>
        <td bgcolor=#f5f5f5>Clinton</td>
        <td bgcolor=#f5f5f5>' . soundex('Clinton') . '</td>
      </tr>';

foreach (\$Clinton as \$name) {
    echo '<tr>
            <td bgcolor=#f5f5f5>ArSoundex Method</td>
            <td bgcolor=#f5f5f5>' . \$name . '</td>
            <td bgcolor=#f5f5f5>' . \$Arabic->soundex(\$name) . '</td>
          </tr>';
}

echo '<tr>
        <td bgcolor=#f5f5c5>ArSoundex Method</td>
        <td bgcolor=#f5f5c5>كلينزمان</td>
        <td bgcolor=#f5f5c5>' . \$Arabic->soundex('كلينزمان') . '</td>
      </tr>';

echo <<<END
<tr>
    <td colspan=3>&nbsp;</td>
</tr>
<tr>
    <td colspan=3>Listed below are 6 different spelling for the name
    <i><a href="http://en.wikipedia.org/wiki/Milosevic" target=_blank>Milosevic</a></i>
     found in collection of news articles in addition to original English spelling.</td>
</tr>
<tr>
    <td bgcolor=#000000><b><font color=#ffffff>Function</font></b></td>
    <td bgcolor=#000000><b><font color=#ffffff>Input</font></b></td>
    <td bgcolor=#000000><b><font color=#ffffff>Output</font></b></td>
</tr>
<tr>
END;
    
    \$Milosevic = array('ميلوسيفيتش', 'ميلوسفيتش', 'ميلوزفيتش', 'ميلوزيفيتش', 'ميلسيفيتش', 'ميلوسيفتش');

    echo '<tr>
            <td bgcolor=#f5f5f5>PHP soundex function</td>
            <td bgcolor=#f5f5f5>Milosevic</td>
            <td bgcolor=#f5f5f5>' . soundex('Milosevic') . '</td>
          </tr>';
                       
    foreach (\$Milosevic as \$name) {
        echo '<tr>
                <td bgcolor=#f5f5f5>ArSoundex Method</td>
                <td bgcolor=#f5f5f5>' . \$name . '</td>
                <td bgcolor=#f5f5f5>' . \$Arabic->soundex(\$name) . '</td>
              </tr>';
    }

    echo '<tr>
            <td bgcolor=#f5f5c5>ArSoundex Method</td>
            <td bgcolor=#f5f5c5>ميلينيوم</td>
            <td bgcolor=#f5f5c5>' . \$Arabic->soundex('ميلينيوم') . '</td>
          </tr></table>';
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
<a href="../Docs/I18N_Arabic/_Arabic---Soundex.php.html" target="_blank">Related Class Documentation</a>
</div>
</center>
</body>
</html>
