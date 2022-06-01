# Change Log
<!-- @no-cannon -->

All notable changes to Zesk will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/) and this project adheres to [Semantic Versioning](http://semver.org/spec/v2.0.0.html).

## Heading toward a 1.0

Version 1.0 of Zesk will have:

- Ability to initialize the application context and then serialize the configuration in a way which allows for faster startup (maybe?)
- PSR-4? - **Yes, for zesk core.**
- Full composer support for both Zesk as well as commonly used modules - **still need module tested**
- Support for `Monolog` within Zesk core - **needs to be tested**
- All modules use **namespaces** - **in progress**
- Website `https://zesk.com` with basic documentation

<!-- HERE -->

## [v0.30.2][]

- **Contact Module**: Fixing Date schema default value
- **MySQL Module**: Adding backwards old-method to invoke MySQL from CLI
- **MySQL Module**: Fixes to types for MySQL 8
- **MySQL Module**: Fixing cli invocation using password on cli
- **MySQL Module**: Fixing internal types to support correct default
- **MySQL Module**: Fixing password quoting in arguments
- **MySQL Module**: MySQL updates for 5.7
- **ORM Module**: Adding --no-hooks to schema update command
- **ORM Module**: Adding Server default ips to localhost
- **ORM Module**: Fixed typo `Databasse_Query_Select`
- **ORM Module**: Server sql updates
- **Zesk Kernel**: Coding style cleanups, Deprecated fixes, Fixed phpstorm flagged issues, Fixing PHP minor issues/warnings
- **Zesk Kernel**: Fixing error `Hook "singleton_namespace_User" cleaned to "singleton_namespace_user" - please fix Deprecated: /opt/app/vendor/zesk/zesk/classes/Hookable.php zesk\Application->deprecated:167`
- **Zesk Kernel**: Refactoring, comments, misspellings, and doccomment updates
- **Zesk Kernel**: Removing references to `backtrace` in code as well as cleaning up various WS
- **Zesk Kernel**: `Hookable` Removing incorrect string type to `call_hook` sheesh
- **Zesk Kernel**: fixing UTF8, fixing invalid UTF8 in response, fixing nativeQuoteText logic, fixing sql for latest MySQL, fixing warnings, new version

## [v0.30.1][]

- **MySQL Module**: Fixed CREATE issue. Timestamp default null issues.
- **Widget Module**: Adding better optgroup support for `value_to_text`
- **World Module**: Updating locale calls
- **Zesk Kernel**: Removing unused private `zesk\Locale` call`. New assert style.

## [v0.30.0][]

- **Bootstrap Datetimepicker**: Fixed range selection issues where multiple selections would cause browsers to fail upon submit
- **MySQL Module**: Support for MySQL 8 (MySQL Enterprise, compatible with MySQL 5)
- **Zesk Kernel**: Code cleanup and linting
- **Zesk Kernel**: Fixed bug in `zesk\ArrayTools::imatch` and added type hint to `zesk\ArrayTools::include_exclude`
- **Zesk Kernel**: Improved code quality with code linter
- **Zesk Kernel**: Removing `zesk\Model_URL` as it is unused
- **zesk\Kernel**: Removed deprecated `zesk\Application::configure_include_path`
- **Bootstrap Datetimepicker**: Modify selected option using jQuery

## [v0.29.0][]

- **Cron Module**: Adding `--last` to options to better debug production issues
- **Cron Module**: Change in semantics for function prefixes: `cron_server` runs per-server, `cron` runs per application and per-server, and `cron_cluster` runs per application on a single server.
- **Cron Module**: Correctly prefix clusters to support multiple applications per-cluster
- **Cron Module**: Fixing issue with cluster name keys
- **Cron Module**: `zesk cron --last` now shows a pretty table
- **MySQL Module**: PHP 5.5+ support for timestamp
- **ORM Module**: Capture database not found exception in Settings load to allow misconfigured app to still run.
- **ORM Module**: Minor fix to documentation
- **ORM Module**: `zesk\Class_ORM::link_many` now throws `zesk\Exception_Key` instead of `zesk\Exception_Semantics` to disambiguate error codes as well as to support catching already-added linkages. Added support to **Permission Module**.
- **Polyglot Module**: `zesk\Controller_Polyglot::action_update` explicit route added; no longer covered by catch-all `controller/action/param` global route.
- **Zesk Kernel**: Adding `zesk\Application->id()` to allow application instance cron tasks
- **Zesk Kernel**: Improved application reconfigure
- **Zesk Kernel**: Improved comments for `zesk\Autoloader`
- **Zesk Kernel**: Modules reload method added
- **Zesk Kernel**: Router fixing issue with intermittent quoting in route compilation
- **Zesk Kernel**: `zesk\Controller_Share` now supports `build_directory()` and configurable prefix.
- **Zesk Kernel**: `zesk\Route_Controller` throws `zesk\Exception_Invalid` on `get_route_map` if the pattern and URL do not match for some reason.
- **Zesk Kernel**: Fixed *PHP Notice*:  Trying to access array offset on value of type int in `zesk/modules/database/classes/Database/SQL.php` on line 472
- **Zesk Kernel**: New countries

## [v0.28.5][]

Maybe we're avoiding even-dotted releases. Next up is dot 7.

- **Zesk Kernel**: Support for loading simple `.sh` files as configuration files
- **Zesk Kernel**: Application handles redirects in main exception handler allowing exceptions to throw redirect exceptions.

## [v0.28.3][]

Who likes .2 releases? Not this dude.

- **ORM Module**: `zesk schema` now exits 0 only when the schema sync completes successfully with zero changes.
- **ORM Module**: fixing `text` default value, no default 0 for timestamp
- **Zesk Kernel**: fixing issue with URL unparsing and `mailto:` addresses

## [v0.28.1][]

Version added to support modern MySQL docker containers enforcement of `Timestamp` defaults:

- **MySQL Module**: `blob` and `text` data types no longer forced to have a default of blank string `""` - instead you must specify this in your SQL if the version of MySQL allows it.
- **Session Module**: Remove invalid `timestamp` default 0 values for MySQL

## [v0.28.0][]

- **Permission Module**: Add exception handling during permission checks, automatic fail
- **Permission Module**: Added `@property` labels for `zesk\Role` class. Fixed bug when user local role cache is not cleared. Removed deprecated calls.
- **Zesk Kernel**: Removing option hooks

## [v0.27.1][]

- **Bootstrap DateTimePicker**: Adding support for range selection
- **ORM Module**: Deprecated call removal, support for primary keys in default output
- **Tag Module**: Added `zesk\Tag\Control_Tags` debugging, an label `name_column` set in `zesk\Tag\Class_Label`
- **Zesk Kernel**: Fixed issue with `zesk\Command` where defaults set up in `initialize` were not honored.
- **Zesk Kernel**: Supporting compiling of `.less` to `.css` as part of Zesk build.
- **Zesk Kernel**: `zesk\Command_File_Convert` now supports `target-prefix` and `mkdir-target` to support mapping files from `foo.less` to `../css/foo.css` if you want.
- **Zesk Kernel**: `zesk\HTML` no longer adds `id` attribute to inputs
- **Zesk Kernel**: `zesk\PHP::log` added as interface to PHP system logger
- **iLess Module**: Adding `lessc` command-line option, use `zesk lessc` to compile `.less` files to `../css/foo.css` automatically.

## [v0.27.0][]

- **Contact Module**: Fixing call to `Mail` class
- **Image Picker Module**: Added `zesk\Image_Picker_Module::image_selector_before` and `_after` hooks
- **Net Module**: Adding comments to POP iterator
- **ORM Module**: Added `zesk\Server->alive_ips()` to fetch known valid IPs of alive servers
- **ORM Module**: Adding better handling of ORM Not found exceptions
- **ORM Module**: Refactored `zesk\Exception_ORM_NotFound` handling to support common mechanism for interception.
- **ORM Module**: Reset global settings upon reset hook
- **ORM Module**: `zesk\Class_Foo::name` now works to set the name in an ORM `Class_ORM` object - ONLY when not explicitly set by the code in `$this->name`
- **ORM Module**: `zesk\Controller_ORM->widget_control_classes()` properly generates class names using the correct parsing of namespace/class
- **PolyGlot Module**: Typo bug in `locale_query` call
- **ReactJS Module**: fixing asset manifest locating in ReactJS
- **Selection Module**: Updated strings to `::class` constants
- **Tag Module**: Removing references to `zesk\Tag_Label` -> `zesk\Tag\Label`
- **Tag Module**: Updated version
- **Widget Module**: Deprecating `zesk\Widget->orm_class` for `zesk\Widget->orm_class_name` as it's a string. **Widget Module**: Added `zesk\Widget->find_parent_class_orm` to find any valid parent class which contains an `orm_class_name()` which is non-empty and returns the `->class_orm()` of type `zesk\Class_ORM` **Widget Module**: `zesk\Control_Filter` will not override user-set names in `->initialize()` **Widget Module**: `zesk\Control_Order` uses `zesk\Exception_RedirectTemporary` now **Widget Module**: `nav/tabs` theme now adds an `id` to the `li` used in the main tabs to allow tooltips. **World Module**: Updated country info, download URL
- **World Module**: Logging missing currency codes as `debug` not `error`
- **Zesk Kernel**: Added a new redirect called `zesk\Exception_RedirectTemporary`
- **Zesk Kernel**: Adding `zesk\ArrayTools::extract` as a better name than `zesk\ArrayTools::key_value` for extracting key values from an array. Deprecating `zesk\ArrayTools::key_value`.
- **Zesk Kernel**: Adding more stack frames to output exceptions
- **Zesk Kernel**: Better support for `reset` hooks to clear out global data based on configuration context.
- **Zesk Kernel**: Capturing image exceptions
- **Zesk Kernel**: Directory::size does not throw zesk\Exception_Directory_Not_Found
- **Zesk Kernel**: Fixing `Mail` call to self
- **Zesk Kernel**: Fixing issue with image data missing
- **Zesk Kernel**: Fixing issue with input not being returned by upload file
- **Zesk Kernel**: Hook constants are now CAPITALIZED
- **Zesk Kernel**: Hook constants are now by default capitalized
- **Zesk Kernel**: JSON handles stdClass better
- **Zesk Kernel**: Locale removing unused hook
- **Zesk Kernel**: More details on empty theme rendering.
- **Zesk Kernel**: Reconfigure improvements
- **Zesk Kernel**: `php-find.sh` adding yml files
- **Zesk Kernel**: `zesk configure` now supports `rm` for files.
- **Zesk Kernel**: `zesk\Application->reconfigure()` now calls `zesk\Objects->reset()` explicitly
- **Zesk Kernel**: `zesk\Application->theme_find` now returns a array of the found themes and the paths attempted to find them. **Zesk Kernel**: `zesk\Application->requestFactory()` is now a public function
- **Zesk Kernel**: `zesk\Locale` updates from deprecated usage, whitespace updates
- **Zesk Kernel**: `zesk\URL::parse` now always sets the `url` key upon return
- **Zesk Kernel**: adding optional append_error setting
- **Zesk Kernel**: fixing merge array func for hook default behavior
- **Zesk ORM**: Improved exception handling for missing or unlinked objects.

## [v0.26.2][]

- **CSV Module**: Remove warning in PHP 7.2 to avoid `count(null)`
- **Content Module**: Adding some `::class` constants instead of strings
- **Content Module**: Image `view` theme adds `data-src` attribute which messes up `unveil` module. So renamed attribute to `data-original` instead.
- **Image Picker Module**: Fixes for updated version of Zesk
- **Image Picker Module**: Fixing theme path
- **Image Picker Module**: Permit the setting of a `zesk\Content_Image` to `zesk\User` link path for queries
- **Job module**: Progress always updates the database
- **ORM Module**: Added support for `HAVING` clauses in `zesk\Database_Query_Select`
- **ORM Module**: Better support for object class aliases in linkages between classes (select query `->link` calls)
- **ORM Module**: Ensured `delete` actions redirect to `/` if no URL set
- **ORM Module**: Fixing  to translate using alternate locales
- **ORM Module**: `_action_default` getting called when `->user` is null, causing an error. Now throws an `Exception_Authenticated` instead.
- **ORM Module**: `zesk\ORM::clean_code_name` now supports `-` and `_` as valid characters in strings (previously just `-` was permitted)
- **ORM Module**: `zesk\Session_ORM::one_time_create` fixed a crashing bug
- **Picker Module**: Some updates for locale changes.
- **PolyGlot Module**: Updating translate page and default, fixing locale issues.
- **Selenium Module**: Getting running again
- **Snapshot Module**: fixing format bug in `Timestamp`
- **Workflow Module**: `zesk\Workflow_Step->is_completed()` is now abstract, and the default function `substeps_completed()` now returns 0 or 1 by calling `is_completed()`.
- **Zesk Kernel**: Do not store identically IDed objects when saving recursively constructed objects
- **Zesk Kernel**: Fix incorrect warnings about depth when exception occurs in `zesk\Application` main
- **Zesk Kernel**: Fixed warning in `zesk\Application` re: `$starting_depth`
- **Zesk Kernel**: Rearranged `zesk\Autoloader` so constants are at top
- **Zesk Kernel**: Theme `bytes` now correctly uses the current locale
- **Zesk Kernel**: `actions` template now correctly outputs `ref`
- **Zesk Kernel**: `zesk database-dump` adding non-blocking to database dump command
- **Zesk Kernel**: `zesk\Controller_Theme::after` only runs `auto_render` when it is HTML output.
- **Zesk Kernel**: `zesk\JSON::prepare` now takes optional arguments parameter for serializing JSON objects and passing arguments to serializer functions
- **Zesk Kernel**: `zesk\Request->urlComponents()` no longer throws `zesk\Exception_Key` - returns a default value if key not found
- **Zesk Kernel**: `zesk\Response\JSON` now supports `zesk\JSON::prepare` arguments
- **Zesk Kernel**: `zesk\Response` now logs a debug message when the `output_handler` is modified to help debug issues with responses.
- **Zesk Kernel**: adding missing forgot template

## [v0.26.1][]

**Thu Jun 27 00:06:53 EDT 2019**

- **Zesk Kernel**: `zesk\Application` now loads `etc/maintenance.json` (or `$app->maintenanceFile()`) upon creation, and places values in `$app->option('maintenance')` which is an array.
- Revising `zesk maintenance` to set simpler key in maintenance structure

## [v0.26.0][]

**Fri Jun 21 10:19:44 EDT 2019**

- Sorting filters in dropdown list for `Control_Filter_Selector`
- **PolyGlot Module**: Fixed incorrect `::parse_dialect` call
- **Preferences Module**: Upgrading ORM calls, removing deprecated calls.
- **Tag Module**: Added feasibility for tag queries
- **World Module**: Updated country info

## [v0.25.2][]

**Fri May 24 20:34:53 EDT 2019**

- **Bootstrap DateTimePicker**: Fixed issue with changing semantics of `JSON::encode`
- **Content Module**: fixing constants for `zesk\Controller_Content_Cache`
- **Developer Module**: Fixing IP check on `ip_restrict` option in `zesk\Developer\Module::ip_restrict` options
- **Help Module**: Fixed `$response->json()` error
- **Help Module**: Fixed issue with HTML being stripped in tips
- **Help Module**: Fixed issue with user-reset, and `<kbd>` not showing up in tips
- **Image Picker Module** - Fixed issues relating to semantic differences between `JSON::encode` and `JSON::encodex` **TinyMCE Module** - Fixed issues relating to semantic differences between `JSON::encode` and `JSON::encodex`
- **Moment Module**: Updated download URLs to latest paths 2019-04-21.
- **ORM Module**: Added implementations of `->duplicate()` to `zesk\Database_Query` and `zesk\Database_Query_Select`
- **ORM Module**: Enhancing object serialization structure by adding `zesk\ORM\Walker`, and `zesk\ORM\JSONWalker` to enable shared options while generating output.
- **ORM Module**: Fixed title generation for `zesk\Database_Query_Select`
- **ORM Module**: Moved the majority of the `zesk\ORM->json()` functionality into `zesk\ORM\Walker`
- **ORM Module**: `zesk\Module_ORM->schema_synchronize()` now outputs shorter error for class not found (but continues)
- **Permission Module**: Now support configuration option `zesk\Module_Permission::warning` to set global warning for **denied** permissions. (Can be overridden on a per-check basis)
- **Permission Module**: Support configuration option `zesk\Module_Permission::role_paths` for paths to role permission files; also support `json` extension as well.
- **Permission Module**: `zesk\Module\Permission` now supports an `warning` option which, when false, displays denied permissions for users. Useful for cleaning up logs for permission tests which are unimportant. Also see configuration option `zesk\Permission\Module::warning` which defaults to `false`
- **PolyGlot Module**: `zesk\PolyGlot_Token` updated `->json(JSONWalker $walker)` call
- **Preference Module**: Adding debug mode to controller to determine when strings do not match and why.
- **Preference Module**: Reducing debug info
- **ReactJS Module**: React modified the `.js` files served, so now serve main `index.html` file via development proxy instead of attempting to map  files directly (closer to real-world testing.)
- **ReactJS Module**: React now serves up more than just the `bundle.js` file so fetch index file from proxy as well isntead of modifying i`ndex.html`.
- **Selection Module**: Removed many deprecated function calls.
- **Selenium Module**: Moved to namespace `zesk\Selenium\Module` etc.
- **Various Modules**: Fixed issue where `$response->json(array(...))` called instead of `$response->json()->data(array(...))`
- **WebApp Module**: During cron, every minute, it will remove dead instances and sites when servers go away or change.
- **WebApp Module**: Fixed `zesk\WebApp\ControllerTrait` to support correct invokation of `->response`
- **WebApp Module**: Local variable is now called `_app_root` to highlight its privacy as well as to avoid having autocomplete mistake it for `->application`.
- **WebApp Module**: Moved authentication around to allow extending web app handlers, added `zesk\WebApp\ControllerTrait` to allow adding webapp functionality to any `zesk\Controller`, `zesk\WebApp\Module->add_message_route()` added.
- **WebApp Module**: Regenerate configuration and scan for apps each cron run
- **WebApp Module**: Remove warnings when instances have errors in `zesk\WebApp\Module->instance_factory()`
- **Widget Module**: Adding logging to login/logout controllers (`zesk\Controller_Login`)
- **Widget Module**: Support `$options` as third parameter in `$user->can()` call
- **Widget Module**: `zesk\Widget->clear()` now recursively clears all children widgets's errors as well.
- **Widget Module**: `zesk\Widget->submit_redirect()` no longer passes back the `object` key with JSON version of the object.
- **Zesk Core**: `zesk\Hookable::hook_results` now supports the value of `true` for `$hook_callback` to skip `zesk\Hookable::combine_hook_results` - consider removing `::combine_hook_results` completely and use a hook to combine arrays, etc.?
- **Zesk Core**: `zesk\JSON::prepare` now properly handles `null` values
- **Zesk Core**: `zesk\Locale::auto_path` now passes through `zesk\Application->paths->expand`
- **Zesk Core**: `zesk\Model::json` removed
- **Zesk Core**: `zesk\Route_Theme` no longer automatically adds `HTML` contents to `JSON` ones. Either set global `zesk\Route_Theme::json_html` to `true` or set it on a per-Route basis.
- **Zesk Core**: `zesk\Router->url_replace` no longer valid, use `zesk\Route->url_replace` instead.
- **Zesk Core**: `zesk\Template->push()` now takes a theme path instead of a template path (adds a `.tpl` to the end)
- **Zesk Core**: `zesk\Timestamp->json()` no longer takes unused `$options` parameter.
- **Zesk Kernel**: Adding controller hooks: `before` and `after` running around controller method.
- **Zesk Kernel**: Fixing `Route` parsing of arguments, replacing all special chars in pattern
- **Zesk Kernel**: Support serialization of `zesk\Process` - possibly for testing
- **Zesk Kernel**: `zesk eval` now supports option `--skip-configure` to skip configuration of the application; previously it was not called, and it now is prior to running any code.
- **Zesk Kernel**: `zesk\Application` now runs `->inheritConfiguration` if hook `configured_files` is run.
- **Zesk Kernel**: `zesk\Configuration_Loader` now returns null from `->load_one` if file is not found.
- **Zesk Kernel**: `zesk\Controller->render()` was removed, deprecated.
- **Zesk Kernel**: `zesk\Exception_Configuration` now includes configuration name in message.
- **Zesk Kernel**: `zesk\Hookable` no longer allows hooks to be stored as options, instead, they are stored as private members of `zesk\Hookable`. Also added new method ->add_hook to add object-specific hooks. As well, hook names were cleaned previously using `zesk\PHP::cleanFunction` but now are cleaned using `zesk\Hooks::clean_name`
- **Zesk Kernel**: `zesk\Hooks` now adds final shutdown function to log to PHP error log if exists with a failure.
- **Zesk Kernel**: `zesk\JSON::encode` was modified so it can be used as a general purpose call, and uses commonly-desired options (largely do not escape slashes ... what's up with that?). Also added `zesk\JSON::zencode` which is the **Zesk** JSON encoding written in PHP and supports object conversion as well as special keys prefixed with a star.
- **Zesk Kernel**: `zesk\JavaScript` - Fixed issues relating to semantic differences between `JSON::encode` and `JSON::encodex` - latter supports *-prefix keys to pass values through unmodified, useful for generating JS for client consumption.
- **Zesk Kernel**: `zesk\Modules` no longer stores value `module` which contained `zesk\Module` subclass - stored in key `object` now. All references too `module` have been removed. Also fixed configuration defaults.
- **Zesk Kernel**: `zesk\Route` arguments was not initialized to `null` unless it was `->match()`ed first. So now args defaults to null.
- **Zesk Kernel**: `zesk\Router->add_route(...)` now returns an object always.

## [v0.25.1][]

**Mon Mar 25 23:54:49 EDT 2019**

- **Cron Module**: Fixing upgraded `zesk\Locale` API calls.
- **Help Module**: Fixing `zesk\Locale` API calls.
- **ORM Module**: `zesk\ORM->member_model_factory()` calling `->refresh` creates infinite loops. Removing `->refresh()` call.
- **Server Module**: Fixing `zesk\Controller_DNS` to correctly link to `zesk\Diff\Lines`
- **Zesk Kernel**: `zesk\Route\Theme` supports HTML to JSON output better.

## [v0.25.0][]

**Mon Mar 25 21:16:05 EDT 2019**

- **Content Module**: `zesk\Content_Data::copy_file` now throws `zesk\Exception_File_NotFound` if unable to find file locally, minor fix.
- **Content Module**: `zesk\Content_Image::_force_to_disk()` now catches `zesk\Exception_Not_Found` and handles it correctly.
- **MySQL Database**: Fixing `database-dump` command to properly order parameters such that `--defaults-extra-file` is first in parameter list
- **ORM Module**: Default generation of polymorphic class name upon load does not lowercase the class anymore.
- **ORM Module**: `zesk\Database_Query_Select_Base::one_timestamp` is now identical to `::timestamp`
- **ORM Module**: `zesk\Server` now insists that `alive` is a `zesk\Timestamp` before checking
- **PolyGlot Module**: Renamed `zesk\PolyGlot_Token::to_json` to `::json`, fixed `zesk\Controller_PolyGlot::action_load` cannon error
- **WebApp Module**: Adding `action_health` to controller to return 200 on alive and non-200 on "down" - used by load balancers to determine if a target is healthy.
- **WebApp Module**: Adding `zesk\WebApp\Module::control_file` hook, adding scan debugger progress
- **WebApp Module**: Adding health support to controller
- **WebApp Module**: Adding support for dry run later, fixing no write issue
- **WebApp Module**: Authentication is now required for most of `zesk\WebApp\Controller` actions (using a shared key `zesk\WebApp\Module::key`), added support for `hostnames` key in `webapp.json` to force host names in rendered configuration files. Added `zesk webapp-api` call to invoke things via command line. Any `zesk\Server` associated with `zesk\WebApp\Module` will add a `zesk\Server_Data` record with the name `zesk\WebApp\Module` and a value of 1. Added `zesk\WebApp\Module->server_actions($action)` to message each server serially and return the results.
- **WebApp Module**: Fixed generator so empty files are deleted. Passing all extraneous data (`zesk\WebApp\Generator_Apache`-specific) through to templates, allowing for overrides. Set default `indexes` to `['index.php','index.html']`. Adding support for serving `/public/` instead of `/build/` for development systems, and only rendering vhost files for web apps assigned at least one host name.
- **WebApp Module**: Require `git` and `subversion` module
- **WebApp Module**: `zesk webapp-api` corrected category and added basic description.
- **WebApp Module**: `zesk\WebApp\Generator->replace_file` now optionally supports saving previous version
- **WebApp Module**: `zesk\WebApp\Module->instance_factory(true)` now refreshes `appversion` for each instance, then refreshes the repo versions for each instance.
- **WebApp Module**: `zesk\WebApp\Type\Zesk` should use `zesk.sh` instead of `zesk-command.php` - doesn't work otherwise
- **Widget Module**: `zesk\Control_Duration` now properly supports ranges greater than 24 hours
- **Zesk Kernel**: Added `zesk\JSON::prepare` to flatten complex objects using object methods
- **Zesk Kernel**: Fixing issue with `zesk latest` where `git clone` fails then kills environment.
- **Zesk Kernel**: Fixing warning in `zesk\Response\Text`
- **Zesk Kernel**: `zesk\PHP::feature` now has associated `zesk\PHP::FEATURE_FOO` constants.
- **Zesk Kernel**: `daemon-bouncer.sh` renamed to `file-trigger.sh` which reflects better what it does. Old binary available for 6 months.
- **Zesk Kernel**: `zesk configure` Handling non-interactive flag
- **Zesk Kernel**: `zesk\Command_Configure` -> `zesk\Configure\Engine` particularly for hooks and extending the configure engine.
- **Zesk Kernel**: `zesk\Command` now correctly outputs class name in errors
- **Zesk Kernel**: `zesk\Configure\Engine` fixing prompt for files differ
- **Zesk Kernel**: `zesk\Deploy` now catches all `\Exception`s
- **Zesk Kernel**: `zesk\Directory::delete` now throws a permission error if it is unable to delete the final directory
- **Zesk Kernel**: `zesk\Image_Libary_GD` now returns a more specific string in the `zesk\Exception_Semantics` containing the file name when unable to create an image from a file.
- **Zesk Kernel**: `zesk\Locale` now supports durations up to millenium for `->now_string(...)`
- **Zesk Kernel**: `zesk\Request` supports `multipart/form-data` posts by pulling in raw PHP `$_REQUEST`.
- **Zesk Kernel**: `zesk\Response\JSON` calls `zesk\JSON::prepare` on output content before converting to JSON
- **Zesk Kernel**: `zesk\Text` Added additional documentation for functions
- **Zesk Kernel**: `zesk\TimeSpan` added proper and more comprehensive formatting
- **Zesk Theme**: `link` theme supports boolean `allow_javascript` = false, boolean `auto_prepend_scheme` = false
- **Zesk Theme**: `link` theme now, by default, ignores `javascript:` links, and allows for URL parts replacement of the text value (so you can use `{host}` as a text value to output the URL host)

## [v0.24.0][]

**Thu Mar  7 12:23:17 EST 2019**

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

## [v0.23.5][]

**Wed Feb 20 21:19:34 EST 2019**

- new version bump - old version did not stick in packagist

## [v0.23.4][]

**Wed Feb 20 20:39:44 EST 2019**

- **DEPRECATED**: `zesk\Command_Loader` no longer supports applications which have their core class named `*.application.inc`
- **ORM Module** added `zesk\ORM->raw_member()` to allow retrieval of a member avoiding `zesk\ORM->refresh()`
- **ORM Module**: Removed a duplicate line in `zesk\Database_Query_Select` duplication
- Moved application configuration into new protected method in `zesk\Command->application_configure()` to be called at start of `zesk\Command->go()` call, moved over from `zesk\Command_Loader.php` as it should be internal, not external.
- `zesk\Command` fixed debug message when configuration missing
- `zesk\ORM::member_model_factory` was performing an extra `->fetch()` of each object when a `->refresh()` was all that is needed (maybe not?). `zesk\ORM::refresh()` now returns self

## [v0.23.3][]

**Fri Feb  8 00:42:41 EST 2019**

- **Widget Module**: `zesk\Control_Select`: Fixed issue with single selection would output a hidden input which was blank; now uses the first key if `value` is not set explicitly.

## [v0.23.2][]

- Code style for `.tpl` files

## [v0.23.1][]

- Better support for control `"status" => false` for controls submitted via AJAX, so now `$response->response_data()` has priority over JSON generated by `zesk\Response\HTML` and other responses, allows overriding of default `status` of `true`.
- Fixing warning about no `$attr[pattern]` in `zesk\Request::parse_accept` in default case
- PHP 7.2 fixes for `zesk update` command
- `Image_Library->installed()` call should not be static, fixing both classes
- `bin\daemon-bouncer.sh` Adding example crontab line to documentation
- `last-seen` theme: Adding never logged in case.

## [v0.23.0][]

**Sat Feb  2 00:41:48 EST 2019**

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
- `zesk\Options::optionPath` wasn't working well, converted it to use `apath()`
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

- **MySQL Module** `MySQL\Database::shellCommand` now uses credentials files to avoid warnings about passwords on command-line
- Do not pass all options to `zesk\Database::shellCommand` from `zesk database-connect` command
- `MySQL\Database::shellCommand()` now accepts a new option `non-blocking` with a boolean value to dump databases in a non-blocking manner. Also added support for `MySQL\Database::$shell_command_options` to validate shell commands and provide internal documentation to their effects.
- fixing `zesk info` for database module
- Adding `zesk database-connect --grant --host localhost` option to support generating grant commands given existing credentials configured.

### Deprecate code

- Removed deprecated `zesk\Response_Text_HTML`

## [v0.22.0][]

- **ORM Module**: adding warning for ORM object members which are has_one and `column_types[member] !== object`
- Added support for `zesk\Route` standard option `react` to spit out React template page (except for JSON calls)
- Added support for Slovak language
- Adding PHP Coding Style support and integration with git pre-commit hook
- Adding `zesk\Database::shellCommand` valid options and usage in dump command
- Fixing comments in `zesk\Response`
- Fixing erroneous output in `zesk database-connect` when dumping formatted output
- **Forgot Module**: Fixing issues with forgotten password expiration, and submission via React apps
- **Contact Module**: Fixing module `contact` bootstrap
- JSON support for `zesk database-connect` call
- Merge pull request #1 from Arthom/master
- Removed deprecated functions `zesk\ORM::cached` and `zesk\ORM::class_table_exists($class)`
- Removing deprecated `$app->zesk_root` usage
- Removing deprecated aliases cache file (`command-aliases.json`, etc.)
- Removing deprecated usage of `zesk\ORM::cache_class`
- `zesk\Database::shellCommand` now supports option `sql-dump-command` boolean value
- `zesk\Route` added `OPTION_` constants for standard options
- `zesk\Server` added remote_ip field and accessor
- adding `bin/daemon-bouncer.sh` to zesk core scripts
- adding debug logging to `zesk command` - `subversion` subcommand
- adding remote_ip to `zesk\Request`
- added `bin/hooks/pre-commit` to share client-side hooks


## [v0.21.4][]

- `zesk version --zesk` now returns the current Zesk version (not the application version)
- `zesk\Version::string()` now requires a `zesk\Locale`

## [v0.21.3][]

- Fix issue when `zesk\Request` is `POST`ed zero-length data with content type `application/json`.
- Fixing documentation to `zesk\Application::locale_path`
- Loosened definition of `zesk\Control_Login::submitted()` to be considered when the request is a `POST` and the variable `login` is present in the form. (Previously required the presence of the `login_button` request variable)
- `zesk\Controller_Login::action_logout` supports `JSON` response.

## [v0.21.2][]

- Add comments to support `utf8` character sets for legacy database setups
- Fixed an issue with `zesk schema` which would output the application hooks in a rather verbose manner
- `ReactJS` module now correctly includes `css` in build installations

## [v0.21.1][]

- removing deprecated reference to ``$application->zesk` in `zesk\Module_Job`

## [v0.21.0][]

- Added ability to store user password hash in the database as well as handle changing it
- Adding `zesk\Interface_Member_Model_Factory` `zesk\Interface_Factory` support to `zesk\Application` and `zesk\ORM` and support throughout module `ORM`
- Adding `zesk\Database_Query::set_factory(zesk\Interface_Member_Model_Factory $factory)`
- Removed `zesk\Database_Query::module_factory`
- Better linking between interated objects to allow child objects to inherit state from parent, specifically for polymorphic classes
- Deprecated `zesk\Database_Query::object_cache`, `zesk\Database_Query::object_class`, `zesk\Database_Query::class_object`, `zesk\Database_Query::object_factory`
- Added `zesk\Interface_Member_ORM_Factory` 

## [v0.20.1][]

- `ARRAY_FILTER_USE_BOTH` not compatible with PHP5, removing references to it
- Fixed an issue in default `zesk\CacheItemPool_Array` where cache items were not returned on second call.
- Fixing `zesk\System::ifconfig` caching, allowing configuration setting `zesk\System::ifconfig::expires_after`, defaults to 60 (seconds)
- Upgraded `$locale->__` usage in `Polyglot` module
- `zesk\Daemon\Module` fixed a type hint
- `zesk\Route::factory` now accepts options of `file` to force a route of `zesk\Route_Content` which serves the file directly.
- `zesk\Route_Content` extended to support `file` option to serve files directly
- `zesk\Server` no longer populates `ip4_external` and `name_external` if not set; they remain empty if not set by any other method. `ip4_internal` and `name_internal` are taken from the first `zesk\System::ip_addresses` which is not localhost, or `127.0.0.1`.
- fixing server awareness callback and `zesk\Server::` config options

##  [v0.20.0][]

- `User::fetch` not called by `User::fetch_by_key` - need to make this consistent
- `Module_ORM::all_classes()` return properly capitalized class name
- `zesk eval` now supports state between command lines in interactive mode
- `zesk\Adapter_TestFramework` adding `::assertIsString`
- `zesk\File_Monitor` issues slews of warnings due to race condition upon deletion of vendor directory - silence warnings
- `zesk\File` fixes to handle no `memory_limit` ini setting
- `zesk\Net_HTTP_Client_Test` Setting up test URL as constant
- `zesk\PHP::ini_path()` added
- `zesk\Response\HTML` links now support all applicable attributes including `sizes`
- added `zesk\Net_SSL_Certificate` to sync certs from curl site
- adding `zesk\PHPUnit_TestCase::assertIsInteger`
- fixing `Kernel_Test.php` and allowing setting `$_SERVER['PATH']`
- Added `bin/link-vendor-to-dev-zesk.sh` to allow linking to development ZESK in any project
- support **http** and **https** in `zesk\Net_HTTP_Client::simple_get`

## [v0.19.1][]

- `Server::DISK_UNITS_FOO` is now captialized
- `zesk\Application::hook_main` can throw a `zesk\Exception_Redirect` now
- For `zesk version` do not require a tag value (eliminated warning)
- Fixing issue with `zesk\Controller_Search` not displaying content

## [v0.19.0][]

### Core issues

- Added `Directory::must` to require a directory to exist
- Adding `$context` to `zesk\Exception_File_NotFound` to give ... context to exception
- Adding lots of comments to `zesk\Net_HTTP`
- Fixing no locks to delete
- Fixing zesk app when composer not run
- Removing map variables which are no longer valid after evaluation
- Support preprocessing fixed variables and applying when generating routes
- `zesk\Command_Loader` fixed output warning to used correct extension
- Exceptions should return an error
- **Cache**: Removed warnings from `zesk\CacheItemPool_File`

### Misc Module fixes

- **Markdown Module**: Adding `zesk markdown` to markdown module command path
- **Markdown Module**: fixing double process output
- **Moment Module**: Correctly output `$locale->id()` for debugging
- **Polyglot module**: Enhancements and fixes for dup list count
- **Widget Module**: Remove warnings to `zesk\Control_Checklist` when iterating over keys
- **Widget module**: `zesk\Widget->widget_factory()` now inherits response from called `zesk\Widget`

### ORM Module

- Adding proper inherit options so options-based tables operate correctly in queries and iterators
- Better error handling in `zesk\Image_Library_GD`, better exception throwing
- Support `zesk\ORM::members()` returning "extra" members

### Picker/Image Picker Module

- **Image Picker Module**: Fixing controller paths, using ::class
- **Picker Module**: `Module_Picker` psr4 updates
- Fixing picker JSON for renaming

## ReactJS Module

- Default host/port for proxy
- handling build via `asset_manifest.json`
- Adding `dot_env_path` to `ReactJS` module

## [v0.18.1][]

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
- adding `ArrayTools::findInsensitive()` and using in `zesk configure`
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

## [v0.18.0][]

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
- `zesk\Session_ORM->initializeSession()` now saves the session instead of waiting until the process exits to ensure the session has an id.
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

### Widget changes

- `zesk\Control_Login` has a new hook `submit` which can be used to short-circuit what response is returned by an authenticated login.
- `zesk\Control_Select`: Supporting `key => array` for allowing attributes in `<option>`
- Added call `zesk\Widget->preferJSON()` to determine if JSON should be returned, added to `zesk\Control_Login`

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

## [v0.17.0][]

### New features

- Adding `Log_Mail` module to log mail to database and optionally prevent it from being sent on staging sites
- `zesk\Command` - Adding file validation so symlinks or links work as well as options of type `file`
- Improving email pattern matching (see `functions.php`)
 - `is_email` test improvements
- Modernizing `zesk check` fix for PHP comments
- `zesk daemon` now supports multiple `--watch` arguments which can be used to watch non-obvious files for changes to force the daemons to restart. For example, if you have a link to your active installation.
- `zesk update` now calls `hook_update` or `hook_updated` for each module.
- `zesk\HTML::wrap` renaming
- `zesk\Route_Template` has been removed
- adding constant OPTION_DESCRIPTION to `zesk\Command_GitHub`

### Bugs fixed

- Fixing `zesk\Locale_EN` plural implementation to avoid PHP warnings for short words
- Fixing `zesk\View_Errors` unintialized `zesk\Response` issue
- Fixing inherited `zesk\Temporal` implementation in `zesk\TimeSpan`
- Fixing issues with all language strings already translated in all languages (moving to `en.php`)
- Ignore `\__PHP_Incomplete_Class` in `zesk\JSON::encode`
- Removing warnings from `zesk help` template

### Changed functionality

- Using `Widget::class` for `->widget_factory()` calls instead of strings.
- Removing deprecated `$account` in `zesk\Controller_Authenticated`
- Having `zesk\Route` with a `template` option will no longer create a route of type `zesk\Route_Theme` - please update your code accordingly and use `theme` option instead.
- Fixing `zesk\Route_Redirect` to throw `zesk\Exception_Redirect`
- `zesk\Class_ORM`: The `$column_types` values are automatically initialized to `self::type_object` by `$has_one` entries
- Allow `zesk\Route::arguments_by_class()` before route execution
- `zesk\Timestamp::add_unit` no longer supports `("second", 1)` argument syntax
- `zesk\Route_Controller` option `controller prefix` and `controller prefixes` is no longer used. The `controller` option must be a fully-qualified class name of a `zesk\Controller` subclass.
- `zesk\Objects->singletonArguments()` and related calls no longer support the usage of the static `instance` method for class creation. You should have a static methods called `singleton` to take advantage of `zesk\Objects` global registration for singletons.
- `zesk\Database::feature_` and `zesk\Database::option_` constants are now all uppercase
- `zesk\File::put` no longer calls hook `File::put`
- `zesk\Directory` has had the global option `zesk\Directory::debug` removed and no longer supports logging directory creation. (Reduce global usage)
- `zesk\FIFO` no longer takes a path relative the the app data path as its first parameter, it now takes an absolute path to reduce global usage.

- `zesk\Command` has a method called `validate_file` which can be implemented by subclasses if desired to validate any arguments which are a `file` or `file[]`.
- `zesk daemon` now has a `--watch` directive to set up a list of files to monitor and quit when they change
- `zesk` commands which take `file` option now accept links as well


### Removed functions

- `zesk\Autoloader::file_search` was removed
- `zesk\Kernel::zesk` was removed
- `zesk\Lock::getLock` was removed
- `zesk\Lock::require_lock` was removed
- `zesk\Directory::temporary` was removed
- `zesk\Database::register` was removed
- `zesk\Database::database_default` was removed
- `zesk\Database::unregister` was removed
- `zesk\Database::valid_schemes` was removed
- `zesk\Database::register_scheme` was removed
- `zesk\Database::schema_factory` was removed
- `zesk\Database::_factory` was removed
- `zesk\Database::instance` was removed
- `zesk\Database::databases` was removed
- `zesk\Locale::translate` was removed
- `zesk\Locale::locale_path` was removed
- `zesk\Locale::current` was removed
- `zesk\Class_ORM::cache` was removed
- `zesk\Class_ORM::cache_dirty` was removed
- `zesk\Class_ORM::classes_exit` was removed
- `zesk\URL::current` was removed
- `zesk\URL::current_query_remove` was removed
- `zesk\URL::current_url` was removed
- `zesk\URL::current_port` was removed
- `zesk\URL::current_host` was removed
- `zesk\URL::current_scheme` was removed
- `zesk\URL::add_ref` was removed
- `zesk\URL::has_ref` was removed
- `zesk\URL::current_left` was removed
- `zesk\URL::current_left_host` was removed
- `zesk\URL::current_left_path` was removed
- `zesk\URL::current_is_secure` was removed
- `zesk\Configuration::pave_set` was removed
- `zesk\Configuration::pave` was removed
- `zesk\Controller::factory` was removed
- `zesk.inc` was removed (used `autoload.php`)

### "Heading toward a 1.0" Progress

- Elimination of most globals, moving from `zesk::` to `Application::` in places where it makes sense - **in progress**
 - <strike>Removal if `global $zesk`</strike>
 - <strike>Removal/reduction of `zesk()`</strike>
 - <strike>Removal/reduction of `app()`</strike> - None left
 - <strike>Removal/reduction of `Kernel::singleton()`</strike>


## [v0.16.5][]

- Detect PHPUnit tests, fixing test suite

## [v0.16.4][]

- `zesk\Widget::initialize` now throws an `Exception_Semantics` if called without a `zesk\Response` set up in the widget for 
- `zesk\Command_Configure` enhanced ability to compare and skip identical files
- `daemon` layout panel was added
- `daemon` system status was added
- ensure one newline at EOF for `zesk configure` file command `crontab` to avoid re-updates
- `zesk\Cron\Module` runs every cron, not every minute

## [v0.16.3][]

## Release v0.16.3

- `DaemonTools` system status support
- Moved `zesk\StringTools::wrap` to `zesk\HTML::wrap`
- `$module->path()` now supports `$suffix` parameter
- adding `crontab` command to `zesk configure` in `cron` module
- `configure` work
- Fixing issue with `zesk\Repository` generation
- Fixing `svstat helper` in `DaemonTools` module
- `zesk\Cron\Module` actually requires `ORM`

## [v0.16.2][]

## Release v0.16.2

- Added `zesk\Paths::expand($file)` which allows for shortcuts for relative paths `["./foo", "~/foo"]` are special Added `zesk\Paths::expand` functionality to application `::include_files()` (NOT to `include_paths`, which is deprecated!) Added `zesk\Paths::expand` functionality to configure core commands. Must be added to individual command implementations. ReactJS better error handling for content types Added `zesk latest` to perform latest updates for `zesk` bleeding edge
- Adding command `zesk latest` to automatically update zesk to the latest GitHub. For zesk core development only, or if you need bleeding-edge features and lots of volatility. Refactored Help module to use PSR4 paths
- Allow Controller reverse routes

### Bugs fixed

- Better reverse route generation
- Correcting login link in `Forgot` module
- Fixing deprecated calls in `zesk\Controller_Forgot`
- Subversion added support for `expand` paths
- `zesk configure` has `file_edit` command to insert
- `zesk\Request` fixes for controls, test fixes
- `zesk\Router` no longer returns `$action` when not found, returns `null` instead
- better debugging messages for `configure`
- `configure` enhancements and improvements
- enhanced `configure` with `file_edit`

## [v0.16.0][]

### `zesk\Response` Refactored

The majority of `zesk\Response_Text_HTML` have been moved up to glue functions in `zesk\Response` for eventual deprecation. `zesk\Response` HTML rendering has been largedly moved to templates. 

- `zesk\Response` contains handlers which handle each distinct output type; HTML being the most complex.
- `zesk\Response` objects are no longer members of `zesk\Application` and instead is generated via the `zesk\Application::main()`
- In addition, `zesk\Controller` now takes a `zesk\Route` as a construct parameter, and `zesk\Route`s inherit the `zesk\Request` which they matched.
- `zesk\Route::_execute` now takes a `zesk\Response` as the first parameter and it is expected to be modified by the route upon execution.

A large portion of the relationships and connections between `zesk\Route`, `zesk\Router`, `zesk\Request`, `zesk\Response` and `zesk\Application` have been modified.

- `zesk\Application::session()` and `zesk\Application::user()` now take `zesk\Request` as their first parameter.

#### `zesk\Controller` changes

- `zesk\Controller::__construct` is now a final function and subclasses must use the `::initialize()` parent chain to construct from options.
- `zesk\Controller::__construct` now takes the `zesk\Route` as its second parameter.
- `zesk\Controller` now includes `zesk\Request`, `zesk\Response` and `zesk\Route`. `zesk\Response` is (optionally)created upon object creation.

### `zesk\Application` state changes

We're migrating away from storing request/response state in the `zesk\Application` object and have moved this state deeper into the page generation. 

- Removed `zesk\Application::$session` and modified `zesk\Application::session()` to take `zesk\Request'
- Removed `zesk\Application::$request` 
- Removed `zesk\Application::$response`
- Removed `zesk\Application::$user` and modified `zesk\Application::user()` to take `zesk\Request'`
- `zesk\User::authenticated` now takes a `zesk\Request` (required) and `zesk\Response` (if you want to authenticate)

### Bugs fixed

- Fixing `zesk help` output, improving categories, and updating descriptions
- Fixing `zesk\Cleaner\Module` configuration errors when instance `lifetime` is `NULL`
- Fixing cache saving
- Fixing old deprecated calling APIs
- Improving comment on `pair()`

### Modified functionality

- `zesk\Module_Session::session_factory` no longer initializes the session; you must initialize the session with the `zesk\Request`
- `zesk\ORM::delete()` no longer deletes the default `zesk\ORM::object_cache()` associated with the object 
 - this is largely due to creation of cache objects for `zesk\ORM` classes which do not have associated `zesk\Class_ORM` settings so cache-deletion can not configured
 - if you need to clean up caches for your objects, implement `zesk\ORM::hook_delete()` in subclasses to delete object caches. Note this hook occurs after the object has been deleted from the database, but the object state is valid.
- `zesk\Response_Text_HTML` will be slowly refactored away to a unified `zesk\Response` object which changes behavior based on its content type.
- Moving process interface into `zesk\Application` context, not `zesk\Kernel`
- Now deprecated `$application->zesk_root()` - renamed to `$application->zeskHome()` `zesk\Locale_Default` is now as generic as it can be in generating default, generic values. Fixing various `zesk\(Date|Time|Timestamp)::format` calls which need to take `zesk\Locale` as first parameter. `zesk\Contact_Address::en_lang_member_names()` added, `::lang_member_names()` now requires `zesk\Locale` parameter. Converting `__("phrase")` to `$locale->__("phrase")` `zesk\View_OrderBy` fixing now-overridden local template variable `$url` which is now set in the `$application` level.
- Pass cache pool to `Response::cached`

### New functionality

- `zesk\File::find_first` now permits `null` as the 2nd parameter to simply search for the first file which exists in an ordered list.
- `zesk\Module_Database` was introduced for all `zesk\Database` and related subclasses
 - `zesk\Application::database_module()`, `zesk\Application::database_registry()` are now activated when `zesk\Module_Database` is loaded.
 - `zesk\Application::database_factory()` is a synonym for `zesk\Application::database_registry()` and both behave identically; although the `registry` call is semantically correct as it returns the same object on 2nd identical invocation.
- `zesk\Command::configure()` now supports JSON-files and extensions
- `theme/response/text/html/` theme path was added to enable customization of HTML page output.
- Added the ability to add the token @no-cannon to any file to avoid having cannon update it
- Add ability to add a class to the main `markdown` theme `div`
- Adding version to `TinyMCE` module share path to avoid caching issues between library versions. Should make this a standard practice among modules which load their own JavaScript via relative paths.
- Better PUT/POST JSON handling
- `zesk\Exception_Redirect` can be used anywhere to redirect the current `zesk\Response`
- Refactored `zesk\Locale` into `zesk\Locale\Module` and related classes

### Broken functionality

- `zesk\\Number::format_bytes` now requries `zesk\Locale` as the first parameter
- `zesk\\(Date|Timestapm|Time)::(format|formatting)`` now require `zesk\Locale` as the first parameter
- `zesk\Widget::locale()` now takes a `zesk\Locale` object to set and get the locale
- `zesk\Widget::__construct()` now uses the `locale` option to set up the locale object, if present
- `zesk\Locale::conjunction` for English languages now inserts the Oxford comma: "Lions, Tigers, and Bears"
- Changed hook name `Module_Apache::htaccess_alter` to `htaccess_alter`
- Removed `zesk\Controller_Template:$template` variable
- Removed deprecated function `zesk\HTML::inputAttributes()` deprecated 2016-12
- `ClassName::hooks` now takes `zesk\Application` as the first parameter Fixing old `zesk\Application` ORM references to use factory/registry calls in ORM module Reduced the usage of the `$zesk` global within Zesk Refactored `zesk\Router` file parsing to new class `zesk\Router\Parser` and now uses new `CacheItemPool` interfaces `zesk\Cache` is completely deprecated `zesk\System::host_id` and `zesk\System::uname` now are configured upon application configuration and are not based on `zesk\Configuration` settings during runtime. Both calls are now setters.
- `zesk\Application::set_cache`, `zesk\Application::setLocale` added, removing `zesk\Application::database_factory` and instead use `zesk\Database\Module` installed registry method

### Deprecated functionality

- `zesk\StringTools` is now `zesk\StringTools`
- `zesk\ArrayTools` is now `zesk\ArrayTools`
- `zesk\Database::(register|unregister|databases|factory|schemeFactory|register_scheme|valid_scheme|supportsScheme|database_default)` have been moved to `zesk\Module_Database`
- `_W` global function is deprecated, use `zesk\StringTools::wrap` instead
- Deprecated `zesk\Application::$zesk` variable to avoid using `zesk\Kernel` except in rare circumstances Added `zesk\Timestamp::json` call for output to JSON responses
- Deprecated `zesk\Database_Query_Select::object_iterator` and `zesk\Database_Query_Select::objects_iterator` and related calls to use new term `ORM`
- Removing `Response_Text_HTML`
- `_W()` deprecated, `->class_orm()` deprecated, `zesk\Module_Database` refactor, misc updates

### Removed functionality

- Removing `Content_Factory`
- `zesk\Database_Table::setIndexes` was removed.
- `zesk\Session` and `Session` globals are no longer honored. Use `zesk\Module_Session` to set session defaults.
- `zesk\Exception_Mail_Send` was removed (not used)
- `zesk\Exception_Mail_Format` was removed (not used)
- `zesk\Database_Index::columnCount` was removed (use `zesk\Database_Index::column_count`)
- `zesk\Database_Index::columnCount` was removed (use `zesk\Database_Index::column_count`)
- `zesk\Database::factory` was removed (use `$application->database_factory()`)
- `zesk\Response_Text_HTML::head` 
- `zesk\Response_Text_HTML::body_begin` 
- `zesk\Response_Text_HTML::body_end` 
- `zesk\Response_Text_HTML::head` (use template override to modify this)
- `zesk\Response_Text_HTML::doctype` (use template override to modify this)
- `zesk\Response_Text_HTML::cdn_...` all removed
- `zesk\Hookable::hook`
- `zesk\Hookable::hook_array`
- `zesk\ORM::cache_object`
- `zesk\ORM::cache_class`
- `zesk\ORM::class_primary_keys`
- `zesk\ORM::class_id_column`
- `zesk\ORM::query`
- `zesk\ORM::class_query`
- `zesk\ORM::class_query_delete`
- `zesk\ORM::class_query_update`
- `zesk\ORM::class_query_insert_select`
- `zesk\ORM::class_query_insert`
- `zesk\ORM::status_exists`
- `zesk\ORM::status_insert`
- `zesk\ORM::status_unknown`

### Miscellaneous Commit Messages

- Removing access to `$zesk`
- `_W()` moved to `zesk\StringTools::wrap()`
- `zesk\Cache` is now deprecated
- `zesk\Controller` should allow initialization without a `zesk\Request`
- `zesk\Database\Module` and related
- `zesk\Database_Query_Edit` supports new `->execute()` returning object now `->exec()` is now deprecated.
- `zesk\DocComment` refactored to use object and manage state and parsing
- `zesk\File::temporary` API changes
- `zesk\Locale` changes to support fewer global accessors, and `zesk\Locale` instance invocation
- `zesk\Locale` updates
- `zesk\Module::$classes` was deprecated, fixing in `zesk\Module_Content`
- `zesk\Module` `*.module.json` files now take a new configuration option `share_path_name` to use an alternate share path name than the module codename. (For versioning share resources, for example)
- `zesk\Response` API changes
- `zesk\Response` Refactored, `zesk\Application` state changes, `zesk\Locale` updates
- `zesk\Response` refactoring
- `zesk\Response` refactoring fixes
- `zesk\Response` serialization and caching fixes
- `zesk\Router` fixing `import` function
- `zesk\Router` removing dependency on `zesk\ORM`


### 1.0 Things Completed

- <strike>Renaming of `zesk\Object` to something non-reserved in PHP 7.2 (Candidates are `zesk\ORM` `zesk\Model` - reuse)</strike>
- <strike>`zesk\` namespace for all `classes` in the system</strike>
- <strike> Merging of `Response` and `Response_Text_HTML` into a single, unified polymorphic `Response` which changes behavior depending on content-type but allows typed API calls for specific response handling. May move `Response_Text_HTML` into a sub-object (e.g. `$response->html()->add_body_class()` for example)</strike>
- <strike>Support for `Psr/Cache` for caching within Zesk - **needs to be tested**</strike>
- <strike>Migrate `Database_Result_Iterator` to remove dependency on `Database_Query_Select_Base`</strike>
- <strike>Migration of `zesk\Locale` to be object-based and not-static-based invocation</strike>


## [v0.15.7][]

### Deprecated calls

- Deprecated `zesk\Database_Query_Select::object_iterator` and `zesk\Database_Query_Select::objects_iterator` and related calls to use new term `ORM`
 - `zesk\ORMIterator zesk\Database_Query_Select_Base::orm_iterator($class = null, array $options = array())`
 - `zesk\ORMSIterator zesk\Database_Query_Select_Base::orms_iterator($class = null, array $options = array())`
 - `zesk\Class_ORM zesk\Database_Query_Select_Base::class_orm()`
 - `string|self zesk\Database_Query_Select_Base::orm_class($class = null)`
- Updated `bin/deprecated/0.15.sh` to make above changes automatically

### Bugs fixed

- Fixing `zesk\Cleaner\Module` configuration errors when instance `lifetime` is `NULL`
- `zesk\Module_Permission` - Fixing cache saving to actually save it

## [v0.15.6][]

- Last version didn't pick up change in file for some reason, trying again.

## [v0.15.5][]

### Bugs fixed

- `zesk\Module_Permission` fixing cron task to properly recompute permissions if needed

## [v0.15.4][]

### Bugs fixed

Main changes were to fix a bug with login where the `zesk\User::hook_login` hook, if it returns NULL, would prevent authentication from succeeding. Modified how hooks are invoked such that when combining two results, the hook chain will choose the non-NULL value and will accumulate values as strings or arrays, depending on previous values.

- Fixing `zesk\User::authenticate()` to permit deferring authentication to application decision
- Fixing incompatible `zesk\Hookable::combine_hook_results` to enable hook chaining where hooks which return NULL are ignored.

## [v0.15.3][]

- `zesk\Command::render_format` now returns true or false
- `zesk\Control_Select::hide_single_text` option should be a string - fixes display of `select.tpl`
- `zesk\Module_Permission` fixing PSR API for caching of permissions, fixing `cache_disable` option

## [v0.15.2][]

- Fixing release date output and fixing date generation

## [v0.15.1][]

- Adding release date

## [v0.15.0][]

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

### `zesk\Locale` refactored

The `zesk\Locale` module has been refactored to support object-based management and avoiding `static` calls.

#### Static methods are now instance methods

- `zesk\Locale::loaded`

#### Removed

- `zesk\Locale::loaded`
- `ZESK_LOCALE_DEFAULT`

### New features

- `zesk\Application` now supports the function calls like `codename_module()` to retrieve module objects. So, `$application->csv_module()` is equivalent to `$application->modules->object("csv")`. You can decorate your application with `@method` doccomments to support types and return values in Eclipse-based editors. See `zesk\Application` class DocComment for examples.
- Added `Database::TABLE_INFO` constants and new `zesk\Database` class abstract `zesk\Database::tableInformation` call
- adding basic CacheItemPool and CacheItem classes

### Fixed bugs

- Fixing `zesk\Process_Tools::process_code_changed` calls in `zesk daemon`
- Fixed SES test file
- Fixing linked classes using `Foo::class` constant instead of strings
- Fixed some ORM minor issues

### Broken features

- `zesk\Locale` has been rewritten to avoid static calls and instead is an object within `zesk\Application`
- `zesk\File::temporary($path, $extension="tmp")` has been re-parameterized to remove global references, and now takes a path and a file extension. It has been updated in all Zesk internal code.

### Deprecated functionality

- Rewriting `cache` calls in `zesk\Object` to support `CacheItemInterface` instead of `zesk\Cache`
- `Cache::` removal
- removed `zesk()` globals
- Removing deprecated configuration path `zesk::paths::`

### Removed functionality

- `zesk\HTML::input_attributes` has been removed
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

- `modules/orm` added and all `Object` and `Class_ORM` functionality moved into there and renamed `ORM` and `Class_ORM` respectively.
- Refactored `Session` and moved to its own module. Likely will need future detachment from the `zesk\Application` class
- Refactored references to `ORM`-related classes and moved into `ORM` module (`User`, `Lock`, `Domain`, `Meta`, )
- References to `ORM` will be detached from zesk core in this and upcoming releases
- When possible `zesk\ORM` references are migrated to `zesk\Model` references instead

Due to the fact that `Database_Query` subclasses all depend on `ORM` and all `zesk\Widget` support `Database_Query_Select`, so all widgets have been moved to their own module `widget` which depends on `orm` as well.

### Application registry and factory calls

The `zesk\Application` is the center of your application, naturally, but it has evolved to the central point for object creation. To allow distrubution of the factory resposibilities, `zesk\Application` now allows modules (or anything) to register a central factory method. So:

	class MyInvoiceModule extends \zesk\Module {
		public function initialize(): void {
			// This adds the method "invoice_factory" to the zesk\Application
			$application->registerFactory("invoice", array($this, "invoice_factory"));
		}
		public function invoice_factory(Application $application, $code) {
			return new Invoice($application, $code);
		}
	}

There are a variety of new patterns, largely those which remove `ORM` functionality from the `zesk\Application` core.

The main point here is that shortcuts which previously pulled a bit of data from the `Class_ORM` (now `Class_ORM`) should use the full call, so:

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
- `zesk\Application::singleton` has been removed (use `::objectSingleton`)
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
- Enhanced `zesk\Object::json()` to support `class_info` and `members` options to modify JSON output
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
- `::inheritConfiguration` moved to `zesk\Hookable` and further expansion of requiring `$options` to be an array in constructors.
- `zesk\Application::application_root` is now `zesk\Application::path`
- `zesk\Application::application_root` renamed to `zesk\Application::path`
- `zesk\Hookable` now requires `$application` as first parameter to constructor
- `zesk\Object::factory` deprecated
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
- `::inheritConfiguration` moved to `zesk\Hookable` and further expansion of requiring `$options` to be an array in constructors.
- `zesk\Application::application_root` renamed to `zesk\Application::path`
- `zesk\Hookable` now requires `$application` as first parameter to constructor
- `zesk\Object::factory` deprecated
- `zesk\World_Bootstrap_Currency` now outputs missing currencies correctly
- adding `$application` to `Net_Client`
- quieter `zesk\Locale` shutdown
- reduce `_dump` verbosity
- `zesk\Module\SMS` work

## [v0.12.1][]

- Fixing `MySQL\Database_Parser` of `COLLATE` in tables to support `_` in names
- `zesk\Database::createDatabase` now contains `$hosts` and uses less assumptions
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
- `zesk\Database::columnDifferences` allow `zesk\Database` classes to affect how columns are compared.
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
- `zesk\Objects::singleton` now takes an object which, if matches, does not return an error. `zesk\Server` add default in for `ipv4_external`
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
- `zesk\Mail`, `zesk\Options::inheritConfiguration`, and `new Net_Foo` calls all now take `$application` as the first parameter.
- `zesk\Module_World` command `world-bootstrap` now properly creates bootstrap objects
- `zesk\Server` remove references to `$zesk`
- `zesk\Timestamp::parse` fix non-string values passed to `preg_match`
- `zesk\\Options::inheritConfiguration` now takes `$application` as first parameter.


## v0.10.12

- Fixing `zesk\Contact_Address_Parser` processing for `zesk\Contact_Address::store()`
- `Country::find_country` now takes `$application` as first parameter for context
- `Model::factory`, `ORM::factory`, and `Widget::factory` now support `$application->factory()` instead
- `zesk\Application::factory` is a shortcut to `$application->objects->factory()`
- `zesk\Database`: Removed references to global `$zesk`
- `zesk\Module_World` command `world-bootstrap` now properly creates bootstrap objects
- `zesk\\Options::inheritConfiguration` now takes `$application` as first parameter.

## v0.10.11

- `zesk\Configuration_Loader::load_one` does not emit error with file name which does not exist is passed in.
- Adding `Exception_Parameter` in `zesk\Objects::resolve`
- `zesk\Command`: Outputting head of backtrace in command exception
- Release v0.10.10
- Text::format_pairs uses JSON instead of PHP::dump
- `zesk\Class_ORM`: removed `$zesk` references
- `zesk\Database_Query`: Now supports application object directly.
- `zesk\Locale`: Removed instance of `global $zesk`
- `zesk\Lock`: Fixed exception typo
- `zesk\Module_Logger_File` removed some deprecated code
- `zesk\Module_ReactJS` added some debugging when something is not found
- `zesk\Object`: Removed unused `use`
- `zesk\Preference_Test` killed some gremlins
- `zesk\Process_Test_Tools`: Fixing uninitialized variable
- `zesk\StringTools_Test`: Removing legacy code
- `zesk\Application::all_classses` returns case-corrected class names
- `zesk\Command_Class_Check` and `zesk\Command_Class_Properties` now support all classes properly.
- `zesk\Control_Forgot` fixed referenced class to have `zesk\` namespace
- `zesk\Deploy`: 	Fixing logger invocation issue when deployment is already running
- `zesk\Text::head` and `zesk\Text::tail` methods similar to shell tools `head` and `tail` for strings with newlines

## v0.10.10

- `MySQL\Database`: `@@storage_engine` is deprecated https://github.com/pimcore/pimcore/issues/490
- Conditional debugging
- `zesk\Database_Parser`: Fixing `splitSQLStatements` to actually work
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

- Adding `zesk\Application::modelFactory` method
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

- `zesk\Application::applicationClass()` called old-style `zesk()->application_class`, now fixed


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
- `Session_ORM` removed deprecated global `$nosession`
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
- `Options::inheritConfiguration` now can inherit from passed in object (uses `get_class`)
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

- Fixing issue with `splitSQLStatements` which did not work for large strings due to limits to PREG backtracking in PHP7. Modified algorithm to use alternate parsing mechanism.
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
- Fixed an issue with `zesk\Options::inheritConfiguration` which incorrectly inherited global options containing a dash (`-`) and did not normalize them using `zesk\Options::_optionKey($key)` first.
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

- `zesk\Time::addUnit($n_units = 1, $units = self::UNIT_SECOND)` parameter order has been swapped to be more natural. The new syntax is `$time->addUnit(3, "minute")`. The old syntax (with `$units` first) will be supported for 6 months.
- `zesk\Date::addUnit($n_units = 1, $units = self::UNIT_DAY)` parameter order has been swapped to be more natural. The new syntax is `$date->addUnit(3, "day")`. The old syntax (with `$units` first) will be supported for 6 months.
- Fixed all references to `->addUnit()` and using UNIT constants
- Fixed issue with contact address editor (theme path missing `zesk` prefix)

## Version 0.9.9

- Fixed `zesk.sh` to better support `--cd` arguments and not rely on `cwd`
- Removed `_zesk_loader_.inc` in root, use `composer` instead for loading
- Deprecated `ZESK_APPLICATION_ROOT` constant to support better composer loading. Use `zesk()->paths->setApplication($path)` instead.
 - Global `application_root` at root `zesk\Configuration` is not longer set
- Added `zesk\PHP::parseNamespaceClass()` utility function
- Adding support for `psr/cache` compatibility and Adapters for zesk classes, so `zesk\Cache` will be deprecated, use `zesk()->cache` instead. It will be guaranteed to be set. Start using this instead of `Cache::` calls. Adapters in zesk are called `zesk\Adapter_CacheItem` and `zesk\Adapter_CachePool`.
- `zesk\DateInterval` was added for `\DateInterval` tools for conversion to/from seconds
- `zesk\Timestamp::UNIT_FOO` have been defined for all valid time units (second, minute, etc.)
- `zesk\Timestamp::addUnit($n_units = 1, $units = self::UNIT_SECOND)` parameter order has been swapped to be more natural. The new syntax is `$ts->addUnit(3, "minute")`. The old syntax (with `$units` first) will be supported for 6 months.
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

- Removed `\zesk\Object::__destruct` for performance improvements (see [this](https://stackoverflow.com/questions/2251113/should-i-use-unset-in-php-destruct))
- `zesk\Class_ORM->variables()` now returns a key 'primary_keys' with an array list of primary key names (member names)
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
- `ORM::permissions` call syntax has changed, return values should return `class::action` as keys, instead of just `action`. This is to prevent duplicate actions registered for child and parents (e.g. `User` and `zesk\User`). The name of the called method is only used as a hint in generating permission names now when the class is not supplied.
- Deprecated `User::instance` for `User::current` and related (`::instance_id`)
- Deprecated `Session::instance` for `Session::singleton`
- Deprecated `Request::instance` for `app()->request()`
- Deprecated `Response::instance` for `app()->response()`
- Deprecated the use of the static "instance" call for singletons created via `zesk()->objects->singleton()` (still works, but calls deprecated callback)
- Moved `Session_Database` to `zesk\Session_Database`
- Expanded the use of cookies via the Request and Response objects, removed this from `Session_ORM` implementation
- Removed all usage of `User::instance` and `Session::instance` from zesk
- Moved `Session_ORM` to `zesk\Sesssion_Database`
- Refactored `zesk\Session_ORM` and migrated globals into `zesk\Application`
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
- Deprecated the following `zesk\Object` calls (use `app()->object()` and `app()->class_orm()` to access)
 - `zesk\Object::class_table_name()`
 - `zesk\Object::class_idColumn()`
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
- function `zesk\StringTools::matches` removed
- function `zesk\StringTools::cexplode` removed
- function `zesk\StringTools::explode_chars` removed
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

[v0.30.2]: https://github.com/zesk/zesk/compare/v0.30.1...v0.30.2
[v0.30.1]: https://github.com/zesk/zesk/compare/v0.30.0...v0.30.1
[v0.30.0]: https://github.com/zesk/zesk/compare/v0.29.0...v0.30.0
[v0.29.0]: https://github.com/zesk/zesk/compare/v0.28.5...v0.29.0
[v0.28.5]: https://github.com/zesk/zesk/compare/v0.28.3...v0.28.5
[v0.28.3]: https://github.com/zesk/zesk/compare/v0.28.1...v0.28.3
[v0.28.1]: https://github.com/zesk/zesk/compare/v0.28.0...v0.28.1
[v0.28.0]: https://github.com/zesk/zesk/compare/v0.27.1...v0.28.0
[v0.27.1]: https://github.com/zesk/zesk/compare/v0.27.0...v0.27.1
[v0.27.0]: https://github.com/zesk/zesk/compare/v0.26.2...v0.27.0
[v0.26.2]: https://github.com/zesk/zesk/compare/v0.26.1...v0.26.2
[v0.26.1]: https://github.com/zesk/zesk/compare/v0.26.0...v0.26.1
[v0.26.0]: https://github.com/zesk/zesk/compare/v0.25.2...v0.26.0
[v0.25.2]: https://github.com/zesk/zesk/compare/v0.25.1...v0.25.2
[v0.25.1]: https://github.com/zesk/zesk/compare/v0.25.0...v0.25.1
[v0.25.0]: https://github.com/zesk/zesk/compare/v0.24.0...v0.25.0
[v0.24.0]: https://github.com/zesk/zesk/compare/v0.23.5...v0.24.0
[v0.23.5]: https://github.com/zesk/zesk/compare/v0.23.4...v0.23.5
[v0.23.4]: https://github.com/zesk/zesk/compare/v0.23.3...v0.23.4
[v0.23.3]: https://github.com/zesk/zesk/compare/v0.23.2...v0.23.3
[v0.23.2]: https://github.com/zesk/zesk/compare/v0.23.1...v0.23.2
[v0.23.1]: https://github.com/zesk/zesk/compare/v0.23.0...v0.23.1
[v0.23.0]: https://github.com/zesk/zesk/compare/v0.22.0...v0.23.0
[v0.22.0]: https://github.com/zesk/zesk/compare/v0.21.4...v0.22.0
[v0.21.4]: https://github.com/zesk/zesk/compare/v0.21.3...v0.21.4
[v0.21.3]: https://github.com/zesk/zesk/compare/v0.21.2...v0.21.3
[v0.21.2]: https://github.com/zesk/zesk/compare/v0.21.1...v0.21.2
[v0.21.1]: https://github.com/zesk/zesk/compare/v0.21.0...v0.21.1
[v0.21.0]: https://github.com/zesk/zesk/compare/v0.20.1...v0.21.0
[v0.20.1]: https://github.com/zesk/zesk/compare/v0.20.0...v0.20.1
[v0.20.0]: https://github.com/zesk/zesk/compare/v0.19.1...v0.20.0
[v0.19.1]: https://github.com/zesk/zesk/compare/v0.19.0...v0.19.1
[v0.19.0]: https://github.com/zesk/zesk/compare/v0.18.1...v0.19.0
[v0.18.1]: https://github.com/zesk/zesk/compare/v0.18.0...v0.18.1
[v0.18.0]: https://github.com/zesk/zesk/compare/v0.17.0...v0.18.0
[v0.17.0]: https://github.com/zesk/zesk/compare/v0.16.5...v0.17.0
[v0.16.5]: https://github.com/zesk/zesk/compare/v0.16.4...v0.16.5
[v0.16.4]: https://github.com/zesk/zesk/compare/v0.16.3...v0.16.4
[v0.16.3]: https://github.com/zesk/zesk/compare/v0.16.2...v0.16.3
[v0.16.2]: https://github.com/zesk/zesk/compare/v0.16.1...v0.16.2
[v0.16.1]: https://github.com/zesk/zesk/compare/v0.16.0...v0.16.1
[v0.16.0]: https://github.com/zesk/zesk/compare/v0.15.7...v0.16.0
[v0.15.7]: https://github.com/zesk/zesk/compare/v0.15.6...v0.15.7
[v0.15.6]: https://github.com/zesk/zesk/compare/v0.15.5...v0.15.6
[v0.15.5]: https://github.com/zesk/zesk/compare/v0.15.4...v0.15.5
[v0.15.4]: https://github.com/zesk/zesk/compare/v0.15.3...v0.15.4
[v0.15.3]: https://github.com/zesk/zesk/compare/v0.15.2...v0.15.3
[v0.15.2]: https://github.com/zesk/zesk/compare/v0.15.1...v0.15.2
[v0.15.1]: https://github.com/zesk/zesk/compare/v0.15.0...v0.15.1
[v0.15.0]: https://github.com/zesk/zesk/compare/v0.14.4...v0.15.0
[v0.14.4]: https://github.com/zesk/zesk/compare/v0.14.3...v0.14.4
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
