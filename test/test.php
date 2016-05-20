<?php

use mindplay\sql\exceptions\SQLException;
use mindplay\sql\framework\DatabaseContainer;
use mindplay\sql\framework\Executable;
use mindplay\sql\framework\MapperProvider;
use mindplay\sql\framework\mappers\BatchMapper;
use mindplay\sql\framework\mappers\RecordMapper;
use mindplay\sql\framework\PreparedStatement;
use mindplay\sql\framework\Query;
use mindplay\sql\framework\Result;
use mindplay\sql\framework\TypeProvider;
use mindplay\sql\model\Column;
use mindplay\sql\model\expr;
use mindplay\sql\model\SQLQuery;
use mindplay\sql\model\Type;
use mindplay\sql\mysql\MySQLDriver;
use mindplay\sql\pdo\PDOConnection;
use mindplay\sql\pdo\PreparedPDOStatement;
use mindplay\sql\postgres\PostgresDatabase;
use mindplay\sql\postgres\PostgresDriver;
use mindplay\sql\types\BoolType;
use mindplay\sql\types\IntType;
use mindplay\sql\types\JSONType;
use mindplay\sql\types\StringType;
use mindplay\sql\types\TimestampType;
use Mockery\MockInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

require __DIR__ . '/fixtures.php';

teardown(function () {
    Mockery::close();
});

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
 * @param Executable  $query
 * @param string      $expected_sql
 * @param string|null $why
 */
function sql_eq(Executable $query, $expected_sql, $why = null)
{
    eq(normalize_sql($query->getSQL()), normalize_sql($expected_sql), $why);
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

test(
    "can quote table-names",
    function () {
        $driver = new PostgresDriver();

        eq($driver->quoteName('foo'), '"foo"');

        $driver = new MySQLDriver();

        eq($driver->quoteName('foo'), '`foo`');
    }
);

test(
    'can generate human-readable SQL exceptions',
    function () {
        $previous = new RuntimeException();

        $exception = new SQLException(
            "SELECT :foo, :bar, :baz",
            [
                'foo' => 'hello',
                'bar' => 123,
                'baz' => null,
            ],
            "oops!",
            99,
            $previous
        );

        eq($exception->getMessage(), "oops!\nSELECT 'hello', 123, NULL");
        eq($exception->getCode(), 99);
        eq($exception->getPrevious(), $previous);
    }
);

test(
    'transaction() commits when function returns true',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);

        $connection = new PDOConnection($mock_pdo, create_driver());

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('commit')->once();

        $result = $connection->transact(function () {
            return true;
        });

        eq($result, true, "transaction succeeds");
    }
);

test(
    'transaction() commits when nested transactions succeed',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);

        $connection = new PDOConnection($mock_pdo, create_driver());

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('commit')->once();

        $result = $connection->transact(function () use ($connection) {
            $connection->transact(function () {
                return true; // inner transcation succeeds
            });

            return true; // outer transaction succeeds
        });

        eq($result, true, "transaction succeeds");
    }
);

test(
    'transact() rolls back when function returns false',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);

        $connection = new PDOConnection($mock_pdo, create_driver());

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('rollBack')->once();

        $result = $connection->transact(function () {
            return false;
        });

        eq($result, false, "transaction fails");
    }
);

test(
    'transact() rolls back when function returns void',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);

        $connection = new PDOConnection($mock_pdo, create_driver());

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('rollBack')->once();

        expect(
            UnexpectedValueException::class,
            "should throw when function fails to indicate success/failure",
            function () use ($connection) {
                $connection->transact(function () {
                    // return void
                });
            }
        );

        ok(true, "transcation fails");
    }
);

test(
    'transact() rolls back and re-throws when function throws an exception',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);

        $connection = new PDOConnection($mock_pdo, create_driver());

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('rollBack')->once();

        expect(
            RuntimeException::class,
            "should re-throw when function throws an exception",
            function () use ($connection) {
                $connection->transact(function () {
                    throw new RuntimeException("oops!");
                });
            }
        );

        ok(true, "transaction fails");
    }
);

test(
    'transact() rolls back and re-throws when nested function throws an exception',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);

        $connection = new PDOConnection($mock_pdo, create_driver());

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('rollBack')->once();

        expect(
            RuntimeException::class,
            "should re-throw when function throws an exception",
            function () use ($connection) {
                $connection->transact(function () use ($connection) {
                    return $connection->transact(function () {
                        throw new RuntimeException("oops!");
                    });
                });
            },
            "/oops/"
        );

        ok(true, "transaction fails");
    }
);

