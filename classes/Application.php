<?php

/**
 * @package zesk
 * @subpackage core
 * @author kent
 * @copyright Copyright &copy; 2018, Market Acumen, Inc.
 */
namespace zesk;

use Psr\Cache\CacheItemPoolInterface;

/**
 * Core web application object for Zesk.
 *
 * If you're doing something useful, it's probably a simple application.
 *
 * @author kent
 *
 * @method ORM orm_factory($class, $mixed, array $options = array())
 * @method Widget widget_factory($class, array $options = array())
 *
 * @method Database database_registry($name)
 * @method Class_ORM class_orm_registry($class = null)
 * @method ORM|Module_ORM orm_registry($class = null, $mixed = null, array $options = null)
 * @method Module_ORM orm_module()
 */
class Application extends Hookable implements Interface_Theme, Interface_Factory {

	/**
	 * Probably should discourage use of this.
	 * Zesk singleton.
	 *
	 * @var Kernel
	 */
	public $zesk = null;

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
	 * @var Command
	 */
	public $command = null;

	/**
	 *
	 * @var Request
	 */
	public $request = null;

	/**
	 *
	 * @var Router
	 */
	public $router = null;

	/**
	 *
	 * @var Route
	 */
	public $route = null;

	/**
	 *
	 * @var Response_Text_HTML
	 */
	public $response = null;

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
	 *
	 * @var array
	 */
	protected $class_cache = array();

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
	 * @var Interface_Session
	 */
	public $session = null;

	/**
	 *
	 * @var User
	 */
	public $user = null;

	/**
	 * Variables for templates
	 *
	 * @var unknown_type
	 */
	private $variables = array();

	/**
	 * Array of calls to create stuff
	 *
	 * @var Closure[string]
	 */
	private $factories = array();

	/**
	 * Array of classes to register hooks automatically
	 *
	 * @var array of string
	 */
	protected $register_hooks = array();

	/**
	 * Array of starting list of model subclasses which are a part of this application.
	 * Used to sync schema and generate dependency classes.
	 *
	 * @var array of string
	 */
	protected $model_classes = array();

	/**
	 * Configuration files to include
	 *
	 * @var array of string
	 */
	protected $includes = array();

	/**
	 * Configuration file paths to search
	 *
	 * @var array of string
	 */
	protected $include_paths = array();

	/**
	 * Configuration options
	 *
	 * @var array
	 */
	static $configuration_options = null;

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
	 * @var string $theme_path
	 */
	protected $theme_path = array();

	/**
	 * Paths to search for shared content
	 *
	 * @var string[]
	 */
	protected $share_path = array();

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
	 * Template stack
	 *
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
	 * @param unknown $options
	 */
	public function __construct(Kernel $zesk, array $options = array()) {
		parent::__construct($this, $options);
		$this->_initialize($zesk, $options);
	}

