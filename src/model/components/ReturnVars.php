<?php

namespace mindplay\sql\model\components;

use mindplay\sql\model\Driver;
use mindplay\sql\model\schema\Column;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\schema\Type;
use mindplay\sql\model\TypeProvider;
use OutOfBoundsException;
use UnexpectedValueException;

/**
 * This component implements support for return variable expressions (for use in a SELECT or RETURNING clause)
 */
class ReturnVars
{
    /**
     * @var string[] list of return variable expressions (for use in a SELECT or RETURNING clause)
     */
    private array $vars = [];

    /**
     * @var Type[] map where return variable name maps to Type
     */
    private array $type_map = [];

    private Table $root;

    private Driver $driver;

    private TypeProvider $types;
    
    public function __construct(Table $root, Driver $driver, TypeProvider $types)
    {
        $this->root = $root;
        $this->driver = $driver;
        $this->types = $types;
    }

    /**
     * Add all the Columns of a full Table to be selected and returned
     *
     * @param Table $table Table to select and return
     */
    public function addTable(Table $table): void
    {
        $this->vars[] = "{$table}.*";

        $this->type_map = array_merge($this->type_map, $this->createTypeMap($table));
    }

    /**
     * Add one or more Columns to select and return
     *
     * @param Column|Column[] $cols one or more Columns to select and return
     */
    public function addColumns(Column|array $cols): void
    {
        /**
         * @var Column[] $cols
         */

        $cols = is_array($cols) ? $cols : [$cols];

        foreach ($cols as $col) {
            $alias = $col->getAlias();

            $col_name = $alias ?: $col->getName();

            $table = $col->getTable();

            $quoted_table_name = $table->getAlias()
                ? $this->driver->quoteName($table->getAlias())
                : $this->driver->quoteTableName($table->getSchema()->getName(), $table->getName());

            $column_expr = $quoted_table_name . '.' . $this->driver->quoteName($col->getName());

            $this->vars[$col_name] = $alias
                ? "{$column_expr} AS " . $this->driver->quoteName($col_name)
                : "{$column_expr}";

            $this->type_map[$col_name] = $col->getType();
        }
    }

    /**
     * Add an SQL expression to select and return
     *
     * @param $expr return expression
     * @param $name return variable name (optional, but usually required)
     * @param $type optional Type (or Type class-name)
     */
    public function addValue(string $expr, string|null $name = null, Type|String|null $type = null): void
    {
        if (isset($this->vars[$name])) {
            throw new OutOfBoundsException("duplicate return variable name: {$name}");
        }

        if ($name === null) {
            if ($type !== null) {
                throw new UnexpectedValueException("type conversion requires a return-variable name");
            }

            $this->vars[] = "{$expr}";
        } else {
            $quoted_name = $this->driver->quoteName($name);

            $this->vars[$name] = "{$expr} AS {$quoted_name}";

            if ($type !== null) {
                $this->type_map[$name] = is_string($type)
                    ? $this->types->getType($type)
                    : $type; // assumes Type instance
            }
        }
    }

    public function createTypeMapper(): TypeMapper
    {
        $type_map = $this->type_map;

        if (count($this->vars) === 0) {
            // no defined return vars - buildReturnVars() will auto-select the root node, so
            // we need to add root Column Types to the TypeMapper we're creating here:

            $type_map = array_merge($this->createTypeMap($this->root), $type_map);
        }

        return new TypeMapper($type_map);
    }

    /**
     * @return bool true, if any return vars have been added
     */
    public function hasReturnVars(): bool
    {
        return count($this->vars) > 0;
    }
    
    /**
     * @return string comma-separated return expressions (for use in the SELECT or RETURNING clause of an SQL query)
     */
    public function buildReturnVars(): string
    {
        $vars = $this->vars;

        if (count($vars) === 0) {
            // no defined return vars - createTypeMapper() will create a Type-map for the root node,
            // so we need to auto-select the root node here:

            $vars[] = "{$this->root}.*";
        }

        return implode(",\n  ", $vars);
    }

    /**
     * Internally creates a full Type-map for all Columns in a given Table
     *
     * @param Table $table
     *
     * @return array<string,Type> map where Column Alias maps to Type
     */
    private function createTypeMap(Table $table): array
    {
        $type_map = [];

        foreach ($table->listColumns() as $column) {
            $type_map[$column->getAlias() ?: $column->getName()] = $column->getType();
        }

        return $type_map;
    }
}
