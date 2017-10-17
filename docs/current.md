## Release {version}

- Fixing `zesk\Database_SQL::function_max` and `zesk\Database_SQL::function_min`
- Fixing and removing references to `zesk()` and `global $zesk`
- Handle default `zesk\Preference`s better
- Passing `null` instead of `false` to some `zesk\Database::query` functions
- Use share path for `theme/body/exception`
- `::inherit_global_options` moved to `zesk\Hookable` and further expansion of requiring `$options` to be an array in constructors.
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


<!-- Generated automatically by release-zesk.sh, beware editing! -->
