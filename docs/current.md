## Release {version}

### New features

- Improved docs
- `zesk classes` now supports outputting alternate formats using `--format json`

### Fixed bugs

- `zesk\Router` was decoding JSON sub-content to objects, not arrays.
- `zesk\Response::process_cached_css` fixed issue with fixing import paths
- fix duplicate confirm
- `zesk\Widget` removing double `validate` hook, and adding `validate_failed` hook
- When zesk is autoloaded, adds an `exit` handler which requires the application to be created; handles exception if application is not created ever
- Adding support for `$lower_dependencies` in `zesk\bash` command interpreter to support new `zesk\Configuration_Loader_Test` case to replicate incorrect dependencies calulation. Fixed issue with `zesk\Configuration_Dependency` and now push and pop configuration names using option `name` Added call `zesk\Configuration_Loader->externals()`

### Removed features

- `zesk\Controller_AJAX` has been removed.

### Changed functionality

- `zesk\Controller_Authenticated` has been moved into the `ORM` module
- `zesk config` now displays just the loading details by default (e.g. `--loaded --not-loaded --skipped`). If you specify any individual flag, the others are then off by default. Previously it showed all sections which is useful less commonly.
- improving comments `zesk\Response_Type_HTML` -> `zesk\Response` `Mail::HEADER_FOO` constants are now capitalized
- `zesk\Session_ORM->initialize_session()` now saves the session instead of waiting until the process exits to ensure the session has an id.
- `zesk\Kernel::set_deprecated` returns `$this`
- `zesk\Controller_Authenticated` sets proper redirect HTTP status
- `dirname(__FILE__)` can now be just `__DIR__` after PHP 5.3
- `Net_HTTP` constants are now ALL CAPS
- `zesk config` default output is less verbose
- Updating usage of `zesk\Response` API
- Removing $URL from svn
- Removed global `zesk::command` set by `zesk\Command_Loader`
- If the `zesk\Route_Controller` action is not a string, then throw an exception
- If `zesk\ORM->members()` call hits an unknown object, returns NULL now instead of throwing an error.
- Allow `zesk\Configuration` constructor with no arguments
- Hook `zesk\Router::new` renamed to `zesk\Router::construct`
- `zesk classes` now supports outputting alternate formats using `--format json`

### Widget changes

- `zesk\Control_Login` has a new hook `submit` which can be used to short-circuit what response is returned by an authenticated login.
- `zesk\Control_Select`: Supporting `key => array` for allowing attributes in `<option>`
- Added call `zesk\Widget->prefer_json()` to determine if JSON should be returned, added to `zesk\Control_Login`

### Test changes

- `zesk\Test` reversed `$actual, $expected` to `$expected, $actual` to match with what PHPUnit does as we'll be migrating to that platform for testing.
- `zesk\Test\Module` is module class now
- `zesk\PHPUnit_TestCase` saves last test run in a hidden file
- `zesk test --database-reset` is now `zesk test --reset` and runs no tests, but rather resets the database on disk. `zesk test --database-report` is now `zesk test --report`
- New `zesk\Configuration_Loader_Test` test case to replicate incorrect dependencies calculation.
- Adding `assertArrayHasKeys` which is oddly not in PHPUnit's assertions library

#### Application

- `zesk\Application::configured` hook was removed is its usage pattern did not make sense.
- `zesk\Application::theme` no longer invokes the `theme` hook on objects which are passed as the content to it. Use `$object->theme()` instead.
- `zesk\Kernel::set_deprecated` returns `$this`
- Hook renamed `zesk\Application::response_output` => `zesk\Response::response_output_before`
- Hook renamed `zesk\Application::response_outputted` => `zesk\Response::response_output_after`
- Hook renamed `zesk\Response::output` => `zesk\Response::output_before`
- Hook renamed `zesk\Response::outputted` => `zesk\Response::output_after`
- `zesk\Response::content()` was added as a getter/setter for `zesk\Response` content to enable chained calls to set up the `zesk\Response` (e.g. `return $response->content("dude")->status(301);`)
- Hook `zesk\Router::new` renamed to `zesk\Router::construct`
- `zesk\Application` now inherits configuration files settings prior to `configured_files` hook is called
- Fixing `zesk info`
- Hook renamed `zesk\Response::output` => `zesk\Response::output_before` - Hook renamed `zesk\Response::outputted` => `zesk\Response::output_after` - `zesk\Response::content()` was added as a getter/setter for `zesk\Response` content to enable chained calls to set up the `zesk\Response` (e.g. `return $response->content("dude")->status(301);`)
- `zesk\Application::configured` hook was removed is its usage pattern did not make sense. - `zesk\Response::content()` was added as a getter/setter for `zesk\Response` content to enable chained calls to set up the `zesk\Response` (e.g. `return $response->content("dude")->status(301);`)
- `zesk\Application::theme` no longer invokes the `theme` hook on objects which are passed as the content to it. Use `$object->theme()` instead.
- `zesk\Response::process_cached_css` fixed issue with fixing import paths

#### ORM module

- `zesk\ORM::members()` and related calls now transform each member via the equivalent of the `__get` call. In addition, a new protected internal method `zesk\ORM::_get` can be used to retrieve a member from an `ORM`. Note that previously, members were returned untransformed (e.g. "2018-07-03 16:35:50" instead of an object of `zesk\Timestamp` for example)
- `zesk\ORM::json()` now supports option `resolve_methods` which is a list (string or array) of methods to attempt to convert objects to JSON syntax. Default is `["json"]`.

#### Developer module

The following URL paths have been renamed in the `Developer` module:

- `debug` -> `developer/debug`
- `development` -> `developer/development`
- `session` -> `developer/session`
- `schema` -> `developer/schema`

Fixed some issues with the system/debug theme.

#### Module updates

- `zesk\Module_ThreeJS` is now `zesk\ThreeJS\Module` and PSR-4
- Fixing refactored `Diff` module and tests to pass
- `zesk\Diff_Lines` is now `zesk\Diff\Lines` etc.
- Upgrading Flot, Apache modules
- Tag module PSR4 + refactor
- PSR4 for Contact module
- PSR4 for Tag module
- PSR4 for jQuery-Unveil module
- PSR4 work on Contact module
- JSONJS updated to PSR4, etc
- Adding template `run` for DaemonTools module

#### Cannon updates

- adding StringTools::wrap cannon
<!-- Generated automatically by release-zesk.sh, beware editing! -->
