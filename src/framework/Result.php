<?php

namespace mindplay\sql\framework;

use Iterator;
use IteratorAggregate;
use RuntimeException;

/**
 * This class represents the result of fetching a `PreparedStatement`, e.g. the results of
 * a `SELECT` SQL query, and with Mappers being applied on-the-fly, in batches.
 *
 * It implements `IteratorAggregate`, allowing you to execute the query and iterate
 * over the result set with a `foreach` statement.
 */
class Result implements IteratorAggregate
{
    /**
     * @var PreparedStatement
     */
    private $statement;

    /**
     * @var int
     */
    private $batch_size;

    /**
     * @var Mapper[] list of Mappers to apply when fetching results
     */
    private $mappers;

    /**
     * @param PreparedStatement $statement  prepared statement
     * @param int               $batch_size batch-size (when fetching large result sets)
     * @param Mapper[]          $mappers    list of Mappers to apply while fetching results
     */
    public function __construct(PreparedStatement $statement, $batch_size, array $mappers)
    {
        $this->statement = $statement;
        $this->batch_size = $batch_size;
        $this->mappers = $mappers;
    }

    /**
     * @return mixed|null first record of the record-set (or NULL, if the record-set is empty)
     */
    public function firstRow()
    {
        foreach ($this->createIterator(1) as $record) {
            return $record; // break from loop immediately after fetching the first record
        }

        return null;
    }

    /**
     * @return mixed|null first column value of the first record of the record-set (or NULL, if the record-set is empty)
     */
    public function firstCol()
    {
        foreach ($this->createIterator(1) as $record) {
            $keys = array_keys($record);

            return $record[$keys[0]]; // break from loop immediately after fetching the first record
        }

        return null;
    }

    /**
     * @return array all the records of the record-set
     */
    public function all()
    {
        return iterator_to_array($this->getIterator());
    }
    
    /**
     * Execute this Statement and return a Generator, so you can iterate over the results.
     *
     * This method implements `IteratorAggregate`, permitting you to iterate directly over
     * the resulting records (or objects) without explicitly having to call this method.
     *
     * @return Iterator
     */
    public function getIterator()
    {
        return $this->createIterator($this->batch_size);
    }

    /**
     * Create an Iterator with a given batch-size.
     *
     * @param int $batch_size batch-size when processing the result set
     *
     * @return Iterator
     */
    private function createIterator($batch_size)
    {
        $fetching = true;

        do {
            // fetch a batch of records:

            $batch = [];

            do {
                $record = $this->statement->fetch();

                if ($record) {
                    $batch[] = $record;
                } else {
                    if (count($batch) === 0) {
                        return; // last batch of records fetched
                    }

                    $fetching = false; // last record of batch fetched
                }
            } while ($fetching && (count($batch) < $batch_size));

            // apply Mappers to current batch of records:

            $num_records = count($batch);

            foreach ($this->mappers as $index => $mapper) {
                $batch = $mapper->map($batch);

                if (count($batch) !== $num_records) {
                    $count = count($batch);

                    throw new RuntimeException("Mapper #{$index} returned {$count} records, expected: {$num_records}");
                }
            }

            // return each record from the current batch:

            foreach ($batch as $record) {
                yield $record;
            }
        } while ($fetching);
    }
}
