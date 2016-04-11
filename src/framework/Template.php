<?php

namespace mindplay\sql\framework;

/**
 * This class represents an SQL statement template, with PDO placeholders, and matching
 * map of parameter names to scalar values and/or arrays of scalar values.
 */
class Template implements Executable
{
    /**
     * @var string
     */
    private $sql;

    /**
     * @var array
     */
    private $params;

    /**
     * @param  string $sql    SQL statement (with placeholders)
     * @param array   $params map where placeholder name maps to a scalar value, or arrays of scalar values
     */
    public function __construct($sql, array $params)
    {
        $this->sql = $sql;
        $this->params = $params;
    }

    /**
     * @return string SQL statement (with placeholders)
     */
    public function getSQL()
    {
        return $this->sql;
    }

    /**
     * @return array map where placeholder name maps to a scalar value, or arrays of scalar values
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @return $this
     */
    public function getTemplate()
    {
        return $this;
    }
}
