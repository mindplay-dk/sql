<?php

namespace mindplay\sql\model\types;

use DateTime;
use DateTimeZone;
use mindplay\sql\model\schema\Type;
use UnexpectedValueException;

/**
 * This class maps an SQL DATETIME value to a Unix timestamp (integer) value in PHP.
 *
 * It assumes DATETIME values being stored relative to the UTC timezone.
 */
class TimestampType implements Type
{
    const FORMAT = 'Y-m-d H:i:s';

    /**
     * @return DateTimeZone
     */
    protected static function getTimeZone()
    {
        static $utc;

        if ($utc === null) {
            $utc = new DateTimeZone('UTC');
        }

        return $utc;
    }

    public function convertToSQL($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $timestamp = (int) $value;

        if ($timestamp === 0) {
            throw new UnexpectedValueException("unable to convert value to int: " . $value);
        }

        $datetime = DateTime::createFromFormat('U', $timestamp, self::getTimeZone());

        return $datetime->format(static::FORMAT);
    }

    public function convertToPHP($value)
    {
        if (is_int($value)) {
            return $value; // return timestamp as-is
        }

        if ($value === null) {
            return $value; // return NULL value as-is
        }

        $datetime = DateTime::createFromFormat(static::FORMAT, $value, self::getTimeZone());

        if ($datetime === false) {
            throw new UnexpectedValueException("unable to convert value from int: " . $value);
        }

        return $datetime->getTimestamp();
    }
}
