<?php

use mindplay\sql\framework\Logger;
use mindplay\sql\model\query\Query;
use mindplay\sql\model\schema\Column;
use mindplay\sql\model\schema\Schema;
use mindplay\sql\model\schema\Table;
use mindplay\sql\model\types\BoolType;
use mindplay\sql\model\types\DateType;
use mindplay\sql\model\types\IntType;
use mindplay\sql\model\types\StringType;
use mindplay\sql\model\types\TimestampType;
use mindplay\sql\mysql\MySQLDatabase;
use Psr\Log\AbstractLogger;

// TODO fixture and tests for PostgresDatabase

/**
 * @return MySQLDatabase
 */
function create_db()
{
    return new MySQLDatabase();
}

class MockQuery extends Query
{
    /**
     * @return string SQL statement (with placeholders)
     */
    public function getSQL()
    {
        return "SELECT 1";
    }
}

class MockParameterQuery extends Query
{
    /**
     * @return string SQL statement (with placeholders)
     */
    public function getSQL()
    {
        return "SELECT :foo";
    }

    public function getParams()
    {
        return [ 'foo' => 'bar' ];
    }
}

/**
 * This logger just delegates to the supplied callable
 */
class MockLogger implements Logger
{
    /**
     * @var callable
     */
    private $log_function;

    public function __construct(callable $log_function)
    {
        $this->log_function = $log_function;
    }

    function logQuery($sql, $params, $time_msec)
    {
        call_user_func_array($this->log_function, func_get_args());
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

    // WARNING: the following is for testing only - obviously NEVER do anything like this in production code!

    private $name;

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }
}

/**
 * @property-read Column $id
 * @property-read Column $first_name
 * @property-read Column $last_name
 * @property-read Column $dob
 * @property-read Column $home_address_id
 * @property-read Column $deleted
 */
class UserTable extends Table
{
    public function id($alias)
    {
        return $this->autoColumn(__FUNCTION__, IntType::class, $alias);
    }
    
    public function first_name($alias)
    {
        return $this->requiredColumn(__FUNCTION__, StringType::class, $alias);
    }

    public function last_name($alias)
    {
        return $this->requiredColumn(__FUNCTION__, StringType::class, $alias);
    }

    public function dob($alias)
    {
        return $this->requiredColumn(__FUNCTION__, TimestampType::class, $alias);
    }

    public function birthday($alias)
    {
        return $this->requiredColumn(__FUNCTION__, DateType::class, $alias);
    }

    public function home_address_id($alias)
    {
        return $this->requiredColumn(__FUNCTION__, IntType::class, $alias);
    }

    public function deleted($alias)
    {
        return $this->requiredColumn(__FUNCTION__, BoolType::class, $alias);
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
        return $this->requiredColumn(__FUNCTION__, IntType::class, $alias);
    }

    public function completed($alias)
    {
        return $this->requiredColumn(__FUNCTION__, TimestampType::class, $alias);
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
        return $this->autoColumn(__FUNCTION__, IntType::class, $alias);
    }

    public function street_name($alias)
    {
        return $this->requiredColumn(__FUNCTION__, StringType::class, $alias);
    }
}

class MockPSRLogger extends AbstractLogger
{
    public $entries = [];

    public function log($level, $message, array $context = []): void
    {
        $this->entries[] = [$level, $message, $context];
    }
}
