<?php

namespace mindplay\sql\model\components;

/**
 * This trait implements query conditions for the `WHERE` clause.
 */
trait Conditions
{
    /**
     * @var string[] list of condition expressions to apply to the WHERE clause
     */
    protected $conditions = [];

    /**
     * @param string|string[] $exprs one or more condition expressions to apply to the WHERE clause
     *
     * @return $this
     */
    public function where($exprs)
    {
        foreach ((array) $exprs as $expr) {
            $this->conditions[] = $expr;
        }

        return $this;
    }

    /**
     * @return string combined condition expression (for use in the WHERE clause of an SQL statement)
     */
    protected function buildConditions()
    {
        return implode(" AND ", $this->conditions);
    }
}
