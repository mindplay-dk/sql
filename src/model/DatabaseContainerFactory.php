<?php

namespace mindplay\sql\model;

use mindplay\unbox\ContainerFactory;

/**
 * This class implements a dedicated dependency injection container for the database domain.
 */
class DatabaseContainerFactory extends ContainerFactory
{
    public function __construct()
    {
        parent::__construct();

        // self-register:

        $this->alias(TypeProvider::class, DatabaseContainer::class);
        $this->alias(TableFactory::class, DatabaseContainer::class);
    }

    /**
     * Create and bootstrap a new `DatabaseContainer` instance
     *
     * @return DatabaseContainer
     */
    public function createContainer()
    {
        return new DatabaseContainer($this);
    }
}
