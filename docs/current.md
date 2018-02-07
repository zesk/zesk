## Release {version}

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
- Now deprecated `$application->zesk_root()` - renamed to `$application->zesk_home()` `zesk\Locale_Default` is now as generic as it can be in generating default, generic values. Fixing various `zesk\(Date|Time|Timestamp)::format` calls which need to take `zesk\Locale` as first parameter. `zesk\Contact_Address::en_lang_member_names()` added, `::lang_member_names()` now requires `zesk\Locale` parameter. Converting `__("phrase")` to `$locale->__("phrase")` `zesk\View_OrderBy` fixing now-overridden local template variable `$url` which is now set in the `$application` level.
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
- Removed deprecated function `zesk\HTML::input_attributes()` deprecated 2016-12
- `ClassName::hooks` now takes `zesk\Application` as the first parameter Fixing old `zesk\Application` ORM references to use factory/registry calls in ORM module Reduced the usage of the `$zesk` global within Zesk Refactored `zesk\Router` file parsing to new class `zesk\Router\Parser` and now uses new `CacheItemPool` interfaces `zesk\Cache` is completely deprecated `zesk\System::host_id` and `zesk\System::uname` now are configured upon application configuration and are not based on `zesk\Configuration` settings during runtime. Both calls are now setters.
- `zesk\Application::set_cache`, `zesk\Application::set_locale` added, removing `zesk\Application::database_factory` and instead use `zesk\Database\Module` installed registry method

### Deprecated functionality

- `zesk\StringTools` is now `zesk\StringTools`
- `zesk\ArrayTools` is now `zesk\ArrayTools`
- `zesk\Database::(register|unregister|databases|factory|scheme_factory|register_scheme|valid_scheme|supports_scheme|database_default)` have been moved to `zesk\Module_Database`
- `_W` global function is deprecated, use `zesk\StringTools::wrap` instead
- Deprecated `zesk\Application::$zesk` variable to avoid using `zesk\Kernel` except in rare circumstances Added `zesk\Timestamp::json` call for output to JSON responses
- Deprecated `zesk\Database_Query_Select::object_iterator` and `zesk\Database_Query_Select::objects_iterator` and related calls to use new term `ORM`
- Removing `Response_Text_HTML`
- `_W()` deprecated, `->class_object()` deprecated, `zesk\Module_Database` refactor, misc updates

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


<!-- Generated automatically by release-zesk.sh, beware editing! -->
