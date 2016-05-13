<?php

namespace mindplay\sql\types;

use mindplay\sql\model\Type;

class BooleanType implements Type
{
    public function convertToSQL($value)
    {
        return $value;
    }

    public function convertToPHP($value)
    {
        return $value;
    }
}
