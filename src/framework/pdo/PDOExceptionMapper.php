<?php

namespace mindplay\sql\framework\pdo;

interface PDOExceptionMapper
{
    /**
     * Determine the `SQLException`-type for the ANSI SQL-state and/or driver-specific error code.
     *
     * @param string $sql_state     ANSI SQL-state
     * @param int    $error_code    driver-specific error-code
     * @param string $error_message driver-specific error-message
     *
     * @return string SQLException-type name
     */
    public function getExceptionType($sql_state, $error_code, $error_message);
}
