<?php

namespace mindplay\sql\framework;

/**
 * This class implements a driver model for DBMS-specific operations.
 */
abstract class Driver
{
    /**
     * @param string      $table  table name
     * @param string|null $column column name (or NULL to quote only the table-name)
     *
     * @return string quoted name
     */
    abstract public function quoteName($table, $column = null);
}
