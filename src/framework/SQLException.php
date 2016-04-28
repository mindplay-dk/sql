<?php

namespace mindplay\sql\framework;

use Exception;
use RuntimeException;

/**
 * This Exception-type represents an SQL error
 */
class SQLException extends RuntimeException
{
    /**
     * @param string         $sql    SQL statement (with ":name" placeholders)
     * @param array          $params map of parameter name/value pairs (to bind against placeholders in the statement)
     * @param string         $message
     * @param int            $code
     * @param Exception|null $previous
     */
    public function __construct($sql, $params = [], $message = 'SQL Error', $code = 0, Exception $previous = null)
    {
        parent::__construct("{$message}\n" . $this->emulatedPrepare($sql, $params), $code, $previous);
    }
    
    /**
     * @param string $sql
     * @param array  $params
     *
     * @return string SQL with emulated prepare (for diagnostic purposes only)
     */
    private function emulatedPrepare($sql, array $params)
    {
        foreach ($params as $name => $value) {
            $quoted_value = $value === null
                ? "NULL"
                : (is_numeric($value) ? $value : "'{$value}'");

            $sql = str_replace(":{$name}", $quoted_value, $sql);
        }

        return $sql;
    }
}