	/**
	 *
	 * @param array $options
	 * @throws Exception_Unimplemented
	 */
	protected function _initialize(Kernel $zesk, array $options = array()) {
		$this->zesk = $zesk;
		$this->paths = $zesk->paths;
		$this->hooks = $zesk->hooks;
		$this->autoloader = $zesk->autoloader;
		$this->configuration = $zesk->configuration;
		$this->cache = $zesk->cache;
		$this->logger = $zesk->logger;
		$this->classes = $zesk->classes;
		$this->objects = $zesk->objects;
		$this->process = $zesk->process;

		$this->module_path = array();
		$this->zesk_command_path = array();
		$this->theme_path = array();
		$this->share_path = array();
		$this->cache_path = null;
		$this->document = null;
		$this->document_prefix = '';
		$this->document_cache = null;
		$this->template_stack = null;
		$this->template = null;
		$this->theme_stack = null;
		$this->configured_was_run = false;

		$this->factories = array();

		$this->command = null;
		$this->request = null;
		$this->router = null;
		$this->route = null;
		$this->response = null;
		$this->session = null;

		$this->class_cache = array();

		// $this->load_modules is set in subclasses
		// $this->class_aliases is set in subclasses
		// $this->file is set in subclasses
		// $this->variables is set in subclasses
		// $this->register_hooks is set in subclasses
		// $this->model_classes is set in subclasses
		//

		// $this->includes is set in subclasses?
		// $this->include_paths is set in subclasses?
		// $this->template_variables is set in application itself?
		$this->template_variables = array();

		foreach ($this->class_aliases as $requested => $resolved) {
			$this->objects->map($requested, $resolved);
		}

		$this->_init_document_root();

		$this->zesk_command_path = array(
			ZESK_ROOT . 'command' => 'zesk\Command_'
		);
		if (is_array($this->modules)) {
			throw new Exception_Unimplemented("Application::\$modules no longer supported");
		}

		$this->module_path($this->path_module_default());

		$this->modules = new Modules($this);

		$this->template_stack = new Template_Stack();
		$this->template = new Template($this);
		$this->template_stack->push($this->template);
		$this->theme_stack = array();

		$this->theme_path($this->path_theme_default());
		$this->share_path($this->path_share_default(), 'zesk');
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
	 * Clone application
	 */
	protected function __clone() {
		if ($this->request) {
			$this->request = clone $this->request;
		}
		if ($this->response) {
			$this->response = clone $this->response;
		}
		if ($this->router) {
			$this->router = clone $this->router;
		}
		if ($this->route) {
			$this->route = clone $this->route;
		}
		if ($this->template) {
			$this->template = clone $this->template;
		}
		if ($this->template_stack) {
			$this->template_stack = clone $this->template_stack;
		}
	}

	/**
	 * Override in subclasses if it is stored in a different way.
	 *
	 * @return mixed|string|array
	 */
	public function version() {
		return $this->option("version");
	}

	/**
	 * Getter/setter to configure a file name to load (from path)
	 *
	 * @param mixed $includes
	 * @return Application
	 */
	final public function configure_include($includes = null) {
		if ($includes === null) {
			return $this->includes;
		}
		$includes = to_list($includes);
		foreach ($includes as $include) {
			$this->includes[$include] = $include;
		}
		return $this;
	}

	/**
	 * Add a path to load configuration files from, or return currentl path list
	 *
	 * @param string $path
	 * @return Application|array
	 */
	final public function configure_include_path($path = null) {
		if ($path === null) {
			return $this->include_paths;
		}
		foreach (to_list($path) as $path) {
			if (!is_dir($path)) {
				$this->logger->error("{class}::{method}: {path} is not a valid directory, ignoring", array(
					"path" => $path,
					"class" => get_class($this),
					"method" => __METHOD__
				));
				continue;
			}
			$this->include_paths[$path] = $path;
		}
		return $this;
	}

	/**
	 * Loads a bunch of configuration files, in the following order:
	 * 1.
	 * application.conf
	 * 2. APPLICATION_NAME.conf
	 * 3. uname.conf
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
		if (self::$configuration_options !== null) {
			$this->logger->warning("Reconfiguring application {class}", array(
				"class" => get_class($this)
			));
		}
		$this->configuration->deprecated("Application::configure_options", __CLASS__ . "::configure_options");
		self::$configuration_options = $options + to_array($this->configuration->path(__CLASS__)->configure_options);
		$this->_configure(self::$configuration_options);
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
			$this->configure_include(avalue($options, 'file', $this->default_includes()));
		}
		if (count($this->include_paths) === 0 || array_key_exists('path', $options)) {
			$this->configure_include_path(avalue($options, 'path', $this->default_include_path()));
		}
		$this->loader = new Configuration_Loader($this->include_paths, $this->includes, new Adapter_Settings_Configuration($configuration));

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
		$this->hooks->register_class(array(
			Database::class
		));
		$this->hooks->register_class($this->register_hooks);

		$this->call_hook('configure');

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
		$result = $this->_configure_files($options);
		if ($profile) {
			$this->zesk->profile_timer("_configure_files", microtime(true) - $mtime);
		}

		$this->call_hook('configured_files');

		$this->modules->load($this->load_modules);

		// Reload application options
		$this->inherit_global_options();

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

		return $result;
	}
	/**
	 *
	 * @return boolean
	 */
	public function is_configured() {
		return $this->configured_was_run;
	}
	public function configured() {
		if (!$this->configured_was_run) {
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
			$this->document_cache = $this->option('document_cache');
		}
	}

	/**
	 */
	private function configured_hooks() {
		$hook_callback = $result_callback = null;

		$this->hooks->call_arguments(Hooks::hook_database_configure, array(
			$this
		), null, $hook_callback, $result_callback);
		$this->hooks->call_arguments(Hooks::hook_configured, array(
			$this
		), null, $hook_callback, $result_callback); // System level
		$this->modules->all_hook_arguments("configured", array(), null, $hook_callback, $result_callback); // Modules
		$this->call_hook_arguments('configured', array(), null, $hook_callback, $result_callback); // Application level
	}

	/**
	 * Runs configuration again, using same options as previous configuration.
	 *
	 * @see Application::configure
	 */
	public function reconfigure() {
		$this->_initialize($this->zesk);
		$result = $this->_configure(to_array(self::$configuration_options));
		$this->_configured();
		return $result;
	}

	/**
	 * Clear application cache
	 */
	final public function cache_clear() {
		foreach (array_unique(array(
			$this->paths->cache(),
			$this->cache_path(),
			$this->document_cache()
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
			"cache_clear_hooks" => $this->format_hooks($hooks)
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
			return file_exists($maintenance_file);
		}
		$result = $this->call_hook_arguments("maintenance", array(
			$set
		), true);
		if (!$result) {
			$this->logger->error("Unable to set application {application_class} maintenance mode to {value}", array(
				"application_class" => get_class($this),
				"value" => $set ? "true" : "false"
			));
			return null;
		}
		if ($set) {
			$context = array(
				"time" => date('Y-m-d H:i:s')
			) + $this->call_hook_arguments("maintenance_context", array(
				array(
					"value" => $set
				)
			), array());
			file_put_contents($this->maintenance_file(), json_encode($context));
		} else if (file_exists($maintenance_file)) {
			unlink($maintenance_file);
			clearstatcache(true, $maintenance_file);
		}
		return $result;
	}

	/**
	 * Return file, which when exists, puts the site into maintenance mode
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
		$object = $this->call_hook_arguments("singleton_$class", $args, null);
		if ($object instanceof $class) {
			return $object;
		}
		return $this->objects->singleton_arguments($class, $args);
	}

	/**
	 * Default include path
	 *
	 * @return array
	 */
	private function default_include_path() {
		$list = array_unique(array(
			'/etc',
			$this->zesk_root('etc'),
			$this->path('etc')
		));
		return $list;
	}

	/**
	 * Default list of files to be loaded as part of this application configuration
	 *
	 * @return array
	 */
	private function default_includes() {
		$files_default = array();
		$files_default[] = 'application.conf';
		if (defined('APPLICATION_NAME')) {
			$files_default[] = APPLICATION_NAME . '.conf';
		}
		$files_default[] = strtolower(System::uname()) . ".conf";
		return $files_default;
	}

	/**
	 *
	 * @return Request
	 */
	protected function hook_request() {
		$request = new Request($this);
		if ($this->console()) {
			$request->initialize_from_settings("http://console/");
		} else {
			$request->initialize_from_globals();
		}
		return $request;
	}

	/**
	 *
	 * @return Request
	 */
	protected function hook_response(Request $request) {
		return Response::instance($this);
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
				"router_file" => $router_file
			));
			$result = $router;
		} else if (($result = $router->cached(filemtime($router_file))) === null) {
			$router->import(file_get_contents($router_file), array(
				"_source" => $router_file
			));
			if ($cache) {
				$router->cache();
			}
			$result = $router;
		}
		$this->modules->all_hook("routes", $result);
		return $result;
	}

