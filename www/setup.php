<?php include("head.inc"); ?>

<a name="setup"> </a>
<h2>Setup</h2>

<ul>
  <li style="list-style-image: url('images/star_02.gif');"><a href="#system">System Configuration</a></li>
  <li style="list-style-image: url('images/star_02.gif');"><a href="#dompdf-config">DOMPDF Configuration</a></li>
</ul>

<h3 id="system">System Configuration</h3>

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
  "PCRE" => array(
    "required" => true,
    "value"    => phpversion("pcre"),
    "result"   => function_exists("preg_match") && @preg_match("/./u", "a"),
    "failure"  => "PCRE is required with Unicode support (the \"u\" modifier)",
  ),
  "Zlib" => array(
    "required" => true,
    "value"    => phpversion("zlib"),
    "result"   => function_exists("gzcompress"),
    "fallback" => "Recommended to compress PDF documents",
  ),
  "MBString extension" => array(
    "required" => true,
    "value"    => phpversion("mbstring"),
    "result"   => function_exists("mb_send_mail"), // Should never be reimplemented in dompdf
    "fallback" => "Recommended, will use fallback functions",
  ),
  "GD" => array(
    "required" => true,
    "value"    => phpversion("gd"),
    "result"   => function_exists("imagecreate"),
    "fallback" => "Required if you have images in your documents",
  ),
  "APC" => array(
    "required" => "For better performances",
    "value"    => phpversion("apc"),
    "result"   => function_exists("apc_fetch"),
    "fallback" => "Recommended for better performances",
  ),
  "GMagick or IMagick" => array(
    "required" => "Better with transparent PNG images",
    "value"    => null,
    "result"   => extension_loaded("gmagick") || extension_loaded("imagick"),
    "fallback" => "Recommended for better performances",
  ),
);

if (($gm = extension_loaded("gmagick")) || ($im = extension_loaded("imagick"))) {
  $server_configs["GMagick or IMagick"]["value"] = ($im ? "IMagick ".phpversion("imagick") : "GMagick ".phpversion("gmagick"));
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
      <td class="title"><?php echo $label; ?></td>
      <td><?php echo ($server_config["required"] === true ? "Yes" : $server_config["required"]); ?></td>
      <td class="<?php echo ($server_config["result"] ? "ok" : (isset($server_config["fallback"]) ? "warning" : "failed")); ?>">
        <?php
        echo $server_config["value"];
        if ($server_config["result"] && !$server_config["value"]) echo "Yes";
        if (!$server_config["result"]) {
          if (isset($server_config["fallback"])) {
            echo "<div>No. ".$server_config["fallback"]."</div>";
          }
          if (isset($server_config["failure"])) {
            echo "<div>".$server_config["failure"]."</div>";
          }
        }
        ?>
      </td>
    </tr>
  <?php } ?>

</table>

<h3 id="dompdf-config">DOMPDF Configuration</h3>

<?php
$dompdf_constants = array();
$defined_constants = get_defined_constants(true);

