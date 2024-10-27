<?php

use mindplay\sql\framework\MapperProvider;
use mindplay\sql\framework\Statement;
use mindplay\sql\model\query\Query;
use mindplay\sql\model\schema\Type;

use function mindplay\testies\{ eq, ok, inspect };

/**
 * @param string $sql
 *
 * @return string SQL with normalized whitespace
 */
function normalize_sql($sql)
{
    return preg_replace('/\s+/', ' ', $sql);
}

/**
 * @param Statement   $query
 * @param string      $expected_sql
 * @param string|null $why
 */
function sql_eq(Statement $query, $expected_sql, string|null $why = null)
{
    $actual = normalize_sql($query->getSQL());
    $expected = normalize_sql($expected_sql);

    eq($actual, $expected, $why);
}

/**
 * @param MapperProvider $query
 * @param string[]       $expected_types map where return variable name => Type class-name
 */
function check_return_types(MapperProvider $query, $expected_types)
{
    /** @var Type[] $types */
    $types = inspect($query->getMappers()[0], 'types');

    $num_types = count($expected_types);
    $num_vars = count($types);

    if ($num_types !== $num_vars) {
        ok(false, "type map count mismatch - expected: {$num_types}, got: {$num_vars}");
    }

    foreach ($expected_types as $var_name => $expected_type) {
        if (isset($types[$var_name])) {
            $type = $types[$var_name];

            ok(
                $type instanceof $expected_type,
                "return var '{$var_name}' should have type: {$expected_type}"
            );
        } else {
            ok(false, "return var '{$var_name}' is undefined");
        }
    }
}

/**
 * @param Query $query
 * @param array $expected_params map where placeholder name => value
 */
function check_params(Query $query, $expected_params)
{
    $params = $query->getParams();

    $expected_num_params = count($expected_params);
    $num_params = count($params);

    if ($expected_num_params !== $num_params) {
        ok(false, "parameter map count mismatch - expected: {$expected_num_params}, got: {$num_params}", $params);
    }

    foreach ($expected_params as $name => $expected_value) {
        if (array_key_exists($name, $params)) {
            eq($params[$name], $expected_value);
        } else {
            ok(false, "undefined parameter: {$name}");
        }
    }
}
