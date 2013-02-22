<?php

require_once "../dompdf_config.inc.php";

//if dompdf.php runs in virtual server root, dirname does not return empty folder but '/' or '\' (windows).
//This leads to a duplicate separator in unix etc. and an error in Windows. Therefore strip off.

$dompdf = dirname(dirname($_SERVER["PHP_SELF"]));
if ( $dompdf == '/' || $dompdf == '\\') {
  $dompdf = '';
}

$dompdf .= "/dompdf.php?base_path=" . rawurlencode("www/test/");

include "head.inc"; 

?>

<script type="text/javascript">
function resizePreview(){
  var preview = $("#preview");
  preview.height($(window).height() - preview.offset().top - 2);
}

function getPath(hash) {
  var file, type;
  var parts = hash.split(/,/);
  
  file = parts[0];
  
  if (parts.length == 2) {
    type = parts[1];
  }
  
  switch(type) {
    default:
    case "html": 
      return "test/"+file;
    case "pdf":
      return "<?php echo $dompdf; ?>&options[Attachment]=0&input_file="+file+"#toolbar=0&view=FitH&statusbar=0&messages=0&navpanes=0";
  }
}

function setHash(hash) {
  location.hash = "#"+hash;
}

$(function(){
  var preview = $("#preview");
  resizePreview();

  $(window).scroll(function() {
    var scrollTop = Math.min($(this).scrollTop(), preview.height()+preview.parent().offset().top) - 2;
    preview.css("margin-top", scrollTop + "px");
  });

  $(window).resize(resizePreview);
  
  var hash = location.hash;
  var type = "html";
  if (hash) {
    hash = hash.substr(1);
    preview.attr("src", getPath(hash));
  }
});
</script>
<iframe id="preview" name="preview" src="about:blank" frameborder="0" marginheight="0" marginwidth="0"></iframe>

<a name="samples"> </a>
<h2>Samples</h2>

<p>Below are some sample files. The PDF version is generated on the fly by dompdf.  (The source HTML &amp; CSS for
these files is included in the test/ directory of the distribution
package.)</p>

<?php

$extensions = array("html");
if ( DOMPDF_ENABLE_PHP ) {
  $extensions[] = "php";
}

// To be compatible with non-GNU systems
if (!defined("GLOB_BRACE")) {
  $test_files = glob("test/*");
  $test_files = preg_grep("/(" . implode("|",$extensions) . ")/i", $test_files);
}
else {
  $test_files = glob("test/*.{".implode(",", $extensions)."}", GLOB_BRACE);
}

$sections = array(
  "print"    => array(), 
  "css"      => array(), 
  "dom"      => array(), 
  "image"    => array(), 
  "page"     => array(),
  "encoding" => array(), 
  "script"   => array(), 
  "quirks"   => array(), 
  "other"    => array(), 
);

foreach ( $test_files as $file ) {
  preg_match("@[\\/](([^_]+)_?(.*))\.(".implode("|", $extensions).")$@i", $file, $matches);
  $prefix = $matches[2];

  if ( array_key_exists($prefix, $sections) ) {
    $sections[$prefix][] = array($file, $matches[3]);
  }
  else {
    $sections["other"][] = array($file, $matches[1]);
  }
}

foreach ( $sections as $section => $files ) {
  echo "<h3>$section</h3>";
  
  echo "<ul class=\"samples\">";
  foreach ( $files as $file ) {
    $filename = basename($file[0]);
    $title = $file[1];
    $arrow = "images/arrow_0" . rand(1, 6) . ".gif";  
    echo "<li style=\"list-style-image: url('$arrow');\">\n";
    echo " 
  [<a class=\"button\" target=\"preview\" onclick=\"setHash('$filename,html')\" href=\"test/$filename\">HTML</a>] 
  [<a class=\"button\" target=\"preview\" onclick=\"setHash('$filename,pdf')\" href=\"$dompdf&amp;options[Attachment]=0&amp;input_file=" . rawurlencode($filename) . "#toolbar=0&amp;view=FitH&amp;statusbar=0&amp;messages=0&amp;navpanes=0\">PDF</a>] ";
    echo $title;
    echo "</li>\n";
  }
  echo "</ul>";
}

include "foot.inc"; 
