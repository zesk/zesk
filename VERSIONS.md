# Versions

## v0.8.1

Settling of `zesk\Kernel` and `zesk\` namespace changes, added additional components to `zesk\` namespace, specifically:

- `Database*` classes
- `Exception*` classes, anything inherits from `Zesk_Exception` (now `zesk\Exception`)
- Further refactoring of `zesk\Paths` into application paths and zesk system paths
- Making way for early caching configuration as part of zesk bootstrap (will allow usage of Psr\Cache interfaces)
- Move `log::` to `Module_Logger_File` and `Module_Logger_Footer`
- added `zesk\Logger\Handler` to simplify and speed up logging functionality. Need to determine if `zesk\Logger` is primary interface for logging or just default one.
- Deprecated `$application->modules` as a protected initialization value (use `$load_modules` instead)
- `\Modules` is now `zesk\Modules` and is now found in `$application->modules->`
- Moved `hex::` to `zesk\Hexadecimal::`
- Renamed `Interface_Foo` to `zesk\Interface_Foo`
- `zesk::autotype` renamed to `zesk\PHP::autotype`

## v0.8.0

- Major revamp of the zesk kernel functionality, refactored most of zesk:: to zesk\Kernel
- Added zesk\Classes, zesk\Hooks, zesk\Configuration, zesk\Autoloader, zesk\Logger, $zesk global
- Added bin/deprecated/0.8.0.sh for automatic conversion to new methods
- Deprecated in this release: zesk:: calls are moved into various alternate classes: zesk\Kernel, zesk\Classes, zesk\Configuration, zesk\Hooks, zesk\Compatibility, zesk\Autoloader
 - `zesk::hook` -> `zesk()->hooks->call`
 - `zesk::hook_array` -> `zesk()->hooks->call_arguments`
 - `zesk::class_hierarchy` -> `zesk()->classes->hierarchy`
- Removed growl module (no longer relevant on Mac OS X)

## Heading toward a 1.0

Version 1.0 of Zesk will have:

- Elimination of most globals, moving from `zesk::` to `Application::` in places where it makes sense
- Ability to initialize the application context and then serialize the configuration in a way which allows for faster startup
- PSR-4?
- `zesk\` Namespace for most objects in the system

