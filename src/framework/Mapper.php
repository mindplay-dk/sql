<?php

namespace mindplay\sql\framework;

/**
 * This interface defines a method for mapping a set of records to a different set of records or objects.
 */
interface Mapper
{
    /**
     * @param array $record_set
     *
     * @return array
     */
    public function map(array $record_set);
}
