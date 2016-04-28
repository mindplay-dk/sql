<?php

namespace mindplay\sql\drivers;

use mindplay\sql\framework\Driver;

class MySQLDriver implements Driver
{
    public function quoteName($name)
    {
        return '`' . $name . '`';
    }
}
