# Zesk Applications

A Zesk Application is the primary container in which to deliver functionality. 

`Application` objects are generally used as singleton objects within your program.

To create an application, all your application need do is create a file with the extension `.application.php`. (e.g. `awesome.application.php`) and place that file in the root directory of your application.

The main application entry point should load the minimum configuration to run your application, typically loading configuration files and any external modules required at all times.

We recommend that your application dynamically locate the Zesk library using composer:

	composer require zesk/zesk

## Application configuration

Your application file solely handles setting up the context (configuration) for your application and has the basic syntax:

	<?php
	/**
	 * Application root - all code/resources for the app is below here
	 * @var string
	 */
	namespace awesome;

	use zesk\Kernel;

	require_once __DIR__ . '/vendor/autoload.php';

	/*
	 * Load Zesk
	 */
	$kernel = Kernel::singleton();

	/*
	 * Allow our application to be found
	 */
	$kernel->autoloader->path(__DIR__ . '/classes', array("class_prefix" => "awesome\\"));
	
	return $kernel
		->application_class(Application::class)
		->create_application()
		->set_application_root(__DIR__)
		->configure();

Let's walk through this section by section. First off, we define our namespace for our application. In our case we'll use the namespace `awesome` - all classes will use this as the root namespace:

	namespace awesome;

Next, we load composer dependencies:

	require_once __DIR__ . '/vendor/autoload.php';

Setting up `Zesk` does a few things, but largely sets up the basics of any web application:

	$kernel = Kernel::singleton();
	
We then tell the kernel that any class which begins with "awesome\\" should be found in the `classes/` directory. The autoloader will use standard PSR-4 loading for our classes.

The final lines do the following, in order:

	return $kernel
		->application_class(Application::class)
		->create_application()
		->set_application_root(__DIR__)
		->configure();

1. Tell Zesk kernel that our main application object is of the class `awesome\Application`
2. Create our application
3. Tell the application the current application root directory (where this file resides)
4. Runs the configuration steps for the application (basically, loading configuration files and modules needed)

As your application evolves, these steps may change depending on your needs.

### Determining which files will configure an application

Zesk application configuration was built to simplify the complexities of multiple environments where an application will run. Development, staging, and live configuration are inherently different, and particularly when development spans multiple developers on different hardware.

> Documentation TODO 2017-11: This is too complex to configure the app - remove the global options which are never used and remove `APPLICATION_NAME`

Configuration of your application should be easily customizable within all of these environments. As such, default configuration of Zesk applications will load [configuration files](/configuration-file-format), by default as follows:

- Using the zesk global `Application::configuration_path`, an array of absolute file paths to scan
- Using the zesk global `Application::configuration_file`, an array of configuration file names (including the .conf)

The default values for `Application::configuration_path` are 

1. "/etc"
1. `$zesk->path("etc")` (e.g. "/usr/local/zesk/etc", "/publish/live/zesk/etc")
1. `$application->path("etc")` (e.g. "/var/www/awesome/etc", "/publish/live/sites/awesome/etc")

The default values for `Application::configuration_file` are:

1. `application.conf`
1. If the constant `APPLICATION_NAME` is defined, APPLICATION_NAME.conf
1. The unique name of the system. In short, the result `zesk\System::uname()`, converted to lowercase, and then concatenated with the ".conf" extension

Both default values can be overridden by setting `zesk` globals to different values, or by passing in TODO

So, how would you use this?

#### Development

Matt is a developer working on Mac OS X, his computer name is `basura` and he also works on a laptop named `kermit`. He creates the following files:

	awesome.application.php
	etc/awesome.json
	etc/platform/basura.json
		
## Application flow

Most applications will require modifications to the base `zesk\Application` class, so all `zesk\Application` instances inherit from the class `zesk\Application` which contains most of the actual application logic.

For web applications, you should create an `index.php` file with the following content, typically inside the `public/` directory within your application:

	<?php
	$application = require_once dirname(dirname(__FILE__)) . '/awesome.application.php';
	$application->index();

## How applications are initialized; TODO Outdated 2017-11

Applications are created upon each web request and generally destroyed at the end of that request. The creation process and ordering of components becomes important in that you may want modules to execute certain functions prior to other setup tasks.

Here's the order of operations of applications during a request. Note individual applications can modify some aspects of initialization if custom operations are required. Application initialization and configuration is generally broken into three parts:

1. Zesk initialization - Setting up the basic environment to find code and resources to run your application
1. Application and module initialization - Register code hooks, load configuration files, and initialize modules with defaults and dependencies
1. Application configuration hooks - Run the `configured` hook on the application to tell all objects we're ready to handle requests

Now, broken into smaller sections:

### 1. Zesk Initialization

1. Zesk initialization, autoload configuration to find application class
1. Set `zesk()->application_class` to class of application (e.g. `amazing\Application`)
1. `\zesk\Application::instance()` or `app()` creates our application singleton. The application singleton contains most global objects from `zesk()` by reference (`zesk\Paths`,`zesk\Hooks`,`zesk\Configuration`,`zesk\Logger`,`zesk\Classes`,`zesk\ORMs`,`zesk\Process`)

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