test(
    'transact() rolls back when a nested call to transact() fails',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);

        $connection = new PDOConnection($mock_pdo, create_driver());

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('rollBack')->once();

        $result = $connection->transact(function () use ($connection) {
            $connection->transact(function () {
                return false; // inner function fails
            });

            return true; // outer transaction succeeds
        });

        eq($result, false, "transaction fails");
    }
);

test(
    'transact() rolls back when one of several nested calls to transact() fails',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);

        $connection = new PDOConnection($mock_pdo, create_driver());

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('rollBack')->once();

        $result = $connection->transact(function () use ($connection) {
            $connection->transact(function () {
                return true; // first inner function succeeds
            });

            $connection->transact(function () {
                return false; // second inner function fails
            });

            $connection->transact(function () {
                return true; // third inner function succeeds
            });

            return true; // outer transaction succeeds
        });

        eq($result, false, "transaction fails");
    }
);

test(
    'can map individual records',
    function () {
        $mapper = new RecordMapper(function ($record) {
            return ['a' => $record['a'] * 10];
        });

        eq($mapper->map([['a' => 1], ['a' => 2], ['a' => 3]]), [['a' => 10], ['a' => 20], ['a' => 30]]);
    }
);

test(
    'can map sets of records',
    function () {
        $mapper = new BatchMapper(function ($records) {
            return array_map(
                function ($record) {
                    return ['a' => $record['a'] * 10];
                },
                $records
            );
        });

        eq($mapper->map([['a' => 1], ['a' => 2], ['a' => 3]]), [['a' => 10], ['a' => 20], ['a' => 30]]);
    }
);

test(
    'can bind statement parameters to values',
    function () {
        $st = create_db()->sql("SELECT 1");

        eq($st->getSQL(), "SELECT 1");

        $st->bind('int', 1);
        $st->bind('float', 1.2);
        $st->bind('string', 'hello');
        $st->bind('true', true);
        $st->bind('false', false);
        $st->bind('null', null);

        $st->bind('int_list', [1, 2]);
        $st->bind('float_list', [1.2, 3.4]);
        $st->bind('string_list', ['hello', 'world']);
        $st->bind('bools', [true, false]);
        $st->bind('nulls', [null, null]);

        $st->apply(['int' => 2, 'foo' => 'bar']); // overrides/adds values

        eq($st->getParams(), [
            'int'    => 2,
            'float'  => 1.2,
            'string' => 'hello',
            'true'   => true,
            'false'  => false,
            'null'   => null,

            'int_list'    => [1, 2],
            'float_list'  => [1.2, 3.4],
            'string_list' => ['hello', 'world'],
            'bools'       => [true, false],
            'nulls'       => [null, null],
            'foo'         => 'bar',
        ]);

        expect(
            RuntimeException::class,
            "rejects non-scalar values",
            function () use ($st) {
                $st->bind('foo', (object) []);
            }
        );

        expect(
            RuntimeException::class,
            "rejects nested arrays",
            function () use ($st) {
                $st->bind('foo', [[1]]);
            }
        );
    }
);

test(
    'can prepare PDO statements and bind scalar values',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);

        /** @var MockInterface|PDOStatement $handle */
        $handle = Mockery::mock(PDOStatement::class);

        $params = [
            'int'    => 1,
            'float'  => 1.2,
            'string' => 'hello',
            'true'   => true,
            'false'  => false,
            'null'   => null,
        ];

        $pdo_types = [
            'int' => PDO::PARAM_INT,
            'float' => PDO::PARAM_STR,
            'string' => PDO::PARAM_STR,
            'true' => PDO::PARAM_BOOL,
            'false' => PDO::PARAM_BOOL,
            'null' => PDO::PARAM_NULL,
        ];

        $sql = "SELECT * FROM foo WHERE " . implode(" AND ", array_map(function ($name) { return "{$name} = :{$name}"; }, array_keys($params)));
        
        $preparator = new PDOConnection($mock_pdo, create_driver());

        $statement = new SQLQuery(Mockery::mock(TypeProvider::class), $sql);

        $mock_pdo->shouldReceive('prepare')->once()->with($sql)->andReturn($handle);

        foreach ($params as $name => $value) {
            $statement->bind($name, $value);

            $handle->shouldReceive('bindValue')->once()->with($name, $value, $pdo_types[$name])->andReturn($handle);
        }

        $preparator->prepare($statement);

        ok(true, "mock assertions completed");
    }
);

