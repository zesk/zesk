## Release {version}

- **Cron Module**: Fixing upgraded `zesk\Locale` API calls.
- **Help Module**: Fixing `zesk\Locale` API calls.
- **ORM Module**: `zesk\ORM->member_model_factory()` calling `->refresh` creates infinite loops. Removing `->refresh()` call.
- **Server Module**: Fixing `zesk\Controller_DNS` to correctly link to `zesk\Diff\Lines`
- **Zesk Kernel**: `zesk\Route\Theme` supports HTML to JSON output better.


<!-- Generated automatically by release-zesk.sh, beware editing! -->
