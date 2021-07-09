<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Spell numbers in the Arabic idiom</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>

<body>

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output 1: المعدود مذكر مرفوع</h2>
<?php
/**
 * Example of Spell numbers in the Arabic idiom
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
$Arabic = new I18N_Arabic('Numbers');

$Arabic->setFeminine(1);
$Arabic->setFormat(1);
           
$integer = 141592653589;

$text = $Arabic->int2str($integer);

echo "<center>$integer<br />$text</center>";
?>

</div><br />
<div class="Paragraph">
<h2>Example Code 1:</h2>
<?php
$code = <<< END
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Numbers');

    \$Arabic->setFeminine(1);
    \$Arabic->setFormat(1);

    \$integer = 141592653589;

    \$text = \$Arabic->int2str(\$integer);

    echo "<center>\$integer<br />\$text</center>";
END;

highlight_string($code);

?>
</div>
<br />
<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output 2: المعدود مؤنث منصوب أو مجرور</h2>
<?php
    $Arabic->setFeminine(2);
    $Arabic->setFormat(2);

    $integer = 141592653589;

    $text = $Arabic->int2str($integer);

    echo "<center>$integer<br />$text</center>";
?>

</div><br />
<div class="Paragraph">
<h2>Example Code 2:</h2>
<?php
$code = <<< END
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Numbers');

    \$Arabic->setFeminine(2);
    \$Arabic->setFormat(2);

    \$integer = 141592653589;
    
    \$text = \$Arabic->int2str(\$integer);
    
    echo "<center>\$integer<br />\$text</center>";
END;

highlight_string($code);

?>
</div><br />

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output 3: المعدود مؤنث منصوب أو مجرور وهو سالب بفاصلة عشرية</h2>
<?php
    $Arabic->setFeminine(2);
    $Arabic->setFormat(2);
    
    $integer = '-2749.317';
    
    $text = $Arabic->int2str($integer);
    
    echo "<p dir=ltr align=center>$integer<br />$text</p>";
?>

</div><br />
<div class="Paragraph">
<h2>Example Code 3:</h2>
<?php
$code = <<< END
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Numbers');
    
    \$Arabic->setFeminine(2);
    \$Arabic->setFormat(2);
    
    \$integer = '-2749.317';
    
    \$text = \$Arabic->int2str(\$integer);
    
    echo "<p dir=ltr align=center>\$integer<br />\$text</p>";
END;

highlight_string($code);

?>
</div><br />

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output 4: العملات العربية</h2>
<?php
    $Arabic->setFeminine(1);

    $number = 24.7;
    $text   = $Arabic->money2str($number, 'KWD', 'ar');
    
    echo "<p dir=ltr align=center>$number<br />$text</p>";
?>

</div><br />
<div class="Paragraph">
<h2>Example Code 4:</h2>
<?php
$code = <<< END
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Numbers');
    
    \$number = 24.7;
    \$text   = \$Arabic->money2str(\$number, 'KWD', 'ar');
    
    echo "<p dir=ltr align=center>\$number<br />\$text</p>";
END;

highlight_string($code);

?>
</div><br />

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output 5: الأرقام الهندية</h2>
<?php
    $text1 = '1975/8/2 9:43 صباحا';
    $text2 = $Arabic->int2indic($text1);
    
    echo "<p dir=ltr align=center>$text1<br />$text2</p>";
?>

</div><br />
<div class="Paragraph">
<h2>Example Code 5:</h2>
<?php
$code = <<< END
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Numbers');
    
    \$text1 = '1975/8/2 9:43 صباحا';
    \$text2 = \$Arabic->int2indic(\$text1);
    
    echo "<p dir=ltr align=center>\$text1<br />\$text2</p>";
END;

highlight_string($code);

?>
</div><br />

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output 6: ترتيب لمعدود مؤنث منصوب أو مجرور</h2>
<?php
    $Arabic->setFeminine(2);
    $Arabic->setFormat(2);
    $Arabic->setOrder(2);
    
    $integer = '17';
    
    $text = $Arabic->int2str($integer);
    
    echo "<p dir=ltr align=center>$integer<br />$text</p>";
?>

</div><br />
<div class="Paragraph">
<h2>Example Code 6:</h2>
<?php
$code = <<< END
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Numbers');
    
    \$Arabic->setFeminine(2);
    \$Arabic->setFormat(2);
    \$Arabic->setOrder(2);
    
    \$integer = '17';
    
    \$text = \$Arabic->int2str(\$integer);
    
    echo "<p dir=ltr align=center>\$integer<br />\$text</p>";
END;

highlight_string($code);

?>
</div><br />

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output 7: تحويل الرقم المكتوب إلى عدد صحيح من جديد</h2>
<?php
    $string  = 'مليار و مئتين و خمسة و ستين مليون و ثلاثمئة و ثمانية و خمسين ألف و تسعمئة و تسعة و سبعين';

    $integer = $Arabic->str2int($string);
    
    echo "<p dir=ltr align=center>$string<br />$integer</p>";
?>

</div><br />
<div class="Paragraph">
<h2>Example Code 7:</h2>
<?php
$code = <<< END
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Numbers');
    
    \$string  = 'مليار و مئتين و خمسة و ستين مليون و ثلاثمئة و ثمانية و خمسين ألف و تسعمئة و تسعة و سبعين';

    \$integer = \$Arabic->str2int(\$string);
    
    echo "<p dir=ltr align=center>\$string<br />\$integer</p>";
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
<a href="../Docs/I18N_Arabic/_Arabic---Numbers.php.html" target="_blank">Related Class Documentation</a>

</div>

</body>
</html>
