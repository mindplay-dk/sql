<?php

namespace mindplay\sql\mysql;

use mindplay\sql\model\Database;
use mindplay\sql\model\DatabaseContainer;
use mindplay\sql\model\Driver;
use mindplay\sql\model\query\DeleteQuery;
use mindplay\sql\model\query\InsertQuery;
use mindplay\sql\model\query\UpdateQuery;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\types\BoolType;
use mindplay\sql\model\types\FloatType;
use PDO;

class MySQLDatabase extends Database implements Driver
{
    protected function bootstrap(DatabaseContainer $container)
    {
        $container->set(Driver::class, $this);

        $container->register(BoolType::class, function () {
            return BoolType::get(1, 0);
        });

        $container->alias("scalar.boolean", BoolType::class);

        $container->register("scalar.double", FloatType::class);
    }
    
    /**
     * @param PDO $pdo
     * 
     * @return MySQLConnection
     */
    public function createConnection(PDO $pdo)
    {
        return $this->container->create(MySQLConnection::class, ['pdo' => $pdo]);
    }

    /**
     * @inheritdoc
     */
    public function quoteName($name)
    {
        return '`' . $name . '`';
    }
    
    /**
     * @param Table $from
     *
     * @return MySQLSelectQuery
     */
    public function select(Table $from)
    {
        return $this->container->create(MySQLSelectQuery::class, ['root' => $from]);
    }

    /**
     * @param Table $into
     *
     * @return InsertQuery
     */
    public function insert(Table $into)
    {
        return $this->container->create(InsertQuery::class, ['table' => $into]);
    }

    /**
     * @param Table $table
     *
     * @return UpdateQuery
     */
    public function update(Table $table)
    {
        return $this->container->create(UpdateQuery::class, ['root' => $table]);
    }

    /**
     * @param Table $table
     *
     * @return DeleteQuery
     */
    public function delete(Table $table)
    {
        return $this->container->create(DeleteQuery::class, ['root' => $table]);
    }
}
