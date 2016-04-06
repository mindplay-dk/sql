<?php

use mindplay\sql\drivers\PostgresDriver;
use mindplay\sql\framework\Driver;

/**
 * @return Driver
 */
function create_driver()
{
    return new PostgresDriver();
}
