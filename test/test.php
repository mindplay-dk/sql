<?php

use mindplay\sql\drivers\MySQLDriver;
use mindplay\sql\drivers\PostgresDriver;
use mindplay\sql\framework\Connection;
use mindplay\sql\framework\Database;
use mindplay\sql\framework\RecordMapper;
use mindplay\sql\framework\RecordSetMapper;
use mindplay\sql\framework\SQLException;
use mindplay\sql\framework\Statement;
use Mockery\MockInterface;

require dirname(__DIR__) . '/vendor/autoload.php';

require __DIR__ . '/fixtures.php';

test(
    "can quote table-names",
    function () {
        $driver = new PostgresDriver();

        eq($driver->quoteName('foo'), '"foo"');
        eq($driver->quoteName('foo', 'bar'), '"foo"."bar"');

        $driver = new MySQLDriver();

        eq($driver->quoteName('foo'), '`foo`');
        eq($driver->quoteName('foo', 'bar'), '`foo`.`bar`');
    }
);

test(
    'can bootstrap Database',
    function () {
        $db = new Database(
            function () {
                return Mockery::mock(PDO::class);
            },
            create_driver()
        );

        ok($db->getConnection() instanceof Connection);
        ok($db->getConnection()->getPDO() instanceof PDO);

        $db = new Database(
            Mockery::mock(PDO::class),
            create_driver()
        );

        ok($db->getConnection()->getPDO() instanceof PDO, "can bootstrap with raw PDO connection object");
    }
);

test(
    'can generate human-readable SQL exceptions',
    function () {
        $previous = new RuntimeException();

        $exception = new SQLException(
            "SELECT :foo, :bar, :baz", [
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
        $driver = create_driver();
        $connection = new Connection($mock_pdo, $driver);

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('commit')->once();

        $result = $connection->transact(function () {
            return true;
        });

        Mockery::close();

        eq($result, true, "transaction succeeds");
    }
);

test(
    'transaction() commits when nested transactions succeed',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);
        $driver = create_driver();
        $connection = new Connection($mock_pdo, $driver);

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('commit')->once();

        $result = $connection->transact(function () use ($connection) {
            $connection->transact(function () {
                return true; // inner transcation succeeds
            });

            return true; // outer transaction succeeds
        });

        Mockery::close();

        eq($result, true, "transaction succeeds");
    }
);

test(
    'transact() rolls back when function returns false',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);
        $driver = create_driver();
        $connection = new Connection($mock_pdo, $driver);

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('rollBack')->once();

        $result = $connection->transact(function () {
            return false;
        });

        Mockery::close();

        eq($result, false, "transaction fails");
    }
);

test(
    'transact() rolls back when function returns void',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);
        $driver = create_driver();
        $connection = new Connection($mock_pdo, $driver);

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

        Mockery::close();

        ok(true, "transcation fails");
    }
);

test(
    'transact() rolls back when function throws an exception',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);
        $driver = create_driver();
        $connection = new Connection($mock_pdo, $driver);

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('rollBack')->once();

        expect(
            LogicException::class,
            "should throw when function throws an exception",
            function () use ($connection) {
                $connection->transact(function () {
                    throw new RuntimeException("oops!");
                });
            }
        );

        Mockery::close();

        ok(true, "transaction fails");
    }
);

test(
    'transact() rolls back when a nested call to transact() fails',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);
        $driver = create_driver();
        $connection = new Connection($mock_pdo, $driver);

        $mock_pdo->shouldReceive('beginTransaction')->once();
        $mock_pdo->shouldReceive('rollBack')->once();

        $result = $connection->transact(function () use ($connection) {
            $connection->transact(function () {
                return false; // inner function fails
            });

            return true; // outer transaction succeeds
        });

        Mockery::close();

        eq($result, false, "transaction fails");
    }
);

test(
    'transact() rolls back when one of several nested calls to transact() fails',
    function () {
        /** @var MockInterface|PDO $mock_pdo */
        $mock_pdo = Mockery::mock(PDO::class);
        $driver = create_driver();
        $connection = new Connection($mock_pdo, $driver);

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

        Mockery::close();

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
        $mapper = new RecordSetMapper(function ($records) {
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
        $st = new Statement("SELECT 1");

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
    'can prepare statements and bind scalar values',
    function () {
        /** @var MockInterface|PDO $pdo */
        $pdo = Mockery::mock(PDO::class);

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

        $connection = new Connection($pdo, create_driver());

        $pdo->shouldReceive('prepare')->once()->with($sql)->andReturn($handle);

        foreach ($params as $name => $value) {
            $handle->shouldReceive('bindValue')->once()->with($name, $value, $pdo_types[$name])->andReturn($handle);
        }

        $statement = new Statement($sql);

        $statement->apply($params);

        $connection->prepare($statement);

        Mockery::close();

        ok(true, "mock assertions completed");
    }
);

test(
    'can prepare statements and bind arrays of scalar values',
    function () {
        /** @var MockInterface|PDO $pdo */
        $pdo = Mockery::mock(PDO::class);

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

        $connection = new Connection($pdo, create_driver());

        $pdo->shouldReceive('prepare')->once()->with($expanded_sql)->andReturn($handle);

        foreach ($params as $name => $values) {
            $index = 1;

            foreach ($values as $value) {
                $handle->shouldReceive('bindValue')->once()->with("{$name}_{$index}", $value, $pdo_types[$name])->andReturn($handle);

                $index += 1;
            }
        }

        $statement = new Statement($sql);

        $statement->apply($params);
        $statement->bind('empty', []);

        $connection->prepare($statement);

        Mockery::close();

        ok(true, "mock assertions completed");
    }
);

test(
    'Connection throws on internal error condition',
    function () {
        /** @var MockInterface|PDO $pdo */
        $pdo = Mockery::mock(PDO::class);

        /** @var MockInterface|PDOStatement $handle */
        $handle = Mockery::mock(PDOStatement::class);

        $connection = new Connection($pdo, create_driver());

        expect(
            InvalidArgumentException::class,
            "internally throws on unexpected value",
            function () use ($connection, $handle) {
                invoke($connection, 'bind', [$handle, 'foo', [1, 2, 3]]);
            }
        );
    }
);

configure()->enableCodeCoverage(__DIR__ . '/build/clover.xml', dirname(__DIR__) . '/src');

exit(run());
