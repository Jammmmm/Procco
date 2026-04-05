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

use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

/**
 * Simple wrapper around Symfony Mailer to send e-mails.
 *
 * Provides a convenience method for sending e-mails with optional
 * text and HTML bodies, while validating that the sender and recipient
 * addresses are non-empty.
 */
final readonly class Mailer
{
    public function __construct(
        private MailerInterface $mailer,
    ) {}

    /**
     * Sends an e-mail using Symfony Mailer.
     *
     * @param string $from The e-mail address to send from.
     * @param string $to The e-mail address to send to.
     * @param string $subject The e-mail subject.
     * @param string ?$textBody The text body of the e-mail.
     * @param string ?$htmlBody The HTML body of the e-mail.
     * @return void
     */
    public function send(string $from, string $to, string $subject, ?string $textBody = null, ?string $htmlBody = null): void
    {
        // Validate basic e-mail format
        if (!filter_var($from, FILTER_VALIDATE_EMAIL) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        // Ensure at least one body is provided
        if ($textBody === null && $htmlBody === null) {
            return;
        }

        // Prepare the e-mail
        $email = (new Email())
            ->from($from)
            ->to($to)
            ->subject($subject);

        if ($textBody !== null) {
            $email->text($textBody);
        }

        if ($htmlBody !== null) {
            $email->html($htmlBody);
        }

        // Attempt to send - silently fail on transport exceptions
        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
        }
    }
}
