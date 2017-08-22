## Release {version}

- Allow reinitialization of app, fixing up reset. Note that `zesk()->reset()` is not production-ready yet, and may possibly be removed in a future version. Likely add an "Application"-level reset instead and will migrate any values from `zesk()` global to Application level if necessary.
- Deprecated module variable `$classes`
- Fixing class names for world bootstrap
- `Options::inherit_global_options` now can inherit from passed in object (uses `get_class`)
- Adding back in `update.conf` to `zesk update` command
