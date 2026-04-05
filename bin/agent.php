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

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Console\Input\ArgvInput;

// Parse the command line arguments
$input = new ArgvInput();

if (!$input->hasParameterOption('--config')) {
	usage();
	exit(1);
}

$configPath = $input->getParameterOption('--config');

// Load the configuration
$config = (is_string($configPath) && is_file($configPath) && is_readable($configPath)) ? require($configPath) : null;
if (!is_array($config)) {
	fwrite(STDERR, "Error: cannot read config file: " . $configPath . "\n");
	exit(1);
}

// Determine input handler class from config
$handlerClass = $config['input_handler'] ?? null;
if (!$handlerClass || !class_exists($handlerClass)) {
	fwrite(STDERR, "Error: input handler class not defined or not found: " . ($handlerClass ?? 'null') . "\n");
	exit(1);
}

// Instantiate the input handler
try {
	$handler = $handlerClass::createFromConfig($config);
} catch (\InvalidArgumentException $e) {
	fwrite(STDERR, "Configuration error: " . $e->getMessage() . "\n");
	exit(1);
} catch (\Throwable $e) {
	fwrite(STDERR, "Input handler error: " . $e->getMessage() . "\n");
	exit(1);
}

// Poll for commands to execute
$handler->poll();

/**
 * Prints usage details.
 *
 * @return void
 */
function usage(): void
{
	echo "Procco v" . getVersion() . "\n";
	echo "\n";
	echo "Usage: php agent.php --config <path/to/config.php>\n";
	echo "\n";
	echo "Options:\n";
	echo "  --config <file> (Required: Path to the configuration file)\n";
	echo "\n";
	echo "Example:\n";
	echo "  php bin/agent.php --config /path/to/myconfig.php\n";
}

/**
 * Reads the version from composer.json.
 *
 * @return string
 */
function getVersion(): string
{
	$composerPath = __DIR__ . '/../composer.json';
	if (is_readable($composerPath)) {
		$composer = json_decode(file_get_contents($composerPath), true);
		if (isset($composer['version'])) {
			return $composer['version'];
		}
	}
	return 'unknown';
}
