## Release {version}

- Adding back in `zesk\Application::application_class`
- Changes to allow subclasses to extend indexes. Probably should use `zesk\Schema` or something instead
- Merge branch 'master' of https://github.com/zesk/zesk into rm-global-zesk
- Providing access to `zesk_weight()`
- Removal of `zesk()` calls
- Reset all whitespace and formatting
- removing `zesk()` references
- `zesk\Application::schema_synchronize()` adding support for `follow` options which synchronizes all objects and dependent objects.
- `zesk\Application`: Removed `die()` and using `$logger->emergency()` instead
- `zesk\Database::column_differences` allow `zesk\Database` classes to affect how columns are compared.
- `zesk\Request::file_migrate` is now `zesk\Request\File::instance($upload_array)->process(...)`
- `zesk\World_Bootstrap_Foo` fixes for factory and `bootstrap` to remove globals
- `zesk_sort_weight_array` renamed from `zesk\Kernel::sort_weight_array`
- removed `zesk\\Kernel::sort_weight_array` and moved to `functions.php`


<!-- Generated automatically by release-zesk.sh, beware editing! -->
