# Zesk Applications

A Zesk Application is the primary container in which to deliver functionality. 

`Application` objects are generally used as singleton objects within your program.

To create an application, all your application need do is create a file with the extension `.application.php`. (e.g. `awesome.application.php`) and place that file in the root directory of your application.

The main application entry point should load the minimum configuration to run your application, typically loading configuration files and any external modules required at all times.

As the location of the Zesk toolkit changes from development to live platforms, we recommend that your application dynamically locate the Zesk library using a technique shown in the file

	https://code.marketacumen.com/zesk/trunk/_zesk_loader_.php

This will allow you to have Zesk installed in different locations on your development or production machines. 

	<?php
	
	class _zesk_loader_ {

		/**
		 * 1. Use ZESK_ROOT as passed through an environment variable 
		 * 2. Look at ZESK_APPLICATION_ROOT/../zesk/
		 * 
		 * 3. Path in a file located at:
		 * 
		 * 3.a. ./.ZESK_ROOT
		 * 3.b. $HOME/.zesk/root
		 * 3.c. /etc/zesk/root
		 * 
		 * @return string
		 */
		private static function _find_root() {
			if (array_key_exists('ZESK_ROOT', $_SERVER)) {
				return $_SERVER['ZESK_ROOT'];
			}
			if (is_file(ZESK_APPLICATION_ROOT . '../zesk/zesk.php')) {
				return dirname(ZESK_APPLICATION_ROOT) . '/zesk/';
			}
			$ff = array(
				dirname(__FILE__) . '/.zesk_root'
			);
			if (array_key_exists('HOME', $_SERVER)) {
				$ff[] = $_SERVER['HOME'] . "/.zesk/root";
			}
			$ff[] = '/etc/zesk/root';
			foreach ($ff as $f) {
				if (is_file($f)) {
					$f = file($f);
					return trim(array_shift($f));
				}
			}
			die("Can't find ZESK_ROOT");
		}

		/**
		 * Define ZESK_ROOT and clean it up
		 * Load zesk
		 */
		public static function init() {
			if (!defined('ZESK_ROOT')) {
				$root = self::_find_root();
				define('ZESK_ROOT', rtrim($root, "/") . "/");
			}
			return require_once ZESK_ROOT . 'zesk.php';
		}
	}

This uses the following selections, in order, to load the Zesk toolkit:

1. an environment variable passed through the web server or the command-line environment with the name `ZESK_ROOT`. To configure Apache to pass environment variables, edit Apache's `envvars` file (usually found at `/etc/apache2/envvars`) adding a line `export ZESK_ROOT=/path/to/zesk` and in your site's `VirtualHost` declaration add a line with `PassEnv ZESK_ROOT`. [Configuring Apache to work with Zesk applications](./apache.md)
1. Place `zesk` directly next to your application folder, e.g. `/Users/matt/development/awesome/`, `/Users/matt/development/zesk/`
1. Place the location of Zesk as the first line in a text file in the same directory as your application configuration file called `.zesk_root`
1. Place the location of Zesk as the first line in a text file located at `$HOME/.zesk/root`

Modifications to the loader can be made depending on your application's requirements; the above is intended to work in all scenarios and provide the maximum flexibility.

## Application configuration

Your application file solely handles setting up the context (configuration) for your application and has the basic syntax:

	<?php
	/**
	 * Application root - all code/resources for the app is below here
	 * @var string
	 */
	define('ZESK_APPLICATION_ROOT', dirname(__FILE__) . '/');
	/**
	 * Will load configuration from awesome.conf
	 *
	 * @var string
	 */
	define("APPLICATION_NAME", "awesome");

	/**
	 * Load Zesk
	 */
	class _zesk_loader_ {
		// See above for sample implementation ...
	}

	/*
	 * Load Zesk
	 */
	$zesk = _zesk_loader_::init();

	/*
	 * Allow our application to be found
	 */
	$zesk->autoloader->path(ZESK_APPLICATION_ROOT . 'classes');

	/*
	 * Configure our application
	 */
	Application::instance()->configure();


### Determining which files will configure an application

Zesk application configuration was built to simplify the complexities of multiple environments where an application will run. Development, staging, and live configuration are inherently different, and particularly when development spans multiple developers on different hardware.

