## Zesk Version {version}

- Allow zesk.sh to interpret --cd in command line before selecting zesk-command.php to run
- Clarification of deprecating `firstarg` using `?:` (ternary operator) not `??` (null coalesce)
- Fixing module-new so it does not create a .php root file which is deprecated
- `zesk-command.php` Remove deprecated config calls, modify semantics "factory" not "instance"
- Test work module factoring, Travis CI integration
- added `Interface_Module_Head` to `Module_Bootstrap`
- `test` module loaded for testing only
- updating `version` doc

<!-- Generated automatically by release-zesk.sh, beware editing! -->
