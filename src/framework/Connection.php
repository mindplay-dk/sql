<?php

namespace mindplay\sql\framework;

use Exception;
use InvalidArgumentException;
use LogicException;
use PDO;
use PDOStatement;
use UnexpectedValueException;

/**
 * This class represents a PDO Connection to a Database using a Driver.
 */
class Connection
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var Driver
     */
    private $driver;

    /**
     * @var int number of nested calls to transact()
     *
     * @see transact()
     */
    private $transaction_level = 0;

    /**
     * @var bool net result of nested calls to transact()
     *
     * @see transact()
     */
    private $transaction_result;

    /**
     * @param PDO    $pdo
     * @param Driver $driver
     */
    public function __construct(PDO $pdo, Driver $driver)
    {
        $this->pdo = $pdo;
        $this->driver = $driver;
    }

    /**
     * @return PDO
     */
    public function getPDO()
    {
        return $this->pdo;
    }

    /**
     * Prepare and bind a `Statement` and create a prepared `PDOStatement` handle.
     * 
     * @param Statement $statement
     *
     * @return PDOStatement
     */
    public function prepare(Statement $statement)
    {
        $params = $statement->getParams();

        $sql = $this->expandPlaceholders($statement->getSQL(), $params);

        $handle = $this->getPDO()->prepare($sql);

        foreach ($params as $name => $value) {
            if (is_array($value)) {
                $index = 1; // use a base-1 offset consistent with expandPlaceholders() 
                
                foreach ($value as $item) {
                    // NOTE: we deliberately ignore the array indices here, as using them could result in broken SQL!
                    
                    $this->bind($handle, "{$name}_{$index}", $item);
                    
                    $index += 1;
                }
            } else {
                $this->bind($handle, $name, $value);
            }
        }

        return $handle;
    }

    /**
     * Internally expand SQL placeholders (for array-types)
     *
     * @param string $sql    SQL with placeholders
     * @param array  $params placeholder name/value pairs
     *
     * @return string SQL with expanded placeholders
     */
    private function expandPlaceholders($sql, array $params)
    {
        $replace_pairs = [];

        foreach ($params as $name => $value) {
            if (is_array($value)) {
                // TODO: QA! For empty arrays, the resulting SQL is e.g.: "SELECT * FROM foo WHERE foo.bar IN (null)"
                
                $replace_pairs[":{$name}"] = count($value) === 0
                    ? "(null)" // empty set
                    : "(" . implode(', ', array_map(function ($i) use ($name) { return ":{$name}_{$i}"; }, range(1, count($value)))) . ")";
            }
        }
        
        return count($replace_pairs)
            ? strtr($sql, $replace_pairs)
            : $sql; // no arrays found in the given parameters
    }

    /**
     * Internally bind a single scalar value against a single placeholder
     *
     * @param PDOStatement               $handle statement handle
     * @param string                     $name   placeholder name
     * @param int|float|string|bool|null $value  scalar value
     *
     * @return void
     */
    private function bind(PDOStatement $handle, $name, $value)
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
            $handle->bindValue($name, $value, $PDO_TYPE[$value_type]);
        } else {
            throw new InvalidArgumentException("unexpected value type: {$value_type}");
        }
    }

    /**
     * @param callable $func function () : bool - must return TRUE to commit or FALSE to roll back
     *
     * @return bool TRUE on success (committed) or FALSE on failure (rolled back)
     *
     * @throws InvalidArgumentException if the provided argument is not a callable function
     * @throws LogicException if an unhandled Exception occurs while calling the provided function
     * @throws UnexpectedValueException if the provided function does not return TRUE or FALSE
     */
    public function transact(callable $func)
    {
        if ($this->transaction_level === 0) {
            // starting a new stack of transactions - assume success:
            $this->pdo->beginTransaction();
            $this->transaction_result = true;
        }

        $this->transaction_level += 1;

        /** @var mixed $commit return type of $func isn't guaranteed, therefore mixed rather than bool */

        try {
            $commit = call_user_func($func);
        } catch (Exception $exception) {
            $commit = false;
        }

        $this->transaction_result = ($commit === true) && $this->transaction_result;

        $this->transaction_level -= 1;

        if ($this->transaction_level === 0) {
            if ($this->transaction_result === true) {
                $this->pdo->commit();

                return true; // the net transaction is a success!
            } else {
                $this->pdo->rollBack();
            }
        }

        if (isset($exception)) {
            // re-throw unhandled Exception as a LogicException:
            throw new LogicException("unhandled Exception during transaction", 0, $exception);
        }

        if (! is_bool($commit)) {
            throw new UnexpectedValueException("\$func must return TRUE (to commit) or FALSE (to roll back)");
        }

        return $this->transaction_result;
    }
}
