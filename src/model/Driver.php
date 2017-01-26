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
     * @param string|null $schema
     * @param string      $table
     *
     * @return string quoted, schema-qualified table-name
     */
    public function quoteTableName($schema, $table);
}
