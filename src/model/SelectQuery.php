<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\Countable;
use mindplay\sql\framework\Driver;
use mindplay\sql\framework\Statement;
use mindplay\sql\framework\MapperProvider;
use mindplay\sql\framework\TypeProvider;
use mindplay\sql\model\components\Mappers;
use mindplay\sql\model\components\ReturnVars;
use mindplay\sql\types\IntType;

/**
 * This class represents a SELECT query.
 *
 * This class implements `__toString()` magic, enabling the use of this query builder
 * in the SELECT, WHERE or ORDER BY clause of a parent SELECT (or other type of) query.
 *
 * Note that, when constructing nested queries, parameters must be bound against the
 * parent query - binding parameters or applying Mappers against a nested query has no effect.
 */
class SelectQuery extends ProjectionQuery implements MapperProvider, Countable
{
    use Mappers;

    /**
     * @var ReturnVars
     */
    protected $return_vars;

    /**
     * @var string[] list of GROUP BY expressions
     */
    protected $group_by = [];

    /**
     * @var string[] list of HAVING expressions
     */
    protected $having = [];

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
     * Add an expression to apply to a GROUP BY clause
     *
     * @param Column|string $expr SQL expression (or Column object) to apply to the GROUP BY clause
     *
     * @return $this
     */
    public function groupBy($expr)
    {
        $this->group_by[] = (string) $expr;

        return $this;
    }

    /**
     * @param string|string[] $exprs one or more condition expressions to apply to the HAVING clause
     *
     * @return $this
     */
    public function having($exprs)
    {
        foreach ((array) $exprs as $expr) {
            $this->having[] = $expr;
        }

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
     * @inheritdoc
     */
    public function getSQL()
    {
        $select = "SELECT " . $this->return_vars->buildReturnVars();

        $from = "\nFROM " . $this->buildNodes();

        $where = count($this->conditions)
            ? "\nWHERE " . $this->buildConditions()
            : ''; // no conditions present
        
        $group_by = count($this->group_by)
            ? "\nGROUP BY " . implode(", ", $this->group_by)
            : ""; // no group-by expressions

        $having = count($this->having)
            ? "\nHAVING " . $this->buildHaving()
            : ''; // no having clause present
        
        $order = count($this->order)
            ? "\nORDER BY " . $this->buildOrderTerms()
            : ''; // no order terms

        $limit = $this->limit !== null
            ? "\nLIMIT {$this->limit}"
            . ($this->offset !== null ? " OFFSET {$this->offset}" : '')
            : ''; // no limit or offset

        return "{$select}{$from}{$where}{$group_by}{$having}{$order}{$limit}";
    }

    /**
     * @inheritdoc
     */
    public function createCountStatement()
    {
        $query = clone $this;

        $query->return_vars = new ReturnVars($this->root, $this->driver, $this->types);

        $query->return_vars->addValue("COUNT(*)", "count", IntType::class);
        
        $query->limit = null;
        $query->offset = null;
        
        $query->order = [];
        
        return $query;
    }

    /**
     * @ignore string magic (enables creation of nested SELECT queries)
     */
    public function __toString()
    {
        return "(" . $this->getSQL() . ")";
    }

    /**
     * @return string combined condition expression (for use in the WHERE clause of an SQL statement)
     */
    protected function buildHaving()
    {
        return implode(" AND ", $this->having);
    }
}
