<?php

namespace mindplay\sql\model\components;

use mindplay\sql\framework\Mapper;
use mindplay\sql\framework\mappers\BatchMapper;
use mindplay\sql\framework\mappers\RecordMapper;

/**
 * This trait implements support for mapping of results.
 */
trait Mappers
{
    /**
     * @var Mapper[] list of Mappers
     */
    protected $mappers = [];

    /**
     * Append a Mapper instance to apply when each batch of a record-set is fetched.
     *
     * @param Mapper $mapper
     *
     * @see mapRecords() to map an anonymous function against every record
     * @see mapBatches() to map an anonymous function against each batch of records
     *                   
     * @return $this
     */
    public function map(Mapper $mapper)
    {
        $this->mappers[] = $mapper;
        
        return $this;
    }

    /**
     * Map an anonymous function against every record.
     *
     * @param callable $mapper function (mixed $record) : mixed
     *
     * @see mapBatches() to map an anonymous function against each batch of records
     *                   
     * @return $this
     */
    public function mapRecords(callable $mapper)
    {
        $this->mappers[] = new RecordMapper($mapper);
        
        return $this;
    }

    /**
     * Map an anonymous function against each batch of records.
     *
     * @param callable $mapper function (array $record_set) : array
     *
     * @see mapRecords() to map an anonymous function against every record
     *                   
     * @return $this
     */
    public function mapBatches(callable $mapper)
    {
        $this->mappers[] = new BatchMapper($mapper);
        
        return $this;
    }
}
