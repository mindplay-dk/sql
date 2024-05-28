<?php

namespace mindplay\sql\model;

use mindplay\sql\model\schema\Schema;
use mindplay\sql\model\schema\Table;

/**
 * This interface defines an internal facet of the DatabaseContainer as a factory
 * for the creation of arbitrary Table objects.
 */
interface TableFactory
{
    /**
     * @param $schema     owner Schema reference
     * @param $class_name Table class-name
     */
    public function createTable(Schema $schema, string $class_name, string $table_name, string|null $alias): Table;
}
