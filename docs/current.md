## Release {version}

- **Zesk Kernel**: `zesk\Application` now loads `etc/maintenance.json` (or `$app->maintenance_file()`) upon creation, and places values in `$app->option('maintenance')` which is an array.
- Revising `zesk maintenance` to set simpler key in maintenance structure

<!-- Generated automatically by release-zesk.sh, beware editing! -->
