<?php

namespace mindplay\sql\model;

use mindplay\sql\model\query\SQLQuery;
use mindplay\sql\model\schema\Schema;
use UnexpectedValueException;

/**
 * This class implements the primary public API of the database model.
 */
abstract class Database
{
    /**
     * @var DatabaseContainer
     */
    protected $container;

    /**
     * The typical use-case is to omit the `$factory` argument - it exists primarily for
     * mocking and dependency-injection under test.
     *
     * @param DatabaseContainerFactory|null $factory custom factory instance (typically omitted)
     */
    public function __construct(DatabaseContainerFactory $factory = null)
    {
        if ($factory === null) {
            $factory = new DatabaseContainerFactory();
        }

        $this->bootstrap($factory);

        $this->container = $factory->createContainer();
    }

    /**
     * @return DatabaseContainer
     */
    abstract protected function bootstrap(DatabaseContainerFactory $factory);

    /**
     * @param string Schema class-name
     *
     * @return Schema
     */
    public function getSchema($schema)
    {
        return $this->container->getSchema($schema);
    }
    
    /**
     * @param string $sql
     * 
     * @return SQLQuery
     */
    public function sql($sql)
    {
        return $this->container->create(SQLQuery::class, ['sql' => $sql]);
    }
}
