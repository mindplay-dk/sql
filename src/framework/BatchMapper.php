<?php

namespace mindplay\sql\framework;

/**
 * Use this Mapper for quick, on-demand batch mapping - as an alternative to implementing an actual Mapper class.
 */
class BatchMapper implements Mapper
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
        return call_user_func($this->mapper, $record_set);
    }
}
