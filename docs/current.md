## Release {version}

- **DEPRECATED**: `zesk\Command_Loader` no longer supports applications which have their core class named `*.application.inc`
- **ORM Module** added `zesk\ORM->raw_member()` to allow retrieval of a member avoiding `zesk\ORM->refresh()`
- **ORM Module**: Removed a duplicate line in `zesk\Database_Query_Select` duplication
- Moved application configuration into new protected method in `zesk\Command->application_configure()` to be called at start of `zesk\Command->go()` call, moved over from `zesk\Command_Loader.php` as it should be internal, not external.
- `zesk\Command` fixed debug message when configuration missing
- `zesk\ORM::member_model_factory` was performing an extra `->fetch()` of each object when a `->refresh()` was all that is needed (maybe not?). `zesk\ORM::refresh()` now returns self

<!-- Generated automatically by release-zesk.sh, beware editing! -->
