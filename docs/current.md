## Release {version}

- Zesk now only supports PHP version 5.5 and higher as we use `ClassName::class`
- Static permissions check fix added
- `zesk\Cleaner\Module::directories::cleaner_name::lifetime` Now supports conversion of time units, e.g. "2 hours", "4 days", "2 weeks". It uses `strtotime` relative to the current time to determine the time period.
- adding JSON examples for SNS
- adding `zesk\TimeSpan`
- Fixing a variety of issues in the Markdown, OpenLayers, and release scripts

<!-- Generated automatically by release-zesk.sh, beware editing! -->
