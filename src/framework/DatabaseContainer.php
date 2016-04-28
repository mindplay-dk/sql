<?php

namespace mindplay\sql\framework;

use mindplay\sql\model\Type;
use mindplay\unbox\Container;
use UnexpectedValueException;

class DatabaseContainer extends Container implements TypeProvider, TableFactory
{
    /**
     * @param Driver $driver
     */
    public function __construct(Driver $driver)
    {
        parent::__construct();
        
        $this->set(Driver::class, $driver);

        // self-register:

        $this->set(self::class, $this);
        $this->set(get_class($this), $this);
        $this->set(TypeProvider::class, $this);
        $this->set(TableFactory::class, $this);
    }

    /**
     * @inheritdoc
     */
    public function getType($type)
    {
        if (! $this->has($type)) {
            $this->register($type); // auto-wiring (for Types with no special constructor dependencies)
        }

        $type = $this->get($type);

        if (! $type instanceof Type) {
            $class_name = get_class($type);

            throw new UnexpectedValueException("{$class_name} does not implement the Type interface");
        }

        return $type;
    }

    /**
     * @inheritdoc
     */
    public function createTable($class_name, $table_name, $alias)
    {
        return $this->create($class_name, ['name' => $table_name, 'alias' => $alias]);
    }
}
