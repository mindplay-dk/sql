<?php

namespace mindplay\sql\types;

use InvalidArgumentException;
use mindplay\sql\model\Type;

class BoolType implements Type
{
    /**
     * @var mixed
     */
    private $true_value;

    /**
     * @var mixed
     */
    private $false_value;

    /**
     * @param mixed $true_value
     * @param mixed $false_value
     */
    public function __construct($true_value = true, $false_value = false)
    {
        $this->true_value = $true_value;
        $this->false_value = $false_value;
    }

    /**
     * @return BoolType
     */
    public static function asInt()
    {
        return new BoolType(1, 0);
    }

    /**
     * @param string $true_value
     * @param string $false_value
     *
     * @return BoolType
     */
    public static function asEnum($true_value, $false_value)
    {
        return new BoolType($true_value, $false_value);
    }

    public function convertToSQL($value)
    {
        if ($value === null) {
            return null;
        }

        if ($value === true) {
            return $this->true_value;
        }

        if ($value === false) {
            return $this->false_value;
        }

        throw new InvalidArgumentException("unexpected value: " . print_r($value));
    }

    public function convertToPHP($value)
    {
        if ($value === null) {
            return null;
        }

        if ($value === $this->true_value) {
            return true;
        }

        if ($value === $this->false_value) {
            return false;
        }

        throw new InvalidArgumentException("unexpected value: " . print_r($value));
    }
}
