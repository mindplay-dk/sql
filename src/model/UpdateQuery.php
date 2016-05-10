<?php

namespace mindplay\sql\model;

/**
 * This class represents an UPDATE query.
 */
class UpdateQuery extends ProjectionQuery
{
    /**
     * @var mixed[] map where Column name => literal SQL expression to assign
     */
    private $assignments = [];

    /**
     * @param Column|string $column Column to update (or Column name)
     * @param mixed         $value  value to apply
     *
     * @return $this
     */
    public function setValue($column, $value)
    {
        if ($column instanceof Column) {
            $name = $this->getPlaceholder($column);

            $quoted_name = $this->driver->quoteName($column->getName());
        } else {
            $name = $column;

            $quoted_name = $this->driver->quoteName($name);
        }

        $this->assignments[$name] = "{$quoted_name} = :{$name}";

        $this->bind($name, $value, $column->getType());

        return $this;
    }

    /**
     * @param Column|string $column Column to update (or Column name)
     * @param string        $expr   literal SQL expression
     *
     * @return $this
     */
    public function setExpr($column, $expr)
    {
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
     * @param array $values map where Column name => scalar values to assign
     *
     * @return $this
     */
    public function assign(array $values)
    {
        $columns = $this->root->listColumns();

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
    public function getSQL()
    {
        $update = "UPDATE " . $this->buildNodes();

        $set = "\nSET " . implode(",\n    ", $this->assignments);

        $where = count($this->conditions)
            ? "\nWHERE " . $this->buildConditions()
            : ''; // no conditions present

        $order = count($this->order)
            ? "\nORDER BY " . $this->buildOrderTerms()
            : ''; // no order terms

        $limit = $this->limit !== null
            ? "\nLIMIT {$this->limit}"
            . ($this->offset !== null ? " OFFSET {$this->offset}" : '')
            : ''; // no limit or offset

        return "{$update}{$set}{$where}{$order}{$limit}";
    }

    /**
     * @param Column $column
     *
     * @return string
     */
    private function getPlaceholder(Column $column)
    {
        $table = $column->getTable();

        $table_name = $table->getAlias() ?: $table->getName();

        $column_name = $column->getName();

        $name = "{$table_name}_{$column_name}";

        return $name;
    }
}
