## Release {version}

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

<!-- Generated automatically by release-zesk.sh, beware editing! -->
