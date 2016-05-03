<?php

namespace mindplay\sql\model;

use InvalidArgumentException;
use mindplay\sql\framework\Driver;
use mindplay\sql\framework\Query;
use mindplay\sql\framework\TypeProvider;

/**
 * Abstract base class for Query types involving projections, e.g. `INSERT`, `SELECT` or `UPDATE`.
 *
 * By "projection", we mean a projection of certain tables, possibly `JOIN`s, conditions, order
 * and limit, all of which affect the scope and order of the set of data the query operates on.
 */
abstract class ProjectionQuery extends Query
{
    /**
     * @var Driver
     */
    protected $driver;

    /**
     * @var Table root Table of this query (from which JOIN clauses may extend the projection)
     */
    protected $root;

    /**
     * @var string[] list of JOIN clauses extending from the root Table of this query
     */
    protected $joins = [];

    /**
     * @var string[] list of condition expressions to apply to the WHERE clause
     */
    protected $conditions = [];

    /**
     * @var string[] list of ORDER BY terms
     */
    protected $order = [];

    /**
     * @var int|null
     */
    protected $offset;

    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @param Table        $root
     * @param Driver       $driver
     * @param TypeProvider $types
     */
    public function __construct(Table $root, Driver $driver, TypeProvider $types)
    {
        parent::__construct($types);

        $this->driver = $driver;
        $this->root = $root;
    }

    /**
     * @param string|string[] $exprs one or more condition expressions to apply to the WHERE clause
     *
     * @return $this
     */
    public function where($exprs)
    {
        foreach ((array) $exprs as $expr) {
            $this->conditions[] = $expr;
        }

        return $this;
    }

    /**
     * @param string $expr order-by expression (which may include a trailing "ASC" or "DESC" modifier)
     * 
     * @return $this
     */
    public function order($expr)
    {
        $this->order[] = "{$expr}";
        
        return $this;
    }
    
    /**
     * @param Table  $table
     * @param string $expr join condition
     *
     * @return $this
     */
    public function innerJoin(Table $table, $expr)
    {
        return $this->join("INNER", $table, $expr);
    }

    /**
     * @param Table  $table
     * @param string $expr join condition
     *
     * @return $this
     */
    public function leftJoin(Table $table, $expr)
    {
        return $this->join("LEFT", $table, $expr);
    }

    /**
     * @param Table  $table
     * @param string $expr join condition
     *
     * @return $this
     */
    public function rightJoin(Table $table, $expr)
    {
        return $this->join("RIGHT", $table, $expr);
    }

    /**
     * @param int      $limit  max. number of records
     * @param int|null $offset base-0 record number offset
     *
     * @return $this
     *
     * @throws InvalidArgumentException if the given limit is less than 1, or if the given offset if less than 0
     */
    public function limit($limit, $offset = null)
    {
        if ($limit < 1) {
            throw new InvalidArgumentException("limit out of range: {$limit}");
        }

        if ($offset < 0) {
            throw new InvalidArgumentException("offset out of range: {$offset}");
        }

        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param int $page_num  base-1 page number
     * @param int $page_size number of records per page
     *
     * @return $this
     *
     * @throws InvalidArgumentException if the given page number or page size are less than 1
     */
    public function page($page_num, $page_size)
    {
        if ($page_size < 1) {
            throw new InvalidArgumentException("page size out of range: {$page_size}");
        }

        if ($page_num < 1) {
            throw new InvalidArgumentException("page number out of range: {$page_num}");
        }

        return $this->limit($page_size, ($page_num - 1) * $page_size);
    }

    /**
     * @param string $type join type ("INNER", "LEFT", etc.)
     * @param Table  $table
     * @param string $expr join condition
     *
     * @return $this
     */
    protected function join($type, Table $table, $expr)
    {
        $table_expr = $this->buildNode($table);

        $this->joins[] = "{$type} JOIN {$table_expr} ON {$expr}";

        return $this;
    }

    /**
     * @param Table $table
     *
     * @return string table expression (e.g. "{table} AS {alias}" for use in the FROM clause of an SQL statement)
     */
    protected function buildNode(Table $table)
    {
        $alias = $table->getAlias();

        return $alias
            ? $this->driver->quoteName($table->getName()) . ' AS ' . $this->driver->quoteName($alias)
            : $this->driver->quoteName($table->getName());
    }

    /**
     * @return string root table expression and JOIN clauses (for use in the FROM clause of an SQL statement)
     */
    protected function buildNodes()
    {
        return implode("\n", array_merge([$this->buildNode($this->root)], $this->joins));
    }

    /**
     * @return string combined condition expression (for use in the WHERE clause of an SQL statement)
     */
    protected function buildConditions()
    {
        return implode(" AND ", $this->conditions);
    }
    
    /**
     * @return string order terms (for use in the ORDER BY clause of an SQL statement)
     */
    protected function buildOrderTerms()
    {
        return implode(', ', $this->order);
    }

    /**
     * @return string the LIMIT/OFFSET clause (or an empty string, if no limit has been set)
     */
    protected function buildLimit()
    {
        return $this->limit !== null
            ? "\nLIMIT {$this->limit}" . ($this->offset !== null ? " OFFSET {$this->offset}" : '')
            : ''; // no limit or offset
    }
}
