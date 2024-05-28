<?php

namespace mindplay\sql\framework;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * This class implements a buffered query logger.
 *
 * To flush recorded log-entries to a PSR-3 logger, call the `flushTo()` method, e.g. at
 * the end of an HTTP request.
 *
 * The queries are emitted in a `kodus/chrome-logger` compatible format.
 *
 * @link https://github.com/kodus/chrome-logger
 */
class BufferedPSRLogger implements Logger
{
    /**
     * @var array<string,mixed>[] buffered query log-entries
     */
    private array $entries = [];

    /**
     * Flush all recorded query log-entries to a single PSR-3 log-entry
     *
     * @param LoggerInterface $logger the PSR Logger to which the query-log will be flushed
     * @param string|mixed    $log_level PSR-3 log level (usually a string, defaults to "info")
     * @param string          $message   combined log entry title
     *
     * @see LogLevel
     */
    public function flushTo(LoggerInterface $logger, mixed $log_level = LogLevel::INFO, string $message = "INFO"): void
    {
        $logger->log(
            $log_level,
            $message,
            ["table: SQL Queries" => $this->entries]
        );

        $this->entries = [];
    }

    /**
     * @param array<string,mixed> $params
     */
    public function logQuery(string $sql, array $params, float $time_msec): void
    {
        $this->entries[] = [
            "time" => sprintf('%0.3f', $time_msec / 1000) . " s",
            "sql"  => QueryFormatter::formatQuery($sql, $params),
        ];
    }
}
