## Zesk Version {version}

- Fixed `zesk.sh` to better support `--cd` arguments and not rely on `cwd`
- Removed `_zesk_loader_.inc` in root, use `composer` instead for loading
- Deprecated `ZESK_APPLICATION_ROOT` constant to support better composer loading. Use `zesk()->paths->set_application($path)` instead.
 - Global `application_root` at root `zesk\Configuration` is not longer set
- Added `zesk\PHP::parse_namespace_class()` utility function
- Adding support for `psr/cache` compatibility and Adapters for zesk classes, so `zesk\Cache` will be deprecated, use `zesk()->cache` instead. It will be guaranteed to be set. Start using this instead of `Cache::` calls. Adapters in zesk are called `zesk\Adapter_CacheItem` and `zesk\Adapter_CachePool`.
- `zesk\Timestamp` now supports the `DateTimeInterface` interface in PHP
- `zesk\DateInterval` was added for `\DateInterval` tools for conversion to/from seconds
- `zesk\Timestamp::UNIT_FOO` have been defined for all valid time units (second, minute, etc.)
- `zesk\Timestamp::add_unit($n_units = 1, $units = self::UNIT_SECOND)` parameter order has been swapped to be more natural. The new syntax is `$ts->add_unit(3, "minute")`. The old syntax (with `$units` first) will be supported for 6 months.
- Removed `ZESK_ROOT/classes-stubs` from default autoloader.