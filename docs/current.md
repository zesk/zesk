## Release {version}

Version added to support modern MySQL docker containers enforcement of `Timestamp` defaults:

- **MySQL Module**: `blob` and `text` data types no longer forced to have a default of blank string `""` - instead you must specify this in your SQL if the version of MySQL allows it.
- **Session Module**: Remove invalid `timestamp` default 0 values for MySQL

<!-- Generated automatically by release-zesk.sh, beware editing! -->
