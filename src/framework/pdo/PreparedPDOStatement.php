<?php

namespace mindplay\sql\framework\pdo;

use mindplay\sql\framework\Logger;
use mindplay\sql\framework\PreparedStatement;
use mindplay\sql\model\TypeProvider;
use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * This class implements a Prepared Statement adapter for PDO Statements.
 */
class PreparedPDOStatement implements PreparedStatement
{
    private PDOStatement $handle;
    
    private PDOExceptionMapper $exception_mapper;
    
    private TypeProvider $types;
    
    /**
     * @var array<string,int|float|string|bool|null>
     */
    private array $params = [];
    
    private bool $executed = false;
    
    private Logger $logger;

    public function __construct(
        PDOStatement $handle,
        PDOExceptionMapper $exception_mapper,
        TypeProvider $types,
        Logger $logger
    ) {
        $this->handle = $handle;
        $this->exception_mapper = $exception_mapper;
        $this->types = $types;
        $this->logger = $logger;
    }

    /**
     * @inheritdoc
     */
    public function bind(string $name, int|float|string|bool|null $value): void
    {
        static $PDO_TYPE = [
            'integer' => PDO::PARAM_INT,
            'double'  => PDO::PARAM_STR, // bind as string, since there's no float type in PDO
            'string'  => PDO::PARAM_STR,
            'boolean' => PDO::PARAM_BOOL,
            'NULL'    => PDO::PARAM_NULL,
        ];

        $value_type = gettype($value);

        $scalar_type = "scalar.{$value_type}";

        if ($this->types->hasType($scalar_type)) {
            $type = $this->types->getType($scalar_type);

            $value = $type->convertToSQL($value);

            $value_type = gettype($value);
        }

        if (isset($PDO_TYPE[$value_type])) {
            $this->handle->bindValue($name, $value, $PDO_TYPE[$value_type]);

            $this->params[$name] = $value;
        } else {
            throw new RuntimeException("internal error: unexpected value type {$value_type}");
        }
    }

    /**
     * @inheritdoc
     */
    public function execute(): void
    {
        $microtime_begin = microtime(true);

        $success = false;

        try {
            $success = @$this->handle->execute();
        } catch (PDOException $e) {
            // error will be handled below
        }

        if ($success) {
            $this->executed = true;
            $microtime_end = microtime(true);
            $time_msec = ($microtime_end - $microtime_begin) * 1000;
            $this->logger->logQuery($this->handle->queryString, $this->params, $time_msec);
        } else {
            list($sql_state, $error_code, $error_message) = $this->handle->errorInfo();

            $exception_type = $this->exception_mapper->getExceptionType($sql_state, $error_code, $error_message);

            throw new $exception_type(
                $this->handle->queryString,
                $this->params,
                "{$sql_state}: {$error_message}",
                $error_code
            );
        }
    }

    /**
     * @inheritdoc
     * 
     * @return array<string,mixed>|null
     */
    public function fetch(): array|null
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
    public function getRowsAffected(): int
    {
        return $this->handle->rowCount();
    }
}
