<?php

namespace mindplay\sql\drivers;

use mindplay\sql\framework\Driver;

class MySQLDriver extends Driver
{
    public function quoteName($table, $column = null)
    {
        return '`' . $table . '`' . ($column ? '.`' . $column . '`' : '');
    }
}
