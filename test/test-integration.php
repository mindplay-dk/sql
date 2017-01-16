<?php

use mindplay\sql\framework\pdo\PDOProvider;
use mindplay\sql\mysql\MySQLDatabase;
use mindplay\sql\postgres\PostgresDatabase;

test(
    'can connect to Postgres',
    function () use ($config) {
        $provider = new PDOProvider(
            PDOProvider::PROTOCOL_POSTGRES,
            $config["postgres"]["database"],
            $config["postgres"]["user"],
            $config["postgres"]["password"]
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
            PDOProvider::PROTOCOL_POSTGRES,
            $config["mysql"]["database"],
            $config["mysql"]["user"],
            $config["mysql"]["password"]
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
