<?php

namespace mindplay\sql\model\components;

use mindplay\sql\model\schema\Column;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\schema\Type;

/**
 * This trait implements support for the `RETURNING` clause of Postgres `INSERT`, `UPDATE` and `DELETE` queries.
 */
trait Returning
{
    use Mappers;

    protected ReturnVars $return_vars;
    
    /**
     * Add all the Columns of a full Table to be selected and returned
     *
     * @param Table $table Table to select and return
     *
     * @return $this
     */
    public function returningTable(Table $table): static
    {
        $this->return_vars->addTable($table);

        return $this;
    }

    /**
     * Add one or more Columns to select and return
     *
     * @param Column|Column[] $cols one or more Columns to select and return
     *
     * @return $this
     */
    public function returningColumns($cols): static
    {
        $this->return_vars->addColumns($cols);

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
    public function returningValue(string $expr, string|null $name = null, Type|string|null $type = null): static
    {
        $this->return_vars->addValue($expr, $name, $type);

        return $this;
    }

    /**
     * @see MapperProvider::getMappers()
     */
    public function getMappers(): array
    {
        return array_merge([$this->return_vars->createTypeMapper()], $this->mappers);
    }
}
