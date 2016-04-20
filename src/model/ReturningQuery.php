<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\BatchMapper;
use mindplay\sql\framework\Driver;
use mindplay\sql\framework\Mapper;
use mindplay\sql\framework\RecordMapper;
use mindplay\sql\framework\ReturningExecutable;
use mindplay\sql\framework\TypeProvider;
use OutOfBoundsException;

/**
 * Abstract base class for Query types that return and map results, such as `SELECT` or `UPDATE RETURNING`.
 */
abstract class ReturningQuery extends ProjectionQuery implements ReturningExecutable
{
    /**
     * @var string[] list of return expressions (for use in a SELECT or RETURNING clause)
     */
    protected $select = [];

    /**
     * @var Type[] map where return variable name maps to Type
     */
    protected $select_type = [];

    /**
     * @var Mapper[] list of Mappers to apply
     */
    protected $mappers = [];

    /**
     * @var Driver
     */
    private $driver;
    
    public function __construct($root, TypeProvider $types, Driver $driver)
    {
        parent::__construct($root, $types);
        
        $this->driver = $driver;
    }

    /**
     * Add one or more Columns to select and return
     * 
     * @param Column|Column[] one or more Columns to select and return
     *
     * @return $this
     */
    public function columns($cols)
    {
        /**
         * @var Column[] $cols
         */
        
        $cols = is_array($cols) ? $cols : [$cols];
        
        foreach ($cols as $col) {
            $this->value($col->__toString(), $col->getAlias() ?: $col->getName(), $col->getType());
        }

        return $this;
    }

    /**
     * Add an SQL expression to select and return
     * 
     * @param string           $expr return expression
     * @param string           $name return variable name
     * @param Type|string|null $type optional Type (or Type class-name)
     *
     * @return $this
     */
    public function value($expr, $name, Type $type = null)
    {
        if (isset($this->select[$name])) {
            throw new OutOfBoundsException("duplicate return variable name: {$name}");
        }
        
        $quoted_name = $this->driver->quoteName($name);
        
        $this->select[$name] = "{$expr} AS {$quoted_name}";
        $this->select_type[$name] = $type;

        return $this;
    }

    /**
     * Append a Mapper instance to apply when each batch of a record-set is fetched.
     *
     * @param Mapper $mapper
     *
     * @return $this
     *
     * @see mapRecords() to map an anonymous function against every record
     * @see mapBatches() to map an anonymous function against each batch of records
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
     * @return $this
     *
     * @see mapBatches() to map an anonymous function against each batch of records
     */
    public function mapRecords(callable $mapper)
    {
        return $this->map(new RecordMapper($mapper));
    }

    /**
     * Map an anonymous function against each batch of records.
     *
     * @param callable $mapper function (array $record_set) : array
     *
     * @return $this
     *
     * @see mapRecords() to map an anonymous function against every record
     */
    public function mapBatches(callable $mapper)
    {
        return $this->map(new BatchMapper($mapper));
    }

    /**
     * @inheritdoc
     */
    public function getMappers()
    {
        return array_merge([new TypeMapper($this->select_type)], $this->mappers);
    }

    /**
     * @return string comma-separated return expressions (for use in the SELECT or RETURNING clause of an SQL query)
     */
    protected function buildReturnVars()
    {
        return implode(",\n  ", $this->select);
    }
}
