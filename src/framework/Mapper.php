<?php

namespace mindplay\sql\framework;

use Traversable;

/**
 * This interface defines a method for mapping a set of records to a different set of records or objects.
 */
interface Mapper
{
    /**
     * @param array $record_set
     *
     * @return array|Traversable
     */
    public function map(array $record_set);
}
