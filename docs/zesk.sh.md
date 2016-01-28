# `zesk.sh`

`zesk.sh` is a shell command for running PHP code in the application context. It "figures out" how to set up your Application configuration and settings, and then lets you run commands to manage or maintain your application.

## How commands are run

Commands are run by one of three starting points:

	zesk
	zesk.sh
	zesk-command.php
	
### `zesk`

`zesk` is a shell shortcut which just calls `zesk.sh` with all command line arguments passed through.

### `zesk.sh`

`zesk.sh` primarily searches from the current working directory up to the root to find a **zesk application file** which has the extension `.application.inc`, and then passes it as the first parameter to `zesk-command.php`, along with the other arguments passed to this shell script.

If multiple files exist in same directory with the extension, it uses the first one alphabetically. (e.g. `aardvarks.application.inc` instead of `zebra.application.inc`)

This command understands a single parameter `--search directory` which switches to a different directory to begin the search for a **zesk application file**. 

The command also allows for the default extension to be modified with a shell global `zesk_root_files` which should be in the form:

	export zesk_root_files='*.zeskapp'
	zesk globals

### `zesk-command.php`

Runs your command in PHP. This command can take a variety of parameters, as follows:

    --set name[=value]   Set a zesk global
	--unset name         Unset a zesk global
	--cd                 Change to a directory
	--config file        Load a configuration file
	--*variable_name*    Toggle a zesk global as a boolean
	/path/to/file        Include a file (determined by slash)
	command              Run a Zesk command

## Zesk Application File

The **Zesk Application File** configures the application and **Zesk**. The simplest one would look like this:

	<?php
	if (!defined('ZESK_APPLICATION_ROOT')) {
		define('ZESK_APPLICATION_ROOT', dirname(__FILE__) . '/');
	}
	if (!defined('ZESK_ROOT')) {
		define('ZESK_ROOT', dirname(ZESK_APPLICATION_ROOT) . "/zesk/");
	}
	require_once ZESK_ROOT . 'zesk.inc';
	zesk::autoload_path(zesk::application_root('classes'));
	Application::configure();

Note that it defines `ZESK_ROOT`, includes `zesk.inc` and configures the application. All other application logic such as handling login or session management should be handled elsewhere.
