## Release {version}

### Core issues

- Added `Directory::must` to require a directory to exist
- Adding `$context` to `zesk\Exception_File_NotFound` to give ... context to exception
- Adding lots of comments to `zesk\Net_HTTP`
- Fixing no locks to delete
- Fixing zesk app when composer not run
- Removing map variables which are no longer valid after evaluation
- Support preprocessing fixed variables and applying when generating routes
- `zesk\Command_Loader` fixed output warning to used correct extension
- Exceptions should return an error
- **Cache**: Removed warnings from `zesk\CacheItemPool_File`

### Misc Module fixes

- **Markdown Module**: Adding `zesk markdown` to markdown module command path
- **Markdown Module**: fixing double process output
- **Moment Module**: Correctly output `$locale->id()` for debugging
- **Polyglot module**: Enhancements and fixes for dup list count
- **Widget Module**: Remove warnings to `zesk\Control_Checklist` when iterating over keys
- **Widget module**: `zesk\Widget->widget_factory()` now inherits response from called `zesk\Widget`

### ORM Module

- Adding proper inherit options so options-based tables operate correctly in queries and iterators
- Better error handling in `zesk\Image_Library_GD`, better exception throwing
- Support `zesk\ORM::members()` returning "extra" members

### Picker/Image Picker Module

- **Image Picker Module**: Fixing controller paths, using ::class
- **Picker Module**: `Module_Picker` psr4 updates
- Fixing picker JSON for renaming

## ReactJS Module

- Default host/port for proxy
- handling build via `asset_manifest.json`
- Adding `dot_env_path` to `ReactJS` module

<!-- Generated automatically by release-zesk.sh, beware editing! -->
