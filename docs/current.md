## Release {version}

## Cron Module

- Added `zesk cron --reset` to reset cron timers to re-run all cron tasks (across all servers!)
- Cron run changes. Cron used to acquire scoping locks (per-cluster and per server) at the start of any cron tasks related to those scopes. The old way was: acquire cluster lock, cluster cron tasks, release cluster lock, acquire server lock, server cron tasks, release server lock. The new way is now: acquire cluster lock, acquire server lock, cluster cron tasks, server cron tasks, release cluster lock, release server lock. The reasoning is that locks should be acquired at the start of process and released at the end of the process, to prevent race conditions between competing servers.
- `zesk cron` now outputs the context in which it invokes methods. Have seen issues with methods being called twice (once via lowercase classname).

## ORM Module

- Fixing support for `zesk\ORM->dependencies()` and related `zesk\Class_ORM->dependencies(ORM $object)`
- Minor documentation updates to `zesk\Meta` class and `zesk\Class_Meta`
- Moved `zesk\ORM->dependencies()` to `zesk\Class_ORM`
- `zesk\ORM` fixing error/warning when dynamically typed `zesk\Class_ORM::type_object` members are loaded with blank values.
- `zesk\Server::singleton` now has secondary check to expire cache based on `zesk\Server` alive setting.
- `zesk\Server` now supports `->delete_all_data` to delete a data member across all servers.
- `zesk database-dump` now does not crash

## PolyGlot Module

- Added locks around updates to prevent/thwart race conditions. Updating database entries upon disk update.

## Repository Module

- `zesk\Repository` now supports `->versions()` call to list of software versions as stored in a repository.

## WebApp Module

- Fixing `zesk\WebApp\Module` model classes to return correct `zesk\WebApp\Repository` name (was `zesk\Repository`)
- Initial version now registers all objects, generates valid Apache configurations. Next up: assigning hosts.
- Removed duplicate class `zesk\Command_Release` in `subversion` module (was work-in-progress)
- Support for Apache per-directory settings, fixing `zesk\WebApp\Site` find-by-keys, added raw `json` structure to `zesk\WebApp\Instance`, support for keyed/authenticated controller actions
- `zesk deploy` moved from Zesk core into Zesk module `WebApp`
- initial WebApp management/deployment module

## Zesk core changes

- Pre-commit hook was not handling renamed files correctly, fixed.
- `daemon-bouncer.sh` Made less verbose way to redirect stdout -> stderr
- `zesk configure`: Moved the majority of the configuration engine into `zesk\Configure\Engine` and connected back to command via `zesk\Interface_Prompt` and `zesk\Logger\Handler`. The idea here is that configuration requirements can be specified using the simple configure meta-language by web applications in `zesk\WebApp\Instance`. Also has applications elsewhere.
- `zesk eval` now outputs results by default and supports multi-statement arguments a little better.
- `zesk\Autoloader` - Adding `OPTION_FOO` constants for Autoloader path options
- `zesk\Command_Loader` no longer strips `+` characters from command-line arguments, but does a `rawurldecode` instead
- `zesk\Command` now outputs less verbose and friendlier error messages.
- `zesk\Configuration\Loader` making variable constants
- `zesk\Directory::list_recursive` now modifies directory strings to ensure they **MUST** end with a trailing slash prior to being matched against the listing rules. So you can better match against directories by enforcing a trailing slash; regular files will **NEVER** have a trailing slash.
- `zesk\Directory` updated documentation
- `zesk\Exception_Class_NotFound` now reports the name of the class as part of the default message.
- `zesk\Hookable::combine_hook_results` used to catenate strings which were passed through a filter, it now returns the new string back. The only mechanism which modifies hook results is `Arrays`: list-style arrays are catenated, key-value arrays are merged with later values overriding earlier values.
- `zesk\Hookable` removing old documentation
- `zesk\Interface_Prompt` defined to support interactivity connectivity between code. `zesk\Command` implements `zesk\Interface_Prompt`.
- `zesk\Kernel` - changed to using __DIR__ constant to include instead of variable
- `zesk\ORM` catches all `\Exception`s now
- `zesk\System::ifconfig` returns a generic localhost set of settings if a system error occurs (which probably is a mistake), but the structure was invalid for `zesk\System::ip_addresses` which parsed the structure. The default structure has been redesigned so it is now processed correctly.
- `zesk\Text::format_pairs` now requires the first parameter to be an `array`
- zesk\Options::option_space` renamed to `zesk\Options::OPTION_SPACE`
- `zesk\arr` is now REMOVED (no longer deprecated)
- **Data Structures**: `zesk\Trie` and `zesk\Trie\Node` refactored and need testing
- **General**: Updating comments throughout the code
- **Module System**: `zesk\Module` begun deprecation of `zesk_command_path_class_prefix` -> `zesk_command_class_prefix`
- **Testing** `test-zesk.sh`: Set up `$APPLICATION_ROOT` and use absolute paths for binaries
- `zesk-command.php` now adds an ini setting `display_startup_errors=Off` to prevent errors from creeping into shell outputs in `zesk\WebApp\Type\Zesk` on Mac OS X.

<!-- Generated automatically by release-zesk.sh, beware editing! -->
