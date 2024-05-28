<?php

namespace mindplay\sql\model;

use mindplay\sql\model\schema\Schema;
use mindplay\sql\model\schema\Type;
use mindplay\sql\model\schema\Table;
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
    public function getType(string $type_name): Type
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
     * @param $schema_type Schema class-name
     *
     * @return Schema
     */
    public function getSchema(string $schema_type): Schema
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
    public function hasType(string $type_name): bool
    {
        return $this->has($type_name);
    }

    /**
     * @inheritdoc
     */
    public function createTable(Schema $schema, string $class_name, string $table_name, string|null $alias): Table
    {
        return $this->create($class_name, [
            Schema::class => $schema,
            'name'        => $table_name,
            'alias'       => $alias,
        ]);
    }
}
