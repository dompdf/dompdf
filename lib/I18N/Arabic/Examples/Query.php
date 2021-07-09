<?php
/**
 * Example of Arabic Query Class
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
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<title>Arabic Query Class</title>
<meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
<link rel="stylesheet" type="text/css" href="style.css" media="all" />
</head>

<body>

<div class="Paragraph" dir="rtl">
<h2 dir="ltr">Example Output:</h2>
    <font face="Tahoma" size="2">
    <table border="0" width="100%" dir="ltr">
      </tr>
        <td align="center">
          <font face="Tahoma" size="2">Example database table contains 574 headline from
          <a href="http://www.aljazeera.net" target=_blank>Aljazeera.net</a>
          news channel website presented at 2003.</font>
        </td>
      </tr>
    </table><hr />
    <form action="Query.php" method="GET" name="search">
        إبحث عن (Search for): <input type="text" name="keyword" value="<?php echo $_GET['keyword']; ?>"> 
        <input type="submit" value="بحث (Go)" name="submit" />
         (مثال: فلسطينيون)<br />
        <blockquote><blockquote><blockquote>
            <input type="radio" name="mode" value="0" checked /> أي من الكلمات (Any word)
            <input type="radio" name="mode" value="1" /> كل الكلمات (All words)
        </blockquote></blockquote></blockquote>
    </form>

<?php if (isset($_GET['keyword'])) { ?>
    <hr />
    نتائج البحث عن (Search for) <b><?php echo $keyword; ?></b>:<br />
    <table cellpadding="5" cellspacing="2" align="center" width="80%">
        <tr>
            <td bgcolor="#004488" align="center">
                <font color="#ffffff" size="2">
                    <b>الخبر كما ورد في موقع الجزيرة<br />
                    Headline at Aljazeera.net</b>
                </font>
            </td>
        </tr>
    <?php
    include '../../Arabic.php';
    $Arabic = new I18N_Arabic('Query');
    echo $Arabic->allForms('فلسطينيون');
        
    $dbuser = 'root';
    $dbpwd = '';
    $dbname = 'test';
    
    try {
        $dbh = new PDO('mysql:host=localhost;dbname='.$dbname, $dbuser, $dbpwd);

        // Set the error reporting attribute
        $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
        $dbh->exec("SET NAMES 'utf8'");
    
        if ($_GET['keyword'] != '') {
            $keyword = @$_GET['keyword'];
            $keyword = str_replace('\"', '"', $keyword);
    
            $Arabic->setStrFields('headline');
            $Arabic->setMode($_GET['mode']);
    
            $strCondition = $Arabic->getWhereCondition($keyword);
            $strOrderBy = $Arabic->getOrderBy($keyword);
        } else {
            $strCondition = '1';
        }
    
        $StrSQL = "SELECT `headline` FROM `aljazeera` WHERE $strCondition ORDER BY $strOrderBy";
    
        $i = 0;
        foreach ($dbh->query($StrSQL) as $row) {
            $headline = $row['headline'];
            $i++;
            if ($i % 2 == 0) {
                $bg = "#f0f0f0";
            } else {
                $bg = "#ffffff";
            }
            echo"<tr bgcolor=\"$bg\"><td><font size=\"2\">$headline</font></td></tr>";
        }
    
        // Close the databse connection
        $dbh = null;
    
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
    ?>
    </table>
    <?php
}
?>

    <hr />
    صيغة الإستعلام <span dir="ltr">(SQL Query Statement)</span>
    <br /><textarea dir="ltr" align="left" cols="80" rows="4"><?php echo $StrSQL; ?></textarea>

</div><br />
<div class="Paragraph">
<h2>Example Code:</h2>
<?php
$code = <<< END
<?php
    require '../../Arabic.php';
    \$Arabic = new I18N_Arabic('Query');
    echo \$Arabic->allForms('فلسطينيون');
        
    \$dbuser = 'root';
    \$dbpwd = '';
    \$dbname = 'test';
    
    try {
        \$dbh = new PDO('mysql:host=localhost;dbname='.\$dbname, \$dbuser, \$dbpwd);

        // Set the error reporting attribute
        \$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        \$dbh->exec("SET NAMES 'utf8'");
    
        if (\$_GET['keyword'] != '') {
            \$keyword = @\$_GET['keyword'];
            \$keyword = str_replace('\"', '"', \$keyword);
    
            \$Arabic->setStrFields('headline');
            \$Arabic->setMode(\$_GET['mode']);
    
            \$strCondition = \$Arabic->getWhereCondition(\$keyword);
            \$strOrderBy = \$Arabic->getOrderBy(\$keyword);
        } else {
            \$strCondition = '1';
        }
    
        \$StrSQL = "SELECT `headline` FROM `aljazeera` WHERE \$strCondition ORDER BY \$strOrderBy";

        \$i = 0;
        foreach (\$dbh->query(\$StrSQL) as \$row) {
            \$headline = \$row['headline'];
            \$i++;
            if (\$i % 2 == 0) {
                \$bg = "#f0f0f0";
            } else {
                \$bg = "#ffffff";
            }
            echo"<tr bgcolor=\"\$bg\"><td><font size=\"2\">\$headline</font></td></tr>";
        }

        // Close the databse connection
        \$dbh = null;

    } catch (PDOException \$e) {
        echo \$e->getMessage();
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
<a href="../Docs/I18N_Arabic/_Arabic---Query.php.html" target="_blank">Related Class Documentation</a>

</div>
</body>
</html>
