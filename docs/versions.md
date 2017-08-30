## Release v0.10.1

- `zesk` command line now MUNGES input date to support easier invocation using namespaces. Specifically, the token `___` (triple underscore) is converted in **ALL** command-line arguments to backslash `\`. This mimics similar functionality in the `Configuration_Loader_CONF` class. If your scripts depend on variables with triple-underscores, you may need to revise them.
- SECURITY: `Preference::user_get` and `Preference::user_set` no longer check if user is authenticated before returning values

## Release v0.10.0

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

## Release v0.9.30

Removed as many references as possible to `app()` and `zesk\Application::instance()` in the code. Removed the `CDN` class.

- `zesk\Application::instance()` is now deprecated, **try to avoid usage**
- `Content_Image::register_from_file` now takes the application as the first parameter
- `Content_Data::from_path` now takes the application as the first parameter
- `Database_Query::__construct` now requires the `zesk\Database` to be non-null
- `Controller_Share::realpath` now takes the application as the first parameter

## Release v0.9.29

- Allow reinitialization of app, fixing up reset. Note that `zesk()->reset()` is not production-ready yet, and may possibly be removed in a future version. Likely add an "Application"-level reset instead and will migrate any values from `zesk()` global to Application level if necessary.
- Deprecated module variable `$classes`
- Fixing class names for world bootstrap
- `Options::inherit_global_options` now can inherit from passed in object (uses `get_class`)
- Adding back in `update.conf` to `zesk update` command

## Release v0.9.28

- Adding maintenance tag as default version `1.2.{maintenance}.0`
- Controls related to `Database_Query_Select` Avoids `query_column` warnings that field didn't modify where clause
- Fix `Contact_Tag` and `Contact` linkage by adding intermediate table
- Widgets: Fix unlikely code structure issue with `_exec_render` to avoid uninitialized variable and double unwrap
- Updated docs in `Control_Select`
- Support for `Control_Select::is_single()`
- `zesk\Directory::list_recursive`: if `opendir` fails on a directory, return an array instead of false
- `Controller_Content_Cache` fixing issue with `Content_Image` data being `null`
- Updating the `version` command to support custom version layouts properly

## Release v0.9.27

- Version parse fixes to support A.B.C.D versions (fixing version parsing)
- `Request::ip()` returns `array()` incorrectly

## Release v0.9.26

- Module `openlayers` URL updates

## Release v0.9.25

- Fixing issue with `split_sql_commands` which did not work for large strings due to limits to PREG backtracking in PHP7. Modified algorithm to use alternate parsing mechanism.
- Release v0.9.24
- `zesk\Request::default_request` may read `zesk\Request::data()` and initialize it, so needs to have object (mostly) initialized before calling.

## Release v0.9.24

- allow setting console state in `zesk()` superglobal via `zesk()->console(true)`
- fixing `zesk\Command` `prefix` option feature

## Release v0.9.23

- adding prefix/suffix to `zesk\Command` as options for stdout decoration

## Release v0.9.22

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

- Fixing path of `zesk\\Control_Select_Object_Available` to be case-sensitive

## v0.9.5

- Skipped a few versions due to `zesk version` testing and other work
- Fixed SQL files in git repo for `zesk\City`, `zesk\Country`, `zesk\County`, `zesk\Currency`, `zesk\Language`, `zesk\Province`, `zesk\Content_Data`, `zesk\Content_File`, `zesk\Content_Image`, `zesk\Lock`, `zesk\Permission`, `zesk\Preference`, `zesk\Preference_Type`, `zesk\Server_Data`
- Worked on `reposotory`, `subversion`, and `github` modules (still work in progress)

## v0.9.2

- Minor fixes. Enhanced version tags to allow mix of numbers and characters only in `zesk\Command_Version`. Updated docs.
- Testing automatic version pushing and publishing, so apologize if you receive a lot of releases in a short period.

## v0.9.1

- Removed `\zesk\Object::__destruct` for performance improvements (see [this](https://stackoverflow.com/questions/2251113/should-i-use-unset-in-php-destruct))
- `zesk\Class_Object->variables()` now returns a key 'primary_keys' with an array list of primary key names (member names)
- `zesk\Object->variables()` now returns a key '_class' with the PHP class name, and '_parent_class' with the PHP parent class name.
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
- `Object::permissions` call syntax has changed, return values should return `class::action` as keys, instead of just `action`. This is to prevent duplicate actions registered for child and parents (e.g. `User` and `zesk\User`). The name of the called method is only used as a hint in generating permission names now when the class is not supplied.
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
- Removed deprecated functions/constants in `zesk\Object`:
 - `zesk\Object::REGISTER_Exists`
 - `zesk\Object::REGISTER_Insert`
 - `zesk\Object::REGISTER_Failed`
 - `zesk\Object::REGISTER_Failed`
 - `zesk\Object::memberIsEmpty()`
 - `zesk\Object::cleanCodeName()`
 - `zesk\Object::objectCache()`
 - `zesk\Object::register_result()`
 - `zesk\Object::memberBoolean()`
 - `zesk\Object::memberInteger()`
 - `zesk\Object::memberSet()`
 - `zesk\Object::className()`
 - `zesk\Object::db()`
 - `zesk\Object::dbname()`
 - `zesk\Object::objectMap()`
 - `zesk\Object::fieldList()`
 - `zesk\Object::fields()`
 - `zesk\Object::hasMember()`
 - `zesk\Object::hasMember()`
 - `zesk\Object::hasMember()`
 - `zesk\Object::hasMember()`
 - `zesk\Object::hasMember()`
 - `zesk\Object::hasMember()`
- Deprecated the following `zesk\Object` calls (use `app()->object()` and `app()->class_object()` to access)
 - `zesk\Object::class_table_name()`
 - `zesk\Object::class_id_column()`
 - `zesk\Object::class_primary_keys()`
 - `zesk\Object::class_table_columns()`
 - `zesk\Object::class_database()`
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

## Heading toward a 1.0

Version 1.0 of Zesk will have:

- Elimination of most globals, moving from `zesk::` to `Application::` in places where it makes sense
- Ability to initialize the application context and then serialize the configuration in a way which allows for faster startup
- PSR-4?
- `zesk\` Namespace for most objects in the system
- Full composer support for both Zesk as well as commonly used modules
- Support for Psr/Cache for caching within Zesk
- Support for Monolog within Zesk core
- All modules use **namespaces**
- Merging of `Response` and `Response_Text_HTML` into a single, unified polymorphic `Response`

