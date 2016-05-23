<?php

namespace mindplay\sql\model;

use mindplay\sql\model\schema\Type;
use mindplay\unbox\Container;
use UnexpectedValueException;

/**
 * This class implements a dedicated dependency injection container for the database domain.
 */
class DatabaseContainer extends Container implements TypeProvider, TableFactory
{
    public function __construct()
    {
        parent::__construct();
        
        // self-register:

        $this->set(TypeProvider::class, $this);
        $this->set(TableFactory::class, $this);
    }

    /**
     * @inheritdoc
     */
    public function getType($type_name)
    {
        if (! $this->has($type_name)) {
            $this->register($type_name); // auto-wiring (for Types with no special constructor dependencies)
        }

        $type = $this->get($type_name);

        if (! $type instanceof Type) {
            $class_name = get_class($type);

            throw new UnexpectedValueException("{$class_name} does not implement the Type interface");
        }
        
        return $type;
    }

    /**
     * @inheritdoc
     */
    public function hasType($type_name)
    {
        return $this->has($type_name);
    }
    
    /**
     * @inheritdoc
     */
    public function createTable($class_name, $table_name, $alias)
    {
        return $this->create($class_name, ['name' => $table_name, 'alias' => $alias]);
    }
}
