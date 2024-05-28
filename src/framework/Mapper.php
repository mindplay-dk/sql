<?php

namespace mindplay\sql\framework;

use Traversable;

/**
 * This interface defines a method for mapping a set of records to a different set of records or objects.
 */
interface Mapper
{
    /**
     * @param array<string,mixed>[] $record_set
     * 
     * @return iterable<array<string,mixed>>
     */
    public function map(array $record_set): array|Traversable;
}
