<?php

namespace mindplay\sql\model\schema;

use mindplay\sql\model\Driver;
use mindplay\sql\model\TypeProvider;
use ReflectionClass;
use ReflectionMethod;

/**
 * This is an abstract base-class for user-defined Table-types belonging to a Schema.
 */
abstract class Table
{
    /**
     * @var Schema
     */
    private $schema;

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
     * @param Schema       $schema
     * @param Driver       $driver
     * @param TypeProvider $types
     * @param string       $name
     * @param string|null  $alias
     */
    public function __construct(Schema $schema, Driver $driver, TypeProvider $types, $name, $alias)
    {
        $this->schema = $schema;
        $this->driver = $driver;
        $this->types = $types;
        $this->name = $name;
        $this->alias = $alias;
    }

    /**
     * @return Schema owner Schema instance
     */
    public function getSchema()
    {
        return $this->schema;
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
     * @return string table expression (e.g. "{table} AS {alias}" for use in the FROM clause of an SQL statement)
     */
    public function getNode()
    {
        $alias = $this->getAlias();

        $quoted_table_name = $this->driver->quoteTableName($this->schema->getName(), $this->getName());

        if ($alias) {
            return $quoted_table_name . ' AS ' . $this->driver->quoteName($alias);
        }

        return $quoted_table_name;
    }

    /**
     * @param string|null $prefix optional Column Alias prefix
     *
     * @return Column[] list of all available Columns
     */
    public function listColumns($prefix = null)
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
                $alias = $prefix
                    ? "{$prefix}_{$method->name}"
                    : null;

                $columns[] =  $method->invoke($this, $alias);
            }
        }

        return $columns;
    }

    /**
     * @param string      $name
     * @param string      $type Type class-name
     * @param string|null $alias
     * @param mixed       $default
     *
     * @return Column
     */
    protected function requiredColumn($name, $type, $alias = null, $default = null)
    {
        return new Column($this->driver, $this, $name, $this->types->getType($type), $alias, true, $default, false);
    }

    /**
     * @param string      $name
     * @param string      $type Type class-name
     * @param string|null $alias
     * @param mixed       $default
     *
     * @return Column
     */
    protected function optionalColumn($name, $type, $alias = null, $default = null)
    {
        return new Column($this->driver, $this, $name, $this->types->getType($type), $alias, false, $default, false);
    }

    /**
     * @param string      $name
     * @param string      $type Type class-name
     * @param string|null $alias
     *
     * @return Column
     */
    protected function autoColumn($name, $type, $alias = null)
    {
        return new Column($this->driver, $this, $name, $this->types->getType($type), $alias, false, null, true);
    }

    /**
     * @ignore
     *
     * @return string
     */
    public function __toString()
    {
        return $this->alias
            ? $this->driver->quoteName($this->alias)
            : $this->driver->quoteTableName($this->schema->getName(), $this->name);
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
