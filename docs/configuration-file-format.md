# Zesk Configuration File format

TODO Way out of date

The Zesk configuration file format allows for diverse structures to be embedded in a plain text file and is used primarily for loading application default settings. 

## Main call

	new zesk\Configuration\Parser\CONF(...)
	
The main workhorse of the configuration file processing is `conf::parse` which takes a list of file lines and a set of options. 

The options affect how the file is loaded, and what features are present in the file. All options are optional, but you should be aware of their defaults The options available are:

- `overwrite` (boolean) - Whether values which appear later in the configuration file parsing will overwrite currently set values. Defaults to `false`.
- `unquote` (mixed) - Specify pairs of valid quote characters to unquote, or false to disable quoting. Defaults to `'\'\'""'`
- `trimKey` (boolean) - Keys should be trimmed for white space. Defaults to `true`.
- `trimValue` (boolean) - Values should be trimmed for white space (unquoted values only). Defaults to `true`.
- `autoType` (boolean) - Values should attempt to automatically type, converting boolean values to PHP boolean values, integers to PHP integral values, and JSON-encoded values. Defaults to `true`.
- `lower` (boolean) - Key values should be converted to lowercase. Defaults to `true`.
- `multiline` (boolean) - Values can span lines by prefixing subsequent lines with whitespace. Defaults to false.
- `variables` (array) - Array of key-value pairs to be defined prior to processing the configuration file lines. Defaults to `zesk::get()` (system-wide globals).

Class is `zesk\Configuration\Parser\CONF` to set default configuration settings.

## Built-in features

The Zesk configuration format was designed to work easily with shell programs such as `bash` or `sh` to enable sharing settings between PHP and other types of scripts. Zesk configuration files support the following features:

### Automatic stripping of the term "export " from variables names. 

So:

    export LOG_PATH=/var/log

AND

	LOG_PATH=/var/log
	
Are equivalent. The "export " portion of the variable name is removed prior to setting.

### Variables substitution

If a variable is already defined, it will be substituted using `bash` variable syntax:

    LOG_PATH=${HOME}/log
	READER_LOG_PATH=${LOG_PATH}/reader/
	HTTPD_LOG_PATH=${LOG_PATH}/httpd/
	
If a variable is *undefined*, then it is replaced with blanks.

### Name munging

Configuration file names can use `__` (double underscore) and `___` (triple underscore) which are converted to `::` and `\` respectively making it easier to specify complex class paths in the Shell if needed.

e.g.

    zesk___Application__version="5.0.0"
    zesk\Appliction::version="5.0.0"

Both are equivalent.

## Other features

As with all things Zesk, the default configuration file behavior can be overridden by specific applications. The main configuration file processing logic appears in `Zesk_Application::configure` and follows this pseudo-code:

 TODO
