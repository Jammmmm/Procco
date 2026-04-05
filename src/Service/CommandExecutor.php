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

namespace Procco\Service;

use Procco\DTO\CommandResult;
use Monolog\Logger;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Service responsible for executing predefined system commands.
 *
 * Handles safe command construction, process execution, logging, and
 * returning a consistent CommandResult for success or failure cases.
 */
final readonly class CommandExecutor
{
    public function __construct(
        private Logger $logger
    ) {}

    /**
     * Executes a system command and returns the result.
     *
     * @param array $commandConfig Command configuration (system, timeout, etc.).
     * @param string $commandKey Command name/key.
     * @param ?string $arguments Optional arguments from input to append to the system command.
     * @param ?string $sender Optional sender for logging context.
     * @return CommandResult The command execution result.
     */
    public function execute(array $commandConfig, string $commandKey, ?string $arguments = null, ?string $sender = null): CommandResult
    {
        $systemCommand = $commandConfig['system'] ?? null;

        // Only continue if a command has been given
        if (empty($systemCommand) || !is_array($systemCommand)) {
            return new CommandResult(
                success: false,
                error: 'No system command defined',
            );
        }

        // Build the command, appending any input arguments (only if explicitly allowed)
        $allowArguments = $commandConfig['allow_arguments'] ?? false;
        if ($arguments !== null && $allowArguments === true) {
            foreach (preg_split('/\s+/', trim($arguments), -1, PREG_SPLIT_NO_EMPTY) as $arg) {
                $systemCommand[] = $arg;
            }
        }

        // Create the process
        $process = new Process($systemCommand);
        $process->setTimeout($commandConfig['timeout'] ?? 60);
        $logOutput = $commandConfig['log_output'] ?? false;
        $this->logger->info('Executing command: ' . $commandKey, ['sender' => $sender]);

        $errorOutput = '';
        $exitCode = 0;

        try {
            // Execute the process and store its output
            $process->run();
            $output = trim($process->getOutput());
            $errorOutput = trim($process->getErrorOutput());
            $exitCode = $process->getExitCode() ?? -1;

            // Check if the process was successfully executed
            if ($process->isSuccessful()) {
                // Log the result and return
                $context = ['sender' => $sender, 'exit_code' => $exitCode];
                if ($logOutput === true) {
                    $context['output'] = $output;
                }
                $this->logger->info('Command execution completed: ' . $commandKey, $context);
                return new CommandResult(
                    success: true,
                    output: $output,
                    exitCode: $exitCode,
                );
            }

            // Failure without exception
            $errorMessage = ($errorOutput !== '') ? $errorOutput : 'Command failed with exit code ' . $exitCode;
        } catch (Throwable $e) {
            $errorOutput = trim($process->getErrorOutput());
            $exitCode = $process->getExitCode() ?? -1;
            $errorMessage = $e->getMessage() . (($errorOutput !== '') ? ' | STDERR: ' . $errorOutput : '');
        }

        // Execution failed. Log the result and return
        $this->logger->error('Command execution failed: ' . $commandKey, [
            'sender' => $sender,
            'error' => $errorMessage,
            'stderr' => $errorOutput,
            'exit_code' => $exitCode,
        ]);
        return new CommandResult(
            success: false,
            error: $errorMessage,
            exitCode: $exitCode,
        );
    }
}
