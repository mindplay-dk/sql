<?php

use mindplay\benchpress\Benchmark;
use mindplay\sql\drivers\MySQLDriver;
use mindplay\sql\framework\DatabaseContainer;
use mindplay\sql\model\expr;
use mindplay\sql\types\IntType;
use mindplay\sql\types\TimestampType;

require dirname(__DIR__) . '/vendor/autoload.php';

require __DIR__ . '/fixtures.php';

$bench = new Benchmark();

$db = create_db();

$bench->add(
    "build simple SELECT query",
    function () use ($db) {
        /** @var SampleSchema $schema */
        $schema = $db->getSchema(SampleSchema::class);

        $user = $schema->user;

        $query = $db->select($user)->order("{$user->first_name}, {$user->last_name}")->page(1, 20);

        $sql = $query->getTemplate()->getSQL();
    }
);

$bench->add(
    "build complex nested SELECT query",
    function () use ($db) {
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

        $sql = $query->getTemplate()->getSQL();
    }
);

$bench->run();
