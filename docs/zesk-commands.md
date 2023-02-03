# Zesk Commands

Zesk has a simple interface to enable creation of chainable, command-line functions which work within your application's context.

	zesk [arguments] [command] [command-arguments]
	
The main binary is `zesk` found at `$ZESK_ROOT/bin` in turn invokes `zesk-command.php`.

`zesk` scans the current directory upwards for a `.application.php` file; and then runs the installed `vendor/bin/zesk-command.php`.

This allows you to have a single copy of the main `zesk` binary in your path, and it will find and run the local installed version of `zesk-command.php` for the application.

> **Why do I care?**
>
> When running different versions of Zesk in different projects, it uses the currently installed version of Zesk PHP code for each application.

The command starts at the current working directory and looks for a file ending with `.application.php` in parent directories until found or the root of the file system is reached.

> The `application.php` file's location sets your application root directory, like `composer.json` does.

Once found, it runs your commands in PHP after first including the primary application include file. This allows your application to load its context, such as globals, database settings, or any other basic configuration needed to run commands.

> Again - setting up the database, connecting to your external services, and configuration your application is all done in a single, central location.

# Special arguments for `zesk`

## --cd directory &middot; *Change directories before search*

To change to a directory prior or during command invocation:

    zesk --cd $HOME/live/application/content convert-content

Changes the file system **current directory** running the application. YMMV. See [PHP's getcwd](https://www.php.net/getcwd) and any command which depends on this.

## --set name=value &middot; *Set arbitrary global*

To set a global value in Zesk prior to invoking a command, the --set parameter allows setting of globals in Zesk via `zesk::set`.

   zesk --set "zesk\\Database::debug=1" update

Be sure to use proper backslash escaping when using the command line.

## --[variable]  &middot; *Set arbitrary global to true*

When specified prior to the first command, this argument will set a zesk global to true.

	zesk --zesk\\Database::debug update
	
## --[variable]=[value]  &middot; *Set arbitrary global to string value*

Similarly, you can specify a value for the global as value

	zesk --LOG_PATH=$HOME/log update
	
## --define [variable]=[value]  &middot; *Set arbitrary global to string value*

Similarly, you can define a variable before loading the application context:

	zesk --define PHPUNIT update

If the value (and the `=`) are not supplied, then the variable is defined to be `true`.

## --unset variable

Unset a previously set variable.

# Commands

The Zesk command can take PHP include files as parameters, or command which essentially point to a class or function to invoke. If the parameter is a file which can be included, it is included directly and no further action is taken.

If the parameter is not a file, then Zesk looks to see if it corresponds to a command by searching the paths listed by `$application->zeskCommandPath()`
	
> Note: This is different from `$application->commandPath()` which is the path used to find shell commands in the system.

All commands are loaded in the directory in memory before running. Shortcuts are gathered from each command class's `$this->shortcuts` member.

# Command Arguments

When a command is specified, each command is allowed to process its own arguments, and may stop processing at any time. This allows sets of commands to be chained to allow for complex commands to be invoked from a single command line.

If a command *does not* process a command line argument, then it *may be* handled by the **Special arguments** handling above. To debug how arguments are handled for your command, try using a global debug setting:

	zesk --command_debug globals
	
For commands which are functions, the current argument list (including the name of the command as invoked) is passed to the function. The function should then process arguments as needed, and return any unprocessed arguments.

For example, a very simple command which loads modules could be saved to `$ZESK_ROOT/classes/command/module.inc`:

	<?php
	function zesk_command_module(array $arguments) {
		if (count($arguments) === 0) {
			throw new Exception_Parameter("Need at least one module to load");
		}
		$module = array_shift($arguments);
		zesk::module($module);
		return $arguments;
	}

The equivalent can be written as a `Command` class:

	<?php
	class Command_Module extends Command {
		protected array $option_types = array("+" => "string");
		function run() {
			zesk::module($this->get_arg("module"));
		}
	}

The difference is that the `Command` class keeps track of the arguments which have been processed or not, and there's no need to return the unprocessed arguments.

# Chaining commands

Certain commands can be chained, and others can not, depending on the command. A simple example which outlines how this works is as follows:

	zesk module server module jquery modules --loaded
	
Outputs:

	jquery: true
	server: true

Commands should be implemented such that they use only arguments which are known to it. In addition, classes which extend `zesk\Command` SHOULD specify a
wildcard parameter for remaining parameters, unless the command is **always** last in a chain (for example `database-connect`).

Some commands can specify an terminating string, which **should** be standardized to the double-dash argument (e.g. "`--`"):

	zesk eval "echo zesk()->configuration->init;" "zesk()->hooks->call('alert')" -- globals
	
For example the `eval` command stops when it encounters the `--` argument, and then passes control to the next command.
