<?php

namespace mindplay\sql\postgres;

use mindplay\sql\framework\Database;
use mindplay\sql\model\Table;

class PostgresDatabase extends Database
{
    /**
     * @param Table $from
     *
     * @return PostgresSelectQuery
     */
    public function select(Table $from)
    {
        return $this->container->create(PostgresSelectQuery::class, ['root' => $from]);
    }
    
    /**
     * @param Table $into
     *
     * @return PostgresInsertQuery
     */
    public function insert(Table $into)
    {
        return $this->container->create(PostgresInsertQuery::class, ['table' => $into]);
    }

    /**
     * @param Table $table
     *
     * @return PostgresUpdateQuery
     */
    public function update(Table $table)
    {
        return $this->container->create(PostgresUpdateQuery::class, ['root' => $table]);
    }

    /**
     * @param Table $table
     *
     * @return PostgresDeleteQuery
     */
    public function delete(Table $table)
    {
        return $this->container->create(PostgresDeleteQuery::class, ['root' => $table]);
    }
    
    protected function createDriver()
    {
        return new PostgresDriver();
    }
}
