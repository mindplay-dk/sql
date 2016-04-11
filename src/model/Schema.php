<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\TableFactory;

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
     * @param             $class_name
     * @param             $table_name
     * @param string|null $alias optional Table alias
     *
     * @return Table
     * @internal param string $name Table class-name
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
        
        return call_user_func([$this, $name], $name);
    }
}
