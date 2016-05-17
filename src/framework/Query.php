<?php

namespace mindplay\sql\framework;

use mindplay\sql\framework\mappers\BatchMapper;
use mindplay\sql\framework\mappers\RecordMapper;
use mindplay\sql\model\Type;
use UnexpectedValueException;

/**
 * Abstract base-class for all types of SQL Query models.
 */
abstract class Query implements Executable, MapperProvider
{
    /**
     * @var TypeProvider
     */
    protected $types;

    /**
     * @var array map where placeholder name => mixed value types
     */
    private $params = [];

    /**
     * @var Type[] map where placeholder name => Type instance
     */
    private $param_types = [];

    /**
     * @var Mapper[] list of Mappers to apply
     */
    protected $mappers = [];

    /**
     * @param TypeProvider $types
     */
    public function __construct(TypeProvider $types)
    {
        $this->types = $types;
    }

    /**
     * Bind an individual placeholder name to a given value.
     *
     * The `$type` argument is optional for scalar types (string, int, float, bool, null) and arrays of scalar values.
     *
     * @param string           $name placeholder name
     * @param mixed            $value
     * @param Type|string|null $type Type instance, or Type class-name (or NULL for scalar types)
     *
     * @return $this
     */
    public function bind($name, $value, $type = null)
    {
        static $SCALAR_TYPES = [
            'integer' => true,
            'double'  => true,
            'string'  => true,
            'boolean' => true,
            'NULL'    => true,
        ];

        $value_type = gettype($value);

        if ($value_type === 'array') {
            foreach ($value as $item) {
                $item_type = gettype($item);

                if (! isset($SCALAR_TYPES[$item_type])) {
                    throw new UnexpectedValueException("unexpected item type in array: {$item_type}");
                }
            }
        } else {
            if (! isset($SCALAR_TYPES[$value_type])) {
                throw new UnexpectedValueException("unexpected value type: {$value_type}");
            }
        }

        $this->params[$name] = $value;

        $this->param_types[$name] = is_string($type)
            ? $this->types->getType($type)
            : $type; // assumes Type instance (or NULL)

        return $this;
    }

    /**
     * Applies a set of placeholder name/value pairs and binds them to individual placeholders.
     * 
     * This works for scalar values only (string, int, float, bool, null) and arrays of scalar values - to
     * bind values with `Type`-support, use the `bind()` method.
     * 
     * @see bind()
     *
     * @param array $params placeholder name/value pairs
     *
     * @return $this
     */
    public function apply(array $params)
    {
        foreach ($params as $name => $value) {
            $this->bind($name, $value);
        }
        
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
        return $this->mappers;
    }

    /**
     * @inheritdoc
     */
    public function getParams()
    {
        $params = [];

        foreach ($this->params as $name => $value) {
            $params[$name] = isset($this->param_types[$name])
                ? $this->param_types[$name]->convertToSQL($value)
                : $value; // assume scalar value (or array of scalar values)
        }

        return $params;
    }
}
