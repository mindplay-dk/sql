<?php

namespace mindplay\sql\types;

use mindplay\sql\model\Type;

class IntType implements Type
{
    public function convertToSQL($value)
    {
        return $value;
    }

    public function convertToPHP($value)
    {
        return $value === null
            ? null
            : (int) $value;
    }
}
