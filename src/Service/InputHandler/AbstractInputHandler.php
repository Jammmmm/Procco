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

namespace Procco\Service\InputHandler;

/**
 * Abstract base for input handlers that poll, parse, and validate commands
 * from external sources, with optional validation.
 */
abstract class AbstractInputHandler
{
    protected array $commands = [];
    protected array $allowedSenders = [];

    public function __construct(
        array $commands = [],
        array $allowedSenders = [],
    ) {
        $this->commands = $commands;
        $this->allowedSenders = $allowedSenders;
    }

    // Each input handler provides its own factory
    abstract public static function createFromConfig(array $config): static;

    // How to poll for commands
    abstract public function poll(): void;

    // Parse raw input into a command/token array
    abstract protected function parse(string $body): array;

    /**
     * Validates a token against a predefined command configuration.
     *
     * If the command has no 'token' key configured, the command is considered
     * unprotected and validation passes. If a token is configured, it must be
     * non-empty and match the provided token exactly.
     *
     * @param ?string $token The token provided.
     * @param string $commandKey The command name/key to validate.
     * @return bool true if the command exists and either has no token configured or the token matches; false otherwise.
     */
    protected function validateToken(?string $token, string $commandKey): bool
    {
        // If the command or key does not exist, do not continue with token validation
        if (!is_array($this->commands) || $commandKey === '' || !isset($this->commands[$commandKey])) {
            return false;
        }

        // No token configured — command is intentionally unprotected
        $configuredToken = trim((string)($this->commands[$commandKey]['token'] ?? ''));
        if ($configuredToken === '') {
            return true;
        }

        // Token configured — provided token must match
        return hash_equals($configuredToken, trim((string)$token));
    }
}
