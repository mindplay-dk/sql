<?php

namespace mindplay\sql\model\components;

use InvalidArgumentException;

/**
 * This trait implements the `LIMIT` and `OFFSET` clause.
 */
trait Range
{
    /**
     * @var int|null
     */
    protected $limit;

    /**
     * @var int|null
     */
    protected $offset;

    /**
     * @param int      $limit  max. number of records
     * @param int|null $offset base-0 record number offset
     *
     * @return $this
     *
     * @throws InvalidArgumentException if the given limit is less than 1, or if the given offset if less than 0
     */
    public function limit($limit, $offset = null)
    {
        if ($limit < 1) {
            throw new InvalidArgumentException("limit out of range: {$limit}");
        }

        if ($offset < 0) {
            throw new InvalidArgumentException("offset out of range: {$offset}");
        }

        $this->limit = $limit;
        $this->offset = $offset;

        return $this;
    }

    /**
     * @param int $page_num  base-1 page number
     * @param int $page_size number of records per page
     *
     * @return $this
     *
     * @throws InvalidArgumentException if the given page number or page size are less than 1
     */
    public function page($page_num, $page_size)
    {
        if ($page_size < 1) {
            throw new InvalidArgumentException("page size out of range: {$page_size}");
        }

        if ($page_num < 1) {
            throw new InvalidArgumentException("page number out of range: {$page_num}");
        }

        return $this->limit($page_size, ($page_num - 1) * $page_size);
    }

    /**
     * @return string the LIMIT/OFFSET clause (or an empty string, if no limit has been set)
     */
    protected function buildLimit()
    {
        return $this->limit !== null
            ? "\nLIMIT {$this->limit}" . ($this->offset !== null ? " OFFSET {$this->offset}" : '')
            : ''; // no limit or offset
    }
}
