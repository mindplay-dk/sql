<?php

namespace mindplay\sql\framework;

/**
 * This interfaces defines a method for mapping a record to an index-value.
 */
interface Indexer
{
    /**
     * @param array $record the record from which to obtain an index-value
     *
     * @return int|string the index-value
     */
    public function index(array $record);
}
