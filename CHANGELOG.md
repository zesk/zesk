# Change Log

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/)
and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Heading toward a 1.0

Version 1.0 of Zesk will have:

- Elimination of most globals, moving from `zesk::` to `Application::` in places where it makes sense - **in progress**
- Ability to initialize the application context and then serialize the configuration in a way which allows for faster startup (maybe)
- PSR-4? - **Yes, for zesk core.**
- Full composer support for both Zesk as well as commonly used modules - **still need module tested**
- Support for `Psr/Cache` for caching within Zesk - **needs to be tested**
- Support for `Monolog` within Zesk core - **needs to be tested**
- All modules use **namespaces** - **in progress**
- Merging of `Response` and `Response_Text_HTML` into a single, unified polymorphic `Response` which changes behavior depending on content-type but allows typed API calls for specific response handling. May move `Response_Text_HTML` into a sub-object (e.g. `$response->html()->add_body_class()` for example)
- Migrate `Database_Result_Iterator` to remove dependency on `Database_Query_Select_Base` 

### 1.0 Things Completed

- <strike>Renaming of `zesk\ORM` to something non-reserved in PHP 7.2 (Candidates are `zesk\ORM` `zesk\Model` - reuse)</strike>
- `zesk\` namespace for all `classes` in the system

## [Unreleased][]


### New features

- `zesk\Command` supports basic ANSI coloring
- `zesk\Command::exec` is now public
- `zesk\Hookable::call_hook_arguments` now uses the `$default` value as the initial `$result`

### Incompatible changes

- `zesk\File::stat` returns `["perms"]["decimal"]` which is actually a decimal value (was octal previously), and is truncated to the bottom 9 bits
- `zesk\Configuration_Loader::__construct` now only takes a single list of configuration files and does not do any path expansion

## [v0.14.4][]

### Fixed bugs

- Fixing `Bootstrap-DateTimePicker` theme precedence
- Fixing `app()` use of global calls
- Fixing `zesk\Model_Settings` usage of `zesk\Settings` global
- `zesk\Controller_Content_Cache::image_from_url` should take `$application` as first parameter

## [v0.14.3][]

- Made some bug fixes to `zesk\CacheItemPool_File` which caused files to not be saved
- `zesk\Application` adds an exit hook to call `$application->cache->commit()` upon exit

## [v0.14.2][]

### Fixed bugs

- Hook `ClassName::register_all_hooks` must take `zesk\Application` as first parameter and did not. Fixed.

## [v0.14.1][]

### New features

- `zesk\Application` now supports the function calls like `codename_module()` to retrieve module objects. So, `$application->csv_module()` is equivalent to `$application->modules->object("csv")`. You can decorate your application with `@method` doccomments to support types and return values in Eclipse-based editors. See `zesk\Application` class DocComment for examples.
- Added `Database::TABLE_INFO` constants and new `zesk\Database` class abstract `zesk\Database::table_information` call
- adding basic CacheItemPool and CacheItem classes

### Fixed bugs

- Fixing `zesk\Process_Tools::process_code_changed` calls in `zesk daemon`
- Fixed SES test file
- Fixing linked classes using `Foo::class` constant instead of strings
- Fixed some ORM minor issues

### Deprecated functionality

- Rewriting `cache` calls in `zesk\ORM` to support `CacheItemInterface` instead of `zesk\Cache`
- `Cache::` removal
- removed `zesk()` globals
- Removing deprecated configuration path `zesk::paths::`

### Removed functionality

- `zesk\User::current`, `zesk\User::set_current`, `zesk\User::current_id` have all been removed
- `zesk\Application::instance` was removed
- `zesk\Application::_class_cache` is obsolete
- `zesk\Settings::instance` was removed

## [v0.14.0][]

### Deprecated functionality

- `zesk\URL` marked deprecated `current_*` calls for future removal, added comments.
- `zesk\Application::query_foo` calls are all deprecated

### New modules

- `ORM`, `Session` and `Widget` are now modules.
- `Database_Query_*` have been moved to `ORM` (including comments such as `database-schema` etc.)
- `Widget` classes amoved to `Widget` module and depends on `ORM`

### Renamed classes

- Anything which had the term `Object` as a namespace or related has been renamed to `ORM` or possibly `Model`
- `Database_Schema` has been renamed to `ORM_Schema`

### ORM Rename

Yes, we've renamed the `zesk\Object` class to `zesk\ORM` because PHP 7.2 makes the term `Object` a reserved word for class or namespace names. So... Major changes in how system is organized:

- `modules/orm` added and all `Object` and `Class_Object` funtcionality moved into there and renamed `ORM` and `Class_ORM` respectively.
- Refactored `Session` and moved to its own module. Likely will need future detachment from the `zesk\Application` class
- Refactored references to `ORM`-related classes and moved into `ORM` module (`User`, `Lock`, `Domain`, `Meta`, )
- References to `ORM` will be detached from zesk core in this and upcoming releases
- When possible `zesk\ORM` references are migrated to `zesk\Model` references instead

Due to the fact that `Database_Query` subclasses all depend on `ORM` and all `zesk\Widget` support `Database_Query_Select`, so all widgets have been moved to their own module `widget` which depends on `orm` as well.

### Application registry and factory calls

The `zesk\Application` is the center of your application, naturally, but it has evolved to the central point for object creation. To allow distrubution of the factory resposibilities, `zesk\Application` now allows modules (or anything) to register a central factory method. So:

	class MyInvoiceModule extends \zesk\Module {
		public function initialize() {
			// This adds the method "invoice_factory" to the zesk\Application
			$application->register_factory("invoice", array($this, "invoice_factory"));
		}
		public function invoice_factory(Application $application, $code) {
			return new Invoice($application, $code);
		}
	}

There are a variety of new patterns, largely those which remove `ORM` functionality from the `zesk\Application` core.

The main point here is that shortcuts which previously pulled a bit of data from the `Class_Object` (now `Class_ORM`) should use the full call, so:

OLD method:

	$application->query_select(User::class, "user");
	$application->class_object_table(User::class);
	$application->class_object(User::class);
	$application->synchronize_schema();
	
NEW method:

	$application->orm_registry(User::class)->query_select("user")
	$application->class_orm_registry(User::class)->table()
	$application->class_orm_registry(User::class);
	$application->orm_registry()->schema_synchronize();

Two calls are available now from the `zesk\Application`:

	$application->orm_factory($class = null, $mixed = null, array $options = array())
	$application->class_orm_registry($class = null)
	$application->orm_registry($class = null)

The main difference between a `registry` and `factory` call is that the `registry` call returns the same object each time.

## [v0.13.2][]

### Incompatibilities

- Zesk now only supports PHP version 5.5 and higher as we use `ClassName::class`

### Bug fixes

- Static permissions check fix added in `permission` module
- Fixing a variety of issues in the Markdown, OpenLayers, and release scripts

### Cleaner Module

The cleaner module cleans files or log files after a certain period of time elapses, and is run via cron.

- `zesk\Cleaner\Module::directories::cleaner_name::lifetime` Now supports conversion of time units, e.g. "2 hours", "4 days", "2 weeks". It uses `strtotime` relative to the current time to determine the time period.

## [v0.13.1][]

- Adding `"lower": boolean` setting to `autoload_options` in modules
- Fixed issues with Cron module loading

## [v0.13.0][]

### Marked as deprecated 

- The external usage of `zesk\Template` objects is now discouraged and all applications are encouraged to use `zesk\Application::theme` only to render content.
- `zesk\Module::$classes` has been deprecated permanently.
- `zesk\Directory::temporary` has just been marked as deprecated
- `zesk\Request::url_parts` is marked as deprecated, use `zesk\Request::url_variables`
- Deprecating `zesk\Module::$classes`
- `zesk\Control_Edit` deprecated use of the `widgets_filter` option, use `widgets_include` instead. Deprecated warning added.

### Deprecated and removed

- `zesk\Paths::document*`, `zesk\Paths::module_path`, `zesk\Paths::share_path`, and `zesk\Paths::zesk_command_path` were removed (deprecated 2016)`
- `zesk\Application::singleton` has been removed (use `::object_singleton`)
- `modules` was trimmed significantly and moved to a 2nd repository for future cleanup

