## Release {version}

- **Cron Module**: Adding `--last` to options to better debug production issues
- **Cron Module**: Change in semantics for function prefixes: `cron_server` runs per-server, `cron` runs per application and per-server, and `cron_cluster` runs per application on a single server.
- **Cron Module**: Correctly prefix clusters to support multiple applications per-cluster
- **Cron Module**: Fixing issue with cluster name keys
- **Cron Module**: `zesk cron --last` now shows a pretty table
- **MySQL Module**: PHP 5.5+ support for timestamp
- **ORM Module**: Capture database not found exception in Settings load to allow misconfigured app to still run.
- **ORM Module**: Minor fix to documentation
- **ORM Module**: `zesk\Class_ORM::link_many` now throws `zesk\Exception_Key` instead of `zesk\Exception_Semantics` to disambiguate error codes as well as to support catching already-added linkages. Added support to **Permission Module**.
- **Polyglot Module**: `zesk\Controller_Polyglot::action_update` explicit route added; no longer covered by catch-all `controller/action/param` global route.
- **Zesk Kernel**: Adding `zesk\Application->id()` to allow application instance cron tasks
- **Zesk Kernel**: Improved application reconfigure
- **Zesk Kernel**: Improved comments for `zesk\Autoloader`
- **Zesk Kernel**: Modules reload method added
- **Zesk Kernel**: Router fixing issue with intermittent quoting in route compilation
- **Zesk Kernel**: `zesk\Controller_Share` now supports `build_directory()` and configurable prefix.
- **Zesk Kernel**: `zesk\Route_Controller` throws `zesk\Exception_Invalid` on `get_route_map` if the pattern and URL do not match for some reason.
- **Zesk Kernel**: Fixed *PHP Notice*:  Trying to access array offset on value of type int in `zesk/modules/database/classes/Database/SQL.php` on line 472
- **Zesk Kernel**: New countries


<!-- Generated automatically by release-zesk.sh, beware editing! -->
