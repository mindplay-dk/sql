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

    protected Table $table;

    /**
     * @param $table Table from which to DELETE
     * @param $types
     */
    public function __construct(Table $table, TypeProvider $types)
    {
        parent::__construct($types);

        $this->table = $table;
    }

    public function getSQL(): string
    {
        $delete = "DELETE FROM " . $this->table->getNode();

        $where = count($this->conditions)
            ? "\nWHERE " . $this->buildConditions()
            : ''; // no conditions present

        return "{$delete}{$where}";
    }
}
