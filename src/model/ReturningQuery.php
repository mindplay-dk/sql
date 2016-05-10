<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\Driver;
use mindplay\sql\framework\TypeProvider;
use OutOfBoundsException;

/**
 * Abstract base class for Query types that return and map results, such as `SELECT` or `UPDATE RETURNING`.
 */
abstract class ReturningQuery extends ProjectionQuery
{
    /**
     * @var string[] list of return variable expressions (for use in a SELECT or RETURNING clause)
     */
    protected $return_vars = [];

    /**
     * @var Type[] map where return variable name maps to Type
     */
    protected $type_map = [];
    
    /**
     * @param Table        $root
     * @param Driver       $driver
     * @param TypeProvider $types
     */
    public function __construct(Table $root, Driver $driver, TypeProvider $types)
    {
        parent::__construct($root, $driver, $types);
    }

    /**
     * Add all the Columns of a full Table to be selected and returned
     * 
     * @param Table $table Table to select and return
     *                     
     * @return $this
     */
    public function table(Table $table)
    {
        $this->return_vars[] = "{$table}.*";

        $this->type_map = array_merge($this->type_map, $this->createTypeMap($table));

        return $this;
    }
    
    /**
     * Add one or more Columns to select and return
     * 
     * @param Column|Column[] one or more Columns to select and return
     *
     * @return $this
     */
    public function columns($cols)
    {
        /**
         * @var Column[] $cols
         */
        
        $cols = is_array($cols) ? $cols : [$cols];
        
        foreach ($cols as $col) {
            $alias = $col->getAlias();

            $col_name = $alias ?: $col->getName();

            $table = $col->getTable();

            $table_name = $table->getAlias() ?: $table->getName();

            $column_expr = $this->driver->quoteName($table_name) . '.' . $this->driver->quoteName($col->getName());

            $this->return_vars[$col_name] = $alias
                ? "{$column_expr} AS " . $this->driver->quoteName($col_name)
                : "{$column_expr}";

            $this->type_map[$col_name] = $col->getType();
        }

        return $this;
    }

    /**
     * Add an SQL expression to select and return
     * 
     * @param string           $expr return expression
     * @param string|null      $name return variable name (optional, but usually required)
     * @param Type|string|null $type optional Type (or Type class-name)
     *
     * @return $this
     */
    public function value($expr, $name = null, $type = null)
    {
        if (isset($this->return_vars[$name])) {
            throw new OutOfBoundsException("duplicate return variable name: {$name}");
        }

        if ($name === null) {
            $this->return_vars[] = "{$expr}";
        } else {
            $quoted_name = $this->driver->quoteName($name);

            $this->return_vars[$name] = "{$expr} AS {$quoted_name}";

            if ($type !== null) {
                $this->type_map[$name] = is_string($type)
                    ? $this->types->getType($type)
                    : $type; // assumes Type instance
            }
        }

        return $this;
    }
    
    /**
     * @inheritdoc
     */
    public function getMappers()
    {
        $type_map = $this->type_map;

        if (count($this->return_vars) === 0) {
            // no defined return vars - buildReturnVars() will auto-select the root node, so
            // we need to add root Column Types to the TypeMapper we're creating here:

            $type_map = array_merge($this->createTypeMap($this->root), $type_map);
        }

        return array_merge([new TypeMapper($type_map)], parent::getMappers());
    }

    /**
     * @return string comma-separated return expressions (for use in the SELECT or RETURNING clause of an SQL query)
     */
    protected function buildReturnVars()
    {
        $return_vars = $this->return_vars;

        if (count($return_vars) === 0) {
            // no defined return vars - getMappers() will create a Type-map for the root node,
            // so we need to auto-select the root node here:

            $return_vars[] = "{$this->root}.*";
        }

        return implode(",\n  ", $return_vars);
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
