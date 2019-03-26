<?php

namespace mindplay\sql\model\components;

/**
 * This trait implements the `ORDER BY` clause.
 */
trait Order
{
    /**
     * @var string[] list of ORDER BY terms
     */
    protected $order = [];

    /**
     * @param string $expr order-by expression (which may include a trailing "ASC" or "DESC" modifier)
     *
     * @return $this
     */
    public function order($expr)
    {
        $this->order[] = "{$expr}";

        return $this;
    }

    /**
     * @return string order terms (for use in the ORDER BY clause of an SQL statement)
     */
    protected function buildOrderTerms()
    {
        return implode(', ', $this->order);
    }
}
