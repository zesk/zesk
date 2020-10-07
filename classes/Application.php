<?php

/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2018, Market Acumen, Inc.
 */
namespace zesk;

use Psr\Cache\CacheItemPoolInterface;
use zesk\Locale\Reader;
use zesk\Router\Parser;

/**
 * Core web application object for Zesk.
 *
 * If you're doing something useful, it's probably a simple application.
 *
 * @author kent
 *
 * Methods below require you to actually load the modules for them to work.
 *
 * @method Widget widget_factory($class, array $options = array())
 *
 * @method Module_ORM orm_module()
 * @method Class_ORM class_orm_registry($class = null)
 * @method ORM orm_registry($class = null, $mixed = null, array $options = null)
 * @method ORM orm_factory($class, $mixed, array $options = array())
 *
 * @method Database database_registry($name)
 * @method Module_Database database_module()
 * @method Module_Permission permission_module()
 * @method Module_Job job_module()
 * @method Module_Repository repository_module()
 * @method Cron\Module cron_module()
 *
 * @method Interface_Session session_factory()
 */
class Application extends Hookable implements Interface_Theme, Interface_Member_Model_Factory, Interface_Factory {
	/**
	 * Default option to store application version - may be stored differently in overridden classes, use
	 *
	 * @see version()
	 * @var string
	 */
	const OPTION_VERSION = "version";

	/**
	 * Zesk singleton. Do not use anywhere but here.
	 *
	 * @var Kernel
	 */
	private $kernel = null;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit the value here.
	 *
	 * @var Paths
	 */
	public $paths = null;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Hooks
	 */
	public $hooks = null;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Autoloader
	 */
	public $autoloader = null;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var CacheItemPoolInterface
	 */
	public $cache = null;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Configuration
	 */
	public $configuration = null;

	/**
	 *
	 * @var Configuration_Loader
	 */
	public $loader = null;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Logger
	 */
	public $logger = null;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Classes
	 */
	public $classes = null;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Objects
	 */
	public $objects = null;

	/**
	 * Inherited directly from zesk\Kernel.
	 * Do not edit.
	 *
	 * @var Process
	 */
	public $process = null;

	/**
	 *
	 * @var Locale
	 */
	public $locale = null;

	/**
	 *
	 * @var Locale[string]
	 */
	private $locales = null;

	/**
	 *
	 * @var Command
	 */
	public $command = null;

	/**
	 *
	 * @var Router
	 */
	public $router = null;

	/**
	 * List of search paths to find modules for loading
	 *
	 * @var string[]
	 */
	private $module_path = array();

	/**
	 * Modules object interface
	 *
	 * @var Modules
	 */
	public $modules = null;

	/**
	 * Array of external modules to load
	 *
	 * @var string[]
	 * @see $this->load_modules
	 */
	protected $load_modules = array();

	/**
	 * Array of parent => child mappings for model creation/instantiation.
	 *
	 * Allows you to set your own user class which extends \zesk\User, for example.
	 *
	 * @var array
	 */
	protected $class_aliases = array();

	/**
	 * File where the application class resides.
	 * Override this in subclasses with
	 * public $file = __FILE__;
	 *
	 * @var string
	 */
	public $file = null;

	/**
	 *
	 * @var Request[]
	 */
	private $request_stack = array();

	/**
	 * @deprecated 2018-01
	 * @var Request
	 */
	protected $request = null;

	/**
	 *
	 * @deprecated 2018-01
	 * @var Response
	 */
	protected $response = null;

	/**
	 * @deprecated 2018-01
	 * @var Interface_Session
	 */
	public $session = null;

	/**
	 * @deprecated 2018-01
	 * @var User
	 */
	public $user = null;

	/**
	 * Array of calls to create stuff
	 *
	 * @var \Closure[string]
	 */
	private $factories = array();

	/**
	 * Array of classes to register hooks automatically
	 *
	 * @var array of string
	 */
	protected $register_hooks = array();

	/**
	 * Configuration files to include
	 *
	 * @var array of string
	 */
	protected $includes = array();

	/**
	 * Configuration options
	 *
	 * @var array
	 */
	private $configuration_options = null;

	/**
	 * Configuration options
	 *
	 * @var array
	 */
	protected $template_variables = array();

	/**
	 * Zesk Command paths for loading zesk-command.php commands
	 *
	 * @var array
	 */
	protected $zesk_command_path = array();

	/**
	 * Paths to search for themes
	 *
	 * @var array $theme_path
	 */
	protected $theme_path = array();

	/**
	 * Paths to search for shared content
	 *
	 * @var string[]
	 */
	protected $share_path = array();

	/**
	 * Paths to search for locale files
	 *
	 * @var string[]
	 */
	protected $locale_path = array();

	/**
	 *
	 * @var string
	 */
	protected $cache_path = null;

	/**
	 *
	 * @var string
	 */
	private $document = null;

	/**
	 *
	 * @var string
	 */
	private $document_prefix = '';

	/**
	 *
	 * @var string
	 */
	private $document_cache = null;

	/**
	 * Top template
	 *
	 * @var Template
	 */
	public $template = null;

	/**
	 * Template stack. public so it can be copied in Template::__construct
	 *
	 * @see Template::__construct
	 * @var Template_Stack
	 */
	public $template_stack = null;

	/**
	 *
	 * @var string[]
	 */
	private $theme_stack = array();

	/**
	 * Boolean
	 *
	 * @var boolean
	 */
	private $configured_was_run = false;

	/**
	 *
	 * @var array:string
	 */
	private $content_recursion = false;

	/**
	 *
	 * @param Kernel $kernel Zesk kernel for core functionality
	 * @param array $options Options passed in by zesk\Kernel::create_application($options)
	 */
	public function __construct(Kernel $kernel, array $options = array()) {
		parent::__construct($this, $options);
		$this->_initialize($kernel);
		$this->_initialize_fixme();
		$this->set_option('maintenance', $this->_load_maintenance());
	}

	/**
	 * The unique ID name for this application, used for cron or identifying multiple instances of a single application on a site.
	 *
	 * @return string
	 */
	public function id() {
		return get_class($this);
	}

	/**
	 *
	 * @throws Exception_Unimplemented
	 */
	protected function _initialize(Kernel $kernel) {
		// Pretty much just copy object references over
		$this->zesk = $kernel;
		$this->kernel = $kernel;
		$this->paths = $kernel->paths;
		$this->hooks = $kernel->hooks;
		$this->autoloader = $kernel->autoloader;
		$this->configuration = $kernel->configuration;
		$this->cache = $kernel->cache;
		$this->logger = $kernel->logger;
		$this->classes = $kernel->classes;
		$this->objects = $kernel->objects;

		/*
		 * Current process interface. Depends on ->hooks
		 */
		$this->process = new Process($this);

		/*
		 * Speaka-da-language?
		 */
		$this->locale = $this->locale_factory();

		/*
		 * Where various things can be found
		 */
		// Find modules here
		$this->module_path = array();
		// Find Zesk commands here
		$this->zesk_command_path = array();
		// Find theme files here
		$this->theme_path = array();
		// Find share files for Controller_Share (move to internal module)
		$this->share_path = array();
		// Where to store temporary files
		$this->cache_path = null;
		// Where our web server is pointing to
		$this->document = null;
		// Web server has a hard-coded prefix
		$this->document_prefix = '';
		// Directory where we can store web-accessible resources
		$this->document_cache = null;

		$this->configured_was_run = false;

		$this->command = null;
		$this->router = null;

		// $this->load_modules is set in subclasses
		// $this->class_aliases is set in subclasses
		// $this->file is set in subclasses
		// $this->register_hooks is set in subclasses
		//

		// $this->includes is set in subclasses?
		// $this->template_variables is set in application itself?
		$this->template_variables = array();

		foreach ($this->class_aliases as $requested => $resolved) {
			$this->objects->map($requested, $resolved);
		}

		$this->_init_document_root();

		$this->zesk_command_path = array(
			ZESK_ROOT . 'command' => 'zesk\Command_',
		);
		if (is_array($this->modules)) {
			throw new Exception_Unimplemented("Application::\$modules no longer supported");
		}

		$this->module_path($this->path_module_default());

		// Variable state
		$this->template_stack = new Template_Stack();
		// Root template
		$this->template = new Template($this);
		$this->template_stack->push($this->template);
		// Stack of currently rendering themes
		$this->theme_stack = array();

		$this->theme_path($this->path_theme_default());
		$this->share_path($this->path_share_default(), 'zesk');
		$this->locale_path($this->path_locale_default());
	}

