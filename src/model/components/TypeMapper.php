<?php

namespace mindplay\sql\model\components;

use mindplay\sql\framework\Mapper;
use mindplay\sql\model\schema\Type;
use OutOfBoundsException;
use Traversable;
use UnexpectedValueException;

/**
 * This Mapper performs Type conversions on return variables (columns) returned by
 * a returning database query, e.g. SELECT or UPDATE RETURNING queries.
 * 
 * @see ReturningQuery::getMappers()
 */
class TypeMapper implements Mapper
{
    /**
     * @var Type[] map where return variable name maps to Type
     */
    private array $types;

    /**
     * @param Type[] $types map where return variable name maps to Type
     */
    public function __construct(array $types)
    {
        $this->types = $types;
    }

    /**
     * @inheritDoc
     */
    public function map(array $record_set): array|Traversable
    {
        foreach ($record_set as $index => $record) {
            if (! is_array($record)) {
                throw new UnexpectedValueException("unexpected record type: " . gettype($record));
            }

            foreach ($this->types as $name => $type) {
                if (! array_key_exists($name, $record)) {
                    throw new OutOfBoundsException("undefined record field: {$name}");
                }

                $record[$name] = $type->convertToPHP($record[$name]);
            }

            yield $index => $record;
        }
    }
}
