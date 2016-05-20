<?php

namespace mindplay\sql\model;

/**
 * This interface defines the driver model for DBMS-specific operations.
 */
interface Driver
{
    /**
     * @param string $name table or column name
     *
     * @return string quoted name
     */
    public function quoteName($name);

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
