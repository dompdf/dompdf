<?php
define("DOWNLOAD_LOG_DIR", "log/downloads/");
if ( !isset($_GET["file"]) )
  die("No file to find, so not found");

$dir = dirname(__FILE__);
$file = rawurldecode($_GET["file"]);
$filename = $dir . "/downloads/$file";

if ( !is_readable($filename) )
  die($filename . " is not readable");

header("Location: http://" . $_SERVER["HTTP_HOST"] . "/dompdf/downloads/$file");

$log_file = DOWNLOAD_LOG_DIR . "download.log";

$referer = isset($_SERVER["HTTP_REFERER"]) ? $_SERVER["HTTP_REFERER"] : "-";
$remote_addr = $_SERVER["HTTP_X_FORWARDED_FOR"] ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];

$entry = date("Y-m-d H:i:s") . " - " . $remote_addr . " - " . $file . " - " . $referer . "\n";
file_put_contents($log_file, $entry, FILE_APPEND);

?>