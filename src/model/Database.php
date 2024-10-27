<?php

namespace mindplay\sql\model;

use mindplay\sql\model\query\SQLQuery;
use mindplay\sql\model\schema\Schema;

/**
 * This class implements the primary public API of the database model.
 */
abstract class Database
{
    protected DatabaseContainer $container;

    /**
     * The typical use-case is to omit the `$factory` argument - it exists primarily for
     * mocking and dependency-injection under test.
     *
     * @param DatabaseContainerFactory|null $factory custom factory instance (typically omitted)
     */
    public function __construct(DatabaseContainerFactory|null $factory = null)
    {
        if ($factory === null) {
            $factory = new DatabaseContainerFactory();
        }

        $this->bootstrap($factory);

        $this->container = $factory->createContainer();
    }

    abstract protected function bootstrap(DatabaseContainerFactory $factory): void;

    /**
     * @param class-string $schema Schema class type-name
     */
    public function getSchema(string $schema): Schema
    {
        return $this->container->getSchema($schema);
    }
    
    public function sql(string $sql): SQLQuery
    {
        return $this->container->create(SQLQuery::class, ['sql' => $sql]);
    }
}
