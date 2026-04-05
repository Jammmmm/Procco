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

use Procco\Logger\LoggerFactory;
use Procco\Service\CommandExecutor;
use Procco\Service\EmailProcessor;
use Procco\Service\InputHandler\AbstractInputHandler;
use Procco\Service\Mailer;
use Procco\Service\NotificationHandler\EmailNotificationHandler;
use InvalidArgumentException;
use Monolog\Logger;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;

/**
 * Input handler that processes commands received via IMAP e-mail.
 *
 * Polls unread messages, validates senders and tokens, executes
 * configured commands, and sends notification e-mails.
 */
final class ImapEmailCommandHandler extends AbstractInputHandler
{
    private EmailProcessor $emailProcessor;
    private EmailNotificationHandler $notifier;
    private Logger $logger;
    private string $emailProcessedAction;

    public function __construct(
        array $commands = [],
        array $allowedSenders = [],
        EmailProcessor $emailProcessor,
        EmailNotificationHandler $notifier,
        Logger $logger,
        string $emailProcessedAction = 'read',
    ) {
        parent::__construct($commands, $allowedSenders);
        $this->emailProcessor = $emailProcessor;
        $this->notifier = $notifier;
        $this->logger = $logger;
        $this->emailProcessedAction = $emailProcessedAction;
    }

    /**
     * Creates an IMAP e-mail command handler from configuration.
     *
     * Expects command definitions, IMAP configuration, and a mailer DSN.
     *
     * @param array<string,mixed> $config Application configuration array.
     * @return static
     */
    public static function createFromConfig(array $config): static
    {
        $handlerConfig = $config['input_handlers'][self::class] ?? [];

        // Ensure a mailer DSN has been provided
        $mailerDsn = $config['mailer_dsn'] ?? '';
        if (!is_string($mailerDsn) || $mailerDsn === '') {
            throw new InvalidArgumentException('Config key "mailer_dsn" is missing or empty.');
        }

        $mailer = new Mailer(new SymfonyMailer(
            Transport::fromDsn($mailerDsn)
        ));

        $fromEmail = $config['from_email'] ?? '';
        $notificationEmail = $config['notification_email'] ?? '';

        $emailProcessedAction = $handlerConfig['email_processed_action'] ?? 'read';
        if (!in_array($emailProcessedAction, ['read', 'delete'], true)) {
            throw new InvalidArgumentException('Config key "email_processed_action" must be "read" or "delete".');
        }

        // Create the handler
        return new self(
            commands: is_array($config['commands'] ?? null) ? $config['commands'] : [],
            allowedSenders: is_array($handlerConfig['allowed_senders'] ?? null) ? $handlerConfig['allowed_senders'] : [],
            emailProcessor: new EmailProcessor(is_array($handlerConfig['imap'] ?? null) ? $handlerConfig['imap'] : []),
            notifier: new EmailNotificationHandler($mailer, $fromEmail, $notificationEmail),
            logger: LoggerFactory::create('input_agent', $config['log_directory'] ?? __DIR__ . '/../logs'),
            emailProcessedAction: $emailProcessedAction,
        );
    }

    /**
     * Polls unread e-mails and processes commands.
     *
     * @return void
     */
    public function poll(): void
    {
        // Fetch unread messages
        $messages = $this->emailProcessor->getUnreadMessages();

        // Get and process each e-mail
        foreach ($messages as $message) {
            $from = $message->getFrom();
            $firstAddress = is_object($from) ? $from->get(0) : null;
            $rawSender = (is_object($firstAddress) && isset($firstAddress->mail)) ? $firstAddress->mail : '';
            $sender = filter_var($rawSender, FILTER_VALIDATE_EMAIL) !== false ? $rawSender : '';

            // Mark as read or delete immediately to avoid reprocessing
            if ($this->emailProcessedAction === 'delete') {
                $this->emailProcessor->deleteMessage($message);
            } else {
                $this->emailProcessor->markAsRead($message);
            }

            // Check sender whitelist if defined
            if (!empty($this->allowedSenders) && !in_array($sender, $this->allowedSenders)) {
                $this->logger->warning('Unauthorized sender attempted command', ['sender' => $sender]);
                $this->notifier->notify(
                    [
                        'subject' => 'Unauthorized sender',
                        'body' => $sender . ' attempted a command but is not in the whitelist.'
                    ]
                );
                continue;
            }

            // Parse the e-mail body for COMMAND + TOKEN + optional ARGUMENTS
            $data = $this->parse($message->getTextBody() ?: strip_tags($message->getHTMLBody() ?? ''));
            $command = $data['COMMAND'] ?? null;
            $token = $data['TOKEN'] ?? null;
            $arguments = $data['ARGUMENTS'] ?? null;

            // Log and send an alert if the command is missing from the e-mail
            if (!$command) {
                $this->logger->error('E-mail missing COMMAND field', ['sender' => $sender]);
                $this->notifier->notify(
                    [
                        'subject' => 'Missing COMMAND',
                        'body' => 'E-mail from ' . $sender . ' did not contain a COMMAND field.'
                    ]
                );
                continue;
            }

            // Log and send an alert if the command is unknown
            if (!isset($this->commands[$command])) {
                $this->logger->error('Unknown command attempted: ' . $command, ['sender' => $sender]);
                $this->notifier->notify(
                    [
                        'subject' => 'Unknown command',
                        'body' => $sender . ' attempted unknown command: ' . $command
                    ]
                );
                continue;
            }

            // Log and send an alert if the token does not match the token for the command
            if (!$this->validateToken($token, $command)) {
                $this->logger->error('Token validation failed for command: ' . $command, ['sender' => $sender]);
                $this->notifier->notify(
                    [
                        'subject' => 'Token failure',
                        'body' => $sender . ' failed token validation for command: ' . $command
                    ]
                );
                continue;
            }

            // Execute the command on successful validation
            $executor = new CommandExecutor($this->logger);
            $commandResult = $executor->execute(
                commandConfig: $this->commands[$command],
                commandKey: $command,
                arguments: $arguments,
                sender: $sender,
            );

            // Send a notification e-mail
            $sendOutput = $this->commands[$command]['send_output'] ?? false;
            $this->notifier->notify([
                'subject' => 'Command executed: ' . $command,
                'body' => $commandResult->toDisplayString($command, $sendOutput === true, $sender)
            ]);
        }
    }

    /**
     * Parses the body of an e-mail into key-value pairs.
     *
     * Each line in the body that contains an equals sign is treated as a
     * key-value pair. The text before the first = is the key (converted to
     * uppercase), and the text after the first = is the value. Empty lines
     * or lines without = are ignored.
     *
     * Example:
     *  COMMAND=start_agent
     *  TOKEN=abc123
     * becomes:
     * [
     * 	 'COMMAND' => 'start_agent',
     *   'TOKEN' => 'abc123'
     * ]
     *
     * @param string $body The raw e-mail body to parse.
     * @return array<string,string> Associative array of parsed key-value pairs.
     */
    protected function parse(string $body): array
    {
        $data = [];

        // Split into an array of lines
        $body = str_replace("\r", "\n", $body);
        $lines = explode("\n", $body);

        // Go through each line and store
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line !== '' && str_contains($line, '=')) {
                [$key, $val] = explode('=', $line, 2);
                $data[mb_strtoupper(trim($key))] = trim($val);
            }
        }

        // Return the parsed lines
        return $data;
    }
}
