<?php

namespace mindplay\sql\framework\pdo;

use Exception;
use mindplay\sql\exceptions\TransactionAbortedException;
use mindplay\sql\framework\Connection;
use mindplay\sql\framework\Countable;
use mindplay\sql\framework\IndexerProvider;
use mindplay\sql\framework\Logger;
use mindplay\sql\framework\MapperProvider;
use mindplay\sql\framework\Result;
use mindplay\sql\framework\Statement;
use mindplay\sql\model\TypeProvider;
use PDO;
use UnexpectedValueException;

/**
 * This class implements a Connection adapter for a PDO connection.
 */
abstract class PDOConnection implements Connection, PDOExceptionMapper, Logger
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var TypeProvider
     */
    private $types;
    
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
     * @var Logger[]
     */
    private $loggers = [];

    /**
     * To avoid duplicating dependencies, you should use DatabaseContainer::createPDOConnection()
     * rather than calling this constructor directly.
     *
     * @param PDO          $pdo
     * @param TypeProvider $types
     */
    public function __construct(PDO $pdo, TypeProvider $types)
    {
        $this->pdo = $pdo;
        $this->types = $types;
    }

    /**
     * @inheritdoc
     */
    public function prepare(Statement $statement)
    {
        $params = $statement->getParams();

        $sql = $this->expandPlaceholders($statement->getSQL(), $params);

        $prepared_statement = new PreparedPDOStatement($this->pdo->prepare($sql), $this, $this->types, $this);
        
        foreach ($params as $name => $value) {
            if (is_array($value)) {
                $index = 1; // use a base-1 offset consistent with expandPlaceholders()

                foreach ($value as $item) {
                    // NOTE: we deliberately ignore the array indices here, as using them could result in broken SQL!

                    $prepared_statement->bind("{$name}_{$index}", $item);

                    $index += 1;
                }
            } else {
                $prepared_statement->bind($name, $value);
            }
        }

        return $prepared_statement;
    }

    /**
     * @inheritdoc
     */
    public function fetch(Statement $statement, $batch_size = 1000)
    {
        $mappers = $statement instanceof MapperProvider
            ? $statement->getMappers()
            : [];
        
        $indexer = $statement instanceof IndexerProvider
            ? $statement->getIndexer()
            : null;

        return new Result(
            $this->prepare($statement),
            $batch_size,
            $mappers,
            $indexer
        );
    }

    /**
     * @inheritdoc
     */
    public function execute(Statement $statement)
    {
        $prepared_statement = $this->prepare($statement);

        $prepared_statement->execute();
        
        return $prepared_statement;
    }

    /**
     * @inheritdoc
     */
    public function count(Countable $statement)
    {
        return $this->fetch($statement->createCountStatement())->firstCol();
    }

    /**
     * @inheritdoc
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
            // re-throw unhandled Exception:
            throw $exception;
        }

        if ($this->transaction_level > 0 && $commit === false) {
            throw new TransactionAbortedException("a nested call to transact() returned FALSE");
        }

        if (! is_bool($commit)) {
            throw new UnexpectedValueException("\$func must return TRUE (to commit) or FALSE (to roll back)");
        }

        return $this->transaction_result;
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
                    : "(" . implode(', ', array_map(function ($i) use ($name) {
                        return ":{$name}_{$i}";
                    }, range(1, count($value)))) . ")";
            }
        }

        return count($replace_pairs)
            ? strtr($sql, $replace_pairs)
            : $sql; // no arrays found in the given parameters
    }

    /**
     * @param string|null $sequence_name auto-sequence name (or NULL for e.g. MySQL which supports only one auto-key)
     *
     * @return int|string
     */
    public function lastInsertId($sequence_name = null)
    {
        $id = $this->pdo->lastInsertId($sequence_name);
        
        return is_numeric($id)
            ? (int) $id
            : $id;
    }
    
    /**
     * @inheritdoc
     */
    public function addLogger(Logger $logger)
    {
        $this->loggers[] = $logger;
    }
    
    /**
     * @inheritdoc
     */
    public function logQuery($sql, $params, $time_msec)
    {
        foreach ($this->loggers as $logger) {
            $logger->logQuery($sql, $params, $time_msec);
        }
    }
}
