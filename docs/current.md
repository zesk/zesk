## Release {version}

- **Bootstrap DateTimePicker**: Adding support for range selection
- **ORM Module**: Deprecated call removal, support for primary keys in default output
- **Tag Module**: Added `zesk\Tag\Control_Tags` debugging, an label `name_column` set in `zesk\Tag\Class_Label`
- **Tag Module**: Improvements
- **Zesk Kernel**: Fixed issue with `zesk\Command` where defaults set up in `initialize` were not honored.
- **Zesk Kernel**: Supporting compiling of `.less` to `.css` as part of Zesk build.
- **Zesk Kernel**: `zesk\Command_File_Convert` now supports `target-prefix` and `mkdir-target` to support mapping files from `foo.less` to `../css/foo.css` if you want.
- **Zesk Kernel**: `zesk\HTML` no longer adds `id` attribute to inputs
- **Zesk Kernel**: `zesk\PHP::log` added as interface to PHP system logger
- **iLess Module**: Adding `lessc` command-line option, use `zesk lessc` to compile `.less` files to `../css/foo.css` automatically.


<!-- Generated automatically by release-zesk.sh, beware editing! -->