test(
    'can prepare PDO statements and bind arrays of scalar values',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);

        /** @var MockInterface|PDOStatement $handle */
        $handle = Mockery::mock(PDOStatement::class);

        $params = [
            'int'    => [1, 2],
            'float'  => [1.2, 3.4],
            'string' => ['hello', 'world'],
            'bool'   => [true, false],
        ];

        $pdo_types = [
            'int'    => PDO::PARAM_INT,
            'float'  => PDO::PARAM_STR,
            'string' => PDO::PARAM_STR,
            'bool'   => PDO::PARAM_BOOL,
            'null'   => PDO::PARAM_NULL,
        ];

        // the following conditions will assert that e.g. ":int" for an array with 2 elements expands to a set like "(:int_1, :int_2)"
        // and that ":empty" for an array with zero elements expands to the empty set, e.g. "(null)" (and doesn't bind any value)

        $sql = "SELECT * FROM foo WHERE empty = :empty AND " . implode(" AND ", array_map(function ($name) { return "{$name} IN :{$name}"; }, array_keys($params)));

        $expanded_sql = "SELECT * FROM foo WHERE empty = (null) AND " . implode(" AND ", array_map(function ($name) { return "{$name} IN (:{$name}_1, :{$name}_2)"; }, array_keys($params)));

        $connection = new PDOConnection($mock_pdo, create_driver());

        $mock_pdo->shouldReceive('prepare')->once()->with($expanded_sql)->andReturn($handle);

        foreach ($params as $name => $values) {
            $index = 1;

            foreach ($values as $value) {
                $handle->shouldReceive('bindValue')->once()->with("{$name}_{$index}", $value, $pdo_types[$name])->andReturn($handle);

                $index += 1;
            }
        }

        $statement = new SQLQuery(Mockery::mock(TypeProvider::class), $sql);

        $statement->apply($params);
        $statement->bind('empty', []);

        $connection->prepare($statement);

        ok(true, "mock assertions completed");
    }
);

test(
    'prepared statements can re-bind parameters to scalar values',
    function () {
        /** @var MockInterface|PDOStatement $mock_handle */
        $mock_handle = Mockery::mock(PDOStatement::class);

        $mock_handle->shouldReceive('bindValue')->with('int', 1, PDO::PARAM_INT)->once();
        $mock_handle->shouldReceive('bindValue')->with('float', 1.2, PDO::PARAM_STR)->once();
        $mock_handle->shouldReceive('bindValue')->with('string', 'hello', PDO::PARAM_STR)->once();
        $mock_handle->shouldReceive('bindValue')->with('true', true, PDO::PARAM_BOOL)->once();
        $mock_handle->shouldReceive('bindValue')->with('false', false, PDO::PARAM_BOOL)->once();
        $mock_handle->shouldReceive('bindValue')->with('null', false, PDO::PARAM_NULL)->once();

        $st = new PreparedPDOStatement($mock_handle, create_driver());

        $st->bind('int', 1);
        $st->bind('float', 1.2);
        $st->bind('string', 'hello');
        $st->bind('true', true);
        $st->bind('false', false);
        $st->bind('null', null);

        expect(
            InvalidArgumentException::class,
            "rejects non-scalar values",
            function () use ($st) {
                $st->bind('foo', (object) []);
            }
        );

        expect(
            InvalidArgumentException::class,
            "rejects arrays",
            function () use ($st) {
                $st->bind('foo', [1]);
            }
        );
    }
);

