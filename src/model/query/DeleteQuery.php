<?php

namespace mindplay\sql\model\query;

use mindplay\sql\model\components\Conditions;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\TypeProvider;

/**
 * This class represents a DELETE query.
 */
class DeleteQuery extends Query
{
    use Conditions;

    /**
     * @var Table Table from which to DELETE
     */
    protected $table;

    /**
     * @param Table        $table
     * @param TypeProvider $types
     */
    public function __construct(Table $table, TypeProvider $types)
    {
        parent::__construct($types);

        $this->table = $table;
    }

    public function getSQL()
    {
        $delete = "DELETE FROM " . $this->table->getNode();

        $where = count($this->conditions)
            ? "\nWHERE " . $this->buildConditions()
            : ''; // no conditions present

        // TODO move ORDER BY and LIMIT support to MySQL-specific UPDATE and DELETE query-builders

//        $order = count($this->order)
//            ? "\nORDER BY " . $this->buildOrderTerms()
//            : ''; // no order terms
//
//        $limit = $this->limit !== null
//            ? "\nLIMIT {$this->limit}"
//            . ($this->offset !== null ? " OFFSET {$this->offset}" : '')
//            : ''; // no limit or offset

        // return "{$delete}{$where}{$order}{$limit}";

        return "{$delete}{$where}";
    }
}
