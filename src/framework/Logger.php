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
     * @param array  $params    placeholder name/value pairs
     * @param float  $time_msec execution time (in milliseconds)
     *
     * @return void
     */
    public function logQuery($sql, $params, $time_msec);
}
