<?php

namespace mindplay\sql\model;

use mindplay\sql\framework\Driver;

class Column
{
    /**
     * @var Table
     */
    private $table;

    /**
     * @var Driver
     */
    private $driver;
    
    /**
     * @var Type
     */
    private $type;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $alias;
    
    /**
     * @param Table       $table parent Table instance
     * @param Driver      $driver
     * @param Type        $type
     * @param string      $name
     * @param string|null $alias
     */
    public function __construct(Table $table, Driver $driver, Type $type, $name, $alias)
    {
        $this->table = $table;
        $this->driver = $driver;
        $this->type = $type;
        $this->name = $name;
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @ignore
     *
     * @return string
     */
    public function __toString()
    {
        return $this->alias
            // if this Column has an alias, quote that:
            ? $this->driver->quoteName($this->alias)
            // otherwise, get the quoted parent Table alias and use dotted (`table`.`column`) notation:
            : $this->table->__toString() . '.' . $this->driver->quoteName($this->name);
    }
}
