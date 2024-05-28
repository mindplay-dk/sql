<?php

namespace mindplay\sql\model\query;

use mindplay\sql\framework\MapperProvider;
use mindplay\sql\model\components\Mappers;
use mindplay\sql\model\TypeProvider;

/**
 * This class represents a custom SQL Query.
 */
class SQLQuery extends Query implements MapperProvider
{
    use Mappers;
    
    private string $sql;

    /**
     * @param $sql SQL statement (with placeholders)
     */
    public function __construct(TypeProvider $types, string $sql)
    {
        parent::__construct($types);
        
        $this->sql = $sql;
    }
    
    public function getSQL(): string
    {
        return $this->sql;
    }

    /**
     * @inheritdoc
     */
    public function getMappers(): array
    {
        return $this->mappers;
    }
}
