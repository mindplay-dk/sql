<?php

namespace mindplay\sql\model\types;

use mindplay\sql\model\schema\Type;

class StringType implements Type
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
