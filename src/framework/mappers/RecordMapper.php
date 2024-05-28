<?php

namespace mindplay\sql\framework\mappers;

use mindplay\sql\framework\Mapper;
use Traversable;

/**
 * Use this Mapper for quick, on-demand record mapping - as an alternative to implementing an actual Mapper class.
 */
class RecordMapper implements Mapper
{
    /**
     * @var callable(array<string,mixed>):array<string,mixed>
     */
    private $mapper;

    /**
     * @param callable(array<string,mixed>):array<string,mixed> $mapper function (array $record_set): array
     */
    public function __construct(callable $mapper)
    {
        $this->mapper = $mapper;
    }

    public function map(array $record_set): array|Traversable
    {
        $result = [];

        foreach ($record_set as $key => $record) {
            $result[$key] = call_user_func($this->mapper, $record);
        }

        return $result;
    }
}
