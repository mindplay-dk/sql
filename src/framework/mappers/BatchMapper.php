<?php

namespace mindplay\sql\framework\mappers;

use mindplay\sql\framework\Mapper;
use Traversable;

/**
 * Use this Mapper for quick, on-demand batch mapping - as an alternative to implementing an actual Mapper class.
 */
class BatchMapper implements Mapper
{
    /**
     * @var callable(array<array<string,mixed>>):iterable<array<string,mixed>>
     */
    private $mapper;

    /**
     * @param callable(array<array<string,mixed>>):iterable<array<string,mixed>> $mapper function (array $record_set): array
     */
    public function __construct(callable $mapper)
    {
        $this->mapper = $mapper;
    }

    public function map(array $record_set): array|Traversable
    {
        return ($this->mapper)($record_set);
    }
}
