<?php

/**
 * Copyright (C) 2026 Jammmmm
 *
 * This file is part of Procco.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 3.
 *
 * See the LICENSE file for details.
 */

declare(strict_types=1);

namespace Procco\Logger;

use Carbon\CarbonImmutable;
use Carbon\CarbonTimeZone;
use Procco\Exception\LogDirectoryException;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;

/**
 * Factory for creating configured Monolog loggers.
 *
 * Logs are written to daily files within monthly directories.
 */
final class LoggerFactory
{
    /**
     * Creates a Monolog logger that appends to a daily log file within a monthly subdirectory.
     *
     * @param string $name Name of the logger.
     * @param string $logDirectory Root directory for logs.
     * @return Logger
     * @throws LogDirectoryException If the log directory could not be created.
     */
    public static function create(string $name = 'input_agent', string $logDirectory = __DIR__ . '/../logs'): Logger
    {
        // Ensure current month directory exists
        $time = new CarbonImmutable('now', new CarbonTimeZone('UTC'));
        $monthDir = $logDirectory . DIRECTORY_SEPARATOR . $time->format('Y-m');
        if (!is_dir($monthDir) && !mkdir($monthDir, 0755, true) && !is_dir($monthDir)) {
            throw new LogDirectoryException('Failed to create log directory: ' . $monthDir);
        }

        // Set the daily log file and formatting
        $handler = new StreamHandler($monthDir . '/' . $time->format('Y-m-d') . '.log', Level::Info);
        $handler->setFormatter(
            formatter: new LineFormatter("[%datetime%][%context.sender%] %level_name%: %message%\n", 'Y-m-d H:i:s', true, true),
        );

        // Create the logger and return
        $logger = new Logger($name);
        $logger->pushHandler($handler);

        return $logger;
    }
}
