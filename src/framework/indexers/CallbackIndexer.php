<?php

namespace mindplay\sql\framework\indexers;

use mindplay\sql\framework\Indexer;

/**
 * This indexer delegates index calculation to a callback function
 */
class CallbackIndexer implements Indexer
{
    /**
     * @var callable
     */
    private $callback;

    /**
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    public function index(array $record)
    {
        return call_user_func($this->callback, $record);
    }
}
