<?php

namespace mindplay\sql\model\query;

use mindplay\sql\model\components\Conditions;
use mindplay\sql\model\Driver;
use mindplay\sql\model\schema\Column;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\TypeProvider;

/**
 * This class represents an UPDATE query.
 */
class UpdateQuery extends Query
{
    use Conditions;

    protected Table $table;
    protected Driver $driver;

    /**
     * @var array<string,string> map where Column name => literal SQL expression to assign
     */
    private $assignments = [];

    /**
     * @param Table        $table
     * @param TypeProvider $types
     */
    public function __construct(Table $table, Driver $driver, TypeProvider $types)
    {
        parent::__construct($types);

        $this->table = $table;
        $this->driver = $driver;
    }

    /**
     * @param Column|string $column Column to update (or Column name)
     * @param mixed         $value  value to apply
     *
     * @return $this
     */
    public function setValue(Column|string $column, mixed $value): static
    {
        // TODO qualify table-references to support UPDATE queries with JOINs

        if ($column instanceof Column) {
            $name = $this->getPlaceholder($column);
            $type = $column->getType();

            $quoted_name = $this->driver->quoteName($column->getName());
        } else {
            $name = $column;
            $type = null;

            $quoted_name = $this->driver->quoteName($name);
        }

        $this->assignments[$name] = "{$quoted_name} = :{$name}";

        $this->bind($name, $value, $type);

        return $this;
    }

    /**
     * @param Column|string $column Column to update (or Column name)
     * @param string        $expr   literal SQL expression
     *
     * @return $this
     */
    public function setExpr(Column|string $column, string $expr): static
    {
        // TODO qualify table-references to support UPDATE queries with JOINs

        if ($column instanceof Column) {
            $name = $this->getPlaceholder($column);

            $quoted_name = $this->driver->quoteName($column->getName());
        } else {
            $name = $column;

            $quoted_name = $this->driver->quoteName($name);
        }

        $this->assignments[$name] = "{$quoted_name} = {$expr}";

        return $this;
    }

    /**
     * @param array<string,int|string|bool|null> $values map where Column name => scalar values to assign
     *
     * @return $this
     */
    public function assign(array $values)
    {
        $columns = $this->table->listColumns();

        foreach ($columns as $column) {
            $name = $column->getName();

            if (array_key_exists($name, $values)) {
                $this->setValue($column, $values[$name]);
            }
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSQL(): string
    {
        $update = "UPDATE " . $this->table->getNode();

        $set = "\nSET " . implode(",\n    ", $this->assignments);

        $where = count($this->conditions)
            ? "\nWHERE " . $this->buildConditions()
            : ''; // no conditions present

        return "{$update}{$set}{$where}";
    }

    /**
     * @param Column $column
     *
     * @return string
     */
    private function getPlaceholder(Column $column): string
    {
        $table = $column->getTable();

        $table_name = $table->getAlias() ?: $table->getName();

        $column_name = $column->getName();

        $name = "{$table_name}_{$column_name}";

        return $name;
    }
}