	/**
	 * Initialize part 2
	 */
	protected function _initialize_fixme() {
		// These two calls mess up reconfigure and do not reset state correctly.
		// Need a robust globals monitor to ensure reconfigure resets state back to default
		// Diffiult issue is class loader modifies state
		$this->factories = array();
		$this->modules = new Modules($this);
	}

	/**
	 *
	 * @return string
	 */
	private function path_module_default() {
		return $this->paths->zesk('modules');
	}

	/**
	 *
	 * @return string
	 */
	private function path_theme_default() {
		return $this->paths->zesk('theme');
	}

	/**
	 *
	 * @return string
	 */
	private function path_share_default() {
		return $this->paths->zesk('share');
	}

	/**
	 *
	 * @return string
	 */
	private function path_locale_default() {
		return $this->paths->zesk('etc/language');
	}

	/**
	 * Clone application
	 */
	protected function __clone() {
		if ($this->configuration) {
			$this->configuration = clone $this->configuration;
		}
		if ($this->router) {
			$this->router = clone $this->router;
		}
		if ($this->template) {
			$this->template = clone $this->template;
		}
		if ($this->template_stack) {
			$this->template_stack = clone $this->template_stack;
		}
	}

	/**
	 *
	 * @param Command $set
	 * @return \zesk\Application|\zesk\Command
	 */
	public function command(Command $set = null) {
		if ($set !== null) {
			$this->command = $set;
			$this->call_hook("command", $set);
			return $this;
		}
		return $this->command;
	}

	/**
	 * Override in subclasses if it is stored in a different way.
	 *
	 * @return string|null|self
	 */
	public function version($set = null) {
		if ($set !== null) {
			$this->set_option(self::OPTION_VERSION, $set);
			return $this;
		}
		return $this->option(self::OPTION_VERSION);
	}

	/**
	 * Expand a list of include files
	 *
	 * @param array $includes
	 * @return string[]
	 */
	private function expand_includes(array $includes) {
		$result = array();
		foreach ($includes as $include) {
			$expand = $this->paths->expand($include);
			$result[$expand] = $expand;
		}
		return $result;
	}

	/**
	 * Getter/setter to configure a file name to load (from path)
	 *
	 * Configuration files can use values which are expanded:
	 *
	 *     /etc/app.json Is absolute
	 *     ./etc/app.json Is application-root relative
	 *     ~/.app/app.json Is user-home relative
	 *
	 * @param mixed $includes An iterator which generates a list of include files to load.
	 * @param boolean $reset When false, adds to the existing include list
	 * @return Application|array
	 */
	final public function configure_include($includes = null, $reset = false) {
		if ($includes === null) {
			return $this->includes;
		}
		$includes = $this->expand_includes(to_list($includes));
		if ($reset) {
			$this->includes = $includes;
		} else {
			$this->includes += $includes;
		}
		return $this;
	}

	/**
	 * Loads a bunch of configuration files, in the following order:
	 * 1. application.conf
	 * 2. honst/*uname*.conf
	 *
	 * Configuration files are simple bash-style NAME=VALUE files with a few features:
	 * - You can use variables in values, like ${FOO} or $FOO; once loaded, the variable is replaced
	 * and no longer part
	 * of the value.
	 * - Values are unquoted automatically, and assumed to be strings
	 * - Unquoted values are coerced to an internal PHP type, if possible
	 * - Loads from the list of ZESK_CONFIG_PATH global
	 * - Loads the files defined in ZESK_CONFIG_FILE global
	 * - If ZESK_CONFIG_PATH changes or INCLUDE changes in a configuration file load sequence, it
	 * continues to load
	 */
	final public function configure(array $options = array()) {
		if ($this->configuration_options !== null) {
			$this->logger->warning("Reconfiguring application {class}", array(
				"class" => get_class($this),
			));
		}
		$this->configuration->deprecated("Application::configure_options", __CLASS__ . "::configure_options");
		$this->configuration_options = $options + to_array($this->configuration->path(__CLASS__)->configure_options);
		$this->_configure($this->configuration_options);
		return $this;
	}

	/**
	 * Run preconfiguration setup
	 */
	protected function preconfigure(array $options) {
		return $options;
	}

	/**
	 * Run post configuration setup
	 */
	protected function postconfigure() {
	}

	/**
	 * Load configuration files
	 *
	 * @param array $options
	 */
	private function _configure_files(array $options) {
		$configuration = $this->configuration;

		if (count($this->includes) === 0 || array_key_exists('file', $options)) {
			$this->configure_include(avalue($options, 'includes', avalue($options, 'file', $this->default_includes())));
		}
		$includes = $this->includes;
		$files = array();
		foreach ($includes as $index => $file) {
			$file = $this->paths->expand($file);
			if (File::is_absolute($file)) {
				if (is_file($file)) {
					$files[] = $file;
				}
				unset($includes[$index]);
			}
		}

		$this->loader = new Configuration_Loader($files, new Adapter_Settings_Configuration($configuration));

		$this->loader->load();

		$configuration->deprecated("host_aliases");
		$configuration->deprecated(__CLASS__ . "::host_aliases");
		$configuration->deprecated("maintenance_file");
	}

	/**
	 * Complete configuration process
	 *
	 * @param array $options
	 * @return number
	 */
	private function _configure(array $options) {
		$skip_configured_hook = avalue($options, 'skip_configured', false);

		// Load hooks
		$this->hooks->register_class($this->register_hooks);

		$application = $this;
		$this->hooks->add(Hooks::HOOK_EXIT, function () use ($application) {
			if ($application->cache) {
				$application->cache->commit();
			}
		}, array(
			"last" => true,
		));

		$this->configure_cache_paths(); // Initial cache paths are set up

		$new_options = $this->preconfigure($options);
		if (is_array($new_options)) {
			$options = $new_options;
		}

		$profile = false;
		if ($profile) {
			$mtime = microtime(true);
			// Old conf:: version (PHP5)
			// 0.011868953704834
			// 0.012212038040161
			// New conf/PHP7
			// 0.0060791969299316
		}
		$this->_configure_files($options);
		if ($profile) {
			$this->kernel->profile_timer("_configure_files", microtime(true) - $mtime);
		}

		// Apply settings from loaded configuration to make settings available to hook_configured_files
		$this->inherit_global_options();
		if ($this->has_hook("configured_files")) {
			$this->call_hook('configured_files');
			// Repopulate Application options after final configurations are loaded
			$this->inherit_global_options();
		}
		$this->modules->load($this->load_modules);

		// Load dynamic modules now
		$modules = $this->option_list('modules');
		if (count($modules) > 0) {
			$this->modules->load($modules);
		}

		// Final cache paths are set up from application options
		$this->configure_cache_paths();

		if (!$skip_configured_hook) {
			$this->configured();
		}
	}

