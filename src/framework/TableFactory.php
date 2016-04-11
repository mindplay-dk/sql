<?php

namespace mindplay\sql\framework;

use mindplay\sql\model\Table;

interface TableFactory
{
    /**
     * @param string      $class_name Table class-name
     * @param             $table_name
     * @param string|null $alias
     *
     * @return Table
     */
    public function createTable($class_name, $table_name, $alias);
}
