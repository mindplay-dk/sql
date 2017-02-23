<?php

namespace mindplay\sql\framework;

/**
 * This interface is implemented by query-builders that provide an Indexer.
 */
interface IndexerProvider
{
    /**
     * @return Indexer|null
     */
    public function getIndexer();
}
