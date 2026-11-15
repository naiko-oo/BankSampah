<?php

require_once __DIR__ . '/TestCase.php';
require_once __DIR__ . '/../backend/services/bank_sampah_rules.php';

$testFiles = glob(__DIR__ . DIRECTORY_SEPARATOR . '*Test.php');
if ($testFiles === false || count($testFiles) === 0) {
    fwrite(STDERR, 'No unit test files found.' . PHP_EOL);
    exit(1);
}

sort($testFiles);
$testCase = new TestCase();

foreach ($testFiles as $testFile) {
    $suite = require $testFile;
    if (!is_callable($suite)) {
        fwrite(STDERR, 'Invalid test suite: ' . basename($testFile) . PHP_EOL);
        exit(1);
    }
    $suite($testCase);
}

exit($testCase->finish());
