<?php
require_once("../dompdf_config.inc.php");
if ( isset( $_POST["html"] ) ) {

  if ( get_magic_quotes_gpc() )
    $_POST["html"] = stripslashes($_POST["html"]);
  
  $old_limit = ini_set("memory_limit", "16M");
  
  $dompdf = new DOMPDF();
  $dompdf->load_html($_POST["html"]);
  $dompdf->set_paper($_POST["paper"], $_POST["orientation"]);
  $dompdf->render();

  $dompdf->stream("dompdf_out.pdf");

  exit(0);
}

?>
<?php include("head.inc"); ?>
<div id="toc">
<h2>On this page:</h2>
<ul>
<?php echo li_arrow() ?><a href="#samples">Samples</a></li>
<?php echo li_arrow() ?><a href="#demo">Demo</a></li>
</ul>
</div>

<a name="samples"> </a>
<h2>Samples</h2>

<p>Below are some sample files. The PDF version is generated on the fly by dompdf.  (The source HTML &amp; CSS for
these files is included in the test/ directory of the distribution
package.)</p>

<ul class="samples">
<?php
$test_files = glob(dirname(__FILE__) . "/test/*.{html,php}", GLOB_BRACE);
$dompdf = dirname(dirname($_SERVER["PHP_SELF"])) . "/dompdf.php?base_path=" . rawurlencode("www/test/");
foreach ( $test_files as $file ) {
  $file = basename($file);
  $arrow = "images/arrow_0" . rand(1, 6) . ".gif";  
  echo "<li style=\"list-style-image: url('$arrow');\">\n";
  echo $file;
  echo " [<a class=\"button\" target=\"blank\" href=\"test/$file\">HTML</a>] [<a class=\"button\" href=\"$dompdf&input_file=" . rawurlencode("www/test/$file") .  "\">PDF</a>]\n";
  echo "</li>\n";
}
?>
</ul>

<a name="demo"> </a>
<h2>Demo</h2>
<p>Enter your html snippet in the text box below to see it rendered as a
PDF: (Note by default, remote stylesheets, images &amp; are disabled.)</p>

<form action="<?php echo $_SERVER["PHP_SELF"];?>" method="post">
<div>
<p>Paper size and orientaion:
<select name="paper">
<?php
foreach ( array_keys(CPDF_Adapter::$PAPER_SIZES) as $size )
  echo "<option ". ($size == "letter" ? "selected " : "" ) . "value=\"$size\">$size</option>\n";
?>
</select>
<select name="orientation">
  <option value="portrait">portrait</option>
  <option value="landscape">landscape</option>
</select>
</p>

<textarea name="html" cols="60" rows="20">
&lt;html&gt;
&lt;head&gt;
&lt;style&gt;

/* Type some style rules here */

&lt;/style&gt;
&lt;/head&gt;

&lt;body&gt;

&lt;!-- Type some HTML here --&gt;

&lt;/body&gt;
&lt;/html&gt;
</textarea>

<div style="text-align: center; margin-top: 1em;">
<input type="submit" name="submit" value="submit"/>
</div>
</div>
</form>
<p style="font-size: 0.65em; text-align: center;">(Note: if you use a KHTML
based browser and are having difficulties loading the sample output, try
saving it to a file first.)</p>

<?php include("foot.inc"); ?>