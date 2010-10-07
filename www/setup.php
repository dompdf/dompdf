<?php include("head.inc"); ?>

<a name="setup"> </a>
<h2>Setup</h2>

<h3>System Configuration</h3>

<?php 
require_once("../dompdf_config.inc.php");

$server_configs = array(
  "PHP Version" => array(
    "required" => "5.0",
    "value"    => phpversion(),
    "result"   => version_compare(phpversion(), "5.0"),
  ),
  "DOMDocument extension" => array(
    "required" => true,
    "value"    => phpversion("DOM"),
    "result"   => class_exists("DOMDocument"),
  ),
  "MBString extension" => array(
    "required" => true,
    "value"    => phpversion("mbstring"),
    "result"   => function_exists("mb_substr"),
    "fallback" => "Recommended",
  ),
  "PCRE" => array(
    "required" => true,
    "value"    => phpversion("pcre"),
    "result"   => function_exists("preg_match"),
  ),
  "Zlib" => array(
    "required" => true,
    "value"    => phpversion("zlib"),
    "result"   => function_exists("gzcompress"),
    "fallback" => "Recommended",
  ),
);

if (strtolower(DOMPDF_PDF_BACKEND) == "pdflib") {
  $server_configs["PDF lib"] = array(
    "required" => false,
    "value"    => phpversion("pdf"),
    "result"   => function_exists("PDF_begin_document"),
    "fallback" => "Required as the chosen backend is PDFLIB",
  );
}

?>

<table class="setup">
  <tr>
    <th></th>
    <th>Required</th>
    <th>Present</th>
  </tr>
  
  <?php foreach($server_configs as $label => $server_config) { ?>
    <tr>
      <td><?php echo $label; ?></td>
      <td><?php echo ($server_config["required"] === true ? "Yes" : $server_config["required"]); ?></td>
      <td class="<?php echo ($server_config["result"] ? "ok" : (isset($server_config["fallback"]) ? "warning" : "failed")); ?>">
        <?php
        echo $server_config["value"];
        if (!$server_config["result"] && isset($server_config["fallback"])) {
          echo "<div>".$server_config["fallback"]."</div>";
        }
        ?>
      </td>
    </tr>
  <?php } ?>
  
</table>

<h3>DOMPDF Configuration</h3>

<?php 
$dompdf_constants = array();
$defined_constants = get_defined_constants(true);
?>

<table class="setup">

  <?php foreach($defined_constants["user"] as $const => $value) { ?>
    <tr>
      <td><?php echo $const; ?></td>
      <td><?php var_export($value); ?></td>
    </tr>
  <?php } ?>

</table>

<h3>Installed fonts</h3>

<?php 
$fonts = Font_Metrics::get_font_families();
?>

<table class="setup">

  <?php foreach($fonts as $family => $variants) { ?>
    <tr>
      <td>
        <?php 
          echo $family; 
          if ($family == DOMPDF_DEFAULT_FONT) echo ' (default)';
        ?>
      </td>
      <td>
      <?php 
      foreach($variants as $name => $path) {
        echo "<strong style='width: 10em;'>$name</strong> : $path<br />";
      }
      ?>
      </td>
    </tr>
  <?php } ?>

</table>

<?php include("foot.inc"); ?>