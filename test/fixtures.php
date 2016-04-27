<?php

use mindplay\sql\drivers\PostgresDriver;
use mindplay\sql\framework\Driver;
use mindplay\sql\model\Column;
use mindplay\sql\model\Query;
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

class MockQuery extends Query
{
    protected function buildQuery()
    {
        return "SELECT 1";
    }
}

/**
 * @property-read UserTable    $user
 * @property-read AddressTable $address
 * @property-read OrderTable   $order
 */
class SampleSchema extends Schema
{
    /**
     * @param string $alias
     * 
     * @return UserTable
     */
    public function user($alias)
    {
        return $this->createTable(UserTable::class, __FUNCTION__, $alias);
    }

    /**
     * @param string $alias
     * 
     * @return AddressTable
     */
    public function address($alias)
    {
        return $this->createTable(AddressTable::class, __FUNCTION__, $alias);
    }

    /**
     * @param string $alias
     * 
     * @return OrderTable
     */
    public function order($alias)
    {
        return $this->createTable(OrderTable::class, __FUNCTION__, $alias);
    }
}

/**
 * @property-read Column $id
 * @property-read Column $first_name
 * @property-read Column $last_name
 * @property-read Column $dob
 * @property-read Column $home_address_id
 */
class UserTable extends Table
{
    public function id($alias)
    {
        return $this->createColumn(IntType::class, __FUNCTION__, $alias);
    }
    
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

/**
 * @property-read Column $user_id
 * @property-read Column $completed
 */
class OrderTable extends Table
{
    public function user_id($alias)
    {
        return $this->createColumn(IntType::class, __FUNCTION__, $alias);
    }

    public function completed($alias)
    {
        return $this->createColumn(TimestampType::class, __FUNCTION__, $alias);
    }
}

/**
 * @property-read Column $id
 * @property-read Column $street_name
 */
class AddressTable extends Table
{
    public function id($alias)
    {
        return $this->createColumn(IntType::class, __FUNCTION__, $alias);
    }

    public function street_name($alias)
    {
        return $this->createColumn(StringType::class, __FUNCTION__, $alias);
    }
}
