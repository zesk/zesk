# Zesk /share/ directory

The share directory will slowly be deprecated in Zesk, and will hold only core-related shared items, eventually.

Shared items across modules and applications can be served using `Controller_Share` for development systems, and ultimately a means of generating proper alias configurations for high-performance sites.

Currently:

- `/ajax/` is deprecated
- `/bootstrap-`_version_`/` has been moved to module bootstrap
- `/font/` can probably be moved to an internal directory, used currently in `View_Image_Text`
- `/jquery/` should likely be migrated to module jquery, although this may remain in core
- `/less/` compiles to files in `/css/`
- `/sounds/` is used for Flash sound interactivity
- `/widgets/` and subfolders may be split into modules as needed

KMD. Wed Dec 11 20:07:52 EST 2013

