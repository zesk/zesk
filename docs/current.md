## Zesk Version {version}

- Removed `User` deprecation to `zesk\User` - may not necessarily be the case
- Fixed an issue with `zesk\Options::inherit_global_options` which incorrectly inherited global options containing a dash (`-`) and did not normalize them using `zesk\Options::_option_key($key)` first.
- Fixing an issue with `Database` auto table names options not being passed through when 1st parameter is an array
