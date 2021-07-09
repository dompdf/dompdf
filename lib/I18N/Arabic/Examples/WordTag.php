<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Tagging Arabic Text</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />

<style type="text/css">
.noun { background-color:#eeee80; }
</style>

</head>

<body>

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output:</h2>
<?php
/**
 * Example of Tagging Arabic Text
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
$Arabic = new I18N_Arabic('WordTag');

$str = 'وحسب إحصائية لوزارة الدفاع الأميركية ما زال نحو 375 معتقلا يقبعون في
غوانتانامو في إطار ما يسمى " الحرب على الإرهاب "، منهم كثيرون محتجزون منذ
أكثر من خمس سنوات ومن بينهم مصور قناة الجزيرة سامي الحاج الذي لم توجه له أي
تهمة رسمية ولم يحظ بأي محاكمة حتى الآن.';

$highlightStr = $Arabic->highlightText($str, 'noun');

echo '<div dir="rtl" align="justify">' . $str . '<hr />' . 
     $highlightStr . '<hr /></div>';

$taggedText = $Arabic->tagText($str);

echo '<div dir="ltr" align="justify">';

foreach ($taggedText as $wordTag) {
    list($word, $tag) = $wordTag;

    if ($tag == 1) {
        echo"<font color=blue>$word is Noun</font>, ";
    }

    if ($tag == 0) {
        echo"<font color=red>$word is not Noun</font>, ";
    }
}
echo '</div>';
?>
</div><br />
<div class="Paragraph">
<h2>Example Code:</h2>
<?php
$code = <<< END
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('WordTag');
    
    \$str = 'وحسب إحصائية لوزارة الدفاع الأميركية ما زال نحو 375 معتقلا يقبعون في
    غوانتانامو في إطار ما يسمى " الحرب على الإرهاب "، منهم كثيرون محتجزون منذ
    أكثر من خمس سنوات ومن بينهم مصور قناة الجزيرة سامي الحاج الذي لم توجه له أي
    تهمة رسمية ولم يحظ بأي محاكمة حتى الآن.';

    \$highlightStr = \$Arabic->highlightText(\$str, 'noun');

    echo '<div dir="rtl" align="justify">' . \$str . '<hr />' . 
         \$highlightStr . '<hr /></div>';
    
    \$taggedText = \$Arabic->tagText(\$str);
    
    echo '<div dir="ltr" align="justify">';

    foreach (\$taggedText as \$wordTag) {
        list(\$word, \$tag) = \$wordTag;
    
        if (\$tag == 1) {
            echo"<font color=blue>\$word is Noun</font>, ";
        }
    
        if (\$tag == 0) {
            echo"<font color=red>\$word is not Noun</font>, ";
        }
    }
    echo '</div>';
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
<a href="../Docs/I18N_Arabic/_Arabic---WordTag.php.html" target="_blank">Related Class Documentation</a>
</div>
</body>
</html>
