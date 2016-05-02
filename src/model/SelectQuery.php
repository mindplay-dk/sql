<?php

namespace mindplay\sql\model;

/**
 * This class represents a SELECT query.
 *
 * This class implements `__toString()` magic, enabling the use of this query builder
 * in the SELECT, WHERE or ORDER BY clause of a parent SELECT (or other type of) query.
 *
 * Note that, when constructing nested queries, parameters must be bound against the
 * parent query - binding parameters or applying Mappers against a nested query has no effect.
 */
class SelectQuery extends ReturningQuery
{
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
