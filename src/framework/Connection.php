<?php

namespace mindplay\sql\framework;

use PDO;
use Exception;
use InvalidArgumentException;
use LogicException;
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
     * @param PDO      $pdo
     * @param Driver   $driver
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