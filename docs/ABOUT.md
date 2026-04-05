# About Procco

Procco (Process Commando) is a PHP agent that lets you trigger system commands from any input source. It is designed to be run on a schedule (e.g. via cron) and acts as a secure bridge between an input handler and the host system.

The input source is determined by the configured input handler. The built-in handler uses e-mail (IMAP), but any source can be supported by implementing a custom handler — a webhook listener, an SMS gateway, etc.

## How It Works

1. The agent polls the configured input source for new requests.
2. Each request is parsed for a `COMMAND`, `TOKEN`, and optional `ARGUMENTS` field.
3. The sender/source is checked against an optional whitelist (if the handler supports it).
4. The token is validated against the expected token for the requested command.
5. If all checks pass, the system command defined for that command is executed.
6. Optionally, a notification is sent with the result (output, exit code, any errors).

## Responsibility

**You are responsible for what you configure.** Procco executes exactly what you define in your configuration. If you configure a command that deletes files, formats a disk, or otherwise damages your system, that is your own doing. Protections such as token validation, sender whitelisting, and process isolation are provided by default — but they only protect against unauthorized use, not misconfiguration. Review your command definitions carefully before deploying.

## Security Model

- **Token validation** — every command has its own token. A request is only executed if the correct token is provided.
- **Source whitelist** — input handlers may optionally restrict which sources (e.g. sender addresses) are permitted to send commands.
- **Explicit command list** — only commands defined in `config.php` can be executed. Unknown commands are rejected.
- **Process isolation** — commands are run via Symfony Process, which avoids shell injection by accepting commands as arrays rather than shell strings.
