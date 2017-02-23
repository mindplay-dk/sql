<?php

namespace mindplay\sql\model\components;

use mindplay\sql\framework\Indexer;
use mindplay\sql\framework\indexers\CallbackIndexer;
use mindplay\sql\framework\indexers\ValueIndexer;
use mindplay\sql\framework\Mapper;
use mindplay\sql\framework\mappers\BatchMapper;
use mindplay\sql\framework\mappers\RecordMapper;
use mindplay\sql\model\schema\Column;
use UnexpectedValueException;

/**
 * This trait implements support for mapping and indexing of results.
 */
trait Mappers
{
    /**
     * @var Mapper[] list of Mappers
     */
    protected $mappers = [];

    /**
     * @var Indexer|null
     */
    protected $indexer;

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

    /**
     * Define an {@see Indexer}, callable, Column, or Column-name to use
     * to compute an index-value for the returned records.
     *
     * Note that this can lead to index-collisions - for example, using a foreign key
     * in a query with multiple records for the same foreign key, may cause the same
     * foreign key to occur as an index multiple times; this will cause a run-time
     * exception when calling e.g. {@see Result::all()}.
     *
     * Note that iterating over the `Result` instance directly (and *not* calling the
     * `all()` method) does permit iteration over results with duplicate indices - this
     * can be useful e.g. when collecting child records to append to a parent.
     *
     * @param Indexer|Column|callable|string $indexer
     *
     * @return $this
     */
    public function index($indexer)
    {
        if ($indexer instanceof Indexer) {
            $this->indexer = $indexer;
        } elseif ($indexer instanceof Column) {
            $this->indexer = new ValueIndexer($indexer->getAlias() ?: $indexer->getName());
        } elseif (is_string($indexer)) {
            $this->indexer = new ValueIndexer($indexer);
        } elseif (is_callable($indexer, true)) {
            $this->indexer = new CallbackIndexer($indexer);
        } else {
            throw new UnexpectedValueException("unsupported indexer: " . print_r($indexer, true));
        }

        return $this;
    }
}
