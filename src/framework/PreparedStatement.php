<?php

namespace mindplay\sql\framework;

use InvalidArgumentException;
use PDO;
use PDOStatement;

/**
 * This class represents a prepared `PDOStatement` and values currently bound to it.
 */
class PreparedStatement
{
    /**
     * @var PDOStatement
     */
    private $handle;

    /**
     * @var array
     */
    private $params;

    /**
     * @var bool
     */
    private $executed = false;

    /**
     * @param PDOStatement $handle
     */
    public function __construct(PDOStatement $handle)
    {
        $this->handle = $handle;
    }
    
    /**
     * Bind an individual placeholder name to a given scalar (int|float|string|bool|null) value.
     *
     * @param string                     $name  placeholder name
     * @param int|float|string|bool|null $value scalar value
     *
     * @return void
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
     * Executes the underlying SQL statement.
     * 
     * @return void
     * 
     * @throws SQLException on failure to execute the underlying SQL statement
     */
    public function execute()
    {
        if ($this->handle->execute() === false) {
            $error = $this->handle->errorInfo();
            
            throw new SQLException($this->handle->queryString, $this->params, "{$error[0]}: {$error[2]}", $error[1]);
        }
        
        $this->executed = true;
    }

    /**
     * Fetches the next record from the result set and advances the cursor.
     * 
     * @return array|null next record-set (or NULL, if no more records are available)
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
}
