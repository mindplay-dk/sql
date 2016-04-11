<?php

namespace mindplay\sql\framework;

use UnexpectedValueException;

/**
 * This class represents an as-of-yet unprepared SQL statement.
 */
class Statement implements Executable
{
    /**
     * @var string
     */
    private $sql;

    /**
     * @var array map where placeholder name maps to a scalar value, or array of scalar values
     */
    private $params = [];

    /**
     * @param string $sql SQL statement (with placeholders)
     */
    public function __construct($sql)
    {
        $this->sql = $sql;
    }

    /**
     * @return string SQL statement (with placeholders)
     */
    public function getSQL()
    {
        return $this->sql;
    }

    /**
     * @return array map where placeholder name maps to a scalar value, or array of scalar values
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * Bind an individual placeholder name to a given value.
     *
     * Acceptable value types are scalar types (string, int, float, bool, null) and arrays of scalar values.
     *
     * @param string                     $name  placeholder name
     * @param array|string|int|bool|null $value value to bind
     *
     * @return void
     */
    public function bind($name, $value)
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
    }

    /**
     * Applies a set of placeholder name/value pairs and binds them to individual placeholders.
     *
     * @param array $params placeholder name/value pairs
     *
     * @return void
     */
    public function apply(array $params)
    {
        foreach ($params as $name => $value) {
            $this->bind($name, $value);
        }
    }
}
