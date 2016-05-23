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
     * @var DateTimeZone
     */
    private static $utc_timezone;

    public function __construct()
    {
        if (self::$utc_timezone === null) {
            self::$utc_timezone = new DateTimeZone('UTC');
        }
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

        $datetime = DateTime::createFromFormat('U', $timestamp, self::$utc_timezone);

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

        $datetime = DateTime::createFromFormat(static::FORMAT, $value, self::$utc_timezone);

        if ($datetime === false) {
            throw new UnexpectedValueException("unable to convert value from int: " . $value);
        }

        return $datetime->getTimestamp();
    }
}
