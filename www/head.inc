<?php

session_start();

require_once "../dompdf_config.inc.php";
require_once "functions.inc.php";

function li_arrow() {
  return '<li style="list-style-image: url(\'images/arrow_0' . rand(1,6) . '.gif\');">';  
}

function li_star() {
  return '<li style="list-style-image: url(\'images/star_0' . rand(1,5) . '.gif\');">';  
}

auth_check();

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>dompdf - The PHP 5 HTML to PDF Converter</title>
  <link rel="stylesheet" href="style.css" type="text/css"/>
  <link rel="SHORTCUT ICON" href="images/favicon.ico"/>
  <script type="text/javascript" src="jquery-1.4.2.js"></script>
  
  <?php if (isset($_SESSION["auth_message"])) { ?>
    <script type="text/javascript">
      alert("<?php echo $_SESSION["auth_message"]; ?>");
    </script>
  <?php } ?>
</head>

<body>

<div id="header" class="bar">
  <a href="index.php"><img id="logo" src="images/title.gif" alt="dompdf"/></a>
  <a href="http://www.dompdf.com/" target="_blank">www.dompdf.com</a>
  &mdash; 
  Send bug reports to <a href="http://code.google.com/p/dompdf/issues/list">the bug tracker</a> 
  &amp; support questions to <a href="http://groups.google.com/group/dompdf">Google Groups</a>.
</div>

<div id="left_col">
  <ul>
    <li style="list-style-image: url('images/star_02.gif');"><a href="index.php">Overview</a></li>
    <li style="list-style-image: url('images/star_02.gif');"><a href="examples.php">Examples</a></li>
    <li style="list-style-image: url('images/star_02.gif');"><a href="demo.php">Demo</a></li>
    <li style="list-style-image: url('images/star_02.gif');"><a href="setup.php">Setup / Config</a></li>
    <li style="list-style-image: url('images/star_02.gif');"><a href="fonts.php">Fonts</a></li>
  </ul>
</div>

<div id="content">