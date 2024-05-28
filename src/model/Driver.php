<?php

namespace mindplay\sql\model;

/**
 * This interface defines the driver model for DBMS-specific operations.
 */
interface Driver
{
    /**
     * @param $name table or column name
     *
     * @return string quoted name
     */
    public function quoteName(string $name): string;

    /**
     * @return string quoted, schema-qualified table-name
     */
    public function quoteTableName(string|null $schema, string $table): string;
}
