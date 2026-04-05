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

namespace Procco\DTO;

final readonly class CommandResult
{
    public function __construct(
        public bool $success,
        public ?string $output = null,
        public ?string $error = null,
        public ?int $exitCode = null,
    ) {}

    /**
     * Generates a display string for the command execution.
     *
     * @param string $command The command that was executed.
     * @param bool $sendOutput Whether to include command output in the display string.
     * @param string $sender The sender of the command.
     * @return string The generated display string.
     */
    public function toDisplayString(string $command, bool $sendOutput = false, string $sender = ''): string
    {
        $string = "Sender: " . $sender . "\n";
        $string .= "Command: " . $command . "\n";
        $string .= "Status: " . (($this->success) ? "Success" : "Failed") . "\n";
        $string .= "Exit Code: " . $this->exitCode . "\n";
        $string .= "Output: " . ($sendOutput && $this->output ? $this->output : "") . "\n";
        $string .= "Error: " . (($this->error) ? $this->error : "") . "\n";

        return $string;
    }
}
