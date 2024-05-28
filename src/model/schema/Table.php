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
    private Schema $schema;
    private Driver $driver;
    private TypeProvider $types;
    private string $name;
    private string|null $alias;

    public function __construct(Schema $schema, Driver $driver, TypeProvider $types, string $name, string|null $alias)
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
    public function getSchema(): Schema
    {
        return $this->schema;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): string|null
    {
        return $this->alias;
    }

    /**
     * @return string table expression (e.g. "{table} AS {alias}" for use in the FROM clause of an SQL statement)
     */
    public function getNode(): string
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
    public function listColumns(string|null $prefix = null): array
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
     * Creates a required Column.
     *
     * A value *must* be specified when building an `INSERT` query - if you don't specify a value
     * for this Column, the INSERT query-builder will throw an exception.
     */
    protected function requiredColumn(string $name, string $type, string|null $alias = null): Column
    {
        return new Column($this->driver, $this, $name, $this->types->getType($type), $alias, true, null, false);
    }

    /**
     * Creates an optional Column.
     *
     * A value is optional (and may have a `$default`) when building an `INSERT` query - if you don't
     * specify a value for this Column, the INSERT query-builder will automatically assign the `$default`.
     *
     * @param $name    Column name
     * @param $type    Type class-name
     * @param $alias   Optional alias
     * @param $default Optional default PHP value (Type-conversion will be applied.)
     *
     * @return Column
     */
    protected function optionalColumn(string $name, string $type, string|null $alias = null, mixed $default = null): Column
    {
        return new Column($this->driver, $this, $name, $this->types->getType($type), $alias, false, $default, false);
    }

    /**
     * Creates an auto-defined Column.
     *
     * A value should *not* by specified when building an `INSERT` query.
     *
     * Use this for Columns that the database itself will populate, e.g. auto-incrementing keys or
     * columns that are otherwise initialized by the database itself.
     *
     * @param $name  Column name
     * @param $type  Type class-name
     * @param $alias Optional alias
     */
    protected function autoColumn(string $name, string $type, string|null $alias = null): Column
    {
        return new Column($this->driver, $this, $name, $this->types->getType($type), $alias, false, null, true);
    }

    /**
     * @ignore
     *
     * @return string
     */
    public function __toString(): string
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
