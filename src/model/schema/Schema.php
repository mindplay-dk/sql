<?php

namespace mindplay\sql\model\schema;

use mindplay\sql\model\TableFactory;

/**
 * This is an abstract base-class for user-defined Schema-types.
 */
abstract class Schema
{
    /**
     * @var TableFactory
     */
    private $factory;

    /**
     * @param TableFactory $factory
     */
    public function __construct(TableFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param string      $class_name Table class-name
     * @param string      $table_name relational table-name
     * @param string|null $alias      optional Table alias
     *
     * @return Table
     */
    protected function createTable($class_name, $table_name, $alias = null)
    {
        return $this->factory->createTable($class_name, $table_name, $alias);
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
