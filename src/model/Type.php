<?php

namespace mindplay\sql\model;

/**
 * This interface defines the responsibilities of a Type definition.
 */
interface Type
{
    /**
     * @param mixed $value
     *
     * @return string|int|float|bool|null
     */
    public function convertToSQL($value);

    /**
     * @param string|int|float|null $value
     *
     * @return mixed
     */
    public function convertToPHP($value);
}