$constants = array(
  "DOMPDF_DIR" => array(
    "desc" => "Root directory of DOMPDF",
    "success" => "read",
  ),
  "DOMPDF_INC_DIR" => array(
    "desc" => "Include directory of DOMPDF",
    "success" => "read",
  ),
  "DOMPDF_LIB_DIR" => array(
    "desc" => "Third-party libraries directory of DOMPDF",
    "success" => "read",
  ),
  "DOMPDF_FONT_DIR" => array(
    "desc" => "Directory containing fonts loaded into DOMPDF",
    "success" => "write",
  ),
  "DOMPDF_FONT_CACHE" => array(
    "desc" => "Font metrics cache (used mainly by CPDF)",
    "success" => "write",
  ),
  "DOMPDF_TEMP_DIR" => array(
    "desc" => "Temporary folder",
    "success" => "write",
  ),
  "DOMPDF_CHROOT" => array(
    "desc" => "Restricted path",
    "success" => "read",
  ),
  "DOMPDF_UNICODE_ENABLED" => array(
    "desc" => "Unicode support (thanks to additional fonts)",
  ),
  "DOMPDF_ENABLE_FONTSUBSETTING" => array(
    "desc" => "Enable font subsetting, will make smaller documents when using Unicode fonts",
  ),
  "DOMPDF_PDF_BACKEND" => array(
    "desc" => "Backend library that makes the outputted file (PDF, image)",
    "success" => "backend",
  ),
  "DOMPDF_DEFAULT_MEDIA_TYPE" => array(
    "desc" => "Default media type (print, screen, ...)",
  ),
  "DOMPDF_DEFAULT_PAPER_SIZE" => array(
    "desc" => "Default paper size (A4, letter, ...)",
  ),
  "DOMPDF_DEFAULT_FONT" => array(
    "desc" => "Default font, used if the specified font in the CSS stylesheet was not found",
  ),
  "DOMPDF_DPI" => array(
    "desc" => "DPI scale of the document",
  ),
  "DOMPDF_ENABLE_PHP" => array(
    "desc" => "Inline PHP support",
  ),
  "DOMPDF_ENABLE_JAVASCRIPT" => array(
    "desc" => "Inline JavaScript support",
  ),
  "DOMPDF_ENABLE_REMOTE" => array(
    "desc" => "Allow remote stylesheets and images",
    "success" => "remote",
  ),
  "DOMPDF_ENABLE_CSS_FLOAT" => array(
    "desc" => "Enable CSS float support (experimental)",
  ),
  "DOMPDF_ENABLE_HTML5PARSER" => array(
    "desc" => "Enable the HTML5 parser (experimental)",
  ),
  "DEBUGPNG" => array(
    "desc" => "Debug PNG images",
  ),
  "DEBUGKEEPTEMP" => array(
    "desc" => "Keep temporary image files",
  ),
  "DEBUGCSS" => array(
    "desc" => "Debug CSS",
  ),
  "DEBUG_LAYOUT" => array(
    "desc" => "Debug layout",
  ),
  "DEBUG_LAYOUT_LINES" => array(
    "desc" => "Debug text lines layout",
  ),
  "DEBUG_LAYOUT_BLOCKS" => array(
    "desc" => "Debug block elements layout",
  ),
  "DEBUG_LAYOUT_INLINE" => array(
    "desc" => "Debug inline elements layout",
  ),
  "DEBUG_LAYOUT_PADDINGBOX" => array(
    "desc" => "Debug padding boxes layout",
  ),
  "DOMPDF_LOG_OUTPUT_FILE" => array(
    "desc" => "The file in which dompdf will write warnings and messages",
    "success" => "write",
  ),
  "DOMPDF_FONT_HEIGHT_RATIO" => array(
    "desc" => "The line height ratio to apply to get a render like web browsers",
  ),
  "DOMPDF_ENABLE_AUTOLOAD" => array(
    "desc" => "Enable the DOMPDF autoloader",
  ),
	"DOMPDF_AUTOLOAD_PREPEND" => array(
    "desc" => "Prepend the dompdf autoload function to the SPL autoload functions already registered instead of appending it",
  ),
  "DOMPDF_ADMIN_USERNAME" => array(
    "desc" => "The username required to access restricted sections",
    "secret" => true,
  ),
  "DOMPDF_ADMIN_PASSWORD" => array(
    "desc" => "The password required to access restricted sections",
    "secret" => true,
    "success" => "auth",
  ),
);
?>

<table class="setup">
  <tr>
    <th>Config name</th>
    <th>Value</th>
    <th>Description</th>
    <th>Status</th>
  </tr>

  <?php foreach($defined_constants["user"] as $const => $value) { ?>
    <tr>
      <td class="title"><?php echo $const; ?></td>
      <td>
      <?php
        if (isset($constants[$const]["secret"])) {
          echo "******";
        }
        else {
          var_export($value);
        }
      ?>
      </td>
      <td><?php if (isset($constants[$const]["desc"])) echo $constants[$const]["desc"]; ?></td>
      <td <?php
        $message = "";
        if (isset($constants[$const]["success"])) {
          switch($constants[$const]["success"]) {
            case "read":
              $success = is_readable($value);
              $message = ($success ? "Readable" : "Not readable");
            break;
            case "write":
              $success = is_writable($value);
              $message = ($success ? "Writable" : "Not writable");
            break;
            case "remote":
              $success = ini_get("allow_url_fopen");
              $message = ($success ? "allow_url_fopen enabled" : "allow_url_fopen disabled");
            break;
            case "backend":
              switch (strtolower($value)) {
                case "cpdf":
                  $success = true;
                break;
                case "pdflib":
                  $success = function_exists("PDF_begin_document");
                  $message = "The PDFLib backend needs the PDF PECL extension";
                break;
                case "gd":
                  $success = function_exists("imagecreate");
                  $message = "The GD backend requires GD2";
                break;
              }
            break;
            case "auth":
              $success = !in_array($value, array("admin", "password"));
              $message = ($success ? "OK" : "Password should be changed");
            break;
          }
          echo 'class="' . ($success ? "ok" : "failed") . '"';
        }
      ?>><?php echo $message; ?></td>
    </tr>
  <?php } ?>

</table>


<?php include("foot.inc"); ?>