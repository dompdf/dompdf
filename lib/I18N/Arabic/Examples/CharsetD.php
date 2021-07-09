<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Detect Arabic String Character Set</title>
<meta http-equiv="Content-Type" content="text/html;charset=windows-1256" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>

<body>

<div class="Paragraph">
<h2>Example Output:</h2>
<?php
/**
 * Example of Detect Arabic String Character Set
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

$text = 'بسم الله الرحمن الرحيم';

require '../../Arabic.php';
$Arabic = new I18N_Arabic('CharsetD');

$charset = $Arabic->getCharset($text);

echo "$text ($charset) <br/>";

print_r($Arabic->guess($text));
?>

</div><br />
<div class="Paragraph">
<h2>Example Code:</h2>
<?php
$code = <<< END
<?php
    \$text = 'بسم الله الرحمن الرحيم';

    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('CharsetD');
    
    \$charset = \$Arabic->getCharset(\$text);
    
    echo "\$text (\$charset) <br/>";
    
    print_r(\$Arabic->guess(\$text));
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
<a href="../Docs/I18N_Arabic/_Arabic---CharsetD.php.html" target="_blank">Related Class Documentation</a>
</div>
</body>
</html>
