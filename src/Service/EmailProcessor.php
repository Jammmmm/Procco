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

use Procco\Exception\ImapConnectionException;
use Procco\Exception\ImapFetchException;
use Procco\Exception\ImapMessageException;
use Throwable;
use Webklex\PHPIMAP\Client;
use Webklex\PHPIMAP\ClientManager;
use Webklex\PHPIMAP\Message;

/**
 * Handles IMAP e-mail interaction for retrieving and updating messages.
 *
 * This service connects to an IMAP account, fetches unread messages,
 * and provides basic operations such as marking messages as read.
 */
final readonly class EmailProcessor
{
    private readonly Client $client;

    /**
     * Initializes the IMAP client using the provided configuration
     * and establishes a connection.
     *
     * @param array $imapConfig Configuration array for the IMAP client.
     * @return void
     * @throws ImapConnectionException If a connection could not be initiated.
     */
    public function __construct(array $imapConfig)
    {
        try {
            $cm = new ClientManager($imapConfig);
            $this->client = $cm->account($imapConfig['default'] ?? 'default');
            $this->client->connect();
        } catch (Throwable $e) {
            throw new ImapConnectionException('Failed to initialize IMAP client', 0, $e);
        }
    }

    /**
     * Retrieves all unread messages from the INBOX.
     *
     * @return Message[] Array of unread Message objects.
     * @throws ImapFetchException If messages cannot be fetched.
     */
    public function getUnreadMessages(): array
    {
        // Get all unread messages from INBOX
        try {
            $folder = $this->client->getFolder('INBOX');

            return $folder->messages()
                ->unseen()
                ->get()
                ->all();
        } catch (Throwable $e) {
            throw new ImapFetchException('Failed to fetch unread email messages', 0, $e);
        }
    }

    /**
     * Marks the given message as read (Seen flag).
     *
     * @param Message $message The message to update.
     * @throws ImapMessageException If the message could not be marked as read.
     */
    public function markAsRead(Message $message): void
    {
        try {
            $message->setFlag('Seen');
        } catch (Throwable $e) {
            throw new ImapMessageException('Failed to mark message as read', 0, $e);
        }
    }

    /**
     * Deletes the given message and expunges it from the mailbox.
     *
     * @param Message $message The message to delete.
     * @throws ImapMessageException If the message could not be deleted.
     */
    public function deleteMessage(Message $message): void
    {
        try {
            $message->delete(expunge: true);
        } catch (Throwable $e) {
            throw new ImapMessageException('Failed to delete message', 0, $e);
        }
    }
}
