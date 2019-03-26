## Release {version}

- **Content Module**: `zesk\Content_Data::copy_file` now throws `zesk\Exception_File_NotFound` if unable to find file locally, minor fix.
- **Content Module**: `zesk\Content_Image::_force_to_disk()` now catches `zesk\Exception_Not_Found` and handles it correctly.
- **MySQL Database**: Fixing `database-dump` command to properly order parameters such that `--defaults-extra-file` is first in parameter list
- **ORM Module**: Default generation of polymorphic class name upon load does not lowercase the class anymore.
- **ORM Module**: `zesk\Database_Query_Select_Base::one_timestamp` is now identical to `::timestamp`
- **ORM Module**: `zesk\Server` now insists that `alive` is a `zesk\Timestamp` before checking
- **PolyGlot Module**: Renamed `zesk\PolyGlot_Token::to_json` to `::json`, fixed `zesk\Controller_PolyGlot::action_load` cannon error
- **WebApp Module**: Adding `action_health` to controller to return 200 on alive and non-200 on "down" - used by load balancers to determine if a target is healthy.
- **WebApp Module**: Adding `zesk\WebApp\Module::control_file` hook, adding scan debugger progress
- **WebApp Module**: Adding health support to controller
- **WebApp Module**: Adding support for dry run later, fixing no write issue
- **WebApp Module**: Authentication is now required for most of `zesk\WebApp\Controller` actions (using a shared key `zesk\WebApp\Module::key`), added support for `hostnames` key in `webapp.json` to force host names in rendered configuration files. Added `zesk webapp-api` call to invoke things via command line. Any `zesk\Server` associated with `zesk\WebApp\Module` will add a `zesk\Server_Data` record with the name `zesk\WebApp\Module` and a value of 1. Added `zesk\WebApp\Module->server_actions($action)` to message each server serially and return the results.
- **WebApp Module**: Fixed generator so empty files are deleted. Passing all extraneous data (`zesk\WebApp\Generator_Apache`-specific) through to templates, allowing for overrides. Set default `indexes` to `['index.php','index.html']`. Adding support for serving `/public/` instead of `/build/` for development systems, and only rendering vhost files for web apps assigned at least one host name.
- **WebApp Module**: Require `git` and `subversion` module
- **WebApp Module**: `zesk webapp-api` corrected category and added basic description.
- **WebApp Module**: `zesk\WebApp\Generator->replace_file` now optionally supports saving previous version
- **WebApp Module**: `zesk\WebApp\Module->instance_factory(true)` now refreshes `appversion` for each instance, then refreshes the repo versions for each instance.
- **WebApp Module**: `zesk\WebApp\Type\Zesk` should use `zesk.sh` instead of `zesk-command.php` - doesn't work otherwise
- **Widget Module**: `zesk\Control_Duration` now properly supports ranges greater than 24 hours
- **Zesk Kernel**: Added `zesk\JSON::prepare` to flatten complex objects using object methods
- **Zesk Kernel**: Fixing issue with `zesk latest` where `git clone` fails then kills environment.
- **Zesk Kernel**: Fixing warning in `zesk\Response\Text`
- **Zesk Kernel**: `zesk\PHP::feature` now has associated `zesk\PHP::FEATURE_FOO` constants.
- **Zesk Kernel**: `daemon-bouncer.sh` renamed to `file-trigger.sh` which reflects better what it does. Old binary available for 6 months.
- **Zesk Kernel**: `zesk configure` Handling non-interactive flag
- **Zesk Kernel**: `zesk\Command_Configure` -> `zesk\Configure\Engine` particularly for hooks and extending the configure engine.
- **Zesk Kernel**: `zesk\Command` now correctly outputs class name in errors
- **Zesk Kernel**: `zesk\Configure\Engine` fixing prompt for files differ
- **Zesk Kernel**: `zesk\Deploy` now catches all `\Exception`s
- **Zesk Kernel**: `zesk\Directory::delete` now throws a permission error if it is unable to delete the final directory
- **Zesk Kernel**: `zesk\Image_Libary_GD` now returns a more specific string in the `zesk\Exception_Semantics` containing the file name when unable to create an image from a file.
- **Zesk Kernel**: `zesk\Locale` now supports durations up to millenium for `->now_string(...)`
- **Zesk Kernel**: `zesk\Request` supports `multipart/form-data` posts by pulling in raw PHP `$_REQUEST`.
- **Zesk Kernel**: `zesk\Response\JSON` calls `zesk\JSON::prepare` on output content before converting to JSON
- **Zesk Kernel**: `zesk\Text` Added additional documentation for functions
- **Zesk Kernel**: `zesk\TimeSpan` added proper and more comprehensive formatting
- **Zesk Theme**: `link` theme supports boolean `allow_javascript` = false, boolean `auto_prepend_scheme` = false
- **Zesk Theme**: `link` theme now, by default, ignores `javascript:` links, and allows for URL parts replacement of the text value (so you can use `{host}` as a text value to output the URL host)


<!-- Generated automatically by release-zesk.sh, beware editing! -->
