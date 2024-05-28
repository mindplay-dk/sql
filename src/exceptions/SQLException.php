<?php

namespace mindplay\sql\exceptions;

use Exception;
use mindplay\sql\framework\QueryFormatter;
use RuntimeException;

/**
 * This Exception-type represents an SQL error
 */
class SQLException extends RuntimeException
{
    /**
     * @param string              $sql    SQL statement (with ":name" placeholders)
     * @param array<string,mixed> $params map of parameter name/value pairs (to bind against placeholders in the statement)
     * @param string              $message
     * @param int                 $code
     * @param Exception|null      $previous
     */
    public function __construct(
        string $sql,
        array $params = [],
        string $message = 'SQL Error',
        int $code = 0,
        Exception $previous = null
    ) {
        parent::__construct(
            "{$message}\n" . QueryFormatter::formatQuery($sql, $params),
            $code,
            $previous
        );
    }
}
