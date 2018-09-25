## Release {version}

- `ARRAY_FILTER_USE_BOTH` not compatible with PHP5, removing references to it
- Fixed an issue in default `zesk\CacheItemPool_Array` where cache items were not returned on second call.
- Fixing `zesk\System::ifconfig` caching, allowing configuration setting `zesk\System::ifconfig::expires_after`, defaults to 60 (seconds)
- Upgraded `$locale->__` usage in `Polyglot` module
- `zesk\Daemon\Module` fixed a type hint
- `zesk\Route::factory` now accepts options of `file` to force a route of `zesk\Route_Content` which serves the file directly.
- `zesk\Route_Content` extended to support `file` option to serve files directly
- `zesk\Server` no longer populates `ip4_external` and `name_external` if not set; they remain empty if not set by any other method. `ip4_internal` and `name_internal` are taken from the first `zesk\System::ip_addresses` which is not localhost, or `127.0.0.1`.
- fixing server awareness callback and `zesk\Server::` config options

<!-- Generated automatically by release-zesk.sh, beware editing! -->
