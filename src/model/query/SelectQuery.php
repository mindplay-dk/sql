<?php

namespace mindplay\sql\model\query;

use InvalidArgumentException;
use mindplay\sql\framework\Countable;
use mindplay\sql\framework\MapperProvider;
use mindplay\sql\model\components\Conditions;
use mindplay\sql\model\components\Mappers;
use mindplay\sql\model\components\ReturnVars;
use mindplay\sql\model\Driver;
use mindplay\sql\model\schema\Column;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\schema\Type;
use mindplay\sql\model\TypeProvider;
use mindplay\sql\model\types\IntType;

/**
 * This class represents a SELECT query.
 *
 * This class implements `__toString()` magic, enabling the use of this query builder
 * in the SELECT, WHERE or ORDER BY clause of a parent SELECT (or other type of) query.
 *
 * Note that, when constructing nested queries, parameters must be bound against the
 * parent query - binding parameters or applying Mappers against a nested query has no effect.
 */
class SelectQuery extends Query implements MapperProvider, Countable
{
    use Mappers;
    use Conditions;

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
     * @var bool[] map where flag => true
     */
    private $flags = [];

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

        $this->root = $root;
        $this->driver = $driver;
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
     * @internal do not call this method directly from client-code (see `Countable` interface)
     *
     * @ignore
     *
     * @see Connection::count()
     *
     * @return SelectQuery
     */
    public function createCountStatement()
    {
        $query = clone $this;

        $query->return_vars = new ReturnVars($this->root, $this->driver, $this->types);

        $query->return_vars->addValue("COUNT(*)", "count", IntType::class);

        $query->mappers = []; // remove existing mappers not applicable to the COUNT result

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
        return $this->addJoin("INNER", $table, $expr);
    }

    /**
     * @param Table  $table
     * @param string $expr join condition
     *
     * @return $this
     */
    public function leftJoin(Table $table, $expr)
    {
        return $this->addJoin("LEFT", $table, $expr);
    }

    /**
     * @param Table  $table
     * @param string $expr join condition
     *
     * @return $this
     */
    public function rightJoin(Table $table, $expr)
    {
        return $this->addJoin("RIGHT", $table, $expr);
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
     * @inheritdoc
     */
    public function getSQL()
    {
        $flags = $this->buildFlags();

        $select = "SELECT " . ($flags ? "{$flags} " : "")
            . $this->return_vars->buildReturnVars();

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
     * @param string $type join type ("INNER", "LEFT", etc.)
     * @param Table  $table
     * @param string $expr join condition
     *
     * @return $this
     */
    protected function addJoin($type, Table $table, $expr)
    {
        $table_expr = $table->getNode();

        $this->joins[] = "{$type} JOIN {$table_expr} ON {$expr}";

        return $this;
    }

    /**
     * @param string $flag
     * @param bool   $state
     */
    protected function setFlag($flag, $state = true)
    {
        if ($state) {
            $this->flags[$flag] = true;
        } else {
            unset($this->flags[$flag]);
        }
    }

    /**
     * @return string root table expression and JOIN clauses (for use in the FROM clause of an SQL statement)
     */
    protected function buildNodes()
    {
        return implode("\n", array_merge([$this->root->getNode()], $this->joins));
    }

    /**
     * @return string query flags (such as "SQL_CALC_FOUND_ROWS" in a MySQL SELECT query)
     */
    protected function buildFlags()
    {
        return implode(" ", array_keys($this->flags));
    }

    /**
     * @return string combined condition expression (for use in the WHERE clause of an SQL statement)
     */
    protected function buildHaving()
    {
        return implode(" AND ", $this->having);
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
