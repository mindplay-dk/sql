<?php

use mindplay\sql\drivers\MySQLDriver;
use mindplay\sql\drivers\PostgresDriver;

require dirname(__DIR__) . '/vendor/autoload.php';

test(
    "can quote table-names",
    function () {
        $driver = new PostgresDriver();

        eq($driver->quoteName('foo'), '"foo"');
        eq($driver->quoteName('foo', 'bar'), '"foo"."bar"');

        $driver = new MySQLDriver();

        eq($driver->quoteName('foo'), '`foo`');
        eq($driver->quoteName('foo', 'bar'), '`foo`.`bar`');
    }
);

exit(run());
