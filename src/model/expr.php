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
     */
    public static function any(array $exprs): string
    {
        if (count($exprs) === 0) {
            throw new UnexpectedValueException("unexpected empty array");
        }

        return count($exprs) > 1
            ? implode(" OR ", array_map(self::group(...), $exprs))
            : $exprs[0];
    }
    
    /**
     * Combine a list of expressions with AND operators
     * 
     * @param string[] $exprs
     */
    public static function all(array $exprs): string
    {
        if (count($exprs) === 0) {
            throw new UnexpectedValueException("unexpected empty array");
        }
        
        return count($exprs) > 1
            ? implode(" AND ", array_map(self::group(...), $exprs))
            : $exprs[0];
    }

    private const FULLY_GROUPED = '/^\((?:[^()]*|\((?:[^()]*|\([^()]*\))*\))*\)$/';

    /**
     * Group an expression in parentheses, if not already fully grouped
     */
    public static function group(string $expr): string
    {
        return preg_match(self::FULLY_GROUPED, $expr) === 1
            ? $expr
            : "($expr)";
    }
}
