# Change Log
<!-- @no-cannon -->

All notable changes to Zesk will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Heading toward a 1.0

Version 1.0 of Zesk will have:

- PSR-4 - Done.
- Full conversion to `camelCase` methods, PHP standards - **in progress**
- Full composer support for both Zesk and commonly used modules - **still need module tested**
- Support for `Monolog` within Zesk core - **needs to be tested**
- All modules use **namespaces** - **in progress**
- Website `https://zesk.com` with basic documentation

### Modules which are currently passing testing

- `CSV` - CSV file loading and manipulation
- `Diff` - PHP implementation of `diff.c`

### Modules which will pass testing in 1.0

- `Cron` - Run hooks in your code on a scheduled basis using `crontab`
- `Daemon` - Support multi-process background daemons running PHP code with full lifecycle management and automatic restart.
- `Locale` - i18n support.
- `Login` - Controller for authentication to a web application
- `Mail` - Internet email support.

### Doctrine-related Modules which will pass testing in 1.0

- `Doctrine` - Layer for ORM abstraction in a Zesk application. `User`, `Server`, `Lock`
- `Job` - Support PHP code execution (using a `Daemon`) in the future.
- `PHPUnit` - Testing for Zesk applications.
- `Session` - Database-based user sessions across distributed systems.
- `World` - A selection of `Country`, `City`, `County`, `Province`, `Currency`, `Language` for your database with populated values in English.

### Modules which will pass testing after 1.0

- `Polyglot` - i18n translation workflow support.
- `Preference` - User settings.

<!-- RELEASE-HERE -->