// NOTE: the following test cannot be implemented, because PDOStatement::$queryString is readonly!
//
//test(
//    'can execute prepared statement',
//    function () {
//        /** @var MockInterface|PDOStatement $mock_handle */
//        $mock_handle = Mockery::mock(PDOStatement::class);
//
//        $mock_handle->queryString = "SELECT 1";
//
//        $mock_handle->shouldReceive('execute')->andReturn(true)->once();
//
//        $st = new PreparedPDOStatement($mock_handle);
//
//        $st->execute();
//
//        ok(true, 'it executes without error');
//
//        $mock_handle = Mockery::mock(PDOStatement::class);
//
//        $mock_handle->shouldReceive('execute')->andReturn(false)->once();
//        $mock_handle->shouldReceive('errorInfo')->andReturn(['XXXXXX', -1, 'ouch'])->once();
//
//        $st = new PreparedPDOStatement($mock_handle);
//
//        try {
//            $st->execute();
//        } catch (SQLException $sql_exception) {
//            // caught
//        }
//
//        ok(isset($sql_exception));
//
//        eq($sql_exception->getCode(), -1);
//        eq($sql_exception->getMessage(), "XXXXXX: ouch");
//    }
//);

test(
    'can fetch; and auto-executes prepared statement on first fetch',
    function () {
        /** @var MockInterface|PDOStatement $mock_handle */
        $mock_handle = Mockery::mock(PDOStatement::class);

        $mock_handle->shouldReceive('execute')->andReturn(true)->once();
        $mock_handle->shouldReceive('fetch')->andReturn(['a' => 1])->once();
        $mock_handle->shouldReceive('fetch')->andReturn(['a' => 2])->once();
        $mock_handle->shouldReceive('fetch')->andReturn(false);

        $st = new PreparedPDOStatement($mock_handle, create_driver());

        eq($st->fetch(), ['a' => 1]);
        eq($st->fetch(), ['a' => 2]);
        eq($st->fetch(), null);
    }
);

test(
    'can apply Mapper to records',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);

        $db = create_db();
        
        /** @var MockInterface|PDOStatement $mock_statement */
        $mock_statement = Mockery::mock(PDOStatement::class);

        $mock_pdo
            ->shouldReceive('prepare')
            ->once()
            ->andReturn($mock_statement);

        $mock_statement
            ->shouldReceive('execute')
            ->once()
            ->andReturn(true);

        $mock_statement
            ->shouldReceive('fetch')
            ->once()
            ->andReturn(['id' => '123', 'first_name' => 'Rasmus']);

        $mock_statement
            ->shouldReceive('fetch')
            ->once()
            ->andReturn(false);

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $user = $schema->user;

        $query = $db
            ->select($user)
            ->columns([$user->id, $user->first_name])
            ->mapRecords(function ($record) {
                $record['mapped'] = true;

                return $record;
            });

        $connection = new PDOConnection($mock_pdo, create_driver());

        $result = $connection->fetch($query)->all();
        
        eq(
            $result,
            [
                ['id' => 123, 'first_name' => 'Rasmus', 'mapped' => true],
            ]
        );

        Mockery::close();
    }
);

test(
    'can fetch records and apply Mappers in batches',
    function () {
        foreach ([30,20] as $num_records) {
            /** @var MockInterface|PreparedStatement $mock_statement */
            $mock_statement = Mockery::mock(PreparedPDOStatement::class);

            $mock_statement
                ->shouldReceive('fetch')
                ->times($num_records)
                ->andReturnValues(array_map(function ($id) use ($num_records) { return ['id' => $id]; }, range(1, $num_records)));

            $mock_statement
                ->shouldReceive('fetch')
                ->once()
                ->andReturn(null);

            $batch_num = 0;

            $mappers = [new BatchMapper(function (array $records) use (&$batch_num) {
                $batch_num += 1;

                foreach ($records as &$record) {
                    $record['batch_num'] = $batch_num;
                }

                return $records;
            })];

            $result = new Result($mock_statement, 20, $mappers);

            foreach ($result as $index => $record) {
                eq($record['id'], $index + 1);
                eq($record['batch_num'], (int) floor($index / 20) + 1, $record['batch_num']);
            }
        }
    }
);

test(
    'can fetch first row of a result set',
    function () {
        /** @var MockInterface|PreparedStatement $mock_statement */
        $mock_statement = Mockery::mock(PreparedPDOStatement::class);

        $mock_statement->shouldReceive('fetch')->andReturn(['id' => 1])->once();

        $calls = [];

        $mappers = [new RecordMapper(function (array $record) use (&$calls) {
            $calls[] = $record;

            return $record;
        })];

        $result = new Result($mock_statement, 20, $mappers);

        $record = $result->firstRow();

        eq($record, ['id' => 1], 'should return first row');

        eq($calls, [['id' => 1]], 'should process precisely one record (disregarding batch size)');
    }
);

