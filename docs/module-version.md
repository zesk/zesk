# Determining Module Version 

Modules are written to load external source code and keep it up to date, automatically.

Version numbers are often embedded in a variety of files within a module, and so Zesk makes it easy to extract the version number simply.

In your module configuration file, you can specify a configuration value `VERSION_DATA` which is a JSON-encoded array with information on how to determine the current version of your module.

The keys in the JSON-encoded array are:

- `file` - Required. The file to look for the version number in. Should include variables such as `${MODULE_PATH}`.
- `pattern` - Search the file for the specified Perl Regular Expression pattern, and return the first match capturing parenthesis, or the entire matched pattern if no first pattern is captured.
- `key` - Search an object after the file is processed based on file extension. Supports JSON-encoded files (with extension `.json`) and PHP-Serialized files (with extension `.phps`).

## Pattern Example

    "version_data": { 
	"file": "vendor/components/jquery/jquery.js", 
	"pattern": "/jQuery[A-Za-z ]+v([0-9.]+)/" 
    }

The above example loads `vendor/components/jquery/jquery.js` then searches for the pattern and returns the version number.

## Key Example

    "version_data": { 
        "file": "vendor/components/jquery/package.json", 
	"key": "version"
    }

The above example loads the specified JSON file, parses it, and returns the version key.

## Built-in value

You can also just specify your module version using the VERSION configuration option:

	VERSION=1.0

## Showing versions

To show all versions of loaded modules, do:

	zesk module-version
	zesk module-version module1 module2 module3
	
The output will look like:

	jquery: 1.9.1
	jqueryui: 1.11.0pre
	developer: -
	markdown: 1130
	bootstrap: 2.3.2
	anythingslider: 1.9.1
	date: -
	content: -
	inplace: -
	highcharts: 3.0.1
	footerlog: -
	underscorejs: 1.4.4
	jsonjs: 2010-11-18
	jquerytimer: -
	ordering: -
	jpicker: 1.1.6
	flot: 0.8.1-alpha
	apache: -
	bootstrapx-clickover: -
	backbonejs: 1.0.0

With module code name, a colon, a space, and the version number found. Dashes mean no version number was found.

