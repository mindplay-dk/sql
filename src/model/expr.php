<?php

namespace mindplay\sql\model;

use UnexpectedValueException;

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
        if (count($exprs) === 0) {
            throw new UnexpectedValueException("unexpected empty array");
        }

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
        if (count($exprs) === 0) {
            throw new UnexpectedValueException("unexpected empty array");
        }
        
        return count($exprs) > 1
            ? "(" . implode(" AND ", $exprs) . ")"
            : $exprs[0];
    }
}
