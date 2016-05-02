<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\Executable;
use mindplay\sql\framework\Statement;
use mindplay\sql\framework\TypeProvider;

/**
 * Abstract base-class for all types of SQL query models.
 */
abstract class Query implements Executable
{
    /**
     * @var TypeProvider
     */
    protected $types;

    /**
     * @var array map where placeholder name => mixed value types
     */
    private $params = [];

    /**
     * @var Type[] map where placeholder name => Type instance
     */
    private $param_types = [];

    /**
     * @param TypeProvider $types
     */
    public function __construct(TypeProvider $types)
    {
        $this->types = $types;
    }

    /**
     * Bind an individual placeholder name to a given value.
     *
     * The `$type` argument is optional for scalar types (string, int, float, bool, null) and arrays of scalar values.
     * 
     * @param string           $name placeholder name
     * @param mixed            $value
     * @param Type|string|null $type Type instance, or Type class-name (or NULL for scalar types)
     *
     * @return $this
     */
    public function bind($name, $value, $type = null)
    {
        $this->params[$name] = $value;

        $this->param_types[$name] = is_string($type)
            ? $this->types->getType($type)
            : $type; // assumes Type instance (or NULL)

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getParams()
    {
        $params = [];

        foreach ($this->params as $name => $value) {
            $params[$name] = isset($this->param_types[$name])
                ? $this->param_types[$name]->convertToSQL($value)
                : $value; // assume scalar value (or array of scalar values)
        }

        return $params;
    }

    /**
     * Internally creates a full Type-map for all Columns in a given Table
     *
     * @param Table $table
     *
     * @return Type[] map where Column Alias maps to Type
     */
    protected function createTypeMap(Table $table)
    {
        $type_map = [];

        foreach ($table->listColumns() as $column) {
            $type_map[$column->getAlias() ?: $column->getName()] = $column->getType();
        }

        return $type_map;
    }
}
