## Release {version}

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
- `zesk\Objects->singleton_arguments()` and related calls no longer support the usage of the static `instance` method for class creation. You should have a static methods called `singleton` to take advantage of `zesk\Objects` global registration for singletons.
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
- `zesk\Lock::get_lock` was removed
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
