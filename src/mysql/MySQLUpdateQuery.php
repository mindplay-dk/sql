<?php

namespace mindplay\sql\mysql;

use mindplay\sql\model\components\Limit;
use mindplay\sql\model\components\Order;
use mindplay\sql\model\query\UpdateQuery;

class MySQLUpdateQuery extends UpdateQuery
{
    use Order;
    use Limit;

    public function getSQL()
    {
        $update = parent::getSQL();

        $order = count($this->order)
            ? "\nORDER BY " . $this->buildOrderTerms()
            : ''; // no order terms

        $limit = $this->limit !== null
            ? "\nLIMIT {$this->limit}"
            : ''; // no limit

        return "{$update}{$order}{$limit}";
    }
}
