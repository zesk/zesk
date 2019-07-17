## Release {version}

- **CSV Module**: Remove warning in PHP 7.2 to avoid `count(null)`
- **Content Module**: Adding some `::class` constants instead of strings
- **Content Module**: Image `view` theme adds `data-src` attribute which messes up `unveil` module. So renamed attribute to `data-original` instead.
- **Image Picker Module**: Fixes for updated version of Zesk
- **Image Picker Module**: Fixing theme path
- **Image Picker Module**: Permit the setting of a `zesk\Content_Image` to `zesk\User` link path for queries
- **Job module**: Progress always updates the database
- **ORM Module**: Added support for `HAVING` clauses in `zesk\Database_Query_Select`
- **ORM Module**: Better support for object class aliases in linkages between classes (select query `->link` calls)
- **ORM Module**: Ensured `delete` actions redirect to `/` if no URL set
- **ORM Module**: Fixing  to translate using alternate locales
- **ORM Module**: `_action_default` getting called when `->user` is null, causing an error. Now throws an `Exception_Authenticated` instead.
- **ORM Module**: `zesk\ORM::clean_code_name` now supports `-` and `_` as valid characters in strings (previously just `-` was permitted)
- **ORM Module**: `zesk\Session_ORM::one_time_create` fixed a crashing bug
- **Picker Module**: Some updates for locale changes.
- **PolyGlot Module**: Updating translate page and default, fixing locale issues.
- **Selenium Module**: Getting running again
- **Snapshot Module**: fixing format bug in `Timestamp`
- **Workflow Module**: `zesk\Workflow_Step->is_completed()` is now abstract, and the default function `substeps_completed()` now returns 0 or 1 by calling `is_completed()`.
- **Zesk Kernel**: Do not store identically IDed objects when saving recursively constructed objects
- **Zesk Kernel**: Fix incorrect warnings about depth when exception occurs in `zesk\Application` main
- **Zesk Kernel**: Fixed warning in `zesk\Application` re: `$starting_depth`
- **Zesk Kernel**: Rearranged `zesk\Autoloader` so constants are at top
- **Zesk Kernel**: Theme `bytes` now correctly uses the current locale
- **Zesk Kernel**: `actions` template now correctly outputs `ref`
- **Zesk Kernel**: `zesk database-dump` adding non-blocking to database dump command
- **Zesk Kernel**: `zesk\Controller_Theme::after` only runs `auto_render` when it is HTML output.
- **Zesk Kernel**: `zesk\JSON::prepare` now takes optional arguments parameter for serializing JSON objects and passing arguments to serializer functions
- **Zesk Kernel**: `zesk\Request->url_variables()` no longer throws `zesk\Exception_Key` - returns a default value if key not found
- **Zesk Kernel**: `zesk\Response\JSON` now supports `zesk\JSON::prepare` arguments
- **Zesk Kernel**: `zesk\Response` now logs a debug message when the `output_handler` is modified to help debug issues with responses.
- **Zesk Kernel**: adding missing forgot template

<!-- Generated automatically by release-zesk.sh, beware editing! -->
