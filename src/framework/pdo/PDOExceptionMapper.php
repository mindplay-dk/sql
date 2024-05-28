<?php

namespace mindplay\sql\framework\pdo;

use mindplay\sql\exceptions\SQLException;

interface PDOExceptionMapper
{
    /**
     * Determine the `SQLException`-type for the ANSI SQL-state and/or driver-specific error code.
     *
     * @param string $sql_state     ANSI SQL-state
     * @param int    $error_code    driver-specific error-code
     * @param string $error_message driver-specific error-message
     *
     * @return class-string<SQLException> SQLException-type name
     */
    public function getExceptionType(string $sql_state, int $error_code, string $error_message): string;
}
