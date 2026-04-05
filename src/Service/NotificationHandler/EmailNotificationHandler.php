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

use Procco\Service\Mailer;
use InvalidArgumentException;
use Symfony\Component\Mailer\Mailer as SymfonyMailer;
use Symfony\Component\Mailer\Transport;

/**
 * Notification handler that sends e-mails using the Mailer service.
 */
final class EmailNotificationHandler extends AbstractNotificationHandler
{
    private Mailer $mailer;
    private string $defaultSender;
    private string $defaultRecipient;

    public function __construct(Mailer $mailer, string $defaultSender = '', string $defaultRecipient = '')
    {
        $this->mailer = $mailer;
        $this->defaultSender = $defaultSender;
        $this->defaultRecipient = $defaultRecipient;
    }

    /**
     * Creates an e-mail notification handler from configuration.
     *
     * Expects a mailer DSN and optional default sender/recipient.
     *
     * @param array<string,mixed> $config Configuration array. Expects 'mailer_dsn', with 'from_email' and
     * 'notification_email' being optional.
     * @return static
     */
    public static function createFromConfig(array $config): static
    {
        $mailerDsn = $config['mailer_dsn'] ?? '';
        if (!is_string($mailerDsn) || $mailerDsn === '') {
            throw new InvalidArgumentException('Config key "mailer_dsn" is missing or empty.');
        }

        return new self(
            mailer: new Mailer(new SymfonyMailer(
                Transport::fromDsn($mailerDsn)
            )),
            defaultSender: $config['from_email'] ?? '',
            defaultRecipient: $config['notification_email'] ?? ''
        );
    }

    /**
     * Sends an e-mail notification using payload data or defaults.
     *
     * @param array $payload The payload data describing the notification with keys: from, to, subject, body.
     * @return void
     */
    public function notify(array $payload): void
    {
        // Retrieve all details
        $from = $payload['from'] ?? $this->defaultSender;
        $to = $payload['to'] ?? $this->defaultRecipient;
        $subject = $payload['subject'] ?? 'Notification';
        $body = $payload['body'] ?? '';

        if ($from === '' || $to === '') {
            return;
        }

        // Send the e-mail
        $this->mailer->send(
            from: $from,
            to: $to,
            subject: $subject,
            textBody: $body
        );
    }
}