	/**
	 *
	 * @return boolean
	 */
	public function is_configured() {
		return $this->configured_was_run;
	}

	/**
	 * Run fini
	 * @param string $force
	 * @return boolean
	 */
	public function configured($force = false) {
		if ($force || !$this->configured_was_run) {
			$this->_configured();
			$this->configured_was_run = true;
			return true;
		}
		return false;
	}

	private function _configured() {
		// Now run all configurations: System, Modules, then Application
		$this->configured_hooks();
		$this->postconfigure();

		$this->configured_was_run = true;
	}

	/**
	 */
	private function configured_compatibility() {
		$this->configuration->deprecated("Router::cache", __CLASS__ . "::cache_router");
	}

	/**
	 */
	private function configure_cache_paths() {
		$cache_path = $this->option("cache_path", $this->paths->cache());
		$this->cache_path = Directory::is_absolute($cache_path) ? $cache_path : $this->path($cache_path);
		if ($this->has_option('document_cache')) {
			$this->document_cache = $this->paths->expand($this->option('document_cache'));
		}
	}

	/**
	 */
	private function configured_hooks() {
		$hook_callback = $result_callback = null;
		$this->hooks->call_arguments(Hooks::HOOK_DATABASE_CONFIGURE, array(
			$this,
		), null, $hook_callback, $result_callback);
		$this->hooks->call_arguments(Hooks::HOOK_CONFIGURED, array(
			$this,
		), null, $hook_callback, $result_callback); // System level
		$this->modules->all_hook_arguments(Hooks::HOOK_CONFIGURED, array(), null, $hook_callback, $result_callback); // Modules
		$this->call_hook_arguments(Hooks::HOOK_CONFIGURED, array(), null, $hook_callback, $result_callback); // Application level
	}

	/**
	 * Runs configuration again, using same options as previous configuration.
	 *
	 * @see Application::configure
	 */
	public function reconfigure() {
		$this->hooks->call(Hooks::HOOK_RESET, $this);
		$modules = array_keys(array_filter($this->modules->loaded()));
		$this->_initialize($this->kernel);
		$result = $this->_configure(to_array($this->configuration_options));
		$this->modules->reload();
		$this->_configured();
		return $result;
	}

	/**
	 *
	 * @param CacheItemPoolInterface $cahe
	 * @return \zesk\Application
	 */
	final public function set_cache(CacheItemPoolInterface $interface) {
		$this->cache = $interface;
		$this->call_hook("set_cache");
		return $this;
	}

	/**
	 * Clear application cache
	 */
	final public function cache_clear() {
		foreach (array_unique(array(
			$this->paths->cache(),
			$this->cache_path(),
			$this->document_cache(),
		)) as $path) {
			if (empty($path)) {
				continue;
			}
			$size = Directory::size($path);
			if ($size > 0) {
				Directory::delete_contents($path);
				$this->logger->notice("Deleted {size} bytes in {path}", compact("size", "path"));
			} else {
				$this->logger->notice("{path} is empty.", compact("size", "path"));
			}
		}
		$this->call_hook('cache_clear');
		$hooks = $this->modules->all_hook_list("cache_clear");
		$this->logger->notice("Running {cache_clear_hooks}", array(
			"cache_clear_hooks" => $this->format_hooks($hooks),
		));
		$this->modules->all_hook("cache_clear", $this);
		$controllers = $this->controllers();
		foreach ($controllers as $controller) {
			$controller->call_hook('cache_clear');
		}
	}

	private function format_hooks(array $hooks) {
		$result = array();
		foreach ($hooks as $hook) {
			$result[] = $this->hooks->callable_string($hook);
		}
		return $result;
	}

	/**
	 * Get/set maintenance flag
	 *
	 * @param string $set
	 * @return boolean Ambigous array, string, number>
	 */
	final public function maintenance($set = null) {
		$maintenance_file = $this->maintenance_file();
		if ($set === null) {
			return $this->option_path('maintenance.enabled', false);
		}
		$result = $this->call_hook_arguments("maintenance", array(
			$set,
		), true);
		if (!$result) {
			$this->logger->error("Unable to set application {application_class} maintenance mode to {value}", array(
				"application_class" => get_class($this),
				"value" => $set ? "true" : "false",
			));
			return null;
		}
		if ($set) {
			$context = array(
				"time" => date('Y-m-d H:i:s'),
			) + $this->call_hook_arguments("maintenance_context", array(
				array(
					"value" => $set,
				),
			), array());
			file_put_contents($this->maintenance_file(), JSON::encode($context));
		} elseif (file_exists($maintenance_file)) {
			unlink($maintenance_file);
			clearstatcache(true, $maintenance_file);
		}
		return $result;
	}

	/**
	 * Load the maintenance JSON file
	 *
	 * @return array
	 */
	private function _load_maintenance() {
		$file = $this->maintenance_file();
		if (!file_exists($file)) {
			$result = array(
				'enabled' => false,
			);
		} else {
			try {
				$result = JSON::decode(file_get_contents($file));
			} catch (Exception_Parse $e) {
				$result = array(
					'error' => 'Unabe to parse maintenance file',
				);
			}
			$result = array(
				'enabled' => true,
			) + $result;
		}
		return $result;
	}

	/**
	 * Return file, which when exists, puts the site into maintenance mode.
	 *
	 * Always a JSON file
	 *
	 * @return string
	 */
	final private function maintenance_file() {
		return $this->option("maintenance_file", $this->path("etc/maintenance.json"));
	}

	/**
	 *
	 * @deprecated 2017-12 use model_singleton
	 * @param unknown $class
	 * @return unknown|object|\zesk\NULL|mixed
	 */
	final public function object_singleton($class) {
		$this->deprecated();
		$args = func_get_args();
		$args[0] = $this;
		$object = $this->call_hook_arguments("singleton_$class", $args, null);
		if ($object instanceof $class) {
			return $object;
		}
		return $this->objects->singleton_arguments($class, $args);
	}

	/**
	 * Override this in child classes to manipulate creation of these objects.
	 * Creates objects which take the application
	 * as the first parameter, and handles passing that on.
	 *
	 * Also optionally calls `zesk\Application::singleton_$class`
	 *
	 * @param string $class
	 * @return Model
	 */
	final public function model_singleton($class) {
		$args = func_get_args();
		$args[0] = $this;
		$suffix = PHP::clean_function($desired_class = $this->objects->resolve($class));
		$object = $this->call_hook_arguments("singleton_$suffix", $args, null);
		if ($object instanceof $desired_class) {
			return $object;
		}
		return $this->objects->singleton_arguments($desired_class, $args);
	}

	/**
	 * Default list of files to be loaded as part of this application configuration
	 *
	 * @return array
	 */
	private function default_includes() {
		$files_default = array();
		$files_default[] = $this->path('etc/application.json');
		$files_default[] = $this->path('etc/host/' . strtolower(System::uname()) . ".json");
		return $files_default;
	}

