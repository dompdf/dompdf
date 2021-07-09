<?php
/**
 * Example of PDF implementation for Arabic glyphs Class
 *
 * @category  I18N
 * @package   I18N_Arabic
 * @author    Khaled Al-Sham'aa <khaled@ar-php.org>
 * @copyright 2006-2016 Khaled Al-Sham'aa
 *
 * @license   LGPL <http://www.gnu.org/licenses/lgpl.txt>
 * @link      http://www.ar-php.org
 */

header('Content-type: application/pdf');

/*
 * Needed for fpdf library not for ArPHP library
 */
error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);

date_default_timezone_set('UTC');

define('FPDF_FONTPATH', 'PDF/font/');
require 'PDF/ufpdf.php';

$pdf = new UFPDF();
$pdf->Open();
$pdf->SetTitle('UFPDF is Cool.');
$pdf->SetAuthor('Khaled Al-Shamaa');

$pdf->AddFont('ae_AlHor', '', 'ae_AlHor.php');

$pdf->AddPage();

require '../../Arabic.php';
$Arabic = new I18N_Arabic('Glyphs');

$text = "\n".'العنوان هو "AJAX era!" باللغة الإنجليزية';
$text .= "\n\n".'في حقيقة الأمر، لقد سبق لشركة Microsoft ذاتها التعامل مع تقنية Ajax هذه منذ أواخر تسعينات القرن الماضي, لا بل أنها لا تزال تستخدم تلك التقنية في تعزيز مقدرة برنامجها الشهير Outlook للبريد الإلكتروني. وعلى الرغم من كون تقنية Ajax تقنية قديمة العهد نسبيا، إلا أنها لم تلق (حين ظهورها أول مرة) الكثير من الاهتمام، إلا أن الفضل يعود إلى شركة Google في نفض الغبار عنها ولإعادة إكتشافها من جديد، وذلك من خلال طائفة من تطبيقاتها الجديدة والتي يقع على رأسها كل من غوغل Maps إضافة إلى مخدم البريد الإلكتروني Gmail واللذين شكلا فعلا علامة فارقة في عالم الويب وإشارة واضحة إلى ما ستؤول إليه تطبيقات الويب في المستقبل القريب. فهل أعجبتك الفكرة؟ سوريا، حلب في 13 أيار 2007 مـ';
$text .= "\n\nتصحيح طريقة عرض الأرقام المتصلة بأحرف إنجليزية كـ 3M و W3C على سبيل المثال";
$text .= "\n\nخالد الشمعة khaled@ar-php.org والموقع هو http://www.ar-php.org";

// Known bugs:
//$text = 'The title is "عصر الأجاكس!" in Arabic';
//$text = ' مؤسسة (World Wide Web Consortium) W3C';
//$text = ' ماذا لو كانت الجملة تنتهي بكلمة إنجليزية مثل Test?';

$font_size = 16;
$chars_in_line = $Arabic->a4MaxChars($font_size);
$total_lines = $Arabic->a4Lines($text, $font_size);

$text = $Arabic->utf8Glyphs($text, $chars_in_line);

$pdf->SetFont('ae_AlHor', '', $font_size);
$pdf->MultiCell(0, $total_lines, $text, 0, 'R', 0);

$pdf->Close();
$pdf->Output();
?>
