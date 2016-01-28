# Module Configuration File variables

## `NAME` (string)

The name of the module. Used in the user interface.

## `DESCRIPTION` (string)

A short description about what your module does.

## `AUTHOR` (string)

Name of the author to be displayed in the user interface.

## `AUTHOR_EMAIL` (string)

Email address of the author.

## `COPYRIGHT` (string)

Copyright string displayed in the user interface.

## `LICENSE` (string|array)

A semi-colon separated list, or a list array of license types available for this module.

e.g.

	LICENSE="mit"
	LICENSE="lgpl-2.1"
	LICENSE=[ "non-commercial-free", "commercial-paid" ]

TODO: Need to figure out how this is really needed.

## `URL_LICENSE` (string)

URL to the license file for this module or dependent libraries.

## `URL_PROJECT` (string)

URL to link to the project/module home page online. Used in the user interface.

## `VERSION` (string)

Version of this module. Optional.

## `VERSION_DATA` (object)

JSON formatted object with the following fields:

- `file` - The file to examine for version information, should be relative to the `$MODULE_PATH`
- `pattern` - A regular expression pattern which captures as the first pattern the version number to be reported

For example:

	VERSION_DATA={ "file": "${MODULE_PATH}/classes/markdown.inc", "pattern": "/\\$Revision: ([0-9]+) \\$/" }

## `SHARE_PATH` (path)

When this module is loaded, a `zesk::share_path` directory is added automatically. As well, this is used for correctly creating a "local" share path for the application in the web root.

# Update command settings

The following fields are used by the [`update`](command-update) command to retrieve remote code libraries and automatically install most recent versions of code from the internet.

## `DELETE_AFTER` (array of string)

List of file patterns to delete from the `DESTINATION` after files are installed. 

## `STRIP_COMPONENTS` (mixed)

When decompressing or extracting an archive after downloading, remove this many directory components from the resulting file.

If an integer, in which case the number given is the number of directories which are deleted in the destination.

If a string, then the path given is stripped from the beginning of all target directories. It can also contain wildcard components ("*") to skip directories which may change. So:

	STRIP_COMPONENTS="*/lib"
	
Will strip

	jwidget-4.2.3/lib
	jwidget-4.2.4/lib
	anyotherterm/lib
	
From the target archive before copying to the destination directory. It uses the `glob` PHP function to determine which files match.

## `URL` (string)

A URL of a dependency package to download as part of this module and place in the `DESTINATION` directory. Should be in `.zip`, or `.tar.gz`, or a raw file to download (such as a JavaScript file).

## `URLS` (array of string)

List of URLs to download and place in the `DESTINATION` directory.

## `URL_DOWNLOAD` (string)

URL of a page for this module where new versions of dependent libraries (downloads) are available.