### Changed functionality

- `Controller_Forgot`, `Controller_ORM`, ``, `` and `` all now inherit from `Controller_Theme`
- Zesk will now adopt the usage of using `ClassName::class` instead of the more complex `__NAMESPACE__ . "\\" . "ClassName"` or strings.
- `zesk\Timestamp::parse` would **fail** for dates before 1970 as `strtotime` returned a negative number for those dates.
- `zesk\Application::theme_path` now supports a prefix for each theme to prevent deep directories for classes which override sections of the theme tree.
- Added `final` to various `zesk\Application` functions to lock down the subclass responsibilities.
- Global configuration starting with `zesk` is now deprecated; use `zesk\Kernel` or other classes instead. Removed deprecated `document_cache` and related globals in `zesk\Kernel` object.
- `zesk\Net_Client` is now a child of `zesk\Hookable` not `zesk\Options`
- `zesk\Debug::dump` Set indent limit to 4
- `zesk\Model::__construct` now calls hook `construct`

### New features

- `zesk\Settings::prefix_updated` Allows for automatic renaming of settings when class names change. Use with caution, usually in `hook_schema_updated`
- `zesk\Hooks::add` now supports the passing of an `'arguments'` key in the `$options` parameter to allow each invokation to have starting paramters which are added upon registration. This is equivalent to JavaScript's `.bind` functionality.
- Pesky gremlin in one of your files outputting whitespace and you're not sure where? `zesk\Autoloader::debug` now sets debugging to check for whitespace output on autoload. Try it out!
- `zesk\Route` file formats now support `GET|POST:path/to/url` for specifying paths to support HTTP Method support.
- `zesk\Settings::prefix_updated` added to support mapping old settings to new upon hook `schema_updated` or other triggers
- `zesk\Version::date()` added
- Work on `zesk\Repository` and `zesk\Git\Repository` and `zesk\Subversion\Repository` to add to `zesk release` command (see `bin/release-zesk.sh`)
- Add `command` field to `zesk\Paths::variables()`
- Added document on how to trigger debugging in various parts of `zesk`
- `zesk\Module` JSON now supports `theme_path_prefix` for auto configuration

### Templates/Theme System

- `zesk\Template` moved template finding over to `zesk\Application`
- `zesk\Application::theme_variable` added to set state of current `Template` stack
- `zesk\Application::theme_find` added to find final path for a theme
- Adding `zesk\Application::theme_variable` and `zesk\Controller::theme_variable`
- Deprecating `zesk\Controller_Template` and subclass `zesk\Controller_Template_Login`. Use `zesk\Controller_Theme` and `zesk\Controller_Authenticated` instead.

### Module changes

- Module `DBLog`: The default table name for this module is now `Log`. 
- The internal `zesk\Modules` structure used to have the key `module` which held the `zesk\Module` object; it is being moved to the `object` key instead; both keys will be populated for the deprecation period.
- Refactored the AWS module for better support for class structures
- Moved `zesk\Module_Cleaner` to `zesk\Cleaner\Module`
- Cleanup `zesk\Control_IP_List` control, adding comments and moving option names to `const OPTION_FOO`
- Renamed `zesk\Module_Cron` to `zesk\Cron\Module` and uses `zesk\Cron` namespace, removed deprecated `cron` class.

### **Zesk Modules Directory** 

