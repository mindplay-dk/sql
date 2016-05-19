<?php

namespace mindplay\sql\mysql;

use mindplay\sql\framework\Database;
use mindplay\sql\framework\Driver;
use mindplay\sql\types\BoolType;

class MySQLDatabase extends Database
{
    public function __construct()
    {
        parent::__construct();
        
        $this->container->register(BoolType::class, function () {
            return BoolType::asInt();
        });
    }

    /**
     * @return Driver
     */
    protected function createDriver()
    {
        return new MySQLDriver();
    }
}
