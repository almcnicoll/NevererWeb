<?php
require_once __DIR__ . '/bootstrap.php';

$testFiles = glob(__DIR__ . '/*Test.php');
sort($testFiles);

if (count($testFiles) === 0) {
    fwrite(STDOUT, "No test files found (expected tests/php/*Test.php)\n");
    exit(1);
}

foreach ($testFiles as $file) {
    require $file;
}

exit(TestRunner::summary());
