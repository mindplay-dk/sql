<?php

namespace mindplay\sql\model;

/**
 * Pseudo-namespace for expression builder functions
 */
abstract class expr
{
    /**
     * Combine a list of expressions with OR operators
     * 
     * @param string[] $exprs
     * 
     * @return string
     */
    public static function any(array $exprs)
    {
        return count($exprs) > 1
            ? "(" . implode(" OR ", $exprs) . ")"
            : $exprs[0];
    }
    
    /**
     * Combine a list of expressions with AND operators
     * 
     * @param string[] $exprs
     * 
     * @return string
     */
    public static function all(array $exprs)
    {
        return count($exprs) > 1
            ? "(" . implode(" AND ", $exprs) . ")"
            : $exprs[0];
    }
}
