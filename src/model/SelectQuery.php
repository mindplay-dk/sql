<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\Driver;
use mindplay\sql\framework\MapperProvider;
use mindplay\sql\framework\TypeProvider;
use mindplay\sql\model\components\Mappers;
use mindplay\sql\model\components\ReturnVars;

/**
 * This class represents a SELECT query.
 *
 * This class implements `__toString()` magic, enabling the use of this query builder
 * in the SELECT, WHERE or ORDER BY clause of a parent SELECT (or other type of) query.
 *
 * Note that, when constructing nested queries, parameters must be bound against the
 * parent query - binding parameters or applying Mappers against a nested query has no effect.
 */
class SelectQuery extends ProjectionQuery implements MapperProvider
{
    use Mappers;

    /**
     * @var ReturnVars
     */
    private $return_vars;
    
    /**
     * @param Table        $root
     * @param Driver       $driver
     * @param TypeProvider $types
     */
    public function __construct(Table $root, Driver $driver, TypeProvider $types)
    {
        parent::__construct($root, $driver, $types);
        
        $this->return_vars = new ReturnVars($root, $driver, $types);
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
        $this->return_vars->addTable($table);

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
    public function value($expr, $name = null, $type = null)
    {
        $this->return_vars->addValue($expr, $name, $type);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMappers()
    {
        return array_merge([$this->return_vars->createTypeMapper()], $this->mappers);
    }

    /**
     * @return string comma-separated return expressions (for use in the SELECT or RETURNING clause of an SQL query)
     */
    protected function buildReturnVars()
    {
        return $this->return_vars->buildReturnVars();
    }
    
    /**
     * @inheritdoc
     */
    public function getSQL()
    {
        $select = "SELECT " . $this->buildReturnVars();

        $from = "\nFROM " . $this->buildNodes();

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

        return "{$select}{$from}{$where}{$order}{$limit}";
    }

    /**
     * @ignore string magic (enables creation of nested SELECT queries)
     */
    public function __toString()
    {
        return "(" . $this->getSQL() . ")";
    }
}
