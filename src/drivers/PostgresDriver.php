<?php

namespace mindplay\sql\drivers;

use mindplay\sql\framework\Driver;

class PostgresDriver implements Driver
{
    public function quoteName($name)
    {
        return '"' . $name . '"';
    }
}
