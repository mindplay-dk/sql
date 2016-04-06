<?php

namespace mindplay\sql\framework;

/**
 * Use this Mapper for quick, on-demand record mapping - as an alternative to implementing an actual Mapper class.
 */
class RecordMapper implements MapperInterface
{
    /**
     * @var callable
     */
    private $mapper;

    /**
     * @param callable $mapper function (array $record_set) : array
     */
    public function __construct(callable $mapper)
    {
        $this->mapper = $mapper;
    }

    public function map(array $record_set)
    {
        $result = [];

        foreach ($record_set as $key => $record) {
            $result[$key] = call_user_func($this->mapper, $record);
        }

        return $result;
    }
}
