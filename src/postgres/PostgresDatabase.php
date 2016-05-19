<?php

namespace mindplay\sql\postgres;

use mindplay\sql\framework\Database;

class PostgresDatabase extends Database
{
    protected function createDriver()
    {
        return new PostgresDriver();
    }
}