Configuration of your application should be easily customizable within all of these environments. As such, default configuration of Zesk applications will load [configuration files](/configuration-file-format), by default as follows:

- Using the zesk global `Application::configuration_path`, an array of absolute file paths to scan
- Using the zesk global `Application::configuration_file`, an array of configuration file names (including the .conf)

The default values for `Application::configuration_path` are 

1. "/etc"
1. `zesk::root("etc")` (e.g. "/usr/local/zesk/etc", "/publish/live/zesk/etc")
1. `zesk::application_root("etc")` (e.g. "/var/www/awesome/etc", "/publish/live/sites/awesome/etc")

The default values for `Application::configuration_file` are:

1. `application.conf`
1. If the constant `APPLICATION_NAME` is defined, APPLICATION_NAME.conf
1. The unique name of the system. In short, the result `zesk\System::uname()`, converted to lowercase, and then concatenated with the ".conf" extension

Both default values can be overridden by setting `zesk` globals to different values, or by passing in TODO

So, how would you use this?

#### Development

Matt is a developer working on Mac OS X, his computer name is `basura` and he also works on a laptop named `kermit`. He creates the following files:

	awesome.application.com
	etc/
		awe

## Application flow

Most applications will require modifications to the base `Application` class, so all `Application` instances inherit from the class `Zesk_Application` which contains most of the actual application logic.

For web applications, you should create an `index.php` file with the following content:

	<?php
	try {
		require_once dirname(dirname(__FILE__)) . '/awesome.application.php';
		$application = Application::instance();
		$application->index();
	} catch (Exception $e) {
		global $zesk;
		$application = Application::instance();
		echo $application->theme("page", $application->theme($zesk->classes->hierarchy($e), array(
			"exception" => $e
		)));
		exit(1);
	}
	

## How applications are initialized

Applications are created upon each web request and generally destroyed at the end of that request. The creation process and ordering of components becomes important in that you may want modules to execute certain functions prior to other setup tasks.

Here's the order of operations of applications during a request. Note individual applications can modify some aspects of initialization if custom operations are required. Application initialization and configuration is generally broken into three parts:

1. Zesk initialization - Setting up the basic environment to find code and resources to run your application
1. Application and module initialization - Register code hooks, load configuration files, and initialize modules with defaults and dependencies
1. Application configuration hooks - Run the `configured` hook on the application to tell all objects we're ready to handle requests

Now, broken into smaller sections:

### 1. Zesk Initialization

1. Zesk initialization, autoload configuration to find application class
1. Set `zesk()->application_class` to class of application (e.g. `amazing\Application`)
1. `\zesk\Application::instance()` or `app()` creates our application singleton. The application singleton contains most global objects from `zesk()` by reference (`zesk\Paths`,`zesk\Hooks`,`zesk\Configuration`,`zesk\Logger`,`zesk\Classes`,`zesk\Objects`,`zesk\Process`)

### 2. Application and module initialization

`app()->configure()` is called which consists of the following steps

1. Hooks are registered using `app()->hooks->register_class()` for
 - `zesk\Cache`
 - `zesk\Database`
 - `zesk\Settings`
1. Hooks are registered using `app()->hooks->register_class()` for classes listed in `app()->register_hooks`
1. `app()->call_hook("configure")` is called
1. Application cache paths and document cache paths is computed
1. `app()->preconfigure()` is called to set up additional paths and configure which files to load
1. Application configuration files are loaded and stored in the global `Configuration` state
1. `app()->call_hook("configured_files")` is called to handle extending or mananging file configuration state before modules are loaded
1. Modules are loaded, and each module object is created and linked to our application instance, and each `zesk\Module->initialize()` call is called (which can register additional hooks). If your module throws an exception during the `initialize()` call, the object is discarded and the exception stored
1. The application's options are reconfigured from the global configuration 
1. Any modules dynamically loaded from `app()->option_list("modules")` are loaded

### 3. Application configuration hooks

The configured hooks instruct the system that "everything is ready and configuration can complete"; typically this is when modules and objects load their initial state from the configuration and deal with deprecated options and settings.

Configured hooks are called from lowest-level to highest level:

- System wide hook `configured` is called first
- Each module's `hook_configured()` call is called, in order of module dependency
- Finally the `\zesk\Application` object `hook_configured()` call is made

