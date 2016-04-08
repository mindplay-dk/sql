<?php

namespace mindplay\sql\framework;

use mindplay\unbox\Container;
use PDO;

class Database
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @param callable|PDO $pdo_or_factory PDO factory function (or existing PDO object)
     * @param Driver       $driver
     */
    public function __construct($pdo_or_factory, Driver $driver)
    {
        $this->container = new Container();
        
        if (is_callable($pdo_or_factory)) {
            // PDO factory given - register with Container:
            $this->container->register(PDO::class, $pdo_or_factory);
        } else {
            // PDO instance given - inject into Container:
            $this->container->set(PDO::class, $pdo_or_factory);
        }
        
        $this->container->set(Driver::class, $driver);

        $this->container->register(Preparator::class);

        $this->container->register(Connection::class);
        
        // self-register:

        $this->container->set(self::class, $this);
        $this->container->set(get_class($this), $this);
    }

    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->container->get(Connection::class);
    }
}
