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
    protected array $order = [];

    /**
     * @param string $expr order-by expression (which may include a trailing "ASC" or "DESC" modifier)
     *
     * @return $this
     */
    public function order(string $expr): static
    {
        $this->order[] = "{$expr}";

        return $this;
    }

    /**
     * @return string order terms (for use in the ORDER BY clause of an SQL statement)
     */
    protected function buildOrderTerms(): string
    {
        return implode(', ', $this->order);
    }
}
