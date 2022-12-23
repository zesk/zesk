# Zesk Commands

Zesk has a simple interface to enable creation of chainable, command-line functions which work within your application's context.

	zesk [arguments] [command] [command-arguments]
	
The main binary is `zesk` found at `$ZESK_ROOT/bin` which invokes `zesk.sh` which in turn invokes `zesk-command.php`. They are functionally identical.

The command starts at the current working directory and looks for a file ending with `.application.php` in parent directories until found or the root of the file system is reached.

Once found, it runs your commands in PHP after first including the primary application include file. This allows your application to load its context, such as globals, database settings, or any other basic configuration needed to run commands.

# Special arguments for `zesk`

## --search directory &middot; *Search starting at directory*

To search for the application include file in a different directroy, pass this parameter before your first command.

    zesk --search $HOME/live/application globals

## --cd directory &middot; *Change directories before search*

To change to a directory prior or during command invocation:

    zesk --cd $HOME/live/application/content convert-content

## --set name=value &middot; *Set arbitrary global*

To set a global value in Zesk prior to invoking a command, the --set parameter allows setting of globals in Zesk via `zesk::set`.

   zesk --set zesk\\database::debug=1 update

Be sure to use proper backslash escaping when using the command line.

## --[variable]  &middot; *Set arbitrary global to true*

When specified prior to the first command, this argument will set a zesk global to true.

	zesk --database::debug update
	
## --[variable]=[value]  &middot; *Set arbitrary global to string value*

Similarly, you can specify a value for the global as value

	zesk --log_path=$HOME/log update
	
## --define [variable]=[value]  &middot; *Set arbitrary global to string value*

Similarly, you can define a variable before loading the application context:

	zesk --define PHPUNIT update

If the value (and the `=`) are not supplied, then the variable is defined to be `true`.

## --unset variable

Unset a previously set variable.

# Commands

The Zesk command can take PHP include files as parameters, or command which essentially point to a class or function to invoke. If the parameter is a file which can be included, it is included directly and no further action is taken.

Note that any file paths passed to the zesk command should be relative to the current working directory, except if `--cd` and `--search` are used. (TODO - this needs to be clearer.)

If the parameter is not a file, then Zesk looks to see if it corresponds to a command by searching the paths listed by:

	zesk::zeskCommandPath();
	
> Note: This is different from `zesk::command_path()` which is the path used to find shell commands in the system.

Commands are translated to paths similarly to how the Zesk autoloader works, e.g. underscores and dashes are converted to slashes, so:

	zesk db-connect

will load:

	$ZESK_ROOT/classes/command/db/connect.inc
	
If the file exists, the final step is to invoke the command. Zesk will either invoke a function:

	function zesk_command_EXAMPLE(array $arguments);
	
Which returns any unparsed arguments, or will invoke an object of class

	class Command_EXAMPLE extends Command {
		function run() {
			/* handle arguments here */
		}
	}
	
**`EXAMPLE`** in the above examples is the command converted to a valid PHP name, so:

- `db-connect` becomes `zesk_command_db_connect` (function) or `Command_DB_Connect` (class)
- `find-nearby-peers` becomes `zesk_command_find_nearby_peers` (function) or `Command_Find_Nearby_Peers` (class)

> Technically, functions and class names in PHP are case-insensitive, so you can capitalize your class name and function name as you wish with no ill side-effects:
>
>     function Zesk_Commmand_DB_Connect(array $arguments);
>     class Command_db_connect extends Command { ... }
>
> are the same as
>
>     function zesk_command_DB_CONNECT(array $arguments)_
>     class Command_Db_Connect extends Command { ... }
>

If both the class and the function are defined, then the class is invoked.

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
