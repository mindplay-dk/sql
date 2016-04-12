<?php

use mindplay\sql\drivers\PostgresDriver;
use mindplay\sql\framework\Driver;
use mindplay\sql\model\Column;
use mindplay\sql\model\Schema;
use mindplay\sql\model\Table;
use mindplay\sql\types\IntType;
use mindplay\sql\types\StringType;
use mindplay\sql\types\TimestampType;

/**
 * @return Driver
 */
function create_driver()
{
    return new PostgresDriver();
}

/**
 * @property-read UserTable $user
 */
class SampleSchema extends Schema
{
    /**
     * @return UserTable
     */
    public function user($alias)
    {
        return $this->createTable(UserTable::class, __FUNCTION__, $alias);
    }
}

/**
 * @property-read Column $first_name
 * @property-read Column $last_name
 * @property-read Column $dob
 * @property-read Column $home_address_id
 */
class UserTable extends Table
{
    public function first_name($alias)
    {
        return $this->createColumn(StringType::class, __FUNCTION__, $alias);
    }

    public function last_name($alias)
    {
        return $this->createColumn(StringType::class, __FUNCTION__, $alias);
    }

    public function dob($alias)
    {
        return $this->createColumn(TimestampType::class, __FUNCTION__, $alias);
    }
    
    public function home_address_id($alias)
    {
        return $this->createColumn(IntType::class, __FUNCTION__, $alias);
    }
}
