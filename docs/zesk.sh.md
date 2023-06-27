# `zesk.sh`

`zesk.sh` is a shell command for running PHP code in the application context. It "figures out" how to set up your Application configuration and settings, and then lets you run commands to manage or maintain your application.

## How commands are run

Commands are run by one of three starting points:

	zesk
	zesk.sh
	zesk-command.php

### `zesk`

`zesk` is a shell shortcut which just calls `zesk.sh` with all command line arguments passed through.

### `zesk-command.php`

`zesk-command.php` processes the command line arguments to set up the initial state for the command, then searches from the current working directory up to the root to find a **zesk application file** which has the extension `.application.php`, loads it, then processes additional arguments to the command.

When finding the application.php file, if multiple files exist in same directory with the extension, it uses the first one alphabetically. (e.g. `aardvarks.application.php` instead of `zebra.application.php`)

This command understands a single parameter `--search directory` which switches to a different directory to begin the search for a **zesk application file**.

Runs your command in PHP. This command can take a variety of parameters, as follows:

	--application        Specify the application.php file explicitly. Must be the first argument to `zesk`.
    --set name[=value]   Set a zesk global
	--unset name         Unset a zesk global
	--config file        Load a configuration file
	--*variable_name*    Toggle a zesk global as a boolean
	/path/to/file        Include a file (determined by slash)
	command              Run a Zesk command

## Zesk Application File

The **Zesk Application File** configures the application and **Zesk**. The simplest one would look like this:

	<?php
	if (!defined('ZESK_APPLICATION_ROOT')) {
		define('ZESK_APPLICATION_ROOT', __DIR__ . '/');
	}
	if (!defined('ZESK_ROOT')) {
		define('ZESK_ROOT', dirname(ZESK_APPLICATION_ROOT) . "/zesk/");
	}
	$zesk = require_once ZESK_ROOT . 'zesk.inc';
	$zesk->autoloader->path($zesk->paths->application('classes'));
	$zesk->application_class = "MyApp";
	Application::instance()->configure();

Note that it defines `ZESK_ROOT`, includes `zesk.inc` and configures the application. All other application logic such as handling login or session management should be handled elsewhere.
