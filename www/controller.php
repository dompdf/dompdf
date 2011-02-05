<?php 

$cmd = isset($_GET["cmd"]) ? $_GET["cmd"] : null;

include "../dompdf_config.inc.php";

switch ($cmd) {
  case "clear-font-cache":
    $files = glob(DOMPDF_FONT_DIR."*.{ufm,afm}.php", GLOB_BRACE);
    foreach($files as $file) {
      unlink($file);
    }
    echo count($files)." cache files removed. Refresh page.<br />";
  break;
}