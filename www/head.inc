<?php

session_start();

require_once "../dompdf_config.inc.php";

function li_arrow() {
  return '<li style="list-style-image: url(\'images/arrow_0' . rand(1,6) . '.gif\');">';  
}

function li_star() {
  return '<li style="list-style-image: url(\'images/star_0' . rand(1,5) . '.gif\');">';  
}

function auth_ok(){
  return isset($_SESSION["authenticated"]) && $_SESSION["authenticated"] === true;
}

function auth_get_link(){
  return '<a href="'.get_php_self().'?login=1">Authenticate to access this section</a>';
}

function get_php_self(){
  return isset($_SERVER['PHP_SELF']) ? htmlentities(strip_tags($_SERVER['PHP_SELF'],''), ENT_QUOTES, 'UTF-8') : '';
}

// From apc.php
function auth_check() {
  if ( DOMPDF_ADMIN_PASSWORD == "password" ) return false;
  
  if ( isset($_GET["login"]) || isset($_SERVER["PHP_AUTH_USER"]) ) {

    if (!isset($_SERVER["PHP_AUTH_USER"]) ||
        !isset($_SERVER["PHP_AUTH_PW"]) ||
        $_SERVER["PHP_AUTH_USER"] != DOMPDF_ADMIN_USERNAME ||
        $_SERVER["PHP_AUTH_PW"]   != DOMPDF_ADMIN_PASSWORD) {
  
      $PHP_SELF = get_php_self();
  
      header('WWW-Authenticate: Basic realm="DOMPDF Login"');
      header('HTTP/1.0 401 Unauthorized');

      echo <<<EOB
        <html><body>
        <h1>Rejected!</h1>
        <big>Wrong Username or Password!</big><br/>&nbsp;<br/>&nbsp;
        <big><a href='$PHP_SELF'>Continue...</a></big>
        </body></html>
EOB;
      exit;
    }
    
    else {
      $_SESSION["authenticated"] = true;
      return true;
    }
  }
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