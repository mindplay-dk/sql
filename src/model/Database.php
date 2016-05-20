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
    
    public function __construct()
    {
        $this->container = new DatabaseContainer();

        $this->container->set(Driver::class, $this->createDriver());
    }
    
    /**
     * @return Driver
     */
    abstract protected function createDriver();

    /**
     * @param string Schema class-name
     *
     * @return Schema
     */
    public function getSchema($schema)
    {
        if (! $this->container->has($schema)) {
            $this->container->register($schema); // auto-wiring (for Schema with no special constructor dependencies)
        }

        $schema = $this->container->get($schema);

        if (! $schema instanceof Schema) {
            $class_name = get_class($schema);

            throw new UnexpectedValueException("{$class_name} does not extend the Schema class");
        }

        return $schema;
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
