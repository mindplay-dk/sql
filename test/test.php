<?php

use mindplay\sql\exceptions\SQLException;
use mindplay\sql\exceptions\TransactionAbortedException;
use mindplay\sql\framework\BufferedPSRLogger;
use mindplay\sql\framework\MapperProvider;
use mindplay\sql\framework\mappers\BatchMapper;
use mindplay\sql\framework\mappers\RecordMapper;
use mindplay\sql\framework\pdo\PDOProvider;
use mindplay\sql\framework\pdo\PreparedPDOStatement;
use mindplay\sql\framework\PreparedStatement;
use mindplay\sql\framework\QueryFormatter;
use mindplay\sql\framework\Result;
use mindplay\sql\framework\Statement;
use mindplay\sql\model\DatabaseContainerFactory;
use mindplay\sql\model\expr;
use mindplay\sql\model\query\Query;
use mindplay\sql\model\schema\Column;
use mindplay\sql\model\schema\Type;
use mindplay\sql\model\types\BoolType;
use mindplay\sql\model\types\IntType;
use mindplay\sql\model\types\JSONType;
use mindplay\sql\model\types\StringType;
use mindplay\sql\model\types\TimestampType;
use mindplay\sql\mysql\MySQLConnection;
use mindplay\sql\mysql\MySQLDatabase;
use mindplay\sql\postgres\PostgresConnection;
use mindplay\sql\postgres\PostgresDatabase;
use Mockery\MockInterface;
use Psr\Log\LogLevel;

require dirname(__DIR__) . '/vendor/autoload.php';

require __DIR__ . '/fixtures.php';
require __DIR__ . '/helpers.php';

$config = json_decode(
    file_get_contents(file_exists(__DIR__ . '/config.json')
        ? __DIR__ . '/config.json'
        : __DIR__ . '/config.dist.json' // fall back on default settings (these will work on travis)
    ),
    true // $assoc
);

teardown(function () {
    Mockery::close();
});

$suites = ["unit", "integration"];

$suites = array_keys(getopt("", $suites)) ?: $suites;

foreach ($suites as $suite) {
    require __DIR__ . "/test-{$suite}.php";
}

configure()->enableCodeCoverage(__DIR__ . '/build/clover.xml', dirname(__DIR__) . '/src');

exit(run());
