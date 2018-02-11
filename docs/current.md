## Release {version}

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

<!-- Generated automatically by release-zesk.sh, beware editing! -->
