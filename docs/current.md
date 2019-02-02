## Release {version}

Largely refactoring constants to follow **PSR-1** 4.1 (Class constanst upper case with underscores), and a variety of bug fixes and improvements for PHP 7.2.

### Core

- Autoloader, by default, does not throw exceptions anymore to be compatible with standard autoloader behavior
- Added compatibility for `is_countable` Fixed last(array $x) to work when indexed with numbers and strings which happen to match the number of keys - 1
- Massive coding style updates using PHPCS
- Adding @see comment to `zesk\Color_RGB`
- Breaking off `zesk\Application::determine_route` to make debugging steps simpler
- Display more detailed errors in console
- Adding `zesk\Kernel::create_application` hook
- Fix `zesk\Controller_Theme::error` to return self
- Fixed `zesk info` to properly call `zesk\Version::string($locale)`
- Ensuring operation with PHP 7.2 issue. (support array-only count)
- Support Zesk built-in etc/cacert.pem or no verify peer on SSL connections
- Model Settings stored hook
- Use class names instead of strings
- Fixing conversion to number for `IPv4::to_integer`
- Fixing `Lists::` capitalization and other related issues
- Found a bizarre bug in `zesk\Application::theme` which converts a local variable (n array) to a string out-of-scope from an internal called function; adding code to prevent warnings due to the error in PHP core.
- `map` function `$insensitive` parameter actually defaults to `false`, fixing doccomment to match implementation
- `zesk\Application::version` added `$set` parameter for optionally setting application version from external libraries
- `zesk\Application`: Main exception should inherit variables from `zesk\Exception::exception_variables()`
- `zesk\Hexadecimal::decode` input is now sanitized for valid characters only
- `zesk\Hooks` updating constant values to be ALL_CAPS
- `zesk\Mail::RFC2047HEADER` Change constants to capitals only
- `zesk\Mail::mulitpart_send` has been removed. `zesk\Mail::header_foo` also removed (used uppercase versions of same constants)
- `zesk\Options::option_path` wasn't working well, converted it to use `apath()`
- `zesk\PHP::autotype` now supports a 2nd parameter `$throw` (boolean) which will throw an error (default behavior) when an invalid JSON structure is passed in. If set to `false`, the raw string is returned for invalid JSON entries.
- `zesk\Response::to_json` should correctly convert `zesk\Reponse\Type` to JSON
- `zesk\Response\Type::to_json` is now a required abstract function
- `zesk\Response_Test` fixes for updated `->html()` output format
- `zesk\Response` Better support for IE html outputs

### Binaries

- `fix-zesk.sh` now supports operating from current directory or environment variable `PHP_CS_FIXER_TOP` if defined
- `svn-build.sh` now dynamically finds app root based on how zesk works
- `svn-build.sh`: Fixing usage, unknown argument error, skip vendor directories for `BUILD_DIRECTORY_LIST`
- fixing composer and daemon-bouncer.sh path
- fixing daemon-bouncer.sh installation

### Cron Module

- lock cleanup screwing everything up in `zesk\Cron` module

### ORM Module

- Fixed an issue with `zesk\ORMIterators` improperly initializing `zesk\ORMIterator::$object`
- `zesk\Class_ORM` Removed deprecated call to `$app->object`, and instead use `$app->orm_registry`
- `zesk\Exception_ORM_Empty` message now passes back class name which is missing primary keys
- `zesk\Server` does not require `name_external` to be set to insert into the database
- `zesk\User::password()` now supports internal hashing, hash upgrading, and `$plaintext` flag.
- `zesk\User` support password binary hash storage and inherit from class settings. Internalizing password hashing as part of `zesk\User` class.
- **Forgot** module improvements - expiration and better `React` support

### NodeJS Module

- NodeJS Module: Adding inherit app version from a `package.json` version

### Testing Module

- Added `zesk\Adapter\TestFramework::assertContains()`
- `zesk\PHPUnit\TestCase` fixing unused variable usage

### Database Module

- **MySQL Module** `MySQL\Database::shell_command` now uses credentials files to avoid warnings about passwords on command-line
- Do not pass all options to `zesk\Database::shell_command` from `zesk database-connect` command
- `MySQL\Database::shell_command()` now accepts a new option `non-blocking` with a boolean value to dump databases in a non-blocking manner. Also added support for `MySQL\Database::$shell_command_options` to validate shell commands and provide internal documentation to their effects.
- fixing `zesk info` for database module
- Adding `zesk database-connect --grant --host localhost` option to support generating grant commands given existing credentials configured.

### Deprecate code

- Removed deprecated `zesk\Response_Text_HTML`

<!-- Generated automatically by release-zesk.sh, beware editing! -->