test(
    'can fetch first column of a result set',
    function () {
        /** @var MockInterface|PreparedStatement $mock_statement */
        $mock_statement = Mockery::mock(PreparedPDOStatement::class);

        $mock_statement->shouldReceive('fetch')->andReturn(['id' => 1])->once();

        $calls = [];

        $mappers = [new RecordMapper(function (array $record) use (&$calls) {
            $calls[] = $record;

            return $record;
        })];

        $result = new Result($mock_statement, 20, $mappers);

        $record = $result->firstCol();

        eq($record, 1, 'should return first column of first record');

        eq($calls, [['id' => 1]], 'should process precisely one record (disregarding batch size)');
    }
);

test(
    'can map DATETIME to Unix timestamps',
    function () {
        $type = new TimestampType();

        $valid_datetime = '2015-11-04 14:40:52';
        $valid_timestamp = 1446648052;

        eq($type->convertToPHP($valid_datetime), $valid_timestamp, "can convert to PHP value");
        eq($type->convertToSQL($valid_timestamp), $valid_datetime, "can convert to SQL DATETIME value");
    }
);

test(
    'can map PHP values to JSON',
    function () {
        $type = new JSONType();

        $valid_value = ['foo' => 'bar'];
        $valid_json = '{"foo":"bar"}';

        eq($type->convertToPHP($valid_json), $valid_value, "can convert to PHP value");
        eq($type->convertToSQL($valid_value), $valid_json, "can convert to SQL JSON value");
    }
);

test(
    'can map bool values to SQL',
    function () {
        $type = new BoolType();
        
        eq($type->convertToPHP(true), true);
        eq($type->convertToPHP(false), false);
        
        eq($type->convertToSQL(true), true);
        eq($type->convertToSQL(false), false);

        $type = BoolType::asInt();

        eq($type->convertToPHP(1), true);
        eq($type->convertToPHP(0), false);

        eq($type->convertToSQL(true), 1);
        eq($type->convertToSQL(false), 0);

        $type = BoolType::asEnum("yes", "no");
        
        eq($type->convertToPHP('yes'), true);
        eq($type->convertToPHP('no'), false);

        eq($type->convertToSQL(true), 'yes');
        eq($type->convertToSQL(false), 'no');
    }
);

test(
    'can define Schema model',
    function () {
        $db = create_db();

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $user = $schema->user;

        ok($user instanceof UserTable);

        ok($user->first_name instanceof Column);

        eq($user->first_name->getName(), 'first_name');
        eq($user->first_name->getAlias(), null);
        eq($user->first_name->__toString(), '`user`.`first_name`');

        eq($user->first_name('foo')->getName(), 'first_name');
        eq($user->first_name('foo')->getAlias(), 'foo');
        eq($user->first_name('foo')->__toString(), '`user`.`first_name`'); // *

        eq($schema->user('foo')->first_name->__toString(), '`foo`.`first_name`'); // *
        eq($schema->user('foo')->first_name('bar')->__toString(), '`foo`.`first_name`'); // *

        // * NOTE: column aliases do not affect __toString() magic, because we don't know whether
        //         an aliased Column has also been selected - we therefore always refer to the
        //         column using qualified "{table or alias}.{column}" notation.

        $columns = $user->listColumns();

        eq(count($columns), 6);

        foreach ($columns as $column) {
            ok($column instanceof Column);
        }

        $columns = $schema->order->listColumns("foo");

        eq(count($columns), 2);
        
        foreach ($columns as $column) {
            eq($column->getAlias(), "foo_" . $column->getName());
        }
    }
);

// TODO IntType test

// TODO StringType test

test(
    'can bind values to query models',
    function () {
        $db = new DatabaseContainer(new MySQLDriver());

        $query = new MockQuery($db);

        $query->bind('int', 123);

        $valid_datetime = '2015-11-04 14:40:52';
        $valid_timestamp = 1446648052;

        $query->bind('date', $valid_timestamp, TimestampType::class);

        $params = $query->getParams();

        eq($params['int'], 123, 'can bind scalar value');
        eq($params['date'], $valid_datetime, 'can bind value using Type conversion');
    }
);

