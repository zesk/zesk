# Zesk Applications

A Zesk Application is the primary container in which to deliver functionality. 

`Application` objects are generally used as singleton objects within your program.

To create an application, all your application need do is create a file with the extension `.application.inc`. (e.g. `awesome.application.inc`) and place that file in the root directory of your application.

The main application entry point should load the minimum configuration to run your application, typically loading configuration files and any external modules required at all times.

As the location of the Zesk toolkit changes from development to live platforms, we recommend that your application dynamically locate the Zesk library using a technique shown in the file

	https://code.marketacumen.com/zesk/trunk/_zesk_loader_.inc

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
			if (is_file(ZESK_APPLICATION_ROOT . '../zesk/zesk.inc')) {
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
			require_once ZESK_ROOT . 'zesk.inc';
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
	_zesk_loader_::init();

	/*
	 * Allow our application to be found
	 */
	zesk::autoload_path(ZESK_APPLICATION_ROOT . 'classes');

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
1. The unique name of the system. In short, the result `system::uname()`, converted to lowercase, and then concatenated with the ".conf" extension

Both default values can be overridden by setting `zesk` globals to different values, or by passing in TODO

So, how would you use this?

#### Development

Matt is a developer working on Mac OS X, his computer name is `basura` and he also works on a laptop named `kermit`. He creates the following files

## Application flow

Most applications will require modifications to the base `Application` class, so all `Application` instances inherit from the class `Zesk_Application` which contains most of the actual application logic.

For web applications, you should create an `index.php` file with the following content:

	<?php
	try {
		require_once dirname(dirname(__FILE__)) . '/awesome.application.inc';
		$application = Application::instance();
		$application->index();
	} catch (Exception $e) {
		$application = Application::instance();
		echo $application->theme("page", zesk::theme(zesk::class_hierarchy($e), array(
			"exception" => $e
		)));
		exit(1);
	}
	

