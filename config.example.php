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

/**
 * Agent configuration.
 */
return [
    // Fully-qualified input handler class to use for inputs
    'input_handler' => \Procco\Service\InputHandler\ImapEmailCommandHandler::class,

    // E-mail notifications
    'mailer_dsn' => 'smtp://user:pass@smtp.example.com:587',
    'from_email' => 'you@example.com',
    'notification_email' => 'them@example.com',

    // Logs base directory. Subdirectories are created in the format YYYY-MM.
    'log_directory' => __DIR__ . '/logs',

    // Only commands in this list will be executed, and the token received must match the defined token for each command.
    // NOTE: When dealing with paths, use the full absolute path. For example, instead of ~/somefile.txt, use /home/user/somefile.txt
    'commands' => [
        'start_agent' => [
            'system' => ['sudo', 'systemctl', 'start', 'agent'],
            'token' => 'token_for_start_agent',
            'timeout' => 30,
        ],
        'stop_agent' => [
            'system' => ['sudo', 'systemctl', 'stop', 'agent'],
            'token' => 'token_for_stop_agent',
            'timeout' => 30,
        ],
        'status_agent' => [
            'system' => ['sudo', 'systemctl', 'status', 'agent'],
            'token' => 'token_for_status_agent',
            'timeout' => 30,
            'allow_arguments' => true, // Optional: allow ARGUMENTS from input to be appended to the system command
            'send_output' => true, // Optional: send the output from the command execution in the notification e-mail
        ],
    ],

    // Handler-specific configuration
    'input_handlers' => [
        // ImapEmailCommandHandler settings
        \Procco\Service\InputHandler\ImapEmailCommandHandler::class => [
            // IMAP settings
            'imap' => [
                'default' => 'default',
                'accounts' => [
                    'default' => [
                        'host' => 'imap.example.com',
                        'port' => 993,
                        'encryption' => 'ssl',
                        'validate_cert' => true,
                        'username' => 'your@email.com',
                        'password' => 'yourpassword',
                        'protocol' => 'imap',
                    ],
                ],
            ],
            // Allowed senders. If left empty, all senders are trusted.
            'allowed_senders' => ['you@example.com', 'trusted@domain.com'],
            // Action to take on a processed e-mail: 'read' (mark as read) or 'delete'. Defaults to 'read'.
            'email_processed_action' => 'read',
        ],
    ],
];