- The `modules` directory has been significantly pruned, with most of the modules moved to a [staging project](https://github.com/zesk/xmodules)

### **Zesk Share directory**

- The zesk share directory has been completely pruned, leaving only a handful of `.js` and `.css` files
- Remaining dangling images and stylesheets will be replaced with Bootstrap-themed variations as needed
- See `share/readme.md` for more details

## [v0.12.15][]

- Add protected member `$generated_sql` to `zesk\Database_Query_Select` to aid in debugging
- Added `zesk\HTML::span_open` and `zesk\HTML::span_close` for convenience
- Added `zesk\Kernel::copyright_holder()` to retrieve Zesk copyright holder
- Added `zesk\Process::user()` to retrieve current process user name
- Adding additional fixes for `zesk\Options::__construct()` requiring an array parameter
- Enhanced `zesk\ORM::json()` to support `class_info` and `members` options to modify JSON output
- Fixed some `zesk\Router` sleep issues
- Fixing `zesk help` command
- Fixing issues with `zesk\Adapter_Settings_Array`
- Initialize `$application` in `zesk\Hookable:__wakeup`
- Minor refactoring of `zesk\Paths`
- Reformatted `AWS_SQS`
- Removed dependencies on `zesk()`
- Removed instances of `global $zesk` from `zesk configure` command
- Removed unused code from `AWS_EC2_Awareness`
- Removing old debugging code from `classes/Autoloader.php`
- Various upgrades and missed deprecated calls
- Working on `zesk test-generate` functionality
- `zesk configure` fixing message {old_file} message
- `zesk\Adapter_Settings_Array` should properly handle hierarchical sets/gets like `zesk\Configuration` etc.
- `zesk\Application::$classes` now defaults to `array()`
- `zesk\Controller` now calls hook `initialize` upon construction
- `zesk\Options` takes an `array` in constructor
- `zesk\Route::__wakeup` should initialize object globals (e.g. `$router`)
- `zesk\Widget` should inherit all subclass global options upon creation
- `zesk` command - support `--cd` to a link

## [v0.12.14][]

- Adding `zesk\PHP_Inspector` tool which given a file, returns declared classes and functions and provides simple access to `\ReflectionClass` factories.
- Removed instances of `global $zesk` from `zesk configure` command
- Removed warnings generated by Zend Studio
- Worked on `zesk\Command_Test_Generate` - still not finished
- `zesk\Adapter_Settings_Array` should properly handle hierarchical sets/gets like `zesk\Configuration`
- `zesk\Command_Iterator_File`: Fix parameter types and allow for early termination by returning `false` from `process_file` call
- `zesk\Session_Mock` fixed construction to require `array $options = array()`
- `zesk\Session_PHP` fixed construction to require `array $options = array()`
- `zesk` command - support `--cd` to a link
- push, then pull at end of `release-zesk.sh`
- Fixing `zesk help` command
- `zesk configure` improvements
 - Fixing issue with `zesk configure` and `defined` for case-sensitivity
 - `zesk configure` now supports only case-insensitive variables in `configure` files
- Whitespace comment cleanup


## [v0.12.13][]

- `git push` at end of `bin\release-zesk.sh` (not `pull`)
- `zesk configure` now supports only case-insensitive variables in `configure` files

## [v0.12.12][]

- `zesk configure` added `defined` command to ensure variables are defined first
- `git pull` at end of `bin/release-zesk.sh`

## [v0.12.11][]

- Adding {home} variable to `zesk configure`

## [v0.12.10][]

- Allow invalid `zesk\Database::names::foo` urls while configuration is being set up


## [v0.12.9][]

- Adding flags to `zesk configure` for `file_catenate` command

## [v0.12.8][]

- Updated `zesk configure` documentation
- Fixed missing command error message in `zesk update`

## [v0.12.7][]

- `zesk update` supports finding composer binary


## [v0.12.6][]

- Adding `zesk\Adapter_Settings_ArrayNoCase` class
- Fixing issue with `zesk\Command_Configure and case-insensitive variables in global conf`


## [v0.12.5][]

- fixing `zesk\Configuration_Editor_CONF` constructor

## [v0.12.4][]

- Fixing `zesk\Configuration_Editor_CONF` constructor
- Adding module dependency which requires `PHPUnit`

## [v0.12.3][]

- removed deprecated `modulename.module.php` files in 3 modules

## [v0.12.2][]

- Fixing `zesk\Database_SQL::function_max` and `zesk\Database_SQL::function_min`
- Fixing and removing references to `zesk()` and `global $zesk`
- Handle default `zesk\Preference`s better
- Passing `null` instead of `false` to some `zesk\Database::query` functions
- Use share path for `theme/body/exception`
- `::inherit_global_options` moved to `zesk\Hookable` and further expansion of requiring `$options` to be an array in constructors.
- `zesk\Application::application_root` is now `zesk\Application::path`
- `zesk\Application::application_root` renamed to `zesk\Application::path`
- `zesk\Hookable` now requires `$application` as first parameter to constructor
- `zesk\ORM::factory` deprecated
- `zesk\World_Bootstrap_Currency` now outputs missing currencies correctly
- adding `zesk\Application` as parameter to creating `zesk\Net_Client`
- Added some notes on changing db character sets
- quieter `Locale` shutdown
- reduce `_dump` verbosity
- removing globals
- zesk\Module\SMS work


## [v0.12.2][]

- Increase `$application` passing around
- Fixing `zesk\Database_SQL::function_max` and `zesk\Database_SQL::function_min`
- Fixing and removing references to `zesk()` and `global $zesk`
- Handle default `zesk\Preference`s better
- Passing `null` instead of `false` to some `zesk\Database::query` functions
- Removing router statics
- Use share path for `theme/body/exception`
- `::inherit_global_options` moved to `zesk\Hookable` and further expansion of requiring `$options` to be an array in constructors.
- `zesk\Application::application_root` renamed to `zesk\Application::path`
- `zesk\Hookable` now requires `$application` as first parameter to constructor
- `zesk\ORM::factory` deprecated
- `zesk\World_Bootstrap_Currency` now outputs missing currencies correctly
- adding `$application` to `Net_Client`
- quieter `zesk\Locale` shutdown
- reduce `_dump` verbosity
- `zesk\Module\SMS` work

## [v0.12.1][]

- Fixing `MySQL\Database_Parser` of `COLLATE` in tables to support `_` in names
- `zesk\Database::create_database` now contains `$hosts` and uses less assumptions
- `zesk\World_Bootstrap_Country` now quieter unless `::debug` option is set

## [v0.12.0][]

- Adding back in `zesk\Application::application_class`
- Changes to allow subclasses to extend indexes. Probably should use `zesk\Schema` or something instead
- Merge branch 'master' of https://github.com/zesk/zesk into rm-global-zesk
- Providing access to `zesk_weight()`
- Removal of `zesk()` calls
- Reset all whitespace and formatting
- removing `zesk()` references
- `zesk\Application::schema_synchronize()` adding support for `follow` options which synchronizes all objects and dependent objects.
- `zesk\Application`: Removed `die()` and using `$logger->emergency()` instead
- `zesk\Database::column_differences` allow `zesk\Database` classes to affect how columns are compared.
- `zesk\Request::file_migrate` is now `zesk\Request\File::instance($upload_array)->process(...)`
- `zesk\World_Bootstrap_Foo` fixes for factory and `bootstrap` to remove globals
- `zesk_sort_weight_array` renamed from `zesk\Kernel::sort_weight_array`
- removed `zesk\\Kernel::sort_weight_array` and moved to `functions.php`


## [v0.11.1][]

- `zesk\Server`: fixing UTC support in database.

## [v0.11.0][]

- Adding `\is_windows()` instead of `zesk()->is_windows`
- Fixing parsing for `zesk\System::volume_info()` and `zesk\Text::parse_columns()`
- New binary `bin/php-find.sh`
- Properly support NULL object passed to `zesk\Router::get_route`
- Quieter testing
- Support of version sniffing to determine `MySQL` compatibility of `timestamp` columns
- Fixing tests for MySQL 5.7
- Test fixes
- Use `zesk\Session_Mock` for tests, to avoid conflict with name `zesk\Session_Test` which could also be a `zesk\Test_Unit`
- `Mail` module refactored to use new case-sensitive autoload
- `zesk test`: Cleaned up `--database-output` option and added support for `--format`
- `zesk\Content_File`: lowercase column names, breaks old implementations
- `zesk\Controller_Template_Login`: Action is on a `zesk\User` object
- `zesk\Functions_Test`: Fixed so that `$nsamples` can be reconfigured
- `zesk\Module_World`: Adding external `currency.json` in attempt to continually modernize our country list.
- `zesk\Module_World`: Removed some currency values which are no longer in use.
- `zesk\ORMs::singleton` now takes an object which, if matches, does not return an error. `zesk\Server` add default in for `ipv4_external`
- `zesk\System::volume_info`: `df` parsing breaks with spaces in volume paths, so we added `zesk\Text::parse_columns` to properly parse the output of `df` (and other column-based outputs from the shell.
- `zesk\\PHP::render`: Fixed issue with $ in strings (now escaped using double-quoted strings)
- `charset` support in `Mail_Message` import
- fixing Session issues in MySQL 5.7
- fixing database test for 5.7 behavior changes
- fixing error mariah issue
- fixing header mapping for Ubuntu 16
- fixing iteration test success and exit code
- fixing load order issue with Modules - depends will actually load dependent modules first
- fixing mail message tests
- fixing test as skipped
- fixing test for MySQL 5.7
- new travis instructions
- support for `ALLOW_INVALID_DATES=false` in MySQL 5.7+
- tests now fail if output `PHP-ERROR` string

## v0.10.13

- Fixed `zesk world-bootstrap --all` so it works properly without error
- Fixing final tests - everything should pass now on development systems

## v0.10.12

- Fixed a variety of tests so they pass
- Fixing `zesk\Contact_Address_Parser` processing for `zesk\Contact_Address::store()`
- Renamed tests to be in the `_Test.php` format for eventual migration to `PHPUnit`
- `Country::find_country` now takes `$application` as first parameter for context
- `Model::factory`, `ORM::factory`, and `Widget::factory` now support `$application->factory()` instead
- `new Net_HTTP_CLient()` and other calls now take `$application` as first parameter
- `zesk\Application::factory` is a shortcut to `$application->objects->factory()`
- `zesk\Database`: Removed references to global `$zesk`
- `zesk\Mail`, `zesk\Options::inherit_global_options`, and `new Net_Foo` calls all now take `$application` as the first parameter.
- `zesk\Module_World` command `world-bootstrap` now properly creates bootstrap objects
- `zesk\Server` remove references to `$zesk`
- `zesk\Timestamp::parse` fix non-string values passed to `preg_match`
- `zesk\\Options::inherit_global_options` now takes `$application` as first parameter.


## v0.10.12

- Fixing `zesk\Contact_Address_Parser` processing for `zesk\Contact_Address::store()`
- `Country::find_country` now takes `$application` as first parameter for context
- `Model::factory`, `ORM::factory`, and `Widget::factory` now support `$application->factory()` instead
- `zesk\Application::factory` is a shortcut to `$application->objects->factory()`
- `zesk\Database`: Removed references to global `$zesk`
- `zesk\Module_World` command `world-bootstrap` now properly creates bootstrap objects
- `zesk\\Options::inherit_global_options` now takes `$application` as first parameter.

## v0.10.11

- `zesk\Configuration_Loader::load_one` does not emit error with file name which does not exist is passed in.
- Adding `Exception_Parameter` in `zesk\ORMs::resolve`
- `zesk\Command`: Outputting head of backtrace in command exception
- Release v0.10.10
- Text::format_pairs uses JSON instead of PHP::dump
- `zesk\Class_ORM`: removed `$zesk` references
- `zesk\Database_Query`: Now supports application object directly.
- `zesk\Locale`: Removed instance of `global $zesk`
- `zesk\Lock`: Fixed exception typo
- `zesk\Module_Logger_File` removed some deprecated code
- `zesk\Module_ReactJS` added some debugging when something is not found
- `zesk\ORM`: Removed unused `use`
- `zesk\Preference_Test` killed some gremlins
- `zesk\Process_Test_Tools`: Fixing uninitialized variable
- `zesk\str_Test`: Removing legacy code
- `zesk\Application::all_classses` returns case-corrected class names
- `zesk\Command_Class_Check` and `zesk\Command_Class_Properties` now support all classes properly.
- `zesk\Control_Forgot` fixed referenced class to have `zesk\` namespace
- `zesk\Deploy`: 	Fixing logger invocation issue when deployment is already running
- `zesk\Text::head` and `zesk\Text::tail` methods similar to shell tools `head` and `tail` for strings with newlines

## v0.10.10

- `MySQL\Database`: `@@storage_engine` is deprecated https://github.com/pimcore/pimcore/issues/490
- Conditional debugging
- `zesk\Database_Parser`: Fixing `split_sql_commands` to actually work
- `Deploy` constructor takes `$application`
- `zesk\Command_Update`: Pass through `--quiet` flag to `composer.phar` as `-q`
- `zesk\Module_ReactJS`: Better warning when `.env` file is missing
- `zesk\Settings`: Conditional debug logging
- `zesk\Template::debug_theme_path` setting make work
- fixing `database_prefix` test is string
- fixing `estimate_rows` issue
- fixing mail parser errors/warnings
- fixing template_test clear_cache is not used
- loader debugging
- `tr` checks `can_iterate` for first param
- updating test for `@depends`, updating tests

## v0.10.9

- fixing `View_Errors::html` call
- fixing `cron` errors
- pass non-array back as-is in `Route::_map_variables`

## v0.10.8

- fixing `Control_Checklist` widgets creation call


## v0.10.7

- Deploy fixes, removal of app()

## v0.10.6

- Lock API changes
- Removal of globals and fixing of `Server::singleton()` calls in Zesk


## v0.10.5

- Lock API changes, now `Lock::instance($application, $name)` then arrow functions to operate.

## v0.10.4

- Adding `zesk\Application::model_factory` method
- Fixing `Model::__construct` takes `$application` as first parameter issues
- Moving `Browser` module to `zesk` namespace
- Removed some references from `global $zesk` and use local context instead
- Stop using deprecated `Class_ORM::cache`
- `Domain::domain_factory` now takes `$application` as first parameter.
- `Response::cdn_javascript` and `Response::cdn_css` are deprecated, removing from all Zesk core
- `zesk\Browser` application global passing
- `zesk\Logger` used to die when log handler threw an exception; now silently continues.
- `zesk\Model` remove redundant initialization code in factory method

## v0.10.3

- Updates to tests, renamed exceptions in test system
- patch `zesk\Application::application_class` to work correctly


## v0.10.2

- `zesk\Application::application_class()` called old-style `zesk()->application_class`, now fixed


## v0.10.1

- `zesk` command line now MUNGES input date to support easier invocation using namespaces. Specifically, the token `___` (triple underscore) is converted in **ALL** command-line arguments to backslash `\`. This mimics similar functionality in the `Configuration_Loader_CONF` class. If your scripts depend on variables with triple-underscores, you may need to revise them.
- SECURITY: `Preference::user_get` and `Preference::user_set` no longer check if user is authenticated before returning values
- 0.10 version deprecated and modified calls
- Adding `dump_config` to debug logging configuration
- `Database_Index`: Allow changing index type
- Pass `$application` around instead of using globals, require context for all __construct calls, moved class cache to `Application`
- Removing deprecated invocation of `Module_Critical::alert`, instead use `critical` logger call. Also `pave_set` is deprecated, using `path_set` instead.
- Testing system: Better error messages
- Fixed issue with `Server::singleton` returning void
- Fixing tests
- Selenium tests: If no host, do not run selenium tests
- Interface_Settings: Added comments
- Major test suite updates, support for fewer references to `app()` global
- Perhaps add a deprecated common.sh toolset?
- Release v0.10.0
- Schema: Fixing issue with modifying a primary column and making sure the index isn't added redundantly
- Support PHPUnit parameters as well
- Support subclasses which do not run `zesk\Application::configured` hooks
- Test renaming and updates in prep for move to PHPUnit
- Test reorganization and cleanup.
- Updated docs for help and .php extension
- Updated tests on the path to all passing
- Updating deprecated tools
- Updating for tests
- Widget `hook_control_options_changed`, ensure `$this->control_options` is array
- XMLRPC namespace and fixing classes and tests
- `Class_User` now adds member columns if they are not declared by subclasses in `$column_types`
- `Command_Loader`: avoid `global`, `Configuration_Loader` API change
- `Configuration::pave_set` and `Configuration::pave` are deprecated, remove internal usage
- `Configuration::pave` is deprecated, changing to `::path`
- `Configuration_Loader` no longer has name as part of constructor
- `Configuration_Loader`: No longer takes `$name` as first parameter in constructor
- `Content_File`: Global reduction, pass `$application` around to static calls
- `Controller::factory` is deprecated
- `Controls_Test` rename and fixes
- `File::put` now throws exception if contents is non-scalar
- `Interface_Session` fixing `__construct` parameter order (application first)
- `Language::clean_table` now requires `$application` as first parameter
- `Module_Permission`: Avoid global usage, API change in `Configuration_Loader`
- `Preference`: Global reduction, pass `$application` around to static calls
- `Session_Database` removed deprecated global `$nosession`
- `Session` adjusted factory parameters for new parameter order 
- `Test_Unit`: `Configuration_Loader` API change
- `Widget::factory` fixes
- `World_Test` fixing namespaces
- `xmlrpc\\Server` supports option `allow_query_string_data` to allow passing of data (via ?data=) for debugging only
- `zesk test` now tests which end with `.php`
- `zesk\Control_Optionss`: Ensure `$this->control_options` is always an array after `::initialize` exist
- `zesk\Database_Column`: Ensure `sql_type` is normalized to lowercase
- `zesk\Database_Table`: Adding `table_attributes_is_similar` and fixing bug with table type changing to database default
- fixing Configuration_Loader_Test issues
- fixing RRule tests and fixing variety of bugs. RRule passes\!
- fixing tests
- getting to testing pass
- moving to own repo
- new test structure and naming
- new version
- no reformatting
- refactored XMLRPC tests
- updating tests and fixing them
- updating tests for travis-ci
- updating tests, support \ better in command line
- version notes


## v0.10.0

- Removed usage of global state
 - `Application::instance` removal across the system, reduced usage of `zesk()`
 - Pass `$application` around instead of using globals, require context for all __construct calls, moved class cache to `Application`
 - Remove references to `app()` and `zesk()` where possible
 - removing global references and restructuring function calls to remove global access
- Test suites
 - Adding `zesk\Logger::dump_config` to debug logging configuration
 - Call to allow changing index type of `zesk\Database_Index`
 - Better error messages for `zesk\Database_Execption_Connect`
 - Major test suite updates, support for fewer references to `app()` global
- Schema
 - Schema: Fixing issue with modifying a primary column and making sure the index isn't added redundantly
 - `zesk\Database_Column`: Ensure `sql_type` is normalized to lowercase
 - `zesk\Database_Table`: Adding `table_attributes_is_similar` and fixing bug with table type changing to database default
- Miscellaneous
 - Updated `zesk help` docs for help and `.php` extension
 - `XMLRPC` namespace and fixing classes and tests
 - `zesk\Command_Base`: Support subclasses which do not run `zesk\Application::configured` hooks
 - `zesk\User`: moving deprecated functions to bottom, moving global state out of `User`
- Modules
 - Moved `ipban` to its own repository
 - Moved `zest` to its own repository

## v0.9.30

Removed as many references as possible to `app()` and `zesk\Application::instance()` in the code. Removed the `CDN` class.

- `zesk\Application::instance()` is now deprecated, **try to avoid usage**
- `Content_Image::register_from_file` now takes the application as the first parameter
- `Content_Data::from_path` now takes the application as the first parameter
- `Database_Query::__construct` now requires the `zesk\Database` to be non-null
- `Controller_Share::realpath` now takes the application as the first parameter

## v0.9.29

- Allow reinitialization of app, fixing up reset. Note that `zesk()->reset()` is not production-ready yet, and may possibly be removed in a future version. Likely add an "Application"-level reset instead and will migrate any values from `zesk()` global to Application level if necessary.
- Deprecated module variable `$classes`
- Fixing class names for world bootstrap
- `Options::inherit_global_options` now can inherit from passed in object (uses `get_class`)
- Adding back in `update.conf` to `zesk update` command

## v0.9.28

- Adding maintenance tag as default version `1.2.{maintenance}.0`
- Controls related to `Database_Query_Select` Avoids `query_column` warnings that field didn't modify where clause
- Fix `Contact_Tag` and `Contact` linkage by adding intermediate table
- Widgets: Fix unlikely code structure issue with `_exec_render` to avoid uninitialized variable and double unwrap
- Updated docs in `Control_Select`
- Support for `Control_Select::is_single()`
- `zesk\Directory::list_recursive`: if `opendir` fails on a directory, return an array instead of false
- `Controller_Content_Cache` fixing issue with `Content_Image` data being `null`
- Updating the `version` command to support custom version layouts properly

## v0.9.27

- Version parse fixes to support A.B.C.D versions (fixing version parsing)
- `Request::ip()` returns `array()` incorrectly

## v0.9.26

- Module `openlayers` URL updates

## v0.9.25

- Fixing issue with `split_sql_commands` which did not work for large strings due to limits to PREG backtracking in PHP7. Modified algorithm to use alternate parsing mechanism.
- Release v0.9.24
- `zesk\Request::default_request` may read `zesk\Request::data()` and initialize it, so needs to have object (mostly) initialized before calling.

## v0.9.24

- allow setting console state in `zesk()` superglobal via `zesk()->console(true)`
- fixing `zesk\Command` `prefix` option feature

## v0.9.23

- adding prefix/suffix to `zesk\Command` as options for stdout decoration

## v0.9.22

- Zesk `release-zesk.sh` script updates
- catch errors updating Server state
- fixing minor issues with i18n and support alternate file layouts using configuration pattern
- fixing release notes

## Version 0.9.21

- adding release-zesk.sh for better release automation

## Version 0.9.20

- Allow zesk.sh to interpret --cd in command line before selecting zesk-command.php to run
- Clarification of deprecating `firstarg` using `?:` (ternary operator) not `??` (null coalesce)
- Fixing module-new so it does not create a .php root file which is deprecated
- `zesk-command.php` Remove deprecated config calls, modify semantics "factory" not "instance"
- Test work module factoring, Travis CI integration
- added `Interface_Module_Head` to `Module_Bootstrap`
- `test` module loaded for testing only
- updating `version` doc

## Version 0.9.19

- Fixed `jqplot` module download link

## Version 0.9.18

- Adding `$app->set_application_root($path)`

## Version 0.9.17

- In `selenium` module, fixed `zesk\Selenium_Browsers::browsers_clean_and_fix` clean to return valid named browsers only

## Version 0.9.16

- Removed `User` deprecation to `zesk\User` - may not necessarily be the case
- Fixed an issue with `zesk\Options::inherit_global_options` which incorrectly inherited global options containing a dash (`-`) and did not normalize them using `zesk\Options::_option_key($key)` first.
- Fixing an issue with `Database` auto table names options not being passed through when 1st parameter is an array

## Version 0.9.15

- Fixed references to `Application` in `modules`

## Version 0.9.14

- Fixed reference to `Application` in `iLess` module

## Version 0.9.13

- Fixed PHP7 constant dependencies in `classes/Temporal.php` (2nd attempt)

## Version 0.9.12

- Fixed PHP7 constant dependencies in `classes/Temporal.php`

## Version 0.9.11

- `zesk\Time::add_unit($n_units = 1, $units = self::UNIT_SECOND)` parameter order has been swapped to be more natural. The new syntax is `$time->add_unit(3, "minute")`. The old syntax (with `$units` first) will be supported for 6 months.
- `zesk\Date::add_unit($n_units = 1, $units = self::UNIT_DAY)` parameter order has been swapped to be more natural. The new syntax is `$date->add_unit(3, "day")`. The old syntax (with `$units` first) will be supported for 6 months.
- Fixed all references to `->add_unit()` and using UNIT constants
- Fixed issue with contact address editor (theme path missing `zesk` prefix)

## Version 0.9.9

- Fixed `zesk.sh` to better support `--cd` arguments and not rely on `cwd`
- Removed `_zesk_loader_.inc` in root, use `composer` instead for loading
- Deprecated `ZESK_APPLICATION_ROOT` constant to support better composer loading. Use `zesk()->paths->set_application($path)` instead.
 - Global `application_root` at root `zesk\Configuration` is not longer set
- Added `zesk\PHP::parse_namespace_class()` utility function
- Adding support for `psr/cache` compatibility and Adapters for zesk classes, so `zesk\Cache` will be deprecated, use `zesk()->cache` instead. It will be guaranteed to be set. Start using this instead of `Cache::` calls. Adapters in zesk are called `zesk\Adapter_CacheItem` and `zesk\Adapter_CachePool`.
- `zesk\DateInterval` was added for `\DateInterval` tools for conversion to/from seconds
- `zesk\Timestamp::UNIT_FOO` have been defined for all valid time units (second, minute, etc.)
- `zesk\Timestamp::add_unit($n_units = 1, $units = self::UNIT_SECOND)` parameter order has been swapped to be more natural. The new syntax is `$ts->add_unit(3, "minute")`. The old syntax (with `$units` first) will be supported for 6 months.
- Removed `ZESK_ROOT/classes-stubs` from default autoloader.

## Version 0.9.8

- Fixing lots of reference errors in Git version of Zesk and Subversion version of Zesk. Mostly removing empty directories from Subversion, and fixing incorrectly logged paths for Git (which is quirky with case-sensitivity on Mac OS X). Full list of fixes is below:

	Only in zesk-git/classes/Control/Select: object
	Only in zesk-svn/classes/Control/Select/Object: Dynamic.php
	Only in zesk-svn/classes/Control/Select/Object: Hierarchy.php
	Only in zesk-svn/classes/Control: Text
	Only in zesk-git/classes/Control: Url.php
	Only in zesk-svn/classes/Control: URL.php
	Only in zesk-svn/classes/Response: Application
	Only in zesk-svn/classes: User
	Only in zesk-git/: composer.lock
	Only in zesk-svn/: .cvsignore
	Only in zesk-git/: .git
	Only in zesk-git/: .gitignore
	Only in zesk-svn/modules/bootstrap/classes/Control: Dropdown.php
	Only in zesk-git/modules/bootstrap/classes/Control: DropDown.php
	Only in zesk-svn/modules/bootstrap/classes/Control/Text: Dropdown.php
	Only in zesk-git/modules/bootstrap/classes/Control/Text: DropDown.php
	Only in zesk-svn/modules/commerce/classes: bootstrap
	Only in zesk-git/modules/content/classes: class
	Only in zesk-git/modules/content/classes/Class: content
	Only in zesk-svn/modules/content/classes/Class/Content: Article.sql
	Only in zesk-svn/modules/content/classes/Class/Content: Group.sql
	Only in zesk-svn/modules/content/classes/Class/Content: Menu.sql
	Only in zesk-svn/modules/content/classes/Class/Content: Video.sql
	Only in zesk-svn/modules/content/classes/Content: Test
	Only in zesk-svn/modules/content/theme/content/group: media
	Only in zesk-svn/modules/content/theme/content/group: video
	Only in zesk-svn/modules/daemontools: etc
	Only in zesk-git/modules/dblog/classes: module
	Only in zesk-svn/modules/dblog/classes: Module
	Only in zesk-svn/modules/developer: share
	Only in zesk-svn/modules/dkim/classes: test
	Only in zesk-svn/modules/dnsmadeeasy/classes/Server/Feature: dns
	Only in zesk-svn/modules/excelx/classes/module: simple
	Only in zesk-svn/modules/excelx: excelx
	Only in zesk-git/modules/health/classes: class
	Only in zesk-svn/modules/health/classes/Class/Health: Event.sql
	Only in zesk-svn/modules/health/classes/Class/Health: Events.sql
	Only in zesk-svn/modules/icalendar/test/rrule: rule
	Only in zesk-svn/modules/import_log/classes/import/log: test
	Only in zesk-svn/modules/import_log/classes/import: test
	Only in zesk-svn/modules/jquery-datetimepicker/classes: control
	Only in zesk-svn/modules/jquery-datetimepicker/classes: view
	Only in zesk-svn/modules/jquerymobile: theme
	Only in zesk-svn/modules/mysql/classes: MySQL
	Only in zesk-svn/modules/pdo/classes/pdo: database
	Only in zesk-git/modules/permission/classes/zesk: class
	Only in zesk-svn/modules/permission/classes/zesk/Class: Role.sql
	Only in zesk-git/modules/permission/classes/zesk/Class: user
	Only in zesk-svn/modules/permission/classes/zesk/Class: User
	Only in zesk-svn/modules/phpunit/classes: Module
	Only in zesk-svn/modules/polyglot: .codekit-cache
	Only in zesk-git/modules/preference/classes: preference
	Only in zesk-svn/modules/preference/classes/Preference: Test
	Only in zesk-git/modules/server/classes: application
	Only in zesk-svn/modules/server/classes/Application: Server.classes
	Only in zesk-svn/modules/server/classes/Application: Server.router
	Only in zesk-svn/modules/server/classes/Class: Zesk
	Only in zesk-git/modules/server/classes: server
	Only in zesk-svn/modules/server/classes/Server/Feature: DaemonTools
	Only in zesk-svn/modules/server/command: daemon
	Only in zesk-svn/modules/server: sbin
	Only in zesk-svn/modules/test: bin
	Only in zesk-svn/modules/zest/classes: application
	Only in zesk-svn/share/widgets: iphoneframe
	Only in zesk-svn/: .svn
	Only in zesk-svn/test/classes: control
	Only in zesk-svn/test/classes: controller
	Only in zesk-svn/test/classes/database/query: select
	Only in zesk-svn/test/classes/view: date
	Only in zesk-svn/test/classes: xml
	Only in zesk-svn/theme/zesk/control: file
	Only in zesk-svn/theme/zesk/control: text

## Version 0.9.7

- Removing reference to `$this` in static function in `modules/content/classes/Content/Data.php`

## Version 0.9.6

- Fixing path of `zesk\\Control_Select_ORM_Available` to be case-sensitive

## v0.9.5

- Skipped a few versions due to `zesk version` testing and other work
- Fixed SQL files in git repo for `zesk\City`, `zesk\Country`, `zesk\County`, `zesk\Currency`, `zesk\Language`, `zesk\Province`, `zesk\Content_Data`, `zesk\Content_File`, `zesk\Content_Image`, `zesk\Lock`, `zesk\Permission`, `zesk\Preference`, `zesk\Preference_Type`, `zesk\Server_Data`
- Worked on `reposotory`, `subversion`, and `github` modules (still work in progress)

## v0.9.2

- Minor fixes. Enhanced version tags to allow mix of numbers and characters only in `zesk\Command_Version`. Updated docs.
- Testing automatic version pushing and publishing, so apologize if you receive a lot of releases in a short period.

## v0.9.1

- Removed `\zesk\ORM::__destruct` for performance improvements (see [this](https://stackoverflow.com/questions/2251113/should-i-use-unset-in-php-destruct))
- `zesk\Class_ORM->variables()` now returns a key 'primary_keys' with an array list of primary key names (member names)
- `zesk\ORM->variables()` now returns a key '_class' with the PHP class name, and '_parent_class' with the PHP parent class name.
- Minor changes to `\zesk\Route_Controller` to avoid usage of the `$zesk` global and use `$application` instead.
- Added `zesk version` to assist in managing version numbers for builds, etc.

## v0.9.0

We've made the leap to PSR-4 loading for Zesk core and all classes, and split the codebase into `namespace zesk` (now in `./classes/`) and non-namespace components (now in `./classes-stubs/`). 

You can add the module (`composer require zesk/zesk-0.8-compat`) to get old class definitions, which are all subclasses to the `zesk` namespace classes.

Remaining module code will be moved into modules or into `namespace zesk` as needed to complete the complete migration to the namespace-based classes.

The change to the loading and PSR-4 should not affect any zesk core functionality, except if you included files directly (which is not recommended in general.)

As well, the following changes occurred:

- Removed deprecated `Module::` static calls (use `app()->modules->` instead)
- Removed deprecated `log::` static calls (use `zesk()->logger->` instead)
- `View::option_hidden_input` has been removed
- `Process_Group` has been removed (use `zesk\Command_Daemon` functionality)
- `Router::add_default_route` has been removed.
- `Router::controller_prefixes()` and related members and calls have been removed
- `zesk update` now by default will **not** update your composer lock file, except if a requirement is added which is not already included in it. To update the composer lock, use `zesk update --composer-update` to always update dependencies in composer, or do `php composer.phar update` from the command line after `zesk update`.
- Application files should end with `.php` (e.g. `invoicing.application.php`) and a warning is displayed if not
- `zesk.inc` is now called `autoload.php`

The following classes are now in the `zesk\` namespace:

- `Cache_APC`
- `Cache_File`
- `Lock`
- `Module_JSLib`
- `Module_Critical`
- `Module_Nominatim`
- `Base26`
- `Server_Data`
- `Model_List`
- `Model_Login`
- `Model_Settings`
- `Model_URL`
- `ulong`
- `View` and all subclasses
- `Widget` and all subclasses
- `Control` and all subclasses
- `Controller` and all subclasses
- `Net_*` classes
- `Deploy`
- `Debug`
- `world` Module: `Currency`

The `Controller` routes no longer support `controller prefix` and `controller prefixes` to search for a controller. Instead the controller class name is used explictly for the `controller` option. The search functionality for controllers was not widely enough used and caused performance issues with routes. This functionality is now deprecated and will be removed in the 0.10.0 release. You should update your router files and `hook_routes` calls appropriately for the new syntax. 

Autoload paths support PSR-4 by default, so lowercase is not ON anymore by default. You will need to update your autoload paths for your application to add ["lower" => true] to added autoload paths.

Note that this release has been tested in PHP 7.0 and 7.1 environments with no issues, so you are highly encouraged to upgrade to the version 7 of PHP as soon as possible due to the enormous potential performance increases available.

## v0.8.2

More `zesk\` namespace changes, cleanup of `instance` static calls.

- Moved `Command` to `zesk\Command`, `Command_Base` to `zesk\Command_Base`
- Modified how zesk commands are loaded, you can now specify a prefix for classes found within each path specified. Default prefix is now `zesk\Command_`
- Moved 'doccomment' class to 'zesk\DocComment'
- Removed static methods from `Module_Permission`
- `ORM::permissions` call syntax has changed, return values should return `class::action` as keys, instead of just `action`. This is to prevent duplicate actions registered for child and parents (e.g. `User` and `zesk\User`). The name of the called method is only used as a hint in generating permission names now when the class is not supplied.
- Deprecated `User::instance` for `User::current` and related (`::instance_id`)
- Deprecated `Session::instance` for `Session::singleton`
- Deprecated `Request::instance` for `app()->request()`
- Deprecated `Response::instance` for `app()->response()`
- Deprecated the use of the static "instance" call for singletons created via `zesk()->objects->singleton()` (still works, but calls deprecated callback)
- Moved `Session_Database` to `zesk\Session_Database`
- Expanded the use of cookies via the Request and Response objects, removed this from `Session_Database` implementation
- Removed all usage of `User::instance` and `Session::instance` from zesk
- Moved `Session_Database` to `zesk\Sesssion_Database`
- Refactored `zesk\Session_Database` and migrated globals into `zesk\Application`
- Added `zesk\Application->user()` and `zesk\Application->session()` to support new access model via objects
- Deprecated `zesk\HTML::input_attributes` is now `zesk\HTML::tag_attributes`
- Removed deprecated functions/constants in `zesk\ORM`:
 - `zesk\ORM::REGISTER_Exists`
 - `zesk\ORM::REGISTER_Insert`
 - `zesk\ORM::REGISTER_Failed`
 - `zesk\ORM::REGISTER_Failed`
 - `zesk\ORM::memberIsEmpty()`
 - `zesk\ORM::cleanCodeName()`
 - `zesk\ORM::objectCache()`
 - `zesk\ORM::register_result()`
 - `zesk\ORM::memberBoolean()`
 - `zesk\ORM::memberInteger()`
 - `zesk\ORM::memberSet()`
 - `zesk\ORM::className()`
 - `zesk\ORM::db()`
 - `zesk\ORM::dbname()`
 - `zesk\ORM::objectMap()`
 - `zesk\ORM::fieldList()`
 - `zesk\ORM::fields()`
 - `zesk\ORM::hasMember()`
 - `zesk\ORM::hasMember()`
 - `zesk\ORM::hasMember()`
 - `zesk\ORM::hasMember()`
 - `zesk\ORM::hasMember()`
 - `zesk\ORM::hasMember()`
- Deprecated the following `zesk\ORM` calls (use `app()->object()` and `app()->class_object()` to access)
 - `zesk\ORM::class_table_name()`
 - `zesk\ORM::class_id_column()`
 - `zesk\ORM::class_primary_keys()`
 - `zesk\ORM::class_table_columns()`
 - `zesk\ORM::class_database()`
- `Control_Text_Dropdown::menu_default` removed (use `::dropdown_default` instead)
- `Control_Text_Dropdown::menu_dropdown` removed (use `::dropdown_menu` instead)
- Removed class `Control_Content_Link_List` from `content` module (deprecated)
- Removed calls in `csv` module: 
 - `CSV::_setFile` (protected)
 - `CSV::setHeaders`
 - `CSV::rowIndex`
 - `CSV::columnIsEmpty`
 - `CSV::columnGet`
- Removed calls in `preference` module:
 - `Preference_Type::registerName()` (use `Preference_Type::register_name()` instead)
- Removed `zesk::theme_path`
- Obsoleted classes:
 - `gzip`, `sql`, `widgets`
- module `server` class `cluster` obsoleted
- function `zesk\str::matches` removed
- function `zesk\str::cexplode` removed
- function `zesk\str::explode_chars` removed
- `array_stddev` removed (use `zesk\Number::stddev`)
- `array_mean` removed (use `zesk\Number::mean`)
- `Net_SMTP_Client::go` removed (use `Net_SMTP_Client::send`)
- `zesk\Template::run` removed
- `zesk\Template::instance` removed
- `zesk\Template::output` removed
- Removed class `xml`, `XML_Writer`, `XML_Writer_Interface`, `Object_XML` (deprecated), `XML_Reader`
- Migrated `xml/rpc` classes to own module (xmlrpc), including tests
- `XML_RPC_Foo` is now `xmlrpc\Foo`

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

[Unreleased]: https://github.com/zesk/zesk/compare/Unreleased...HEAD
[v0.14.4]: https://github.com/zesk/zesk/compare/v0.14.3...Unreleased
[v0.14.3]: https://github.com/zesk/zesk/compare/v0.14.2...v0.14.3
[v0.14.2]: https://github.com/zesk/zesk/compare/v0.14.1...v0.14.2
[v0.14.1]: https://github.com/zesk/zesk/compare/v0.14.0...v0.14.1
[v0.14.0]: https://github.com/zesk/zesk/compare/v0.13.2...v0.14.0
[v0.13.2]: https://github.com/zesk/zesk/compare/v0.13.1...v0.13.2
[v0.13.1]: https://github.com/zesk/zesk/compare/v0.13.0...v0.13.1
[v0.13.0]: https://github.com/zesk/zesk/compare/v0.12.15...v0.13.0
[v0.12.15]: https://github.com/zesk/zesk/compare/v0.12.14...v0.12.15
[v0.12.14]: https://github.com/zesk/zesk/compare/v0.12.13...v0.12.14
[v0.12.13]: https://github.com/zesk/zesk/compare/v0.12.12...v0.12.13
[v0.12.12]: https://github.com/zesk/zesk/compare/v0.12.11...v0.12.12
[v0.12.11]: https://github.com/zesk/zesk/compare/v0.12.10...v0.12.11
[v0.12.10]: https://github.com/zesk/zesk/compare/v0.12.9...v0.12.10
[v0.12.9]: https://github.com/zesk/zesk/compare/v0.12.8...v0.12.9
[v0.12.8]: https://github.com/zesk/zesk/compare/v0.12.7...v0.12.8
[v0.12.7]: https://github.com/zesk/zesk/compare/v0.12.6...v0.12.7
[v0.12.6]: https://github.com/zesk/zesk/compare/v0.12.5...v0.12.6
[v0.12.5]: https://github.com/zesk/zesk/compare/v0.12.4...v0.12.5
[v0.12.4]: https://github.com/zesk/zesk/compare/v0.12.3...v0.12.4
[v0.12.3]: https://github.com/zesk/zesk/compare/v0.12.2...v0.12.3
[v0.12.2]: https://github.com/zesk/zesk/compare/v0.12.1...v0.12.2
[v0.12.1]: https://github.com/zesk/zesk/compare/v0.12.0...v0.12.1
[v0.12.0]: https://github.com/zesk/zesk/compare/v0.11.1...v0.12.0
[v0.11.1]: https://github.com/zesk/zesk/compare/v0.11.1...v0.11.0
[v0.11.0]: https://github.com/zesk/zesk/compare/v0.10.13...v0.11.0
