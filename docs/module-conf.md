# Module Configuration File variables

Module configuration files can be a `.conf` file or a JSON file (ending with `.json`).

Note that `.conf` files, when loaded, all top-level key names are converted to lower case, so:

	NAME="Module"
   
and

    name="Module"

Are equivalent.

## `name` (string)

The name of the module. Used in the user interface. Optional.

## `description` (string)

A short description about what your module does. Optional.

## `author` (string)

Name of the author to be displayed in the user interface. Optional.

## `author_email` (string)

Email address of the author. Optional.

## `author_url` (string)

Url of author's website. Optional.

## `copyright` (string)

Copyright string displayed in the user interface. Optional.

## `license` (string|array)

A semi-colon separated list, or a list array of license types available for this module.

e.g.

	license="mit"
	license="lgpl-2.1"
	license=[ "non-commercial-free", "commercial-paid" ]

TODO: Need to figure out how this is really needed.

## `url_license` (string)

URL to the license file for this module or dependent libraries. Optional.

## `url_project` (string)

URL to link to the project/module home page online. Used in the user interface. Optional.

## `version` (string)

Version of this module. Optional.

## `version_data` (object)

JSON formatted object with the following fields:

- `file` - The file to examine for version information, should be relative to the `$MODULE_PATH`
- `pattern` - A regular expression pattern which captures as the first pattern the version number to be reported
- `key` - If `file` is a JSON file, the path to retrieve the version by key, path segments separated by periods.

For example:

	version_data={ "file": "${MODULE_PATH}/classes/markdown.inc", "pattern": "/\\$Revision: ([0-9]+) \\$/" }

Or

	"version_data": {
		"file": "vendor/thingy/package.json",
		"key": "version"
	}

Generally, this is used to dynamically determine the version of the software when the URL to download is always bleeding-edge (e.g. new versions overwrite the old ones, etc.). 

## `share_path` (path or list of paths)

When this module is loaded, a `Application::share_path` directory is added automatically. (See [sharing](share.md)). As well, this is used for correctly creating a "local" share path for the application in the web root.

## `requires` (array of other module names)

If your module requires other modules, enter that list here, e.g.

	{
		"name": "ReactJS",
		"requires": [ "NodeJS" ]
	}

The dependent modules will be loaded prior to your module being initialized.

# Update command settings

The following fields are used by the [`update`](command-update) command to retrieve remote code libraries and automatically install most recent versions of code from the internet.

## `delete_after` (array of string)

List of file patterns to delete from the `destination` after files are installed. 

## `strip_components` (mixed)

When decompressing or extracting an archive after downloading, remove this many directory components from the resulting file.

If an integer, in which case the number given is the number of directories which are deleted in the destination.

If a string, then the path given is stripped from the beginning of all target directories. It can also contain wildcard components ("*") to skip directories which may change. So:

	STRIP_COMPONENTS="*/lib"
	
Will strip

	jwidget-4.2.3/lib
	jwidget-4.2.4/lib
	anyotherterm/lib
	
From the target archive before copying to the destination directory. It uses the `glob` PHP function to determine which files match.

## `url` (string)

A URL of a dependency package to download as part of this module and place in the `destination` directory. Should be in `.zip`, or `.tar.gz`, or a raw file to download (such as a JavaScript file).

## `urls` (array of string)

List of URLs to download and place in the `destination` directory.

## `url_download` (string)

URL of a page for this module where new versions of dependent libraries (downloads) are available.

## `versions` (array of array) 

Versions is the preferred method of specifying a module which needs to be downloaded. It's a structure with keys of `[version]` and values of a combination of `url`, `destination`, `urls` as values, e.g.

	{
		"name": "Chosen",
		"versions": {
			"1.4.1": {
				"url": "https://github.com/harvesthq/chosen/releases/download/v{version}/chosen_v{version}.zip"
			},
			"1.4.2": {
				"url": "https://github.com/harvesthq/chosen/releases/download/{version}/chosen_v{version}.zip"
			}
		},
		"destination": "vendor/components/chosen",
		"share_path": "vendor/components/chosen"
	}

Note in this example, the token `{version}` is replaced in every value with the key (e.g. either "1.4.1" or "1.4.2" above, depending on context).

You can specify multiple files by using `urls` instead. The final version structure can contain the following keys:

- `url` - The url to load
- `urls` - One or more URLs to load in the format { "url to load": "destination path" }
- `destination` - A unique destination path for this version
- `strip_components` - A unique `strip_components` value for this version
- ``

## `module_class` (string)

The name of the subclass of `zesk\Module` to instantiate for this module.

## `autoload_path` (string)

The name of the autoload path relative to the module path to use for autoloading. Defaults to `classes`.

## `autoload_class_prefix` (string)

The name of the class to prefix all classes which appear in autoload path. e.g. "mymodule\\" or "zesk\\" for example. Defaults to blank string.

## `zesk_command_path` (string)

The name of the zesk command path relative to the module path to use for autoloading. Defaults to `command`.

## `zesk_command_class_prefix` (string)

A string to prefix before any command classes prior to invokation. Defaults to `Commmand_`. Commands are converted to class names by converting non-alphanumeric characters to underscores, then prefixing with this string.

## `locale_path` (string)

Path to the locale files for this module relative to the module path. Defaults to `etc/language`.

# Custom module extensions

## `node_modules` (associative array)

A custom mapping of `node_modules` directories to be aliased upon build by the `NodeJS` module:

	{
		"jquery": "vendor/components/jquery"
	}
	
Will add an alias to the current application's `node_modules/jquery` to the application directory:

	{app_root}/vendor/components/jquery
	
This allows modules to include code which is not a part of the `npm` repository to be included as part of a project.
