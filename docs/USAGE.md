# Usage

## Configuration

Copy `config.example.php` to a secure and accessible location, and fill in the values for your environment.

### Top-level options

| Key | Description |
|---|---|
| `input_handler` | Fully-qualified class name of the input handler to use. |
| `mailer_dsn` | Mailer DSN to use when sending notification e-mails. (used by the built-in `EmailNotificationHandler`). |
| `from_email` | E-mail address that notifications are sent from (used by `EmailNotificationHandler`). |
| `notification_email` | E-mail address that notifications are sent to (used by `EmailNotificationHandler`). |
| `log_directory` | Root directory for log files. |
| `commands` | Map of command names to their configuration (see below). |
| `input_handlers` | Handler-specific configuration keyed by handler class (see below). |

`mailer_dsn`, `from_email`, and `notification_email` are only required if you are using `EmailNotificationHandler`. Notifications are optional — if your input handler does not use a notification handler, these keys can be omitted.

### Defining commands

Each entry under `commands` is a key (the command name) mapped to its configuration:

```php
'commands' => [
    'restart_service' => [
        'system'  => ['sudo', 'systemctl', 'restart', 'my-service'],
        'token'   => 'a-secret-token',
        'timeout' => 30,
    ],
],
```

| Key | Type | Description |
|---|---|---|
| `system` | `array` | The command to run as an array of arguments (e.g. `['systemctl', 'restart', 'my-service']`). Each element is passed directly to the process without shell interpretation, which prevents shell injection attacks. |
| `token` | `string` | Secret token the requester must supply to authorize the command. Omit or leave empty to allow the command without a token. |
| `timeout` | `int` | Maximum execution time in seconds. Defaults to 60 if omitted. |
| `allow_arguments` | `bool` | Optional. When `true`, runtime `ARGUMENTS` from the request are appended to the command. Defaults to `false`. |
| `log_output` | `bool` | Optional. When `true`, the command's stdout is included in the log entry. Defaults to `false` to avoid logging sensitive output. |
| `send_output` | `bool` | Optional. When `true`, the command's stdout is included in the notification e-mail. Defaults to `false` to avoid leaking sensitive output. |

### Handler-specific configuration

Each input handler reads its own settings from `input_handlers`, keyed by its fully-qualified class name. These settings are defined by the handler itself. For example, `ImapEmailCommandHandler` expects `imap` connection details and an optional `allowed_senders` list:

```php
'input_handlers' => [
    \Procco\Service\InputHandler\ImapEmailCommandHandler::class => [
        'imap' => [
            'default' => 'default',
            'accounts' => [
                'default' => [
                    'host'          => 'imap.example.com',
                    'port'          => 993,
                    'encryption'    => 'ssl',
                    'validate_cert' => true,
                    'username'      => 'your@email.com',
                    'password'      => 'yourpassword',
                    'protocol'      => 'imap',
                ],
            ],
        ],
        'allowed_senders' => ['trusted@example.com'],
        'email_processed_action' => 'read',
    ],
],
```

| Key | Type | Description |
|---|---|---|
| `imap` | `array` | IMAP connection settings (see `webklex/php-imap` for the full schema) |
| `allowed_senders` | `array` |  Whitelist of sender addresses permitted to issue commands. If empty, all senders are trusted. |
| `email_processed_action` | `string` | What to do with an e-mail once it has been processed. `'read'` marks it as read (the default); `'delete'` permanently deletes and expunges it. |

Custom handlers can define whatever configuration structure they need under their own class key.

## Running the Agent

```bash
php bin/agent.php --config /path/to/config.php
```

To run it on a schedule, add a cron entry:

```
* * * * * php /path/to/bin/agent.php --config /path/to/config.php
```

## Sending a Command

The format of a request depends on the input handler in use. The built-in `ImapEmailCommandHandler` parses `KEY=VALUE` pairs from the body of an email, one per line:

```
COMMAND=restart_service
TOKEN=a-secret-token
```

Custom handlers can parse input in any format, as long as they produce the expected `COMMAND` and `TOKEN` values internally.

### Passing Arguments

To append runtime arguments to the system command, the command must have `allow_arguments` set to `true` in its configuration:

```php
'list_files' => [
    'system'         => ['ls', '-lh'],
    'token'          => 'a-secret-token',
    'timeout'        => 30,
    'allow_arguments' => true,
],
```

Then include an `ARGUMENTS` field in the request:

```
COMMAND=list_files
TOKEN=a-secret-token
ARGUMENTS=/var/log
```

The value of `ARGUMENTS` is split by whitespace and each token is appended to the command's `system` array. For the example above, the process will run `ls -lh /var/log`.

If `allow_arguments` is `false` or omitted, any `ARGUMENTS` in the request are ignored.

### Receiving Output

By default, command output is not included in notification emails to avoid leaking sensitive information. To include it, set `send_output` to `true` in the command configuration:

```php
'list_files' => [
    'system'      => ['ls', '-lh'],
    'token'       => 'a-secret-token',
    'timeout'     => 30,
    'send_output' => true,
],
```

Error output is always included in notifications regardless of this setting.

## Logs

Logs are written to the directory specified by `log_directory` and sorted into monthly subdirectories (`YYYY-MM/`). Each day gets its own log file. All times are UTC.

Log lines follow this format:

```
[YYYY-MM-DD HH:MM:SS][sender@example.com] INFO: Executing command: restart_service
```