	/**
	 * Creates a default reqeust for the application. Useful in self::main
	 *
	 * Returns generic GET http://console/ when in the console.
	 *
	 * @return Request
	 */
	public function request_factory(Request $inherit = null) {
		$request = new Request($this);
		if ($inherit) {
			$request->initialize_from_request($inherit);
		} elseif ($this->console()) {
			$request->initialize_from_settings("http://console/");
		} else {
			$request->initialize_from_globals();
		}
		return $request;
	}

	/**
	 *
	 * @return Router
	 */
	protected function hook_router() {
		$router_file = File::extension_change($this->file, 'router');
		$exists = is_file($router_file);
		$cache = $this->option("cache_router", null);

		$router = Router::factory($this);
		if (!$exists) {
			$this->logger->debug("No router file {router_file} to load - creating blank router", array(
				"router_file" => $router_file,
			));
			$result = $router;
		} else {
			$mtime = filemtime($router_file);
			if (($result = $router->cached($mtime)) === null) {
				$parser = new Parser(file_get_contents($router_file), $router_file);
				$parser->execute($router, array(
					"_source" => $router_file,
				));
				if ($cache) {
					$router->cache($mtime);
				}
				$result = $router;
			}
		}
		$this->modules->all_hook("routes", $result);
		return $result;
	}

	/**
	 *
	 * @param Request $request
	 * @param unknown $content_type
	 * @return \zesk\Response
	 */
	final public function response_factory(Request $request, $content_type = null) {
		return Response::factory($this, $request, $content_type ? array(
			"content_type" => $content_type,
		) : array());
	}

	/**
	 *
	 * @param Request $request
	 * @param \Exception $exception
	 * @return \zesk\Response
	 */
	final private function main_exception(Request $request, \Exception $exception) {
		$response = $this->response_factory($request, Response::CONTENT_TYPE_HTML);

		try {
			$response->content = $this->theme($this->classes->hierarchy($exception), array(
				"request" => $request,
				"response" => $response,
				"exception" => $exception,
				"content" => $exception,
			) + Exception::exception_variables($exception), array(
				"first" => true,
			));
			if (!$exception instanceof Exception_Redirect) {
				$this->hooks->call("exception", $exception);
			}
			$this->call_hook('main_exception', $exception, $response);
		} catch (Exception_Redirect $e) {
			$response->redirect()->handle_exception($e);
		}
		return $response;
	}

	/**
	 * Returns the current executing request. May be NULL if no request running.
	 *
	 * If performing sub-requests, this reflects the most-recent request state (a stack).
	 *
	 * @return Request
	 */
	final public function request() {
		return last($this->request_stack);
	}

	/**
	 * Load router
	 *
	 * @return Router NULL
	 */
	final public function router() {
		if ($this->router instanceof Router) {
			return $this->router;
		}
		/* @var $router Router */
		$router = $this->router = $this->call_hook("router");
		$this->call_hook("router_loaded", $router);
		return $router;
	}

	/**
	 * Return all known/discerable Controllers for the application.
	 *
	 * Potentially slow.
	 *
	 * @return array of Controller
	 */
	final public function controllers() {
		return $this->router()->controllers($this);
	}

	/**
	 * Initialize variables
	 *
	 * @return boolean
	 */
	private function _templates_initialize(array $variables) {
		$variables['application'] = $this;
		$variables += $this->template_variables;
		$variables += $this->call_hook_arguments("template_defaults", array(
			$variables,
		), array());
		$this->template->set($variables);
		return $this->template;
	}

	/**
	 *
	 * @param Request $request
	 * @throws Exception_NotFound
	 * @return Route
	 */
	private function determine_route(Request $request) {
		$router = $this->router();
		$this->logger->debug("App bootstrap took {seconds} seconds", array(
			"seconds" => sprintf("%.3f", microtime(true) - $this->kernel->initialization_time),
		));
		$this->call_hook("router_prematch", $router, $request);
		$route = $router->match($request);
		$this->_templates_initialize(array(
			"router" => $router,
			"route" => $route,
			"request" => $request,
		));
		if (!$route) {
			$this->call_hook("router_no_match", $request, $router);

			throw new Exception_NotFound("The resource does not exist on this server: {url}", $request->url_variables());
		}
		if ($this->option_bool("debug_route")) {
			$this->logger->debug("Matched route {class} Pattern: \"{clean_pattern}\" {options}", $route->variables());
		}
		$new_route = $this->call_hook_arguments("router_matched", array(
			$request,
			$router,
			$route,
		), null);
		if ($new_route instanceof Route) {
			$route = $new_route;
		}
		return $route;
	}

	/**
	 * Application main execution:
	 *
	 * - Load the router
	 * - Find a matched route
	 * - Execute it
	 * - Return response
	 */
	public function main(Request $request) {
		$starting_depth = count($this->request_stack);

		try {
			$response = $this->call_hook("main", $request);
			if ($response instanceof Response) {
				return $response;
			}
			$this->request_stack[] = $request;
			$starting_depth = count($this->request_stack);
			$route = $this->determine_route($request);
			$response = $route->execute($request);
		} catch (\Exception $exception) {
			$response = $this->main_exception($request, $exception);
		}
		$ending_depth = count($this->request_stack);
		if ($ending_depth !== $starting_depth) {
			$this->logger->error("Request ending depth mismatch start {starting_depth} !== end {ending_depth}", array(
				"starting_depth" => $starting_depth,
				"ending_depth" => $ending_depth,
			));
		}
		if ($ending_depth !== 0) {
			$popped = array_pop($this->request_stack);
			if ($popped !== $request) {
				$this->logger->error("Request changed between push and pop? {origial} => {popped}", array(
					"original" => $request->variables(),
					"popped" => $popped->variables(),
				));
			}
		}
		return $response;
	}

	/**
	 * Utility for index.php file for all public-served content.
	 */
	final public function content($path) {
		if (isset($this->content_recursion[$path])) {
			return "";
		}
		$this->content_recursion[$path] = true;
		$this->call_hook("content");

		$router = $this->router();
		$url = "http://localhost/";
		$url = rtrim(URL::left_host($url), "/") . $path;
		$request = new Request($this);
		$request->initialize_from_settings(array(
			"url" => $url,
			"method" => Net_HTTP::METHOD_GET,
			"data" => "",
			"variables" => URL::query_parse_url($path),
		));
		$response = $this->main($request);
		ob_start();
		$response->output(array(
			"skip_headers" => true,
		));
		$content = ob_get_clean();
		unset($this->content_recursion[$path]);
		return $content;
	}

	/**
	 * Get a list of repositories for this application (dependencies)
	 *
	 * @return array
	 */
	public function repositories() {
		$repos = array(
			'zesk' => $this->zesk_home(),
			get_class($this) => $this->path(),
		);
		return $this->call_hook_arguments("repositories", array(
			$repos,
		), $repos);
	}

	/**
	 * Utility for index.php file for all public-served content.
	 *
	 * KMD 2018-01 Made this more Response-centric and less content-centric
	 */
	public function index() {
		$final_map = array();

		$request = $this->request_factory();
		$this->call_hook("request", $request);
		$options = array();
		if (($response = Response::cached($this->cache, $url = $request->url())) === null) {
			$response = $this->main($request);
			$response->cache_save($this->cache, $url);
			$final_map['{page-is-cached}'] = '0';
		} else {
			$options['skip_hooks'] = true;
			$this->hooks->unhook('exit');
			$final_map['{page-is-cached}'] = '1';
		}
		$final_map += array(
			"{page-render-time}" => sprintf("%.3f", microtime(true) - $this->kernel->initialization_time),
		);
		if (!$response || $response->is_content_type(array(
			"text/",
			"javascript",
		))) {
			$response->content = strtr($response->content, $final_map);
		}
		$response->output($options);
		return $response;
	}

