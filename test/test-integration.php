<?php

use mindplay\sql\framework\pdo\PDOProvider;
use mindplay\sql\mysql\MySQLDatabase;
use mindplay\sql\postgres\PostgresDatabase;

use function mindplay\testies\{ test, eq };

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

// TODO tests for prepared statements

// TODO test for PreparedStatement::getRowsAffected()

// TODO integration test for Connection::lastInsertId()

// TODO integration test for driver-generated SQLException-types
