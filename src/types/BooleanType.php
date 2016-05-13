<?php

namespace mindplay\sql\types;

use mindplay\sql\model\Type;
use UnexpectedValueException;

class BooleanType implements Type
{
    protected $boolean_literals = [
        't'     => true,
        'true'  => true,
        'y'     => true,
        'yes'   => true,
        'on'    => true,
        '1'     => true,
        'f'     => false,
        'false' => false,
        'n'     => false,
        'no'    => false,
        'off'   => false,
        '0'     => false,
    ];

    public function convertToSQL($value)
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }

    public function convertToPHP($value)
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return (bool) $value;
        }

        if (isset($this->boolean_literals[$value])) {
            return $this->boolean_literals[$value];
        }

        throw new UnexpectedValueException("Unexpected value given as boolean");
    }
}
