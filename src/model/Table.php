<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\Driver;
use mindplay\sql\framework\TypeProvider;
use ReflectionClass;
use ReflectionMethod;

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

    /**
     * Table constructor.
     *
     * @param Driver       $driver
     * @param TypeProvider $types
     * @param string       $name
     * @param string|null  $alias
     */
    public function __construct(Driver $driver, TypeProvider $types, $name, $alias)
    {
        $this->driver = $driver;
        $this->types = $types;
        $this->name = $name;
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @return Column[] list of all available Columns
     */
    public function listColumns()
    {
        // create a whitelist of parent types, excluding the Table class itself:

        $type = get_class($this);

        $whitelist = [];

        while ($type && $type !== self::class) {
            $whitelist[$type] = true;

            $type = get_parent_class($type);
        }

        // reflect all available public methods:

        $reflection = new ReflectionClass($this);

        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        $columns = [];

        foreach ($methods as $method) {
            if (isset($whitelist[$method->class]) && !$method->isStatic()) {
                $columns[] = $this->__get($method->name);
            }
        }

        return $columns;
    }

    /**
     * @param string      $type Type class-name
     * @param string      $name
     * @param string|null $alias
     *
     * @return Column
     */
    protected function createColumn($type, $name, $alias)
    {
        return new Column($this, $this->driver, $this->types->getType($type), $name, $alias);
    }

    /**
     * @ignore
     *
     * @return string
     */
    public function __toString()
    {
        return $this->driver->quoteName($this->alias ?: $this->name);
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

        return $this->$name($this->alias ? "{$this->alias}_{$name}" : null);
    }
}
