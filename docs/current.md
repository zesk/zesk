## Release {version}

- Adding maintenance tag as default version `1.2.{maintenance}.0`
- Controls related to `Database_Query_Select` Avoids `query_column` warnings that field didn't modify where clause
- Fix `Contact_Tag` and `Contact` linkage by adding intermediate table
- Widgets: Fix unlikely code structure issue with `_exec_render` to avoid uninitialized variable and double unwrap
- Updated docs in `Control_Select`
- Support for `Control_Select::is_single()`
- `zesk\Directory::list_recursive`: if `opendir` fails on a directory, return an array instead of false
- `Controller_Content_Cache` fixing issue with `Content_Image` data being `null`
- Updating the `version` command to support custom version layouts properly
