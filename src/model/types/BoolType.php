<?php

namespace mindplay\sql\model\types;

use InvalidArgumentException;
use mindplay\sql\model\schema\Type;

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
    protected function __construct($true_value = true, $false_value = false)
    {
        $this->true_value = $true_value;
        $this->false_value = $false_value;
    }

    /**
     * Flyweight factory method.
     *
     * @param int|string|bool $true_value  value to which boolean TRUE should be mapped
     * @param int|string|bool $false_value value to which boolean FALSE should be mapped
     *
     * @return self
     */
    public static function get($true_value = true, $false_value = false)
    {
        static $instances = [];

        $key = "{$true_value}_{$false_value}";

        if (!isset($instances[$key])) {
            $instances[$key] = new BoolType($true_value, $false_value);
        }
        
        return $instances[$key];
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
