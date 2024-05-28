<?php

namespace mindplay\sql\model\schema;

use mindplay\sql\model\TableFactory;

/**
 * This is an abstract base-class for user-defined Schema-types.
 */
abstract class Schema
{
    private TableFactory $factory;

    public function __construct(TableFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * This name is used to qualify table-references, e.g. using schema-names in Postgres, or
     * table-name prefixes in MySQL.
     *
     * The default implementation returns NULL - override this in your Schema implementation, if needed.
     *
     * @return string|null optional Schema-name, or NULL
     */
    public function getName(): string|null
    {
        return null;
    }

    /**
     * @param $class_name Table class-name
     * @param $table_name relational table-name
     * @param $alias      optional Table alias
     */
    protected function createTable(string $class_name, string $table_name, string|null $alias = null): Table
    {
        return $this->factory->createTable($this, $class_name, $table_name, $alias);
    }

    /**
     * @ignore
     *
     * @param string $name
     *
     * @return Table
     */
    public function __get($name)
    {
        // TODO caching

        return $this->$name(null);
    }
}
