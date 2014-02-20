<?php

define('DOMPDF_ENABLE_AUTOLOAD', false);
require_once __DIR__ . '/../dompdf_config.inc.php';

DomPdf\Autoloader::register();

if (!@include_once __DIR__ . '/../vendor/autoload.php') {
    if (!@include_once __DIR__ . '/../../../autoload.php') {
        trigger_error("Unable to load dependencies", E_USER_ERROR);
    }
}