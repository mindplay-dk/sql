<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\Driver;
use mindplay\sql\framework\TypeProvider;

/**
 * This class represents a DELETE query.
 */
class DeleteQuery extends ProjectionQuery
{
    public function buildQuery()
    {
        $delete = "DELETE FROM " . $this->buildNodes();

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

        return "{$delete}{$where}{$order}{$limit}";
    }
}
