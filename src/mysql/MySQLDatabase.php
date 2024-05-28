<?php

namespace mindplay\sql\mysql;

use mindplay\sql\model\Database;
use mindplay\sql\model\DatabaseContainerFactory;
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
    protected function bootstrap(DatabaseContainerFactory $factory): void
    {
        $factory->set(Driver::class, $this);

        $factory->register(BoolType::class, function () {
            return BoolType::get(1, 0);
        });

        $factory->alias("scalar.boolean", BoolType::class);

        $factory->register("scalar.double", FloatType::class);
    }
    
    public function createConnection(PDO $pdo): MySQLConnection
    {
        return $this->container->create(MySQLConnection::class, ['pdo' => $pdo]);
    }

    /**
     * @inheritdoc
     */
    public function quoteName(string $name): string
    {
        return '`' . $name . '`';
    }

    /**
     * @inheritdoc
     */
    public function quoteTableName(string|null $schema, string $table): string
    {
        return $schema
            ? "`{$schema}_{$table}`"
            : "`{$table}`";
    }

    public function select(Table $from): MySQLSelectQuery
    {
        return $this->container->create(MySQLSelectQuery::class, [Table::class => $from]);
    }

    public function insert(Table $into): InsertQuery
    {
        return $this->container->create(InsertQuery::class, [Table::class => $into]);
    }

    public function update(Table $table): UpdateQuery
    {
        return $this->container->create(MySQLUpdateQuery::class, [Table::class => $table]);
    }

    public function delete(Table $table): DeleteQuery
    {
        return $this->container->create(MySQLDeleteQuery::class, [Table::class => $table]);
    }
}
