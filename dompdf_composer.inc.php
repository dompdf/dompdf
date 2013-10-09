<?php

if (defined('DOMPDF_COMPOSER_LOADED')) {
  return;
}

define('DOMPDF_COMPOSER_LOADED', true);
define('DOMPDF_ENABLE_AUTOLOAD', false);

if (!function_exists('dompdf_composer_autoload')) {
    function dompdf_composer_autoload($classname)
    {
        if (0 === strncmp($classname, 'DOMPDF', 6)) {
            spl_autoload_unregister('dompdf_composer_autoload');
            require_once dirname(__FILE__) . '/dompdf_config.inc.php';
        }
    }
}

spl_autoload_register('dompdf_composer_autoload', true, true);
