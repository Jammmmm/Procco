# Architecture

## Directory Structure

```
procco/
├── bin/
│   └── agent.php                # CLI entry point
├── config.example.php           # Agent configuration
├── composer.json
├── src/
│   ├── DTO/
│   │   └── CommandResult.php    # Result object returned by CommandExecutor
│   ├── Exception/
│   │   ├── ImapConnectionException.php  # Thrown when IMAP client fails to connect
│   │   ├── ImapFetchException.php       # Thrown when fetching messages fails
│   │   ├── ImapMessageException.php     # Thrown when marking a message as read fails
│   │   └── LogDirectoryException.php    # Thrown when the log directory cannot be created
│   ├── Logger/
│   │   └── LoggerFactory.php    # Creates and configures the Monolog logger
│   └── Service/
│       ├── CommandExecutor.php  # Runs system commands via Symfony Process
│       ├── EmailProcessor.php   # IMAP connection and message retrieval (used by ImapEmailCommandHandler)
│       ├── Mailer.php           # Thin wrapper around Symfony Mailer (used by EmailNotificationHandler)
│       ├── InputHandler/
│       │   ├── AbstractInputHandler.php        # Base class for input handlers
│       │   └── ImapEmailCommandHandler.php     # Built-in e-mail-based input handler
│       └── NotificationHandler/
│           ├── AbstractNotificationHandler.php  # Base class for notification handlers
│           └── EmailNotificationHandler.php     # Built-in e-mail notification handler
└── docs/
```

## Component Overview

### `AbstractInputHandler`

Defines the contract all input handlers must implement:

- `createFromConfig(array $config): static` — factory to construct the handler from the config array
- `poll(): void` — reads from the input source and processes any pending requests
- `parse(string $body): array` — converts raw input into a key-value array (`COMMAND`, `TOKEN`, `ARGUMENTS`, etc.)
- `validateToken(?string $token, string $commandKey): bool` — checks the token against the command's configured token

The built-in `ImapEmailCommandHandler` implements this using IMAP, but any input source can be supported by extending this class.

### `ImapEmailCommandHandler` (built-in)

The default input handler. It:

- Connects to an IMAP mailbox via `EmailProcessor`
- Marks messages as read or deletes them immediately on retrieval to prevent reprocessing (controlled by `email_processed_action`)
- Parses the message body into key-value pairs (`COMMAND`, `TOKEN`, `ARGUMENTS`)
- Enforces an optional sender whitelist and validates the token
- Delegates execution to `CommandExecutor`
- Optionally sends a result notification via a notification handler

### `EmailProcessor`

Used internally by `ImapEmailCommandHandler`. Wraps `webklex/php-imap` to connect to an IMAP account and return unread inbox messages.

### `CommandExecutor`

Takes a command configuration and optional runtime arguments, builds a Symfony `Process`, runs it, and returns a `CommandResult`. Commands must be defined as arrays — each element is a separate argument passed directly to the process without shell interpretation, preventing shell injection.

### `CommandResult`

A readonly DTO carrying `success`, `output`, `error`, and `exitCode`. Has a `toDisplayString()` method for producing a human-readable summary of the result.

### `AbstractNotificationHandler`

Defines the contract all notification handlers must implement:

- `createFromConfig(array $config): static`
- `notify(array $payload): void`

Notifications are entirely optional. Input handlers are free to use any notification handler — or none at all. The built-in `EmailNotificationHandler` sends result summaries via e-mail, but custom handlers could post to a webhook, write to a log, send an SMS, and so on.

### `EmailNotificationHandler` (built-in)

Implements `AbstractNotificationHandler` using `Mailer`. Accepts a payload with `subject`, `body`, and optional `from`/`to` keys, falling back to the addresses configured in `config.php`.

### `Mailer`

A thin wrapper around Symfony Mailer used by `EmailNotificationHandler`. Constructs and dispatches `Email` objects, and silently skips sending if either address is empty.

### `LoggerFactory`

Creates a Monolog logger that writes to a daily log file within a monthly subdirectory (`YYYY-MM/YYYY-MM-DD.log`). Sender context is passed via Monolog's context array and rendered inline in each log entry.

## Adding a New Input Handler

1. Create a class that extends `AbstractInputHandler`.
2. Implement `createFromConfig(array $config): static` to construct it from the config array.
3. Implement `poll(): void` with your input source logic.
4. Implement `parse(string $body): array` to extract command data from raw input.
5. Set `input_handler` in `config.php` to your new fully-qualified class name.
6. Add any handler-specific config under `input_handlers[YourHandler::class]` in `config.php`.

## Adding a New Notification Handler

1. Create a class that extends `AbstractNotificationHandler`.
2. Implement `createFromConfig(array $config): static`.
3. Implement `notify(array $payload): void` — the payload shape is defined by whatever the calling input handler passes.
