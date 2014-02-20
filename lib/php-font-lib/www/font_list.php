<?php
/**
 * @package php-font-lib
 * @link    https://github.com/PhenX/php-font-lib
 * @author  Fabien Ménager <fabien.menager@gmail.com>
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 */
?><!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8" />
  <link rel="stylesheet" href="css/style.css" />
</head>
<body>
<?php 

$fonts = glob("../fonts/*.{ttf,TTF,otf,OTF,ttc,TTC,eot,EOT,woff,WOFF}", GLOB_BRACE);
sort($fonts);

echo "<ul>";
foreach($fonts as $font) {
  echo "<li><a href=\"font_info.php?fontfile=$font\" target=\"font-info\">".basename($font)."</a></li>";
}
echo "</ul>";

?>
</body>
</html>