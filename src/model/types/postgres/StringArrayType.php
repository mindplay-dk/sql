<?php

namespace mindplay\sql\model\types\postgres;

use mindplay\sql\model\schema\Type;

/**
 * Support for one-dimensional (Postgres) string arrays
 *
 * TODO add unit-test
 *
 * @see https://github.com/opensoft/doctrine-postgres-types/blob/master/src/Doctrine/DBAL/PostgresTypes/TextArrayType.php
 */
class StringArrayType implements Type
{
    /**
     * @see http://stackoverflow.com/a/19082849/1160901
     */
    const POSTGRES_SYNTAX_PATTERN = '/(?<=^\{|,)(([^,"{]*)|\s*"((?:[^"\\\\]|\\\\(?:.|[0-9]+|x[0-9a-f]+))*)"\s*)(,|(?<!^\{)(?=\}$))/iu';

    public function convertToSQL($value)
    {
        if (empty($value)) {
            return '{}';
        }

        $result = '';

        foreach ($value as $part) {
            if (null === $part) {
                $result .= 'NULL,';
            } elseif ('' === $part) {
                $result .= '"",';
            } else {
                $result .= '"' . addcslashes($part, '"') . '",';
            }
        }

        return '{' . substr($result, 0, -1) . '}';
    }

    public function convertToPHP($value)
    {
        if (empty($value) || '{}' === $value) {
            return [];
        }

        $array = [];

        if (preg_match_all(self::POSTGRES_SYNTAX_PATTERN, $value, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                if ('' !== $match[3]) {
                    $array[] = stripcslashes($match[3]);
                } else {
                    $array[] = 'NULL' === $match[2] ? null : $match[2];
                }
            }
        }

        return $array;
    }
}
