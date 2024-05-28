<?php

use mindplay\sql\framework\pdo\PDOProvider;
use mindplay\sql\mysql\MySQLDatabase;
use mindplay\sql\postgres\PostgresDatabase;
use mindplay\sql\exceptions\SQLException;

use function mindplay\testies\{ test, eq, expect };

$config = file_exists(__DIR__ . '/config.json')
    ? json_decode(file_get_contents(__DIR__ . '/config.json'), true)
    : json_decode(file_get_contents(__DIR__ . '/config.dist.json'), true);

test(
    'can connect to Postgres',
    function () use ($config) {
        $provider = new PDOProvider(
            PDOProvider::PROTOCOL_POSTGRES,
            ...$config["postgres"]
        );

        $db = new PostgresDatabase();

        $connection = $db->createConnection($provider->getPDO());

        eq($connection->fetch($db->sql('SELECT 123'))->firstCol(), 123);
    }
);

test(
    'can connect to MySQL',
    function () use ($config) {
        $provider = new PDOProvider(
            PDOProvider::PROTOCOL_MYSQL,
            ...$config["mysql"]
        );

        $db = new MySQLDatabase();

        $connection = $db->createConnection($provider->getPDO());

        eq($connection->fetch($db->sql('SELECT 123'))->firstCol(), 123);
    }
);

test(
    'handles PDO::ERRMODE_EXCEPTION and produces an SQLException',
    function () use ($config) {
        $postgres_config = [
            ...$config["postgres"],
            "options" => [
                ...$config["postgres"]["options"] ?? [],
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        ];

        $provider = new PDOProvider(
            PDOProvider::PROTOCOL_POSTGRES,
            ...$postgres_config
        );

        $db = new PostgresDatabase();
        
        $connection = $db->createConnection($provider->getPDO());

        expect(
            SQLException::class,
            'pdo exception mode is handled',
            function () use ($connection, $db) {
                $connection->fetch($db->sql('invalid syntax'))->firstCol();
            },
            ['/42601: ERROR:  syntax error/']
        );
    }
);

// TODO tests for prepared statements

// TODO test for PreparedStatement::getRowsAffected()

// TODO integration test for Connection::lastInsertId()

// TODO integration test for driver-generated SQLException-types
