<?php

namespace mindplay\sql\model;

use mindplay\sql\model\schema\Table;

/**
 * This interface defines an internal facet of the DatabaseContainer as a factory
 * for the creation of arbitrary Table objects.
 */
interface TableFactory
{
    /**
     * @param string      $class_name Table class-name
     * @param string      $table_name
     * @param string|null $alias
     *
     * @return Table
     */
    public function createTable($class_name, $table_name, $alias);
}
