<?php
use Dompdf\Tests\OutputTest\Dataset;
use Dompdf\Tests\OutputTest\OutputTest;

/**
 * Usage:
 * * `php bin/update-reference-output.php` to update the reference files for all
 *   test cases
 * * `php bin/update-reference-output.php <name-prefix>` to update the reference
 *   files for all test cases with a path starting with the specified prefix
 *   (paths considered relative to the parent `OutputTest` directory)
 */
require __DIR__ . "/../vendor/autoload.php";

$pathTest = $argv[1] ?? "";
$datasets = OutputTest::datasets();
$include = $pathTest !== ""
    ? function (Dataset $set) use ($pathTest) {
        return substr($set->name, 0, strlen($pathTest)) === $pathTest;
    } : function () {
        return true;
    };

foreach ($datasets as $dataset) {
    if (!$include($dataset)) {
        continue;
    }

    echo "Updating " . $dataset->name . PHP_EOL;
    $dataset->updateReferenceFile();
}
