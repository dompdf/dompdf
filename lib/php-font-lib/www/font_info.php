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
  <title>Font information</title>
  <link rel="stylesheet" href="css/style.css" />
  <link rel="stylesheet" href="css/blitzer/jquery-ui-1.8.14.custom.css" />
  <script type="text/javascript" src="js/jquery-1.5.1.min.js"></script>
  <script type="text/javascript" src="js/jquery-ui-1.8.14.custom.min.js"></script>
  <script type="text/javascript" src="js/glyph.js?v=5"></script>
  <script type="text/javascript">
    $(function() {
      $("#tabs").tabs({
        select: function(event, ui) {
          if (ui.panel.id == "tabs-unicode-map") {
            $(ui.panel).load("<?php echo $_SERVER['REQUEST_URI']; ?>&unicodemap=1");
          }
        }
      });
    });
  </script>
</head>
<body>
<?php 

require_once "../classes/Font.php";

$fontfile = null;
if (isset($_GET["fontfile"])) {
  $fontfile = basename($_GET["fontfile"]);
  $fontfile = "../fonts/$fontfile";
}

$unicodemap = @$_GET["unicodemap"];

$t = microtime(true);

$font = Font::load($fontfile);

if ($font instanceof Font_TrueType_Collection) {
  $font = $font->getFont(0);
}

if ($unicodemap) {
  ?>
<style type="text/css">
@font-face {
  font-family: unicode-map; 
  font-weight: normal;
  font-style: normal;
  font-variant: normal;
  src: url('<?php echo $fontfile; ?>'); 
}
</style>
<div class="unicode-map">
  <?php 
        
  $subtable = null;
  foreach($font->getData("cmap", "subtables") as $_subtable) {
    if ($_subtable["platformID"] == 3 && $_subtable["platformSpecificID"] == 1) {
      $subtable = $_subtable;
      break;
    }
  }
  
  $empty = 0;
  $names = $font->getData("post", "names");
  
  for($c = 0; $c <= 0xFFFF; $c++) { 
    if (($c % 256 == 0 || $c == 0xFFFF) && $empty > 0) {
      echo "<b style=\"width:".($empty*3)."px\"></b>";
      $empty = 0;
    }
    
    if (isset($subtable["glyphIndexArray"][$c])) {
      $g = $subtable["glyphIndexArray"][$c];
      
      if ($empty > 0) {
        echo "<b style=\"width:".($empty*3)."px\"></b>";
        $empty = 0;
      }
      echo "<i><s>&#$c;<br /><div class=\"info\">$c<br />".(isset($names[$g]) ? $names[$g] : sprintf("uni%04x", $c))."</div></s></i>";
    }
    else {
      $empty++;
    }
  } ?>
</div>

<?php
} 
else { 
  $font->parse();

  //$font->saveAdobeFontMetrics("$fontfile.ufm");
  ?>
<span style="float: right;">
  File size: <?php echo round(filesize($fontfile) / 1024, 3); ?>KB &mdash;
  Memory: <?php echo (memory_get_peak_usage(true) / 1024); ?>KB &mdash;
  Time: <?php echo round(microtime(true) - $t, 4); ?>s
  <br />
  <a href="make_subset.php?fontfile=<?php echo $fontfile; ?>&amp;name=<?php echo urlencode($font->getFontSubfamilyID()); ?>">Make a subset of this font</a>
</span>

<h1><?php echo $font->getFontFullName(); ?></h1>
<h3><?php echo $font->getFontVersion(); ?></h3>
<hr />

<div id="tabs">
  <ul>
    <li><a href="#tabs-header">Header</a></li>

    <?php foreach($font->getTable() as $entry) {
      $tag = $entry->tag; 
      $data = $font->getData($tag); 
      
      ?>
      <li>
        <a <?php if (!$data) { ?> style="color: #ccc;" <?php } ?> href="#tabs-<?php echo preg_replace("/[^a-z0-9]/i", "_", $tag); ?>"><?php echo $tag; ?></a>
      </li>
    <?php } ?>
    <li><a href="#tabs-unicode-map">Unicode map</a></li>
  </ul>

  <div id="tabs-header"><pre><?php echo var_export($font->header->data, true); ?></pre></div>
  
  <?php foreach($font->getTable() as $table) {
    $tag = $table->tag;
    $data = $font->getData($tag); 
    
    ?>
    <div id="tabs-<?php echo preg_replace("/[^a-z0-9]/i", "_", $tag); ?>">
      <?php 
      if ($data) {
        echo $font->getTableObject($tag)->toHTML();
      }
      else {
        echo "Not yet implemented";
      }
      
      ?>
    </div>
    
    <div id="tabs-unicode-map"></div>
  <?php } ?>
</div>

<?php } ?>
</body>
</html>