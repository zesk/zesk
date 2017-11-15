## Release {version}

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

<!-- Generated automatically by release-zesk.sh, beware editing! -->
