<?php

namespace mindplay\sql\model\schema;

use mindplay\sql\model\Driver;

/**
 * This class represents a Column belonging to a Table.
 */
class Column
{
    private Table $table;
    private Driver $driver;
    private Type $type;
    private string $name;
    private string|null $alias;
    private bool $required;
    private mixed $default;
    private bool $auto;

    /**
     * @param $table parent Table instance
     */
    public function __construct(Driver $driver, Table $table, string $name, Type $type, string|null $alias, bool $required, mixed $default, bool $auto)
    {
        $this->table = $table;
        $this->driver = $driver;
        $this->type = $type;
        $this->name = $name;
        $this->alias = $alias;
        $this->required = $required;
        $this->default = $default;
        $this->auto = $auto;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getType(): Type
    {
        return $this->type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlias(): string|null
    {
        return $this->alias;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function isAuto(): bool
    {
        return $this->auto;
    }

    public function __toString(): string
    {
        return $this->table->__toString() . '.' . $this->driver->quoteName($this->name);
    }
}
