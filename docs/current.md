## Release {version}

- `zesk\Widget::initialize` now throws an `Exception_Semantics` if called without a `zesk\Response` set up in the widget for 
- `zesk\Command_Configure` enhanced ability to compare and skip identical files
- `daemon` layout panel was added
- `daemon` system status was added
- ensure one newline at EOF for `zesk configure` file command `crontab` to avoid re-updates
- `zesk\Cron\Module` runs every cron, not every minute

<!-- Generated automatically by release-zesk.sh, beware editing! -->