	/**
	 * Template or logging variables
	 *
	 * @return Router
	 */
	public function variables() {
		$parameters['application'] = $this;
		$parameters['router'] = $this->router;
		// Do not include "request" in here as it may be NULL and it should NOT override subclass values
		return $parameters;
	}

	/**
	 * Retrieve the list of theme file paths, or add a path to be searched before existing paths
	 * (first in the list).
	 *
	 * @param string $add
	 *        	(Optional) Path to add to the theme path. Pass in null to do nothing.
	 * @param string $prefix
	 *        	(Optional) Handle theme requests which begin with this prefix. Saves having deep
	 *        	directories.
	 * @return array The ordered list of paths to search for theme files as prefix => search list.
	 */
	final public function theme_path($add = null, $prefix = null) {
		if (is_bool($prefix)) {
			throw new Exception_Parameter("theme_path now takes a string as the 2nd parameter");
		}
		if (is_array($add)) {
			foreach ($add as $k => $v) {
				if (is_numeric($k)) {
					$this->theme_path($v);
				} else {
					$this->theme_path($v, $k);
				}
			}
			return $this->theme_path;
		}
		if ($add) {
			$prefix = strval($prefix);
			if (!isset($this->theme_path[$prefix])) {
				$this->theme_path[$prefix] = array();
			}
			if (!in_array($add, $this->theme_path[$prefix])) {
				array_unshift($this->theme_path[$prefix], $add);
			}
		}
		return $this->theme_path;
	}

	/**
	 * Search the theme paths for a target file
	 *
	 * @param string $file
	 * @param boolean $first
	 * @return string|string[]
	 */
	final public function theme_find($theme, array $options = array()) {
		$extension = to_bool(avalue($options, "no_extension")) ? "" : $this->option("theme_extension", ".tpl");
		$all = to_bool(avalue($options, "all"));
		$theme = $this->clean_template_path($theme) . $extension;
		$theme_path = $this->theme_path();
		$prefixes = array_keys($theme_path);
		usort($prefixes, function ($a, $b) {
			return strlen($b) - strlen($a);
		});
		$result = array();
		foreach ($prefixes as $prefix) {
			if ($prefix === "" || strpos($theme, $prefix) === 0) {
				$suffix = substr($theme, strlen($prefix));
				foreach ($theme_path[$prefix] as $path) {
					$path = path($path, $suffix);
					if (file_exists($path)) {
						if (!$all) {
							return $path;
						}
						$result[] = $path;
					} else {
						$tried_path[] = $path;
					}
				}
			}
		}
		if (!$all && count($result) === 0) {
			return null;
		}
		return array(
			$result,
			$tried_path,
		);
	}

	/**
	 * Add or retrieve the share path for this application - used to serve
	 * shared content via Controller_Share as well as populate automatically with files within the
	 * system.
	 *
	 * By default, it's /share/
	 *
	 * @param unknown $add
	 * @param unknown $name
	 * @return array
	 */
	final public function share_path($add = null, $name = null) {
		$list = $this->share_path;
		if ($add) {
			if (!is_dir($add)) {
				throw new Exception_Directory_NotFound($add);
			}
			$this->share_path[$name] = $add;
		}
		return $this->share_path;
	}

	/**
	 * Setter for locale - calls hook
	 *
	 * @param Locale $set
	 * @return \zesk\Locale|\zesk\Application
	 */
	public function set_locale(Locale $set) {
		$this->locale = $set;
		$this->call_hook("set_locale", $set);
		return $this;
	}

	/**
	 * Create a new `zesk\Locale`
	 *
	 * @param string $code
	 * @param array $extensions
	 * @param array $options
	 * @return \zesk\Locale
	 */
	public function locale_factory($code = null, array $extensions = array(), array $options = array()) {
		return Reader::factory($this->locale_path(), $code, $extensions)->locale($this, $options);
	}

	/**
	 * Create a `zesk\Locale` if it has not been encountered in this process and cache it as part of the `Application`
	 *
	 * @param string $code
	 * @param array $extensions
	 * @param array $options
	 * @return \zesk\Locale
	 */
	public function locale_registry($code = null, array $extensions = array(), array $options = array()) {
		$code = Locale::normalize($code);
		if (isset($this->locales[$code])) {
			return $this->locales[$code];
		}
		return $this->locales[$code] = $this->locale_factory($code);
	}

	/**
	 * Add or retrieve the locale path for this application - used to load locales
	 *
	 * By default, it's ./etc/language/
	 *
	 * @param string $add Locale path to add
	 * @return array
	 */
	final public function locale_path($add = null) {
		$list = $this->locale_path;
		if ($add) {
			$add = $this->paths->expand($add);
			if (!is_dir($add)) {
				throw new Exception_Directory_NotFound($add);
			}
			$this->locale_path[] = $add;
		}
		return $this->locale_path;
	}

	/**
	 * Add or retrieve the data path for this application
	 *
	 * @param string $add
	 *        	Value to set
	 * @return string Current data_path value
	 */
	final public function data_path($suffix = null) {
		return $this->paths->data($suffix);
	}

	/**
	 * Get or set the zesk command path, which is where Zesk searches for commands from the
	 * command-line tool.
	 *
	 * As of 0.8.2, command paths store a class prefix
	 *
	 * The default path is ZESK_ROOT 'classes/command', but applications can add their own tools
	 * upon initialization.
	 *
	 * This call always returns the complete path, even when adding. Note that adding a path which
	 * does not exist has no effect.
	 *
	 * @param mixed $add
	 *        	A path or array of paths => prefixes to add. (Optional)
	 * @param string $prefix
	 *        	The class prefix to add to found files in this directory (defaults to
	 *        	"zesk\Command_")
	 * @global boolean debug.zesk_command_path Whether to log errors occurring during this call
	 * @return array
	 * @throws Exception_Directory_NotFound
	 */
	final public function zesk_command_path($add = null, $prefix = null) {
		if ($add !== null) {
			if (!$prefix) {
				$prefix = "zesk\Command_";
			}
			$debug = $this->debug;
			$add = to_list($add, array(), ":");
			foreach ($add as $path) {
				if ($debug && !is_dir($path)) {
					$this->logger->warning("{method}: adding path \"{path}\" was not found", array(
						"method" => __METHOD__,
						"path" => $path,
					));
				}
				if (!isset($this->zesk_command_path[$path])) {
					$this->zesk_command_path[$path] = $prefix;
				} elseif ($debug) {
					$this->logger->debug("{method}: did not add \"{path}\" (prefix {prefix}) because it already exists", array(
						"method" => __METHOD__,
						"path" => $path,
						"prefix" => $prefix,
					));
				}
			}
		}
		return $this->zesk_command_path;
	}

	/**
	 *
	 * @return NULL|mixed
	 */
	final public function theme_current() {
		return last($this->theme_stack);
	}

	/**
	 * Getter/setter for top theme variable
	 * @param string|array|Traversable $name
	 * @param mixed|null $value
	 * @return mixed|self
	 */
	final public function theme_variable($name = null, $value = null) {
		if ($name === null) {
			return $this->template_stack->top()->variables();
		}
		if ($value === null) {
			return $this->template_stack->top()->get($name);
		}
		if (can_iterate($name)) {
			foreach ($name as $k => $v) {
				$this->theme_variable($k, $v);
			}
			return $this;
		}
		$this->template_stack->top()->set($name, $value);
		return $this;
	}

