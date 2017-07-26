## Release {version}

- Fixing issue with `split_sql_commands` which did not work for large strings due to limits to PREG backtracking in PHP7. Modified algorithm to use alternate parsing mechanism.
- Release v0.9.24
- `zesk\Request::default_request` may read `zesk\Request::data()` and initialize it, so needs to have object (mostly) initialized before calling.
