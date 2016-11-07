# Zesk Naming Conventions

This article outlines how developers should name features of their code related to Zesk. This is a requirement for Zesk [core](), or Zesk [modules](), and optional for  applications built on Zesk. It covers:

- File names
- Directory names
- File Extensions
- Class names
- Class method names
- Hook names
- Configuration settings names
- Option names

# Background Material

- [Naming Styles Definitions][]
- [PHP Case Sensitivity][]

## Files

All Zesk internal file names are lowercase, with no spaces in the names. Dashes or underscores in names are both acceptable. Modules or third-party libraries may use whatever naming convention allowed, but the main entry point should follow the Zesk naming guidelines.

## File Extensions

Zesk supports the following types of file extensions, and conventions:

### PHP Files end with `.inc`, `.php`, `.phpt` or `.tpl`

- `.php` files are intended to be invoked externally, e.g. a command line function, or a web server page request.
- `.inc` files are intended to be included in other files.
- `.phpt` files are unit test files, and are invoked via the PHP interpreter (**deprecated**)
- `.tpl` files are template files and are invoked via the PHP interpreter, usually as an `include`

PHP files of all three types should have `<?php` as the first line in the file, and the trailing `?>` should be left off to avoid trailing white space issues.

Command-line files (`.phpt`, and `.php` files which are command-line only) may be invoked using the shell bang format and have the first two lines as:

    #!/usr/bin/env php
    <?php

The preferred invocation style for command-line tools is to use `/usr/bin/env` to determine the PHP path. Most command-line tools can be written using `zesk-command.php` which provides a slim wrapper around commands.

### Configuration files end with `.conf`

The configuration file format _may_ be compatible with Unix `sh` or `bash` interpreters and can be used to share configuration settings across interpreters.

The basic configuration format is:

    STYLE_PATH=${ZESK_SITE_ROOT}/designs
    APP_STYLE=orange
	MAIN_CSS=$STYLE_PATH/$APP_STYLE/file.css

More about configuration files in [configuration files][].

### Router files end with `.router`

A special file type called a "router" file is the file format for the `Router` class to easily set up web site routes.

The format is similar to [configuration files][] however it uses a space-based indentation scheme, as follows:

    pattern
        template=login.tpl
        login=true
        page template=page/login.tpl
	login
	logout
	    controller=login
	    action="{0}"

More about router files in [router files][].

## Classes

Classes are loaded automatically based on name and the paths set up by `zesk::autoload_path`.

Class paths are computed based on replacement of the underscore `_` in a class name with a path slash `/` and making the entire term lower-case, so:

	$f = new Route_Controller($pattern, $options);
	
Will search the autoload path for:

    route/controller.inc

Class names are [Upper-first CamelCase](naming-styles-definitions.md), optionally separated by underscores for classes which are intended to be instantiated:

    $u = new User();
	$c = new Control_Image_Toggle();
	$s = new Province();

The corresponding file names which contain these classes are lower-case:

	classes/user.inc
	classes/control/image/toggle.inc
	classes/province.inc
	
Class names for purely static, utility classes are lower-case:

    echo HTML::tag('div', '#customer-name', $content);
    echo zesk::get("Company.Name");
    JavaScript::obfuscate_begin();

## Functions and Methods

Functions and methods within the system generally follow the PHP function syntax by using lower-case and underscores to separate known words:

    echo number::format_bytes(1512312);
    $result = Route::compare_weight($route1, $route2);

Note that PHP itself uses two different naming methodologies for class methods and functions (class methods use first-lower [camelcase](glossary.md#camelcase)), while functions use [lower-case underscored](naming-style-definition.md) to separate words. Zesk uses the single convention [Lower Underscore](naming-styles-definitions.md) for new methods added to classes. 

> PHP "magic" methods like `__toString`, `__callStatic`, obviously should be named using the PHP convention, as they will be 
> inoperative using the Zesk method convention `__to_string`, '__call_static'

## Hooks

Hooks are a simple but powerful method for modules to modify or interact with various behaviors in the system. As a general rule, Hooks are named as follows:

### Hookable syntax

Classes which inherit from `Hookable` have hook functionality built in. To invoke a hook:

    $x->call_hook('notify', $emails);

Hook names within a class are [Lower Underscore](naming-styles-definitions.md) and generally are message phrases, such a:

    'controller_not_found'
    'output_alter'

### Class syntax

The `Hookable` class invokes `hook_`_`message`_ first, then calls the class hierarchy version of a hook:

	_`Class`_::` TODO

[configuration files]: configuration-file-format.md "Configuration File Format"
[router files]: router-file-format.md "Router File Format"
[Naming Styles Definitions]: naming-styles-definitions.md "Naming Styles Definitions"
[PHP Case Sensitivity]: php-case-sensitivity.md "PHP Case Sensitivity"
