## Release {version}

- `zesk\Configuration_Loader::load_one` does not emit error with file name which does not exist is passed in.
- Adding `Exception_Parameter` in `zesk\Objects::resolve`
- `zesk\Command`: Outputting head of backtrace in command exception
- Release v0.10.10
- Text::format_pairs uses JSON instead of PHP::dump
- `zesk\Class_Object`: removed `$zesk` references
- `zesk\Database_Query`: Now supports application object directly.
- `zesk\Locale`: Removed instance of `global $zesk`
- `zesk\Lock`: Fixed exception typo
- `zesk\Module_Logger_File` removed some deprecated code
- `zesk\Module_ReactJS` added some debugging when something is not found
- `zesk\Object`: Removed unused `use`
- `zesk\Preference_Test` killed some gremlins
- `zesk\Process_Test_Tools`: Fixing uninitialized variable
- `zesk\str_Test`: Removing legacy code
- `zesk\Application::all_classses` returns case-corrected class names
- `zesk\Command_Class_Check` and `zesk\Command_Class_Properties` now support all classes properly.
- `zesk\Control_Forgot` fixed referenced class to have `zesk\` namespace
- `zesk\Deploy`: 	Fixing logger invocation issue when deployment is already running
- `zesk\Text::head` and `zesk\Text::tail` methods similar to shell tools `head` and `tail` for strings with newlines

<!-- Generated automatically by release-zesk.sh, beware editing! -->
