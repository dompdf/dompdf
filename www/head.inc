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
  <link rel="SHORTCUT ICON" href="images/favicon.ico"/>
  <script type="text/javascript" src="jquery-1.4.2.js"></script>
</head>

<body>

<div id="header" class="bar">
  <a href="index.php"><img id="logo" src="images/title.gif" alt="dompdf"/></a>
  <a href="http://code.google.com/p/dompdf/" target="_blank">dompdf @ google code</a>
  &mdash; 
  Send bug reports &amp; support questions to <a href="http://groups.google.com/group/dompdf">Google Groups</a>.
</div>

<div id="left_col">
  <ul>
    <li style="list-style-image: url('images/arrow_02.gif');"><a href="examples.php">Examples</a></li>
    <li style="list-style-image: url('images/arrow_03.gif');"><a href="setup.php">Setup / Config</a></li>
  </ul>
</div>

<div id="content">