test(
    'can build and/or expressions',
    function () {
        eq(expr::any(['a']), 'a');
        eq(expr::any(['a', 'b']), '(a OR b)');
        eq(expr::any(['a', 'b', 'c']), '(a OR b OR c)');

        expect(RuntimeException::class, 'shoud throw for empty array', function () { expr::any([]); });

        eq(expr::all(['a']), 'a');
        eq(expr::all(['a', 'b']), '(a AND b)');
        eq(expr::all(['a', 'b', 'c']), '(a AND b AND c)');

        expect(RuntimeException::class, 'shoud throw for empty array', function () { expr::all([]); });
    }
);

test(
    'can build SELECT queries',
    function () {
        $db = create_db();

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        sql_eq(
            $db->select($schema->user),
            'SELECT `user`.* FROM `user`',
            'auto-selects everything from root node'
        );

        sql_eq(
            $db->select($schema->user('u')),
            'SELECT `u`.* FROM `user` AS `u`',
            'auto-select with root alias'
        );

        $user = $schema->user;

        sql_eq(
            $db->select($user)
                ->columns([$user->first_name, $user->last_name])
                ->where("{$user->first_name} LIKE :name"),
            'SELECT `user`.`first_name`, `user`.`last_name` FROM `user` WHERE `user`.`first_name` LIKE :name',
            'manually select columns'
        );

        sql_eq(
            $db->select($user)
                ->columns([$user->first_name('first'), $user->last_name('last')])
                ->where("{$user->first_name('first')} LIKE :name"),
            'SELECT `user`.`first_name` AS `first`, `user`.`last_name` AS `last` FROM `user` WHERE `user`.`first_name` LIKE :name',
            'manually select aliased columns'
        );

        $user = $schema->user('u');

        sql_eq(
            $db->select($user)
                ->columns([$user->first_name, $user->last_name])
                ->where("{$user->first_name} LIKE :name"),
            'SELECT `u`.`first_name` AS `u_first_name`, `u`.`last_name` AS `u_last_name` FROM `user` AS `u` WHERE `u`.`first_name` LIKE :name',
            'manually select columns using a table alias'
        );

        sql_eq(
            $db->select($user)
                ->columns([$user->first_name('first'), $user->last_name('last')])
                ->where("{$user->first_name('first')} LIKE :name"),
            'SELECT `u`.`first_name` AS `first`, `u`.`last_name` AS `last` FROM `user` AS `u` WHERE `u`.`first_name` LIKE :name',
            'manually select aliased columns using a table alias'
        );

        $user = $schema->user;

        sql_eq(
            $db->select($user),
            'SELECT `user`.* FROM `user`',
            'auto-select table'
        );

        sql_eq(
            $db->select($user)->table($user),
            'SELECT `user`.* FROM `user`',
            'manually select tables'
        );

        sql_eq(
            $db->select($user)
                ->columns([$user->first_name, $user->last_name])
                ->where([
                    "{$user->first_name} LIKE :first",
                    "{$user->last_name} LIKE :last"
                ])
                ->order("{$user->first_name} ASC")
                ->order("{$user->last_name} ASC")
                ->page(1, 20),
            'SELECT `user`.`first_name`, `user`.`last_name` FROM `user` WHERE `user`.`first_name` LIKE :first AND `user`.`last_name` LIKE :last ORDER BY `user`.`first_name` ASC, `user`.`last_name` ASC LIMIT 20 OFFSET 0',
            'select with multiple WHERE conditions, multiple ORDER BY terms, and LIMIT and OFFSET'
        );
    }
);

test(
    'can map return vars to types',
    function () {
        $db = create_db();

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $user = $schema->user;

        $expected_types = [
            'id'              => IntType::class,
            'first_name'      => StringType::class,
            'last_name'       => StringType::class,
            'dob'             => TimestampType::class,
            'home_address_id' => IntType::class,
            'deleted'         => BoolType::class,
        ];

        check_return_types($db->select($user), $expected_types);

        check_return_types($db->select($user)->table($user), $expected_types);

        check_return_types(
            $db->select($user)
                ->value("CONCAT({$user->first_name}, ' ', {$user->last_name})", "full_name")
                ->value("DATE_DIFF(NOW(), {$user->dob})", "age", IntType::class),
            [
                'age' => IntType::class,
            ]
        );
    }
);

