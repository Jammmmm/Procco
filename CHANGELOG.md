# Changelog

All notable changes to this project will be documented in this file.

## [1.0.0] - 2026-04-05

### Added
- Core functionality to poll an IMAP inbox for `COMMAND`, `TOKEN`, and optional `ARGUMENTS` fields.
- Token validation per command using constant-time comparison (`hash_equals`) to prevent timing attacks.
- Optional sender whitelist (`allowed_senders`); all senders are trusted if left empty.
- `allow_arguments` command option — when `true`, runtime `ARGUMENTS` from the request are appended to the system command.
- `send_output` command option — when `true`, command stdout is included in the notification e-mail. Defaults to `false` to prevent accidental output leakage.
- `log_output` command option — when `true`, command stdout is included in the log entry on success. Defaults to `false`.
- `email_processed_action` handler option — controls whether processed e-mails are marked as read (`read`) or permanently deleted (`delete`). Defaults to `read`.
- Logging system with UTC timestamps and daily rotating log files (`logs/YYYY-MM/YYYY-MM-DD.log`). Each log entry includes the sender address and command name.
- E-mail notifications for command results, missing or unknown commands, token failures, and unauthorised senders.
- Sender address is included in notification e-mails and log entries.
- Commands are executed via Symfony Process using argument arrays, preventing shell injection.
- Sender addresses are validated with `FILTER_VALIDATE_EMAIL` before use to prevent header injection.
- `--config` CLI flag (required) for specifying the configuration file. Running without arguments prints usage text.
- `config.example.php` example configuration file covering all available options.
- Full documentation in `docs/` covering usage, architecture, and about/security model.
- Licensed under GNU General Public License v3.0.
