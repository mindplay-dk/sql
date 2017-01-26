<?php

namespace mindplay\sql\model;

use mindplay\sql\model\schema\Schema;
use mindplay\sql\model\schema\Type;
use mindplay\unbox\Container;
use UnexpectedValueException;

/**
 * This class implements a dedicated dependency injection container for the database domain.
 */
class DatabaseContainer extends Container implements TypeProvider, TableFactory
{
    /**
     * @inheritdoc
     */
    public function getType($type_name)
    {
        if (! $this->has($type_name)) {
            $this->inject($type_name, $this->create($type_name)); // auto-wiring
        }

        $type = $this->get($type_name);

        if (! $type instanceof Type) {
            $class_name = get_class($type);

            throw new UnexpectedValueException("{$class_name} does not implement the Type interface");
        }

        return $type;
    }

    /**
     * @param string Schema class-name
     *
     * @return Schema
     */
    public function getSchema($schema_type)
    {
        if (! $this->has($schema_type)) {
            $this->inject($schema_type, $this->create($schema_type)); // auto-wiring
        }

        $schema_type = $this->get($schema_type);

        if (! $schema_type instanceof Schema) {
            $class_name = get_class($schema_type);

            throw new UnexpectedValueException("{$class_name} does not extend the Schema class");
        }

        return $schema_type;
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
    public function createTable(Schema $schema, $class_name, $table_name, $alias)
    {
        return $this->create($class_name, [
            Schema::class => $schema,
            'name'        => $table_name,
            'alias'       => $alias,
        ]);
    }
}
