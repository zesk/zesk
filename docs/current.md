## Release {version}

- **ORM Module**: adding warning for ORM object members which are has_one and `column_types[member] !== object`
- Added support for `zesk\Route` standard option `react` to spit out React template page (except for JSON calls)
- Added support for Slovak language
- Adding PHP Coding Style support and integration with git pre-commit hook
- Adding `zesk\Database::shell_command` valid options and usage in dump command
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
- `zesk\Database::shell_command` now supports option `sql-dump-command` boolean value
- `zesk\Route` added `OPTION_` constants for standard options
- `zesk\Server` added remote_ip field and accessor
- adding `bin/daemon-bouncer.sh` to zesk core scripts
- adding debug logging to `zesk command` - `subversion` subcommand
- adding remote_ip to `zesk\Request`
- added `bin/hooks/pre-commit` to share client-side hooks

<!-- Generated automatically by release-zesk.sh, beware editing! -->