test(
    'can build nested SELECT queries',
    function () {
        $db = create_db();

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $user = $schema->user;
        
        $home_address = $schema->address('home_address');
        $work_address = $schema->address('work_address');

        $order = $schema->order;
        
        $num_orders = $db
            ->select($order)
            ->value("COUNT(`order_id`)")
            ->where([
                "{$order->user_id} = {$user->id}",
                "{$order->completed} >= :order_date"
            ]);

        $query = $db
            ->select($user)
            ->columns([$user->first_name, $user->last_name])
            ->innerJoin($home_address, "{$home_address->id} = {$user->home_address_id}")
            ->innerJoin($work_address, "{$work_address->id} = {$user->home_address_id}")
            ->columns([$home_address->street_name, $work_address->street_name])
            ->value("NOW()", "now", TimestampType::class)
            ->where([
                "{$user->first_name} LIKE :first_name",
                "{$user->dob} = :dob",
                expr::any([
                    "{$home_address->street_name} LIKE :street_name",
                    "{$work_address->street_name} LIKE :street_name"
                ])
            ])
            ->value($num_orders, "num_orders", IntType::class)
            ->where("{$num_orders} > 3")
            ->bind("order_date", strtotime('2015-03-20'), TimestampType::class)
            ->bind("first_name", "rasmus")
            ->bind("street_name", "dronningensgade")
            ->bind("dob", strtotime('1975-07-07'), TimestampType::class)
            ->bind("groups", [1,2,3]);

        $expected_sql = <<<'SQL'
SELECT
    `user`.`first_name`,
    `user`.`last_name`,
    `home_address`.`street_name` AS `home_address_street_name`,
    `work_address`.`street_name` AS `work_address_street_name`,
    NOW() AS `now`,
    (SELECT COUNT(`order_id`) FROM `order` WHERE `order`.`user_id` = `user`.`id` AND `order`.`completed` >= :order_date) AS `num_orders`
FROM `user`
    INNER JOIN `address` AS `home_address` ON `home_address`.`id` = `user`.`home_address_id`
    INNER JOIN `address` AS `work_address` ON `work_address`.`id` = `user`.`home_address_id`
WHERE
    `user`.`first_name` LIKE :first_name
    AND `user`.`dob` = :dob
    AND (`home_address`.`street_name` LIKE :street_name OR `work_address`.`street_name` LIKE :street_name)
    AND (SELECT COUNT(`order_id`) FROM `order` WHERE `order`.`user_id` = `user`.`id` AND `order`.`completed` >= :order_date) > 3
SQL;
        
        sql_eq($query, $expected_sql);
    }
);

test(
    'can build SELECT..GROUP BY..HAVING queries',
    function () {
        $db = create_db();

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $user = $schema->user;
        $order = $schema->order;

        $query = $db
            ->select($order)
            ->table($user)
            ->value("COUNT({$order->completed}) AS completed_orders")
            ->innerJoin($user, "{$order->user_id} = {$user->id}")
            ->groupBy($order->user_id)
            ->having("completed_orders > 0");

        $expected_sql = <<<'SQL'
SELECT
    `user`.*,
    COUNT(`order`.`completed`) AS completed_orders
FROM `order`
    INNER JOIN `user` ON `order`.`user_id` = `user`.`id`
GROUP BY `order`.`user_id`
HAVING completed_orders > 0
SQL;

        sql_eq($query, $expected_sql);
    }
);

test(
    'can create INSERT queries',
    function () {
        $db = create_db();

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $valid_datetime = '2015-11-04 14:40:52';
        $valid_timestamp = 1446648052;

        $insert = $db
            ->insert($schema->order)
            ->add(["user_id" => 123, "completed" => $valid_timestamp]);

        sql_eq(
            $insert,
            'INSERT INTO `order` (`user_id`, `completed`) VALUES (:c0_0, :c0_1)'
        );

        $params = $insert->getParams();

        eq($params['c0_0'], 123, 'binds values to parameters');
        eq($params['c0_1'], $valid_datetime, 'performs type conversion on input');

        sql_eq(
            $db->insert($schema->address)->add(['street_name' => 'One Street']),
            'INSERT INTO `address` (`street_name`) VALUES (:c0_1)',
            'can insert multiple columns'
        );

        sql_eq(
            $db->insert($schema->address)->add(['street_name' => 'One Street'])->add(['street_name' => 'Two Street']),
            'INSERT INTO `address` (`street_name`) VALUES (:c0_1), (:c1_1)',
            'can insert multiple rows'
        );
    }
);

