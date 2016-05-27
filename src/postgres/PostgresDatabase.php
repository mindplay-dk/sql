<?php

namespace mindplay\sql\postgres;

use mindplay\sql\model\Database;
use mindplay\sql\model\DatabaseContainer;
use mindplay\sql\model\Driver;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\types\BoolType;
use mindplay\sql\model\types\FloatType;
use PDO;

class PostgresDatabase extends Database implements Driver
{
    protected function bootstrap(DatabaseContainer $container)
    {
        $container->set(Driver::class, $this);
        
        $container->register(BoolType::class, function () {
            return BoolType::get(true, false);
        });
        
        $container->alias("scalar.boolean", BoolType::class);

        $container->register("scalar.double", FloatType::class);
    }

    /**
     * @param PDO $pdo
     *
     * @return PostgresConnection
     */
    public function createConnection(PDO $pdo)
    {
        return $this->container->create(PostgresConnection::class, ['pdo' => $pdo]);
    }

    /**
     * @inheritdoc
     */
    public function quoteName($name)
    {
        return '"' . $name . '"';
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
        return $this->container->create(PostgresUpdateQuery::class, ['table' => $table]);
    }

    /**
     * @param Table $table
     *
     * @return PostgresDeleteQuery
     */
    public function delete(Table $table)
    {
        return $this->container->create(PostgresDeleteQuery::class, ['table' => $table]);
    }
}
