<?php

namespace mindplay\sql\framework;

use Exception;
use InvalidArgumentException;
use LogicException;
use mindplay\sql\exceptions\TransactionAbortedException;
use UnexpectedValueException;

/**
 * This interface defines the responsibilities of the Connection model.
 */
interface Connection
{
    /**
     * Fetch the `Result` of executing an SQL "SELECT" statement.
     *
     * Note that you can directly iterate over the `Result` instance.
     *
     * @see MapperProvider optional interface enabling an Executable to provide Mappers
     *
     * @param Statement $statement
     * @param int       $batch_size batch-size (when fetching large result sets)
     *
     * @return Result
     */
    public function fetch(Statement $statement, $batch_size = 1000);

    /**
     * Execute an SQL statement, which does not produce a result, e.g. an "INSERT", "UPDATE" or "DELETE" statement.
     *
     * @param Statement $statement
     *
     * @return PreparedStatement
     */
    public function execute(Statement $statement);

    /**
     * Prepare an SQL statement.
     *
     * @param Statement $statement
     *
     * @return PreparedStatement
     */
    public function prepare(Statement $statement);

    /**
     * Execute a `SELECT COUNT(*)` SQL statement and return the result.
     * 
     * @param Countable $statement
     *
     * @return int
     */
    public function count(Countable $statement);

    /**
     * @param callable $func function () : bool - must return TRUE to commit or FALSE to roll back
     *
     * @return bool TRUE on success (committed) or FALSE on failure (rolled back)
     *
     * @throws TransactionAbortedException if a transaction is implicitly aborted by `return false` in a nested call
     * @throws Exception if the provided function throws an Exception, that Exception will be re-thrown
     * @throws InvalidArgumentException if the provided argument is not a callable function
     * @throws LogicException if an unhandled Exception occurs while calling the provided function
     * @throws UnexpectedValueException if the provided function does not return TRUE or FALSE
     */
    public function transact(callable $func);

    /**
     * @param string|null $sequence_name auto-sequence name (or NULL for e.g. MySQL which supports only one auto-key)
     *
     * @return mixed
     */
    public function lastInsertId($sequence_name = null);

    /**
     * Add a `Logger` instance, which will be notified when a query is executed.
     *
     * @param Logger $logger
     *
     * @return void
     */
    public function addLogger(Logger $logger);
}
