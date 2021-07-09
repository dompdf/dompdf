<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Arabic Stemmer</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>

<body>

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output:</h2>
<?php
/**
 * Example of Arabic Stemmer
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
$Arabic = new I18N_Arabic('Stemmer');

$examples = array();
$examples[] = 'سيعرفونها من خلال العمل بالحاسوبين المستعملين لديهما';
$examples[] = 'الخيليات البرية المهددة بالإنقراض';
$examples[] = 'تزايدت الحواسيب الشخصية بمساعدة التطبيقات الرئيسية';
$examples[] = 'سيتعذر هذا على عمليات نشر المساعدات للجائعين بالطريقة الجديدة';
$examples[] = 'ليس هذا بالحل المثالي انظر  كتبي وكتابك';
foreach ($examples as $str) {
    echo $str . ' <br />(';
    
    $words = preg_split('/\s+/', $str);
    $stems = array();

    foreach ($words as $word) {
        $stem = $Arabic->stem($word);
        if ($stem) {
            $stems[] = $stem; 
        }
    }
    
    echo implode('، ', $stems) . ')<br /><br />';
}
?>
</div><br />
<div class="Paragraph">
<h2>Example Code:</h2>
<?php
$code = <<< END
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Stemmer');
    
    \$examples = array();
    \$examples[] = 'سيعرفونها من خلال العمل بالحاسوبين المستعملين لديهما';
    \$examples[] = 'الخيليات البرية المهددة بالإنقراض';
    \$examples[] = 'تزايدت الحواسيب الشخصية بمساعدة التطبيقات الرئيسية';
    \$examples[] = 'سيتعذر هذا على عمليات نشر المساعدات للجائعين بالطريقة الجديدة';
    \$examples[] = 'ليس هذا بالحل المثالي انظر  كتبي وكتابك';
    foreach (\$examples as \$str) {
        echo \$str . ' <br />(';
        
        \$words = split(' ', \$str);
        \$stems = array();
        
        foreach (\$words as \$word) {
            \$stem = \$Arabic->stem(\$word);
            if (\$stem) {
                \$stems[] = \$stem; 
            }
        }
        
        echo implode('، ', \$stems) . ')<br /><br />';
    }
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
<a href="../Docs/I18N_Arabic/_Arabic---Stemmer.php.html" target="_blank">Related Class Documentation</a>
</div>
</body>
</html>