	/**
	 * theme an element
	 *
	 * @param string $type
	 * @return string
	 */
	final public function theme($types, $arguments = array(), array $options = array()) {
		if (!is_array($arguments)) {
			$arguments = array(
				"content" => $arguments,
			);
		}
		$arguments['application'] = $this;
		$arguments['locale'] = $this->locale;

		$types = to_list($types);
		$extension = avalue($options, "no_extension") ? null : ".tpl";
		if (count($types) === 1) {
			$result = $this->_theme_arguments($types[0], $arguments, null, $extension);
			if ($result === null) {
				$this->logger->warning("Theme {type} had no output", array(
					"type" => $types[0],
				));
			}
			return $result;
		}
		if (!is_array($types)) {
			throw new Exception_Parameter("Application::theme: \$types is " . gettype($types));
		}
		if (count($types) === 0) {
			return avalue($options, 'default', null);
		}
		$type = array_shift($types);
		$arguments['content_previous'] = null;
		$has_output = false;
		$content = $this->_theme_arguments($type, $arguments, null, $extension);
		if (!is_array($types)) {
			// Something's fucked.
			return $content;
		}
		if ($content !== null) {
			$arguments['content'] = $content;
			$has_output = true;
		}
		$first = avalue($options, 'first', false);
		$concatenate = avalue($options, 'concatenate', false);
		// 2019-01-15 PHP 7.2 $types converts to a string with value "array()" upon throwing a foreign Exception and rendering the theme
		while (is_countable($types) && count($types) > 0) {
			if ($first && !empty($content)) {
				break;
			}
			$type = array_shift($types);
			$content_previous = $content;
			$content_next = $this->_theme_arguments($type, $arguments, $content, $extension);
			if ($content_next !== null) {
				$has_output = true;
			}
			$content = $concatenate ? $content . $content_next : $content_next;
			$arguments['content_previous'] = $content_previous;
			$arguments['content'] = $content;
		}
		if (!$has_output) {
			$this->logger->warning("Theme {types} had no output ({details})", array(
				"types" => $types,
				"details" => _backtrace(),
			));
		}
		return $content;
	}

	/**
	 * Convert from a theme name to a pathname
	 *
	 * @param string $path
	 * @return mixed
	 */
	private function clean_template_path($path) {
		return preg_replace("%[^-_./a-zA-Z0-9]%", '_', strtr(strtolower($path), array(
			"_" => "/",
			"\\" => "/",
		)));
	}

	/**
	 * Invoke a single theme type
	 *
	 * @param string $type
	 * @param array $args
	 * @param string $content
	 *        	Default content
	 * @return string
	 */
	private function _theme_arguments($type, array $args, $content = null, $extension = ".tpl") {
		if (!empty($extension) && $this->development() && ends($type, $extension)) {
			throw new Exception_Semantics("Theme called with .tpl suffix - not required {type}", compact("type"));
		}
		$type = strtolower($type);
		array_push($this->theme_stack, $type);
		$t = new Template($this, $this->clean_template_path($type) . $extension, $args);
		if ($t->exists()) {
			$content = $t->render();
		}
		array_pop($this->theme_stack);
		return $content;
	}

