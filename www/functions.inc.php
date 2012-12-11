<?php 

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
  if ( isset($_GET["login"]) && DOMPDF_ADMIN_PASSWORD == "password" ) {
    $_SESSION["auth_message"] = "The password must be changed in 'dompdf_config.custom.inc.php'";
    return false;
  }
  else {
    $_SESSION["auth_message"] = null;
  }
  
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
      $_SESSION["auth_message"] = null;
      $_SESSION["authenticated"] = true;
      return true;
    }
  }
}