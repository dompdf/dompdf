<?php

date_default_timezone_set('UTC');

// Add composer autoloader
if (!@include_once __DIR__ . '/../vendor/autoload.php') {
    if (!@include_once __DIR__ . '/../../../autoload.php') {
        trigger_error("Unable to load dependencies", E_USER_ERROR);
    }
}

// Add test autoloader
spl_autoload_register(function ($class) {
    $class = str_replace('\\', '/', $class);
    $class = preg_replace('/^Dompdf/', 'Dompdf/Tests/_includes', $class);
    if (file_exists(__DIR__ . '/' . $class . '.php')) {
        require_once __DIR__ . '/' . $class . '.php';
    }
});

