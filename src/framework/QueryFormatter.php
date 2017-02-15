<?php

namespace mindplay\sql\framework;

/**
 * This is a pseudo-namespace for a simple query-formatter *strictly* for diagnostic
 * purposes - it implements a simple emulation of PDO placeholder interpolation, so
 * that queries can be rendered in error-messages with actual parameter values.
 */
abstract class QueryFormatter
{
    /**
     * @param string $sql
     * @param array  $params
     *
     * @return string SQL with emulated prepare (for diagnostic purposes only)
     */
    public static function formatQuery($sql, $params)
    {
        $quoted_params = [];

        foreach ($params as $name => $value) {
            $quoted_params[":{$name}"] = $value === null
                ? "NULL"
                : (is_numeric($value) ? $value : "'{$value}'");
        }

        return strtr($sql, $quoted_params);
    }
}
