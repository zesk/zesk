## Release {version}

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

- `Controller_Forgot`, `Controller_Object`, ``, `` and `` all now inherit from `Controller_Theme`
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


<!-- Generated automatically by release-zesk.sh, beware editing! -->
