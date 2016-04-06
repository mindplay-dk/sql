<?php

use mindplay\sql\drivers\MySQLDriver;
use mindplay\sql\drivers\PostgresDriver;
use mindplay\sql\framework\Connection;
use mindplay\sql\framework\Database;
use mindplay\sql\framework\RecordMapper;
use mindplay\sql\framework\RecordSetMapper;
use mindplay\sql\framework\SQLException;
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
                'baz' => null
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

configure()->enableCodeCoverage(__DIR__ . '/build/clover.xml', dirname(__DIR__) . '/src');

exit(run());
