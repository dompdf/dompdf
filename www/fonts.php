<?php 
include "head.inc"; 
require_once "../dompdf_config.inc.php"; 

function to_bytes($string) {
  $string = strtolower(trim($string));
  
  if (!preg_match("/(.*)([kmgt])/", $string, $matches)) {
    return intval($string);
  }
  
  list($string, $value, $suffix) = $matches;
  switch($suffix) {
    case 't': $value *= 1024;     
    case 'g': $value *= 1024;
    case 'm': $value *= 1024;
    case 'k': $value *= 1024;
  }
  
  return intval($value);
}

?>

<a name="setup"> </a>
<h2>Font manager</h2>

<ul>
  <li style="list-style-image: url('images/star_02.gif');"><a href="#installed-fonts">Installed fonts</a></li>
  <li style="list-style-image: url('images/star_02.gif');"><a href="#install-fonts">Install new fonts</a></li>
</ul>

<h3 id="installed-fonts">Installed fonts</h3>

<?php 
Font_Metrics::init();
$fonts = Font_Metrics::get_font_families();
$extensions = array("ttf", "afm", "afm.php", "ufm", "ufm.php");
?>

<button onclick="$('#clear-font-cache-message').load('controller.php?cmd=clear-font-cache', function(){ location.reload(); })">Clear font cache</button>
<span id="clear-font-cache-message"></span>

<table class="setup">
  <tr>
    <th rowspan="2">Font family</th>
    <th rowspan="2">Variants</th>
    <th colspan="6">File versions</th>
  </tr>
  <tr>
    <th>TTF</th>
    <th>AFM</th>
    <th>AFM cache</th>
    <th>UFM</th>
    <th>UFM cache</th>
  </tr>
  <?php foreach($fonts as $family => $variants) { ?>
    <tr>
      <td class="title" rowspan="<?php echo count($variants); ?>">
        <?php 
          echo htmlentities($family);
          if ($family == DOMPDF_DEFAULT_FONT) {
            echo ' <strong>(default)</strong>';
          }
        ?>
      </td>
      <?php 
      $i = 0;
      foreach($variants as $name => $path) {
        if ($i > 0) {
          echo "<tr>";
        }
        
        echo "
        <td>
          <strong style='width: 10em;'>".htmlentities($name)."</strong> : ".htmlentities($path)."<br />
        </td>";
        
        foreach ($extensions as $ext) {
          $v = "";
          $class = "";
          
          if (is_readable("$path.$ext")) {
            // if not cache file
            if (strpos($ext, ".php") === false) {
              $class = "ok";
              $v = $ext;
            }
            
            // cache file
            else {
              // check if old cache format
              $content = file_get_contents("$path.$ext", null, null, null, 50);
              if (strpos($content, '$this->')) {
                $v = "DEPREC.";
              }
              else {
                ob_start();
                $d = include "$path.$ext";
                ob_end_clean();
                
                if ($d == 1)
                  $v = "DEPREC.";
                else {
                  $class = "ok";
                  $v = $d["_version_"];
                }
              }
            }
          }
          
          echo "<td style='width: 2em; text-align: center;' class='$class'>$v</td>";
        }
        
        echo "</tr>";
        $i++;
      }
      ?>
  <?php } ?>

</table>

<h3 id="install-fonts">Install new fonts</h3>

<script type="text/javascript">
function checkFileName(form) {
  var fields = {
    normal: "Normal",
    bold: "Bold",
    bold_italic: "Bold italic",
    italic: "Italic"
  };
  var pattern = /\.[ot]tf$/i;
  var ok = true;

  if (!form.elements.family.value) {
    alert("The font name is required");
    form.elements.family.focus();
    return false;
  }
  
  $.each(fields, function(key, name){
    var value = form.elements["file["+key+"]"].value;

    if (!value) return;
    
    if (!value.match(pattern)) {
      alert("The font name specified for "+name+" is not a TrueType font");
      ok = false;
      return false;
    }
  });
    
  return ok;
}
</script>

<?php 

if (auth_ok()) {
$max_size = min(to_bytes(ini_get('post_max_size')), to_bytes(ini_get('upload_max_filesize'))); 
?>

<form name="upload-font" method="post" action="controller.php?cmd=install-font" target="upload-font" enctype="multipart/form-data" onsubmit="return checkFileName(this)">
  <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $max_size; ?>" />
  
  <table class="setup">
    <tr>
      <td class="title">Name</td>
      <td><input type="text" name="family" /></td>
      <td rowspan="6"><iframe name="upload-font" id="upload-font" style="border: 0; width: 500px;"></iframe></td>
    </tr>
    <tr>
      <td class="title">Normal</td>
      <td><input type="file" name="file[normal]" /></td>
    </tr>
    <tr>
      <td class="title">Bold</td>
      <td><input type="file" name="file[bold]" /></td>
    </tr>
    <tr>
      <td class="title">Bold italic</td>
      <td><input type="file" name="file[bold_italic]" /></td>
    </tr>
    <tr>
      <td class="title">Italic</td>
      <td><input type="file" name="file[italic]" /></td>
    </tr>
    <tr>
      <td></td>
      <td><button>Install !!</button></td>
    </tr>
  </table>
</form>
<?php }
else {
  echo auth_get_link();
} ?>

<?php include("foot.inc"); ?>