<?php

namespace mindplay\sql\framework\indexers;

use mindplay\sql\framework\Indexer;
use UnexpectedValueException;

/**
 * This indexer takes a specific named value from a given record
 */
class ValueIndexer implements Indexer
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function index($record)
    {
        if (!isset($record[$this->name])) {
            throw new UnexpectedValueException("the given record does not contain a value named: {$this->name}");
        }

        return $record[$this->name];
    }
}
