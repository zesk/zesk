## Release {version}

- Added `zesk\Paths::expand($file)` which allows for shortcuts for relative paths `["./foo", "~/foo"]` are special Added `zesk\Paths::expand` functionality to application `::include_files()` (NOT to `include_paths`, which is deprecated!) Added `zesk\Paths::expand` functionality to configure core commands. Must be added to individual command implementations. ReactJS better error handling for content types Added `zesk latest` to perform latest updates for `zesk` bleeding edge
- Adding command `zesk latest` to automatically update zesk to the latest GitHub. For zesk core development only, or if you need bleeding-edge features and lots of volatility. Refactored Help module to use PSR4 paths
- `zesk\Request` fixes for controls, test fixes
- PSR-4 for `Help` module

### Bugs fixed

- fixing `zesk\Controller_Forgot` and updating code semantics
- fixing infinite loop in `zesk\ORM_CacheItem`
- `zesk\Database_Parser` fixing API
- `zesk\Database` fixing supports scheme API (used to use `Module`, should use `Database`)
- Adding internal versions for modules using method

<!-- Generated automatically by release-zesk.sh, beware editing! -->
