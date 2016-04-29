<?php

namespace mindplay\sql\framework;

use mindplay\sql\model\InsertQuery;
use mindplay\sql\model\Schema;
use mindplay\sql\model\SelectQuery;
use mindplay\sql\model\Table;
use mindplay\sql\model\UpdateQuery;
use UnexpectedValueException;

/**
 * This class implements the primary public API of the database model.
 */
class Database
{
    /**
     * @var DatabaseContainer
     */
    protected $container;
    
    /**
     * @param DatabaseContainer $container
     */
    public function __construct(DatabaseContainer $container)
    {
        $this->container = $container;
    }

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
     * @param Table $from
     *
     * @return SelectQuery
     */
    public function select(Table $from)
    {
        return $this->container->create(SelectQuery::class, ['root' => $from]);
    }

    /**
     * @param Table                  $into
     * @param mixed[]|mixed[][]|null $record optional record map (or list of record maps) where Column name => value
     *
     * @return InsertQuery
     */
    public function insert(Table $into, $record = null)
    {
        return $this->container->create(InsertQuery::class, ['table' => $into, 'record' => $record]);
    }

    /**
     * @param Table $table
     * 
     * @return UpdateQuery
     */
    public function update(Table $table)
    {
        return $this->container->create(UpdateQuery::class, ['table' => $table]);
    }
}
