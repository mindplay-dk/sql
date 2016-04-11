<?php

namespace mindplay\sql\model;

class Column
{
    /**
     * @var Table
     */
    private $owner;

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
     * @param Table       $owner owner Table instance
     * @param Type        $type
     * @param string      $name
     * @param string|null $alias
     */
    public function __construct(Table $owner, Type $type, $name, $alias)
    {
        $this->owner = $owner;
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
}
