# Procco

Procco (Process Commando) is a PHP agent that lets you trigger system commands from any input source. It is designed to be run on a schedule (e.g. via cron) and acts as a secure bridge between an input handler and the host system.

The input source is determined by the configured input handler. The built-in handler uses e-mail (IMAP), but any source can be supported by implementing a custom handler — a webhook listener, an SMS gateway, etc.

## Requirements

- PHP 8.4+
- PHP `imap` extension (`php -m | grep imap`)
- Linux host (only tested platform)
- Optional: `sudo` privileges for commands requiring elevated permissions

## Installation

```bash
git clone https://github.com/Jammmmm/Procco.git
cd procco
composer install
cp config.example.php /path/to/myconfig.php
# Edit myconfig.php with your settings
```

## Usage

See [docs/USAGE.md](docs/USAGE.md) for full configuration reference and usage instructions.

## Architecture

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for a component overview and guide to extending Procco with custom input and notification handlers.

## Security

See [docs/ABOUT.md](docs/ABOUT.md) for the security model.

> **You are responsible for what you configure.** Procco executes exactly what you define in your configuration. If you configure a command that deletes files, formats a disk, or otherwise damages your system, that is your own doing. Protections such as token validation, sender whitelisting, and process isolation are provided by default — but they only protect against unauthorized use, not misconfiguration. Review your command definitions carefully before deploying.

## Changelog

See [CHANGELOG.md](CHANGELOG.md).

## License

Licensed under the [GNU General Public License v3.0](LICENSE).
