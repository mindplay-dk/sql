<?php

use function mindplay\testies\{ run, configure, teardown };

require dirname(__DIR__) . '/vendor/autoload.php';

require __DIR__ . '/fixtures.php';
require __DIR__ . '/helpers.php';

$config = json_decode(
    file_get_contents(file_exists(__DIR__ . '/config.json')
        ? __DIR__ . '/config.json'
        : __DIR__ . '/config.dist.json' // fall back on default settings (these will work on travis)
    ),
    true // $assoc
);

teardown(function () {
    Mockery::close();
});

$suites = ["unit", "integration"];

$suites = array_keys(getopt("", $suites)) ?: $suites;

foreach ($suites as $suite) {
    require __DIR__ . "/test-{$suite}.php";
}

configure()->enableCodeCoverage(__DIR__ . '/build/clover.xml', dirname(__DIR__) . '/src');

exit(run());
