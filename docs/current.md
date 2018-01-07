## Release {version}

### New features

#### Configure Command enhancements

- Added ability to hook into Configure command to extend commands with Modules
- Moved `zesk configure subversion` command to `subversion` module
- `zesk\Command_Configure::handle_owner_mode` `$want_mode` validation
- `zesk\Command_Configure` adding comments and ability to hook for custom commands

#### DaemonTools Module and Daemon changes

- Added Daemontools Module and interface
- `zesk daemon` enhancements
- Adding hook `zesk\Command_Daemon::daemon_hooks` to allow collection of daemon processes from any class in the system
- DaemonTools module work

#### Command ANSI colors

- `zesk\Command` supports basic ANSI coloring
- `zesk\Command::exec` is now public
- Updating `zesk\Command` ANSI output

#### Hookable call chains

- `zesk\Hookable::call_hook_arguments` now uses the `$default` value as the initial `$result`
- `zesk\Hookable::collect_hooks` to treat all hooks identically or support alternate accumulation strategies

#### Documentation and Release

- Added `zesk\Directory::list_recursive` documentation
- Adding automatic `etc/db/release-date` generation to Zesk release script

### Improvements

#### `Database_Table`

- `zesk\Database_Table` is now subclass of `zesk\Hookable` to enable hooks for database manipulation calls

#### ORM

- Fixing `zesk\ORM` hook invocation for cache hooks (use `_` not `-`)
- Adding per-database adapters to ORM for mapping from ORM types to SQL types - see `zesk\ORM_Database_Adapter_MySQL`
- Adding support for `zesk\Class_ORM::type_binary` and fixing existing `Schema_Foo` class types for new `ORM`

### Incompatible changes

#### Application configuration changes

- Support `zesk\Configuration_Loader::current` to allow include in same directory
- `zesk\Application` default include files are now: `etc/application.json` and `etc/host/uname.json`
- `zesk\Configuration_Loader::__construct` now only takes a single list of configuration files and does not do any path expansion
- `zesk\Application::configure_include_paths` is now deprecated
- `zesk\Configuration_Loader` API change to only take a list of files, not a list of paths+file extensions to concatenate

#### `zesk\File`

- `zesk\File::stat` returns `["perms"]["decimal"]` which is actually a decimal value (was octal previously), and is truncated to the bottom 9 bits

### Deprecated functionality

- `zesk\Session` will be deprecated and instead will move to modules `session-orm` and `session-php`
- Adding `zesk\Application::session_factory` support, removing `zesk\Session` class permanently
- `zesk\Application::model_classes` is deprecated and is now ignored
- `zesk\Database` splitting out deprecated configuration options
- `zesk\Module::classes` is deprecated, use `zesk\Module::model_classes` to collect models (usually for `zesk\ORM`)

### Fixes

- `zesk\Forgot` cron updates
- Fixing image hook
- Fixing subversion module
- fixing JSON
- fixing `Repository` subclasses
- fixing `Settings` cache delete
- fixing dependency issues
- fixing `version` callback
- support non-string environment variables

<!-- Generated automatically by release-zesk.sh, beware editing! -->
