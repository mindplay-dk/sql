<?php

namespace mindplay\sql\model\types;

use mindplay\sql\model\schema\Type;

class FloatType implements Type
{
    public function convertToSQL($value)
    {
        return $value === null
            ? null
            : (string) $value;
    }

    public function convertToPHP($value)
    {
        return $value === null
            ? null
            : (float) $value;
    }
}
