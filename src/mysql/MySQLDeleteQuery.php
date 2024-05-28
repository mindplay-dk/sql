<?php

namespace mindplay\sql\mysql;

use mindplay\sql\model\components\Limit;
use mindplay\sql\model\components\Order;
use mindplay\sql\model\query\DeleteQuery;

class MySQLDeleteQuery extends DeleteQuery
{
    use Order;
    use Limit;

    public function getSQL(): string
    {
        $delete = parent::getSQL();

        $order = count($this->order)
            ? "\nORDER BY " . $this->buildOrderTerms()
            : ''; // no order terms

        $limit = $this->limit !== null
            ? "\nLIMIT {$this->limit}"
            : ''; // no limit

        return "{$delete}{$order}{$limit}";
    }
}
