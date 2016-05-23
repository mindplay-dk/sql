<?php

namespace mindplay\sql\postgres;

use mindplay\sql\model\Database;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\types\BoolType;
use mindplay\sql\model\types\FloatType;

class PostgresDatabase extends Database
{
    public function __construct()
    {
        parent::__construct();

        $this->container->register(BoolType::class, function () {
            return BoolType::get(true, false);
        });
        
        $this->container->alias("scalar.boolean", BoolType::class);

        $this->container->register("scalar.double", FloatType::class);
    }

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
