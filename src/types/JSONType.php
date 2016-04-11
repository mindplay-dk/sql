<?php

namespace mindplay\sql\types;

use mindplay\sql\model\Type;

class JSONType implements Type
{
    public function convertToSQL($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        return json_encode($value);
    }

    public function convertToPHP($value)
    {
        return ($value === null || $value === '')
            ? null
            : json_decode($value, true);
    }
}
