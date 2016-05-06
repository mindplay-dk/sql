<?php

namespace mindplay\sql\drivers;

use mindplay\sql\exceptions\ForeignKeyException;
use mindplay\sql\exceptions\SQLException;
use mindplay\sql\exceptions\UniqueConstraintException;
use mindplay\sql\framework\Driver;

class PostgresDriver implements Driver
{
    public function quoteName($name)
    {
        return '"' . $name . '"';
    }

    public function getExceptionType($sql_state, $error_code, $error_message)
    {
        switch ($sql_state) {
            case '23503':
                return ForeignKeyException::class;

            case '23505':
                return UniqueConstraintException::class;

            default:
                return SQLException::class;
        }
    }
}
