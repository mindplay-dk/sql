<?php

namespace mindplay\sql\framework;

class QueryFormatter
{
    /**
     * @param string $sql
     * @param array  $params
     *
     * @return string SQL with emulated prepare (for diagnostic purposes only)
     */
    public static function formatQuery($sql, $params)
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