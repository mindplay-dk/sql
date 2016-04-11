<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\Driver;
use mindplay\sql\framework\TypeProvider;

abstract class Table
{
    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var TypeProvider
     */
    private $types;

    /**
     * @var string
     */
    private $name;
    
    /**
     * @var string|null
     */
    private $alias;
    
    public function __construct(Driver $driver, TypeProvider $types, $name, $alias)
    {
        $this->driver = $driver;
        $this->types = $types;
        $this->name = $name;
        $this->alias = $alias;
    }

    /**
     * @param string $type Type class-name
     * @param string $name
     * @param string $alias
     * 
     * @return Column
     */
    protected function createColumn($type, $name, $alias)
    {
        return new Column($this, $this->types->getType($type), $name, $alias);
    }
    
    /**
     * @ignore
     *
     * @param string $name
     * 
     * @return Column
     */
    public function __get($name)
    {
        // TODO caching

        return call_user_func([$this, $name], $name);
    }
}