	/**
	 * Does one or more themes exist?
	 *
	 * @param mixed $types
	 *        	List of themes
	 * @return boolean If all exist, returns true, otherwise false
	 */
	final public function theme_exists($types, array $args = array(), array $options = array()) {
		if (empty($types)) {
			return false;
		}
		$types = to_list($types);
		foreach ($types as $type) {
			if (!$this->_theme_exists($type, $args, $options)) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Returns similar result as _theme_arguments except just tests to see if theme would
	 * possibly generate content
	 *
	 * @param mixed $type
	 * @return boolean
	 */
	private function _theme_exists($type, array $args, array $options) {
		$type = strtolower($type);
		$object = avalue($args, "content");
		if (is_object($object) && method_exists($object, "hook_theme")) {
			return true;
		}
		// TODO is this called?
		if ($this->hooks->has("theme_${type}")) {
			return true;
		}
		if ($this->theme_find($type)) {
			return true;
		}
		return false;
	}

	/**
	 * Set autoload path for the application.
	 *
	 * @param mixed $add
	 * @param array $options
	 * @return array The ordered list of paths to search for class names
	 */
	final public function autoload_path($add = null, $options = true) {
		return $this->autoloader->path($add, $options);
	}

	/**
	 * Get or set the command path for the application.
	 *
	 * @param mixed $add
	 * @param string $options
	 * @return array The ordered list of paths to search for class names
	 */
	final public function command_path($add = null) {
		return $this->paths->command($add);
	}

	/**
	 * Register a class with the application to make it discoverable and
	 * to register any hooks.
	 *
	 * @param string $class
	 * @return array This class name and parent classes
	 */
	final public function register_class($class) {
		$this->hooks->register_class($class);
		return $this->classes->register($class);
	}

	/**
	 * Return a path relative to the application root
	 */
	final public function path($suffix = null) {
		return $this->paths->application($suffix);
	}

	/**
	 * Return the application root path.
	 *
	 * @param string $suffix
	 *        	Optional path to add to the application path
	 * @return string
	 */
	final public function application_class() {
		return $this->kernel->application_class();
	}

	/**
	 *
	 * @param string $path
	 * @return \zesk\Application
	 */
	final public function set_application_root($path) {
		$this->paths->set_application($path, true);
		return $this;
	}

	/**
	 * Return the zesk home path, usually used to load built-in themes directly.
	 *
	 * @param string $suffix
	 *        	Optional path to add to the application path
	 * @return string
	 */
	final public function zesk_home($suffix = null) {
		return $this->paths->zesk($suffix);
	}

	/**
	 * Get the cache path for the application
	 *
	 * @return string
	 * @param unknown $add
	 */
	final public function cache_path($suffix = null) {
		return path($this->cache_path, $suffix);
	}

	/**
	 * Initialize web root to enable non-rooted web sites.
	 * This should be called from any script which interacts with
	 * files on the web path or any script which is invoked from the web. Ideally, it should be in
	 * your application initialization code. It determines the web root from
	 * $_SERVER['DOCUMENT_ROOT'] so if your web
	 * server doesn't support setting this or you are invoking it from a script (which, for example,
	 * manipulates
	 * files which depend on this being set correctly) then you should initialize it with
	 *
	 * $application->set_document_root(...)
	 *
	 * Currently things which use this are: TODO
	 *
	 * @throws Exception_Directory_NotFound
	 *
	 * @param string $document_root_prefix
	 */
	private function _init_document_root() {
		$http_document_root = rtrim(avalue($_SERVER, 'DOCUMENT_ROOT'), '/');
		if ($http_document_root) {
			$this->set_document_root($http_document_root);
		}
		$this->document_cache = $this->document ? path($this->document, "cache") : null;
	}

	/**
	 * Your web root is the directory in the file system which contains our application and other
	 * files.
	 *
	 * It may be served from an aliased or shared directory and as such may not appear at the web
	 * server's root.
	 *
	 * To ensure all URLs are generated correctly, you can set $this->set_document_root_prefix() to
	 * set
	 * a portion of the URL which is always prefixed to any generated url.
	 *
	 * @param string $suffix ptionally append to web root
	 * @return string Path relative to document root
	 */
	final public function document_root($suffix = null) {
		return path($this->document, $suffix);
	}

	/**
	 * Your web root is the directory in the file system which contains our application and other
	 * files.
	 *
	 * It may be served from an aliased or shared directory and as such may not appear at the web
	 * server's root.
	 *
	 * To ensure all URLs are generated correctly, you can set $this->document_root_prefix(string) to set
	 * a portion of the URL which is always prefixed to any generated url.
	 *
	 * @param string $set
	 *        	Optionally set the web root
	 * @throws Exception_Directory_NotFound
	 * @return self
	 */
	final public function set_document_root($set, $prefix = null) {
		if (!is_dir($set)) {
			throw new Exception_Directory_NotFound($set);
		}
		$set = rtrim($set, '/');
		$this->document = $set;
		if ($prefix !== null) {
			$this->document_prefix($prefix);
		}
		return $this;
	}

	/**
	 * Your web root may be served from an aliased or shared directory and as such may not appear at
	 * the web server's root.
	 *
	 * To ensure all URLs are generated correctly, you can set a portion of the URL which is always
	 * prefixed to any generated url.
	 *
	 * @param string $set
	 *        	Optionally set the web root
	 * @throws Exception_Directory_NotFound
	 * @return string The directory
	 * @todo should this be urlescpaed by web_root_prefix function to avoid & and + to be set?
	 */
	final public function document_root_prefix($set = null) {
		if ($set !== null) {
			$this->document_prefix = rtrim($set, '/');
			return $this;
		}
		return $this->document_prefix;
	}

	/**
	 * Directory of the path to files which can be served from the webserver.
	 * Used for caching CSS or
	 * other resources. Should not serve any links to this path.
	 *
	 * Default document cache path is $this->document_root("cache")
	 *
	 * @param string $set
	 *        	Set the document cache
	 * @return string
	 */
	final public function document_cache($suffix = null) {
		return path($this->document_cache, $suffix);
	}

	/**
	 * Get or set the module search path
	 *
	 * @param string $add
	 * @return string[] List of paths searched
	 */
	final public function module_path($add = null) {
		if ($add !== null) {
			if (!is_dir($add)) {
				throw new Exception_Directory_NotFound($add);
			}
			$this->module_path[] = $add;
		}
		return $this->module_path;
	}

	/**
	 * Return the development status of this application
	 *
	 * @param boolean $set
	 *        	Optionally set value
	 * @return boolean
	 */
	public function development($set = null) {
		if (is_bool($set)) {
			return $this->set_option("development", to_bool($set));
		}
		return $this->option_bool("development");
	}

	/**
	 * Create a model
	 *
	 * @param string $class
	 * @param array $options
	 * @return Model
	 */
	public function model_factory($class, $mixed = null, array $options = array()) {
		return Model::factory($this, $class, $mixed, $options);
	}

	/**
	 * Create a model
	 *
	 * @param string $class
	 * @param array $options
	 * @return Model
	 */
	public function member_model_factory($member, $class, $mixed = null, array $options = array()) {
		return Model::factory($this, $class, $mixed, array(
			"_member" => $member,
		) + $options);
	}

	/**
	 * Create a generic object
	 *
	 * @param string $class
	 * @return object
	 */
	public function factory($class) {
		$arguments = func_get_args();
		$class = array_shift($arguments);
		return $this->objects->factory_arguments($class, $arguments);
	}

	/**
	 * This loads an include without the $application variable defined, and $this which is also an Application.
	 * is meant to return a value, or has its own "internal" variables which may corrupt the global or current scope of
	 * a function, for example.
	 *
	 * @param string $__file__
	 *        	File to include
	 * @return mixed Whatever is returned by the include file
	 */
	public function load($__file__) {
		$application = $this;
		return include $__file__;
	}

	/**
	 * Create a generic object
	 *
	 * @param string $class
	 * @return object
	 */
	public function factory_arguments($class, array $arguments = array()) {
		return $this->objects->factory_arguments($class, $arguments);
	}

	/**
	 *
	 * @param boolean $require
	 *        	Throw exception if no session found
	 * @throws Exception_NotFound
	 * @return \zesk\Interface_Session
	 */
	public function session(Request $request, $require = true) {
		if ($request->has_option(__METHOD__)) {
			return $request->option(__METHOD__);
		}
		if (!$require) {
			return null;
		}
		$session = $this->session_factory();
		$session->initialize_session($request);
		$request->set_option(__METHOD__, $session);
		return $session;
	}

	/**
	 * Uses current application Request for authentication if not supplied.
	 *
	 * @param Request $request Request to use for
	 * @param boolean $require Force object creation if not found. May have side effect of creating a Session_Interface within the Request.
	 * @return \zesk\User
	 */
	public function user(Request $request = null, $require = true) {
		if ($request === null) {
			$request = $this->request();
		}
		if ($request) {
			$session = $this->session($request, $require);
			if ($session) {
				$user = $session->user();
				if ($user) {
					return $user;
				}
			}
		}
		if (!$require) {
			return null;
		}
		return $this->model_singleton(User::class);
	}

	/**
	 *
	 * @param string $message
	 * @param array $arguments
	 * @return void
	 */
	public function deprecated($message = null, array $arguments = array()) {
		$arguments['depth'] = to_integer(avalue($arguments, 'depth', 0)) + 1;
		$this->kernel->deprecated($message, $arguments);
	}

	/**
	 * Console status getter/setter
	 *
	 * @param boolean $set
	 * @return boolean
	 */
	public function console($set = null) {
		return $this->kernel->console($set);
	}

	/**
	 *
	 * @return double Microseconds initialization time
	 */
	final public function initialization_time() {
		return $this->kernel->initialization_time;
	}

	/**
	 *
	 * @return string
	 */
	final public function kernel_copyright_holder() {
		return $this->kernel->copyright_holder();
	}

	/**
	 * Add support for generic extension calls
	 *
	 * @param string $code
	 * @param callable $callable
	 * @return callable
	 */
	private function _register_factory($code, $callable) {
		$old_factory = isset($this->factories[$code]) ? $this->factories[$code] : null;
		$this->factories[$code] = $callable;
		$this->application->logger->debug("Adding factory for {code}", array(
			"code" => $code,
		));
		return $old_factory;
	}

	/**
	 * Register a factory function
	 *
	 * @param string $code
	 * @param callable $callable
	 * @return callable
	 */
	final public function register_factory($code, $callable) {
		return $this->_register_factory($code . '_factory', $callable);
	}

	/**
	 * Register a factory function.
	 * Returns previous factory registered if ya want to use it.
	 *
	 * @param string $code
	 * @param callable $callable
	 * @return callable
	 */
	final public function register_registry($code, $callable) {
		return $this->_register_factory($code . '_registry', $callable);
	}

	/**
	 * Support foo_factory and foo_registry calls
	 *
	 * @param string $name
	 *        	Method called
	 * @return \object
	 */
	final public function __call($name, array $args) {
		if (isset($this->factories[$name])) {
			array_unshift($args, $this);
			return call_user_func_array($this->factories[$name], $args);
		}
		$suffix = "_module";
		if (ends($name, $suffix)) {
			return $this->modules->object(substr($name, 0, -strlen($suffix)));
		}

		throw new Exception_Unsupported("Application call {method} is not supported.\n\n\tCalled from: {calling}\n\nDo you ned to register the module which adds this functionality?\n\nAvailable: {available}", array(
			"method" => $name,
			"calling" => calling_function(),
			"available" => implode(", ", array_keys($this->factories)),
		));
	}

	/**
	 *
	 * @deprecated 2017-12
	 * @param mixed $add
	 * @see orm_classes
	 */
	final public function classes($add = null) {
		$this->deprecated();
		return $this->orm_classes($add);
	}

	/**
	 * Create an ORM
	 *
	 * @deprecated 2017-12 Blame PHP 7.2
	 * @param string $class
	 * @param array $options
	 * @todo Pass application as part of creation call
	 * @return ORM
	 */
	public function object_factory($class, $mixed = null, array $options = array()) {
		$this->deprecated();
		return ORM::factory($this, $class, $mixed, $options);
	}

	/**
	 *
	 * Access a class_object
	 *
	 * @deprecated 2017-12 use $this->class_orm_registry($class)
	 * @return Class_ORM
	 */
	public function class_object($class) {
		$this->deprecated();
		return $this->class_orm_registry($class);
	}

	/**
	 * Retrieve the database for a specific object class
	 *
	 * @deprecated 2017-12
	 * @param string $class
	 * @return \zesk\Database
	 */
	final public function class_object_database($class) {
		$this->deprecated();
		return $this->class_orm($class)->database();
	}

	/**
	 * Return the application root path.
	 *
	 * @param string $suffix
	 *        	Optional path to add to the application path
	 * @return string
	 * @deprecated 2017-10
	 * @see self::path()
	 */
	final public function application_root($suffix = null) {
		$this->deprecated();
		return $this->paths->application($suffix);
	}

	/**
	 * Retrieve object or classes from cache
	 *
	 * @deprecated immediately
	 * @param string $class
	 * @param string $component
	 *        	Optional component to retrieve
	 * @throws Exception_Semantics
	 * @return Ambigous <mixed, array>
	 */
	public function _class_cache($class, $component = "") {
		$this->kernel->obsolete();
	}

	/**
	 * Access an ORM by class name
	 *
	 * @deprecated 2017-12 Use ->orm_registry($class, $mixed, $options) instead.
	 *
	 * @return ORM
	 */
	final public function object($class, $mixed = null, $options = null) {
		$this->deprecated();
		return $this->orm_registry($class, $mixed, $options);
	}

	/**
	 * Determine object table name based on class and optional initialization parameters
	 *
	 * @deprecated 2017-12 Use ->orm_registry($class, $mixed, $options)->table() instead.
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return string|\zesk\Ambigous
	 */
	final public function object_table_name($class, $mixed = null, $options = null) {
		$this->deprecated();
		return $this->object($class, $mixed, $options)->table();
	}

	/**
	 * Determine object table columns based on class and optional initialization parameters
	 *
	 * @deprecated 2017-12 Use ->orm_registry($class, $mixed, $options)->columns() instead.
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return string|\zesk\Ambigous
	 */
	final public function object_table_columns($class, $mixed = null, $options = null) {
		$this->deprecated();
		return $this->object($class, $mixed, $options)->columns();
	}

	/**
	 * Determine object database based on class and optional initialization parameters
	 *
	 * @deprecated 2017-12 Use ->orm_registry($class, $mixed, $options)->database() instead.
	 * @param string $class
	 * @param mixed $mixed
	 * @param array $options
	 * @return \zesk\Database
	 */
	final public function object_database($class, $mixed = null, $options = null) {
		$this->deprecated();
		return $this->object($class, $mixed, $options)->database();
	}

	/**
	 *
	 * @deprecated 2017-12 Use ->orm_registry($class)->query_select($alias) instead.
	 * @return Database_Query_Select
	 */
	public function query_select($class, $alias = null) {
		$this->deprecated();
		return $this->object($class)->query_select($alias);
	}

	/**
	 *
	 * @deprecated 2017-12 Use ->orm_registry($class)->query_update($alias) instead.
	 * @return Database_Query_Update
	 */
	public function query_update($class, $alias = null) {
		$this->deprecated();
		return $this->object($class)->query_update($alias);
	}

	/**
	 *
	 * @deprecated 2017-12 Use ->orm_registry($class)->query_insert() instead.
	 *
	 * @return Database_Query_Insert
	 */
	public function query_insert($class) {
		$this->deprecated();
		return $this->object($class)->query_insert();
	}

	/**
	 *
	 * @deprecated 2017-12 Use ->orm_registry($class)->query_insert_select($alias) instead.
	 * @return Database_Query_Insert
	 */
	public function query_insert_select($class, $alias = null) {
		$this->deprecated();
		return $this->object($class)->query_insert_select($alias);
	}

	/**
	 *
	 * @deprecated 2017-12 Use ->orm_registry($class)->query_delete() instead.
	 * @return Database_Query_Delete
	 */
	public function query_delete($class) {
		$this->deprecated();
		return $this->object($class)->query_delete();
	}

	/**
	 * Access a Class_ORM
	 *
	 * @deprecated 2017-12 use class_orm_registry
	 * @return Class_ORM
	 */
	public function class_orm($class) {
		$this->deprecated();
		return $this->class_orm_registry($class);
	}

	/**
	 * Retrieve the database for a specific object class
	 *
	 * @deprecated 2017-12 use orm_regsitry($class)->database()
	 * @param string $class
	 * @return \zesk\Database
	 */
	final public function class_orm_database($class) {
		$this->deprecated();
		return $this->orm_registry($class)->database();
	}

	/**
	 *
	 * @deprecated 2017-12 use $this->orm_registry()->clear_cache();
	 * @param unknown $class
	 * @throws Exception_Parameter
	 */
	public function clear_class_cache($class = null) {
		$this->deprecated();
		return $this->orm_module()->clear_cache($class);
	}

	/**
	 *
	 * @deprecated 2017-12
	 * @see Module_ORM
	 * @param unknown $add
	 */
	final public function orm_classes($add = null) {
		$this->deprecated();
		return $this->orm_module()->orm_classes($add);
	}

	/**
	 * Retrieve all classes with additional fields
	 *
	 * @todo move ORM related to hooks
	 * @deprecated 2017-12
	 * @see Module_ORM
	 *
	 * @return array
	 */
	final public function all_classes() {
		$this->deprecated();
		return $this->orm_module()->all_classes();
	}

	/**
	 * Synchronzie the schema.
	 * TODO move this elsewhere
	 *
	 * @deprecated 2017-12
	 * @see Module_ORM::schema_synchronize
	 * @return multitype:
	 */
	public function schema_synchronize(Database $db = null, array $classes = null, array $options = array()) {
		$this->deprecated();
		return $this->orm_module()->schema_synchronize($db, $classes, $options);
	}

	/**
	 * Default include path
	 *
	 * @deprecated 2018-01
	 * @return array
	 */
	private function default_include_path() {
		$list = array_unique(array(
			'/etc',
			$this->zesk_home('etc'),
			$this->path('etc'),
		));
		return $list;
	}

	/**
	 * Return the zesk root path.
	 *
	 * @deprecated 2018-01 Use self::zesk_home
	 * @param string $suffix
	 *        	Optional path to add to the application path
	 * @return string
	 */
	final public function zesk_root($suffix = null) {
		zesk()->deprecated();
		return $this->zesk_home($suffix);
	}

	/**
	 *
	 * @deprecated 2017-12
	 * @param string $uri
	 * @return string
	 */
	public function url($uri) {
		$this->deprecated();
		// TODO Remove this
		return $uri;
	}
}
