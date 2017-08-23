## Release {version}

- Removed usage of global state
 - `Application::instance` removal across the system, reducing in usage of `zesk()`
 - Pass `$application` around instead of using globals, require context for all __construct calls, moved class cache to `Application`
 - Remove references to `app()` and `zesk()` where possible
 - removing global references and restructuring function calls to remove global access
- Test suites
 - Adding `zesk\Logger::dump_config` to debug logging configuration
 - Call to allow changing index type of `zesk\Database_Index`
 - Better error messages for `zesk\Database_Execption_Connect`
 - Major test suite updates, support for fewer references to `app()` global
- Schema
 - Schema: Fixing issue with modifying a primary column and making sure the index isn't added redundantly
 - `zesk\Database_Column`: Ensure `sql_type` is normalized to lowercase
 - `zesk\Database_Table`: Adding `table_attributes_is_similar` and fixing bug with table type changing to database default
- Miscellaneous
 - Updated `zesk help` docs for help and `.php` extension
 - `XMLRPC` namespace and fixing classes and tests
 - `zesk\Command_Base`: Support subclasses which do not run `zesk\Application::configured` hooks
 - `zesk\User`: moving deprecated functions to bottom, moving global state out of `User`
- Modules
 - Moved `ipban` to its own repository
 - Moved `zest` to its own repository


<!-- Generated automatically by release-zesk.sh, beware editing! -->