test(
    'can create UPDATE query',
    function () {
        $db = create_db();

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $user = $schema->user;

        $query = $db->update($user)
            ->setValue($user->first_name, "Rasmus")
            ->where("{$user->id} = :id")
            ->bind("id", 123);

        $expected_sql = <<<'SQL'
UPDATE `user`
SET `first_name` = :user_first_name
WHERE `user`.`id` = :id
SQL;

        sql_eq($query, $expected_sql);

        check_params($query, [
            'user_first_name' => 'Rasmus',
            'id'              => 123,
        ]);
    }
);

test(
    'can create UPDATE query with alias',
    function () {
        $db = create_db();

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $user = $schema->user('u');

        $query = $db->update($user)
            ->setValue($user->first_name, "Rasmus")
            ->setValue($user->dob, 173923200)
            ->setExpr($user->last_name, "'Schultz'")
            ->where("{$user->id} = :id")
            ->bind("id", 123);

        $expected_sql = <<<'SQL'
UPDATE `user` AS `u`
SET `first_name` = :u_first_name,
    `dob` = :u_dob,
    `last_name` = 'Schultz'
WHERE `u`.`id` = :id
SQL;

        sql_eq($query, $expected_sql);

        check_params($query, [
            'u_first_name' => 'Rasmus',
            'u_dob'        => '1975-07-07 00:00:00',
            'id'           => 123,
        ]);
    }
);

test(
    'can create UPDATE query with bulk assignment',
    function () {
        $db = create_db();

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $user = $schema->user;

        $query = $db->update($user)
            ->assign([
                'first_name' => 'Rasmus',
                'dob'        => 173923200,
            ])
            ->where("{$user->id} = :id")
            ->bind("id", 123);

        $expected_sql = <<<'SQL'
UPDATE `user`
SET `first_name` = :user_first_name,
    `dob` = :user_dob
WHERE `user`.`id` = :id
SQL;

        sql_eq($query, $expected_sql);

        check_params($query, [
            'user_first_name' => 'Rasmus',
            'user_dob'        => '1975-07-07 00:00:00',
            'id'              => 123,
        ]);
    }
);

test(
    'can create DELETE query',
    function () {
        $db = create_db();

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $user = $schema->user;

        $query = $db->delete($user)
            ->where("{$user->id} = :id")
            ->bind("id", 123)
            ->limit(1);

        $expected_sql = <<<'SQL'
DELETE FROM `user`
WHERE `user`.`id` = :id
LIMIT 1
SQL;

        sql_eq($query, $expected_sql);

        check_params($query, [
            'id' => 123,
        ]);
    }
);

test(
    'can build INSERT RETURNING query',
    function () {
        $db = new PostgresDatabase();

        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $address = $schema->address;

        $query = $db
            ->insert($address)
            ->add(['street_name' => "Fake Street"])
            ->returningColumns($address->id);

        $expected_sql = <<<'SQL'
INSERT INTO "address" ("street_name")
VALUES (:c0_1)
RETURNING "address"."id"
SQL;

        sql_eq($query, $expected_sql);

        check_params($query, ['c0_1' => 'Fake Street']);
        
        check_return_types($query, [
            'id' => IntType::class
        ]);
    }
);

// TODO tests for UPDATE RETURNING and DELETE RETURNING 

// TODO can't write a meaningful unit-test getRowsAffected() - needs an integration test
//
//test(
//    'can get rows affected',
//    function () {
//        /** @var PDO|MockInterface $mock_pdo */
//        $mock_pdo = Mockery::mock(PDO::class);
//
//        /** @var PDOStatement|MockInterface $mock_statement */
//        $mock_statement = Mockery::mock(PDOStatement::class);
//
//        $mock_pdo->shouldReceive('prepare')->andReturn($mock_statement);
//
//        $mock_statement->shouldReceive('execute')->andReturn(true);
//
//        $mock_statement->shouldReceive('rowCount')->andReturn(1);
//        
//        $connection = new PDOConnection($mock_pdo);
//        
//        $statement = new PreparedPDOStatement($mock_statement);
//        
//        eq($connection->execute($statement)->getRowsAffected(), 1);
//    }
//);

// TODO integration test for Connection::lastInsertId()

// TODO integration test for driver-generated SQLException-types

configure()->enableCodeCoverage(__DIR__ . '/build/clover.xml', dirname(__DIR__) . '/src');

exit(run());
