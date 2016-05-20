<?php

namespace mindplay\sql\model\types;

use mindplay\sql\model\schema\Type;

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
