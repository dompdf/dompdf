<?php
function li_arrow() {
  return '<li style="list-style-image: url(\'images/arrow_0' . rand(1,6) . '.gif\');">';  
}
function li_star() {
  return '<li style="list-style-image: url(\'images/star_0' . rand(1,5) . '.gif\');">';  
}
?>
<?php echo '<?' . 'xml version="1.0" encoding="iso-8859-1"?' . '>'; ?>
<!DOCTYPE html
  PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>dompdf - The PHP 5 HTML to PDF Converter</title>
<link rel="stylesheet" href="style.css" type="text/css"/>
<link rel="SHORTCUT ICON" href="http://<?php echo $_SERVER["HTTP_HOST"] . dirname($_SERVER["PHP_SELF"]);?>/images/favicon.ico"/>
</head>

<body>
<div id="header">
<a href="index.php"><img id="logo" src="images/title.gif" alt="dompdf"/></a>
</div>
<div id="left_col">
<h2>dompdf Homepage</h2>
  <ul>
    <li style="list-style-image: url('images/star_02.gif');"><a style="font-size: 0.8em;" href="http://digitaljunkies.ca/dompdf/">digitaljunkies.ca/dompdf</a></li>
  </ul>
  
<h2>About dompdf</h2>
  <ul>
    <li style="list-style-image: url('images/arrow_02.gif');"><a href="index.php">overview</a></li>
    <li style="list-style-image: url('images/arrow_03.gif');"><a href="index.php#features">features</a></li>
    <li style="list-style-image: url('images/arrow_04.gif');"><a href="index.php#limitations">limitations</a></li>
  </ul>
<h2>Documentation</h2>
  <ul>
    <li style="list-style-image: url('images/arrow_05.gif');"><a href="install.php">installation</a></li>
    <li style="list-style-image: url('images/arrow_06.gif');"><a href="usage.php">usage</a></li>
    <li style="list-style-image: url('images/arrow_01.gif');"><a href="http://www.digitaljunkies.ca/dompdf/doc/">api documentation</a></li>
  </ul>
<h2>Examples</h2>
  <ul>
    <li style="list-style-image: url('images/arrow_02.gif');"><a href="examples.php">samples</a></li>
    <li style="list-style-image: url('images/arrow_03.gif');"><a href="examples.php#demo">demo</a></li>
  </ul>
<h2>Support</h2>
  <ul>
    <li style="list-style-image: url('images/arrow_04.gif');"><a href="faq.php">FAQ</a></li>
  </ul>

  <p>Send bug reports &amp; support questions to
  <a href="http://sourceforge.net/projects/dompdf/">SourceForge</a>
  or send an email to <a style="white-space: nowrap"
  href="mailto:dompdf%40digitaljunkies%2eca">dompdf at
  digitaljunkies.ca</a>.</p>
</div>  
<div id="content">