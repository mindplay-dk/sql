<?php

namespace mindplay\sql\model\types;

use mindplay\sql\model\schema\Type;
use RuntimeException;

class JSONType implements Type
{
    /**
     * @var int options for json_encode()
     *
     * @see json_encode()
     */
    public $json_encode_options = JSON_UNESCAPED_UNICODE;

    public function convertToSQL($value)
    {
        if ($value === null || $value === '') {
            return null;
        }

        $json = json_encode($value, $this->json_encode_options);

        if ($json === false) {
            throw new RuntimeException(json_last_error_msg());
        }

        return $json;
    }

    public function convertToPHP($value)
    {
        return ($value === null || $value === '')
            ? null
            : json_decode($value, true);
    }
}
