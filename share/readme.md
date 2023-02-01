# Zesk /share/ directory

The share directory has been slowly deprecated in Zesk, and has been trimmed to the bare minimum of core required files.

Shared items across modules and applications can be served using `zesk\Controller_Share` for development systems, and ultimately a means of generating proper alias configurations for high-performance sites.

Currently:

- `/share/js/` contains zesk.js and other tools. **This has been migrated to NPM module zeskjs**

The `/share/` folder is shared via the `zesk\Controller_Share` as the '/share/zesk/' URI.
