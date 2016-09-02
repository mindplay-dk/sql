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
     * @param DatabaseContainer|null $factory
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

    /**
     * @return DatabaseContainer
     */
    protected function createContainer()
    {
        $factory = new DatabaseContainerFactory();

        return $factory->createContainer();
    }
}
