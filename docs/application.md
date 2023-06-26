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
	use zesk\Application;
	use zesk\Kernel;

	require_once __DIR__ . '/vendor/autoload.php';

	/*
	 * Load Zesk
	 */
	$application = Kernel::singleton()->createApplication(MyApplicationClass::class, [
        Application::OPTION_PATH => __DIR__,
    ]);
	
	return $application->configure();

### Determining which files will configure an application

TODO

#### Development

Matt is a developer working on Mac OS X, his computer name is `basura` and he also works on a laptop named `kermit`. He creates the following files:

	awesome.application.php
	etc/awesome.json
	etc/platform/basura.json
		
## Application flow

Most applications will require modifications to the base `zesk\Application` class, so all `zesk\Application` instances inherit from the class `zesk\Application` which contains most of the actual application logic.

For web applications, you should create an `index.php` file with the following content, typically inside the `public/` directory within your application:

	<?php
	$application = require_once dirname(__DIR__) . '/awesome.application.php';
	$application->index();

## How applications are initialized; TODO Outdated 2017-11

Applications are created upon each web request and generally destroyed at the end of that request. The creation process and ordering of components becomes important in that you may want modules to execute certain functions prior to other setup tasks.

Here's the order of operations of applications during a request. Note individual applications can modify some aspects of initialization if custom operations are required. Application initialization and configuration is generally broken into three parts:

1. Zesk initialization - Setting up the basic environment to find code and resources to run your application
2. Application and module initialization - Register code hooks, load configuration files, and initialize modules with defaults and dependencies
3. Application configuration hooks - Run the `configured` hook on the application to tell all objects we're ready to handle requests

Now, broken into smaller sections:

### 1. Zesk Initialization

1. Zesk initialization, autoload configuration to find application class
2. Set `zesk()->application_class` to class of application (e.g. `amazing\Application`)
3. `\zesk\Application::instance()` or `$application` creates our application singleton. The application singleton contains most global objects from `zesk()` by reference (`zesk\Paths`,`zesk\Hooks`,`zesk\Configuration`,`zesk\Logger`,`zesk\Classes`,`zesk\ORMs`,`zesk\Process`)

### 2. Application and module initialization

`$application->configure()` is called which consists of the following steps

1. Hooks are registered using `$application->hooks->registerClass()` for
 - `zesk\Cache`
 - `zesk\Database`
 - `zesk\Settings`
1. Hooks are registered using `$application->hooks->registerClass()` for classes listed in `$application->register_hooks`
2. `$application->callHook("configure")` is called
3. Application cache paths and document cache paths is computed
4. `$application->beforeConfigure()` is called to set up additional paths and configure which files to load
5. Application configuration files are loaded and stored in the global `Configuration` state
6. `$application->callHook("configured_files")` is called to handle extending or mananging file configuration state before modules are loaded
7. Modules are loaded, and each module object is created and linked to our application instance, and each `zesk\Module->initialize()` call is called (which can register additional hooks). If your module throws an exception during the `initialize()` call, the object is discarded and the exception stored
8. The application's options are reconfigured from the global configuration
9. Any modules dynamically loaded from `$application->optionIterable("modules")` are loaded

### 3. Application configuration hooks

The configured hooks instruct the system that "everything is ready and configuration can complete"; typically this is when modules and objects load their initial state from the configuration and deal with deprecated options and settings.

Configured hooks are called from lowest-level to highest level:

- System wide hook `configured` is called first
- Each module's `hook_configured()` call is called, in order of module dependency
- Finally the `\zesk\Application` object `hook_configured()` call is made
