# Zesk /share/ directory

The share directory has been slowly deprecated in Zesk, and has been trimmed to the bare minimum of core required files.

Shared items across modules and applications can be served using `zesk\Controller_Share` for development systems, and ultimately a means of generating proper alias configurations for high-performance sites.

Currently:

- `/share/js/` contains zesk.js and other tools. **This has been migrated to NPM module zeskjs**
- `/share/css/` contains some debugging stylesheets
- `/share/less/` compiles into `../css`

The `/share/` folder is shared via the `zesk\Controller_Share` as the '/share/zesk/' URI.

KMD. Wed Nov 29 17:48:05 EST 2017

## Sampling of existing share files

The following list was generated from the existing Zesk code files and contains referenced images. Updated 2022.
