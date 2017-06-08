# Versions

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


