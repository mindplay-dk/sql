<?php

namespace mindplay\sql\pdo;

use InvalidArgumentException;
use mindplay\sql\exceptions\SQLException;
use mindplay\sql\framework\Driver;
use mindplay\sql\framework\PreparedStatement;
use PDO;
use PDOStatement;

/**
 * This class implements a Prepared Statement adapter for PDO Statements.
 */
class PreparedPDOStatement implements PreparedStatement
{
    /**
     * @var PDOStatement
     */
    private $handle;

    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var array
     */
    private $params = [];

    /**
     * @var bool
     */
    private $executed = false;

    /**
     * @param PDOStatement $handle
     * @param Driver       $driver
     */
    public function __construct(PDOStatement $handle, Driver $driver)
    {
        $this->handle = $handle;
        $this->driver = $driver;
    }

    /**
     * @inheritdoc
     */
    public function bind($name, $value)
    {
        static $PDO_TYPE = [
            'integer' => PDO::PARAM_INT,
            'double'  => PDO::PARAM_STR, // bind as string, since there's no float type in PDO
            'string'  => PDO::PARAM_STR,
            'boolean' => PDO::PARAM_BOOL,
            'NULL'    => PDO::PARAM_NULL,
        ];

        $value_type = gettype($value);

        if (isset($PDO_TYPE[$value_type])) {
            $this->handle->bindValue($name, $value, $PDO_TYPE[$value_type]);

            $this->params[$name] = $value;
        } else {
            throw new InvalidArgumentException("unexpected value type: {$value_type}");
        }
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (@$this->handle->execute()) {
            $this->executed = true;
        } else {
            list($sql_state, $error_code, $error_message) = $this->handle->errorInfo();

            $exception_type = $this->driver->getExceptionType($sql_state, $error_code, $error_message);

            throw new $exception_type($this->handle->queryString, $this->params, "{$sql_state}: {$error_message}", $error_code);
        }
    }

    /**
     * @inheritdoc
     */
    public function fetch()
    {
        if (! $this->executed) {
            $this->execute();
        }

        $result = $this->handle->fetch(PDO::FETCH_ASSOC);
        
        if ($result === false) {
            return null;
        }
        
        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getRowsAffected()
    {
        return $this->handle->rowCount();
    }
}
