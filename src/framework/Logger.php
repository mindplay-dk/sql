<?php

namespace mindplay\sql\framework;

/**
 * Interface for logging SQL queries
 */
interface Logger
{
    /**
     * This function is called every time a query has been executed
     *
     * @param string $sql       SQL statement
     * @param array  $params    Interpolated parameter
     * @param float  $time_msec Query duration in milliseconds
     *
     * @return void
     */
    function logQuery($sql, $params, $time_msec);
}