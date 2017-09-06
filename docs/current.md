## Release {version}

- `MySQL\Database`: `@@storage_engine` is deprecated https://github.com/pimcore/pimcore/issues/490
- Conditional debugging
- `zesk\Database_Parser`: Fixing `split_sql_commands` to actually work
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

<!-- Generated automatically by release-zesk.sh, beware editing! -->
