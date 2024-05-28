<?php

namespace mindplay\sql\model\schema;

/**
 * This interface defines the responsibilities of a Type definition.
 * 
 * Note that ALL Types MUST support NULL - consistent with SQL types, the Column model defines
 * whether or not the column accepts NULL, but all Types are essentially nullable.
 * 
 * TODO improve type safety? the benefits here would be very limited, and run-time type-checks a bit of a performance hit
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
