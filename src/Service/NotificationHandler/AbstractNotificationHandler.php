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

namespace Procco\Service\NotificationHandler;

/**
 * Abstract base for notification handlers that send messages
 * through external channels using a generic payload.
 */
abstract class AbstractNotificationHandler
{
    /**
     * Factory method to create a handler from config
     */
    abstract public static function createFromConfig(array $config): static;

    /**
     * Sends a notification with a flexible payload
     *
     * @param array<string,mixed> $payload Generic data describing the notification
     */
    abstract public function notify(array $payload): void;
}