	/**
	 * When an exception happens in the main loop, generate content related to the exception.
	 *
	 * @param Exception $e
	 */
	final private function _main_exception(\Exception $exception) {
		$content = $this->theme($this->zesk->classes->hierarchy($exception), array(
			"exception" => $exception,
			"content" => $exception
		), array(
			"first" => true
		));
		if ($this->response && $this->response->content_type === "text/html") {
			$this->response->content = $content;
			// 			$this->theme('page', array(
			// 				'content' => $content
			// 			) + $this->template_variables);
		}
		$this->call_hook('main_exception', $exception);
		$this->hooks->call("exception", $exception);
	}

	/**
	 *
	 * @return Request
	 */
	final public function request() {
		if ($this->request instanceof Request) {
			return $this->request;
		}
		return $this->request = $this->call_hook("request");
	}

	/**
	 *
	 * @return Response
	 */
	final public function response() {
		if ($this->response instanceof Response) {
			return $this->response;
		}
		return $this->response = $this->call_hook("response", $this->request());
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
		try {
			/* @var $request Request */
			$request = $this->request();

			// TODO Investigate creating response via Router instead of here
			/* @var $response Response_Text_HTML */
			$response = $this->response();

			/* @var $router Router */
			$router = $this->router = $this->call_hook("router");
			$this->call_hook("router_loaded", $router);

			return $router;
		} catch (\Exception $exception) {
			$this->_main_exception($exception);
			return null;
		}
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
	private function _templates_initialize() {
		$variables = array();
		$variables['application'] = $this;
		$variables['request'] = $this->request;
		$variables['response'] = $this->response;
		$variables['router'] = $this->router;
		$variables['route'] = $this->route;
		$variables += $this->template_variables;
		$variables += $this->call_hook_arguments("template_defaults", array(
			$variables
		), array());
		$this->template->set($variables);
		return $this->template;
	}

	/**
	 * Initialize template variables
	 *
	 * Execute route
	 *
	 * @param Router $router
	 */
	private function _main_route(Router $router) {
		$this->call_hook("router_prematch", $router);
		$this->route = $router->match($this->request);
		$this->_templates_initialize();
		if (!$this->route) {
			$this->call_hook("router_no_match");
			$this->response->status(Net_HTTP::Status_File_Not_Found, "No route found");
			throw new Exception_NotFound("The resource does not exist on this server: {url}", $this->request->url_variables());
		}
		if ($this->option_bool("debug_route")) {
			$this->logger->debug("Matched route {class} Pattern: \"{clean_pattern}\" {options}", $this->route->variables());
		}
		$this->call_hook("router_matched", $router, $this->route);
		$router->execute($this->request);
		$this->call_hook('router_postprocess', $router);
	}

	/**
	 * Application main execution:
	 *
	 * - Load the router
	 * - Find a matched route
	 * - Execute it
	 * - Render response
	 */
	public function main() {
		$this->call_hook("main");

		$this->variables = array();

		if (($router = $this->router()) !== null) {
			try {
				$this->logger->debug("App bootstrap took {seconds} seconds", array(
					"seconds" => sprintf("%.3f", microtime(true) - $this->zesk->initialization_time)
				));
				$this->_main_route($router);
			} catch (Exception $exception) {
				$this->_main_exception($exception);
			}
			$this->response->output();
		} else if ($this->response) {
			$this->response->output();
		}
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
		$old_request = $this->request;
		$old_response = $this->response;
		$old_route = $router->route;

		if ($old_request) {
			$url = $old_request->url();
		} else {
			$url = "http://localhost/";
		}
		$url = rtrim(URL::left_host($url), "/") . $path;
		$this->request = new Request($this);
		$this->request->initialize_from_settings(array(
			"url" => $url,
			"method" => Net_HTTP::Method_GET,
			"data" => "",
			"variables" => URL::query_parse_url($path)
		));
		$this->response = Response::factory($this);

		ob_start();
		$this->_main_route($this->router->reset());
		$this->response->output(array(
			"skip-headers" => true
		));
		$content = ob_get_clean();

		$this->router->route = $old_route;
		$this->route = $old_route;
		$this->request = $old_request;
		$this->response = $old_response;

		unset($this->content_recursion[$path]);

		return $content;
	}

	/**
	 * Hook for taking old `.php` URLs and converting to router-based URLs
	 *
	 * @param Model_URL $state
	 * @return true
	 */
	public static function hook_url_php(Model_URL $state) {
		if (URL::valid($state->url)) {
			return true;
		}
		list($u, $qs) = pair($state->url, '?', $state->url, '');
		if (!str::ends($u, "/")) {
			$u .= ".php";
		}
		if ($u[0] !== '/') {
			$u = "/$u";
		}
		$state->url = URL::query_append($u, $qs);
		return true;
	}

	/**
	 * Get a list of repositories for this application (dependencies)
	 *
	 * @return array
	 */
	public function repositories() {
		$repos = array(
			'zesk' => $this->zesk_root(),
			get_class($this) => $this->path()
		);
		return $this->call_hook_arguments("repositories", array(
			$repos
		), $repos);
	}

	/**
	 * Utility for index.php file for all public-served content.
	 */
	public function index() {
		$final_map = array();

		$request = $this->request();
		if (($content = Response::cached($request->url())) === null) {
			ob_start();
			$this->main();
			$content = ob_get_clean();
			$final_map['{page-is-cached}'] = '0';
		} else {
			$this->hooks->unhook('exit');
			$final_map['{page-is-cached}'] = '1';
		}
		$final_map += array(
			"{page-render-time}" => sprintf("%.3f", microtime(true) - $this->zesk->initialization_time)
		);
		if ($this->response) {
			$this->response->cache_save($content);
		}
		if (!$this->response || $this->response->is_content_type(array(
			"text/",
			"javascript"
		))) {
			$content = strtr($content, $final_map);
		}
		echo $content;
	}

	/**
	 * Template or logging variables
	 *
	 * @return Router
	 */
	public function variables() {
		$parameters['application'] = $this;
		$parameters['request'] = $request = $this->request;
		$parameters['response'] = $this->response;
		$parameters['router'] = $this->router;
		$parameters['route'] = $this->route;
		$parameters['url'] = $request ? $this->request->url() : null;
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
		return $result;
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
	 * @return string[string]
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
						"path" => $path
					));
				}
				if (!isset($this->zesk_command_path[$path])) {
					$this->zesk_command_path[$path] = $prefix;
				} else if ($debug) {
					$this->logger->debug("{method}: did not add \"{path}\" (prefix {prefix}) because it already exists", array(
						"method" => __METHOD__,
						"path" => $path,
						"prefix" => $prefix
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
	 *
	 * @param unknown $name
	 * @param unknown $value
	 * @return mixed|self
	 */
	final public function theme_variable($name = null, $value = null) {
		if ($name === null) {
			return $this->template_stack->top()->variables();
		}
		if ($value === null) {
			return $this->template_stack->top()->get($name);
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
				"content" => $arguments
			);
		}
		$arguments['application'] = $this;
		$types = to_list($types);
		$extension = avalue($options, "no_extension") ? null : ".tpl";
		if (count($types) === 1) {
			$result = $this->_theme_arguments($types[0], $arguments, null, $extension);
			if ($result === null) {
				$this->logger->warning("Theme {type} had no output", array(
					"type" => $types[0]
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
		if ($content !== null) {
			$arguments['content'] = $content;
			$has_output = true;
		}
		$first = avalue($options, 'first', false);
		$concatenate = avalue($options, 'concatenate', false);
		while (count($types) > 0) {
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
			$this->logger->warning("Theme {types} had no output ({caller})", array(
				"types" => $types,
				"caller" => calling_function(1)
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
		return preg_replace("%[^-_./a-zA-Z0-9]%", '_', strtr($path, array(
			"_" => "/",
			"\\" => "/"
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
		{
			$object = avalue($args, "content");
			if (is_object($object)) {
				if (method_exists($object, "hook_theme")) {
					$result = $object->call_hook_arguments("theme", array(
						$args,
						$content
					), $content);
					array_pop($this->theme_stack);
					return $result;
				}
				if (method_exists($object, 'variables')) {
					$args += $object->variables();
				}
			} else {
				$object = null;
			}
			$template_name = $this->clean_template_path($type) . $extension;
			$t = new Template($this, $template_name, $args);
			if ($t->exists()) {
				$content = $t->render();
			}
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
		return $this->zesk->autoloader->path($add, $options);
	}

	/**
	 * Set command path for the application.
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
		return $this->zesk->application_class();
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
	 * Return the zesk root path.
	 *
	 * @param string $suffix
	 *        	Optional path to add to the application path
	 * @return string
	 */
	final public function zesk_root($suffix = null) {
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
	 * To ensure all URLs are generated correctly, you can set $this->document_root_prefixstring) to
	 * set
	 * a portion of the URL which is always prefixed to any generated url.
	 *
	 * @param string $set
	 *        	Optionally set the web root
	 * @throws Exception_Directory_NotFound
	 * @return string The directory
	 */
	final public function document_root($set = null, $prefix = null) {
		if ($set !== null) {
			$this->deprecated("Convert " . __METHOD__ . " method to append-style method, use set_document_root()");
			$this->set_document_root($set, $prefix);
		}
		return $this->document;
	}

	/**
	 * Your web root is the directory in the file system which contains our application and other
	 * files.
	 *
	 * It may be served from an aliased or shared directory and as such may not appear at the web
	 * server's root.
	 *
	 * To ensure all URLs are generated correctly, you can set $this->web_root_prefix(string) to set
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
	 * Retrieve the database for this application.
	 * This call is meant to deprecate the global Database::factory eventually.
	 *
	 * @param string $mixed
	 *        	Name or URL
	 * @param array $options
	 *        	Options for the database
	 * @return Database
	 */
	public function database_factory($mixed = null, array $options = array()) {
		// Call non-deprecated version, for now. Move this elsewhere?
		return Database::_factory($this, $mixed, $options);
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
	 * This loads an include without any variables defined, except super globals Handy when the file
	 * is meant to return a value, or has its own "internal" variables which may corrupt the global
	 * or current scope of a function, for example.
	 *
	 * @param string $file
	 * @return mixed
	 */
	public function load($file) {
		return $this->zesk->load($file);
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
	 * @param string $require
	 *        	Throw exception if no session found
	 * @throws Exception_NotFound
	 * @return \zesk\Interface_Session|NULL
	 */
	public function session($require = true) {
		if ($this->session) {
			return $this->session;
		}
		$this->session = Session::factory($this);
		if (!$this->session) {
			if ($require) {
				throw new Exception_NotFound("No session");
			}
			return null;
		}
		return $this->session;
	}

	/**
	 *
	 * @return User
	 */
	public function user($require = true) {
		$user_class = $this->objects->resolve(User::class);
		if ($this->user instanceof $user_class) {
			return $this->user;
		}
		try {
			return $this->user = $this->session()->user();
		} catch (\Exception $e) {
			if ($require) {
				throw $e;
			}
		}
		return $this->user = null;
	}

	/**
	 *
	 * @param string $message
	 * @param array $arguments
	 * @return void
	 */
	public function deprecated($message = null, array $arguments = array()) {
		$arguments['depth'] = to_integer(avalue($arguments, 'depth', 0)) + 1;
		$this->zesk->deprecated($message, $arguments);
	}

	/**
	 * Console status getter/setter
	 *
	 * @param boolean $set
	 * @return boolean
	 */
	public function console($set = null) {
		return $this->zesk->console($set);
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

	/**
	 *
	 * @return double Microseconds initialization time
	 */
	final public function initialization_time() {
		return $this->zesk->initialization_time;
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
		throw new Exception_Unsupported("Application call {method} is not supported. Do you need to register the module which adds this functionality?", array(
			"method" => $name
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
	public final function class_object_database($class) {
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
		$this->zesk->obsolete();
	}

	/**
	 * Access an ORM by class name
	 *
	 * @deprecated 2017-12 Use ->orm_registry($class, $mixed, $options) instead.
	 *
	 * @return ORM
	 */
	public final function object($class, $mixed = null, $options = null) {
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
	public final function object_table_name($class, $mixed = null, $options = null) {
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
	public final function object_table_columns($class, $mixed = null, $options = null) {
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
	public final function object_database($class, $mixed = null, $options = null) {
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
	public final function class_orm_database($class) {
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
		return $this->orm_registry()->clear_cache($class);
	}

	/**
	 *
	 * @deprecated 2017-12
	 * @see Module_ORM
	 * @param unknown $add
	 */
	final public function orm_classes($add = null) {
		$this->deprecated();
		return $this->modules->object("orm")->orm_classes($add);
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
		return $this->modules->object("orm")->all_classes();
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
		return $this->modules->object("orm")->schema_synchronize($db, $classes, $options);
	}
}

