<?php

namespace mindplay\sql\model;

class SelectQuery extends ReturningQuery
{
    /**
     * @inheritdoc
     */
    protected function buildQuery()
    {
        $select = "SELECT " . $this->buildReturnVars();

        $from = "\nFROM " . $this->buildNodes();

        $where = count($this->where)
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
}
