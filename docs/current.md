## Release {version}

### PHP 7 Changes

- `$errcontext` is deprecated - removed from error handlers
- Adding PHP 7.2 compatibility checker
- `create_function` is deprecated in PHP 7.2 - removed from codebase

### Feed Module

- Adding `CachedFeed` to feed module
- Fixed issue with serializing `Feed_Post` values

### `zesk configure` enhancements

Enhancements to the `zesk\Repository` and `Subversion` modules were related to these changes:

- Adding `rmdir` command to `zesk configure` command
- Expanded `zesk\Repository` API to support `zesk\Repository::info()` and `zesk\Repository::need_update()`, and updated implementation of `zesk\Subversion\Repository::status()` to use XML output instead. Updated tests.
- Deprecated call `zesk\Paths::command()` - use `$_SERVER['PATH']` to update this value dynamically instead.
- Repository work to fix `zesk\Subversion\Repository`
- `zesk configure` command uses app locale
- `zesk configure` updates to yarn command
- `zesk\Repository::pre_update` and `zesk\Repository::post_update` were deprecated and removed (and unused). Use `zesk\Repository::need_update` and `zesk\Repository::need_commit` instead. Note that `zesk\Git\Repository` is largely unimplemented.
- adding `ArrayTools::ifind()` and using in `zesk configure`
- `configure composer` and `yarn` commands added
- fixing `zesk\Subversion\Repository::need_update()` to check remote
- fixing `zesk\Subversion\Repository::need_update()` to normalize URLs
- fixing `rmdir` command for `zesk configure`
- fixing `subversion` repo tests and status xml parsing
- fixing url match in `zesk\Subversion\Repository`
- fixing `yarn` args
- fixing `yarn` command-line parameters
- no `--flat` for `yarn` installations - causes errors
- piping `composer` to STDOUT, adding process debug

### Test updates

- Adding `zesk\Timestamp` serialize tests
- ignore test case last file generated for phpunit

### Deprecated functionality 

- `zesk\Kernel::singleton()` now throws a `zesk\Exception_Semantics` if `zesk\Kernel::factory($options)` is not called first.

### Bugs fixed

- Removing `Locale::duration_string` unnecessary `$locale` parameter
- When no server locks exist, do not throw an error

### Forgotten Password/Login refactoring

- Fixing issue with CacheItem storage
- Fixing `Control_Login` log message upon login
- Fixing issue with `Hookable` concatenation of strings for `Controller_Markdown`
- Forgotten password refactoring
- Refactoring `Forgot` module to increase security; password is set upon return not upon send.

### General

- Support milliseconds in `zesk\Timestamp::ymd_hms` call
- adding members_handler to `zesk\ORM::json`
- Fixed all misspellings of `diretory` => `directory` 

<!-- Generated automatically by release-zesk.sh, beware editing! -->
